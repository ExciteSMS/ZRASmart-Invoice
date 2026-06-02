<?php

// ZRA Martin Invoicing Module Language File - English

$lang['zra_invoicing'] = 'ZRA Invoicing';
$lang['zra_invoicing_dashboard'] = 'ZRA Invoicing Dashboard';
$lang['zra_settings'] = 'ZRA Settings';
$lang['zra_logs'] = 'ZRA Logs';

// Dashboard
$lang['zra_total_submissions'] = 'Total Submissions';
$lang['zra_successful_submissions'] = 'Successful';
$lang['zra_failed_submissions'] = 'Failed';
$lang['zra_pending_submissions'] = 'Pending';
$lang['zra_quick_actions'] = 'Quick Actions';
$lang['zra_recent_logs'] = 'Recent Activity';
$lang['zra_test_connection'] = 'Test Connection';
$lang['zra_view_logs'] = 'View Logs';

// Settings
$lang['zra_general_settings'] = 'General Settings';
$lang['zra_api_settings'] = 'API Settings';
$lang['zra_tax_settings'] = 'Tax Settings';
$lang['zra_enable_integration'] = 'Enable ZRA Integration';
$lang['zra_enabled_tooltip'] = 'Enable or disable the ZRA Smart Invoice integration';
$lang['zra_environment'] = 'Environment';
$lang['zra_test_environment'] = 'Test Environment';
$lang['zra_production_environment'] = 'Production Environment';
$lang['zra_auto_submit_invoices'] = 'Auto Submit Invoices';
$lang['zra_auto_submit_help'] = 'Automatically submit invoices to ZRA when created or updated';
$lang['zra_debug_mode'] = 'Debug Mode';
$lang['zra_debug_mode_help'] = 'Enable debug mode for detailed logging (disable in production)';

// API Settings
$lang['zra_api_url'] = 'ZRA API URL';
$lang['zra_api_url_help'] = 'The URL of your ZRA VSDC API endpoint';
$lang['zra_company_tin'] = 'Company TIN';
$lang['zra_company_tin_help'] = 'Your company\'s Tax Identification Number (exactly 10 digits)';
$lang['zra_branch_id'] = 'Branch ID';
$lang['zra_branch_id_help'] = 'Your branch location identifier as supplied by ZRA (3 characters, default: 000)';
$lang['zra_device_serial'] = 'Device Serial Number';
$lang['zra_device_serial_help'] = 'Device serial number from ZRA device management portal';
$lang['zra_timeout'] = 'API Timeout (seconds)';
$lang['zra_timeout_help'] = 'Timeout for API requests in seconds (5-120)';

// Tax Settings
$lang['zra_tax_rate_standard'] = 'Standard Tax Rate (%)';
$lang['zra_tax_rate_standard_help'] = 'Standard VAT rate (typically 16% in Zambia)';
$lang['zra_tax_rate_zero'] = 'Zero Tax Rate (%)';
$lang['zra_tax_rate_zero_help'] = 'Zero-rated tax percentage';
$lang['zra_default_currency'] = 'Default Currency';
$lang['zra_default_currency_help'] = 'Default currency code (ZMW for Zambian Kwacha)';
$lang['zra_tax_codes_info'] = 'ZRA Tax Codes Reference';

// Logs
$lang['zra_request_type'] = 'Request Type';
$lang['zra_invoice_number'] = 'ZRA Invoice Number';
$lang['zra_log_details'] = 'Log Details';
$lang['zra_retry_submission'] = 'Retry Submission';

// Messages
$lang['zra_invoice_submitted_successfully'] = 'Invoice submitted to ZRA successfully';
$lang['zra_invoice_submission_failed'] = 'Invoice submission to ZRA failed';
$lang['zra_connection_successful'] = 'ZRA API connection successful';
$lang['zra_connection_failed'] = 'ZRA API connection failed';
$lang['zra_connection_error'] = 'Error testing ZRA connection';
$lang['zra_initialize_device'] = 'Initialize Device';
$lang['zra_device_initialization_successful'] = 'Device initialization successful';
$lang['zra_device_initialization_failed'] = 'Device initialization failed';
$lang['zra_device_initialization_status'] = 'Device Initialization Status';
$lang['zra_device_initialized'] = 'Initialized';
$lang['zra_retrieve_standard_codes'] = 'Retrieve Standard Codes';
$lang['zra_retrieve_item_classification_codes'] = 'Retrieve Item Classification Codes';
$lang['zra_retry_pending_submissions'] = 'Retry Pending Submissions';
$lang['zra_codes_retrieved_successfully'] = 'Codes retrieved successfully';
$lang['zra_codes_retrieval_failed'] = 'Failed to retrieve codes';
$lang['zra_pending_retries_processed'] = 'Pending submissions retry triggered';
$lang['zra_pending_retries_failed'] = 'Retrying pending submissions failed';
$lang['zra_device_not_initialized'] = 'Not Initialized';
$lang['zra_device_initialized_message'] = 'The VSDC device has been successfully initialized.';
$lang['zra_device_not_initialized_message'] = 'Complete device initialization to obtain the VSDC configuration.';
$lang['zra_invoice_already_submitted'] = 'Invoice already submitted to ZRA';
$lang['zra_invalid_invoice'] = 'Invalid invoice data';
$lang['zra_api_error'] = 'ZRA API Error';
$lang['zra_not_configured'] = 'ZRA integration not properly configured';

