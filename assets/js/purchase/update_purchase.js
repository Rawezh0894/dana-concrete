// Multiple submission prevention flag
let isUpdating = false;

function getEditPurchaseDropdownParent() {
    if (typeof $ === 'undefined') return null;
    const $panel = $('#editPurchasePanel');
    if ($panel.length > 0) return $panel;
    const $modal = $('#editPurchaseModal');
    return $modal.length > 0 ? $modal : null;
}

function ensureEditDriverSelect2() {
    if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') return;
    const $driverSelect = $('#edit_driver_id');
    const $parent = getEditPurchaseDropdownParent();
    if ($driverSelect.length === 0 || !$parent || $parent.length === 0) return;
    
    try {
        if ($driverSelect.hasClass('select2-hidden-accessible')) {
            $driverSelect.select2('destroy');
        }
        $driverSelect.select2({
            dropdownParent: $parent,
            width: '100%',
            dir: 'rtl',
            placeholder: $driverSelect.attr('data-placeholder') || 'شۆفێرەکان',
            allowClear: $driverSelect.find('option[value=""]').length > 0
        });
    } catch (error) {
        console.error('Failed to initialize select2 on edit driver select:', error);
    }
}

function ensureEditPurchasePanelSelect2() {
    if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') return;
    const $panel = $('#editPurchasePanel');
    if ($panel.length === 0) return;

    const ids = ['#edit_company_id', '#edit_driver_id', '#edit_factory_truck_id', '#edit_location_id', '#edit_material_id', '#edit_bin_id'];
    ids.forEach((sel) => {
        const $el = $(sel);
        if ($el.length === 0) return;
        try {
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            $el.select2({
                dropdownParent: $panel,
                width: '100%',
                dir: 'rtl',
                allowClear: $el.find('option[value=""]').length > 0
            });
        } catch (e) {
            console.error('Failed to initialize select2 for', sel, e);
        }
    });
}

function hideEditPurchaseUi() {
    const panel = document.getElementById('editPurchasePanel');
    if (panel) {
        panel.classList.add('d-none');
        return;
    }
    const modalEl = document.getElementById('editPurchaseModal');
    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const inst = bootstrap.Modal.getInstance(modalEl);
        if (inst) inst.hide();
    }
}
window.hideEditPurchaseUi = hideEditPurchaseUi;

document.addEventListener('DOMContentLoaded', function() {
    ensureEditDriverSelect2();
    $('#editPurchaseModal').on('shown.bs.modal', ensureEditDriverSelect2);
    $(document).on('editPurchasePanel:opened', '#editPurchasePanel', function () {
        ensureEditPurchasePanelSelect2();
        ensureEditDriverSelect2();
    });
});

// API call removed - exchange_rate will be manually entered by user with default value of 0

document.getElementById('editPurchaseForm').onsubmit = async function(e) {
    e.preventDefault();
    
    // Prevent multiple submissions
    if (isUpdating) {
        showAlert('warning', 'تکایە چاوەڕوان بە...');
        return false;
    }
    
    // Set updating flag and disable submit button
    isUpdating = true;
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>چاوەڕوان بە...';
    }
    
    const form = e.target;
    
    // Validate required fields
    const requiredFields = [
        'id', 'company_id', 'driver_id', 'location_id', 'invoice_number', 
        'material_id', 'date', 'type', 'kg', 'exchange_rate', 'price', 'payment_type'
    ];
    
    const missingFields = [];
    for (const fieldName of requiredFields) {
        const field = form.querySelector(`[name="${fieldName}"]`);
        if (!field || field.value.trim() === '') {
            missingFields.push(fieldName);
            if (field) field.classList.add('is-invalid');
        } else {
            if (field) field.classList.remove('is-invalid');
        }
    }
    
    // Validate exchange_rate (must be greater than 0)
    const exchangeRate = parseFloat(form.querySelector('[name="exchange_rate"]').value) || 0;
    if (exchangeRate <= 0) {
        missingFields.push('exchange_rate');
        form.querySelector('[name="exchange_rate"]').classList.add('is-invalid');
    } else {
        form.querySelector('[name="exchange_rate"]').classList.remove('is-invalid');
    }
    
    // Validate price_per_kg based on type
    const type = form.querySelector('[name="type"]').value;
    const pricePerKgIqd = parseFloat(form.querySelector('[name="price_per_kg_iqd"]').value) || 0;
    const pricePerKgUsd = parseFloat(form.querySelector('[name="price_per_kg_usd"]').value) || 0;
    
    if (type === 'دینار' && pricePerKgIqd <= 0) {
        missingFields.push('price_per_kg_iqd');
        form.querySelector('[name="price_per_kg_iqd"]').classList.add('is-invalid');
    }
    if (type === 'دۆلار' && pricePerKgUsd <= 0) {
        missingFields.push('price_per_kg_usd');
        form.querySelector('[name="price_per_kg_usd"]').classList.add('is-invalid');
    }
    
    if (missingFields.length > 0) {
        Swal.fire('هەڵە!', `تکایە خانەکانی خوارەوە پڕ بکە: ${missingFields.join(', ')}`, 'error');
        isUpdating = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
        return false;
    }
    
    // Validate numeric fields
    let hasNegative = false;
    form.querySelectorAll('input[type="number"]').forEach(input => {
        if (parseFloat(input.value) < 0) {
            hasNegative = true;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    if (hasNegative) {
        Swal.fire('هەڵە!', 'نابێت هیچ بڕێک منفی بێت!', 'error');
        isUpdating = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
        return false;
    }
    
    // Prevent remaining_usd or remaining_iqd if payment_type is 'نەقد'
    const paymentType = form.querySelector('[name="payment_type"]').value;
    const remainingUsd = parseFloat(form.querySelector('[name="remaining_usd"]').value) || 0;
    const remainingIqd = parseFloat(form.querySelector('[name="remaining_iqd"]').value) || 0;
    if (paymentType === 'نەقد' && (remainingUsd !== 0 || remainingIqd !== 0)) {
        Swal.fire('هەڵە!', 'بڕی پارەی ماوە نابێت بێت کاتێک جۆری پارەدان نەقدە!', 'error');
        isUpdating = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
        return false;
    }
    
    const formData = new FormData(form);
    try {
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
            hideEditPurchaseUi();
            if (typeof reloadPurchases === 'function') {
                reloadPurchases();
            } else if (typeof refreshPurchaseTable === 'function') {
                refreshPurchaseTable();
            } else if (typeof loadPurchaseData === 'function') {
                loadPurchaseData(true);
            }
            if (typeof loadPurchaseSummary === 'function') {
                loadPurchaseSummary(typeof currentFilterParams === 'string' ? currentFilterParams : '');
            }
        } else {
            Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
        }
    } catch (err) {
        console.error('Error updating purchase:', err);
        Swal.fire('هەڵە!', 'هەڵەیەک ڕویدا', 'error');
    } finally {
        // Reset updating flag and restore submit button
        isUpdating = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    }
};
