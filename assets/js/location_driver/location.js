document.getElementById('addLocationForm').onsubmit = async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const res = await fetch('../process/location_driver/add_location.php', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    if (data.success) {
        // زیادکردنی option ـی نوێ بۆ select
        const select = document.getElementById('location_id');
        const editSelect = document.getElementById('edit_location_id');
        const filterSelect = document.getElementById('filter_location');
        
        // زیادکردن بۆ add purchase modal
        const opt = document.createElement('option');
        opt.value = data.id;
        opt.textContent = data.name;
        select.appendChild(opt);
        select.value = data.id;
        
        // زیادکردن بۆ edit purchase modal
        const editOpt = document.createElement('option');
        editOpt.value = data.id;
        editOpt.textContent = data.name;
        editSelect.appendChild(editOpt);
        
        // زیادکردن بۆ filter
        const filterOpt = document.createElement('option');
        filterOpt.value = data.id;
        filterOpt.textContent = data.name;
        filterSelect.appendChild(filterOpt);
        
        // گەڕاندنەوەی select2 بە شێوەیەکی دروست
        if (typeof $().select2 === 'function') {
            // Destroy existing select2 instances
            if ($(select).hasClass('select2-hidden-accessible')) {
                $(select).select2('destroy');
            }
            if ($(editSelect).hasClass('select2-hidden-accessible')) {
                $(editSelect).select2('destroy');
            }
            
            // Reinitialize select2 with proper configuration
            $(select).select2({
                dropdownParent: $('#addPurchaseModal'),
                width: '100%',
                placeholder: 'شوێن هەڵبژێرە',
                dir: 'rtl',
                allowClear: true
            });
            
            const editPurchaseParent = ($('#editPurchasePanel').length ? $('#editPurchasePanel') : $('#editPurchaseModal'));
            $(editSelect).select2({
                dropdownParent: editPurchaseParent,
                width: '100%',
                placeholder: 'شوێن هەڵبژێرە',
                dir: 'rtl',
                allowClear: true
            });
        }
        
        // گەڕاندنەوەی شوێنەکان لە تەیبڵەکەدا
        if (typeof loadLocations === 'function') {
            loadLocations();
        }
        
        Swal.fire('سەرکەوتوو!', 'شوێن زیادکرا', 'success');
        form.reset();
        var modal = bootstrap.Modal.getInstance(document.getElementById('addLocationModal'));
        modal.hide();
    } else {
        Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
    }
};
