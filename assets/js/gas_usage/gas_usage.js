$(document).ready(function() {
    loadGasData();
    loadCars();
    getCurrentGasPrice();

    // Refresh current gas price when modal opens
    $('#addGasModal').on('show.bs.modal', function() {
        getCurrentGasPrice();
    });

    // Auto-calculate total cost
    $('#modal_gas_liters').on('input', function() {
        calculateTotalCost();
    });

    // Handle Form Submission
    $('#addGasForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: '../process/gas_usage/add.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تۆمارکرا',
                        text: 'بەکارهێنانی گاز بە سەرکەوتوویی تۆمارکرا',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#addGasModal').modal('hide');
                    $('#addGasForm')[0].reset();
                    loadGasData();
                } else {
                    Swal.fire('هەڵە', response.msg, 'error');
                }
            },
            error: function() {
                Swal.fire('هەڵە', 'هەڵەیەک لە سێرڤەر ڕوویدا', 'error');
            }
        });
    });

    // Filter Button
    $('#btnFilter').click(function() {
        loadGasData();
    });
});

function loadGasData() {
    const filters = {
        dateFrom: $('#filterDateFrom').val(),
        dateTo: $('#filterDateTo').val(),
        car_id: $('#filterCar').val()
    };

    $.ajax({
        url: '../process/gas_usage/select.php',
        type: 'GET',
        data: filters,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                gridOptions.api.setRowData(response.data);
                
                // Update stats
                $('#totalGasLiters').text(parseFloat(response.summary.total_liters).toLocaleString());
                $('#totalGasCost').text(parseFloat(response.summary.total_cost).toLocaleString());
            }
        }
    });
}

function loadCars() {
    $.ajax({
        url: '../process/other_expenses/get_cars.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">هەڵبژێرە...</option>';
                let filterOptions = '<option value="">هەموو سەیارەکان</option>';
                
                response.data.forEach(car => {
                    options += `<option value="${car.id}">${car.name}</option>`;
                    filterOptions += `<option value="${car.id}">${car.name}</option>`;
                });
                
                $('#modal_car_id').html(options);
                $('#filterCar').html(filterOptions);
            }
        }
    });
}

function getCurrentGasPrice() {
    $.ajax({
        url: '../process/gas_usage/get_current_price.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#currentGasPrice').text(parseFloat(response.price).toLocaleString() + ' د.ع');
                $('#modal_gas_price').val(response.price);
                calculateTotalCost();
            } else {
                $('#currentGasPrice').text('0 د.ع');
                Swal.fire('ئاگاداری', response.msg, 'warning');
            }
        }
    });
}

function calculateTotalCost() {
    const liters = parseFloat($('#modal_gas_liters').val()) || 0;
    const price = parseFloat($('#modal_gas_price').val()) || 0;
    const total = liters * price;
    
    $('#modal_total_cost_display').val(total.toLocaleString() + ' د.ع');
    $('#modal_gas_total_cost').val(total);
}

function deleteGasRecord(id) {
    Swal.fire({
        title: 'ئایا دڵنیایت؟',
        text: "دەتەوێت ئەم تۆمارە بسڕیتەوە؟",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، بسڕەوە',
        cancelButtonText: 'پاشگەزبوونەوە'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../process/gas_usage/delete.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('سڕایەوە', 'تۆمارەکە بە سەرکەوتوویی سڕایەوە', 'success');
                        loadGasData();
                    } else {
                        Swal.fire('هەڵە', response.msg, 'error');
                    }
                }
            });
        }
    });
}
