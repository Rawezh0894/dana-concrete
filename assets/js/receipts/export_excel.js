function getReceiptExportFilters() {
    const typeEl = document.getElementById('transaction-type-filter');
    const monthEl = document.getElementById('month-filter');
    const dateFromEl = document.getElementById('date-from-filter');
    const dateToEl = document.getElementById('date-to-filter');
    const paidFromEl = document.getElementById('paid-date-from-filter');
    const paidToEl = document.getElementById('paid-date-to-filter');
    const invoiceEl = document.getElementById('invoice-number-filter');
    const invoiceModeEl = document.getElementById('invoice-filter-mode');

    return {
        customer_id: typeof CUSTOMER_ID !== 'undefined' ? CUSTOMER_ID : 0,
        type: typeEl ? typeEl.value : 'all',
        month: monthEl ? monthEl.value : 'all',
        date_from: dateFromEl ? dateFromEl.value : '',
        date_to: dateToEl ? dateToEl.value : '',
        location: typeof getSelectedLocations === 'function' ? getSelectedLocations() : 'all',
        recipient: typeof getSelectedRecipients === 'function' ? getSelectedRecipients() : 'all',
        paid_date_from: paidFromEl ? paidFromEl.value : '',
        paid_date_to: paidToEl ? paidToEl.value : '',
        invoice_number: invoiceEl ? invoiceEl.value.trim() : '',
        invoice_filter_mode: invoiceModeEl && invoiceModeEl.value === 'exclude' ? 'exclude' : 'include'
    };
}

function exportReceiptsToExcel() {
    if (typeof CUSTOMER_ID === 'undefined' || !CUSTOMER_ID) {
        if (typeof Swal !== 'undefined') {
            Swal.fire('هەڵە', 'ناسنامەی کڕیار نەدۆزرایەوە', 'error');
        } else {
            alert('ناسنامەی کڕیار نەدۆزرایەوە');
        }
        return;
    }

    const filters = getReceiptExportFilters();
    const formData = new FormData();
    Object.keys(filters).forEach((key) => {
        formData.append(key, filters[key] == null ? '' : String(filters[key]));
    });

    const showLoading = typeof Swal !== 'undefined';
    if (showLoading) {
        Swal.fire({
            title: 'چاوەڕوان بە...',
            text: 'خەملێنراوە بۆ ئیکسپۆرتی Excel',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
    }

    fetch('../process/receipts/export_excel.php', {
        method: 'POST',
        body: formData
    })
        .then((response) => {
            if (!response.ok) {
                return response.text().then((text) => {
                    throw new Error(text || 'Network response was not ok');
                });
            }
            const disposition = response.headers.get('Content-Disposition') || '';
            let filename = `پسووڵە_${new Date().toISOString().split('T')[0]}.xls`;
            const match = disposition.match(/filename="?([^"]+)"?/i);
            if (match && match[1]) {
                filename = match[1];
            }
            return response.blob().then((blob) => ({ blob, filename }));
        })
        .then(({ blob, filename }) => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            if (showLoading) {
                Swal.fire('سەرکەوتوو', 'فایلی Excel بە سەرکەوتوویی داگیرا', 'success');
            }
        })
        .catch((error) => {
            console.error('Receipt Excel export failed:', error);
            if (showLoading) {
                Swal.fire('هەڵە', error.message || 'هەڵەیەک لە ئیکسپۆرتکردن هەیە', 'error');
            } else {
                alert(error.message || 'هەڵەیەک لە ئیکسپۆرتکردن هەیە');
            }
        });
}

document.addEventListener('DOMContentLoaded', function () {
    const exportBtn = document.getElementById('export-excel-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', exportReceiptsToExcel);
    }
});

window.exportReceiptsToExcel = exportReceiptsToExcel;
