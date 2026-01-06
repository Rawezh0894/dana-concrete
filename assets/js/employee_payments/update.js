(function (global) {
    const $ = global.jQuery || global.$;
    const swalAlert = global.swalAlert;
    const console = global.console;
    const doc = global.document;

    if (!$ || !doc) return;

    $(function () {
        $(doc).on('click', '.edit-payment', function () {
            const id = $(this).data('id');
            const employeeId = $(this).data('employee-id');
            const row = $(this).closest('tr');
            $('#edit_payment_id').val(id);
            $('#edit_employee_id').val(employeeId).trigger('change');
            $('#edit_type').val(row.find('td').eq(2).text().trim());
            $('#edit_operation').val(row.find('td').eq(3).text().trim());
            // amount cell is signed +/-
            $('#edit_amount').val(parseFloat(row.find('td').eq(4).text().replace(/[^\d.]/g, '') || 0).toFixed(2));
            $('#edit_pay_month').val(row.find('td').eq(5).text().trim());
            const dt = row.find('td').eq(6).text().trim();
            // best-effort: convert "YYYY-MM-DD HH:MM:SS" to "YYYY-MM-DDTHH:MM"
            if (dt.includes(' ')) {
                const parts = dt.split(' ');
                $('#edit_transaction_date').val(parts[0] + 'T' + (parts[1] ? parts[1].slice(0, 5) : '00:00'));
            } else {
                $('#edit_transaction_date').val(dt);
            }
            $('#edit_description').val(row.find('td').eq(7).text().trim());
            $('#editPaymentModal').modal('show');
        });
        $('#editPaymentForm').on('submit', function (e) {
            e.preventDefault();
            var formData = $(this).serialize();
            $.post('../process/employee_payments/update.php', formData, function (response) {
                if (response.success) {
                    $('#editPaymentModal').modal('hide');
                    if (typeof global.loadPayments === 'function') global.loadPayments();
                    if (global.employeePaymentsSummary && typeof global.employeePaymentsSummary.loadSummaryData === 'function') {
                        global.employeePaymentsSummary.loadSummaryData();
                    }
                    if (typeof swalAlert === 'function') swalAlert('سەرکەوتوو', 'نوێکرایەوە!', 'success');
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
