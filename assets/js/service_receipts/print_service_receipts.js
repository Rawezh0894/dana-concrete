// Handle Printing of Service Receipts
$(document).on('click', '.print-receipt', function() {
    const id = $(this).data('id');
    
    if (!id) {
        Swal.fire('هەڵە!', 'ناسنامەی پسوڵە نەدۆزرایەوە', 'error');
        return;
    }

    // Confirmation dialog before printing (optional, but consistent with project style)
    Swal.fire({
        title: 'چاپکردنی پسوڵە',
        text: "دەتەوێت ئەم پسوڵەیە چاپ بکەیت؟",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'بەڵێ، چاپکردن',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            // Open print page in a new window/tab
            const printUrl = `../pages/service_receipt_print.php?id=${id}`;
            const printWindow = window.open(printUrl, '_blank');
            
            if (!printWindow) {
                Swal.fire('ئاگاداری', 'تکایە ڕێگە بدە پەنجەرەی نوێ (Pop-up) بکرێتەوە', 'warning');
            }
        }
    });
});
