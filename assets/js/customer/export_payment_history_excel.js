$(document).ready(function() {
    const $exportBtn = $('#exportPaymentHistoryExcelBtn');
    if (!$exportBtn.length) {
        return;
    }

    const originalHtml = $exportBtn.html();

    function resetButton() {
        $exportBtn.prop('disabled', false);
        $exportBtn.html(originalHtml);
    }

    $exportBtn.on('click', function() {
        if ($exportBtn.prop('disabled')) {
            return;
        }

        const today = new Date();
        const formattedDate = today.toISOString().split('T')[0];
        const downloadLink = document.createElement('a');

        $exportBtn.prop('disabled', true);
        $exportBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>چاوەڕوان بە...');

        downloadLink.href = '../process/customer/export_payment_history_excel.php';
        downloadLink.download = 'customer_debt_payments_history_' + formattedDate + '.xls';

        downloadLink.addEventListener('click', function() {
            setTimeout(resetButton, 1200);
        });

        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);

        setTimeout(resetButton, 4000);
    });
});

