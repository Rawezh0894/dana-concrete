function openEditIncomeModal(id, description, amount_iqd, amount_usd, currency, date) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_currency').value = currency;
    document.getElementById('edit_date').value = date;

    // Set amount based on currency
    const amount = currency === 'دینار' ? amount_iqd : amount_usd;
    document.getElementById('edit_amount').value = amount;

    // Update currency label
    const addon = document.getElementById('edit-currency-addon');
    if (currency === 'دینار') {
        addon.textContent = 'د.ع';
    } else {
        addon.textContent = '$';
    }

    const editModal = new bootstrap.Modal(document.getElementById('editIncomeModal'));
    editModal.show();
}

document.addEventListener('DOMContentLoaded', function () {
    const editForm = document.getElementById('editIncomeForm');

    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('../process/other_income/update_income.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو',
                            text: data.msg,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            // Close modal
                            const modalEl = document.getElementById('editIncomeModal');
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();

                            // Refresh grid
                            if (typeof refreshIncomeGrid === 'function') {
                                refreshIncomeGrid();
                            } else {
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: data.msg
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'هەڵەیەک لە پەیوەندی ڕویدا'
                    });
                });
        });
    }
});
