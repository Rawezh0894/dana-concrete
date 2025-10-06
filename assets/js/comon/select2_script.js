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
            placeholder: $(selector).attr('data-placeholder') || "هەڵبژێرە",
            dir: "rtl",
            matcher: customMatcher
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

// Normalize Arabic/Kurdish characters for search
function normalizeArabicKurdish(str) {
    if (!str) return '';
    return str
        .replace(/[كڪ]/g, 'ک')
        .replace(/[يى]/g, 'ی')
        .replace(/[ةە]/g, 'ە')
        .replace(/[ؤ]/g, 'ۆ')
        .replace(/[إأآا]/g, 'ا')
        .replace(/[ئء]/g, 'ئ')
        .replace(/[و]/g, 'و')
        .replace(/[ذ]/g, 'ذ')
        .replace(/[ز]/g, 'ز')
        .replace(/[ر]/g, 'ر')
        .replace(/[ط]/g, 'ط')
        .replace(/[ظ]/g, 'ظ')
        .replace(/[ص]/g, 'ص')
        .replace(/[ض]/g, 'ض')
        .replace(/[ث]/g, 'ث')
        .replace(/[ق]/g, 'ق')
        .replace(/[ف]/g, 'ف')
        .replace(/[غ]/g, 'غ')
        .replace(/[ع]/g, 'ع')
        .replace(/[س]/g, 'س')
        .replace(/[ش]/g, 'ش')
        .replace(/[ن]/g, 'ن')
        .replace(/[م]/g, 'م')
        .replace(/[ل]/g, 'ل')
        .replace(/[ب]/g, 'ب')
        .replace(/[ت]/g, 'ت')
        .replace(/[ج]/g, 'ج')
        .replace(/[ح]/g, 'ح')
        .replace(/[د]/g, 'د')
        .replace(/[پ]/g, 'پ')
        .replace(/[چ]/g, 'چ')
        .replace(/[ژ]/g, 'ژ')
        .replace(/[گ]/g, 'گ')
        .replace(/[ڕ]/g, 'ڕ')
        .replace(/[ڵ]/g, 'ڵ')
        .replace(/[ێ]/g, 'ێ')
        .replace(/[ۆ]/g, 'ۆ')
        .replace(/[ە]/g, 'ە')
        .replace(/[ی]/g, 'ی')
        .replace(/[ق]/g, 'ق');
}
function customMatcher(params, data) {
    if ($.trim(params.term) === '') {
        return data;
    }
    var term = normalizeArabicKurdish(params.term);
    var text = normalizeArabicKurdish(data.text);
    if (text.indexOf(term) > -1) {
        return data;
    }
    return null;
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
        enableSelect2('#driver_id', '#addPurchaseModal');
        enableSelect2('#location_id', '#addPurchaseModal');
    }
    if ($('#editPurchaseModal').length > 0) {
        enableSelect2('#edit_company_id', '#editPurchaseModal');
        enableSelect2('#edit_driver_id', '#editPurchaseModal');
        enableSelect2('#edit_location_id', '#editPurchaseModal');
    }
    
    // بۆ concrete receipts - تەنها ئەگەر مۆداڵەکە هەبێت
    if ($('#addConcreteReceiptModal').length > 0) {
        enableSelect2('#customer_id', '#addConcreteReceiptModal');
    }
    if ($('#editConcreteReceiptModal').length > 0) {
        enableSelect2('#edit_customer_id', '#editConcreteReceiptModal');
    }
    
    // بۆ purchase materials - تەنها ئەگەر مۆداڵەکە هەبێت
    if ($('#addPurchaseModal').length > 0) {
        enableSelect2('#person_id', '#addPurchaseModal');
        enableSelect2('#currency_type', '#addPurchaseModal');
    }
    if ($('#editPurchaseModal').length > 0) {
        enableSelect2('#edit_person_id', '#editPurchaseModal');
        enableSelect2('#edit_currency_type', '#editPurchaseModal');
    }

    // بۆ other expenses - add/edit modals
    if ($('#addExpenseModal').length > 0) {
        enableSelect2('#employee_id', '#addExpenseModal');
        enableSelect2('#car_id', '#addExpenseModal');
        enableSelect2('#person_id', '#addExpenseModal');
        enableSelect2('#payment_type', '#addExpenseModal');
        enableSelect2('#currency_type', '#addExpenseModal');
        enableSelect2('#material_id', '#addExpenseModal');
        enableSelect2('#usage_unit_type', '#addExpenseModal');
    }
    if ($('#editExpenseModal').length > 0) {
        enableSelect2('#edit_employee_id', '#editExpenseModal');
        enableSelect2('#edit_car_id', '#editExpenseModal');
        enableSelect2('#edit_person_id', '#editExpenseModal');
        enableSelect2('#edit_payment_type', '#editExpenseModal');
        enableSelect2('#edit_currency_type', '#editExpenseModal');
        enableSelect2('#edit_material_id', '#editExpenseModal');
        enableSelect2('#edit_usage_unit_type', '#editExpenseModal');
    }
    
    // بۆ notes - تەنها ئەگەر مۆداڵەکە هەبێت
    if ($('#addNoteModal').length > 0) {
        enableSelect2('#customer_id', '#addNoteModal');
    }
    if ($('#editNoteModal').length > 0) {
        enableSelect2('#edit_customer_id', '#editNoteModal');
    }
    
    // بۆ notes filters - تەنها ئەگەر پەیجەکە هەبێت
    if ($('#filter_customer').length > 0) {
        enableSelect2('#filter_customer', 'body');
    }
    if ($('#filter_read').length > 0) {
        enableSelect2('#filter_read', 'body');
    }
    
    // بۆ concrete receipts filters - تەنها ئەگەر پەیجەکە هەبێت
    if ($('#filter_customer_id').length > 0) {
        enableSelect2('#filter_customer_id', 'body');
    }
    if ($('#filter_formulas_id').length > 0) {
        enableSelect2('#filter_formulas_id', 'body');
    }
});

// Focus select2 search input when dropdown opens for customer select in addConcreteReceiptModal
$(document).on('select2:open', function(e) {
    if (e.target && (e.target.id === 'customer_id' || e.target.id === 'edit_customer_id')) {
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