document.getElementById('editPurchaseForm').onsubmit = async function(e) {
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
        return;
    }
    if (type === 'دۆلار' && pricePerKgUsd < 0) {
        Swal.fire('هەڵە!', 'بڕی price_per_kg_usd نابێت منفی بێت!', 'error');
        return;
    }
    if (hasNegative) {
        Swal.fire('هەڵە!', 'نابێت هیچ بڕێک منفی بێت!', 'error');
        return;
    }
    // Prevent remaining_usd or remaining_iqd if payment_type is 'نەقد'
    const paymentType = form.querySelector('[name="payment_type"]').value;
    const remainingUsd = parseFloat(form.querySelector('[name="remaining_usd"]').value) || 0;
    const remainingIqd = parseFloat(form.querySelector('[name="remaining_iqd"]').value) || 0;
    if (paymentType === 'نەقد' && (remainingUsd !== 0 || remainingIqd !== 0)) {
        Swal.fire('هەڵە!', 'بڕی پارەی ماوە نابێت بێت کاتێک جۆری پارەدان نەقدە!', 'error');
        return;
    }
    const formData = new FormData(form);
    const res = await fetch('../process/purchase/update_purchase.php', {
        method: 'POST',
        body: formData
    });
    let data;
    try {
        data = await res.json();
    } catch (e) {
        const text = await res.text();
        console.error('Raw response:', text);
        Swal.fire('هەڵە!', 'هەڵەیەک لە وەڵامەکەی سێرڤەر هەیە. زانیاری زیاتر لە console.', 'error');
        return;
    }
    if (data.success) {
        Swal.fire('سەرکەوتوو!', 'کڕین نوێکرایەوە', 'success');
        var modal = bootstrap.Modal.getInstance(document.getElementById('editPurchaseModal'));
        modal.hide();
        if (typeof loadPurchases === 'function') loadPurchases();
    } else {
        Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
    }
};
