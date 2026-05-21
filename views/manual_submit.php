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
$(document).ready(function() {
    // Master checkbox functionality
    $('#master-checkbox').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('.invoice-checkbox').prop('checked', isChecked);
        updateBulkActions();
    });
    
    // Individual checkbox functionality
    $('.invoice-checkbox').on('change', function() {
        updateBulkActions();
        updateMasterCheckbox();
    });
    
    // Select all button
    $('#select-all').on('click', function() {
        $('.invoice-checkbox').prop('checked', true);
        $('#master-checkbox').prop('checked', true);
        updateBulkActions();
    });
    
    // Deselect all button
    $('#deselect-all').on('click', function() {
        $('.invoice-checkbox').prop('checked', false);
        $('#master-checkbox').prop('checked', false);
        updateBulkActions();
    });
    
    // Submit single invoice
    $('.submit-single').on('click', function() {
        var invoiceId = $(this).data('invoice-id');
        var btn = $(this);
        var originalText = btn.html();
        
        btn.html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l("submitting"); ?>...');
        btn.prop('disabled', true);
        
        $.post('<?php echo admin_url("zra_martin_invoicing/submit_invoice/"); ?>' + invoiceId)
        .done(function(response) {
            var data = JSON.parse(response);
            if (data.success) {
                alert_float('success', '<?php echo _l("zra_invoice_submitted_successfully"); ?>');
                // Remove row from table
                btn.closest('tr').fadeOut();
            } else {
                alert_float('danger', '<?php echo _l("zra_invoice_submission_failed"); ?>: ' + data.message);
            }
        })
        .fail(function() {
            alert_float('danger', '<?php echo _l("something_went_wrong"); ?>');
        })
        .always(function() {
            btn.html(originalText);
            btn.prop('disabled', false);
        });
    });
    
    // Bulk submit form
    $('#manual-submit-form').on('submit', function(e) {
        e.preventDefault();
        
        var selectedInvoices = $('.invoice-checkbox:checked');
        if (selectedInvoices.length === 0) {
            alert_float('warning', '<?php echo _l("zra_no_invoices_selected"); ?>');
            return;
        }
        
        var invoiceIds = [];
        selectedInvoices.each(function() {
            invoiceIds.push($(this).val());
        });
        
        submitBulkInvoices(invoiceIds);
    });
    
    // Refresh invoices
    $('#refresh-invoices').on('click', function() {
        location.reload();
    });
    
    function updateBulkActions() {
        var selectedCount = $('.invoice-checkbox:checked').length;
        $('#bulk-submit-btn').prop('disabled', selectedCount === 0);
        $('.selected-count').text('(' + selectedCount + ' <?php echo _l("selected"); ?>)');
    }
    
    function updateMasterCheckbox() {
        var totalCheckboxes = $('.invoice-checkbox').length;
        var checkedCheckboxes = $('.invoice-checkbox:checked').length;
        
        if (checkedCheckboxes === 0) {
            $('#master-checkbox').prop('indeterminate', false);
            $('#master-checkbox').prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#master-checkbox').prop('indeterminate', false);
            $('#master-checkbox').prop('checked', true);
        } else {
            $('#master-checkbox').prop('indeterminate', true);
        }
    }
    
    function submitBulkInvoices(invoiceIds) {
        $('#progress-modal').modal('show');
        
        var totalInvoices = invoiceIds.length;
        var processedInvoices = 0;
        var successCount = 0;
        var failedCount = 0;
        
        function processNextInvoice(index) {
            if (index >= totalInvoices) {
                // All invoices processed
                $('#progress-modal').modal('hide');
                
                if (successCount > 0) {
                    alert_float('success', successCount + ' <?php echo _l("zra_invoices_submitted_successfully"); ?>');
                }
                if (failedCount > 0) {
                    alert_float('warning', failedCount + ' <?php echo _l("zra_invoices_submission_failed"); ?>');
                }
                
                // Refresh the page after 2 seconds
                setTimeout(function() {
                    location.reload();
                }, 2000);
                
                return;
            }
            
            var invoiceId = invoiceIds[index];
            var progress = Math.round(((index + 1) / totalInvoices) * 100);
            
            $('.progress-bar').css('width', progress + '%');
            $('#progress-text').text('<?php echo _l("zra_processing_invoice"); ?> ' + (index + 1) + ' <?php echo _l("of"); ?> ' + totalInvoices);
            
            $.post('<?php echo admin_url("zra_martin_invoicing/submit_invoice/"); ?>' + invoiceId)
            .done(function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    successCount++;
                    $('#progress-details').append('<div class="text-success"><i class="fa fa-check"></i> Invoice #' + invoiceId + ' - Success</div>');
                } else {
                    failedCount++;
                    $('#progress-details').append('<div class="text-danger"><i class="fa fa-times"></i> Invoice #' + invoiceId + ' - ' + data.message + '</div>');
                }
            })
            .fail(function() {
                failedCount++;
                $('#progress-details').append('<div class="text-danger"><i class="fa fa-times"></i> Invoice #' + invoiceId + ' - Connection Error</div>');
            })
            .always(function() {
                processedInvoices++;
                
                // Wait 1 second before processing next invoice to avoid overwhelming the API
                setTimeout(function() {
                    processNextInvoice(index + 1);
                }, 1000);
            });
        }
        
        processNextInvoice(0);
    }
});
</script>

<?php init_tail(); ?>