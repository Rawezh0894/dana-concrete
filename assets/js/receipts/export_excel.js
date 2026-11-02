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

function safeSheetName(name) {
    return String(name || 'Sheet')
        .replace(/[\\/?*\[\]]/g, ' ')
        .substring(0, 31) || 'Sheet';
}

function buildReceiptWorkbook(data) {
    const wb = XLSX.utils.book_new();
    const customer = data.customer || {};
    const salesTotals = data.sales_totals || {};
    const paymentTotals = data.payments_totals || {};

    const infoRows = [
        ['پسووڵەی کڕیار'],
        ['ناوی کڕیار', customer.name || ''],
        ['ژ.م', customer.mobile || ''],
        ['قەرزی سەرەتایی (USD)', customer.opening_debt_usd || 0],
        ['بەرواری ئیکسپۆرت', data.exported_at || ''],
        []
    ];

    const salesHeaders = [
        '#',
        'شوێن',
        'پێوانە (م³)',
        'ڕێژە',
        'جۆر',
        'نرخی 1 م³',
        'کۆی نرخ',
        'پارەی ماوە',
        'ژمارەی پسووڵە',
        'بەروار'
    ];

    const salesRows = (data.sales || []).map((row) => [
        row['#'] || '',
        row['شوێن'] || '',
        row['پێوانە (م³)'] || 0,
        row['ڕێژە'] || '',
        row['جۆر'] || '',
        row['نرخی 1 م³'] || 0,
        row['کۆی نرخ'] || 0,
        row['پارەی ماوە'] || 0,
        row['ژمارەی پسووڵە'] || '',
        row['بەروار'] || ''
    ]);

    if (salesRows.length) {
        salesRows.push([
            'کۆی گشتی',
            '',
            salesTotals.quantity || 0,
            '',
            '',
            '',
            salesTotals.total_price || 0,
            salesTotals.remaining || 0,
            '',
            ''
        ]);
    } else {
        salesRows.push(['هیچ مامەڵەیەک نەدۆزرایەوە']);
    }

    const salesSheet = XLSX.utils.aoa_to_sheet([
        ...infoRows,
        salesHeaders,
        ...salesRows
    ]);
    XLSX.utils.book_append_sheet(wb, salesSheet, safeSheetName('مامەڵەکان'));

    const paymentHeaders = [
        '#',
        'پارەی واسڵ کراو (USD)',
        'پارەی واسڵ کراو (د.ع)',
        'باقی پێدراوەتەوە (USD)',
        'باقی پێدراوەتەوە (د.ع)',
        'داشکاندن',
        'بەرواری پارەدان',
        'تێبینی'
    ];

    const paymentRows = (data.payments || []).map((row) => [
        row['#'] || '',
        row['پارەی واسڵ کراو (USD)'] || 0,
        row['پارەی واسڵ کراو (د.ع)'] || 0,
        row['باقی پێدراوەتەوە (USD)'] || 0,
        row['باقی پێدراوەتەوە (د.ع)'] || 0,
        row['داشکاندن'] || 0,
        row['بەرواری پارەدان'] || '',
        row['تێبینی'] || ''
    ]);

    if (paymentRows.length) {
        paymentRows.push([
            'کۆی گشتی',
            paymentTotals.paid_usd || 0,
            paymentTotals.paid_iqd || 0,
            '',
            '',
            '',
            '',
            ''
        ]);
    } else {
        paymentRows.push(['هیچ دانەوەیەک نەدۆزرایەوە']);
    }

    const paymentsSheet = XLSX.utils.aoa_to_sheet([
        ['دانەوەی قەرز'],
        [],
        paymentHeaders,
        ...paymentRows
    ]);
    XLSX.utils.book_append_sheet(wb, paymentsSheet, safeSheetName('دانەوەی قەرز'));

    return wb;
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

    if (typeof XLSX === 'undefined') {
        if (typeof Swal !== 'undefined') {
            Swal.fire('هەڵە', 'کتێبخانەی Excel بارنەبووە. پەڕەکە refresh بکە.', 'error');
        } else {
            alert('کتێبخانەی Excel بارنەبووە');
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
            text: 'خەملێنراوە بۆ ئیکسپۆرتی Excel (.xlsx)',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
    }

    fetch('../process/receipts/export_excel.php', {
        method: 'POST',
        body: formData
    })
        .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) {
                throw new Error((data && data.message) || 'هەڵەیەک لە ئیکسپۆرتکردن هەیە');
            }

            const wb = buildReceiptWorkbook(data);
            const safeName = String(data.customer?.name || 'customer')
                .replace(/[\\/:*?"<>|]+/g, '_')
                .trim() || 'customer';
            const filename = `پسووڵە_${safeName}_${new Date().toISOString().split('T')[0]}.xlsx`;
            XLSX.writeFile(wb, filename);

            if (showLoading) {
                Swal.fire('سەرکەوتوو', 'فایلی Excel (.xlsx) بە سەرکەوتوویی داگیرا', 'success');
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
