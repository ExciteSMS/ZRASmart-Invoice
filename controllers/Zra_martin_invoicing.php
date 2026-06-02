<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Zra_martin_invoicing extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('zra_api_model');
        $this->load->model('invoices_model');
    }

    public function index()
    {
        if (!has_permission('zra_invoicing', '', 'view')) {
            access_denied('zra_invoicing');
        }

        $data['title'] = _l('zra_invoicing_dashboard');
        
        // Get recent logs
        $data['recent_logs'] = $this->zra_api_model->get_recent_logs(10);
        
        // Get statistics
        $data['stats'] = [
            'total_submissions' => $this->zra_api_model->get_total_submissions(),
            'successful_submissions' => $this->zra_api_model->get_successful_submissions(),
            'failed_submissions' => $this->zra_api_model->get_failed_submissions(),
            'pending_submissions' => $this->zra_api_model->get_pending_submissions()
        ];
        $data['device_initialized'] = get_option('zra_device_initialized') == '1';
        
        $this->load->view('dashboard', $data);
    }

    public function settings()
    {
        if (!has_permission('zra_invoicing', '', 'edit')) {
            access_denied('zra_invoicing');
        }

        if ($this->input->post()) {
            $this->handle_settings_post();
        }

        $data['title'] = _l('zra_settings');
        $data['tab'] = $this->input->get('tab');
        
        $this->load->view('settings', $data);
    }

    public function logs()
    {
        if (!has_permission('zra_invoicing', '', 'view')) {
            access_denied('zra_invoicing');
        }

        $data['title'] = _l('zra_logs');
        
        // Handle AJAX request for logs table
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path(ZRA_MARTIN_INVOICING_MODULE_NAME, 'logs/table'));
        }
        
        $this->load->view('logs/manage', $data);
    }

    public function submit_invoice($invoice_id = null)
    {
        $debugFile = APPPATH . 'logs/zra_submit_invoice_debug.log';
        $tempFile = sys_get_temp_dir() . '/zra_submit_invoice_error.log';
        @file_put_contents($debugFile, date('Y-m-d H:i:s') . ' CONTROLLER ENTRY invoice_id=' . var_export($invoice_id, true) . ' ajax=' . var_export($this->input->is_ajax_request(), true) . "\n", FILE_APPEND);
        @file_put_contents($tempFile, date('Y-m-d H:i:s') . ' CONTROLLER ENTRY invoice_id=' . var_export($invoice_id, true) . ' ajax=' . var_export($this->input->is_ajax_request(), true) . "\n", FILE_APPEND);

        if (!has_permission('zra_invoicing', '', 'create')) {
            if ($this->input->is_ajax_request()) {
                ajax_access_denied();
            }
            access_denied('zra_invoicing');
        }

        if (!$invoice_id) {
            show_404();
        }

        try {
            $result = $this->zra_api_model->submit_invoice($invoice_id);
        } catch (\Throwable $th) {
            $debugFile = APPPATH . 'logs/zra_submit_invoice_debug.log';
            $tempFile = sys_get_temp_dir() . '/zra_submit_invoice_error.log';
            $message = 'ZRA submit_invoice throwable: ' . $th->getMessage() . ' in ' . $th->getFile() . ' on line ' . $th->getLine();
            log_message('error', $message);
            log_message('error', $th->getTraceAsString());
            @file_put_contents($debugFile, date('Y-m-d H:i:s') . ' CONTROLLER: ' . $message . "\n" . $th->getTraceAsString() . "\n\n", FILE_APPEND);
            @file_put_contents($tempFile, date('Y-m-d H:i:s') . ' CONTROLLER: ' . $message . "\n" . $th->getTraceAsString() . "\n\n", FILE_APPEND);
            $result = ['success' => false, 'message' => 'Internal server error while submitting invoice'];
        }
        
        if ($this->input->is_ajax_request()) {
            file_put_contents(dirname(__DIR__) . '/zra_submit_invoice_entry.log', date('Y-m-d H:i:s') . ' CONTROLLER RESULT ' . json_encode($result) . "\n", FILE_APPEND);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result);
            return;
        }

        if ($result['success']) {
            set_alert('success', _l('zra_invoice_submitted_successfully'));
        } else {
            set_alert('danger', _l('zra_invoice_submission_failed') . ': ' . $result['message']);
        }

        redirect(admin_url('invoices/list_invoices/' . $invoice_id));
    }

    public function test_connection()
    {
        if (!has_permission('zra_invoicing', '', 'view')) {
            ajax_access_denied();
        }

        $result = $this->zra_api_model->test_api_connection();
        $result['csrf_token_name'] = $this->security->get_csrf_token_name();
        $result['csrf_hash'] = $this->security->get_csrf_hash();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
    }

    public function initialize_device()
    {
        if (!has_permission('zra_invoicing', '', 'edit')) {
            ajax_access_denied();
        }

        // Call model to perform initialization
        $result = $this->zra_api_model->initialize_device();

        // Server-side logging: capture request details and raw response for debugging
        $log_data = [
            'invoice_id' => 0,
            'request_type' => 'initialize_device',
            'request_data' => json_encode([
                'api_url' => get_option('zra_api_url'),
                'tpin' => get_option('zra_company_tin'),
                'bhfId' => get_option('zra_branch_id'),
                'dvcSrlNo' => get_option('zra_device_serial')
            ]),
            'response_data' => json_encode($result),
            'status' => (isset($result['success']) && $result['success']) ? 'success' : 'failed',
            'error_code' => $result['resultCd'] ?? ($result['error_code'] ?? null),
            'error_message' => $result['message'] ?? null
        ];

        // Use model helper to insert into logs table
        $this->zra_api_model->log_transaction($log_data);

        $result['csrf_token_name'] = $this->security->get_csrf_token_name();
        $result['csrf_hash'] = $this->security->get_csrf_hash();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
    }

    public function get_standard_codes()
    {
        if (!has_permission('zra_invoicing', '', 'view')) {
            ajax_access_denied();
        }

        $payload = $this->input->post();
        $response = $this->zra_api_model->get_standard_codes($payload);

        echo json_encode($response);
    }

    public function get_item_classification_codes()
    {
        if (!has_permission('zra_invoicing', '', 'view')) {
            ajax_access_denied();
        }

        $payload = $this->input->post();
        $response = $this->zra_api_model->get_item_classification_codes($payload);

        echo json_encode($response);
    }

    public function fetch_import_items()
    {
        if (!has_permission('zra_invoicing', '', 'view')) {
            ajax_access_denied();
        }

        $payload = $this->input->post();
        $response = $this->zra_api_model->fetch_import_items($payload);

        echo json_encode($response);
    }

    public function retrieve_purchases()
    {
        if (!has_permission('zra_invoicing', '', 'view')) {
            ajax_access_denied();
        }

        $payload = $this->input->post();
        $response = $this->zra_api_model->retrieve_purchases($payload);

        echo json_encode($response);
    }

    public function save_purchase()
    {
        if (!has_permission('zra_invoicing', '', 'create')) {
            ajax_access_denied();
        }

        $payload = $this->input->post();
        $response = $this->zra_api_model->save_purchase($payload);

        echo json_encode($response);
    }

    public function save_non_smart_supplier_purchase()
    {
        if (!has_permission('zra_invoicing', '', 'create')) {
            ajax_access_denied();
        }

        $payload = $this->input->post();
        $response = $this->zra_api_model->save_non_smart_supplier_purchase($payload);

        echo json_encode($response);
    }

    public function save_item()
    {
        if (!has_permission('zra_invoicing', '', 'create')) {
            ajax_access_denied();
        }

        $payload = $this->input->post();
        $response = $this->zra_api_model->save_item($payload);

        echo json_encode($response);
    }

    public function save_stock_adjustment()
    {
        if (!has_permission('zra_invoicing', '', 'create')) {
            ajax_access_denied();
        }

        $payload = $this->input->post();
        $response = $this->zra_api_model->save_stock_adjustment($payload);

        echo json_encode($response);
    }

    public function retry_pending_submissions()
    {
        if (!has_permission('zra_invoicing', '', 'edit')) {
            ajax_access_denied();
        }

        $result = $this->zra_api_model->retry_pending_submissions();
        echo json_encode(['success' => true, 'results' => $result]);
    }

    public function get_invoice_status($invoice_id)
    {
        if (!has_permission('zra_invoicing', '', 'view')) {
            ajax_access_denied();
        }

        $status = $this->zra_api_model->get_invoice_zra_status($invoice_id);
        
        echo json_encode($status);
    }

    public function bulk_submit()
    {
        if (!has_permission('zra_invoicing', '', 'create')) {
            ajax_access_denied();
        }

        $invoice_ids = $this->input->post('invoice_ids');
        
        if (empty($invoice_ids)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'No invoices selected']);
            return;
        }

        $results = $this->zra_api_model->bulk_submit_invoices($invoice_ids);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'results' => $results]);
    }

    public function manual_submit()
    {
        if (!has_permission('zra_invoicing', '', 'view')) {
            access_denied('zra_invoicing');
        }

        if ($this->input->post()) {
            $this->handle_manual_submit_post();
        }

        $data['title'] = _l('zra_manual_submit');
        
        // Get unsubmitted invoices
        $data['unsubmitted_invoices'] = $this->zra_api_model->get_unsubmitted_invoices(100);
        
        $this->load->view('manual_submit', $data);
    }

    public function fetch_invoices()
    {
        if (!has_permission('zra_invoicing', '', 'view')) {
            access_denied('zra_invoicing');
        }

        try {
            if ($this->input->post()) {
                $this->handle_fetch_invoices_post();
            }

            $data['title'] = _l('zra_fetch_invoices');
            $this->load->view('fetch_invoices', $data);
        } catch (Exception $e) {
            $message = 'Error loading fetch_invoices: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
            log_message('error', $message);
            @file_put_contents('/tmp/zra_fetch_invoices_error.log', $message . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
            show_error('An unexpected error occurred while loading the Fetch Invoices page. Please check the application logs.');
        }
    }

    public function fetch_single_invoice()
    {
        if (!has_permission('zra_invoicing', '', 'view')) {
            ajax_access_denied();
        }

        $invoice_reference = $this->input->post('invoice_reference');
        
        if (empty($invoice_reference)) {
            echo json_encode(['success' => false, 'message' => 'Invoice reference is required']);
            return;
        }

        $result = $this->zra_api_model->fetch_invoice_from_zra($invoice_reference);
        
        echo json_encode($result);
    }

    public function fetch_all_pending()
    {
        if (!has_permission('zra_invoicing', '', 'view')) {
            ajax_access_denied();
        }

        $results = $this->zra_api_model->fetch_all_pending_invoices();
        
        echo json_encode(['success' => true, 'results' => $results]);
    }

    public function get_unsubmitted_invoices_ajax()
    {
        if (!has_permission('zra_invoicing', '', 'view')) {
            ajax_access_denied();
        }

        $invoices = $this->zra_api_model->get_unsubmitted_invoices();
        
        echo json_encode(['success' => true, 'invoices' => $invoices]);
    }

    public function latest_initialize_log()
    {
        if (!is_admin()) {
            ajax_access_denied();
        }

        $logs = $this->zra_api_model->get_logs_by_type('initialize_device', 1);
        $log = isset($logs[0]) ? $logs[0] : null;

        echo json_encode(['success' => true, 'log' => $log]);
    }

    private function handle_settings_post()
    {
        $settings = [
            'zra_enabled',
            'zra_environment',
            'zra_api_url',
            'zra_company_tin',
            'zra_branch_id',
            'zra_device_serial',
            'zra_auto_submit_invoices',
            'zra_tax_rate_standard',
            'zra_tax_rate_zero',
            'zra_default_currency',
            'zra_timeout',
            'zra_debug_mode'
        ];

        foreach ($settings as $setting) {
            $value = $this->input->post($setting);
            update_option($setting, $value);
        }

        set_alert('success', _l('settings_updated'));
        redirect(admin_url('zra_martin_invoicing/settings'));
    }

    private function handle_manual_submit_post()
    {
        $action = $this->input->post('action');
        
        if ($action === 'submit_selected') {
            $invoice_ids = $this->input->post('invoice_ids');
            
            if (empty($invoice_ids)) {
                set_alert('danger', _l('zra_no_invoices_selected'));
                redirect(admin_url('zra_martin_invoicing/manual_submit'));
                return;
            }
            
            $results = $this->zra_api_model->bulk_submit_invoices($invoice_ids);
            
            $success_count = 0;
            $failed_count = 0;
            
            foreach ($results as $result) {
                if ($result['success']) {
                    $success_count++;
                } else {
                    $failed_count++;
                }
            }
            
            if ($success_count > 0) {
                set_alert('success', sprintf(_l('zra_bulk_submit_success'), $success_count));
            }
            
            if ($failed_count > 0) {
                set_alert('warning', sprintf(_l('zra_bulk_submit_failed'), $failed_count));
            }
        }
        
        redirect(admin_url('zra_martin_invoicing/manual_submit'));
    }

    private function handle_fetch_invoices_post()
    {
        $action = $this->input->post('action');
        
        if ($action === 'fetch_single') {
            $invoice_reference = $this->input->post('invoice_reference');
            
            if (empty($invoice_reference)) {
                set_alert('danger', _l('zra_invoice_reference_required'));
                redirect(admin_url('zra_martin_invoicing/fetch_invoices'));
                return;
            }
            
            $result = $this->zra_api_model->fetch_invoice_from_zra($invoice_reference);
            
            if ($result['success']) {
                set_alert('success', _l('zra_fetch_success'));
            } else {
                set_alert('danger', _l('zra_fetch_failed') . ': ' . $result['message']);
            }
        } elseif ($action === 'fetch_all_pending') {
            $results = $this->zra_api_model->fetch_all_pending_invoices();
            
            $fetched_count = count($results);
            
            if ($fetched_count > 0) {
                set_alert('success', sprintf(_l('zra_fetch_all_success'), $fetched_count));
            } else {
                set_alert('info', _l('zra_no_pending_invoices'));
            }
        }
        
        redirect(admin_url('zra_martin_invoicing/fetch_invoices'));
    }
}

?>