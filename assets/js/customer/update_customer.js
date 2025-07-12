// Handle edit button click for customers
document.addEventListener('click', function(e) {
    if (e.target.closest('.edit-customer-btn')) {
        const btn = e.target.closest('.edit-customer-btn');
        const id = btn.getAttribute('data-id');
        fetch(`../process/customer/select_customer.php?id=${id}`)
            .then(res => res.json())
            .then(row => {
                var modal = new bootstrap.Modal(document.getElementById('editCustomerModal'));
                modal.show();
                document.getElementById('editCustomerId').value = row.id;
                document.getElementById('editCustomerName').value = row.name || '';
                document.getElementById('editCustomerMobile1').value = row.mobile1 || '';
                document.getElementById('editCustomerMobile2').value = row.mobile2 || '';
                document.getElementById('editCustomerOpeningDebtUsd').value = row.opening_debt_usd || 0;
                document.getElementById('editCustomerOpeningDebtIqd').value = row.opening_debt_iqd || 0;
            });
    }
});

// Handle update submit
const editCustomerForm = document.getElementById('editCustomerForm');
if (editCustomerForm) {
    editCustomerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(editCustomerForm);
        fetch('../process/customer/update_customer.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('نوێکرایەوە!', data.message || 'کڕیار نوێکرایەوە', 'success');
                editCustomerForm.reset();
                if (typeof loadCustomers === 'function') loadCustomers();
                const modal = bootstrap.Modal.getInstance(document.getElementById('editCustomerModal'));
                if (modal) modal.hide();
            } else {
                Swal.fire('هەڵە!', data.message || 'هەڵەیەک ڕووی دا', 'error');
            }
        })
        .catch(() => {
            Swal.fire('هەڵە!', 'هەڵەیەک ڕووی دا', 'error');
        });
    });
}

// Handle delete button click for customers
