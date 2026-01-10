async function deleteExpense(id) {
    try {
        console.log('Deleting expense...', { id });
        const result = await Swal.fire({
            title: 'دڵنیایت؟',
            text: 'دەتەوێت ئەم خەرجیە بسڕیتەوە؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بەڵێ، بسڕەوە',
            cancelButtonText: 'داخستن',
            reverseButtons: true
        });
        if (!result.isConfirmed) {
            console.log('Delete cancelled by user');
            return;
        }

        const formData = new FormData();
        formData.append('id', id);

        const res = await fetch('../process/other_expenses/delete_expenses.php', {
            method: 'POST',
            body: formData
        });

        console.log('Delete response status:', res.status, res.statusText);

        const rawText = await res.text();
        let data;
        try {
            data = rawText ? JSON.parse(rawText) : {};
        } catch (parseError) {
            console.error('Failed to parse delete response as JSON. Raw response:', rawText);
            throw new Error('Invalid JSON from delete_expenses.php');
        }

        console.log('Delete response JSON:', data);

        if (!res.ok) {
            const msg = data.msg || `HTTP ${res.status}`;
            console.error('Delete request failed', { status: res.status, msg });
            Swal.fire('هەڵە!', msg, 'error');
            return;
        }

        if (data.success) {
            console.log('Expense deleted successfully');
            Swal.fire('سەرکەوتوو!', 'خەرجیەکە سڕایەوە', 'success');
            if (typeof reloadOtherExpenses === 'function') {
                reloadOtherExpenses();
            } else if (typeof loadOtherExpenses === 'function') {
                loadOtherExpenses();
            }
        } else {
            console.error('Server returned error for delete:', data.msg);
            Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
        }
    } catch (err) {
        console.error('Error deleting expense:', err);
        console.error('Error details:', {
            message: err.message,
            stack: err.stack,
            expenseId: id
        });
        Swal.fire('هەڵە!', 'هەڵەیەک ڕویدا لە کاتی سڕینەوەدا', 'error');
    }
}

// Expose explicitly for click handlers
window.deleteExpense = deleteExpense;
