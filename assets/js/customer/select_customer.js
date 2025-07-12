async function loadCustomers() {
    const res = await fetch('../process/customer/select_customer.php');
    const data = await res.json();
    const tableData = data.map((row, idx) => ({
        '#': idx + 1,
        name: row.name || '',
        mobile1: row.mobile1 || '',
        mobile2: row.mobile2 || '',
        opening_debt_usd: row.opening_debt_usd || 0,
        opening_debt_iqd: row.opening_debt_iqd || 0,
        actions: `
            <button class="btn btn-sm btn-primary edit-customer-btn" data-id="${row.id}"><i class="fa fa-edit"></i></button>
            <button class="btn btn-sm btn-danger delete-customer-btn" data-id="${row.id}"><i class="fa fa-trash"></i></button>
            <button class="btn btn-sm btn-info person-customer-btn" data-id="${row.id}"><i class="fa fa-user"></i></button>
        `
    }));
    TableController.renderWithPagination(
        '#customerTable',
        tableData,
        ['#', 'name', 'mobile1', 'mobile2', 'opening_debt_usd', 'opening_debt_iqd', 'actions']
    );
}
document.addEventListener('DOMContentLoaded', () => {
    loadCustomers();
    // Delegate click for person-customer-btn
    document.querySelector('#customerTable').addEventListener('click', function(e) {
        if (e.target.closest('.person-customer-btn')) {
            const btn = e.target.closest('.person-customer-btn');
            const id = btn.getAttribute('data-id');
            window.location.href = `customer_profile.php?id=${id}`;
        }
    });
});
