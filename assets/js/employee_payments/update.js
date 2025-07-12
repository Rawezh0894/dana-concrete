$(function() {
    $(document).on('click', '.edit-payment', function() {
        const id = $(this).data('id');
        const employeeId = $(this).data('employee-id');
        const row = $(this).closest('tr');
        $('#edit_payment_id').val(id);
        $('#edit_employee_id').val(employeeId).trigger('change');
        $('#edit_salary').val(parseFloat(row.find('td').eq(2).text().replace(/[^\d.]/g, '') || 0).toFixed(0));
        $('#edit_karwanhisabi').val(parseFloat(row.find('td').eq(3).text().replace(/[^\d.]/g, '') || 0).toFixed(0));
        $('#edit_bonus').val(parseFloat(row.find('td').eq(4).text().replace(/[^\d.]/g, '') || 0).toFixed(0));
        $('#total_edit').val(parseFloat(row.find('td').eq(5).text().replace(/[^\d.]/g, '') || 0).toLocaleString('en-US') + ' د.ع');
        $('#edit_pay_month').val(row.find('td').eq(6).text());
        $('#editPaymentModal').modal('show');
    });
    $('#editPaymentForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.post('../process/employee_payments/update.php', formData, function(response) {
            if (response.success) {
                $('#editPaymentModal').modal('hide');
                if (window.loadPayments) window.loadPayments();
                swalAlert('سەرکەوتوو', 'پارەدان نوێکرایەوە!', 'success');
            } else {
                swalAlert('هەڵە', response.message || 'هەڵەیەک هەیە', 'error');
            }
        }, 'json').fail(function(xhr) {
            console.error('AJAX Error:', xhr.responseText);
            swalAlert('هەڵە', 'هەڵەیەک هەیە لە پەیوەندیدا.', 'error');
        });
    });
});
