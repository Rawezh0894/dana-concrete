function loadRecipientSummaryCards() {
    $.ajax({
        url: '../process/recipients/recipients.php',
        type: 'GET',
        dataType: 'json',
        success: function (response) {
            if (response.success && response.data) {
                const summary = response.data;
                $('#total_recipients').text(Number(summary.total_recipients || 0).toLocaleString('en-US'));
                $('#recipients_with_meter').text(Number(summary.recipients_with_meter || 0).toLocaleString('en-US'));
                $('#total_opening_meter').text(`${Number(summary.total_opening_meter || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })} م³`);
            } else {
                setSummaryDefaults();
                console.error('Error loading recipients summary:', response.message);
            }
        },
        error: function () {
            setSummaryDefaults();
            console.error('Network error while loading recipients summary.');
        }
    });
}

function setSummaryDefaults() {
    $('#total_recipients').text('0');
    $('#recipients_with_meter').text('0');
    $('#total_opening_meter').text('0 م³');
}

$(document).ready(function () {
    loadRecipientSummaryCards();

    $(document).on('recipientAdded recipientUpdated recipientDeleted', function () {
        loadRecipientSummaryCards();
    });
});


