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
                responsive: false,
                scrollX: true,
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

        // Store original data for calculations
        window.expensesOriginalData = data;

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
            responsive: false,
            scrollX: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[13, 'desc']],
            dom: 'Bfrtip',
            buttons: [
                { extend: 'copy', text: 'لەبەرگرتنەوە', className: 'btn btn-sm btn-outline-secondary' },
                { extend: 'csv', text: 'CSV', className: 'btn btn-sm btn-outline-secondary' },
                { extend: 'excel', text: 'Excel', className: 'btn btn-sm btn-outline-success' },
                { extend: 'print', text: 'پرینت', className: 'btn btn-sm btn-outline-primary' }
            ],
            drawCallback: function () {
                // Update summary cards when table is redrawn
                updateExpensesSummaryCards();
            }
        });

        // Setup date filter
        setupDateFilter('#expensesDateFrom', '#expensesDateTo', expensesTable, 13, '#clearExpensesFilter');

    } catch (error) {
        console.error('Error loading other expenses:', error);
        if (expensesTable) {
            expensesTable.destroy();
            expensesTable = null;
        }
        $('#expensesTable').html(`<tr><td colspan="14" class="text-danger text-center">هەڵە لە بارکردنی زانیاریەکان</td></tr>`);
    }
}

// Update summary cards based on filtered expenses data
function updateExpensesSummaryCards() {
    if (!expensesTable) return;

    // Get date filter values
    const dateFromInput = document.querySelector('#expensesDateFrom');
    const dateToInput = document.querySelector('#expensesDateTo');

    const dateFrom = dateFromInput ? dateFromInput.value : null;
    const dateTo = dateToInput ? dateToInput.value : null;

    // Reload summary cards with date filters
    if (typeof loadSummaryCards === 'function') {
        loadSummaryCards(dateFrom, dateTo);
    }
}

// Date filter function for DataTables
function setupDateFilter(fromId, toId, table, dateColumnIndex, clearBtnId) {
    const fromInput = document.querySelector(fromId);
    const toInput = document.querySelector(toId);
    const clearBtn = document.querySelector(clearBtnId);

    if (!fromInput || !toInput || !table) return;

    // Custom filter function
    $.fn.dataTable.ext.search.push(
        function (settings, data, dataIndex) {
            // Only apply to the specific table
            if (settings.nTable.id !== table.table().node().id) return true;

            const rowDate = data[dateColumnIndex] || '';
            if (!rowDate) return true;

            const dateFrom = fromInput.value ? new Date(fromInput.value) : null;
            const dateTo = toInput.value ? new Date(toInput.value) : null;
            const rowDateObj = new Date(rowDate);

            // If both dates are empty, show all
            if (!dateFrom && !dateTo) return true;

            // Check date range
            if (dateFrom && rowDateObj < dateFrom) return false;
            if (dateTo && rowDateObj > dateTo) return false;

            return true;
        }
    );

    // Add event listeners
    fromInput.addEventListener('change', function () {
        table.draw();
    });

    toInput.addEventListener('change', function () {
        table.draw();
    });

    // Clear filter button
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            fromInput.value = '';
            toInput.value = '';
            table.draw();
        });
    }
}

document.addEventListener('DOMContentLoaded', loadOtherExpenses);
