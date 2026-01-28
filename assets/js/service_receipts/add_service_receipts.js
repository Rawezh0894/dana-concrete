// Multiple submission prevention flag
let submitting = false;
$(document).ready(function () {
    // Restore form data from localStorage
    const storageKey = 'addServiceReceiptFormData';
    const $form = $('#addServiceReceiptForm');

    const saved = localStorage.getItem(storageKey);
    if (saved) {
        try {
            const data = JSON.parse(saved);
            Object.entries(data).forEach(([k, v]) => {
                if (k === 'receipt_number') return;
                const $el = $form.find(`[name="${k}"]`);
                if ($el.is('select')) {
                    $el.val(v).trigger('change');
                } else {
                    $el.val(v);
                }
            });
        } catch (e) { }
    }

    // Toggle Payment Fields
    $('#payment_type').on('change', function () {
        if ($(this).val() === 'cash') {
            $('.cash-fields').removeClass('d-none');
        } else {
            $('.cash-fields').addClass('d-none');
            $('#paid_usd, #paid_iqd').val(0);
        }
        calculateReceiptTotals();
    }).trigger('change');

    // Live Calculation Logic
    function calculateReceiptTotals() {
        const meter = parseFloat($('#meter_amount').val()) || 0;
        const price = parseFloat($('#price_per_meter').val()) || 0;
        const paidUsd = parseFloat($('#paid_usd').val()) || 0;
        const paidIqd = parseFloat($('#paid_iqd').val()) || 0;
        const rate = parseFloat($('#exchange_rate').val()) || 1;

        const totalUsd = meter * price;
        const paidFromIqd = rate > 0 ? (paidIqd / rate) : 0;
        const totalPaid = paidUsd + paidFromIqd;
        const balance = totalUsd - totalPaid;

        $('#display_total_price').val(totalUsd.toFixed(2));

        const $balanceEl = $('#display_remaining_balance');
        $balanceEl.text(balance.toFixed(2) + ' $');

        if (balance > 0.01) {
            $balanceEl.removeClass('text-success text-muted').addClass('text-danger');
        } else if (balance < -0.01) {
            $balanceEl.removeClass('text-danger text-muted').addClass('text-success');
        } else {
            $balanceEl.removeClass('text-danger text-success').addClass('text-muted');
        }
    }

    $form.on('input', '.calc-input', calculateReceiptTotals);

    // Save form data on change
    $form.on('input change', 'input, select, textarea', function () {
        const data = {};
        $form.serializeArray().forEach(({ name, value }) => {
            if (name !== 'receipt_number') data[name] = value;
        });
        localStorage.setItem(storageKey, JSON.stringify(data));
    });

    $('#addServiceReceiptForm').on('submit', async function (e) {
        e.preventDefault();

        if (submitting) {
            showAlert('warning', 'تکایە چاوەڕوان بە...');
            return false;
        }

        submitting = true;
        const submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true);
        submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...');

        try {
            const formData = new FormData(this);
            const res = await fetch('../process/service_receipts/add_service_receipts.php', {
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
                            const allData = {};
                            $form.serializeArray().forEach(({ name, value }) => {
                                if (!["meter_amount", "receipt_number", "notes", "paid_usd", "paid_iqd"].includes(name)) {
                                    allData[name] = value;
                                }
                            });
                            localStorage.setItem(storageKey, JSON.stringify(allData));
                        }

                        $('#addServiceReceiptForm')[0].reset();
                        $('#addServiceReceiptModal').modal('hide');

                        if (window.reloadServiceReceipts) window.reloadServiceReceipts();

                        // Reset display
                        $('#display_total_price').val('0.00');
                        $('#display_remaining_balance').text('0.00 $').removeClass('text-danger text-success').addClass('text-muted');

                        // Restore retained values
                        const restoredData = localStorage.getItem(storageKey);
                        if (restoredData) {
                            const data = JSON.parse(restoredData);
                            Object.entries(data).forEach(([k, v]) => {
                                const $el = $form.find(`[name="${k}"]`);
                                if ($el.is('select')) {
                                    $el.val(v).trigger('change');
                                } else {
                                    $el.val(v);
                                }
                            });
                        }
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: data.message || 'هەڵەیەک ڕویدا!'
                });
            }
        } catch (error) {
            console.error('Error adding service receipt:', error);
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'هەڵەیەک لە پەیوەندی بە سێرڤەرەوە هەیە'
            });
        } finally {
            submitting = false;
            submitBtn.prop('disabled', false);
            submitBtn.html(originalBtnText);
        }
    });

    $('#addServiceReceiptModal').on('show.bs.modal', function () {
        $.get('../process/service_receipts/get_next_receipt_number.php', function (res) {
            if (res && res.success && res.next) {
                $('#receipt_number').val(res.next);
            } else {
                $('#receipt_number').val('A-0001');
            }
        }, 'json').fail(function () {
            $('#receipt_number').val('A-0001');
        });
        calculateReceiptTotals();
    });
});
