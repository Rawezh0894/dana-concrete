async function loadCars() {
    const res = await fetch('../process/car/select_car.php');
    const data = await res.json();
    // Prepare data for TableController
    const tableData = data.map((row, idx) => ({
        '#': idx + 1,
        name: row.name,
        actions: `
            <button class="btn btn-sm btn-warning edit-car" data-id="${row.id}" data-name="${row.name}"><i class="fa fa-edit"></i></button>
            <button class="btn btn-sm btn-danger delete-car" data-id="${row.id}"><i class="fa fa-trash"></i></button>
        `
    }));
    TableController.renderWithPagination('#carTable', tableData, ['#', 'name', 'actions']);
    attachCarRowEvents();
}
document.addEventListener('DOMContentLoaded', loadCars);

function attachCarRowEvents() {
    document.querySelectorAll('.edit-car').forEach(btn => {
        btn.onclick = function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            // Fill modal fields
            document.getElementById('edit_car_id').value = id;
            document.getElementById('edit_car_name').value = name;
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('editCarModal'));
            modal.show();
        };
    });
    document.querySelectorAll('.delete-car').forEach(btn => {
        btn.onclick = function() {
            const id = this.dataset.id;
            if (typeof deleteCar === 'function') deleteCar(id);
        };
    });
}

// Handle edit form submit
const editCarForm = document.getElementById('editCarForm');
if (editCarForm) {
    editCarForm.onsubmit = async function(e) {
        e.preventDefault();
        const formData = new FormData(editCarForm);
        const res = await fetch('../process/car/update_car.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire('سەرکەوتوو!', 'زانیاری سەیارە نوێکرایەوە', 'success');
            var modal = bootstrap.Modal.getInstance(document.getElementById('editCarModal'));
            modal.hide();
            loadCars();
        } else {
            Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
        }
    }
}

async function deleteCar(id) {
    const result = await Swal.fire({
        title: 'دڵنیایت؟',
        text: 'دەتەوێت ئەم سەیارە بسڕیتەوە؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'بەڵێ، بسڕەوە',
        cancelButtonText: 'داخستن',
        reverseButtons: true
    });
    if (!result.isConfirmed) return;
    const formData = new FormData();
    formData.append('car_id', id);
    const res = await fetch('../process/car/delete_car.php', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    if (data.success) {
        Swal.fire('سەرکەوتوو!', 'سەیارە سڕایەوە', 'success');
        loadCars();
    } else {
        Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
    }
}
