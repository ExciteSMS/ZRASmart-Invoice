<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">
                            <i class="fa fa-cog"></i>
                            <?php echo _l('zra_settings'); ?>
                        </h4>
                        <hr class="hr-panel-heading">
                        
                        <?php echo form_open(admin_url('zra_martin_invoicing/settings')); ?>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="horizontal-scrollable-tabs preview-tabs-top">
                                    <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
                                    <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
                                    <div class="horizontal-tabs">
                                        <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
                                            <li role="presentation" class="<?php echo (!$tab || $tab == 'general') ? 'active' : ''; ?>">
                                                <a href="#general" aria-controls="general" role="tab" data-toggle="tab">
                                                    <?php echo _l('zra_general_settings'); ?>
                                                </a>
                                            </li>
                                            <li role="presentation" class="<?php echo ($tab == 'api') ? 'active' : ''; ?>">
                                                <a href="#api" aria-controls="api" role="tab" data-toggle="tab">
                                                    <?php echo _l('zra_api_settings'); ?>
                                                </a>
                                            </li>
                                            <li role="presentation" class="<?php echo ($tab == 'tax') ? 'active' : ''; ?>">
                                                <a href="#tax" aria-controls="tax" role="tab" data-toggle="tab">
                                                    <?php echo _l('zra_tax_settings'); ?>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="tab-content">
                                    <!-- General Settings Tab -->
                                    <div role="tabpanel" class="tab-pane <?php echo (!$tab || $tab == 'general') ? 'active' : ''; ?>" id="general">
                                        <div class="form-group">
                                            <label for="zra_enabled" class="control-label">
                                                <i class="fa fa-question-circle" data-toggle="tooltip" 
                                                   data-title="<?php echo _l('zra_enabled_tooltip'); ?>"></i>
                                                <?php echo _l('zra_enable_integration'); ?>
                                            </label>
                                            <select name="zra_enabled" id="zra_enabled" class="selectpicker form-control" data-width="100%">
                                                <option value="0" <?php echo (get_option('zra_enabled') == '0') ? 'selected' : ''; ?>>
                                                    <?php echo _l('disabled'); ?>
                                                </option>
                                                <option value="1" <?php echo (get_option('zra_enabled') == '1') ? 'selected' : ''; ?>>
                                                    <?php echo _l('enabled'); ?>
                                                </option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="zra_environment" class="control-label"><?php echo _l('zra_environment'); ?></label>
                                            <select name="zra_environment" id="zra_environment" class="selectpicker form-control" data-width="100%">
                                                <option value="test" <?php echo (get_option('zra_environment') == 'test') ? 'selected' : ''; ?>>
                                                    <?php echo _l('zra_test_environment'); ?>
                                                </option>
                                                <option value="production" <?php echo (get_option('zra_environment') == 'production') ? 'selected' : ''; ?>>
                                                    <?php echo _l('zra_production_environment'); ?>
                                                </option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="zra_auto_submit_invoices" class="control-label">
                                                <?php echo _l('zra_auto_submit_invoices'); ?>
                                            </label>
                                            <select name="zra_auto_submit_invoices" id="zra_auto_submit_invoices" class="selectpicker form-control" data-width="100%">
                                                <option value="0" <?php echo (get_option('zra_auto_submit_invoices') == '0') ? 'selected' : ''; ?>>
                                                    <?php echo _l('no'); ?>
                                                </option>
                                                <option value="1" <?php echo (get_option('zra_auto_submit_invoices') == '1') ? 'selected' : ''; ?>>
                                                    <?php echo _l('yes'); ?>
                                                </option>
                                            </select>
                                            <small class="help-block"><?php echo _l('zra_auto_submit_help'); ?></small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="zra_debug_mode" class="control-label"><?php echo _l('zra_debug_mode'); ?></label>
                                            <select name="zra_debug_mode" id="zra_debug_mode" class="selectpicker form-control" data-width="100%">
                                                <option value="0" <?php echo (get_option('zra_debug_mode') == '0') ? 'selected' : ''; ?>>
                                                    <?php echo _l('disabled'); ?>
                                                </option>
                                                <option value="1" <?php echo (get_option('zra_debug_mode') == '1') ? 'selected' : ''; ?>>
                                                    <?php echo _l('enabled'); ?>
                                                </option>
                                            </select>
                                            <small class="help-block"><?php echo _l('zra_debug_mode_help'); ?></small>
                                        </div>
                                    </div>
                                    
                                    <!-- API Settings Tab -->
                                    <div role="tabpanel" class="tab-pane <?php echo ($tab == 'api') ? 'active' : ''; ?>" id="api">
                                        <div class="form-group">
                                            <label for="zra_api_url" class="control-label clearfix"><?php echo _l('zra_api_url'); ?></label>
                                            <?php echo render_input('zra_api_url', '', get_option('zra_api_url'), 'url', ['placeholder' => 'https://localhost:8080/zrasandboxvsdc']); ?>
                                            <small class="help-block"><?php echo _l('zra_api_url_help'); ?></small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="zra_company_tin" class="control-label clearfix"><?php echo _l('zra_company_tin'); ?></label>
                                            <?php echo render_input('zra_company_tin', '', get_option('zra_company_tin'), 'text', ['placeholder' => '1004097050', 'maxlength' => '10']); ?>
                                            <small class="help-block"><?php echo _l('zra_company_tin_help'); ?></small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="zra_branch_id" class="control-label clearfix"><?php echo _l('zra_branch_id'); ?></label>
                                            <?php echo render_input('zra_branch_id', '', get_option('zra_branch_id') ?: '000', 'text', ['placeholder' => '000', 'maxlength' => '3']); ?>
                                            <small class="help-block"><?php echo _l('zra_branch_id_help'); ?></small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="zra_company_name" class="control-label clearfix"><?php echo _l('zra_company_name'); ?></label>
                                            <?php echo render_input('zra_company_name', '', get_option('zra_company_name'), 'text', ['placeholder' => 'Your Company Name']); ?>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="zra_security_key" class="control-label clearfix"><?php echo _l('zra_security_key'); ?></label>
                                            <?php echo render_input('zra_security_key', '', get_option('zra_security_key'), 'password', ['placeholder' => 'Your ZRA Security Key']); ?>
                                            <small class="help-block"><?php echo _l('zra_security_key_help'); ?></small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="zra_timeout" class="control-label clearfix"><?php echo _l('zra_timeout'); ?></label>
                                            <?php echo render_input('zra_timeout', '', get_option('zra_timeout') ?: '30', 'number', ['min' => '5', 'max' => '120']); ?>
                                            <small class="help-block"><?php echo _l('zra_timeout_help'); ?></small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <button type="button" class="btn btn-info" id="test-api-connection">
                                                <i class="fa fa-plug"></i> <?php echo _l('zra_test_connection'); ?>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Tax Settings Tab -->
                                    <div role="tabpanel" class="tab-pane <?php echo ($tab == 'tax') ? 'active' : ''; ?>" id="tax">
                                        <div class="form-group">
                                            <label for="zra_tax_rate_standard" class="control-label clearfix"><?php echo _l('zra_tax_rate_standard'); ?></label>
                                            <?php echo render_input('zra_tax_rate_standard', '', get_option('zra_tax_rate_standard') ?: '16', 'number', ['step' => '0.01', 'min' => '0', 'max' => '100']); ?>
                                            <small class="help-block"><?php echo _l('zra_tax_rate_standard_help'); ?></small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="zra_tax_rate_zero" class="control-label clearfix"><?php echo _l('zra_tax_rate_zero'); ?></label>
                                            <?php echo render_input('zra_tax_rate_zero', '', get_option('zra_tax_rate_zero') ?: '0', 'number', ['step' => '0.01', 'min' => '0', 'max' => '100']); ?>
                                            <small class="help-block"><?php echo _l('zra_tax_rate_zero_help'); ?></small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="zra_default_currency" class="control-label clearfix"><?php echo _l('zra_default_currency'); ?></label>
                                            <?php echo render_input('zra_default_currency', '', get_option('zra_default_currency') ?: 'ZMW', 'text', ['maxlength' => '3']); ?>
                                            <small class="help-block"><?php echo _l('zra_default_currency_help'); ?></small>
                                        </div>
                                        
                                        <div class="alert alert-info">
                                            <h5><?php echo _l('zra_tax_codes_info'); ?></h5>
                                            <ul>
                                                <li><strong>A:</strong> Exempted (0%)</li>
                                                <li><strong>B:</strong> Minimum Taxable Value (16%)</li>
                                                <li><strong>C:</strong> Exports (0%)</li>
                                                <li><strong>D:</strong> Zero-rating LPO</li>
                                                <li><strong>F:</strong> Standard Rated (16%)</li>
                                                <li><strong>G:</strong> Economy Rate (0%)</li>
                                                <li><strong>H:</strong> Exempt (0%)</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <hr>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> <?php echo _l('save_settings'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#test-api-connection').on('click', function() {
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