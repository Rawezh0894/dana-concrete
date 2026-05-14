$(function () {
    function formatMoney(val) {
        return Number(val).toLocaleString('en-US') + ' د.ع';
    }

    function formatPayCell(row) {
        var parts = [];
        parts.push('<span class="fw-semibold">' + Number(row.amount || 0).toLocaleString('en-US') + ' د.ع</span> <span class="text-muted small">(حساب)</span>');
        var u = parseFloat(row.amount_usd) || 0;
        var iq = parseFloat(row.amount_iqd) || 0;
        var r = parseFloat(row.exchange_rate) || 0;
        if (u > 0) {
            parts.push('<span class="text-primary"><i class="fas fa-dollar-sign me-1"></i>' + u.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</span>');
        }
        if (iq > 0) {
            parts.push('<span class="text-success">' + Number(iq).toLocaleString('en-US') + ' د.ع</span> <small class="text-muted">قاسە</small>');
        }
        if (r > 0 && u > 0) {
            parts.push('<small class="text-muted">نرخ: ' + Number(r).toLocaleString('en-US') + ' د.ع/\$</small>');
        }
        if (u <= 0 && iq <= 0) {
            parts.push('<small class="text-muted">قاسە: ' + Number(row.amount || 0).toLocaleString('en-US') + ' د.ع (دینار)</small>');
        }
        return '<div class="small text-start" style="min-width:140px;">' + parts.join('<br>') + '</div>';
    }

    function loadExpenses() {
        const columns = ['#', 'employee_name', 'expense_type_kurdish', 'amount', 'expense_date', 'notes', 'employee_balance', 'created_at', 'actions'];
        TableController.showLoading('#employeeExpensesTable', columns);

        // Get filter values
        const monthFilter = $('#month-filter').val();
        const employeeFilter = $('#employee-filter').val();
        const startDate = $('#start-date').val();
        const endDate = $('#end-date').val();

        // Build query parameters
        const params = new URLSearchParams();
        if (monthFilter) params.append('month', monthFilter);
        if (employeeFilter) params.append('employee', employeeFilter);
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);

        const url = '../process/employee_payments/select_expenses.php' + (params.toString() ? '?' + params.toString() : '');

        $.get(url, function (res) {
            if (!res || !Array.isArray(res)) {
                TableController.render('#employeeExpensesTable', [], columns);
                return;
            }

            res.forEach((row, index) => {
                row['#'] = index + 1;
                row.amount = formatPayCell(row);

                // Format employee balance
                const payable = parseFloat(row.employee_payable_balance || 0);
                const receivable = parseFloat(row.employee_receivable_balance || 0);
                const netBalance = payable - receivable;

                let balanceHtml = '';
                if (payable > 0 || receivable > 0) {
                    balanceHtml = `
                        <div class="small">
                            <div class="text-success">
                                <i class="fas fa-arrow-up"></i> قەرزی کۆمپانیا: ${formatMoney(payable)}
                            </div>
                            <div class="text-danger">
                                <i class="fas fa-arrow-down"></i> قەرزی کارمەند: ${formatMoney(receivable)}
                            </div>
                            <div class="fw-bold mt-1 ${netBalance >= 0 ? 'text-success' : 'text-danger'}">
                                <i class="fas fa-balance-scale"></i> باڵانسی خالص: ${formatMoney(Math.abs(netBalance))}
                                ${netBalance >= 0 ? '(کۆمپانیا قەرزی کارمەندە)' : '(کارمەند قەرزی کۆمپانیایە)'}
                            </div>
                        </div>
                    `;
                } else {
                    balanceHtml = '<span class="text-muted">0 د.ع</span>';
                }
                row.employee_balance = balanceHtml;

                row.actions = `
                    <button class="btn btn-sm btn-primary update-expense me-1" data-id="${row.id}" title="نوێکردنەوە">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-expense" data-id="${row.id}" title="سڕینەوە">
                        <i class="fa fa-trash"></i>
                    </button>
                `;
            });

            TableController.renderWithPagination('#employeeExpensesTable', res, columns);
        }, 'json').fail(function (xhr) {
            console.error('Error loading expenses:', xhr.responseText);
            TableController.render('#employeeExpensesTable', [], columns);
        });
    }

    loadExpenses();
    window.loadExpenses = loadExpenses;

    // Reload when filters change (handle both regular select and Select2)
    $('#month-filter').on('change', function () {
        loadExpenses();
    });

    // Handle Select2 change event for employee filter
    $(document).on('change', '#employee-filter', function () {
        loadExpenses();
    });

    // Make loadExpenses available globally
    window.loadExpenses = loadExpenses;
});

