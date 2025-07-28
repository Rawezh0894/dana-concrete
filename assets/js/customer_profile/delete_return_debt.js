document.addEventListener('click', async function(e) {
    if (e.target.classList.contains('delete-return-debt') || e.target.closest('.delete-return-debt')) {
        e.preventDefault();
        e.stopPropagation();
        
        const button = e.target.classList.contains('delete-return-debt') ? e.target : e.target.closest('.delete-return-debt');
        const id = button.getAttribute('data-id');
        
        if (!id) {
            console.error('No debt ID found for deletion');
            Swal.fire('هەڵە', 'ناسنامەی قەرز نەدۆزرایەوە!', 'error');
            return;
        }
        
        const result = await Swal.fire({
            title: 'دڵنیایت؟',
            text: 'دەتەوێت ئەم دانەوەی قەرزە بسڕیتەوە؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ، بسڕەوە',
            cancelButtonText: 'داخستن',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        });
        
        if (result.isConfirmed) {
            // Show loading state
            button.disabled = true;
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
            
            try {
                const formData = new FormData();
                formData.append('id', id);
                
                const res = await fetch('../process/customer_profile/delete_return_debt.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                
                const data = await res.json();
                if (data.success) {
                    Swal.fire('سەرکەوتوو', data.msg, 'success');
                    
                    // Automatically refresh all customer data
                    if (typeof refreshCustomerData === 'function') {
                        refreshCustomerData();
                    } else {
                        // Fallback to individual refresh functions
                        if (typeof loadCustomerReturnDebts === 'function' && typeof CUSTOMER_ID !== 'undefined') {
                            loadCustomerReturnDebts(CUSTOMER_ID);
                        }
                        if (typeof loadCustomerSales === 'function' && typeof CUSTOMER_ID !== 'undefined') {
                            loadCustomerSales(CUSTOMER_ID);
                        }
                        if (typeof loadCustomerSummaryCards === 'function') {
                            loadCustomerSummaryCards();
                        }
                    }
                } else {
                    Swal.fire('هەڵە', data.msg || 'هەڵەیەک ڕووی دا', 'error');
                }
            } catch (error) {
                console.error('Error deleting debt:', error);
                Swal.fire('هەڵە', 'هەڵەیەک ڕووی دا لە پەیوەندی بە سێرڤەرەوە', 'error');
            } finally {
                // Restore button state
                button.disabled = false;
                button.innerHTML = originalHTML;
            }
        }
    }
});
