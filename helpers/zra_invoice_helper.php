<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Add ZRA status to invoice view
hooks()->add_action('after_invoice_view_as_client_link', 'add_zra_status_to_invoice_view');
hooks()->add_action('invoice_html_pdf_data', 'add_zra_qr_code_to_pdf');

function add_zra_status_to_invoice_view($invoice)
{
    if (!get_option('zra_enabled')) {
        return;
    }
    
    $CI = &get_instance();
    $CI->load->model(ZRA_MARTIN_INVOICING_MODULE_NAME . '/zra_api_model');
    
    $status = $CI->zra_api_model->get_invoice_zra_status($invoice->id);
    
    echo '<div class="col-md-6 col-sm-6">';
    echo '<p class="bold">' . _l('zra_status') . '</p>';
    
    switch ($status['status']) {
        case 'success':
            echo '<span class="label label-success">' . _l('zra_status_success') . '</span>';
            if ($status['zra_invoice_number']) {
                echo '<br><small>ZRA #: ' . $status['zra_invoice_number'] . '</small>';
            }
            break;
        case 'failed':
            echo '<span class="label label-danger">' . _l('zra_status_failed') . '</span>';
            if ($status['message']) {
                echo '<br><small>' . $status['message'] . '</small>';
            }
            break;
        case 'pending':
            echo '<span class="label label-warning">' . _l('zra_status_pending') . '</span>';
            break;
        default:
            echo '<span class="label label-default">' . _l('zra_status_not_submitted') . '</span>';
            if (has_permission('zra_invoicing', '', 'create')) {
                echo '<br><a href="' . admin_url('zra_martin_invoicing/submit_invoice/' . $invoice->id) . '" class="btn btn-sm btn-primary mt-5">';
                echo '<i class="fa fa-send"></i> ' . _l('zra_submit_to_zra');
                echo '</a>';
            }
    }
    
    echo '</div>';
}

function add_zra_qr_code_to_pdf($invoice_data)
{
    if (!get_option('zra_enabled')) {
        return $invoice_data;
    }
    
    $CI = &get_instance();
    $CI->load->model(ZRA_MARTIN_INVOICING_MODULE_NAME . '/zra_api_model');
    
    $status = $CI->zra_api_model->get_invoice_zra_status($invoice_data['invoice']->id);
    
    if ($status['status'] == 'success' && !empty($status['qr_code'])) {
        $invoice_data['zra_qr_code'] = $status['qr_code'];
        $invoice_data['zra_invoice_number'] = $status['zra_invoice_number'];
        $invoice_data['zra_fiscal_tax_id'] = $status['fiscal_tax_id'];
    }
    
    return $invoice_data;
}

// Add ZRA submit button to invoice actions
hooks()->add_action('after_invoice_view_as_client_link', 'add_zra_submit_button_to_invoice');

function add_zra_submit_button_to_invoice($invoice)
{
    if (!get_option('zra_enabled') || !has_permission('zra_invoicing', '', 'create')) {
        return;
    }
    
    $CI = &get_instance();
    $CI->load->model(ZRA_MARTIN_INVOICING_MODULE_NAME . '/zra_api_model');
    
    $status = $CI->zra_api_model->get_invoice_zra_status($invoice->id);
    
    if ($status['status'] != 'success') {
        echo '<div class="btn-group pull-right mleft4 btn-with-tooltip-group" data-toggle="tooltip" data-title="' . _l('zra_submit_to_zra') . '">';
        echo '<a href="' . admin_url('zra_martin_invoicing/submit_invoice/' . $invoice->id) . '" class="btn btn-default btn-with-tooltip" data-toggle="tooltip" data-title="' . _l('zra_submit_to_zra') . '">';
        echo '<i class="fa fa-send"></i>';
        echo '</a>';
        echo '</div>';
    }
}

// Add ZRA column to invoices table
hooks()->add_filter('invoices_table_columns', 'add_zra_column_to_invoices_table');

function add_zra_column_to_invoices_table($columns)
{
    if (!get_option('zra_enabled')) {
        return $columns;
    }
    
    $columns[] = [
        'name' => 'zra_status',
        'th'   => _l('zra_status'),
    ];
    
    return $columns;
}

// Add ZRA status data to invoices table
hooks()->add_filter('invoices_table_row_data', 'add_zra_status_to_invoices_table_row');

function add_zra_status_to_invoices_table_row($row, $invoice)
{
    if (!get_option('zra_enabled')) {
        return $row;
    }
    
    $CI = &get_instance();
    $CI->load->model(ZRA_MARTIN_INVOICING_MODULE_NAME . '/zra_api_model');
    
    $status = $CI->zra_api_model->get_invoice_zra_status($invoice['id']);
    
    $status_html = '';
    switch ($status['status']) {
        case 'success':
            $status_html = '<span class="label label-success"><i class="fa fa-check"></i></span>';
            break;
        case 'failed':
            $status_html = '<span class="label label-danger"><i class="fa fa-times"></i></span>';
            break;
        case 'pending':
            $status_html = '<span class="label label-warning"><i class="fa fa-clock-o"></i></span>';
            break;
        default:
            $status_html = '<span class="label label-default"><i class="fa fa-minus"></i></span>';
    }
    
    $row[] = $status_html;
    
    return $row;
}

?>