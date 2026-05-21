<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Get log details by ID
$log_id = $this->uri->segment(4);

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

?>

<div class="row">
    <div class="col-md-12">
        <h5><?php echo _l('zra_log_details'); ?> - <?php echo $invoice_number; ?></h5>
        
        <div class="panel panel-default">
            <div class="panel-heading">
                <h6 class="panel-title"><?php echo _l('zra_submission_details'); ?></h6>
            </div>
            <div class="panel-body">
                <table class="table table-bordered">
                    <tr>
                        <td><strong><?php echo _l('invoice'); ?>:</strong></td>
                        <td>
                            <a href="<?php echo admin_url('invoices/list_invoices/' . $log->invoice_id); ?>" target="_blank">
                                <?php echo $invoice_number; ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php echo _l('zra_request_type'); ?>:</strong></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $log->request_type)); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo _l('status'); ?>:</strong></td>
                        <td>
                            <?php if ($log->status == 'success') { ?>
                                <span class="label label-success"><?php echo _l('success'); ?></span>
                            <?php } elseif ($log->status == 'failed') { ?>
                                <span class="label label-danger"><?php echo _l('failed'); ?></span>
                            <?php } else { ?>
                                <span class="label label-warning"><?php echo _l('pending'); ?></span>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php if ($log->zra_invoice_number) { ?>
                    <tr>
                        <td><strong><?php echo _l('zra_invoice_number'); ?>:</strong></td>
                        <td><?php echo $log->zra_invoice_number; ?></td>
                    </tr>
                    <?php } ?>
                    <?php if ($log->fiscal_tax_id) { ?>
                    <tr>
                        <td><strong><?php echo _l('zra_fiscal_tax_id'); ?>:</strong></td>
                        <td><?php echo $log->fiscal_tax_id; ?></td>
                    </tr>
                    <?php } ?>
                    <?php if ($log->error_code) { ?>
                    <tr>
                        <td><strong><?php echo _l('error_code'); ?>:</strong></td>
                        <td><code><?php echo $log->error_code; ?></code></td>
                    </tr>
                    <?php } ?>
                    <?php if ($log->error_message) { ?>
                    <tr>
                        <td><strong><?php echo _l('zra_error_details'); ?>:</strong></td>
                        <td><span class="text-danger"><?php echo $log->error_message; ?></span></td>
                    </tr>
                    <?php } ?>
                    <tr>
                        <td><strong><?php echo _l('zra_submission_date'); ?>:</strong></td>
                        <td><?php echo _dt($log->created_at); ?></td>
                    </tr>
                    <?php if ($log->updated_at != $log->created_at) { ?>
                    <tr>
                        <td><strong><?php echo _l('last_updated'); ?>:</strong></td>
                        <td><?php echo _dt($log->updated_at); ?></td>
                    </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
        
        <?php if ($log->qr_code) { ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h6 class="panel-title"><?php echo _l('zra_qr_code'); ?></h6>
            </div>
            <div class="panel-body text-center">
                <img src="data:image/png;base64,<?php echo $log->qr_code; ?>" alt="ZRA QR Code" class="img-responsive" style="max-width: 200px; margin: 0 auto;">
            </div>
        </div>
        <?php } ?>
        
        <div class="panel panel-default">
            <div class="panel-heading">
                <h6 class="panel-title"><?php echo _l('zra_request_data'); ?></h6>
            </div>
            <div class="panel-body">
                <pre class="zra-json-data"><?php echo json_encode(json_decode($log->request_data), JSON_PRETTY_PRINT); ?></pre>
            </div>
        </div>
        
        <div class="panel panel-default">
            <div class="panel-heading">
                <h6 class="panel-title"><?php echo _l('zra_response_data'); ?></h6>
            </div>
            <div class="panel-body">
                <pre class="zra-json-data"><?php echo json_encode(json_decode($log->response_data), JSON_PRETTY_PRINT); ?></pre>
            </div>
        </div>
        
        <?php if ($log->status == 'failed' && has_permission('zra_invoicing', '', 'create')) { ?>
        <div class="text-center">
            <a href="<?php echo admin_url('zra_martin_invoicing/submit_invoice/' . $log->invoice_id); ?>" class="btn btn-warning">
                <i class="fa fa-refresh"></i> <?php echo _l('zra_retry_submission'); ?>
            </a>
        </div>
        <?php } ?>
    </div>
</div>

<style>
.zra-json-data {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 10px;
    font-size: 12px;
    line-height: 1.4;
    max-height: 300px;
    overflow-y: auto;
}
</style>