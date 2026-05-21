<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'id',
    'invoice_id',
    'request_type',
    'status',
    'zra_invoice_number',
    'error_code',
    'created_at'
];

$sIndexColumn = 'id';
$sTable = db_prefix() . 'zra_invoicing_logs';

$join = [
    'LEFT JOIN ' . db_prefix() . 'invoices ON ' . db_prefix() . 'invoices.id = ' . db_prefix() . 'zra_invoicing_logs.invoice_id'
];

$additionalSelect = [
    db_prefix() . 'invoices.number as invoice_number',
    db_prefix() . 'invoices.clientid'
];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, [], $additionalSelect);

$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    
    // ID
    $row[] = $aRow['id'];
    
    // Invoice
    $invoiceNumber = $aRow['invoice_number'] ?: 'INV-' . str_pad($aRow['invoice_id'], 6, '0', STR_PAD_LEFT);
    $row[] = '<a href="' . admin_url('invoices/list_invoices/' . $aRow['invoice_id']) . '">' . $invoiceNumber . '</a>';
    
    // Request Type
    $row[] = ucfirst(str_replace('_', ' ', $aRow['request_type']));
    
    // Status
    $statusLabel = '';
    switch ($aRow['status']) {
        case 'success':
            $statusLabel = '<span class="label label-success">' . _l('success') . '</span>';
            break;
        case 'failed':
            $statusLabel = '<span class="label label-danger">' . _l('failed') . '</span>';
            break;
        case 'pending':
            $statusLabel = '<span class="label label-warning">' . _l('pending') . '</span>';
            break;
        default:
            $statusLabel = '<span class="label label-default">' . ucfirst($aRow['status']) . '</span>';
    }
    $row[] = $statusLabel;
    
    // ZRA Invoice Number
    $row[] = $aRow['zra_invoice_number'] ?: '-';
    
    // Error Code
    $row[] = $aRow['error_code'] ?: '-';
    
    // Date
    $row[] = _dt($aRow['created_at']);
    
    // Options
    $options = '<div class="row-options">';
    $options .= '<a href="#" class="view-log-details" data-log-id="' . $aRow['id'] . '" data-toggle="tooltip" title="' . _l('view_details') . '">';
    $options .= '<i class="fa fa-eye"></i>';
    $options .= '</a>';
    
    if (has_permission('zra_invoicing', '', 'create') && $aRow['status'] == 'failed') {
        $options .= ' | <a href="' . admin_url('zra_martin_invoicing/submit_invoice/' . $aRow['invoice_id']) . '" class="text-warning" data-toggle="tooltip" title="' . _l('zra_retry_submission') . '">';
        $options .= '<i class="fa fa-refresh"></i>';
        $options .= '</a>';
    }
    $options .= '</div>';
    
    $row[] = $options;
    
    $output['aaData'][] = $row;
}

echo json_encode($output);

?>