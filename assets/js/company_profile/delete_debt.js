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
                    
                    // Refresh all data without page reload
                    if (typeof loadDebts === 'function') loadDebts();
                    if (typeof loadPurchases === 'function') loadPurchases();
                    if (typeof loadCompanyInfoCards === 'function') loadCompanyInfoCards();
                    
                    // Also refresh the debt table if it's currently visible
                    if ($('#debt').hasClass('active')) {
                        loadDebts();
                    }
                } else {
                    Swal.fire('هەڵە!', res.msg || 'هەڵەیەک ڕویدا', 'error');
                }
            }, 'json');
        }
    });
});
