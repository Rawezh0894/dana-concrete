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
        calculateTotalCost('modal');
    });

    $('#edit_gas_liters').on('input', function() {
        calculateTotalCost('edit');
    });

    // Handle Form Submission
    $('#addGasForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        
        // Prevent double click
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).prepend('<i class="fas fa-spinner fa-spin me-2"></i>');

        const formData = $form.serialize();
        
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
                    $form[0].reset();
                    loadGasData();
                } else {
                    Swal.fire('هەڵە', response.msg, 'error');
                }
            },
            error: function() {
                Swal.fire('هەڵە', 'هەڵەیەک لە سێرڤەر ڕوویدا', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).find('i.fa-spinner').remove();
            }
        });
    });

    // Handle Edit Form Submission
    $('#editGasForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        
        // Prevent double click
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true).prepend('<i class="fas fa-spinner fa-spin me-2"></i>');

        const formData = $form.serialize();
        
        $.ajax({
            url: '../process/gas_usage/update.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'نوێکرایەوە',
                        text: 'تۆمارەکە بە سەرکەوتوویی نوێکرایەوە',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#editGasModal').modal('hide');
                    loadGasData();
                } else {
                    Swal.fire('هەڵە', response.msg, 'error');
                }
            },
            error: function() {
                Swal.fire('هەڵە', 'هەڵەیەک لە سێرڤەر ڕوویدا', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).find('i.fa-spinner').remove();
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
            if (response.success && gasGridApi) {
                gasGridApi.setGridOption('rowData', response.data);
                
                // Update stats
                $('#totalGasLiters').text(parseFloat(response.summary.total_liters).toLocaleString());
                $('#totalGasCost').text(parseFloat(response.summary.total_cost).toLocaleString());
            }
        }
    });
}

function loadCars() {
    $.ajax({
        url: '../process/other_expenses/select_cars.php',
        type: 'GET',
        dataType: 'json',
    success: function(data) {
        if (Array.isArray(data)) {
            let options = '<option value="">هەڵبژێرە...</option>';
            let filterOptions = '<option value="">هەموو سەیارەکان</option>';
            
            data.forEach(car => {
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
                const priceFormatted = parseFloat(response.price).toLocaleString() + ' د.ع';
                $('#currentGasPrice').text(priceFormatted);
                $('#modal_gas_price').val(response.price);
                $('#edit_gas_price').val(response.price);
                calculateTotalCost('modal');
                calculateTotalCost('edit');
            } else {
                $('#currentGasPrice').text('0 د.ع');
                Swal.fire('ئاگاداری', response.msg, 'warning');
            }
        }
    });
}

window.openEditModal = function(data) {
    $('#edit_id').val(data.id);
    $('#edit_car_id').val(data.car_id);
    $('#edit_gas_liters').val(data.gas_liters);
    $('#edit_gas_price').val(data.gas_purchase_price_input);
    $('#edit_date').val(data.date);
    
    calculateTotalCost('edit');
    $('#editGasModal').modal('show');
};

function calculateTotalCost(type) {
    const prefix = type === 'edit' ? 'edit_' : 'modal_';
    const liters = parseFloat($(`#${prefix}gas_liters`).val()) || 0;
    const price = parseFloat($(`#${prefix}gas_price`).val()) || 0;
    const total = liters * price;
    
    $(`#${prefix}total_cost_display`).val(total.toLocaleString() + ' د.ع');
    $(`#${prefix}gas_total_cost`).val(total);
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
