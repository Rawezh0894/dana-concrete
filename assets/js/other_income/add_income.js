document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('addIncomeForm');

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('../process/other_income/add_income.php', {
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
                            // Reset form
                            form.reset();
                            // Close modal
                            const modalEl = document.getElementById('addIncomeModal');
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();

                            // Default date to today
                            document.getElementById('date').valueAsDate = new Date();

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
