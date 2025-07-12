async function deleteExpense(id) {
    const result = await Swal.fire({
        title: 'دڵنیایت؟',
        text: 'دەتەوێت ئەم خەرجیە بسڕیتەوە؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'بەڵێ، بسڕەوە',
        cancelButtonText: 'داخستن',
        reverseButtons: true
    });
    if (!result.isConfirmed) return;
    const formData = new FormData();
    formData.append('id', id);
    const res = await fetch('../process/other_expenses/delete_expenses.php', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    if (data.success) {
        Swal.fire('سەرکەوتوو!', 'خەرجیەکە سڕایەوە', 'success');
        if (typeof loadOtherExpenses === 'function') loadOtherExpenses();
    } else {
        Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
    }
}
