<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_buttons">
                            <div class="visible-xs">
                                <div class="clearfix"></div>
                            </div>
                        </div>
                        
                        <div class="clearfix"></div>
                        
                        <div class="panel_s mt-10">
                            <div class="panel-body">
                                <h4 class="no-margin">
                                    <i class="fa fa-list"></i>
                                    <?php echo _l('zra_logs'); ?>
                                </h4>
                                <hr class="hr-panel-heading">
                                
                                <div class="table-responsive">
                                    <table class="table dt-table table-zra-logs" data-order-col="0" data-order-type="desc">
                                        <thead>
                                            <tr>
                                                <th><?php echo _l('id'); ?></th>
                                                <th><?php echo _l('invoice'); ?></th>
                                                <th><?php echo _l('zra_request_type'); ?></th>
                                                <th><?php echo _l('status'); ?></th>
                                                <th><?php echo _l('zra_invoice_number'); ?></th>
                                                <th><?php echo _l('error_code'); ?></th>
                                                <th><?php echo _l('date'); ?></th>
                                                <th><?php echo _l('options'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="log-details-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?php echo _l('zra_log_details'); ?></h4>
            </div>
            <div class="modal-body">
                <div id="log-details-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    initDataTable('.table-zra-logs', '<?php echo admin_url('zra_martin_invoicing/logs'); ?>', undefined, undefined, undefined, [0, 'desc']);
    
    // Handle log details modal
    $(document).on('click', '.view-log-details', function(e) {
        e.preventDefault();
        var logId = $(this).data('log-id');
        
        $.get('<?php echo admin_url('zra_martin_invoicing/log_details/'); ?>' + logId)
        .done(function(response) {
            $('#log-details-content').html(response);
            $('#log-details-modal').modal('show');
        })
        .fail(function() {
            alert_float('danger', '<?php echo _l('something_went_wrong'); ?>');
        });
    });
});
</script>

<?php init_tail(); ?>