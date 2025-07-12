document.addEventListener('click', async function(e) {
    if (e.target.classList.contains('delete-return-debt')) {
        const id = e.target.getAttribute('data-id');
        if (!id) return;
        Swal.fire({
            title: 'دڵنیایت؟',
            text: 'دەتەوێت ئەم دانەوەی قەرزە بسڕیتەوە؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ، بسڕەوە',
            cancelButtonText: 'داخستن'
        }).then(async (result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('id', id);
                const res = await fetch('../process/customer_profile/delete_return_debt.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire('سەرکەوتوو', data.msg, 'success');
                    if (typeof loadCustomerReturnDebts === 'function' && typeof CUSTOMER_ID !== 'undefined') {
                        loadCustomerReturnDebts(CUSTOMER_ID);
                    }
                    // نوێکردنەوەی قەرزی ماوە
                    if (typeof fetchCustomerDebt === 'function' && typeof CUSTOMER_ID !== 'undefined') {
                        fetchCustomerDebt(CUSTOMER_ID);
                    }
                } else {
                    Swal.fire('هەڵە', data.msg || 'هەڵەیەک ڕووی دا', 'error');
                }
            }
        });
    }
});
