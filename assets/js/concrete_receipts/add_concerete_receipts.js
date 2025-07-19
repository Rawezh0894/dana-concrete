let submitting = false;
$(document).ready(function() {
    $('#addConcreteReceiptForm').on('submit', async function(e) {
        if (submitting) return false;
        submitting = true;
        e.preventDefault();
        const formData = new FormData(this);
        const res = await fetch('../process/concrete_receipts/add_concerete_receipts.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'پسوڵە زیادکرا',
                text: 'ئایا دەتەوێت پسوڵە چاپ بکەیت؟',
                showCancelButton: true,
                confirmButtonText: 'بەڵێ',
                cancelButtonText: 'نەخێر',
            }).then((result) => {
                // Always reset form and close modal
                $('#addConcreteReceiptForm')[0].reset();
                $('#addConcreteReceiptModal').modal('hide');
                if (window.reloadConcreteReceipts) window.reloadConcreteReceipts();
                if (window.reloadConcreteReceiptsSummary) window.reloadConcreteReceiptsSummary();
                if (result.isConfirmed && data.id) {
                    window.open('../receipt.html?id=' + data.id, '_blank');
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: data.message || 'هەڵەیەک ڕویدا لە زیادکردنی پسوڵە!'
            });
        }
        submitting = false;
    });

    $('#addConcreteReceiptModal').on('show.bs.modal', function() {
        $.get('../process/concrete_receipts/get_next_receipt_number.php', function(res) {
            if (res && res.success && res.next) {
                $('#receipt_number').val(res.next);
            } else {
                $('#receipt_number').val('A-0001');
            }
        }, 'json').fail(function() {
            $('#receipt_number').val('A-0001');
        });
    });
});
