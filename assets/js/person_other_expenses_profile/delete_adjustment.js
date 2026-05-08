function deleteAdjustment(id) {
    Swal.fire({
        title: 'ئایا دڵنیایت؟',
        text: "ناتوانیت ئەم کارە بگەڕێنیتەوە!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، بیسڕەوە!',
        cancelButtonText: 'پاشگەزبوونەوە'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../process/person_other_expenses_profile/delete_adjustment.php', { id: id }, function (res) {
                if (res.success) {
                    Swal.fire('سڕایەوە!', res.msg, 'success');
                    if (typeof loadAdjustmentTable === 'function') loadAdjustmentTable();
                    if (typeof loadSummaryCards === 'function') loadSummaryCards();
                } else {
                    Swal.fire('هەڵە!', res.msg || 'هەڵەیەک ڕوویدا.', 'error');
                }
            }, 'json');
        }
    });
}

window.deleteAdjustment = deleteAdjustment;
