let submitting = false;
document.getElementById('addCarForm').onsubmit = async function(e) {
    if (submitting) return false;
    submitting = true;
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    try {
        const res = await fetch('../process/car/add_car.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire('سەرکەوتوو!', 'سەیارە زیادکرا', 'success');
            form.reset();
            var modal = bootstrap.Modal.getInstance(document.getElementById('addCarModal'));
            modal.hide();
            loadCars();
        } else {
            Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
        }
    } catch (err) {
        Swal.fire('هەڵە!', 'هەڵەیەک ڕویدا', 'error');
    }
    submitting = false;
};
