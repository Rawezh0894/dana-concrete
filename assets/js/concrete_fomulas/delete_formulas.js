// Handle delete button click for formulas
document.addEventListener('click', function(e) {
    if (e.target.closest('.delete-formula-btn')) {
        const btn = e.target.closest('.delete-formula-btn');
        const id = btn.getAttribute('data-id');
        Swal.fire({
            title: 'دڵنیایت؟',
            text: 'ئایا دەتەوێت ئەم فۆرمولا بسڕیتەوە؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ',
            cancelButtonText: 'نەخێر'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`../process/concrete_fomulas/delete_formulas.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${encodeURIComponent(id)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('سڕایەوە!', data.message || 'فۆرمولا سڕایەوە', 'success');
                        if (typeof loadFormulas === 'function') loadFormulas();
                    } else {
                        Swal.fire('هەڵە!', data.message || 'هەڵەیەک ڕووی دا', 'error');
                    }
                })
                .catch(() => {
                    Swal.fire('هەڵە!', 'هەڵەیەک ڕووی دا', 'error');
                });
            }
        });
    }
});
