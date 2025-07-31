// Multiple submission prevention flag
let submitting = false;
$(document).ready(function() {
    // Restore form data from localStorage
    const storageKey = 'addConcreteReceiptFormData';
    const $form = $('#addConcreteReceiptForm');
    const saved = localStorage.getItem(storageKey);
    if (saved) {
        try {
            const data = JSON.parse(saved);
            Object.entries(data).forEach(([k, v]) => {
                if (k === 'receipt_number') return; // never restore receipt_number
                const $el = $form.find(`[name="${k}"]`);
                if ($el.is('select')) {
                    $el.val(v).trigger('change');
                } else {
                    $el.val(v);
                }
            });
        } catch(e) {}
    }
    // Save form data on change
    $form.on('input change', 'input, select, textarea', function() {
        const data = {};
        $form.serializeArray().forEach(({name, value}) => {
            if (name !== 'receipt_number') data[name] = value;
        });
        localStorage.setItem(storageKey, JSON.stringify(data));
    });

    $('#addConcreteReceiptForm').on('submit', async function(e) {
        e.preventDefault();
        
        // Prevent multiple submissions
        if (submitting) {
            showAlert('warning', 'تکایە چاوەڕوان بە...');
            return false;
        }
        
        // Set submitting flag and disable submit button
        submitting = true;
        const submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true);
        submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');
        
        try {
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
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1000,
                    timerProgressBar: true,
                    didClose: () => {
                        if (data.id) {
                            // Save all fields except meter_amount, mixer_car_id, mixer_driver_id
                            const allData = {};
                            $form.serializeArray().forEach(({name, value}) => {
                                if (!["meter_amount","mixer_car_id","mixer_driver_id","receipt_number"].includes(name)) {
                                    allData[name] = value;
                                }
                            });
                            localStorage.setItem(storageKey, JSON.stringify(allData));
                            
                            // Check if auto-print is requested (from notes page)
                            if (window.autoPrintAfterCreation) {
                                window.open('../pages/central_receipts.php?id=' + data.id + '&auto_print=1', '_self');
                                window.autoPrintAfterCreation = false; // Reset flag
                            } else {
                                // Normal behavior - just close modal and reload
                                $('#addConcreteReceiptForm')[0].reset();
                                $('#addConcreteReceiptModal').modal('hide');
                                if (window.reloadConcreteReceipts) window.reloadConcreteReceipts();
                                if (window.reloadConcreteReceiptsSummary) window.reloadConcreteReceiptsSummary();
                            }
                        }
                        // Do NOT clear localStorage here, it is handled above
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: data.message || 'هەڵەیەک ڕویدا لە زیادکردنی پسوڵە!'
                });
            }
        } catch (error) {
            console.error('Error adding concrete receipt:', error);
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'هەڵەیەک لە پەیوەندی بە سێرڤەرەوە هەیە'
            });
        } finally {
            // Reset submitting flag and restore submit button
            submitting = false;
            submitBtn.prop('disabled', false);
            submitBtn.html(originalBtnText);
        }
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
