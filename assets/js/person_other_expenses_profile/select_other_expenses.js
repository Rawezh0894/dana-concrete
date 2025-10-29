let expensesTable = null;

function formatNumber(num) {
    return Number(num).toLocaleString('en-US');
}

function formatUSD(num) {
    return num ? `$${formatNumber(num)}` : '$0';
}

function formatIQD(num) {
    return num ? `${formatNumber(num)} د.ع` : '0 د.ع';
}

async function loadOtherExpenses() {
    try {
        // Destroy existing table if it exists
        if (expensesTable) {
            expensesTable.destroy();
            expensesTable = null;
            $('#expensesTable').empty();
        }

    const res = await fetch(`../process/person_other_expenses_profile/select_other_expenses.php?person_id=${PERSON_ID}`);
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
    const data = await res.json();
        
        if (!data || data.length === 0) {
            expensesTable = new DataTable('#expensesTable', {
                data: [],
                columns: [
                    { title: 'مەبەست' },
                    { title: 'کارمەند' },
                    { title: 'سەیارە' },
                    { title: 'جۆری مامەڵە' },
                    { title: 'جۆری پارە' },
                    { title: 'ژمارەی وەسڵ' },
                    { title: 'بڕی دینار' },
                    { title: 'بڕی دۆلار' },
                    { title: 'پارەی دراو دینار' },
                    { title: 'پارەی دراو دۆلار' },
                    { title: 'نرخی 100 دۆلار' },
                    { title: 'ماوە دینار' },
                    { title: 'ماوە دۆلار' },
                    { title: 'بەروار' }
                ],
                language: {
                    "processing": "چاوەڕوان بە...",
                    "search": "گەڕان:",
                    "lengthMenu": "نیشاندان _MENU_ ڕیکۆرد",
                    "info": "نوێنراوە _START_ لە _END_ لە _TOTAL_ ڕیکۆرد",
                    "infoEmpty": "نوێنراوە 0 لە 0 لە 0 ڕیکۆرد",
                    "infoFiltered": "(فلتەرکراو لە _MAX_ کۆی ڕیکۆرد)",
                    "loadingRecords": "لۆدینگ...",
                    "zeroRecords": "هیچ ڕیکۆردێک نەدۆزرایەوە",
                    "emptyTable": "هیچ زانیارییەک لە خشتەکەدا نییە",
                    "paginate": {
                        "first": "یەکەم",
                        "previous": "پێشوو",
                        "next": "دواتر",
                        "last": "کۆتایی"
                    }
                },
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[13, 'desc']],
                dom: 'Bfrtip',
                buttons: [
                    { extend: 'copy', text: 'لەبەرگرتنەوە', className: 'btn btn-sm btn-outline-secondary' },
                    { extend: 'csv', text: 'CSV', className: 'btn btn-sm btn-outline-secondary' },
                    { extend: 'excel', text: 'Excel', className: 'btn btn-sm btn-outline-success' },
                    { extend: 'print', text: 'پرینت', className: 'btn btn-sm btn-outline-primary' }
                ]
            });
            return;
        }
        
        const tableData = data.map((row) => [
            row.purpose || '',
            row.employee_name || '',
            row.car_name || '',
            row.payment_type || '',
            row.currency_type || '',
            row.invoice_number || '',
            formatIQD(row.amount_iqd || 0),
            formatUSD(row.amount_usd || 0),
            formatIQD(row.paid_iqd || 0),
            formatUSD(row.paid_usd || 0),
            formatNumber(row.exchange_rate || 0),
            formatIQD(row.remaining_iqd || 0),
            formatUSD(row.remaining_usd || 0),
            row.date || ''
        ]);
        
        expensesTable = new DataTable('#expensesTable', {
            data: tableData,
            columns: [
                { title: 'مەبەست' },
                { title: 'کارمەند' },
                { title: 'سەیارە' },
                { title: 'جۆری مامەڵە' },
                { title: 'جۆری پارە' },
                { title: 'ژمارەی وەسڵ' },
                { title: 'بڕی دینار' },
                { title: 'بڕی دۆلار' },
                { title: 'پارەی دراو دینار' },
                { title: 'پارەی دراو دۆلار' },
                { title: 'نرخی 100 دۆلار' },
                { title: 'ماوە دینار' },
                { title: 'ماوە دۆلار' },
                { title: 'بەروار' }
            ],
            language: {
                "processing": "چاوەڕوان بە...",
                "search": "گەڕان:",
                "lengthMenu": "نیشاندان _MENU_ ڕیکۆرد",
                "info": "نوێنراوە _START_ لە _END_ لە _TOTAL_ ڕیکۆرد",
                "infoEmpty": "نوێنراوە 0 لە 0 لە 0 ڕیکۆرد",
                "infoFiltered": "(فلتەرکراو لە _MAX_ کۆی ڕیکۆرد)",
                "loadingRecords": "لۆدینگ...",
                "zeroRecords": "هیچ ڕیکۆردێک نەدۆزرایەوە",
                "emptyTable": "هیچ زانیارییەک لە خشتەکەدا نییە",
                "paginate": {
                    "first": "یەکەم",
                    "previous": "پێشوو",
                    "next": "دواتر",
                    "last": "کۆتایی"
                }
            },
            responsive: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[13, 'desc']],
            dom: 'Bfrtip',
            buttons: [
                { extend: 'copy', text: 'لەبەرگرتنەوە', className: 'btn btn-sm btn-outline-secondary' },
                { extend: 'csv', text: 'CSV', className: 'btn btn-sm btn-outline-secondary' },
                { extend: 'excel', text: 'Excel', className: 'btn btn-sm btn-outline-success' },
                { extend: 'print', text: 'پرینت', className: 'btn btn-sm btn-outline-primary' }
            ]
        });
        
    } catch (error) {
        console.error('Error loading other expenses:', error);
        if (expensesTable) {
            expensesTable.destroy();
            expensesTable = null;
        }
        $('#expensesTable').html(`<tr><td colspan="14" class="text-danger text-center">هەڵە لە بارکردنی زانیاریەکان</td></tr>`);
    }
}

document.addEventListener('DOMContentLoaded', loadOtherExpenses);
