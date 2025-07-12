$(function() {
    function loadAdjustments() {
        const columns = ['#', 'bin_name', 'adjustment', 'reason', 'price_usd', 'price_iqd', 'username', 'created_at', 'actions'];
        TableController.showLoading('#adjustmentsTable', columns);
        $.get('../process/stock_adjustments/select.php', function(res) {
            if (!res.success || !Array.isArray(res.data)) {
                TableController.render('#adjustmentsTable', [], columns);
                return;
            }
            res.data.forEach(row => {
                row.actions = `<button class="btn btn-sm btn-danger delete-adjustment" data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
            });
            TableController.renderWithPagination('#adjustmentsTable', res.data, columns);
        }, 'json');
    }
    loadAdjustments();
    window.loadAdjustments = loadAdjustments;
});
