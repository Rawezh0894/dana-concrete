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

const RECEIPT_EXCEL_THEME = {
    brand: '003B73',
    accent: '009688',
    headerBg: '003B73',
    headerFg: 'FFFFFF',
    titleBg: '003B73',
    titleFg: 'FFFFFF',
    labelBg: 'E8F4FD',
    labelFg: '003B73',
    altRow: 'F3F8FC',
    totalBg: 'D4EFDF',
    totalFg: '145A32',
    border: '90A4AE',
    emptyBg: 'FFF3CD',
    emptyFg: '856404'
};

function excelBorder() {
    const side = { style: 'thin', color: { rgb: RECEIPT_EXCEL_THEME.border } };
    return { top: side, bottom: side, left: side, right: side };
}

function excelFont(overrides) {
    return Object.assign({
        name: 'Arial',
        sz: 11,
        color: { rgb: '212121' }
    }, overrides || {});
}

function excelAlign(overrides) {
    return Object.assign({
        horizontal: 'right',
        vertical: 'center',
        wrapText: true,
        readingOrder: 2
    }, overrides || {});
}

function styledCell(value, style) {
    return { v: value == null ? '' : value, t: typeof value === 'number' ? 'n' : 's', s: style };
}

function applySheetRtl(ws, colCount, rowCount) {
    ws['!views'] = [{ rightToLeft: true, state: 'normal' }];
    ws['!margins'] = { left: 0.5, right: 0.5, top: 0.5, bottom: 0.5 };
    if (!ws['!ref']) {
        const endCol = XLSX.utils.encode_col(Math.max(colCount - 1, 0));
        ws['!ref'] = `A1:${endCol}${Math.max(rowCount, 1)}`;
    }
}

function setColWidths(ws, widths) {
    ws['!cols'] = widths.map((wch) => ({ wch }));
}

function setRowHeights(ws, heights) {
    ws['!rows'] = heights.map((hpt) => ({ hpt }));
}

function buildInfoBlock(customer, exportedAt, colCount) {
    const rows = [];
    const titleStyle = {
        font: excelFont({ bold: true, sz: 16, color: { rgb: RECEIPT_EXCEL_THEME.titleFg } }),
        fill: { patternType: 'solid', fgColor: { rgb: RECEIPT_EXCEL_THEME.titleBg } },
        alignment: excelAlign({ horizontal: 'center' }),
        border: excelBorder()
    };
    const labelStyle = {
        font: excelFont({ bold: true, color: { rgb: RECEIPT_EXCEL_THEME.labelFg } }),
        fill: { patternType: 'solid', fgColor: { rgb: RECEIPT_EXCEL_THEME.labelBg } },
        alignment: excelAlign(),
        border: excelBorder()
    };
    const valueStyle = {
        font: excelFont({ bold: true }),
        fill: { patternType: 'solid', fgColor: { rgb: 'FFFFFF' } },
        alignment: excelAlign(),
        border: excelBorder()
    };

    const titleRow = [styledCell('دانا کۆنکرێت — پسووڵەی کڕیار', titleStyle)];
    for (let i = 1; i < colCount; i++) {
        titleRow.push(styledCell('', titleStyle));
    }
    rows.push(titleRow);

    const infoPairs = [
        ['ناوی کڕیار', customer.name || ''],
        ['ژ.م کڕیار', customer.mobile || ''],
        ['قەرزی سەرەتایی (USD)', customer.opening_debt_usd || 0],
        ['بەرواری ئیکسپۆرت', exportedAt || '']
    ];

    infoPairs.forEach(([label, value]) => {
        const row = [styledCell(label, labelStyle), styledCell(value, valueStyle)];
        for (let i = 2; i < colCount; i++) {
            row.push(styledCell('', valueStyle));
        }
        rows.push(row);
    });

    rows.push(new Array(colCount).fill(styledCell('', {
        fill: { patternType: 'solid', fgColor: { rgb: 'FFFFFF' } }
    })));

    return rows;
}

function buildHeaderRow(headers) {
    const style = {
        font: excelFont({ bold: true, color: { rgb: RECEIPT_EXCEL_THEME.headerFg }, sz: 11 }),
        fill: { patternType: 'solid', fgColor: { rgb: RECEIPT_EXCEL_THEME.headerBg } },
        alignment: excelAlign({ horizontal: 'center' }),
        border: excelBorder()
    };
    return headers.map((h) => styledCell(h, style));
}

function buildDataRow(values, isAlt) {
    const style = {
        font: excelFont(),
        fill: {
            patternType: 'solid',
            fgColor: { rgb: isAlt ? RECEIPT_EXCEL_THEME.altRow : 'FFFFFF' }
        },
        alignment: excelAlign({ horizontal: 'center' }),
        border: excelBorder()
    };
    return values.map((value, index) => {
        const cellStyle = Object.assign({}, style, {
            alignment: excelAlign({
                horizontal: index === 0 || typeof value === 'number' ? 'center' : 'right'
            })
        });
        return styledCell(value, cellStyle);
    });
}

function buildTotalRow(values) {
    const style = {
        font: excelFont({ bold: true, color: { rgb: RECEIPT_EXCEL_THEME.totalFg } }),
        fill: { patternType: 'solid', fgColor: { rgb: RECEIPT_EXCEL_THEME.totalBg } },
        alignment: excelAlign({ horizontal: 'center' }),
        border: excelBorder()
    };
    return values.map((value) => styledCell(value, style));
}