// Permissions
$lang['permission_view'] = 'View';
$lang['permission_create'] = 'Create';
$lang['permission_edit'] = 'Edit';
$lang['permission_delete'] = 'Delete';

// Invoice Status
$lang['zra_status_not_submitted'] = 'Not Submitted';
$lang['zra_status_submitted'] = 'Submitted';
$lang['zra_status_success'] = 'Success';
$lang['zra_status_failed'] = 'Failed';
$lang['zra_status_pending'] = 'Pending';

// Error Messages
$lang['zra_error_invalid_tin'] = 'Invalid TIN format';
$lang['zra_error_missing_client_info'] = 'Missing client information';
$lang['zra_error_invalid_amount'] = 'Invalid amount';
$lang['zra_error_connection_timeout'] = 'Connection timeout';
$lang['zra_error_server_error'] = 'Server error';
$lang['zra_error_invalid_response'] = 'Invalid API response';

// Form Labels
$lang['save_settings'] = 'Save Settings';
$lang['testing'] = 'Testing';
$lang['success'] = 'Success';
$lang['failed'] = 'Failed';
$lang['pending'] = 'Pending';
$lang['error_code'] = 'Error Code';
$lang['view_details'] = 'View Details';
$lang['close'] = 'Close';
$lang['invoice'] = 'Invoice';
$lang['status'] = 'Status';
$lang['date'] = 'Date';
$lang['options'] = 'Options';
$lang['id'] = 'ID';
$lang['enabled'] = 'Enabled';
$lang['disabled'] = 'Disabled';
$lang['yes'] = 'Yes';
$lang['no'] = 'No';
$lang['something_went_wrong'] = 'Something went wrong';

// Additional functionality
$lang['zra_manual_submit'] = 'Manual Submit';
$lang['zra_fetch_invoices'] = 'Fetch Invoices';
$lang['zra_manual_submit_info'] = 'Manually submit invoices to ZRA Smart Invoice system. Select invoices below and click submit.';
$lang['zra_fetch_invoices_info'] = 'Fetch invoice status and updates from ZRA Smart Invoice system.';
$lang['zra_unsubmitted_invoices'] = 'Unsubmitted Invoices';
$lang['zra_no_unsubmitted_invoices'] = 'No Unsubmitted Invoices';
$lang['zra_all_invoices_submitted'] = 'All invoices have been submitted to ZRA.';
$lang['zra_submit_selected'] = 'Submit Selected';
$lang['zra_submit'] = 'Submit';
$lang['zra_submitting_invoices'] = 'Submitting Invoices';
$lang['zra_preparing_submission'] = 'Preparing submission...';
$lang['zra_processing_invoice'] = 'Processing invoice';
$lang['zra_invoices_submitted_successfully'] = 'invoices submitted successfully';
$lang['zra_invoices_submission_failed'] = 'invoices failed to submit';
$lang['zra_no_invoices_selected'] = 'No invoices selected';
$lang['zra_bulk_submit_success'] = '%d invoices submitted successfully';
$lang['zra_bulk_submit_failed'] = '%d invoices failed to submit';

// Fetch functionality
$lang['zra_fetch_single_invoice'] = 'Fetch Single Invoice';
$lang['zra_fetch_all_pending'] = 'Fetch All Pending';
$lang['zra_fetch_all_pending_info'] = 'Fetch status updates for all pending/failed invoice submissions.';
$lang['zra_invoice_reference'] = 'Invoice Reference';
$lang['zra_invoice_reference_placeholder'] = 'e.g., INV-000001';
$lang['zra_invoice_reference_help'] = 'Enter the invoice reference number used when submitting to ZRA';
$lang['zra_invoice_reference_required'] = 'Invoice reference is required';
$lang['zra_fetch_invoice'] = 'Fetch Invoice';
$lang['zra_fetch_success'] = 'Invoice fetched successfully';
$lang['zra_fetch_failed'] = 'Failed to fetch invoice';
$lang['zra_fetch_all_success'] = '%d invoices fetched successfully';
$lang['zra_no_pending_invoices'] = 'No pending invoices found';
$lang['zra_quick_fetch_actions'] = 'Quick Fetch Actions';
$lang['zra_fetch_by_reference'] = 'Fetch by Reference';
$lang['zra_bulk_operations'] = 'Bulk Operations';
$lang['zra_fetch_results'] = 'Fetch Results';
$lang['zra_fetching_invoices'] = 'Fetching Invoices';
$lang['zra_fetching_please_wait'] = 'Fetching invoice data, please wait...';
$lang['zra_fetching_pending_invoices'] = 'Fetching pending invoices...';
$lang['zra_invoices_fetched_successfully'] = 'invoices fetched successfully';

// Common terms
$lang['fetch'] = 'Fetch';
$lang['fetching'] = 'Fetching';
$lang['submitting'] = 'Submitting';
$lang['selected'] = 'selected';
$lang['of'] = 'of';
$lang['refresh'] = 'Refresh';
$lang['select_all'] = 'Select All';
$lang['deselect_all'] = 'Deselect All';
$lang['invoice_number'] = 'Invoice Number';
$lang['invoice_date'] = 'Invoice Date';
$lang['client'] = 'Client';
$lang['invoice_total'] = 'Invoice Total';
$lang['actions'] = 'Actions';

?>