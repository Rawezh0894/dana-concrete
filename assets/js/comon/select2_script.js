// گشتی: چالاککردنی select2 بۆ هەر select ـێک
function enableSelect2(selector, modalSelector) {
    // چێککردنی select2 ئەگەر پێشتر چالاک نەبووبێت
    if ($(selector).hasClass('select2-hidden-accessible')) {
        try {
            $(selector).select2('destroy');
        } catch (e) {
            console.log('Error destroying select2:', e);
        }
    }
    
    // چێککردنی select2
    try {
        $(selector).select2({
            dropdownParent: $(modalSelector),
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: "هەڵبژێرە",
            dir: "rtl"
        });
    } catch (e) {
        console.log('Error initializing select2:', e);
        return;
    }
    
    // بۆ ئەوەی dropdown لە مۆداڵی bootstrap 5 کار بکات
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