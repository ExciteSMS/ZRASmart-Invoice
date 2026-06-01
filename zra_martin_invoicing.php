<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: ZRA Martin Invoicing
Description: Integration module for Zambia Revenue Authority (ZRA) Smart Invoice system
Version: 1.1.0
Requires at least: 2.3.0
Author: Kazashim Kuzasuwat
*/

define('ZRA_MARTIN_INVOICING_MODULE_NAME', 'zra_martin_invoicing');
define('ZRA_MARTIN_INVOICING_PATH', 'modules/zra_martin_invoicing/');

// Module activation hook
register_activation_hook(ZRA_MARTIN_INVOICING_MODULE_NAME, 'zra_martin_invoicing_activation_hook');

function zra_martin_invoicing_activation_hook()
{
    $CI = &get_instance();
    
    // Create necessary database tables
    require_once(__DIR__ . '/install/install.php');
}

// Module deactivation hook
register_deactivation_hook(ZRA_MARTIN_INVOICING_MODULE_NAME, 'zra_martin_invoicing_deactivation_hook');

function zra_martin_invoicing_deactivation_hook()
{
    // Clean up resources if needed
}

// Add admin menu items
hooks()->add_action('admin_init', 'zra_martin_invoicing_init_menu_items');

function zra_martin_invoicing_init_menu_items()
{
    if (has_permission('zra_invoicing', '', 'view')) {
        $CI = &get_instance();
        
        // Main menu item
        $CI->app_menu->add_sidebar_menu_item('zra-invoicing', [
            'name'     => _l('zra_invoicing'),
            'href'     => admin_url('zra_martin_invoicing'),
            'icon'     => 'fa fa-file-text-o',
            'position' => 35,
        ]);
        
        // Sub-menu items
        $CI->app_menu->add_sidebar_children_item('zra-invoicing', [
            'slug'     => 'zra-manual-submit',
            'name'     => _l('zra_manual_submit'),
            'href'     => admin_url('zra_martin_invoicing/manual_submit'),
            'position' => 1,
        ]);
        
        $CI->app_menu->add_sidebar_children_item('zra-invoicing', [
            'slug'     => 'zra-fetch-invoices',
            'name'     => _l('zra_fetch_invoices'),
            'href'     => admin_url('zra_martin_invoicing/fetch_invoices'),
            'position' => 2,
        ]);
        
        $CI->app_menu->add_sidebar_children_item('zra-invoicing', [
            'slug'     => 'zra-settings',
            'name'     => _l('zra_settings'),
            'href'     => admin_url('zra_martin_invoicing/settings'),
            'position' => 3,
        ]);
        
        $CI->app_menu->add_sidebar_children_item('zra-invoicing', [
            'slug'     => 'zra-logs',
            'name'     => _l('zra_logs'),
            'href'     => admin_url('zra_martin_invoicing/logs'),
            'position' => 4,
        ]);
    }
}

// Register language files
register_language_files(ZRA_MARTIN_INVOICING_MODULE_NAME, [ZRA_MARTIN_INVOICING_MODULE_NAME]);

// Hook into invoice creation
hooks()->add_action('after_invoice_added', 'zra_martin_invoicing_after_invoice_added');
hooks()->add_action('after_invoice_updated', 'zra_martin_invoicing_after_invoice_updated');

function zra_martin_invoicing_after_invoice_added($invoice_id)
{
    if (get_option('zra_auto_submit_invoices') == '1') {
        zra_martin_invoicing_submit_invoice($invoice_id);
    }
}

function zra_martin_invoicing_after_invoice_updated($invoice_id)
{
    if (get_option('zra_auto_submit_invoices') == '1') {
        zra_martin_invoicing_submit_invoice($invoice_id);
    }
}

function zra_martin_invoicing_submit_invoice($invoice_id)
{
    $CI = &get_instance();
    $CI->load->model(ZRA_MARTIN_INVOICING_MODULE_NAME . '/zra_api_model');
    
    return $CI->zra_api_model->submit_invoice($invoice_id);
}

// Add permissions
hooks()->add_action('admin_init', 'zra_martin_invoicing_permissions');

function zra_martin_invoicing_permissions()
{
    $capabilities = [];
    
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view') . '(' . _l('zra_invoicing') . ')',
        'create' => _l('permission_create') . '(' . _l('zra_invoicing') . ')',
        'edit'   => _l('permission_edit') . '(' . _l('zra_invoicing') . ')',
        'delete' => _l('permission_delete') . '(' . _l('zra_invoicing') . ')',
    ];
    
    register_staff_capabilities('zra_invoicing', $capabilities, _l('zra_invoicing'));
}

// Add settings tab
hooks()->add_action('before_settings_updated', 'zra_martin_invoicing_before_settings_updated');

function zra_martin_invoicing_before_settings_updated()
{
    // Validate ZRA settings before saving
    $CI = &get_instance();
    
    if (isset($_POST['zra_company_tin']) && !empty($_POST['zra_company_tin'])) {
        if (strlen($_POST['zra_company_tin']) != 10) {
            set_alert('danger', 'ZRA Company TIN must be exactly 10 characters');
            redirect(admin_url('settings?group=zra_settings'));
        }
    }
}

?>