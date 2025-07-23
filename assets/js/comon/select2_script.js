// گشتی: چالاککردنی select2 بۆ هەر select ـێک
function enableSelect2(selector, modalSelector) {
    // Destroy previous select2 instance if exists
    if ($(selector).hasClass('select2-hidden-accessible')) {
        try {
            $(selector).select2('destroy');
        } catch (e) {
            console.log('Error destroying select2:', e);
        }
    }
    // Initialize select2 (no theme for max compatibility)
    try {
        $(selector).select2({
            dropdownParent: $(modalSelector),
            width: '100%',
            placeholder: "هەڵبژێرە",
            dir: "rtl"
        });
    } catch (e) {
        console.log('Error initializing select2:', e);
        return;
    }
    // Fix: Only initialize once per modal show
    $(modalSelector).off('shown.bs.modal.select2').on('shown.bs.modal.select2', function () {
        try {
            if ($(selector).length > 0 && $(selector).hasClass('select2-hidden-accessible')) {
                setTimeout(function() {
                    $(selector).select2('open');
                    $(selector).select2('close');
                }, 100);
            }
        } catch (e) {
            console.log('Select2 modal error:', e);
        }
    });
}

// چالاککردنی select2 بۆ کڕیار لە مۆداڵی زیادکردن
$(document).ready(function() {
    // بۆ sale - تەنها ئەگەر مۆداڵەکە هەبێت
    if ($('#addSaleModal').length > 0) {
        enableSelect2('#customer_id', '#addSaleModal');
    }
    if ($('#editSaleModal').length > 0) {
        enableSelect2('#edit_customer_id', '#editSaleModal');
    }
    
    // بۆ purchase - تەنها ئەگەر مۆداڵەکە هەبێت
    if ($('#addPurchaseModal').length > 0) {
        enableSelect2('#company_id', '#addPurchaseModal');
    }
    if ($('#editPurchaseModal').length > 0) {
        enableSelect2('#edit_company_id', '#editPurchaseModal');
    }
    
    // بۆ concrete receipts - تەنها ئەگەر مۆداڵەکە هەبێت
    if ($('#addConcreteReceiptModal').length > 0) {
        enableSelect2('#customer_id', '#addConcreteReceiptModal');
    }
    if ($('#editConcreteReceiptModal').length > 0) {
        enableSelect2('#edit_customer_id', '#editConcreteReceiptModal');
    }
});

// Focus select2 search input when dropdown opens for customer select in addConcreteReceiptModal
$(document).on('select2:open', function(e) {
    if (e.target && e.target.id === 'customer_id') {
        setTimeout(function() {
            let searchBox = document.querySelector('.select2-container--open .select2-search__field');
            if (searchBox) searchBox.focus();
        }, 10);
    }
});

// Add CSS for select2 dropdown scroll
if (typeof window !== 'undefined') {
    var style = document.createElement('style');
    style.innerHTML = '.select2-results__options { max-height: 220px !important; overflow-y: auto !important; }';
    document.head.appendChild(style);
}