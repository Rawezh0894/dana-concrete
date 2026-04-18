// گشتی: چالاککردنی select2 بۆ هەر select ـێک
function enableSelect2(selector, modalSelector) {
    const $element = $(selector);
    if ($element.length === 0) {
        return;
    }
    // Destroy previous select2 instance if exists
    if ($element.hasClass('select2-hidden-accessible')) {
        try {
            $element.select2('destroy');
        } catch (e) {
            console.log('Error destroying select2:', e);
        }
    }
    const allowNewRecipient = String($element.data('allowNewRecipient')).toLowerCase() === 'true';
    // Check if select has an empty option (for allowing "none" selection)
    const hasEmptyOption = $element.find('option[value=""]').length > 0;
    const select2Options = {
        theme: 'bootstrap-5',
        dropdownParent: $(modalSelector),
        width: '100%',
        placeholder: $element.attr('data-placeholder') || "هەڵبژێرە",
        dir: "rtl",
        matcher: customMatcher,
        allowClear: hasEmptyOption // Allow clearing if empty option exists
    };

    if (allowNewRecipient) {
        select2Options.tags = true;
        select2Options.createTag = function (params) {
            const term = $.trim(params.term || '');
            if (!term) return null;
            return {
                id: '__new__' + Date.now() + Math.floor(Math.random() * 1000),
                text: term,
                newTag: true
            };
        };
        select2Options.templateResult = function (data) {
            if (data.newTag) {
                return $('<span class="text-success"><i class="fas fa-plus-circle me-2"></i>زیادکردنی وەرگر: ' + data.text + '</span>');
            }
            return data.text;
        };
    }

    // Initialize select2
    try {
        $element.select2(select2Options);
    } catch (e) {
        console.log('Error initializing select2:', e);
        return;
    }
    if (allowNewRecipient) {
        setupRecipientQuickAdd($element);
    }
    // Fix: Only initialize once per modal show using unique namespace
    const elementId = $element.attr('id') || Math.random().toString(36).substring(7);
    $(modalSelector).off('shown.bs.modal.select2_' + elementId).on('shown.bs.modal.select2_' + elementId, function () {
        try {
            if ($element.length > 0 && $element.hasClass('select2-hidden-accessible')) {
                setTimeout(function () {
                    $element.select2('open');
                    $element.select2('close');
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
    var extra = '';
    if (data.element && data.element.dataset && data.element.dataset.search) {
        extra = normalizeArabicKurdish(data.element.dataset.search);
    }
    if (text.indexOf(term) > -1 || extra.indexOf(term) > -1) {
        return data;
    }
    return null;
}

function setupRecipientQuickAdd($select) {
    if (!$select.length) return;
    const selector = '#' + $select.attr('id');
    $select.off('select2:select.recipientQuickAdd').on('select2:select.recipientQuickAdd', function (e) {
        const data = e.params && e.params.data;
        if (data && data.newTag) {
            const option = $select.find('option[value="' + data.id + '"]');
            if (option.length) {
                option.remove();
            }
            $select.val('').trigger('change.select2');
            openRecipientModal((data.text || '').trim(), selector);
        }
    });
}

function openRecipientModal(initialName, selector) {
    if (typeof bootstrap === 'undefined') return;
    const modalEl = document.getElementById('addRecipientModal');
    if (!modalEl) return;
    window.pendingRecipientSelectId = selector;
    window.pendingRecipientInitialName = initialName || '';

    const nameInput = modalEl.querySelector('#recipient_name');
    if (nameInput) {
        nameInput.value = initialName || '';
        setTimeout(() => nameInput.focus(), 150);
    }
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    if (!modalEl.dataset.quickAddHooked) {
        modalEl.addEventListener('hidden.bs.modal', function () {
            window.pendingRecipientSelectId = null;
            window.pendingRecipientInitialName = null;
        });
        modalEl.dataset.quickAddHooked = '1';
    }
}

// چالاککردنی select2 بۆ کڕیار لە مۆداڵی زیادکردن
$(document).ready(function () {
    // بۆ sale - تەنها ئەگەر مۆداڵەکە هەبێت
    if ($('#addSaleModal').length > 0) {
        enableSelect2('#customer_id', '#addSaleModal');
        enableSelect2('#recipient', '#addSaleModal');
    }
    if ($('#editSaleModal').length > 0) {
        enableSelect2('#edit_customer_id', '#editSaleModal');
        enableSelect2('#edit_recipient', '#editSaleModal');
    }

    // بۆ purchase - تەنها ئەگەر مۆداڵەکە هەبێت
    if ($('#addPurchaseModal').length > 0) {
        enableSelect2('#company_id', '#addPurchaseModal');
        enableSelect2('#driver_id', '#addPurchaseModal');
        enableSelect2('#factory_truck_id', '#addPurchaseModal');
        enableSelect2('#location_id', '#addPurchaseModal');
        enableSelect2('#material_id', '#addPurchaseModal');
    }
    if ($('#editPurchasePanel').length > 0) {
        enableSelect2('#edit_company_id', '#editPurchasePanel');
        enableSelect2('#edit_driver_id', '#editPurchasePanel');
        enableSelect2('#edit_factory_truck_id', '#editPurchasePanel');
        enableSelect2('#edit_location_id', '#editPurchasePanel');
        enableSelect2('#edit_material_id', '#editPurchasePanel');
    } else if ($('#editPurchaseModal').length > 0) {
        enableSelect2('#edit_company_id', '#editPurchaseModal');
        enableSelect2('#edit_driver_id', '#editPurchaseModal');
        enableSelect2('#edit_location_id', '#editPurchaseModal');
        enableSelect2('#edit_material_id', '#editPurchaseModal');
    }

    // بۆ purchase filters
    if ($('#filter_company').length > 0) {
        enableSelect2('#filter_company', 'body');
    }
    if ($('#filter_location').length > 0) {
        enableSelect2('#filter_location', 'body');
    }
    if ($('#filter_driver').length > 0) {
        enableSelect2('#filter_driver', 'body');
    }
    if ($('#filter_material').length > 0) {
        enableSelect2('#filter_material', 'body');
    }

    // بۆ concrete receipts - تەنها ئەگەر مۆداڵەکە هەبێت
    if ($('#addConcreteReceiptModal').length > 0) {
        enableSelect2('#customer_id', '#addConcreteReceiptModal');
        enableSelect2('#receiver_name', '#addConcreteReceiptModal');
        enableSelect2('#mixer_driver_id', '#addConcreteReceiptModal');
        enableSelect2('#pump_driver_id', '#addConcreteReceiptModal');
    }
    if ($('#editConcreteReceiptModal').length > 0) {
        enableSelect2('#edit_customer_id', '#editConcreteReceiptModal');
        enableSelect2('#edit_receiver_name', '#editConcreteReceiptModal');
        enableSelect2('#edit_mixer_driver_id', '#editConcreteReceiptModal');
        enableSelect2('#edit_pump_driver_id', '#editConcreteReceiptModal');
    }

    // بۆ service receipts - تەنها ئەگەر مۆداڵەکە هەبێت
    if ($('#addServiceReceiptModal').length > 0) {
        enableSelect2('#customer_id', '#addServiceReceiptModal');
        enableSelect2('#receiver_name', '#addServiceReceiptModal');
        enableSelect2('#mixer_car_id', '#addServiceReceiptModal');
        enableSelect2('#mixer_driver_id', '#addServiceReceiptModal');
        enableSelect2('#pump_car_id', '#addServiceReceiptModal');
        enableSelect2('#pump_driver_id', '#addServiceReceiptModal');
    }
    if ($('#editServiceReceiptModal').length > 0) {
        enableSelect2('#edit_customer_id', '#editServiceReceiptModal');
        enableSelect2('#edit_receiver_name', '#editServiceReceiptModal');
        enableSelect2('#edit_mixer_car_id', '#editServiceReceiptModal');
        enableSelect2('#edit_mixer_driver_id', '#editServiceReceiptModal');
        enableSelect2('#edit_pump_car_id', '#editServiceReceiptModal');
        enableSelect2('#edit_pump_driver_id', '#editServiceReceiptModal');
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
        enableSelect2('#recipient', '#addNoteModal');
        enableSelect2('#mixer_driver_id', '#addNoteModal');
        enableSelect2('#pump_driver_id', '#addNoteModal');
    }
    if ($('#editNoteModal').length > 0) {
        enableSelect2('#edit_customer_id', '#editNoteModal');
        enableSelect2('#edit_recipient', '#editNoteModal');
        enableSelect2('#edit_mixer_driver_id', '#editNoteModal');
        enableSelect2('#edit_pump_driver_id', '#editNoteModal');
    }

    // بۆ notes filters - تەنها ئەگەر پەیجەکە هەبێت
    if ($('#filter_customer').length > 0) {
        enableSelect2('#filter_customer', 'body');
    }

    // بۆ concrete receipts filters - تەنها ئەگەر پەیجەکە هەبێت
    if ($('#filter_customer_id').length > 0) {
        enableSelect2('#filter_customer_id', 'body');
    }
    if ($('#filter_formulas_id').length > 0) {
        enableSelect2('#filter_formulas_id', 'body');
    }

    // بۆ employee role filter - تەنها ئەگەر پەیجەکە هەبێت
    if ($('#filter_role').length > 0) {
        enableSelect2('#filter_role', 'body');
    }

    // بۆ other expenses - تەنها ئەگەر مۆداڵەکە هەبێت
    if ($('#addExpenseModal').length > 0) {
        enableSelect2('#person_id', '#addExpenseModal');
        enableSelect2('#material_id', '#addExpenseModal');
    }
    if ($('#editExpenseModal').length > 0) {
        enableSelect2('#edit_person_id', '#editExpenseModal');
        enableSelect2('#edit_material_id', '#editExpenseModal');
    }
    // بۆ other expenses filters - تەنها ئەگەر پەیجەکە هەبێت
    if ($('#personFilter').length > 0) {
        enableSelect2('#personFilter', 'body');
    }
});

// Focus select2 search input when dropdown opens for customer select in addConcreteReceiptModal
$(document).on('select2:open', function (e) {
    if (e.target && (e.target.id === 'customer_id' || e.target.id === 'edit_customer_id')) {
        setTimeout(function () {
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