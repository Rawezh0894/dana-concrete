$(document).ready(function () {
    // Initialize DataTable
    var table = $('#salesTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/km.json",
            "search": "گەڕان:",
            "lengthMenu": "پیشاندانی _MENU_ دانە",
            "info": "پیشاندانی _START_ بۆ _END_ لە _TOTAL_ دانە",
            "paginate": {
                "first": "سەرەتا",
                "last": "کۆتا",
                "next": "دواتر",
                "previous": "پێشوو"
            },
            "zeroRecords": "هیچ تۆمارێک نەدۆزرایەوە",
            "infoEmpty": "هیچ تۆمارێک بەردەست نییە",
            "infoFiltered": "(پاڵێوراو لە _MAX_ تۆمار)"
        },
        "ajax": {
            "url": "../process/add_material/get_sales.php",
            "data": function (d) {
                d.from_date = $('#filterFrom').val();
                d.to_date = $('#filterTo').val();
                d.material_id = $('#filterMaterial').val();
                d.buyer_type = $('#filterBuyerType').val();
            }
        },
        "columns": [
            { "data": "id" },
            { "data": "material_name" },
            {
                "data": null,
                "render": function (data, type, row) {
                    let typeLabel = '';
                    if (row.buyer_type === 'customer') typeLabel = '<span class="badge bg-primary">کڕیار</span>';
                    else if (row.buyer_type === 'company') typeLabel = '<span class="badge bg-success">کۆمپانیا</span>';
                    else typeLabel = '<span class="badge bg-secondary">دەرەوە</span>';

                    return `${typeLabel} ${row.buyer_name}`;
                }
            },
            { "data": "quantity" },
            { "data": "unit" },
            { "data": "price" },
            { "data": "total_price" },
            { "data": "currency" },
            { "data": "date" },
            { "data": "note" },
            {
                "data": null,
                "render": function (data, type, row) {
                    // Stringify row data for data attribute (be careful with quotes)
                    const rowData = encodeURIComponent(JSON.stringify(row));

                    return `
                        <button class="btn btn-sm btn-primary edit-btn" data-json="${rowData}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    `;
                }
            }
        ],
        "footerCallback": function (row, data, start, end, display) {
            var api = this.api();

            // Total calculation
            var total = api.column(6).data().reduce(function (a, b) {
                return parseFloat(a) + parseFloat(b);
            }, 0);

            $(api.column(6).footer()).html(total.toLocaleString());
        }
    });

    // Filter Actions
    $('#applyFilters').on('click', function () {
        table.ajax.reload();
    });

    $('#clearFilters').on('click', function () {
        $('#filterFrom').val('');
        $('#filterTo').val('');
        $('#filterMaterial').val('');
        $('#filterBuyerType').val('');
        table.ajax.reload();
    });

    // Delete Action
    $(document).on('click', '.delete-btn', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'دڵنیایت لە سڕینەوە؟',
            text: "ئەم کردارە کاڵاکە دەگەڕێنێتەوە کۆگا!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'بەڵێ، بیسڕەوە!',
            cancelButtonText: 'نەخێر'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../process/add_material/delete_sale.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('سڕایەوە!', 'فرۆشتنەکە سڕایەوە و کاڵا گەڕایەوە کۆگا.', 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('هەڵە', response.message, 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('هەڵە', 'سوێرڤەر کێشەی هەیە', 'error');
                    }
                });
            }
        });
    });

    // Edit Action - Open Modal
    let currentEditMaterial = {}; // To store conversion factors

    $(document).on('click', '.edit-btn', function () {
        const rowJson = decodeURIComponent($(this).data('json'));
        const data = JSON.parse(rowJson);

        currentEditMaterial = {
            base_unit: data.base_unit,
            pieces_per_carton: parseFloat(data.pieces_per_carton) || 1,
            buckets_per_barrel: parseFloat(data.buckets_per_barrel) || 1,
            liters_per_bucket: parseFloat(data.liters_per_bucket) || 1,
            liters_per_barrel: parseFloat(data.liters_per_barrel) || 1
        };

        // Populate Fields
        $('#edit_sale_id').val(data.id);
        $('#edit_material_id').val(data.material_id);
        $('#edit_old_quantity').val(data.quantity);
        $('#edit_old_unit').val(data.unit);
        $('#edit_material_name').val(data.material_name);
        $('#edit_date').val(data.date);
        $('#edit_quantity').val(data.quantity);
        $('#edit_price').val(data.price);
        $('#edit_total_price').val(data.total_price);
        $('#edit_currency').val(data.currency);
        $('#edit_note').val(data.note);

        // Populate Units based on material type
        const unitSelect = $('#edit_unit');
        unitSelect.empty();
        unitSelect.append(`<option value="${data.base_unit}">${data.base_unit}</option>`);

        if (data.base_unit === 'کارتۆن') {
            unitSelect.append(`<option value="دانە">دانە</option>`);
        } else if (data.base_unit === 'بەرمیل') {
            unitSelect.append(`<option value="دەبە">دەبە</option>`);
            unitSelect.append(`<option value="لیتر">لیتر</option>`);
        } else if (data.base_unit === 'دەبە') {
            unitSelect.append(`<option value="لیتر">لیتر</option>`);
        }

        unitSelect.val(data.unit);

        $('#editSaleModal').modal('show');
    });

    // Recalculate Total on Edit
    $('#edit_quantity, #edit_price').on('input', function () {
        const qty = parseFloat($('#edit_quantity').val()) || 0;
        const price = parseFloat($('#edit_price').val()) || 0;
        $('#edit_total_price').val((qty * price).toFixed(2));
    });

    // Submit Edit
    $('#editSaleForm').on('submit', function (e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.ajax({
            url: '../process/add_material/update_sale.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    $('#editSaleModal').modal('hide');
                    Swal.fire('سەرکەوتوو', 'فرۆشتنەکە نوێکرایەوە', 'success');
                    table.ajax.reload();
                } else {
                    Swal.fire('هەڵە', response.message, 'error');
                }
            },
            error: function () {
                Swal.fire('هەڵە', 'سوێرڤەر کێشەی هەیە', 'error');
            }
        });
    });
});
