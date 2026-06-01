(function() {
    "use strict";

    function init($) {
        function updateBulkActions() {
            var selectedCount = $('.invoice-checkbox:checked').length;
            $('#bulk-submit-btn').prop('disabled', selectedCount === 0);
            $('.selected-count').text('(' + selectedCount + ' ' + (window.zraManualSubmitConfig && window.zraManualSubmitConfig.selectedText ? window.zraManualSubmitConfig.selectedText : 'selected') + ')');
        }

        function updateMasterCheckbox() {
            var totalCheckboxes = $('.invoice-checkbox').length;
            var checkedCheckboxes = $('.invoice-checkbox:checked').length;

            if (checkedCheckboxes === 0) {
                $('#master-checkbox').prop('indeterminate', false);
                $('#master-checkbox').prop('checked', false);
            } else if (checkedCheckboxes === totalCheckboxes) {
                $('#master-checkbox').prop('indeterminate', false);
                $('#master-checkbox').prop('checked', true);
            } else {
                $('#master-checkbox').prop('indeterminate', true);
            }
        }

        function handleSingleSubmit(event) {
            var btn = $(event.currentTarget);
            var invoiceId = btn.data('invoice-id');
            var originalHtml = btn.html();

            if (window.zraManualSubmitConfig && window.zraManualSubmitConfig.confirmSingleSubmitText) {
                if (!window.confirm(window.zraManualSubmitConfig.confirmSingleSubmitText)) {
                    return;
                }
            } else {
                if (!window.confirm('Are you sure you want to submit invoice #' + invoiceId + '?')) {
                    return;
                }
            }

            btn.html('<i class="fa fa-spinner fa-spin"></i> ' + (window.zraManualSubmitConfig && window.zraManualSubmitConfig.submittingText ? window.zraManualSubmitConfig.submittingText : 'Submitting') + '...');
            btn.prop('disabled', true);

            $.ajax({
                url: (window.zraManualSubmitConfig && window.zraManualSubmitConfig.submitInvoiceUrl ? window.zraManualSubmitConfig.submitInvoiceUrl : '') + invoiceId,
                type: 'POST',
                dataType: 'json',
                data: (window.zraManualSubmitConfig && window.zraManualSubmitConfig.csrfData ? window.zraManualSubmitConfig.csrfData : {}),
                success: function(response) {
                    if (response && response.success) {
                        alert_float('success', window.zraManualSubmitConfig.successSubmitText);
                        btn.closest('tr').fadeOut(function() {
                            $(this).remove();
                            updateBulkActions();
                            updateMasterCheckbox();
                        });
                    } else {
                        var message = response && response.message ? response.message : (window.zraManualSubmitConfig && window.zraManualSubmitConfig.failedSubmitText ? window.zraManualSubmitConfig.failedSubmitText : 'Submission failed');
                        alert_float('danger', (window.zraManualSubmitConfig && window.zraManualSubmitConfig.failedSubmitText ? window.zraManualSubmitConfig.failedSubmitText : 'Submission failed') + ': ' + message);
                    }
                },
                error: function(xhr) {
                    var errMsg = (window.zraManualSubmitConfig && window.zraManualSubmitConfig.failedSubmitText ? window.zraManualSubmitConfig.failedSubmitText : 'Submission failed') + ': ' + xhr.statusText;
                    console.error('Single invoice submit error:', xhr);
                    alert_float('danger', errMsg);
                    try {
                        $('#progress-details').append('<div class="text-danger"><i class="fa fa-exclamation-triangle"></i> Invoice #' + invoiceId + ' - ' + errMsg + '</div>');
                    } catch (e) {
                        console.error('Error appending to progress details:', e);
                    }
                },
                complete: function() {
                    btn.html(originalHtml);
                    btn.prop('disabled', false);
                }
            });
        }

        function handleBulkSubmit(event) {
            event.preventDefault();

            var invoiceIds = $('.invoice-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (invoiceIds.length === 0) {
                alert_float('warning', (window.zraManualSubmitConfig && window.zraManualSubmitConfig.noInvoicesSelectedText) ? window.zraManualSubmitConfig.noInvoicesSelectedText : 'No invoices selected');
                return;
            }

            if (window.zraManualSubmitConfig && window.zraManualSubmitConfig.confirmBulkSubmitText) {
                if (!window.confirm(window.zraManualSubmitConfig.confirmBulkSubmitText)) {
                    return;
                }
            } else {
                if (!window.confirm('Are you sure you want to submit ' + invoiceIds.length + ' invoices?')) {
                    return;
                }
            }

            var requestData = {
                invoice_ids: invoiceIds
            };

            if (window.zraManualSubmitConfig && window.zraManualSubmitConfig.csrfData) {
                $.extend(requestData, window.zraManualSubmitConfig.csrfData);
            }

            $('#progress-modal').modal('show');
            $('#progress-details').empty();
            $('.progress-bar').css('width', '0%');
            $('#progress-text').text(window.zraManualSubmitConfig.preparingSubmissionText);

            $.ajax({
                url: window.zraManualSubmitConfig.bulkSubmitUrl,
                type: 'POST',
                dataType: 'json',
                data: requestData,
                success: function(response) {
                    var results = response && response.results ? response.results : [];
                    var successCount = 0;
                    var failedCount = 0;

                    results.forEach(function(result, index) {
                        var progress = Math.round(((index + 1) / results.length) * 100);
                        $('.progress-bar').css('width', progress + '%');
                        $('#progress-text').text(window.zraManualSubmitConfig.processingInvoiceText + ' ' + (index + 1) + ' ' + window.zraManualSubmitConfig.ofText + ' ' + results.length);

                        if (result.success) {
                            successCount++;
                            $('#progress-details').append('<div class="text-success"><i class="fa fa-check"></i> Invoice #' + result.invoice_id + ' - ' + window.zraManualSubmitConfig.successSubmitText + '</div>');
                        } else {
                            failedCount++;
                            var message = result.message ? result.message : window.zraManualSubmitConfig.failedSubmitText;
                            $('#progress-details').append('<div class="text-danger"><i class="fa fa-times"></i> Invoice #' + result.invoice_id + ' - ' + message + '</div>');
                        }
                    });

                    if (successCount > 0) {
                        alert_float('success', successCount + ' ' + window.zraManualSubmitConfig.successBulkText);
                    }
                    if (failedCount > 0) {
                        alert_float('warning', failedCount + ' ' + window.zraManualSubmitConfig.failedBulkText);
                    }

                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                },
                error: function(xhr) {
                    $('#progress-modal').modal('hide');
                    var errMsg = window.zraManualSubmitConfig.failedSubmitText + ': ' + xhr.statusText;
                    console.error('Bulk submit error:', xhr);
                    alert_float('danger', errMsg);
                    try {
                        $('#progress-details').append('<div class="text-danger"><i class="fa fa-exclamation-triangle"></i> Bulk submit error - ' + errMsg + '</div>');
                    } catch (e) {
                        console.error('Error appending bulk error to progress details:', e);
                    }
                }
            });
        }

        $(document).ready(function() {
            $('#master-checkbox').on('change', function() {
                var isChecked = $(this).is(':checked');
                $('.invoice-checkbox').prop('checked', isChecked);
                updateBulkActions();
            });

            $(document).on('change', '.invoice-checkbox', function() {
                updateBulkActions();
                updateMasterCheckbox();
            });

            $('#select-all').on('click', function() {
                $('.invoice-checkbox').prop('checked', true);
                $('#master-checkbox').prop('checked', true);
                updateBulkActions();
            });

            $('#deselect-all').on('click', function() {
                $('.invoice-checkbox').prop('checked', false);
                $('#master-checkbox').prop('checked', false);
                updateBulkActions();
            });

            $(document).on('click', '.submit-single', handleSingleSubmit);

            $('#manual-submit-form').on('submit', handleBulkSubmit);

            $('#refresh-invoices').on('click', function() {
                location.reload();
            });

            updateBulkActions();
            updateMasterCheckbox();
        });
    }

    function run() {
        if (typeof jQuery !== 'undefined') {
            init(jQuery);
            return;
        }

        // Load jQuery from CDN as fallback
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
        script.integrity = 'sha256-/xUj+3OJ+YVqD/6nQm8q3m1Q5Y3l9p2Z5x5o5Z6VYk8=';
        script.crossOrigin = 'anonymous';
        script.onload = function() {
            if (typeof jQuery !== 'undefined') {
                init(window.jQuery);
            } else {
                console.error('jQuery failed to load. manual_submit.js cannot initialize.');
            }
        };
        script.onerror = function() {
            console.error('Failed to load jQuery from CDN. manual_submit.js cannot initialize.');
        };
        document.head.appendChild(script);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
