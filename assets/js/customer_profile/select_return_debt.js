async function loadCustomerReturnDebts(customerId) {
    const res = await fetch(`../process/customer_profile/select_return_debt.php?customer_id=${customerId}`);
    const data = await res.json();
    const columns = ['#', 'date', 'dolar_rate', 'paid_usd', 'paid_iqd', 'discount', 'note', 'actions'];
    function formatNumber(n) {
        if (n === null || n === undefined || n === '') return '';
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    function formatUSD(n) {
        if (!n || isNaN(n)) return '';
        return formatNumber(Number(n).toFixed(2)) + ' $';
    }
    function formatIQD(n) {
        if (!n || isNaN(n)) return '';
        return formatNumber(Number(n).toFixed(0)) + ' د.ع';
    }
    const rows = (data || []).map((row, idx) => ({
        '#': idx + 1,
        date: row.date || '-',
        dolar_rate: row.dolar_rate !== null && row.dolar_rate !== undefined && row.dolar_rate !== '' ? formatNumber(row.dolar_rate) : '-',
        paid_usd: row.paid_usd !== null && row.paid_usd !== undefined && row.paid_usd !== '' ? formatUSD(row.paid_usd) : '-',
        paid_iqd: row.paid_iqd !== null && row.paid_iqd !== undefined && row.paid_iqd !== '' ? formatIQD(row.paid_iqd) : '-',
        discount: row.discount !== null && row.discount !== undefined && row.discount !== '' ? formatUSD(row.discount) : '-',
        note: row.note || '-',
        actions: `
            <button class="btn btn-sm btn-primary edit-return-debt" data-id="${row.id}"><i class="fa fa-edit"></i></button>
            <button class="btn btn-sm btn-danger delete-return-debt" data-id="${row.id}"><i class="fa fa-trash"></i></button>
        `
    }));
    TableController.renderWithPagination('#customerDebtTable', rows, columns, { pageSize: 10 });
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof CUSTOMER_ID !== 'undefined' && CUSTOMER_ID) {
        loadCustomerReturnDebts(CUSTOMER_ID);
    }
});

document.addEventListener('click', async function(e) {
    if (e.target.classList.contains('edit-return-debt')) {
        const id = e.target.getAttribute('data-id');
        if (!id) return;
        // وەرگرتنی داتای دانەوەی قەرز
        const res = await fetch(`../process/customer_profile/select_return_debt.php?debt_id=${id}`);
        const data = await res.json();
        if (!data || !data.id) {
            console.error('Debt fetch error:', data);
            Swal.fire('هەڵە', 'داتای دانەوە نەدۆزرایەوە!', 'error');
            return;
        }
        // پڕکردنەوەی مۆداڵ
        document.getElementById('edit_customer_debt_id').value = data.id;
        document.getElementById('edit_customer_debt_date').value = data.date || '';
        document.getElementById('edit_customer_debt_dolar_rate').value = data.dolar_rate || '';
        document.getElementById('edit_customer_debt_paid_usd').value = data.paid_usd || '';
        document.getElementById('edit_customer_debt_paid_iqd').value = data.paid_iqd || '';
        document.getElementById('edit_customer_debt_discount').value = data.discount || '';
        document.getElementById('edit_customer_debt_note').value = data.note || '';
        // نیشاندانی مۆداڵ
        const modal = new bootstrap.Modal(document.getElementById('editCustomerDebtModal'));
        modal.show();
    }
});
