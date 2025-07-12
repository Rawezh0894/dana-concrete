async function updateCar(id, oldName) {
    const { value: newName } = await Swal.fire({
        title: 'دەستکاری ناوی سەیارە',
        input: 'text',
        inputLabel: 'ناوی نوێ',
        inputValue: oldName,
        showCancelButton: true,
        confirmButtonText: 'نوێکردنەوە',
        cancelButtonText: 'داخستن',
        inputValidator: (value) => {
            if (!value) {
                return 'تکایە ناوی نوێ بنووسە';
            }
        }
    });
    if (!newName || newName === oldName) return;
    const formData = new FormData();
    formData.append('car_id', id);
    formData.append('car_name', newName);
    const res = await fetch('../process/car/update_car.php', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    if (data.success) {
        Swal.fire('سەرکەوتوو!', 'ناوی سەیارە نوێکرایەوە', 'success');
        loadCars();
    } else {
        Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
    }
}

// Attach to edit buttons if not already
if (typeof attachCarRowEvents === 'function') {
    const oldAttach = attachCarRowEvents;
    window.attachCarRowEvents = function() {
        oldAttach();
        document.querySelectorAll('.edit-car').forEach(btn => {
            btn.onclick = function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                updateCar(id, name);
            };
        });
    }
}

document.getElementById('edit_car_name').value = car.name;
document.getElementById('edit_expense_usd').value = car.expense_usd || 0;
document.getElementById('edit_expense_iqd').value = car.expense_iqd || 0;
