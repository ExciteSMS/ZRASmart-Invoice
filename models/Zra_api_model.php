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
        if (!get_option('zra_enabled')) {
            return ['success' => false, 'message' => 'ZRA integration is disabled'];
        }

        // Get invoice data
        $this->load->model('invoices_model');
        $invoice = $this->invoices_model->get($invoice_id);
        
        if (!$invoice) {
            return ['success' => false, 'message' => 'Invoice not found'];
        }

        // Check if already submitted
        $existing_log = $this->get_invoice_log($invoice_id, 'success');
        if ($existing_log) {
            return ['success' => false, 'message' => 'Invoice already submitted to ZRA'];
        }

        // Prepare invoice data for ZRA
        $zra_data = $this->prepare_invoice_data($invoice);
        
        // Submit to ZRA API
        $response = $this->call_api('/trnsSales/saveSales', $zra_data);
        
        // Log the transaction with VSDC response format
        $log_data = [
            'invoice_id' => $invoice_id,
            'request_type' => 'invoice_submission',
            'request_data' => json_encode($zra_data),
            'response_data' => json_encode($response),
            'status' => $response['success'] ? 'success' : 'failed',
            'error_code' => $response['resultCd'] ?? null,
            'error_message' => $response['message'] ?? null,
            'zra_invoice_number' => $response['data']['invoice_number'] ?? null,
            'qr_code' => $response['data']['qr_code'] ?? null,
            'fiscal_tax_id' => $response['data']['fiscal_tax_id'] ?? null,
            'result_date' => $response['resultDt'] ?? null
        ];
        
        $this->log_transaction($log_data);
        
        return $response;
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
        $currency = $this->currencies_model->get($invoice->currency);
        
        // Get invoice items
        $items = $this->invoices_model->get_invoice_items($invoice->id);
        
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
            $line_total = $item['qty'] * $item['rate'];
            $tax_amount = 0;
            $vat_category = 'A'; // Default to standard VAT
            
            // Calculate tax if applicable
            $tax_rate = $this->determine_tax_rate($item);
            if ($tax_rate > 0) {
                $tax_amount = ($line_total * $tax_rate) / (100 + $tax_rate) * 100; // Tax-inclusive calculation
            }
            
            $taxable_amount = $line_total - $tax_amount;
            
            // Build line item according to VSDC specification
            $invoice_items[] = [
                'itemSeq' => $item_sequence++,
                'itemCd' => substr($item['description'], 0, 20), // Max 20 chars as per spec
                'itemClsCd' => '85121801', // Default UNSPSC code - should be configurable
                'itemNm' => substr($item['description'], 0, 200), // Max 200 chars
                'bcd' => '', // Barcode if available
                'pkgUnitCd' => 'U', // Default packaging unit
                'pkg' => 0,
                'qtyUnitCd' => 'U', // Default quantity unit
                'qty' => (float)$item['qty'],
                'prc' => (float)$item['rate'],
                'splyAmt' => (float)$line_total,
                'dcRt' => 0, // Discount rate
                'dcAmt' => 0, // Discount amount
                'isrccCd' => '', // Insurance company code
                'isrccNm' => '', // Insurance company name
                'isrcRt' => 0, // Insurance rate
                'isrcAmt' => 0, // Insurance amount
                'vatCatCd' => $vat_category, // VAT category
                'exciseTxCatCd' => null, // Excise tax category
                'vatTaxblAmt' => (float)$taxable_amount,
                'exciseTaxblAmt' => 0,
                'tlTaxblAmt' => 0, // Tourism levy taxable amount
                'iplTaxblAmt' => 0, // IPL taxable amount
                'iplAmt' => 0, // IPL amount
                'tlAmt' => 0, // Tourism levy amount
                'vatAmt' => (float)$tax_amount,
                'exciseTxAmt' => 0,
                'totAmt' => (float)$line_total
            ];
            
            // Accumulate tax totals by category
            $tax_totals['taxblAmtA'] += $taxable_amount;
            $tax_totals['taxAmtA'] += $tax_amount;
            
            $total_taxable += $taxable_amount;
            $total_tax += $tax_amount;
            $total_amount += $line_total;
        }

        // Standard tax rates as per ZRA specification
        $tax_rates = [
            'taxRtA' => 16, 'taxRtB' => 16, 'taxRtC1' => 0, 'taxRtC2' => 0, 'taxRtC3' => 0,
            'taxRtD' => 0, 'taxRtRvat' => 16, 'taxRtE' => 0, 'taxRtF' => 10,
            'taxRtIpl1' => 5, 'taxRtIpl2' => 0, 'taxRtTl' => 1.5, 'taxRtEcm' => 5,
            'taxRtExeeg' => 3, 'taxRtTot' => 0
        ];

        // Build complete request as per VSDC specification
        $request_data = array_merge([
            'tpin' => $this->company_tin,
            'bhfId' => $this->branch_id,
            'orgSdcId' => null, // Original SDC ID - only used for credit/debit notes
            'orgInvcNo' => null, // Original invoice number for debit/credit notes
            'cisInvcNo' => $invoice->number,
            'custTpin' => $client->vat ?? '', // Customer TIN if available
            'custNm' => $client->company,
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
            'totTaxblAmt' => (float)$total_taxable,
            'totTaxAmt' => (float)$total_tax,
            'cashDcRt' => 0, // Cash discount rate
            'cashDcAmt' => 0, // Cash discount amount
            'totAmt' => (float)$total_amount,
            'prchrAcptcYn' => 'N', // Purchase acceptance
            'remark' => 'Invoice submitted via PerfexCRM ZRA Module',
            'regrId' => 'ADMIN',
            'regrNm' => 'ADMIN',
            'modrId' => 'ADMIN',
            'modrNm' => 'ADMIN',
            'saleCtyCd' => '1', // Sales category code
            'lpoNumber' => null, // Local purchase order number
            'currencyTyCd' => $currency->name ?? 'ZMW', // Currency type code
            'exchangeRt' => '1', // Exchange rate
            'destnCountryCd' => '', // Destination country code
            'dbtRsnCd' => null, // Debit reason code
            'invcAdjustReason' => null, // Invoice adjustment reason
            'itemList' => $invoice_items
        ], $tax_totals, $tax_rates);

        return $request_data;
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

    private function determine_tax_rate($item)
    {
        // Default to standard rate
        $standard_rate = get_option('zra_tax_rate_standard') ?: 16;
        
        // You can implement custom logic here to determine tax rate based on item
        // For now, return standard rate
        return $standard_rate;
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
                'resultCd' => '998'
            ];
        }
        
        $response_data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'message' => 'Invalid JSON response',
                'error_code' => 'JSON_ERROR',
                'resultCd' => '997'
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

    public function log_transaction($data)
    {
        $this->db->insert(db_prefix() . 'zra_invoicing_logs', $data);
        return $this->db->insert_id();
    }

    public function get_invoice_log($invoice_id, $status = null)
    {
        $this->db->where('invoice_id', $invoice_id);
        if ($status) {
            $this->db->where('status', $status);
        }
        $this->db->order_by('id', 'DESC');
        return $this->db->get(db_prefix() . 'zra_invoicing_logs')->row();
    }

    public function get_recent_logs($limit = 10)
    {
        $this->db->select('zil.*, i.number as invoice_number');
        $this->db->from(db_prefix() . 'zra_invoicing_logs zil');
        $this->db->join(db_prefix() . 'invoices i', 'i.id = zil.invoice_id', 'left');
        $this->db->order_by('zil.id', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    public function get_total_submissions()
    {
        return $this->db->count_all_results(db_prefix() . 'zra_invoicing_logs');
    }

    public function get_successful_submissions()
    {
        $this->db->where('status', 'success');
        return $this->db->count_all_results(db_prefix() . 'zra_invoicing_logs');
    }

    public function get_failed_submissions()
    {
        $this->db->where('status', 'failed');
        return $this->db->count_all_results(db_prefix() . 'zra_invoicing_logs');
    }

    public function get_pending_submissions()
    {
        $this->db->where('status', 'pending');
        return $this->db->count_all_results(db_prefix() . 'zra_invoicing_logs');
    }

    public function get_invoice_zra_status($invoice_id)
    {
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
        $this->db->where('i.id NOT IN (
            SELECT DISTINCT invoice_id 
            FROM ' . db_prefix() . 'zra_invoicing_logs 
            WHERE status = "success" AND invoice_id > 0
        )');
        
        $this->db->order_by('i.date', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result();
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
    }
}

?>