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
                title: 'سەرکەوتوو!',
                text: 'دەتەوێت پسوڵەکە چاپ بکەیت؟',
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: 'بەڵێ، چاپ',
                cancelButtonText: 'نەخێر'
            }).then((result) => {
                if (result.isConfirmed && data.id) {
                    window.location.href = '../pages/central_receipts.php?id=' + data.id;
                }
                // else: stay on page
            });
        } else {
            Swal.fire('هەڵە!', data.message || 'هەڵەیەک ڕویدا', 'error');
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
