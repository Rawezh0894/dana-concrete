function deleteIncome(id) {
    Swal.fire({
        title: 'ئایا دڵنیایت؟',
        text: "ئەم کردارە ناگەڕێتەوە!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'بەڵێ، بیسڕەوە!',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id', id);

            fetch('../process/other_income/delete_income.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'سڕایەوە',
                            text: data.msg,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
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
        }
    });
}
