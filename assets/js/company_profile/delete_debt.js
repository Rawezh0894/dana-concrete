$(document).on('click', '.delete-debt', function() {
    const id = $(this).data('id');
    Swal.fire({
        title: 'دڵنیایت؟',
        text: 'ئەم دانەوەی قەرزە بسڕێتەوە؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، سڕەوە!',
        cancelButtonText: 'داخستن'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../process/company_profile/delete_debt.php', { id }, function(res) {
                if (res.success) {
                    Swal.fire('سڕایەوە!', 'دانەوەی قەرزەکە سڕایەوە.', 'success');
                    if (typeof loadDebts === 'function') loadDebts();
                    if (typeof loadPurchases === 'function') loadPurchases();
                    if (typeof loadCompanyStats === 'function') loadCompanyStats();
                } else {
                    Swal.fire('هەڵە!', res.msg || 'هەڵەیەک ڕویدا', 'error');
                }
            }, 'json');
        }
    });
});
