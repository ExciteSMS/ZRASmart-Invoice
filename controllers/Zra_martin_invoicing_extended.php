<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Zra_martin_invoicing extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('zra_api_model');
    }
    
    // Additional method for log details
    public function log_details($log_id = null)
    {
        if (!has_permission('zra_invoicing', '', 'view')) {
            ajax_access_denied();
        }

        if (!$log_id) {
            echo '<div class="alert alert-danger">Log ID not provided</div>';
            return;
        }

        $this->db->where('id', $log_id);
        $log = $this->db->get(db_prefix() . 'zra_invoicing_logs')->row();

        if (!$log) {
            echo '<div class="alert alert-danger">Log not found</div>';
            return;
        }

        // Get invoice details
        $this->load->model('invoices_model');
        $invoice = $this->invoices_model->get($log->invoice_id);
        $invoice_number = $invoice ? $invoice->number : 'INV-' . str_pad($log->invoice_id, 6, '0', STR_PAD_LEFT);

        $data = [
            'log' => $log,
            'invoice_number' => $invoice_number,
            'invoice' => $invoice
        ];
        
        $this->load->view('log_details', $data);
    }

    // Webhook endpoint for ZRA notifications (if supported)
    public function webhook()
    {
        // Verify webhook signature if required
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            return;
        }
        
        // Process webhook data
        $this->process_webhook_data($data);
        
        echo json_encode(['status' => 'success']);
    }
    
    private function process_webhook_data($data)
    {
        // Implementation depends on ZRA webhook format
        // This would update invoice statuses based on ZRA notifications
        
        if (isset($data['invoice_reference']) && isset($data['status'])) {
            // Find the invoice by reference
            $invoice_ref = $data['invoice_reference'];
            $status = $data['status'];
            
            // Update the log entry
            $this->db->where('request_data LIKE', '%"INVOICE_REFERENCE":"' . $invoice_ref . '"%');
            $this->db->update(db_prefix() . 'zra_invoicing_logs', [
                'status' => $status,
                'response_data' => json_encode($data),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    // Export logs functionality
    public function export_logs()
    {
        if (!has_permission('zra_invoicing', '', 'view')) {
            access_denied('zra_invoicing');
        }
        
        $this->load->helper('download');
        
        // Get all logs
        $this->db->select('zil.*, i.number as invoice_number');
        $this->db->from(db_prefix() . 'zra_invoicing_logs zil');
        $this->db->join(db_prefix() . 'invoices i', 'i.id = zil.invoice_id', 'left');
        $this->db->order_by('zil.id', 'DESC');
        $logs = $this->db->get()->result_array();
        
        // Create CSV content
        $csv_content = "ID,Invoice,Request Type,Status,ZRA Invoice Number,Error Code,Error Message,Created At\n";
        
        foreach ($logs as $log) {
            $csv_content .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $log['id'],
                $log['invoice_number'] ?: 'INV-' . str_pad($log['invoice_id'], 6, '0', STR_PAD_LEFT),
                $log['request_type'],
                $log['status'],
                $log['zra_invoice_number'] ?: '',
                $log['error_code'] ?: '',
                str_replace('"', '""', $log['error_message'] ?: ''),
                $log['created_at']
            );
        }
        
        $filename = 'zra_logs_' . date('Y-m-d_H-i-s') . '.csv';
        
        force_download($filename, $csv_content);
    }
}

?>