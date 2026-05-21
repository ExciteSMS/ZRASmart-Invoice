<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Database installation for ZRA Martin Invoicing module

$CI = &get_instance();

// Create ZRA logs table
if (!$CI->db->table_exists(db_prefix() . 'zra_invoicing_logs')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'zra_invoicing_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `invoice_id` int(11) NOT NULL,
        `request_type` varchar(50) NOT NULL,
        `request_data` longtext,
        `response_data` longtext,
        `status` varchar(20) NOT NULL DEFAULT "pending",
        `error_code` varchar(10) NULL,
        `error_message` text NULL,
        `zra_invoice_number` varchar(100) NULL,
        `qr_code` text NULL,
        `fiscal_tax_id` varchar(100) NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `invoice_id` (`invoice_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
}

// Create ZRA configuration table
if (!$CI->db->table_exists(db_prefix() . 'zra_configuration')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'zra_configuration` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `setting_name` varchar(100) NOT NULL,
        `setting_value` text,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `setting_name` (`setting_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
}

// Insert default configuration options
add_option('zra_enabled', '0');
add_option('zra_environment', 'test'); // test or production
add_option('zra_api_url', 'https://localhost:8080/zrasandboxvsdc');
add_option('zra_company_tin', '');
add_option('zra_company_name', '');
add_option('zra_security_key', '');
add_option('zra_auto_submit_invoices', '0');
add_option('zra_tax_rate_standard', '16');
add_option('zra_tax_rate_zero', '0');
add_option('zra_default_currency', 'ZMW');
add_option('zra_timeout', '30');
add_option('zra_debug_mode', '0');

// Add notification when module is activated
if (is_admin()) {
    set_alert('success', 'ZRA Martin Invoicing module has been successfully installed. Please configure your ZRA settings in Settings > ZRA Configuration.');
}

?>