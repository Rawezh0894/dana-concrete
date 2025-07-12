document.addEventListener('click', function(e) {
    if (e.target.classList.contains('edit-return-debt')) {
        const id = e.target.getAttribute('data-id');
        if (!id) return;
        // Load data for modal (you may need to implement this part)
        // ...
        // Show modal for editing
        const modal = new bootstrap.Modal(document.getElementById('editCustomerDebtModal'));
        modal.show();
    }
});

document.getElementById('editCustomerDebtForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const id = document.getElementById('edit_customer_debt_id').value;
    const customer_id = typeof CUSTOMER_ID !== 'undefined' ? CUSTOMER_ID : null;
    const date = document.getElementById('edit_customer_debt_date').value;
    const dolar_rate = parseFloat(document.getElementById('edit_customer_debt_dolar_rate').value) || 0;
    const paid_usd = parseFloat(document.getElementById('edit_customer_debt_paid_usd').value) || 0;
    const paid_iqd = parseFloat(document.getElementById('edit_customer_debt_paid_iqd').value) || 0;
    const discount = parseFloat(document.getElementById('edit_customer_debt_discount').value) || 0;
    const note = document.getElementById('edit_customer_debt_note').value;

    if (!id || !customer_id || !date || (paid_usd <= 0 && paid_iqd <= 0 && discount <= 0)) {
        Swal.fire('هەڵە', 'هەموو خانەکان پڕ بکە!', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('id', id);
    formData.append('customer_id', customer_id);
    formData.append('date', date);
    formData.append('dolar_rate', dolar_rate);
    formData.append('paid_usd', paid_usd);
    formData.append('paid_iqd', paid_iqd);
    formData.append('discount', discount);
    formData.append('note', note);

    const res = await fetch('../process/customer_profile/update_return_debt.php', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    if (data.success) {
        Swal.fire('سەرکەوتوو', data.msg, 'success');
        // Close modal and reload debts
        const modal = bootstrap.Modal.getInstance(document.getElementById('editCustomerDebtModal'));
        if (modal) modal.hide();
        if (typeof loadCustomerReturnDebts === 'function' && typeof CUSTOMER_ID !== 'undefined') {
            loadCustomerReturnDebts(CUSTOMER_ID);
        }
        // نوێکردنەوەی قەرزی ماوە
        if (typeof fetchCustomerDebt === 'function' && typeof CUSTOMER_ID !== 'undefined') {
            fetchCustomerDebt(CUSTOMER_ID);
        }
    } else {
        Swal.fire('هەڵە', data.msg || 'هەڵەیەک ڕووی دا', 'error');
    }
});
