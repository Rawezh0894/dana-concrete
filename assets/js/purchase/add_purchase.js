let submitting = false;
document.getElementById('addPurchaseForm').onsubmit = async function(e) {
    if (submitting) return false;
    submitting = true;
    e.preventDefault();
    const form = e.target;
    let hasNegative = false;
    form.querySelectorAll('input[type="number"]').forEach(input => {
        if (parseFloat(input.value) < 0) {
            hasNegative = true;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    });
    const type = form.querySelector('[name="type"]').value;
    const pricePerKgIqd = parseFloat(form.querySelector('[name="price_per_kg_iqd"]').value) || 0;
    const pricePerKgUsd = parseFloat(form.querySelector('[name="price_per_kg_usd"]').value) || 0;
    if (type === 'دینار' && pricePerKgIqd < 0) {
        Swal.fire('هەڵە!', 'بڕی price_per_kg_iqd نابێت منفی بێت!', 'error');
        submitting = false;
        return;
    }
    if (type === 'دۆلار' && pricePerKgUsd < 0) {
        Swal.fire('هەڵە!', 'بڕی price_per_kg_usd نابێت منفی بێت!', 'error');
        submitting = false;
        return;
    }
    if (hasNegative) {
        Swal.fire('هەڵە!', 'نابێت هیچ بڕێک منفی بێت!', 'error');
        submitting = false;
        return;
    }
    // Prevent remaining_usd or remaining_iqd if payment_type is 'نەقد'
    const paymentType = form.querySelector('[name="payment_type"]').value;
    const remainingUsd = parseFloat(form.querySelector('[name="remaining_usd"]').value) || 0;
    const remainingIqd = parseFloat(form.querySelector('[name="remaining_iqd"]').value) || 0;
    if (paymentType === 'نەقد' && (remainingUsd !== 0 || remainingIqd !== 0)) {
        Swal.fire('هەڵە!', 'بڕی پارەی ماوە نابێت بێت کاتێک جۆری پارەدان نەقدە!', 'error');
        submitting = false;
        return;
    }
    const formData = new FormData(form);
    try {
        const res = await fetch('../process/purchase/add_purchase.php', {
            method: 'POST',
            body: formData
        });
        let text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Raw response:', text);
            Swal.fire('هەڵە!', text || 'هەڵەیەک لە وەڵامەکەی سێرڤەر هەیە. زانیاری زیاتر لە console.', 'error');
            submitting = false;
            return;
        }
        if (data.success) {
            Swal.fire('سەرکەوتوو!', 'کڕین زیادکرا', 'success');
            form.reset();
            var modal = bootstrap.Modal.getInstance(document.getElementById('addPurchaseModal'));
            modal.hide();
            if (typeof loadPurchases === 'function') loadPurchases();
        } else {
            Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
        }
    } catch (err) {
        Swal.fire('هەڵە!', 'هەڵەیەک ڕویدا', 'error');
    }
    submitting = false;
};
