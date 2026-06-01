<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">
                            <i class="fa fa-send"></i>
                            <?php echo _l('zra_manual_submit'); ?>
                        </h4>
                        <hr class="hr-panel-heading">
                        
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            <?php echo _l('zra_manual_submit_info'); ?>
                        </div>
                        
                        <?php echo form_open(admin_url('zra_martin_invoicing/manual_submit'), ['id' => 'manual-submit-form']); ?>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="panel_s">
                                    <div class="panel-heading">
                                        <h5 class="panel-title"><?php echo _l('zra_unsubmitted_invoices'); ?></h5>
                                        <div class="panel-heading-actions">
                                            <button type="button" class="btn btn-sm btn-info" id="refresh-invoices">
                                                <i class="fa fa-refresh"></i> <?php echo _l('refresh'); ?>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-default" id="select-all">
                                                <i class="fa fa-check-square-o"></i> <?php echo _l('select_all'); ?>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-default" id="deselect-all">
                                                <i class="fa fa-square-o"></i> <?php echo _l('deselect_all'); ?>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="panel-body">
                                        <?php if (!empty($unsubmitted_invoices)) { ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th width="50">
                                                            <input type="checkbox" id="master-checkbox">
                                                        </th>
                                                        <th><?php echo _l('invoice_number'); ?></th>
                                                        <th><?php echo _l('invoice_date'); ?></th>
                                                        <th><?php echo _l('client'); ?></th>
                                                        <th><?php echo _l('invoice_total'); ?></th>
                                                        <th><?php echo _l('actions'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="invoices-table-body">
                                                    <?php foreach ($unsubmitted_invoices as $invoice) { ?>
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" name="invoice_ids[]" value="<?php echo $invoice->id; ?>" class="invoice-checkbox">
                                                        </td>
                                                        <td>
                                                            <a href="<?php echo admin_url('invoices/list_invoices/' . $invoice->id); ?>" target="_blank">
                                                                <?php echo $invoice->number; ?>
                                                            </a>
                                                        </td>
                                                        <td><?php echo _d($invoice->date); ?></td>
                                                        <td><?php echo $invoice->client_name; ?></td>
                                                        <td><?php echo app_format_money($invoice->total, get_base_currency()); ?></td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-primary submit-single" data-invoice-id="<?php echo $invoice->id; ?>">
                                                                <i class="fa fa-send"></i> <?php echo _l('zra_submit'); ?>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-12">
                                                <hr>
                                                <div class="bulk-actions">
                                                    <button type="submit" name="action" value="submit_selected" class="btn btn-primary" id="bulk-submit-btn" disabled>
                                                        <i class="fa fa-send"></i> <?php echo _l('zra_submit_selected'); ?>
                                                        <span class="selected-count">(0 <?php echo _l('selected'); ?>)</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php } else { ?>
                                        <div class="text-center">
                                            <h4 class="text-muted">
                                                <i class="fa fa-check-circle text-success"></i>
                                                <?php echo _l('zra_no_unsubmitted_invoices'); ?>
                                            </h4>
                                            <p class="text-muted"><?php echo _l('zra_all_invoices_submitted'); ?></p>
                                        </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Progress Modal -->
<div class="modal fade" id="progress-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?php echo _l('zra_submitting_invoices'); ?></h4>
            </div>
            <div class="modal-body">
                <div class="progress">
                    <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%">
                        <span class="sr-only">0% Complete</span>
                    </div>
                </div>
                <p class="text-center" id="progress-text"><?php echo _l('zra_preparing_submission'); ?></p>
                <div id="progress-details"></div>
            </div>
        </div>
    </div>
</div>

<script>
    window.zraManualSubmitConfig = {
        submitInvoiceUrl: '<?php echo admin_url("zra_martin_invoicing/submit_invoice/"); ?>',
        bulkSubmitUrl: '<?php echo admin_url("zra_martin_invoicing/bulk_submit"); ?>',
        selectedText: '<?php echo _l("selected"); ?>',
        submittingText: '<?php echo _l("submitting"); ?>',
        successSubmitText: '<?php echo _l("zra_invoice_submitted_successfully"); ?>',
        failedSubmitText: '<?php echo _l("zra_invoice_submission_failed"); ?>',
        successBulkText: '<?php echo _l("zra_invoices_submitted_successfully"); ?>',
        failedBulkText: '<?php echo _l("zra_invoices_submission_failed"); ?>',
        preparingSubmissionText: '<?php echo _l("zra_preparing_submission"); ?>',
        processingInvoiceText: '<?php echo _l("zra_processing_invoice"); ?>',
        ofText: '<?php echo _l("of"); ?>',
        noInvoicesSelectedText: '<?php echo _l("zra_no_invoices_selected"); ?>',
        csrfData: {
            '<?php echo $this->security->get_csrf_token_name(); ?>': '<?php echo $this->security->get_csrf_hash(); ?>'
        }
    };
</script>
<script src="<?php echo base_url('modules/zra_martin_invoicing/assets/js/manual_submit.js?v=' . filemtime(__DIR__ . '/../assets/js/manual_submit.js')); ?>"></script>

<?php init_tail(); ?>