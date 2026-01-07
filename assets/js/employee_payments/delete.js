$(function() {
    $(document).on('click', '.delete-payment', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'دڵنیایت؟',
            text: 'دەتەوێت پارەدانەکە بسڕیتەوە؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'بەڵێ',
            cancelButtonText: 'داخستن'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../process/employee_payments/delete.php', {id: id}, function(response) {
                    if (response.success) {
                        if (window.loadPayments) window.loadPayments();
                        if (window.employeePaymentsSummary && window.employeePaymentsSummary.loadSummaryData) {
                            window.employeePaymentsSummary.loadSummaryData();
                        }
                        if (window.loadBalances) window.loadBalances();
                        swalAlert('سەرکەوتوو', 'پارەدان سڕایەوە!', 'success');
                    } else {
                        swalAlert('هەڵە', response.message || 'هەڵەیەک هەیە', 'error');
                    }
                }, 'json').fail(function(xhr) {
                    console.error('AJAX Error:', xhr.responseText);
                    swalAlert('هەڵە', 'هەڵەیەک هەیە لە پەیوەندیدا.', 'error');
                });
            }
        });
    });
    
    // Handle expense deletion
    $(document).on('click', '.delete-expense', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'دڵنیایت؟',
            text: 'دەتەوێت خەرجیەکە بسڕیتەوە؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'بەڵێ',
            cancelButtonText: 'داخستن'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../process/employee_payments/delete_expense.php', {id: id}, function(response) {
                    if (response.success) {
                        if (window.loadExpenses) window.loadExpenses();
                        if (window.loadBalances) window.loadBalances();
                        swalAlert('سەرکەوتوو', 'خەرجی سڕایەوە!', 'success');
                    } else {
                        swalAlert('هەڵە', response.message || 'هەڵەیەک هەیە', 'error');
                    }
                }, 'json').fail(function(xhr) {
                    console.error('AJAX Error:', xhr.responseText);
                    swalAlert('هەڵە', 'هەڵەیەک هەیە لە پەیوەندیدا.', 'error');
                });
            }
        });
    });
});
