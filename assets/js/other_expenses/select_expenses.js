async function loadOtherExpenses() {
    const monthFilter = document.getElementById('monthFilter');
    const res = await fetch('../process/other_expenses/select_expenses.php');
    const data = await res.json();
    function formatNumber(num) {
        return Number(num).toLocaleString('en-US');
    }
    function formatUSD(num) {
        return num ? `$${formatNumber(num)}` : '$0';
    }
    function formatIQD(num) {
        return num ? `${formatNumber(num)} د.ع` : '0 د.ع';
    }
    // Filter by month
    let filtered = data;
    if (monthFilter && monthFilter.value) {
        const [year, month] = monthFilter.value.split('-');
        filtered = data.filter(row => row.date && row.date.startsWith(`${year}-${month}`));
    }
    // Calculate totals
    let totalCashIqd = 0, totalCashUsd = 0, totalCreditIqd = 0, totalCreditUsd = 0;
    filtered.forEach(row => {
        if (row.payment_type === 'نەقد') {
            totalCashIqd += parseFloat(row.amount_iqd) || 0;
            totalCashUsd += parseFloat(row.amount_usd) || 0;
        }
        if (row.payment_type === 'قەرز') {
            totalCreditIqd += parseFloat(row.amount_iqd) || 0;
            totalCreditUsd += parseFloat(row.amount_usd) || 0;
        }
    });
    // Update cards
    document.getElementById('totalCashIqd').textContent = formatIQD(totalCashIqd);
    document.getElementById('totalCashUsd').textContent = formatUSD(totalCashUsd);
    document.getElementById('totalCreditIqd').textContent = formatIQD(totalCreditIqd);
    document.getElementById('totalCreditUsd').textContent = formatUSD(totalCreditUsd);
    // Table
    const tableData = filtered.map((row, idx) => ({
        '#': idx + 1,
        purpose: row.purpose,
        person_name: row.person_name || '',
        employee_name: row.employee_name || '',
        car_name: row.car_name || '',
        gas_liters: row.gas_liters ? formatNumber(row.gas_liters) : '',
        payment_type: row.payment_type,
        currency_type: row.currency_type,
        invoice_number: row.invoice_number || '',
        amount_iqd: row.amount_iqd ? formatIQD(row.amount_iqd) : '',
        amount_usd: row.amount_usd ? formatUSD(row.amount_usd) : '',
        paid_iqd: row.paid_iqd ? formatIQD(row.paid_iqd) : '',
        paid_usd: row.paid_usd ? formatUSD(row.paid_usd) : '',
        exchange_rate: row.exchange_rate ? formatNumber(row.exchange_rate) : '',
        remaining_iqd: row.remaining_iqd ? formatIQD(row.remaining_iqd) : '',
        remaining_usd: row.remaining_usd ? formatUSD(row.remaining_usd) : '',
        date: row.date,
        actions: `<button class="btn btn-sm btn-danger delete-expense" data-id="${row.id}"><i class="fa fa-trash"></i></button> <button class="btn btn-sm btn-primary edit-expense" data-id="${row.id}"><i class="fa fa-edit"></i></button>`
    }));
    TableController.renderWithPagination('#otherExpensesTable', tableData, [
        '#', 'purpose', 'person_name', 'employee_name', 'car_name', 'gas_liters', 'payment_type', 'currency_type',
        'invoice_number', 'amount_iqd', 'amount_usd', 'paid_iqd', 'paid_usd', 'exchange_rate',
        'remaining_iqd', 'remaining_usd', 'date', 'actions'
    ]);
    // Attach delete event
    setTimeout(() => {
        document.querySelectorAll('.delete-expense').forEach(btn => {
            btn.onclick = function() {
                const id = this.dataset.id;
                if (typeof deleteExpense === 'function') deleteExpense(id);
            };
        });
    }, 0);
    // Attach edit event
    setTimeout(() => {
        document.querySelectorAll('.edit-expense').forEach(btn => {
            btn.onclick = async function() {
                const id = this.dataset.id;
                // Find the row data
                const row = data.find(r => r.id == id);
                if (!row) return;
                // Populate selects
                await populateSelect('../process/other_expenses/select_persons.php', 'edit_person_id', row.person_id);
                await populateSelect('../process/other_expenses/select_employees.php', 'edit_employee_id', row.employee_id);
                await populateSelect('../process/other_expenses/select_cars.php', 'edit_car_id', row.car_id);
                // Populate fields
                document.getElementById('edit_id').value = row.id;
                document.getElementById('edit_purpose').value = row.purpose;
                document.getElementById('edit_payment_type').value = row.payment_type;
                document.getElementById('edit_currency_type').value = row.currency_type;
                document.getElementById('edit_invoice_number').value = row.invoice_number;
                document.getElementById('edit_amount_iqd').value = row.amount_iqd;
                document.getElementById('edit_amount_usd').value = row.amount_usd;
                document.getElementById('edit_paid_iqd').value = row.paid_iqd;
                document.getElementById('edit_paid_usd').value = row.paid_usd;
                document.getElementById('edit_exchange_rate').value = row.exchange_rate;
                document.getElementById('edit_remaining_iqd').value = row.remaining_iqd;
                document.getElementById('edit_remaining_usd').value = row.remaining_usd;
                // Add gas_liters to edit modal if present
                if (document.getElementById('edit_gas_liters')) {
                    document.getElementById('edit_gas_liters').value = row.gas_liters || '';
                }
                document.getElementById('edit_date').value = row.date;
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('editExpenseModal'));
                modal.show();
                if (typeof setupEditExpenseModal === 'function') setupEditExpenseModal();
            };
        });
        // Edit form submit
        const editExpenseForm = document.getElementById('editExpenseForm');
        if (editExpenseForm) {
            editExpenseForm.onsubmit = async function(e) {
                e.preventDefault();
                const id = document.getElementById('edit_id').value;
                const data = Object.fromEntries(new FormData(editExpenseForm).entries());
                await editExpense(id, data);
                const modal = bootstrap.Modal.getInstance(document.getElementById('editExpenseModal'));
                modal.hide();
            };
        }
    }, 0);
}
document.addEventListener('DOMContentLoaded', function() {
    loadOtherExpenses();
    const monthFilter = document.getElementById('monthFilter');
    if (monthFilter) {
        monthFilter.addEventListener('change', loadOtherExpenses);
    }
});
// Helper for populating selects with selected value
async function populateSelect(url, selectId, selectedId) {
    const res = await fetch(url);
    const data = await res.json();
    const select = document.getElementById(selectId);
    select.innerHTML = '<option value="">-- هەلبژێرە --</option>';
    data.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = item.name;
        if (selectedId && String(item.id) === String(selectedId)) opt.selected = true;
        select.appendChild(opt);
    });
}
