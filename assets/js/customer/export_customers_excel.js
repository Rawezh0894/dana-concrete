$(document).ready(function() {
    const $exportBtn = $('#exportCustomersExcelBtn');
    if (!$exportBtn.length) {
        return;
    }

    $exportBtn.on('click', function() {
        // Use AG Grid export if available
        if (typeof exportCustomersToExcel === 'function') {
            exportCustomersToExcel();
        } else if (typeof customerGridApi !== 'undefined' && customerGridApi) {
            const params = {
                fileName: `کڕیارەکان_${new Date().toISOString().split('T')[0]}.csv`
            };
            customerGridApi.exportDataAsCsv(params);
        } else {
            // Fallback to old method
            const today = new Date();
            const formattedDate = today.toISOString().split('T')[0];
            const downloadLink = document.createElement('a');
            downloadLink.href = '../process/customer/export_excel.php';
            downloadLink.download = 'customers_debt_' + formattedDate + '.xls';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    });
});

