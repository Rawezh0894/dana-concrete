async function loadOtherExpenses() {
    const res = await fetch(`../process/person_other_expenses_profile/select_other_expenses.php?person_id=${PERSON_ID}`);
    const data = await res.json();
    const tableData = data.map((row, idx) => ({
        '#': idx + 1,
        purpose: row.purpose || '',
        employee_name: row.employee_name || '',
        car_name: row.car_name || '',
        payment_type: row.payment_type || '',
        currency_type: row.currency_type || '',
        invoice_number: row.invoice_number || '',
        amount_iqd: Number(row.amount_iqd || 0).toLocaleString() + ' د.ع',
        amount_usd: Number(row.amount_usd || 0).toLocaleString() + ' $',
        paid_iqd: Number(row.paid_iqd || 0).toLocaleString() + ' د.ع',
        paid_usd: Number(row.paid_usd || 0).toLocaleString() + ' $',
        exchange_rate: row.exchange_rate || '',
        remaining_iqd: Number(row.remaining_iqd || 0).toLocaleString() + ' د.ع',
        remaining_usd: Number(row.remaining_usd || 0).toLocaleString() + ' $',
        date: row.date || ''
    }));
    TableController.renderWithPagination('#expensesTable', tableData, ['#', 'purpose', 'employee_name', 'car_name', 'payment_type', 'currency_type', 'invoice_number', 'amount_iqd', 'amount_usd', 'paid_iqd', 'paid_usd', 'exchange_rate', 'remaining_iqd', 'remaining_usd', 'date']);
}
document.addEventListener('DOMContentLoaded', loadOtherExpenses);
