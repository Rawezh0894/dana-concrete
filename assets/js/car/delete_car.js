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
