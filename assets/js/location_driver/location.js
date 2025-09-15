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
        
        // گەڕاندنەوەی select2
        if (typeof $().select2 === 'function') {
            $(select).select2('destroy').select2({
                theme: 'bootstrap-5',
                placeholder: 'شوێن هەڵبژێرە',
                allowClear: true
            });
            $(editSelect).select2('destroy').select2({
                theme: 'bootstrap-5',
                placeholder: 'شوێن هەڵبژێرە',
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
