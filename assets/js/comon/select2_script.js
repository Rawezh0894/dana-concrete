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
    
    // بۆ purchase materials - تەنها ئەگەر مۆداڵەکە هەبێت
    if ($('#addPurchaseModal').length > 0) {
        enableSelect2('#person_id', '#addPurchaseModal');
        enableSelect2('#currency_type', '#addPurchaseModal');
    }
    if ($('#editPurchaseModal').length > 0) {
        enableSelect2('#edit_person_id', '#editPurchaseModal');
        enableSelect2('#edit_currency_type', '#editPurchaseModal');
    }
    
    // بۆ notes - تەنها ئەگەر مۆداڵەکە هەبێت
    if ($('#addNoteModal').length > 0) {
        enableSelect2('#customer_id', '#addNoteModal');
        enableSelect2('#formula_id', '#addNoteModal');
    }
    if ($('#editNoteModal').length > 0) {
        enableSelect2('#edit_customer_id', '#editNoteModal');
        enableSelect2('#edit_formula_id', '#editNoteModal');
    }
    
    // بۆ notes filters - تەنها ئەگەر پەیجەکە هەبێت
    if ($('#filter_customer').length > 0) {
        enableSelect2('#filter_customer', 'body');
    }
    if ($('#filter_read').length > 0) {
        enableSelect2('#filter_read', 'body');
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

// Mobile-specific Select2 handling
$(document).ready(function() {
    // Check if device is mobile
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        // Handle mobile Select2 dropdown positioning
        $(document).on('select2:open', function(e) {
            setTimeout(function() {
                const dropdown = $('.select2-container--open .select2-dropdown');
                if (dropdown.length > 0) {
                    // Force dropdown to bottom of screen on mobile
                    dropdown.css({
                        'position': 'fixed',
                        'bottom': '0',
                        'top': 'auto',
                        'left': '0',
                        'right': '0',
                        'width': '100%',
                        'max-width': '100vw',
                        'z-index': '9999'
                    });
                    
                    // Add close button functionality
                    const closeBtn = $('<div class="mobile-select2-close">✕</div>');
                    closeBtn.css({
                        'position': 'absolute',
                        'top': '8px',
                        'right': '12px',
                        'font-size': '18px',
                        'color': '#666',
                        'z-index': '10000',
                        'cursor': 'pointer',
                        'background': 'white',
                        'padding': '4px 8px',
                        'border-radius': '4px',
                        'border': '1px solid #ddd'
                    });
                    
                    dropdown.prepend(closeBtn);
                    
                    // Close dropdown when close button is clicked
                    closeBtn.on('click', function() {
                        $(e.target).select2('close');
                    });
                    
                    // Close dropdown when clicking outside
                    $(document).on('click.mobileSelect2', function(event) {
                        if (!$(event.target).closest('.select2-container').length) {
                            $(e.target).select2('close');
                            $(document).off('click.mobileSelect2');
                        }
                    });
                }
            }, 100);
        });
        
        // Clean up when dropdown closes
        $(document).on('select2:close', function(e) {
            $(document).off('click.mobileSelect2');
            $('.mobile-select2-close').remove();
        });
    }
});

// Add CSS for select2 dropdown scroll
if (typeof window !== 'undefined') {
    var style = document.createElement('style');
    style.innerHTML = '.select2-results__options { max-height: 220px !important; overflow-y: auto !important; }';
    document.head.appendChild(style);
}