function buildEmptyRow(message, colCount) {
    const style = {
        font: excelFont({ italic: true, color: { rgb: RECEIPT_EXCEL_THEME.emptyFg } }),
        fill: { patternType: 'solid', fgColor: { rgb: RECEIPT_EXCEL_THEME.emptyBg } },
        alignment: excelAlign({ horizontal: 'center' }),
        border: excelBorder()
    };
    const row = [styledCell(message, style)];
    for (let i = 1; i < colCount; i++) {
        row.push(styledCell('', style));
    }
    return row;
}

function sheetFromRows(rows, merges) {
    const ws = XLSX.utils.aoa_to_sheet(rows);
    if (merges && merges.length) {
        ws['!merges'] = merges;
    }
    return ws;
}

function buildReceiptWorkbook(data) {
    const wb = XLSX.utils.book_new();
    wb.Workbook = wb.Workbook || {};
    wb.Workbook.Views = [{ RTL: true }];

    const customer = data.customer || {};
    const salesTotals = data.sales_totals || {};
    const paymentTotals = data.payments_totals || {};

    const salesHeaders = [
        '#', 'شوێن', 'پێوانە (م³)', 'ڕێژە', 'جۆر',
        'نرخی 1 م³', 'کۆی نرخ', 'پارەی ماوە', 'ژمارەی پسووڵە', 'بەروار'
    ];
    const salesColCount = salesHeaders.length;

    const salesAoA = [
        ...buildInfoBlock(customer, data.exported_at, salesColCount),
        buildHeaderRow(salesHeaders)
    ];

    const sales = data.sales || [];
    if (!sales.length) {
        salesAoA.push(buildEmptyRow('هیچ مامەڵەیەک نەدۆزرایەوە', salesColCount));
    } else {
        sales.forEach((row, index) => {
            salesAoA.push(buildDataRow([
                row['#'] || index + 1,
                row['شوێن'] || '',
                row['پێوانە (م³)'] || 0,
                row['ڕێژە'] || '',
                row['جۆر'] || '',
                row['نرخی 1 م³'] || 0,
                row['کۆی نرخ'] || 0,
                row['پارەی ماوە'] || 0,
                row['ژمارەی پسووڵە'] || '',
                row['بەروار'] || ''
            ], index % 2 === 1));
        });
        salesAoA.push(buildTotalRow([
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
        ]));
    }

    const salesMerges = [
        { s: { r: 0, c: 0 }, e: { r: 0, c: salesColCount - 1 } },
        { s: { r: 1, c: 1 }, e: { r: 1, c: salesColCount - 1 } },
        { s: { r: 2, c: 1 }, e: { r: 2, c: salesColCount - 1 } },
        { s: { r: 3, c: 1 }, e: { r: 3, c: salesColCount - 1 } },
        { s: { r: 4, c: 1 }, e: { r: 4, c: salesColCount - 1 } }
    ];

    const salesSheet = sheetFromRows(salesAoA, salesMerges);
    applySheetRtl(salesSheet, salesColCount, salesAoA.length);
    setColWidths(salesSheet, [6, 18, 12, 12, 12, 12, 12, 12, 22, 12]);
    setRowHeights(salesSheet, [
        28, 22, 22, 22, 22, 10, 24,
        ...new Array(Math.max(salesAoA.length - 7, 0)).fill(20)
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
    const payColCount = paymentHeaders.length;

    const paymentsAoA = [
        ...buildInfoBlock(customer, data.exported_at, payColCount),
        buildHeaderRow(paymentHeaders)
    ];

    const payments = data.payments || [];
    if (!payments.length) {
        paymentsAoA.push(buildEmptyRow('هیچ دانەوەیەک نەدۆزرایەوە', payColCount));
    } else {
        payments.forEach((row, index) => {
            paymentsAoA.push(buildDataRow([
                row['#'] || index + 1,
                row['پارەی واسڵ کراو (USD)'] || 0,
                row['پارەی واسڵ کراو (د.ع)'] || 0,
                row['باقی پێدراوەتەوە (USD)'] || 0,
                row['باقی پێدراوەتەوە (د.ع)'] || 0,
                row['داشکاندن'] || 0,
                row['بەرواری پارەدان'] || '',
                row['تێبینی'] || ''
            ], index % 2 === 1));
        });
        paymentsAoA.push(buildTotalRow([
            'کۆی گشتی',
            paymentTotals.paid_usd || 0,
            paymentTotals.paid_iqd || 0,
            '',
            '',
            '',
            '',
            ''
        ]));
    }

    const paymentMerges = [
        { s: { r: 0, c: 0 }, e: { r: 0, c: payColCount - 1 } },
        { s: { r: 1, c: 1 }, e: { r: 1, c: payColCount - 1 } },
        { s: { r: 2, c: 1 }, e: { r: 2, c: payColCount - 1 } },
        { s: { r: 3, c: 1 }, e: { r: 3, c: payColCount - 1 } },
        { s: { r: 4, c: 1 }, e: { r: 4, c: payColCount - 1 } }
    ];

    const paymentsSheet = sheetFromRows(paymentsAoA, paymentMerges);
    applySheetRtl(paymentsSheet, payColCount, paymentsAoA.length);
    setColWidths(paymentsSheet, [6, 18, 18, 18, 18, 12, 14, 24]);
    setRowHeights(paymentsSheet, [
        28, 22, 22, 22, 22, 10, 24,
        ...new Array(Math.max(paymentsAoA.length - 7, 0)).fill(20)
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
