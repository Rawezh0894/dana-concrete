$(document).ready(function () {
    let currentMaterial = {};

    // Open Modal
    $(document).on('click', '.sell-btn', function () {
        const btn = $(this);
        currentMaterial = {
            id: btn.data('id'),
            name: btn.data('name'),
            unit_type: btn.data('unit_type'),
            quantity: parseFloat(btn.data('quantity')),
            currency_type: btn.data('currency_type'),

            // Conversion factors
            pieces_per_carton: parseFloat(btn.data('pieces_per_carton')) || 1,
            buckets_per_barrel: parseFloat(btn.data('buckets_per_barrel')) || 1,
            liters_per_bucket: parseFloat(btn.data('liters_per_bucket')) || 1,
            liters_per_barrel: parseFloat(btn.data('liters_per_barrel')) || 1,

            // Prices
            purchase_price_usd: parseFloat(btn.data('purchase_price_usd')) || 0,
            purchase_price_iqd: parseFloat(btn.data('purchase_price_iqd')) || 0,
            price_per_piece_usd: parseFloat(btn.data('price_per_piece_usd')) || 0,
            price_per_piece_iqd: parseFloat(btn.data('price_per_piece_iqd')) || 0,
            price_per_bucket_usd: parseFloat(btn.data('price_per_bucket_usd')) || 0,
            price_per_bucket_iqd: parseFloat(btn.data('price_per_bucket_iqd')) || 0,
            price_per_liter_usd: parseFloat(btn.data('price_per_liter_usd')) || 0,
            price_per_liter_iqd: parseFloat(btn.data('price_per_liter_iqd')) || 0
        };

        $('#sell_material_id').val(currentMaterial.id);
        $('#sell_material_info').html(`
            <strong>کاڵا:</strong> ${currentMaterial.name} <br>
            <strong>بەردەست:</strong> ${currentMaterial.quantity} ${currentMaterial.unit_type}
        `);

        // Populate Units
        const unitSelect = $('#sell_unit_type');
        unitSelect.empty();
        unitSelect.append(`<option value="${currentMaterial.unit_type}">${currentMaterial.unit_type}</option>`);

        if (currentMaterial.unit_type === 'کارتۆن') {
            unitSelect.append(`<option value="دانە">دانە</option>`);
        } else if (currentMaterial.unit_type === 'بەرمیل') {
            unitSelect.append(`<option value="دەبە">دەبە</option>`);
            unitSelect.append(`<option value="لیتر">لیتر</option>`);
        } else if (currentMaterial.unit_type === 'دەبە') {
            unitSelect.append(`<option value="لیتر">لیتر</option>`);
        }

        // Set default currency
        $('#sell_currency').val(currentMaterial.currency_type === 'دۆلار' ? 'USD' : 'IQD');

        // Reset form fields
        $('#buyer_type').val('');
        $('#sell_quantity').val('');
        $('#sell_note').val('');
        $('#customer_select_group, #company_select_group, #outsider_name_group').hide();
        $('#sell_customer_id, #sell_company_id').val('');
        $('#outsider_name').val('');

        updatePrice();
        $('#sellMaterialModal').modal('show');
    });

    // Handle Buyer Type
    $('#buyer_type').on('change', function () {
        const type = $(this).val();
        $('#customer_select_group, #company_select_group, #outsider_name_group').hide();

        if (type === 'customer') {
            $('#customer_select_group').show();
            if ($('#sell_customer_id option').length <= 1) {
                // Fetch customers if not loaded
                $.get('../process/customer/select_customer.php', function (data) {
                    $('#sell_customer_id').append(data);
                });
            }
        } else if (type === 'company') {
            $('#company_select_group').show();
            if ($('#sell_company_id option').length <= 1) {
                // Fetch companies
                $.get('../process/company/select_company.php', function (data) {
                    $('#sell_company_id').append(data);
                });
            }
        } else if (type === 'outsider') {
            $('#outsider_name_group').show();
        }
    });

    // Update Price on Unit or Currency changes
    $('#sell_unit_type, #sell_currency').on('change', updatePrice);

    // Calc Total Price on Quantity or Price change
    $('#sell_quantity, #sell_price_per_unit').on('input', function () {
        const qty = parseFloat($('#sell_quantity').val()) || 0;
        const price = parseFloat($('#sell_price_per_unit').val()) || 0;
        $('#sell_total_price').val((qty * price).toFixed(2));
        validateStock();
    });

    function updatePrice() {
        const unit = $('#sell_unit_type').val();
        const currency = $('#sell_currency').val(); // USD or IQD
        let price = 0;

        if (unit === currentMaterial.unit_type) { // Base unit
            price = currency === 'USD' ? currentMaterial.purchase_price_usd : currentMaterial.purchase_price_iqd;
        } else if (unit === 'دانە') {
            price = currency === 'USD' ? currentMaterial.price_per_piece_usd : currentMaterial.price_per_piece_iqd;
        } else if (unit === 'دەبە') {
            price = currency === 'USD' ? currentMaterial.price_per_bucket_usd : currentMaterial.price_per_bucket_iqd;
        } else if (unit === 'لیتر') {
            price = currency === 'USD' ? currentMaterial.price_per_liter_usd : currentMaterial.price_per_liter_iqd;
        }

        $('#sell_price_per_unit').val(price || 0);
        $('#sell_quantity').trigger('input'); // Recalc total
    }

    function validateStock() {
        const sellQty = parseFloat($('#sell_quantity').val()) || 0;
        const sellUnit = $('#sell_unit_type').val();

        let requiredQtyBase = sellQty;

        // Convert to base unit to compare with stock
        if (currentMaterial.unit_type === 'کارتۆن') {
            if (sellUnit === 'دانە') requiredQtyBase = sellQty / currentMaterial.pieces_per_carton;
        } else if (currentMaterial.unit_type === 'بەرمیل') {
            if (sellUnit === 'دەبە') requiredQtyBase = sellQty / currentMaterial.buckets_per_barrel;
            else if (sellUnit === 'لیتر') requiredQtyBase = sellQty / currentMaterial.liters_per_barrel;
        } else if (currentMaterial.unit_type === 'دەبە') {
            if (sellUnit === 'لیتر') requiredQtyBase = sellQty / currentMaterial.liters_per_bucket;
        }

        if (requiredQtyBase > currentMaterial.quantity) {
            $('#stock_error').show();
            return false;
        } else {
            $('#stock_error').hide();
            return true;
        }
    }

    // Form Submission
    $('#sellMaterialForm').on('submit', function (e) {
        e.preventDefault();

        if (!validateStock()) {
            Swal.fire('هەڵە', 'بڕی پێویست بەردەست نییە', 'error');
            return;
        }

        const formData = $(this).serialize();

        $.ajax({
            url: '../process/add_material/sell.php', // We will create this
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $('#sellMaterialModal').modal('hide');
                    Swal.fire('سەرکەوتوو', 'فرۆشتنەکە بە سەرکەوتوویی کرا', 'success');
                    // Reload table
                    if (typeof loadMaterialsTable === 'function') loadMaterialsTable();
                } else {
                    Swal.fire('هەڵە', response.message, 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error(error);
                Swal.fire('هەڵە', 'هەڵەیەک لە پەیوەندیکردن هەیە', 'error');
            }
        });
    });
});
