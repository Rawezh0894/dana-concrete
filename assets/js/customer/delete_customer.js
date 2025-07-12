document.addEventListener('click', function(e) {
    if (e.target.closest('.delete-customer-btn')) {
        const btn = e.target.closest('.delete-customer-btn');
        const id = btn.getAttribute('data-id');
        Swal.fire({
            title: 'دڵنیایت؟',
            text: 'ئایا دەتەوێت ئەم کڕیارە بسڕیتەوە؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ',
            cancelButtonText: 'نەخێر'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../process/customer/delete_customer.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${encodeURIComponent(id)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('سڕایەوە!', data.message || 'کڕیار سڕایەوە', 'success');
                        if (typeof loadCustomers === 'function') loadCustomers();
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
