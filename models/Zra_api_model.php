<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Zra_api_model extends CI_Model
{
    private $api_url;
    private $company_tin;
    private $branch_id;
    private $device_serial;
    private $timeout;
    private $debug_mode;
    private $success_code;

    public function __construct()
    {
        parent::__construct();
        $this->load_configuration();
        $this->ensure_log_table_exists();
    }

    private function load_configuration()
    {
        // API Configuration based on ZRA VSDC API Specification v1.0.7
        $this->api_url = get_option('zra_api_url') ?: 'https://localhost:8080/zravsdc';
        $this->company_tin = get_option('zra_company_tin'); // TPIN (VARCHAR 10)
        $this->branch_id = get_option('zra_branch_id') ?: '000'; // bhfId (VARCHAR 3) - Supplied by ZRA at registration
        $this->device_serial = get_option('zra_device_serial');
        $this->timeout = get_option('zra_timeout') ?: 30;
        $this->debug_mode = get_option('zra_debug_mode') == '1';
        
        // VSDC Response Codes
        $this->success_code = '000'; // Standard success code per ZRA specification
    }

    /**
     * Submit invoice to ZRA Smart Invoice API using official VSDC specification
     */
    public function submit_invoice($invoice_id)
    {
        $debugFile = APPPATH . 'logs/zra_submit_invoice_debug.log';
        $tempFile = sys_get_temp_dir() . '/zra_submit_invoice_error.log';
        @file_put_contents($debugFile, date('Y-m-d H:i:s') . ' MODEL ENTRY invoice_id=' . var_export($invoice_id, true) . "\n", FILE_APPEND);
        @file_put_contents($tempFile, date('Y-m-d H:i:s') . ' MODEL ENTRY invoice_id=' . var_export($invoice_id, true) . "\n", FILE_APPEND);

        if (!get_option('zra_enabled')) {
            $result = ['success' => false, 'message' => 'ZRA integration is disabled'];
            @file_put_contents($debugFile, date('Y-m-d H:i:s') . ' MODEL RESULT ' . json_encode($result) . "\n", FILE_APPEND);
            @file_put_contents($tempFile, date('Y-m-d H:i:s') . ' MODEL RESULT ' . json_encode($result) . "\n", FILE_APPEND);
            return $result;
        }

        try {
            // Get invoice data
            @file_put_contents($debugFile, date('Y-m-d H:i:s') . ' MODEL LOADING invoices_model\n', FILE_APPEND);
            @file_put_contents($tempFile, date('Y-m-d H:i:s') . ' MODEL LOADING invoices_model\n', FILE_APPEND);
            $this->load->model('invoices_model');
            @file_put_contents($debugFile, date('Y-m-d H:i:s') . ' MODEL LOADED invoices_model\n', FILE_APPEND);
            @file_put_contents($tempFile, date('Y-m-d H:i:s') . ' MODEL LOADED invoices_model\n', FILE_APPEND);
            $invoice = $this->invoices_model->get($invoice_id);
            @file_put_contents($debugFile, date('Y-m-d H:i:s') . ' MODEL GOT invoice=' . var_export($invoice ? $invoice->id : null, true) . "\n", FILE_APPEND);
            @file_put_contents($tempFile, date('Y-m-d H:i:s') . ' MODEL GOT invoice=' . var_export($invoice ? $invoice->id : null, true) . "\n", FILE_APPEND);
            
            if (!$invoice) {
                $result = ['success' => false, 'message' => 'Invoice not found'];
                @file_put_contents($debugFile, date('Y-m-d H:i:s') . ' MODEL RESULT ' . json_encode($result) . "\n", FILE_APPEND);
                @file_put_contents($tempFile, date('Y-m-d H:i:s') . ' MODEL RESULT ' . json_encode($result) . "\n", FILE_APPEND);
                return $result;
            }

            // Check if already submitted
            $existing_log = $this->get_invoice_log($invoice_id, 'success');
            if ($existing_log) {
                return ['success' => false, 'message' => 'Invoice already submitted to ZRA'];
            }

            // Prepare invoice data for ZRA
            @file_put_contents($debugFile, date('Y-m-d H:i:s') . ' MODEL PREPARE_INVOICE_DATA\n', FILE_APPEND);
            @file_put_contents($tempFile, date('Y-m-d H:i:s') . ' MODEL PREPARE_INVOICE_DATA\n', FILE_APPEND);
            $zra_data = $this->prepare_invoice_data($invoice);
            @file_put_contents($debugFile, date('Y-m-d H:i:s') . ' MODEL PREPARE_INVOICE_DATA_DONE\n', FILE_APPEND);
            @file_put_contents($tempFile, date('Y-m-d H:i:s') . ' MODEL PREPARE_INVOICE_DATA_DONE\n', FILE_APPEND);
            if (isset($zra_data['success']) && $zra_data['success'] === false) {
                @file_put_contents($debugFile, date('Y-m-d H:i:s') . ' MODEL RESULT ' . json_encode($zra_data) . "\n", FILE_APPEND);
                @file_put_contents($tempFile, date('Y-m-d H:i:s') . ' MODEL RESULT ' . json_encode($zra_data) . "\n", FILE_APPEND);
                return $zra_data;
            }
            
            // Submit to ZRA API
            $response = $this->call_api('/trnsSales/saveSales', $zra_data);
            
            $status = 'failed';
            if (isset($response['success']) && $response['success']) {
                $status = 'success';
            } elseif ($this->is_offline_failure($response)) {
                $status = 'pending';
            }

            // Log the transaction with VSDC response format
            $log_data = [
                'invoice_id' => $invoice_id,
                'request_type' => 'invoice_submission',
                'request_data' => json_encode($zra_data),
                'response_data' => json_encode($response),
                'status' => $status,
                'error_code' => $response['resultCd'] ?? $response['error_code'] ?? null,
                'error_message' => $response['message'] ?? null,
                'zra_invoice_number' => $response['data']['invoice_number'] ?? null,
                'qr_code' => $response['data']['qr_code'] ?? null,
                'fiscal_tax_id' => $response['data']['fiscal_tax_id'] ?? null,
                'result_date' => $response['resultDt'] ?? null
            ];
            
            $this->log_transaction($log_data);
            @file_put_contents($debugFile, date('Y-m-d H:i:s') . ' MODEL RESPONSE_RECEIVED\n', FILE_APPEND);
            @file_put_contents($tempFile, date('Y-m-d H:i:s') . ' MODEL RESPONSE_RECEIVED\n', FILE_APPEND);
            @file_put_contents($debugFile, date('Y-m-d H:i:s') . ' MODEL RESULT ' . json_encode($response) . "\n", FILE_APPEND);
            @file_put_contents($tempFile, date('Y-m-d H:i:s') . ' MODEL RESULT ' . json_encode($response) . "\n", FILE_APPEND);
            return $response;
        } catch (\Throwable $th) {
            $message = 'ZRA API submit_invoice throwable: ' . $th->getMessage() . ' in ' . $th->getFile() . ' on line ' . $th->getLine();
            log_message('error', $message);
            log_message('error', $th->getTraceAsString());
            @file_put_contents($debugFile, date('Y-m-d H:i:s') . ' MODEL CATCH: ' . $message . "\n" . $th->getTraceAsString() . "\n\n", FILE_APPEND);
            @file_put_contents($tempFile, date('Y-m-d H:i:s') . ' MODEL CATCH: ' . $message . "\n" . $th->getTraceAsString() . "\n\n", FILE_APPEND);
            return ['success' => false, 'message' => 'Internal server error while submitting invoice'];
        }
    }

    public function submit_refund($invoice_id, $refund_data)
    {
        if (!get_option('zra_enabled')) {
            return ['success' => false, 'message' => 'ZRA integration is disabled'];
        }

        // Get original invoice ZRA data
        $original_log = $this->get_invoice_log($invoice_id, 'success');
        if (!$original_log) {
            return ['success' => false, 'message' => 'Original invoice not found in ZRA system'];
        }

        // Prepare refund data for ZRA
        $zra_refund_data = $this->prepare_refund_data($refund_data, $original_log);
        
        // Submit refund to ZRA API
        $response = $this->call_api('/trnsSales/saveRefund', $zra_refund_data);
        
        // Log the transaction
        $log_data = [
            'invoice_id' => $invoice_id,
            'request_type' => 'refund_submission',
            'request_data' => json_encode($zra_refund_data),
            'response_data' => json_encode($response),
            'status' => $response['success'] ? 'success' : 'failed',
            'error_code' => $response['error_code'] ?? null,
            'error_message' => $response['message'] ?? null
        ];
        
        $this->log_transaction($log_data);
        
        return $response;
    }

    public function test_api_connection()
    {
        return $this->initialize_device();
    }

    public function initialize_device()
    {
        if (!get_option('zra_enabled')) {
            return ['success' => false, 'message' => 'ZRA integration is disabled'];
        }

        if (empty($this->company_tin) || empty($this->branch_id) || empty($this->device_serial)) {
            return [
                'success' => false,
                'message' => 'Device initialization requires TPIN, branch ID, and device serial number'
            ];
        }

        $init_data = [
            'tpin' => $this->company_tin,
            'bhfId' => $this->branch_id,
            'dvcSrlNo' => $this->device_serial
        ];

        $response = $this->call_api('/initializer/selectInitInfo', $init_data);

        if ($response['success']) {
            update_option('zra_device_initialized', '1');
        }

        return $response;
    }

    public function get_sales_principals($data = [])
    {
        return $this->call_api('/trnsSales/selectPrincip', $data);
    }

    public function save_stock_items($data = [])
    {
        return $this->call_api('/stock/saveStockItems', $data);
    }

    public function save_stock_master($data = [])
    {
        return $this->call_api('/stockMaster/saveStockMaster', $data);
    }

    public function save_item($data = [])
    {
        return $this->call_api('/items/saveItem', $data);
    }

    public function get_standard_codes($data = [])
    {
        if (!get_option('zra_enabled')) {
            return ['success' => false, 'message' => 'ZRA integration is disabled'];
        }

        $payload = array_merge([
            'tpin' => $this->company_tin,
            'bhfId' => $this->branch_id
        ], $data);

        return $this->try_api_endpoints([
            '/master/selectStdCodes',
            '/master/selectStandardCodes',
            '/codes/getStandardCodes'
        ], $payload);
    }

    public function get_item_classification_codes($data = [])
    {
        if (!get_option('zra_enabled')) {
            return ['success' => false, 'message' => 'ZRA integration is disabled'];
        }

        $payload = array_merge([
            'tpin' => $this->company_tin,
            'bhfId' => $this->branch_id
        ], $data);

        return $this->try_api_endpoints([
            '/master/selectItemClassificationCodes',
            '/items/selectItemClsCd',
            '/items/selectItemClassificationCodes'
        ], $payload);
    }

    public function fetch_import_items($data = [])
    {
        if (!get_option('zra_enabled')) {
            return ['success' => false, 'message' => 'ZRA integration is disabled'];
        }

        $payload = array_merge([
            'tpin' => $this->company_tin,
            'bhfId' => $this->branch_id
        ], $data);

        return $this->try_api_endpoints([
            '/imports/selectImportItems',
            '/imports/getImportItems',
            '/imports/selectImports'
        ], $payload);
    }

    public function retrieve_purchases($data = [])
    {
        if (!get_option('zra_enabled')) {
            return ['success' => false, 'message' => 'ZRA integration is disabled'];
        }

        $payload = array_merge([
            'tpin' => $this->company_tin,
            'bhfId' => $this->branch_id
        ], $data);

        return $this->try_api_endpoints([
            '/trnsPurchases/selectPurchases',
            '/purchases/selectPurchases',
            '/trnsPurchases/getPurchaseList'
        ], $payload);
    }

    public function save_purchase($data = [])
    {
        return $this->call_api('/trnsPurchases/savePurchase', $data);
    }

    public function save_non_smart_supplier_purchase($data = [])
    {
        return $this->call_api('/trnsPurchases/saveNonSmartSupplierPurchase', $data);
    }

    public function save_item_composition($data = [])
    {
        return $this->call_api('/items/saveItemComposition', $data);
    }

    public function save_stock_adjustment($data = [])
    {
        return $this->call_api('/stock/saveStockAdjustments', $data);
    }

    public function retry_pending_submissions($limit = 50)
    {
        $results = [];

        if (!$this->log_table_exists()) {
            return $results;
        }

        $this->db->where_in('status', ['pending', 'failed']);
        $this->db->where('request_type', 'invoice_submission');
        $this->db->where('invoice_id >', 0);
        $this->db->order_by('id', 'ASC');
        $this->db->limit($limit);
        $logs = $this->db->get(db_prefix() . 'zra_invoicing_logs')->result();

        foreach ($logs as $log) {
            $results[$log->invoice_id] = $this->submit_invoice($log->invoice_id);
        }

        return $results;
    }

    public function update_item($data = [])
    {
        return $this->call_api('/items/updateItem', $data);
    }

    public function update_import_items($data = [])
    {
        return $this->call_api('/imports/updateImportItems', $data);
    }

    /**
     * Prepare invoice data for ZRA API submission according to VSDC specification
     */
    private function prepare_invoice_data($invoice)
    {
        $this->load->model('currencies_model');
        $this->load->model('clients_model');
        
        $client = $this->clients_model->get($invoice->clientid);
        if (!$client) {
            return ['success' => false, 'message' => 'Customer record not found for this invoice'];
        }

        $currency = $this->currencies_model->get($invoice->currency);
        if (!$currency) {
            $base_currency = get_base_currency();
            $currency = (object) ['name' => is_object($base_currency) ? $base_currency->name : ($base_currency ?: 'ZMW')];
        }
        
        // Get invoice items
        if (method_exists($this->invoices_model, 'get_invoice_items')) {
            $items = $this->invoices_model->get_invoice_items($invoice->id);
        } elseif (method_exists($this->invoices_model, 'get_items')) {
            $items = $this->invoices_model->get_items($invoice->id);
        } elseif (method_exists($this->invoices_model, 'get_items_by_invoice')) {
            $items = $this->invoices_model->get_items_by_invoice($invoice->id);
        } else {
            $items = $this->get_invoice_items_by_query($invoice->id);
        }

        if (!is_array($items)) {
            $items = [];
        }
        
        $invoice_items = [];
        $item_sequence = 1;
        
        // Initialize tax category totals as per VSDC specification
        $tax_totals = [
            'taxblAmtA' => 0, 'taxAmtA' => 0, // VAT Standard Rate
            'taxblAmtB' => 0, 'taxAmtB' => 0, // VAT Minimum Taxable
            'taxblAmtC1' => 0, 'taxAmtC1' => 0, // VAT C1 Exports
            'taxblAmtC2' => 0, 'taxAmtC2' => 0, // VAT C2 Zero-rated LPO
            'taxblAmtC3' => 0, 'taxAmtC3' => 0, // VAT C3 Zero-rated by nature
            'taxblAmtD' => 0, 'taxAmtD' => 0, // VAT D Exempt
            'taxblAmtRvat' => 0, 'taxAmtRvat' => 0, // RVAT
            'taxblAmtE' => 0, 'taxAmtE' => 0, // VAT E Disbursements
            'taxblAmtF' => 0, 'taxAmtF' => 0, // VAT F Service Charge
            'taxblAmtIpl1' => 0, 'taxAmtIpl1' => 0, // Insurance Premium Levy 1
            'taxblAmtIpl2' => 0, 'taxAmtIpl2' => 0, // Insurance Premium Levy 2
            'taxblAmtTl' => 0, 'taxAmtTl' => 0, // Tourism Levy
            'taxblAmtEcm' => 0, 'taxAmtEcm' => 0, // Excise Coal Mining
            'taxblAmtExeeg' => 0, 'taxAmtExeeg' => 0, // Excise Electricity
            'taxblAmtTot' => 0, 'taxAmtTot' => 0 // TOT
        ];
        
        $total_taxable = 0;
        $total_tax = 0;
        $total_amount = 0;

        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? $item['quantity'] ?? 0);
            $rate = (float) ($item['rate'] ?? $item['unit_price'] ?? $item['price'] ?? 0);
            $line_total = round($qty * $rate, 4);
            $tax_rate = $this->determine_tax_rate($item);
            $tax_amount = round(($line_total * $tax_rate) / 100, 4);
            $taxable_amount = round($line_total, 4);
            $vat_category = $this->determine_tax_category($item);
            $category_key = 'taxblAmt' . strtoupper($vat_category);
            $amount_key = 'taxAmt' . strtoupper($vat_category);

            if (!array_key_exists($category_key, $tax_totals)) {
                $category_key = 'taxblAmtA';
                $amount_key = 'taxAmtA';
            }

            $invoice_items[] = [
                'itemSeq' => $item_sequence++,
                'itemCd' => substr($item['item_code'] ?? ($item['code'] ?? ($item['description'] ?? 'ITEM')), 0, 20),
                'itemClsCd' => $item['itemClsCd'] ?? $item['class_code'] ?? '85121801',
                'itemNm' => substr($item['description'] ?? ($item['item_name'] ?? ''), 0, 200),
                'bcd' => $item['barcode'] ?? '',
                'pkgUnitCd' => $item['pkgUnitCd'] ?? $item['package_unit'] ?? 'U',
                'pkg' => $item['pkg'] ?? 0,
                'qtyUnitCd' => $item['qtyUnitCd'] ?? $item['qty_unit'] ?? 'U',
                'qty' => round($qty, 4),
                'prc' => round($rate, 4),
                'splyAmt' => round($line_total, 4),
                'dcRt' => isset($item['discount_rate']) ? round($item['discount_rate'], 4) : 0,
                'dcAmt' => isset($item['discount_amount']) ? round($item['discount_amount'], 4) : 0,
                'isrccCd' => $item['isrccCd'] ?? '',
                'isrccNm' => $item['isrccNm'] ?? '',
                'isrcRt' => isset($item['isrcRt']) ? round($item['isrcRt'], 4) : 0,
                'isrcAmt' => isset($item['isrcAmt']) ? round($item['isrcAmt'], 4) : 0,
                'vatCatCd' => $vat_category,
                'exciseTxCatCd' => $item['exciseTxCatCd'] ?? null,
                'vatTaxblAmt' => round($taxable_amount, 4),
                'exciseTaxblAmt' => isset($item['exciseTaxblAmt']) ? round($item['exciseTaxblAmt'], 4) : 0,
                'tlTaxblAmt' => isset($item['tlTaxblAmt']) ? round($item['tlTaxblAmt'], 4) : 0,
                'iplTaxblAmt' => isset($item['iplTaxblAmt']) ? round($item['iplTaxblAmt'], 4) : 0,
                'iplAmt' => isset($item['iplAmt']) ? round($item['iplAmt'], 4) : 0,
                'tlAmt' => isset($item['tlAmt']) ? round($item['tlAmt'], 4) : 0,
                'vatAmt' => round($tax_amount, 4),
                'exciseTxAmt' => isset($item['exciseTxAmt']) ? round($item['exciseTxAmt'], 4) : 0,
                'totAmt' => round($taxable_amount + $tax_amount, 2)
            ];

            $tax_totals[$category_key] += $taxable_amount;
            $tax_totals[$amount_key] += $tax_amount;

            $total_taxable += $taxable_amount;
            $total_tax += $tax_amount;
            $total_amount += ($taxable_amount + $tax_amount);
        }

        array_walk($tax_totals, function (&$amount) {
            $amount = round($amount, 4);
        });

        $total_taxable = round($total_taxable, 4);
        $total_tax = round($total_tax, 4);
        $total_amount = round($total_amount, 4);

        // Standard tax rates as per ZRA specification
        $tax_rates = [
            'taxRtA' => 0,
            'taxRtB' => 16,
            'taxRtC1' => 0,
            'taxRtC2' => 0,
            'taxRtC3' => 0,
            'taxRtD' => 0,
            'taxRtRvat' => 16,
            'taxRtE' => 0,
            'taxRtF' => (float) get_option('zra_tax_rate_standard') ?: 16,
            'taxRtIpl1' => 5,
            'taxRtIpl2' => 0,
            'taxRtTl' => 1.5,
            'taxRtEcm' => 5,
            'taxRtExeeg' => 3,
            'taxRtTot' => 0
        ];

        // Build complete request as per VSDC specification
        $customer_tpin = $this->sanitize_tpin($client->vat ?? $client->tax_number ?? '');
        $customer_name = trim($client->company ?? ($client->name ?? ($client->firstname . ' ' . $client->lastname ?? '')));
        $exchange_rate = 1;
        if (isset($currency->rate) && is_numeric($currency->rate) && (float) $currency->rate > 0) {
            $exchange_rate = round((float) $currency->rate, 4);
        }

        $request_data = array_merge([
            'tpin' => $this->company_tin,
            'bhfId' => $this->branch_id,
            'orgSdcId' => '', // Original SDC ID - empty for normal invoices
            'orgInvcNo' => 0, // Original invoice number - use 0 for normal invoices
            'cisInvcNo' => $invoice->number,
            'custTpin' => $customer_tpin,
            'custNm' => $customer_name,
            'salesTyCd' => 'N', // Normal sale
            'rcptTyCd' => 'S', // Sales receipt
            'pmtTyCd' => '01', // Cash payment
            'salesSttsCd' => '02', // Confirmed
            'cfmDt' => date('YmdHis'),
            'salesDt' => date('Ymd', strtotime($invoice->date)),
            'stockRlsDt' => null, // Stock release date
            'cnclReqDt' => null, // Cancel request date
            'cnclDt' => null, // Cancel date
            'rfdDt' => null, // Refund date
            'rfdRsnCd' => null, // Refund reason code
            'totItemCnt' => count($invoice_items),
            'totTaxblAmt' => round($total_taxable, 2),
            'totTaxAmt' => round($total_tax, 2),
            'cashDcRt' => 0, // Cash discount rate
            'cashDcAmt' => 0, // Cash discount amount
            'totAmt' => round($total_amount, 2),
            'prchrAcptcYn' => 'N', // Purchase acceptance
            'remark' => 'Invoice submitted via ZRA integration module',
            'regrId' => 'ADMIN',
            'regrNm' => 'ADMIN',
            'modrId' => 'ADMIN',
            'modrNm' => 'ADMIN',
            'saleCtyCd' => '1', // Sales category code
            'lpoNumber' => $invoice->lpo_number ?? $invoice->lpo ?? null,
            'currencyTyCd' => strtoupper($currency->name ?? 'ZMW'), // Currency type code
            'exchangeRt' => $exchange_rate, // Exchange rate
            'destnCountryCd' => strtoupper($invoice->destination_country ?? $invoice->country_code ?? ''),
            'dbtRsnCd' => null, // Debit reason code
            'invcAdjustReason' => null, // Invoice adjustment reason
            'itemList' => $invoice_items
        ], $tax_totals, $tax_rates);

        // If no items were found, fail early with clear message rather than calling the API
        if (count($invoice_items) === 0) {
            return ['success' => false, 'message' => 'Invoice has no line items; cannot submit to ZRA'];
        }

        // Ensure totItemCnt reflects actual items
        $request_data['totItemCnt'] = count($invoice_items);

        // Log final payload structure for debugging if debug mode is enabled
        if ($this->debug_mode) {
            @file_put_contents(APPPATH . 'logs/zra_submit_invoice_debug.log', date('Y-m-d H:i:s') . ' MODEL DEBUG: final request payload=' . json_encode($request_data) . "\n", FILE_APPEND);
        }

        return $request_data;
    }

    private function get_invoice_items_by_query($invoice_id)
    {
        $debugFile = APPPATH . 'logs/zra_submit_invoice_debug.log';
        $tempFile = sys_get_temp_dir() . '/zra_submit_invoice_error.log';

        $prefix = db_prefix();
        $candidate_suffixes = ['invoice_items', 'invoiceitems', 'invoice_item', 'items'];
        $possible_tables = [];

        foreach ($candidate_suffixes as $suffix) {
            $possible_tables[] = $prefix . $suffix;
            $possible_tables[] = $suffix;
            if (strpos($suffix, 'tbl') !== 0) {
                $possible_tables[] = 'tbl' . $suffix;
            }
        }

        $possible_tables = array_values(array_unique($possible_tables));

        @file_put_contents($debugFile, date('Y-m-d H:i:s') . " MODEL DEBUG: db_prefix=" . var_export($prefix, true) . "; checking possible item tables: " . json_encode($possible_tables) . "\n", FILE_APPEND);
        @file_put_contents($tempFile, date('Y-m-d H:i:s') . " MODEL DEBUG: db_prefix=" . var_export($prefix, true) . "; checking possible item tables: " . json_encode($possible_tables) . "\n", FILE_APPEND);

        $candidate_where_columns = ['invoiceid', 'invoice_id', 'rel_id', 'parent_id', 'related_id', 'invoice'];

        foreach ($possible_tables as $table) {
            $exists = $this->db->table_exists($table);
            @file_put_contents($debugFile, date('Y-m-d H:i:s') . " MODEL DEBUG: table {$table} exists=" . var_export($exists, true) . "\n", FILE_APPEND);
            @file_put_contents($tempFile, date('Y-m-d H:i:s') . " MODEL DEBUG: table {$table} exists=" . var_export($exists, true) . "\n", FILE_APPEND);

            if ($exists) {
                $fields = $this->db->list_fields($table);
                @file_put_contents($debugFile, date('Y-m-d H:i:s') . " MODEL DEBUG: table {$table} fields=" . json_encode($fields) . "\n", FILE_APPEND);
                @file_put_contents($tempFile, date('Y-m-d H:i:s') . " MODEL DEBUG: table {$table} fields=" . json_encode($fields) . "\n", FILE_APPEND);

                foreach ($candidate_where_columns as $col) {
                    $hasField = $this->db->field_exists($col, $table);
                    @file_put_contents($debugFile, date('Y-m-d H:i:s') . " MODEL DEBUG: checking column {$col} in {$table}: " . var_export($hasField, true) . "\n", FILE_APPEND);
                    @file_put_contents($tempFile, date('Y-m-d H:i:s') . " MODEL DEBUG: checking column {$col} in {$table}: " . var_export($hasField, true) . "\n", FILE_APPEND);

                    if (!$hasField) {
                        continue;
                    }

                    if ($col === 'related_id' && $this->db->field_exists('related_type', $table)) {
                        $related_types = ['invoice', 'invoices', 'invoice_item', 'invoice_items', 'tblinvoice', 'tblinvoiceitems', 'tblinvoices'];
                        foreach ($related_types as $related_type) {
                            $rows = $this->db->select('*')
                                ->from($table)
                                ->where('related_id', $invoice_id)
                                ->where('related_type', $related_type)
                                ->get()
                                ->result_array();

                            $count = is_array($rows) ? count($rows) : 0;
                            @file_put_contents($debugFile, date('Y-m-d H:i:s') . " MODEL DEBUG: found {$count} rows in {$table} where related_id={$invoice_id} and related_type='{$related_type}'\n", FILE_APPEND);
                            @file_put_contents($tempFile, date('Y-m-d H:i:s') . " MODEL DEBUG: found {$count} rows in {$table} where related_id={$invoice_id} and related_type='{$related_type}'\n", FILE_APPEND);

                            if ($count > 0) {
                                $items = [];
                                foreach ($rows as $r) {
                                    // Map possible column names to expected keys
                                    $description = null;
                                    foreach (['description', 'item_description', 'item_name', 'name'] as $d) {
                                        if (isset($r[$d]) && $r[$d] !== null) {
                                            $description = $r[$d];
                                            break;
                                        }
                                    }

                                    $qty = null;
                                    foreach (['qty', 'quantity', 'amount'] as $q) {
                                        if (isset($r[$q]) && $r[$q] !== null) {
                                            $qty = $r[$q];
                                            break;
                                        }
                                    }

                                    $rate = null;
                                    foreach (['rate', 'unit_price', 'price', 'amount'] as $p) {
                                        if (isset($r[$p]) && $r[$p] !== null) {
                                            $rate = $r[$p];
                                            break;
                                        }
                                    }

                                    $tax = null;
                                    foreach (['tax', 'tax_rate', 'taxrate'] as $t) {
                                        if (isset($r[$t]) && $r[$t] !== null) {
                                            $tax = $r[$t];
                                            break;
                                        }
                                    }

                                    $items[] = [
                                        'description' => $description ?? (isset($r['description']) ? $r['description'] : ''),
                                        'qty' => $qty !== null ? $qty : (isset($r['qty']) ? $r['qty'] : 1),
                                        'rate' => $rate !== null ? $rate : 0,
                                        'tax' => $tax !== null ? $tax : 0,
                                        'raw_row_sample' => array_slice($r, 0, 6)
                                    ];
                                }

                                @file_put_contents($debugFile, date('Y-m-d H:i:s') . " MODEL DEBUG: returning items from {$table} where related_id={$invoice_id} and related_type='{$related_type}'\n" , FILE_APPEND);
                                @file_put_contents($tempFile, date('Y-m-d H:i:s') . " MODEL DEBUG: returning items from {$table} where related_id={$invoice_id} and related_type='{$related_type}'\n" , FILE_APPEND);
                                return $items;
                            }
                        }
                    }

                    $rows = $this->db->select('*')
                        ->from($table)
                        ->where($col, $invoice_id)
                        ->get()
                        ->result_array();

                    $count = is_array($rows) ? count($rows) : 0;
                    @file_put_contents($debugFile, date('Y-m-d H:i:s') . " MODEL DEBUG: found {$count} rows in {$table} where {$col}={$invoice_id}\n", FILE_APPEND);
                    @file_put_contents($tempFile, date('Y-m-d H:i:s') . " MODEL DEBUG: found {$count} rows in {$table} where {$col}={$invoice_id}\n", FILE_APPEND);

                    if ($count > 0) {
                        $items = [];
                        foreach ($rows as $r) {
                            // Map possible column names to expected keys
                            $description = null;
                            foreach (['description', 'item_description', 'item_name', 'name'] as $d) {
                                if (isset($r[$d]) && $r[$d] !== null) {
                                    $description = $r[$d];
                                    break;
                                }
                            }

                            $qty = null;
                            foreach (['qty', 'quantity', 'amount'] as $q) {
                                if (isset($r[$q]) && $r[$q] !== null) {
                                    $qty = $r[$q];
                                    break;
                                }
                            }

                            $rate = null;
                            foreach (['rate', 'unit_price', 'price', 'amount'] as $p) {
                                if (isset($r[$p]) && $r[$p] !== null) {
                                    $rate = $r[$p];
                                    break;
                                }
                            }

                            $tax = null;
                            foreach (['tax', 'tax_rate', 'taxrate'] as $t) {
                                if (isset($r[$t]) && $r[$t] !== null) {
                                    $tax = $r[$t];
                                    break;
                                }
                            }

                            $items[] = [
                                'description' => $description ?? (isset($r['description']) ? $r['description'] : ''),
                                'qty' => $qty !== null ? $qty : (isset($r['qty']) ? $r['qty'] : 1),
                                'rate' => $rate !== null ? $rate : 0,
                                'tax' => $tax !== null ? $tax : 0,
                                'raw_row_sample' => array_slice($r, 0, 6)
                            ];
                        }

                        @file_put_contents($debugFile, date('Y-m-d H:i:s') . " MODEL DEBUG: returning items from {$table} where {$col}={$invoice_id}\n" , FILE_APPEND);
                        @file_put_contents($tempFile, date('Y-m-d H:i:s') . " MODEL DEBUG: returning items from {$table} where {$col}={$invoice_id}\n" , FILE_APPEND);
                        return $items;
                    }
                }
            }
        }

        @file_put_contents($debugFile, date('Y-m-d H:i:s') . " MODEL DEBUG: no invoice item rows found in candidate tables, starting broad fallback search\n", FILE_APPEND);
        @file_put_contents($tempFile, date('Y-m-d H:i:s') . " MODEL DEBUG: no invoice item rows found in candidate tables, starting broad fallback search\n", FILE_APPEND);

        $all_tables = $this->db->list_tables();
        @file_put_contents($debugFile, date('Y-m-d H:i:s') . " MODEL DEBUG: list_tables returned " . count($all_tables) . " tables\n" . json_encode($all_tables) . "\n", FILE_APPEND);
        @file_put_contents($tempFile, date('Y-m-d H:i:s') . " MODEL DEBUG: list_tables returned " . count($all_tables) . " tables\n" . json_encode($all_tables) . "\n", FILE_APPEND);

        foreach ($all_tables as $table) {
            $lower_table = strtolower($table);
            if (stripos($lower_table, 'invoice') === false && stripos($lower_table, 'item') === false && stripos($lower_table, 'line') === false) {
                continue;
            }
            if (in_array($table, $possible_tables, true)) {
                continue;
            }

            @file_put_contents($debugFile, date('Y-m-d H:i:s') . " MODEL DEBUG: broad search checking table {$table}\n", FILE_APPEND);
            @file_put_contents($tempFile, date('Y-m-d H:i:s') . " MODEL DEBUG: broad search checking table {$table}\n", FILE_APPEND);

            if (!$this->db->table_exists($table)) {
                continue;
            }

            $fields = $this->db->list_fields($table);
            @file_put_contents($debugFile, date('Y-m-d H:i:s') . " MODEL DEBUG: broad search table {$table} fields=" . json_encode($fields) . "\n", FILE_APPEND);
            @file_put_contents($tempFile, date('Y-m-d H:i:s') . " MODEL DEBUG: broad search table {$table} fields=" . json_encode($fields) . "\n", FILE_APPEND);

            foreach ($candidate_where_columns as $col) {
                $hasField = $this->db->field_exists($col, $table);
                @file_put_contents($debugFile, date('Y-m-d H:i:s') . " MODEL DEBUG: broad search checking column {$col} in {$table}: " . var_export($hasField, true) . "\n", FILE_APPEND);
                @file_put_contents($tempFile, date('Y-m-d H:i:s') . " MODEL DEBUG: broad search checking column {$col} in {$table}: " . var_export($hasField, true) . "\n", FILE_APPEND);

                if (!$hasField) {
                    continue;
                }

                $rows = $this->db->select('*')
                    ->from($table)
                    ->where($col, $invoice_id)
                    ->get()
                    ->result_array();

                $count = is_array($rows) ? count($rows) : 0;
                @file_put_contents($debugFile, date('Y-m-d H:i:s') . " MODEL DEBUG: broad search found {$count} rows in {$table} where {$col}={$invoice_id}\n", FILE_APPEND);
                @file_put_contents($tempFile, date('Y-m-d H:i:s') . " MODEL DEBUG: broad search found {$count} rows in {$table} where {$col}={$invoice_id}\n", FILE_APPEND);

                if ($count > 0) {
                    $items = [];
                    foreach ($rows as $r) {
                        $description = null;
                        foreach (['description', 'item_description', 'item_name', 'name'] as $d) {
                            if (isset($r[$d]) && $r[$d] !== null) {
                                $description = $r[$d];
                                break;
                            }
                        }

                        $qty = null;
                        foreach (['qty', 'quantity', 'amount'] as $q) {
                            if (isset($r[$q]) && $r[$q] !== null) {
                                $qty = $r[$q];
                                break;
                            }
                        }

                        $rate = null;
                        foreach (['rate', 'unit_price', 'price', 'amount'] as $p) {
                            if (isset($r[$p]) && $r[$p] !== null) {
                                $rate = $r[$p];
                                break;
                            }
                        }

                        $tax = null;
                        foreach (['tax', 'tax_rate', 'taxrate'] as $t) {
                            if (isset($r[$t]) && $r[$t] !== null) {
                                $tax = $r[$t];
                                break;
                            }
                        }

                        $items[] = [
                            'description' => $description ?? (isset($r['description']) ? $r['description'] : ''),
                            'qty' => $qty !== null ? $qty : (isset($r['qty']) ? $r['qty'] : 1),
                            'rate' => $rate !== null ? $rate : 0,
                            'tax' => $tax !== null ? $tax : 0,
                            'raw_row_sample' => array_slice($r, 0, 6)
                        ];
                    }

                    @file_put_contents($debugFile, date('Y-m-d H:i:s') . " MODEL DEBUG: returning items from broad search table {$table} where {$col}={$invoice_id}\n", FILE_APPEND);
                    @file_put_contents($tempFile, date('Y-m-d H:i:s') . " MODEL DEBUG: returning items from broad search table {$table} where {$col}={$invoice_id}\n", FILE_APPEND);
                    return $items;
                }
            }
        }

        return [];
    }

    private function prepare_refund_data($refund_data, $original_log)
    {
        return [
            'tpin' => $this->company_tin,
            'bhfId' => $this->branch_id,
            'REFUND_ID' => $refund_data['reference'],
            'ORIGINAL_INVOICE_NUMBER' => $original_log->zra_invoice_number,
            'REFUND_DATE' => date('Y-m-d'),
            'REFUND_TIME' => date('H:i:s'),
            'REFUND_AMOUNT' => $refund_data['amount'],
            'REFUND_REASON' => $refund_data['reason'] ?? 'Customer refund'
        ];
    }

    private function sanitize_tpin($tpin)
    {
        $tpin = trim((string)$tpin);
        // Remove any non-alphanumeric characters
        $clean = preg_replace('/[^0-9A-Za-z]/', '', $tpin);
        // TPIN expected to be 10 characters (digits); validate and return or empty string
        if (strlen($clean) === 10) {
            return $clean;
        }
        return '';
    }

    private function determine_tax_category($item)
    {
        $category = '';
        if (!empty($item['vatCatCd'])) {
            $category = $item['vatCatCd'];
        } elseif (!empty($item['tax_category'])) {
            $category = $item['tax_category'];
        } elseif (!empty($item['tax_code'])) {
            $category = $item['tax_code'];
        } elseif (isset($item['tax']) && $item['tax'] === 0) {
            return 'A';
        }

        $category = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $category));
        $valid = ['A','B','C1','C2','C3','D','E','ECM','EXEEG','F','IPL1','IPL2','RVAT','TL','TOT'];

        if (in_array($category, $valid, true)) {
            return $category;
        }

        return 'F';
    }

    private function determine_tax_rate($item)
    {
        if (isset($item['tax_rate']) && is_numeric($item['tax_rate'])) {
            return (float) $item['tax_rate'];
        }

        if (isset($item['tax']) && is_numeric($item['tax'])) {
            return (float) $item['tax'];
        }

        $category = $this->determine_tax_category($item);
        return $this->tax_rate_for_category($category);
    }

    private function tax_rate_for_category($category)
    {
        switch (strtoupper($category)) {
            case 'A':
            case 'C1':
            case 'C2':
            case 'C3':
            case 'D':
            case 'E':
            case 'IPL2':
            case 'TOT':
                return 0;
            case 'B':
            case 'F':
            case 'RVAT':
                return (float) get_option('zra_tax_rate_standard') ?: 16;
            case 'ECM':
                return 5;
            case 'EXEEG':
                return 3;
            case 'IPL1':
                return 5;
            case 'TL':
                return 1.5;
            default:
                return (float) get_option('zra_tax_rate_standard') ?: 16;
        }
    }

    private function is_offline_failure($response)
    {
        if (!is_array($response)) {
            return false;
        }

        $message = strtolower($response['message'] ?? '');
        $error_code = strtoupper($response['error_code'] ?? $response['resultCd'] ?? '');

        if (in_array($error_code, ['CURL_ERROR', 'HTTP_502', 'HTTP_503', 'HTTP_504', '998', '997'], true)) {
            return true;
        }

        if (strpos($message, 'connection') !== false || strpos($message, 'timeout') !== false || strpos($message, 'service unavailable') !== false) {
            return true;
        }

        return false;
    }

    private function determine_tax_code($tax_rate)
    {
        // Map tax rates to ZRA tax codes
        switch ($tax_rate) {
            case 0:
                return 'A'; // Exempted
            case 16:
                return 'F'; // Standard Rated (16%)
            default:
                return 'F'; // Default to standard
        }
    }

    /**
     * Make API call to ZRA VSDC endpoint
     */
    private function call_api($endpoint, $data)
    {
        $url = rtrim($this->api_url, '/') . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false, // For development - enable in production
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $curl_error,
                'error_code' => 'CURL_ERROR',
                'resultCd' => '999'
            ];
        }
        
        if ($http_code !== 200) {
            return [
                'success' => false,
                'message' => 'HTTP error: ' . $http_code,
                'error_code' => 'HTTP_' . $http_code,
                'resultCd' => '998',
                'raw_response' => $response
            ];
        }
        
        $response_data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'message' => 'Invalid JSON response',
                'error_code' => 'JSON_ERROR',
                'resultCd' => '997',
                'raw_response' => $response
            ];
        }
        
        // Check VSDC API response format
        if (isset($response_data['resultCd'])) {
            $success = ($response_data['resultCd'] === $this->success_code);
            return [
                'success' => $success,
                'message' => $response_data['resultMsg'] ?? 'Unknown response',
                'resultCd' => $response_data['resultCd'],
                'resultDt' => $response_data['resultDt'] ?? null,
                'data' => $response_data['data'] ?? null,
                'raw_response' => $response_data
            ];
        } else {
            // Legacy response format handling
            if (isset($response_data['STATUS']) && $response_data['STATUS'] === 'SUCCESS') {
                return [
                    'success' => true,
                    'message' => 'Success',
                    'resultCd' => '000',
                    'data' => $response_data
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response_data['MESSAGE'] ?? 'Unknown error',
                    'error_code' => $response_data['ERROR_CODE'] ?? 'UNKNOWN',
                    'resultCd' => '996',
                    'data' => $response_data
                ];
            }
        }
    }

    private function try_api_endpoints(array $endpoints, array $payload)
    {
        $lastResponse = ['success' => false, 'message' => 'No endpoint responded successfully'];

        foreach ($endpoints as $endpoint) {
            $lastResponse = $this->call_api($endpoint, $payload);
            if (isset($lastResponse['success']) && $lastResponse['success']) {
                return $lastResponse;
            }
        }

        return $lastResponse;
    }

    private function log_table_exists()
    {
        return $this->db->table_exists(db_prefix() . 'zra_invoicing_logs');
    }

    private function ensure_log_table_exists()
    {
        $table = db_prefix() . 'zra_invoicing_logs';

        if ($this->db->table_exists($table)) {
            $this->ensure_log_table_columns($table);
            return true;
        }

        $sql = 'CREATE TABLE `' . $table . '` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `invoice_id` int(11) NOT NULL,
            `request_type` varchar(50) NOT NULL,
            `request_data` longtext,
            `response_data` longtext,
            `status` varchar(20) NOT NULL DEFAULT "pending",
            `error_code` varchar(10) NULL,
            `error_message` text NULL,
            `zra_invoice_number` varchar(100) NULL,
            `qr_code` text NULL,
            `fiscal_tax_id` varchar(100) NULL,
            `result_date` varchar(100) NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `invoice_id` (`invoice_id`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

        $this->db->query($sql);
        return $this->db->table_exists($table);
    }

    private function ensure_log_table_columns($table)
    {
        $columns = [
            'invoice_id' => 'int(11) NOT NULL',
            'request_type' => 'varchar(50) NOT NULL',
            'request_data' => 'longtext',
            'response_data' => 'longtext',
            'status' => 'varchar(20) NOT NULL DEFAULT "pending"',
            'error_code' => 'varchar(10) NULL',
            'error_message' => 'text NULL',
            'zra_invoice_number' => 'varchar(100) NULL',
            'qr_code' => 'text NULL',
            'fiscal_tax_id' => 'varchar(100) NULL',
            'result_date' => 'varchar(100) NULL',
            'created_at' => 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ];

        foreach ($columns as $field => $definition) {
            if (!$this->db->field_exists($field, $table)) {
                $this->db->query('ALTER TABLE `' . $table . '` ADD COLUMN `' . $field . '` ' . $definition);
            }
        }
    }

    public function log_transaction($data)
    {
        if (!$this->log_table_exists()) {
            return 0;
        }

        $this->db->insert(db_prefix() . 'zra_invoicing_logs', $data);
        return $this->db->insert_id();
    }

    public function get_invoice_log($invoice_id, $status = null)
    {
        if (!$this->log_table_exists()) {
            return null;
        }

        $this->db->where('invoice_id', $invoice_id);
        if ($status) {
            $this->db->where('status', $status);
        }
        $this->db->order_by('id', 'DESC');
        return $this->db->get(db_prefix() . 'zra_invoicing_logs')->row();
    }

    public function get_recent_logs($limit = 10)
    {
        if (!$this->log_table_exists()) {
            return [];
        }

        $this->db->select('zil.*, i.number as invoice_number');
        $this->db->from(db_prefix() . 'zra_invoicing_logs zil');
        $this->db->join(db_prefix() . 'invoices i', 'i.id = zil.invoice_id', 'left');
        $this->db->order_by('zil.id', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    public function get_total_submissions()
    {
        if (!$this->log_table_exists()) {
            return 0;
        }

        return $this->db->count_all_results(db_prefix() . 'zra_invoicing_logs');
    }

    public function get_successful_submissions()
    {
        if (!$this->log_table_exists()) {
            return 0;
        }

        $this->db->where('status', 'success');
        return $this->db->count_all_results(db_prefix() . 'zra_invoicing_logs');
    }

    public function get_failed_submissions()
    {
        if (!$this->log_table_exists()) {
            return 0;
        }

        $this->db->where('status', 'failed');
        return $this->db->count_all_results(db_prefix() . 'zra_invoicing_logs');
    }

    public function get_pending_submissions()
    {
        if (!$this->log_table_exists()) {
            return 0;
        }

        $this->db->where('status', 'pending');
        return $this->db->count_all_results(db_prefix() . 'zra_invoicing_logs');
    }

    public function get_invoice_zra_status($invoice_id)
    {
        if (!$this->log_table_exists()) {
            return ['status' => 'not_submitted', 'message' => 'Not submitted to ZRA'];
        }

        $log = $this->get_invoice_log($invoice_id);
        
        if (!$log) {
            return ['status' => 'not_submitted', 'message' => 'Not submitted to ZRA'];
        }
        
        return [
            'status' => $log->status,
            'message' => $log->error_message ?: 'Success',
            'zra_invoice_number' => $log->zra_invoice_number,
            'qr_code' => $log->qr_code,
            'fiscal_tax_id' => $log->fiscal_tax_id,
            'submitted_at' => $log->created_at
        ];
    }

    public function fetch_invoice_from_zra($invoice_reference)
    {
        if (!get_option('zra_enabled')) {
            return ['success' => false, 'message' => 'ZRA integration is disabled'];
        }

        $fetch_data = [
            'tpin' => $this->company_tin,
            'bhfId' => $this->branch_id,
            'INVOICE_REFERENCE' => $invoice_reference
        ];
        
        $response = $this->call_api('/trnsSales/getInvoiceStatus', $fetch_data);
        
        // Log the fetch operation
        $log_data = [
            'invoice_id' => 0, // No specific invoice ID for fetch
            'request_type' => 'fetch_invoice',
            'request_data' => json_encode($fetch_data),
            'response_data' => json_encode($response),
            'status' => $response['success'] ? 'success' : 'failed',
            'error_code' => $response['error_code'] ?? null,
            'error_message' => $response['message'] ?? null
        ];
        
        $this->log_transaction($log_data);
        
        return $response;
    }

    public function fetch_all_pending_invoices()
    {
        // Get all invoices that have been submitted but are still pending
        if (!$this->log_table_exists()) {
            return [];
        }

        $this->db->where('status', 'pending');
        $this->db->or_where('status', 'failed');
        $pending_logs = $this->db->get(db_prefix() . 'zra_invoicing_logs')->result();
        
        $results = [];
        foreach ($pending_logs as $log) {
            if ($log->invoice_id > 0) {
                $invoice_ref = $this->extract_invoice_reference($log->request_data);
                if ($invoice_ref) {
                    $status = $this->fetch_invoice_from_zra($invoice_ref);
                    $results[] = [
                        'invoice_id' => $log->invoice_id,
                        'invoice_reference' => $invoice_ref,
                        'fetch_result' => $status
                    ];
                    
                    // Update local status if fetch was successful
                    if ($status['success'] && isset($status['data']['STATUS'])) {
                        $this->update_invoice_status_from_fetch($log->id, $status['data']);
                    }
                }
            }
        }
        
        return $results;
    }

    public function get_unsubmitted_invoices($limit = 50)
    {
        // Get invoices that haven't been submitted to ZRA yet
        $this->db->select('i.id, i.number, i.date, i.total, i.clientid, c.company as client_name');
        $this->db->from(db_prefix() . 'invoices i');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = i.clientid', 'left');
        $this->db->where('i.status !=', 5); // Not cancelled
        
        // Exclude invoices that have successful submissions
        if ($this->log_table_exists()) {
            $this->db->where('i.id NOT IN (
                SELECT DISTINCT invoice_id 
                FROM ' . db_prefix() . 'zra_invoicing_logs 
                WHERE status = "success" AND invoice_id > 0
            )');
        }
        
        $this->db->order_by('i.date', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result();
    }

    public function get_logs_by_type($type, $limit = 10)
    {
        if (!$this->log_table_exists()) {
            return [];
        }

        $this->db->where('request_type', $type);
        $this->db->order_by('id', 'DESC');
        $this->db->limit($limit);
        return $this->db->get(db_prefix() . 'zra_invoicing_logs')->result();
    }

    public function bulk_submit_invoices($invoice_ids)
    {
        $results = [];
        
        foreach ($invoice_ids as $invoice_id) {
            $result = $this->submit_invoice($invoice_id);
            $results[$invoice_id] = $result;
            
            // Add small delay to avoid overwhelming the API
            usleep(500000); // 0.5 second delay
        }
        
        return $results;
    }

    private function extract_invoice_reference($request_data)
    {
        $data = json_decode($request_data, true);
        return $data['INVOICE_REFERENCE'] ?? null;
    }

    private function update_invoice_status_from_fetch($log_id, $fetch_data)
    {
        if (!$this->log_table_exists()) {
            return false;
        }

        $update_data = [
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (isset($fetch_data['STATUS'])) {
            $update_data['status'] = strtolower($fetch_data['STATUS']) === 'success' ? 'success' : 'failed';
        }
        
        if (isset($fetch_data['INVOICE_NUMBER'])) {
            $update_data['zra_invoice_number'] = $fetch_data['INVOICE_NUMBER'];
        }
        
        if (isset($fetch_data['QR_CODE'])) {
            $update_data['qr_code'] = $fetch_data['QR_CODE'];
        }
        
        if (isset($fetch_data['FISCAL_TAX_ID'])) {
            $update_data['fiscal_tax_id'] = $fetch_data['FISCAL_TAX_ID'];
        }
        
        $this->db->where('id', $log_id);
        $this->db->update(db_prefix() . 'zra_invoicing_logs', $update_data);
        return true;
    }
}

?>