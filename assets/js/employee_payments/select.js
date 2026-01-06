$(function () {
    function formatMoney(val) {
        return Number(val).toLocaleString('en-US') + ' د.ع';
    }
    function loadPayments() {
        const columns = ['#', 'date', 'employee_name', 'type', 'amount_iqd', 'amount_usd', 'pay_month', 'note', 'created_by_name', 'actions'];
        TableController.showLoading('#hrTransactionsTable', columns);

        const monthFilter = $('#month-filter').val();
        const employeeFilter = $('#employee-filter').val();

        const params = new URLSearchParams();
        if (monthFilter) params.append('month', monthFilter);
        if (employeeFilter) params.append('employee', employeeFilter);

        const url = '../process/hr/select_transactions.php' + (params.toString() ? '?' + params.toString() : '');

        $.get(url, function (res) {
            if (!res || !Array.isArray(res)) {
                TableController.render('#hrTransactionsTable', [], columns);
                return;
            }
            const displayData = res.map(row => {
                const newRow = { ...row };
                newRow.amount_iqd = (parseFloat(row.amount_iqd) || 0).toLocaleString() + ' د.ع';
                newRow.amount_usd = '$' + (parseFloat(row.amount_usd) || 0).toLocaleString();

                // Color formatting for transaction types
                let typeColor = 'text-primary';
                if (row.type.includes('Payment') || row.type.includes('وەصڵ')) typeColor = 'text-success';
                else if (row.type.includes('Bonus') || row.type.includes('پاداشت')) typeColor = 'text-info';
                else if (row.type.includes('Penalty') || row.type.includes('سزا')) typeColor = 'text-danger';
                else if (row.type.includes('Advance') || row.type.includes('پێشەکی')) typeColor = 'text-warning';

                newRow.type = `<span class="fw-bold ${typeColor}">${row.type}</span>`;

                newRow.actions = `
                    <button class="btn btn-sm btn-primary edit-transaction" data-id="${row.id}" data-json='${JSON.stringify(row)}'><i class="fa fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger delete-transaction" data-id="${row.id}"><i class="fa fa-trash"></i></button>
                `;
                return newRow;
            });
            TableController.renderWithPagination('#hrTransactionsTable', displayData, columns);
        }, 'json');
    }

    $(document).on('click', '.edit-transaction', function () {
        const data = JSON.parse($(this).attr('data-json'));
        $('#transaction_id').val(data.id);
        $('#employee_id').val(data.employee_id).trigger('change');
        $('#transaction_type').val(data.type);
        $('#amount_iqd').val(data.amount_iqd || 0);
        $('#amount_usd').val(data.amount_usd || 0);
        $('#pay_month').val(data.pay_month);
        $('#transaction_date').val(data.date);
        $('#note').val(data.note);

        $('#hrTransactionModalLabel').text('دەستکاری مامەڵە');
        $('#hrTransactionModal').modal('show');
    });

    $(document).on('click', '.delete-transaction', function () {
        const id = $(this).attr('data-id');
        Swal.fire({
            title: 'ئایا دڵنیای لە سڕینەوەی ئەم مامەڵەیە؟',
            text: "ئەمە کار دەکتە سەر باڵانسی کارمەندەکە!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'بەڵێ، بیسڕەوە',
            cancelButtonText: 'نەخێر'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../process/hr/delete_transaction.php', { id: id }, function (res) {
                    if (res.success) {
                        swalAlert('سەرکەوتوو', res.msg, 'success');
                        loadPayments();
                    } else {
                        swalAlert('هەڵە', res.msg, 'error');
                    }
                }, 'json');
            }
        });
    });

    loadPayments();
    window.loadPayments = loadPayments;
});
