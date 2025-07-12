async function loadDebtPayments() {
    const res = await fetch(`../process/person_other_expenses_profile/select_debt.php?person_id=${PERSON_ID}`);
    const data = await res.json();
    const tableData = data.map((row, idx) => ({
        '#': idx + 1,
        date: row.date || '',
        amount_usd: Number(row.amount_usd || 0).toLocaleString() + ' $',
        amount_iqd: Number(row.amount_iqd || 0).toLocaleString() + ' د.ع',
        note: row.note || '',
        actions: `
            <button class="btn btn-sm btn-warning edit-debt" data-id="${row.id}" data-date="${row.date}" data-amount_usd="${row.amount_usd}" data-amount_iqd="${row.amount_iqd}" data-note="${row.note || ''}"><i class="fa fa-edit"></i></button>
            <button class="btn btn-sm btn-danger delete-debt" data-id="${row.id}"><i class="fa fa-trash"></i></button>
        `
    }));
    TableController.renderWithPagination('#debtTable', tableData, ['#', 'date', 'amount_usd', 'amount_iqd', 'note', 'actions']);
    if (typeof attachEditDebtEvents === 'function') attachEditDebtEvents();
    if (typeof attachDeleteDebtEvents === 'function') attachDeleteDebtEvents();
}

function attachEditDebtEvents() {
    $(document).off('click', '.edit-debt').on('click', '.edit-debt', function() {
        const btn = $(this);
        $('#edit_debt_id').val(btn.data('id'));
        $('#edit_debt_date').val(btn.data('date'));
        $('#edit_debt_amount_usd').val(btn.data('amount_usd'));
        $('#edit_debt_amount_iqd').val(btn.data('amount_iqd'));
        $('#edit_debt_note').val(btn.data('note'));
        $('#editDebtModal').modal('show');
    });
}

document.addEventListener('DOMContentLoaded', loadDebtPayments);

window.loadDebtTable = loadDebtPayments;
