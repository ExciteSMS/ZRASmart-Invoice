# ZRA Martin Invoicing Module for PerfexCRM

A comprehensive module for integrating PerfexCRM with the Zambia Revenue Authority (ZRA) Smart Invoice system for electronic invoicing compliance.

## Features

- **Seamless Integration**: Direct integration with ZRA Smart Invoice API
- **Automatic Submission**: Option to automatically submit invoices to ZRA
- **Manual Submission**: Bulk and individual manual submission of invoices
- **Invoice Fetching**: Retrieve invoice status and updates from ZRA
- **Real-time Status Tracking**: Monitor invoice submission status in real-time
- **Comprehensive Logging**: Detailed logs of all API interactions
- **Error Handling**: Robust error handling with detailed error messages
- **Tax Code Mapping**: Automatic mapping of tax rates to ZRA tax codes
- **QR Code Support**: Display ZRA QR codes on invoices
- **Multi-environment Support**: Test and production environment configurations
- **Bulk Operations**: Submit multiple invoices at once with progress tracking
- **Status Synchronization**: Fetch and update status for pending/failed submissions
- **Security**: Secure API authentication using company credentials

## Requirements

- PerfexCRM version 2.3.0 or higher
- PHP 7.4 or higher
- cURL extension enabled
- Valid ZRA VSDC registration and credentials

## Installation

1. Upload the `zra_martin_invoicing` folder to your PerfexCRM `modules` directory
2. Navigate to **Setup > Modules** in your PerfexCRM admin panel
3. Find "ZRA Martin Invoicing" and click **Activate**
4. Configure your ZRA settings in **ZRA Invoicing > Settings**

## Configuration

### Step 1: ZRA VSDC Setup

Before using this module, you need to:

1. Register on the ZRA Smart Invoice Taxpayer Portal
2. Apply for VSDC service
3. Complete technical and administrative verification
4. Download and deploy the VSDC on your local server
5. Complete device initialization to obtain your security key

### Step 2: Module Configuration

1. **General Settings**:
   - Enable ZRA Integration: Turn on/off the integration
   - Environment: Choose between Test and Production
   - Auto Submit: Automatically submit invoices when created
   - Debug Mode: Enable for detailed logging (disable in production)

2. **API Settings**:
   - API URL: Your VSDC API endpoint (e.g., `https://localhost:8080/zrasandboxvsdc`)
   - Company TIN: Your 10-digit Tax Identification Number
   - Company Name: Your registered company name
   - Security Key: Security key obtained during VSDC initialization
   - Timeout: API request timeout in seconds

3. **Tax Settings**:
   - Standard Tax Rate: Usually 16% for Zambia
   - Zero Tax Rate: 0% for exempt items
   - Default Currency: ZMW (Zambian Kwacha)

## Usage

### Automatic Invoice Submission

When "Auto Submit" is enabled, invoices are automatically submitted to ZRA when:
- A new invoice is created
- An existing invoice is updated

### Manual Invoice Submission

1. Navigate to **Sales > Invoices**
2. Open the invoice you want to submit
3. Click the ZRA submit button (if not already submitted)
4. Monitor the submission status

### Manual Invoice Submission

1. Navigate to **ZRA Invoicing > Manual Submit**
2. Review the list of unsubmitted invoices
3. Select invoices you want to submit (individual or bulk)
4. Click **Submit Selected** or use individual submit buttons
5. Monitor progress in the submission modal
6. Review results and retry failed submissions if needed

### Fetching Invoice Status

1. Navigate to **ZRA Invoicing > Fetch Invoices**
2. **Single Invoice Fetch**:
   - Enter the invoice reference number
   - Click **Fetch Invoice** to get status
3. **Bulk Fetch All Pending**:
   - Click **Fetch All Pending** to update all pending/failed submissions
   - System automatically updates local status based on ZRA response

### Bulk Operations

- **Bulk Submission**: Select multiple invoices and submit them with progress tracking
- **Smart Delays**: System adds delays between submissions to avoid API overload
- **Progress Monitoring**: Real-time progress bars and status updates
- **Error Recovery**: Failed submissions can be retried individually

### Viewing ZRA Status

- **Invoice List**: ZRA status column shows submission status
- **Invoice Details**: ZRA status panel shows detailed information
- **ZRA Dashboard**: Overview of all submissions and statistics
- **ZRA Logs**: Detailed logs of all API interactions

### Error Handling

If submission fails:
1. Check the error message in ZRA Logs
2. Verify your API credentials and settings
3. Ensure VSDC is running and accessible
4. Retry submission after fixing issues

## ZRA Tax Codes

The module automatically maps tax rates to ZRA tax codes:

- **A**: Exempted (0%)
- **B**: Minimum Taxable Value (16%)
- **C**: Exports (0%)
- **D**: Zero-rating LPO
- **F**: Standard Rated (16%)
- **G**: Economy Rate (0%)
- **H**: Exempt (0%)

## API Endpoints Used

- `/trnsSales/saveSales` - Submit sales invoices
- `/trnsSales/saveRefund` - Submit refunds
- `/trnsSales/getInvoiceStatus` - Fetch invoice status and updates
- `/api/health` - Test API connection

## Database Tables

The module creates two tables:

1. **zra_invoicing_logs**: Stores all API interaction logs
2. **zra_configuration**: Stores module configuration (optional)

## Troubleshooting

### Common Issues

1. **Connection Failed**:
   - Verify VSDC is running
   - Check API URL format
   - Ensure firewall allows connections

2. **Authentication Failed**:
   - Verify Company TIN (must be exactly 10 digits)
   - Check Company Name matches registration
   - Ensure Security Key is correct

3. **Invoice Submission Failed**:
   - Check invoice data completeness
   - Verify client TIN format (if provided)
   - Ensure tax rates are valid

### Debug Mode

Enable debug mode in settings for detailed logging:
- All API requests and responses are logged
- Error details are captured
- Performance metrics are recorded

**Important**: Disable debug mode in production for better performance

## Security Considerations

- Store security keys securely
- Use HTTPS for production API endpoints
- Regularly update VSDC software
- Monitor logs for suspicious activity
- Restrict module permissions to authorized users

## Support

For technical support:
1. Check ZRA Smart Invoice documentation
2. Review module logs for error details
3. Contact ZRA technical support at smartinvoice@zra.org.zm
4. Consult PerfexCRM documentation for module-related issues

## License

This module is provided as-is for integration with ZRA Smart Invoice system. Ensure compliance with ZRA requirements and local tax regulations.

## Version History

### Version 1.0.0
- Initial release
- Basic invoice submission functionality
- Settings management
- Logging and error handling
- Dashboard and reporting

---

**Developed by**: MiniMax Agent  
**Version**: 1.0.0  
**Compatibility**: PerfexCRM 2.3.0+  
**Last Updated**: 2025-09-28