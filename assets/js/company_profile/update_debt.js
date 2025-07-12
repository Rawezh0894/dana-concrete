$(document).on('click', '.edit-debt', function() {
    const id = $(this).data('id');
    // Fetch current debt info
    $.get('../process/company_profile/select_debt.php', { company_id: COMPANY_ID }, function(data) {
        const debt = (data || []).find(d => d.id == id);
        if (!debt) return;
        $('#edit_debt_id').val(debt.id);
        $('#edit_debt_date').val(debt.date);
        $('#edit_debt_amount_usd').val(debt.amount_usd || 0);
        $('#edit_debt_amount_iqd').val(debt.amount_iqd || 0);
        $('#edit_debt_dollar_rate').val(debt.dollar_rate || 0);
        $('#edit_debt_note').val(debt.note || '');
        var modal = new bootstrap.Modal(document.getElementById('editDebtModal'));
        modal.show();
    }, 'json');
});

$('#editDebtForm').on('submit', function(e) {
    e.preventDefault();
    const form = this;
    const amount_usd = parseFloat(form.amount_usd.value) || 0;
    const amount_iqd = parseFloat(form.amount_iqd.value) || 0;
    if (amount_usd <= 0 && amount_iqd <= 0) {
        Swal.fire('هەڵە!', 'بە لایەنی کەم یەک بڕ پڕبکە (دۆلار یان دینار)', 'error');
        return;
    }
    $.post('../process/company_profile/update_debt.php', $(form).serialize(), function(res) {
        if (res.success) {
            Swal.fire('سەرکەوتوو!', 'دانەوەی قەرز نوێکرایەوە', 'success');
            var modal = bootstrap.Modal.getInstance(document.getElementById('editDebtModal'));
            modal.hide();
            if (typeof loadDebts === 'function') loadDebts();
            if (typeof loadPurchases === 'function') loadPurchases();
            if (typeof loadCompanyStats === 'function') loadCompanyStats();
        } else {
            Swal.fire('هەڵە!', res.msg || 'هەڵەیەک ڕویدا', 'error');
        }
    }, 'json');
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.edit-debt-btn')) {
        const btn = e.target.closest('.edit-debt-btn');
        $('#edit_debt_id').val(btn.getAttribute('data-id'));
        $('#edit_debt_date').val(btn.getAttribute('data-date'));
        $('#edit_debt_amount_usd').val(btn.getAttribute('data-amount_usd'));
        $('#edit_debt_amount_iqd').val(btn.getAttribute('data-amount_iqd'));
        $('#edit_debt_dollar_rate').val(btn.getAttribute('data-dollar_rate'));
        $('#edit_debt_note').val(btn.getAttribute('data-note'));
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editDebtModal'));
        modal.show();
    }
});
