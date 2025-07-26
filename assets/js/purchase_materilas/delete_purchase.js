// Delete functionality is already implemented in select_purchase.js
// This file is kept for consistency with the project structure

function deletePurchase(purchaseId) {
    Swal.fire({
        title: 'دڵنیای لە سڕینەوە؟',
        text: 'ئەم کردارە ناتوانرێت هەڵوەشێنرێتەوە!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، سڕەوە!',
        cancelButtonText: 'پاشگەزبوونەوە'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../process/purchase_materilas/delete_purchase.php',
                type: 'POST',
                data: { id: purchaseId },
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        
                        if (result.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'سەرکەوتوو',
                                text: result.message || 'کڕینەکە بە سەرکەوتووی سڕایەوە',
                                confirmButtonText: 'باشە'
                            }).then(() => {
                                loadPurchaseMaterialsTable();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'هەڵە',
                                text: result.error || 'هەڵەیەک ڕوویدا',
                                confirmButtonText: 'باشە'
                            });
                        }
                    } catch (e) {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: 'هەڵەیەک لە وەڵامەکەدا هەیە',
                            confirmButtonText: 'باشە'
                        });
                        console.error('Response:', response);
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'هەڵەی پەیوەندی بە سێرڤەرەوە: ' + error,
                        confirmButtonText: 'باشە'
                    });
                    console.error('AJAX Error:', xhr.responseText);
                }
            });
        }
    });
}
