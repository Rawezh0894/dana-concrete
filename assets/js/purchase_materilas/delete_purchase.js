// Delete Purchase Functionality
function deletePurchase(purchaseId) {
    Swal.fire({
        title: 'دڵنیای لە سڕینەوە؟',
        text: 'ئەم کردارە ناتوانرێت هەڵوەشێنرێتەوە!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، سڕەوە',
        cancelButtonText: 'پاشگەزبوونەوە'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../process/purchase_materilas/delete_purchase.php',
                type: 'POST',
                data: { id: purchaseId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو',
                            text: response.message || 'کڕینەکە بە سەرکەوتووی سڕایەوە',
                            confirmButtonText: 'باشە'
                        }).then(() => {
                            // Refresh the purchase list without page reload
                            if (typeof loadPurchaseMaterialsTable === 'function') {
                                loadPurchaseMaterialsTable();
                            }
                            
                            // Refresh summary cards without page reload
                            if (typeof loadSummaryCards === 'function') {
                                loadSummaryCards();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: response.error || 'هەڵەیەک ڕوویدا',
                            confirmButtonText: 'باشە'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵەی AJAX',
                        text: 'هەڵە لە پەیوەندی بە سێرڤەر: ' + error + ' (Status: ' + xhr.status + ')',
                        confirmButtonText: 'باشە'
                    });
                }
            });
        }
    });
}
