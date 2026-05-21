<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">
                            <i class="fa fa-download"></i>
                            <?php echo _l('zra_fetch_invoices'); ?>
                        </h4>
                        <hr class="hr-panel-heading">
                        
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            <?php echo _l('zra_fetch_invoices_info'); ?>
                        </div>
                        
                        <div class="row">
                            <!-- Fetch Single Invoice -->
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h5 class="panel-title">
                                            <i class="fa fa-search"></i>
                                            <?php echo _l('zra_fetch_single_invoice'); ?>
                                        </h5>
                                    </div>
                                    <div class="panel-body">
                                        <?php echo form_open(admin_url('zra_martin_invoicing/fetch_invoices'), ['id' => 'fetch-single-form']); ?>
                                        
                                        <div class="form-group">
                                            <label for="invoice_reference"><?php echo _l('zra_invoice_reference'); ?></label>
                                            <input type="text" name="invoice_reference" id="invoice_reference" class="form-control" 
                                                   placeholder="<?php echo _l('zra_invoice_reference_placeholder'); ?>" required>
                                            <small class="help-block"><?php echo _l('zra_invoice_reference_help'); ?></small>
                                        </div>
                                        
                                        <button type="submit" name="action" value="fetch_single" class="btn btn-primary">
                                            <i class="fa fa-search"></i> <?php echo _l('zra_fetch_invoice'); ?>
                                        </button>
                                        
                                        <?php echo form_close(); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Fetch All Pending -->
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h5 class="panel-title">
                                            <i class="fa fa-refresh"></i>
                                            <?php echo _l('zra_fetch_all_pending'); ?>
                                        </h5>
                                    </div>
                                    <div class="panel-body">
                                        <p><?php echo _l('zra_fetch_all_pending_info'); ?></p>
                                        
                                        <?php echo form_open(admin_url('zra_martin_invoicing/fetch_invoices'), ['id' => 'fetch-all-form']); ?>
                                        
                                        <button type="submit" name="action" value="fetch_all_pending" class="btn btn-warning">
                                            <i class="fa fa-refresh"></i> <?php echo _l('zra_fetch_all_pending'); ?>
                                        </button>
                                        
                                        <?php echo form_close(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- AJAX Fetch Options -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h5 class="panel-title">
                                            <i class="fa fa-bolt"></i>
                                            <?php echo _l('zra_quick_fetch_actions'); ?>
                                        </h5>
                                    </div>
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><?php echo _l('zra_fetch_by_reference'); ?></h6>
                                                <div class="input-group">
                                                    <input type="text" id="quick-invoice-reference" class="form-control" 
                                                           placeholder="<?php echo _l('zra_invoice_reference_placeholder'); ?>">
                                                    <span class="input-group-btn">
                                                        <button type="button" class="btn btn-primary" id="quick-fetch-btn">
                                                            <i class="fa fa-search"></i> <?php echo _l('fetch'); ?>
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6><?php echo _l('zra_bulk_operations'); ?></h6>
                                                <button type="button" class="btn btn-warning" id="fetch-all-pending-ajax">
                                                    <i class="fa fa-refresh"></i> <?php echo _l('zra_fetch_all_pending'); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Fetch Results -->
                        <div class="row" id="fetch-results" style="display: none;">
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h5 class="panel-title">
                                            <i class="fa fa-list"></i>
                                            <?php echo _l('zra_fetch_results'); ?>
                                        </h5>
                                    </div>
                                    <div class="panel-body">
                                        <div id="fetch-results-content"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fetch Progress Modal -->
<div class="modal fade" id="fetch-progress-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?php echo _l('zra_fetching_invoices'); ?></h4>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-3x text-primary"></i>
                    <p class="mt-20" id="fetch-status-text"><?php echo _l('zra_fetching_please_wait'); ?></p>
                </div>
                <div id="fetch-progress-details"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" disabled id="fetch-close-btn">
                    <?php echo _l('close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Quick fetch by reference
    $('#quick-fetch-btn').on('click', function() {
        var invoiceReference = $('#quick-invoice-reference').val().trim();
        
        if (!invoiceReference) {
            alert_float('warning', '<?php echo _l("zra_invoice_reference_required"); ?>');
            return;
        }
        
        fetchSingleInvoice(invoiceReference);
    });
    
    // Enter key on quick fetch input
    $('#quick-invoice-reference').on('keypress', function(e) {
        if (e.which === 13) {
            $('#quick-fetch-btn').click();
        }
    });
    
    // Fetch all pending invoices (AJAX)
    $('#fetch-all-pending-ajax').on('click', function() {
        fetchAllPendingInvoices();
    });
    
    function fetchSingleInvoice(invoiceReference) {
        var btn = $('#quick-fetch-btn');
        var originalText = btn.html();
        
        btn.html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l("fetching"); ?>...');
        btn.prop('disabled', true);
        
        $.post('<?php echo admin_url("zra_martin_invoicing/fetch_single_invoice"); ?>', {
            invoice_reference: invoiceReference
        })
        .done(function(response) {
            var data = JSON.parse(response);
            
            if (data.success) {
                alert_float('success', '<?php echo _l("zra_fetch_success"); ?>');
                displayFetchResults([{
                    invoice_reference: invoiceReference,
                    fetch_result: data
                }]);
            } else {
                alert_float('danger', '<?php echo _l("zra_fetch_failed"); ?>: ' + data.message);
            }
        })
        .fail(function() {
            alert_float('danger', '<?php echo _l("something_went_wrong"); ?>');
        })
        .always(function() {
            btn.html(originalText);
            btn.prop('disabled', false);
        });
    }
    
    function fetchAllPendingInvoices() {
        $('#fetch-progress-modal').modal('show');
        $('#fetch-status-text').text('<?php echo _l("zra_fetching_pending_invoices"); ?>');
        
        $.post('<?php echo admin_url("zra_martin_invoicing/fetch_all_pending"); ?>')
        .done(function(response) {
            var data = JSON.parse(response);
            
            if (data.success) {
                var fetchedCount = data.results.length;
                
                if (fetchedCount > 0) {
                    alert_float('success', fetchedCount + ' <?php echo _l("zra_invoices_fetched_successfully"); ?>');
                    displayFetchResults(data.results);
                } else {
                    alert_float('info', '<?php echo _l("zra_no_pending_invoices"); ?>');
                }
            } else {
                alert_float('danger', '<?php echo _l("zra_fetch_failed"); ?>: ' + data.message);
            }
        })
        .fail(function() {
            alert_float('danger', '<?php echo _l("something_went_wrong"); ?>');
        })
        .always(function() {
            $('#fetch-progress-modal').modal('hide');
        });
    }
    
    function displayFetchResults(results) {
        var html = '<div class="table-responsive">';
        html += '<table class="table table-striped">';
        html += '<thead>';
        html += '<tr>';
        html += '<th><?php echo _l("zra_invoice_reference"); ?></th>';
        html += '<th><?php echo _l("status"); ?></th>';
        html += '<th><?php echo _l("zra_invoice_number"); ?></th>';
        html += '<th><?php echo _l("message"); ?></th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';
        
        results.forEach(function(result) {
            var statusClass = result.fetch_result.success ? 'success' : 'danger';
            var statusIcon = result.fetch_result.success ? 'check' : 'times';
            var statusText = result.fetch_result.success ? '<?php echo _l("success"); ?>' : '<?php echo _l("failed"); ?>';
            
            html += '<tr>';
            html += '<td>' + result.invoice_reference + '</td>';
            html += '<td><span class="label label-' + statusClass + '"><i class="fa fa-' + statusIcon + '"></i> ' + statusText + '</span></td>';
            html += '<td>' + (result.fetch_result.data && result.fetch_result.data.INVOICE_NUMBER ? result.fetch_result.data.INVOICE_NUMBER : '-') + '</td>';
            html += '<td>' + (result.fetch_result.message || '-') + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody>';
        html += '</table>';
        html += '</div>';
        
        $('#fetch-results-content').html(html);
        $('#fetch-results').show();
        
        // Scroll to results
        $('html, body').animate({
            scrollTop: $('#fetch-results').offset().top - 100
        }, 1000);
    }
});
</script>

<?php init_tail(); ?>