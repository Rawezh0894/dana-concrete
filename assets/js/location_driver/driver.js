document.getElementById('addDriverForm').onsubmit = async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const res = await fetch('../process/location_driver/add_driver.php', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    if (data.success) {
        // زیادکردنی option ـی نوێ بۆ select
        const select = document.getElementById('driver_id');
        const opt = document.createElement('option');
        opt.value = data.id;
        opt.textContent = data.name;
        select.appendChild(opt);
        select.value = data.id;
        Swal.fire('سەرکەوتوو!', 'شۆفێر زیادکرا', 'success');
        form.reset();
        var modal = bootstrap.Modal.getInstance(document.getElementById('addDriverModal'));
        modal.hide();
    } else {
        Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
    }
};
