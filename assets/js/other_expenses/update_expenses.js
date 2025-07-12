async function editExpense(id, data) {
    const formData = new FormData();
    for (const key in data) {
        formData.append(key, data[key]);
    }
    formData.append('id', id);
    const res = await fetch('../process/other_expenses/update_expenses.php', {
        method: 'POST',
        body: formData
    });
    const result = await res.json();
    if (result.success) {
        Swal.fire('سەرکەوتوو!', 'خەرجیەکە نوێکرایەوە', 'success');
        if (typeof loadOtherExpenses === 'function') loadOtherExpenses();
    } else {
        Swal.fire('هەڵە!', result.msg || 'هەڵەیەک ڕویدا', 'error');
    }
}
window.editExpense = editExpense;

function setupEditExpenseModal() {
    const currencyType = document.getElementById('edit_currency_type');
    const amountIqd = document.getElementById('edit_amount_iqd');
    const amountUsd = document.getElementById('edit_amount_usd');
    const paidIqd = document.getElementById('edit_paid_iqd');
    const paidUsd = document.getElementById('edit_paid_usd');
    const remainingIqd = document.getElementById('edit_remaining_iqd');
    const remainingUsd = document.getElementById('edit_remaining_usd');
    const exchangeRate = document.getElementById('edit_exchange_rate');
    if (currencyType && amountIqd && amountUsd && paidIqd && paidUsd && exchangeRate) {
        function handleCurrencyChange() {
            if (currencyType.value === 'دینار') {
                amountUsd.value = 0;
                amountUsd.disabled = true;
                amountIqd.disabled = false;
            } else if (currencyType.value === 'دۆلار') {
                amountIqd.value = 0;
                amountIqd.disabled = true;
                amountUsd.disabled = false;
            } else {
                amountIqd.disabled = false;
                amountUsd.disabled = false;
            }
            if (remainingIqd) remainingIqd.value = 0;
            if (remainingUsd) remainingUsd.value = 0;
            updateRemaining();
        }
        function updateRemaining() {
            let paidIqdVal = parseFloat(paidIqd.value) || 0;
            let paidUsdVal = parseFloat(paidUsd.value) || 0;
            let exRate = parseFloat(exchangeRate.value) || 150000;
            if (currencyType.value === 'دینار') {
                if (paidUsdVal > 0) {
                    paidIqdVal += paidUsdVal * (exRate / 100);
                }
                remainingIqd.value = (parseFloat(amountIqd.value) || 0) - paidIqdVal;
            } else if (currencyType.value === 'دۆلار') {
                if (paidIqd.value > 0) {
                    paidUsdVal += (parseFloat(paidIqd.value) || 0) / (exRate / 100);
                }
                remainingUsd.value = (parseFloat(amountUsd.value) || 0) - paidUsdVal;
            } else {
                if (remainingIqd) remainingIqd.value = (parseFloat(amountIqd.value) || 0) - paidIqdVal;
                if (remainingUsd) remainingUsd.value = (parseFloat(amountUsd.value) || 0) - paidUsdVal;
            }
        }
        currencyType.addEventListener('change', handleCurrencyChange);
        [amountIqd, paidIqd, amountUsd, paidUsd, exchangeRate].forEach(input => {
            if (input) input.addEventListener('input', updateRemaining);
        });
        handleCurrencyChange();
    }
}

// Setup when edit modal is shown
const editExpenseModal = document.getElementById('editExpenseModal');
if (editExpenseModal) {
    editExpenseModal.addEventListener('show.bs.modal', setupEditExpenseModal);
}
