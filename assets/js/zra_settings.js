function zraSetInitializeStatus(type, text) {
    var statusContainer = document.getElementById('zra-initialize-status-container');
    var status = document.getElementById('zra-initialize-status');

    if (!statusContainer || !status) {
        return;
    }

    statusContainer.style.display = 'block';
    status.classList.remove('text-success', 'text-danger', 'text-muted');

    if (type === 'loading') {
        status.classList.add('text-muted');
        status.innerHTML = '<i class="fa fa-spinner fa-spin"></i> ' + text;
    } else if (type === 'success') {
        status.classList.add('text-success');
        status.innerHTML = '<i class="fa fa-check"></i> ' + text;
    } else if (type === 'error') {
        status.classList.add('text-danger');
        status.innerHTML = '<i class="fa fa-times"></i> ' + text;
    }
}

function zraSetButtonLoading(btn, text) {
    if (!btn) {
        return;
    }

    btn.dataset.originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> ' + text;
    btn.disabled = true;
}

function zraResetButton(btn) {
    if (!btn) {
        return;
    }

    if (typeof btn.dataset.originalHtml !== 'undefined') {
        btn.innerHTML = btn.dataset.originalHtml;
        delete btn.dataset.originalHtml;
    }
    btn.disabled = false;
}

function zraParseResponseText(responseText) {
    try {
        return JSON.parse(responseText);
    } catch (e) {
        return { success: false, message: 'Invalid JSON response from server', raw: responseText };
    }
}

function zraHandleAjaxError(btn, title, errorText, responseText) {
    zraSetInitializeStatus('error', title);
    if (typeof alert_float !== 'undefined') {
        alert_float('danger', title + ': ' + errorText + '\n' + responseText);
    }
    zraResetButton(btn);
}

var zraSettingsConfig = window.zraSettingsConfig || {
    testUrl: '',
    initializeUrl: '',
    connectionSuccessfulText: 'Connection successful',
    connectionFailedText: 'Connection failed',
    initializeSuccessfulText: 'Device initialization successful',
    initializeFailedText: 'Device initialization failed'
};

function zraExecutePost(url, btn, loadingText, successText, errorText) {
    if (typeof console !== 'undefined') {
        console.log('zraExecutePost', url);
    }

    zraSetInitializeStatus('loading', loadingText);
    zraSetButtonLoading(btn, loadingText);

    var body = new URLSearchParams();
    if (zraSettingsConfig.csrfTokenName && zraSettingsConfig.csrfHash) {
        body.append(zraSettingsConfig.csrfTokenName, zraSettingsConfig.csrfHash);
    }

    fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: body
    })
    .then(function(response) {
        return response.text().then(function(text) {
            return { status: response.status, statusText: response.statusText, text: text };
        });
    })
    .then(function(result) {
        if (typeof console !== 'undefined') {
            console.log('zra response', result.status, result.statusText, result.text);
        }

        if (result.status !== 200) {
            var data = zraParseResponseText(result.text);
            data.message = data.message || 'HTTP ' + result.status + ' ' + result.statusText;
            data.raw = result.text;
            return Promise.reject(data);
        }

        var data = zraParseResponseText(result.text);
        if (data.csrfTokenName && data.csrfHash) {
            zraSettingsConfig.csrfTokenName = data.csrfTokenName;
            zraSettingsConfig.csrfHash = data.csrfHash;
        }
        if (data.success) {
            zraSetInitializeStatus('success', successText);
            if (typeof alert_float !== 'undefined') {
                alert_float('success', successText);
            }
            return data;
        }

        zraSetInitializeStatus('error', errorText + ': ' + (data.message || 'Unknown error'));
        if (typeof alert_float !== 'undefined') {
            alert_float('danger', errorText + ': ' + (data.message || 'Unknown error'));
        }
        return Promise.reject(data);
    })
    .catch(function(error) {
        if (typeof error === 'object' && error.raw) {
            zraHandleAjaxError(btn, errorText, error.message || 'Request failed', error.raw);
        } else {
            zraHandleAjaxError(btn, errorText, error.message || 'Request failed', error);
        }
    })
    .finally(function() {
        zraResetButton(btn);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    var testButton = document.getElementById('test-api-connection');
    var initializeButton = document.getElementById('initialize-device');

    if (typeof console !== 'undefined') {
        console.log('ZRA settings JS loaded');
    }

    if (testButton) {
        testButton.addEventListener('click', function() {
            zraExecutePost(zraSettingsConfig.testUrl, testButton, 'Testing connection...', zraSettingsConfig.connectionSuccessfulText, zraSettingsConfig.connectionFailedText);
        });
    }

    if (initializeButton) {
        initializeButton.addEventListener('click', function() {
            zraExecutePost(zraSettingsConfig.initializeUrl, initializeButton, 'Initializing device...', zraSettingsConfig.initializeSuccessfulText, zraSettingsConfig.initializeFailedText);
        });
    }
});
