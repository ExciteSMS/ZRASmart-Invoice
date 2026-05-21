<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">
                            <i class="fa fa-file-text-o"></i>
                            <?php echo _l('zra_invoicing_dashboard'); ?>
                        </h4>
                        <hr class="hr-panel-heading">
                        
                        <!-- Statistics Cards -->
                        <div class="row">
                            <div class="col-lg-3 col-md-6">
                                <div class="panel_s stats-wrapper">
                                    <div class="panel-body">
                                        <div class="widget-dragger"></div>
                                        <div class="row">
                                            <div class="col-xs-8">
                                                <span class="text-dark stats-title"><?php echo _l('zra_total_submissions'); ?></span>
                                                <br />
                                                <span class="stats-number"><?php echo $stats['total_submissions']; ?></span>
                                            </div>
                                            <div class="col-xs-4 text-right">
                                                <i class="fa fa-file-text stats-icon" style="color:#28B8DA"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-3 col-md-6">
                                <div class="panel_s stats-wrapper">
                                    <div class="panel-body">
                                        <div class="widget-dragger"></div>
                                        <div class="row">
                                            <div class="col-xs-8">
                                                <span class="text-dark stats-title"><?php echo _l('zra_successful_submissions'); ?></span>
                                                <br />
                                                <span class="stats-number text-success"><?php echo $stats['successful_submissions']; ?></span>
                                            </div>
                                            <div class="col-xs-4 text-right">
                                                <i class="fa fa-check-circle stats-icon text-success"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-3 col-md-6">
                                <div class="panel_s stats-wrapper">
                                    <div class="panel-body">
                                        <div class="widget-dragger"></div>
                                        <div class="row">
                                            <div class="col-xs-8">
                                                <span class="text-dark stats-title"><?php echo _l('zra_failed_submissions'); ?></span>
                                                <br />
                                                <span class="stats-number text-danger"><?php echo $stats['failed_submissions']; ?></span>
                                            </div>
                                            <div class="col-xs-4 text-right">
                                                <i class="fa fa-times-circle stats-icon text-danger"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-3 col-md-6">
                                <div class="panel_s stats-wrapper">
                                    <div class="panel-body">
                                        <div class="widget-dragger"></div>
                                        <div class="row">
                                            <div class="col-xs-8">
                                                <span class="text-dark stats-title"><?php echo _l('zra_pending_submissions'); ?></span>
                                                <br />
                                                <span class="stats-number text-warning"><?php echo $stats['pending_submissions']; ?></span>
                                            </div>
                                            <div class="col-xs-4 text-right">
                                                <i class="fa fa-clock-o stats-icon text-warning"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="panel_s">
                                    <div class="panel-body">
                                        <h5><?php echo _l('zra_quick_actions'); ?></h5>
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo admin_url('zra_martin_invoicing/manual_submit'); ?>" class="btn btn-primary">
                                                <i class="fa fa-send"></i> <?php echo _l('zra_manual_submit'); ?>
                                            </a>
                                            <a href="<?php echo admin_url('zra_martin_invoicing/fetch_invoices'); ?>" class="btn btn-info">
                                                <i class="fa fa-download"></i> <?php echo _l('zra_fetch_invoices'); ?>
                                            </a>
                                            <a href="<?php echo admin_url('zra_martin_invoicing/settings'); ?>" class="btn btn-default">
                                                <i class="fa fa-cog"></i> <?php echo _l('zra_settings'); ?>
                                            </a>
                                            <button type="button" class="btn btn-warning" id="test-connection">
                                                <i class="fa fa-plug"></i> <?php echo _l('zra_test_connection'); ?>
                                            </button>
                                            <a href="<?php echo admin_url('zra_martin_invoicing/logs'); ?>" class="btn btn-default">
                                                <i class="fa fa-list"></i> <?php echo _l('zra_view_logs'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Logs -->
                        <?php if (!empty($recent_logs)) { ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="panel_s">
                                    <div class="panel-body">
                                        <h5><?php echo _l('zra_recent_logs'); ?></h5>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th><?php echo _l('invoice'); ?></th>
                                                        <th><?php echo _l('zra_request_type'); ?></th>
                                                        <th><?php echo _l('status'); ?></th>
                                                        <th><?php echo _l('zra_invoice_number'); ?></th>
                                                        <th><?php echo _l('date'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recent_logs as $log) { ?>
                                                    <tr>
                                                        <td>
                                                            <a href="<?php echo admin_url('invoices/list_invoices/' . $log->invoice_id); ?>">
                                                                <?php echo $log->invoice_number; ?>
                                                            </a>
                                                        </td>
                                                        <td><?php echo ucfirst(str_replace('_', ' ', $log->request_type)); ?></td>
                                                        <td>
                                                            <?php if ($log->status == 'success') { ?>
                                                                <span class="label label-success"><?php echo _l('success'); ?></span>
                                                            <?php } elseif ($log->status == 'failed') { ?>
                                                                <span class="label label-danger"><?php echo _l('failed'); ?></span>
                                                            <?php } else { ?>
                                                                <span class="label label-warning"><?php echo _l('pending'); ?></span>
                                                            <?php } ?>
                                                        </td>
                                                        <td><?php echo $log->zra_invoice_number ?: '-'; ?></td>
                                                        <td><?php echo _dt($log->created_at); ?></td>
                                                    </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#test-connection').on('click', function() {
        var btn = $(this);
        var originalText = btn.html();
        
        btn.html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l("testing"); ?>...');
        btn.prop('disabled', true);
        
        $.post('<?php echo admin_url("zra_martin_invoicing/test_connection"); ?>')
        .done(function(response) {
            var data = JSON.parse(response);
            if (data.success) {
                alert_float('success', '<?php echo _l("zra_connection_successful"); ?>');
            } else {
                alert_float('danger', '<?php echo _l("zra_connection_failed"); ?>: ' + data.message);
            }
        })
        .fail(function() {
            alert_float('danger', '<?php echo _l("zra_connection_error"); ?>');
        })
        .always(function() {
            btn.html(originalText);
            btn.prop('disabled', false);
        });
    });
});
</script>

<?php init_tail(); ?>