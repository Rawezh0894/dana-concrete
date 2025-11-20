function loadRecipientSummary() {
    if (!RECIPIENT_ID || RECIPIENT_ID <= 0) return;

    $.get('../process/recipient_profile/recipient_profile.php', { recipient_id: RECIPIENT_ID }, function(response) {
        if (!response || !response.success || !response.data) {
            console.error('Recipient summary error:', response?.message);
            setRecipientSummaryFallback();
            return;
        }

        const data = response.data;
        $('#total_quantity').text(`${formatNumber(data.total_quantity || 0, 2)} م³`);
        $('#sales_count').text(formatNumber(data.sales_count || 0, 0));
        $('#total_remaining').text(`${formatCurrency(data.total_remaining || 0)} $`);
    }, 'json').fail(function() {
        console.error('Network error loading recipient summary');
        setRecipientSummaryFallback();
    });
}

function setRecipientSummaryFallback() {
    $('#total_quantity').text('0 م³');
    $('#sales_count').text('0');
    $('#total_remaining').text('$0.00');
}

function formatNumber(value, decimals = 0) {
    const number = Number(value) || 0;
    return number.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

function formatCurrency(value) {
    const number = Number(value) || 0;
    return number.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

$(document).ready(function() {
    loadRecipientSummary();
});


