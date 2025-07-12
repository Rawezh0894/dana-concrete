document.addEventListener('DOMContentLoaded', function() {
    const currencyType = document.getElementById('currency_type');
    const amountIqd = document.getElementById('amount_iqd');
    const amountUsd = document.getElementById('amount_usd');
    const paidIqd = document.getElementById('paid_iqd');
    const paidUsd = document.getElementById('paid_usd');
    const remainingIqd = document.getElementById('remaining_iqd');
    const remainingUsd = document.getElementById('remaining_usd');
    const dateInput = document.getElementById('date');
    const exchangeRate = document.getElementById('exchange_rate');
    const addExpenseModal = document.getElementById('addExpenseModal');
    if (addExpenseModal && dateInput) {
        addExpenseModal.addEventListener('show.bs.modal', function () {
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            dateInput.value = `${yyyy}-${mm}-${dd}`;
        });
    }
    if (currencyType && amountIqd && amountUsd && paidIqd && paidUsd && exchangeRate) {
        // Set default option
        currencyType.insertAdjacentHTML('afterbegin', '<option value="" selected hidden>-- هەلبژێرە --</option>');
        currencyType.value = '';
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
                // If paid_usd entered, convert to IQD and add to paid_iqd
                if (paidUsdVal > 0) {
                    paidIqdVal += paidUsdVal * (exRate / 100);
                }
                remainingIqd.value = (parseFloat(amountIqd.value) || 0) - paidIqdVal;
            } else if (currencyType.value === 'دۆلار') {
                // If paid_iqd entered, convert to USD and add to paid_usd
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
});
