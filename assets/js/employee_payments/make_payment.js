(function (global) {
    const $ = global.jQuery || global.$;
    const swalAlert = global.swalAlert;
    const console = global.console;
    const doc = global.document;

    if (!$ || !doc) return;

    $(function () {
        $('#addDebitForm').on('submit', function (e) {
            e.preventDefault();
            const formData = $(this).serialize();
            $.post('../process/employee_payments/make_payment.php', formData, function (response) {
                if (response.success) {
                    if (typeof swalAlert === 'function') swalAlert('سەرکەوتوو', response.message || 'تۆمارکرا', 'success');
                    $('#addDebitForm')[0].reset();
                    $('#addDebitModal').modal('hide');
                    if (typeof global.loadPayments === 'function') global.loadPayments();
                    if (global.employeePaymentsSummary && typeof global.employeePaymentsSummary.loadSummaryData === 'function') {
                        global.employeePaymentsSummary.loadSummaryData();
                    }
                } else {
                    if (typeof swalAlert === 'function') swalAlert('هەڵە', response.message || 'هەڵەیەک هەیە', 'error');
                }
            }, 'json').fail(function (xhr) {
                if (console && console.error) console.error('AJAX Error:', xhr.responseText);
                if (typeof swalAlert === 'function') swalAlert('هەڵە', 'هەڵەیەک هەیە لە پەیوەندیدا.', 'error');
            });
        });
    });
})(globalThis);
