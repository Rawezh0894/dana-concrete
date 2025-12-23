let debtTable = null;

function formatNumber(num) {
    return Number(num).toLocaleString('en-US');
}

function formatUSD(num) {
    return num ? `$${formatNumber(num)}` : '$0';
}

function formatIQD(num) {
    return num ? `${formatNumber(num)} د.ع` : '0 د.ع';
}

async function openEditDebtModal(debtId) {
    try {
        const res = await fetch(`../process/person_other_expenses_profile/select_debt.php?debt_id=${debtId}`);
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        const payload = await res.json();
        if (!payload.success || !payload.data) {
            Swal.fire('هەڵە!', 'دانەوە نەدۆزرایەوە.', 'error');
            return;
        }

        const debt = payload.data;
        const amountUsd = parseFloat(debt.amount_usd ?? 0) || 0;
        const discountUsd = parseFloat(debt.discount_usd ?? 0) || 0;
        const amountIqd = parseFloat(debt.amount_iqd ?? 0) || 0;
        const discountIqd = parseFloat(debt.discount_iqd ?? 0) || 0;

        $('#edit_debt_id').val(debt.id);
        $('#edit_debt_date').val(debt.date || '');
        $('#edit_debt_amount_usd').val(amountUsd);
        $('#edit_debt_discount_usd').val(discountUsd);
        $('#edit_debt_amount_iqd').val(amountIqd);
        $('#edit_debt_discount_iqd').val(discountIqd);
        $('#edit_debt_note').val(debt.note || '');

        if (typeof setupEditDebtModal === 'function') {
            setupEditDebtModal({
                amount_usd: amountUsd,
                discount_usd: discountUsd,
                amount_iqd: amountIqd,
                discount_iqd: discountIqd
            });
        }

        $('#editDebtModal').modal('show');
    } catch (error) {
        console.error('Error loading debt payment:', error);
        Swal.fire('هەڵە!', 'هەڵە لە بارکردنی دانەوە.', 'error');
    }
}

async function loadDebtPayments() {
    try {
        // Destroy existing table if it exists
        if (debtTable) {
            debtTable.destroy();
            debtTable = null;
            $('#debtTable').empty();
        }

        const res = await fetch(`../process/person_other_expenses_profile/select_debt.php?person_id=${PERSON_ID}`);

        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }

        const data = await res.json();

        if (!data || data.length === 0) {
            debtTable = new DataTable('#debtTable', {
                data: [],
                columns: [
                    { title: 'بەروار' },
                    { title: 'بڕی دۆلار' },
                    { title: 'داشکاندن بە دۆلار' },
                    { title: 'بڕی دینار' },
                    { title: 'داشکاندن بە دینار' },
                    { title: 'تێبینی' },
                    { title: 'کردارەکان' }
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
                scrollX: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[0, 'desc']],
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

        const tableData = data.map((row) => {
            const amountUsd = parseFloat(row.amount_usd ?? 0) || 0;
            const discountUsd = parseFloat(row.discount_usd ?? 0) || 0;
            const amountIqd = parseFloat(row.amount_iqd ?? 0) || 0;
            const discountIqd = parseFloat(row.discount_iqd ?? 0) || 0;

            return [
                row.date || '',
                formatUSD(amountUsd),
                formatUSD(discountUsd),
                formatIQD(amountIqd),
                formatIQD(discountIqd),
                row.note || '',
                `
                <button class="btn btn-sm btn-warning edit-debt" data-id="${row.id}">
                    <i class="fa fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger delete-debt" data-id="${row.id}">
                    <i class="fa fa-trash"></i>
                </button>
            `
            ];
        });

        debtTable = new DataTable('#debtTable', {
            data: tableData,
            columns: [
                { title: 'بەروار' },
                { title: 'بڕی دۆلار' },
                { title: 'داشکاندن بە دۆلار' },
                { title: 'بڕی دینار' },
                { title: 'داشکاندن بە دینار' },
                { title: 'تێبینی' },
                { title: 'کردارەکان' }
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
            scrollX: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[0, 'desc']],
            dom: 'Bfrtip',
            buttons: [
                { extend: 'copy', text: 'لەبەرگرتنەوە', className: 'btn btn-sm btn-outline-secondary' },
                { extend: 'csv', text: 'CSV', className: 'btn btn-sm btn-outline-secondary' },
                { extend: 'excel', text: 'Excel', className: 'btn btn-sm btn-outline-success' },
                { extend: 'print', text: 'پرینت', className: 'btn btn-sm btn-outline-primary' }
            ],
            rowCallback: function (row) {
                $(row).find('button.edit-debt').off('click').on('click', function () {
                    const id = $(this).data('id');
                    if (id) {
                        openEditDebtModal(id);
                    }
                });

                $(row).find('button.delete-debt').off('click').on('click', function () {
                    if (typeof deleteDebt === 'function') {
                        deleteDebt($(this).data('id'));
                    }
                });
            },
            drawCallback: function () {
                // Update summary cards when debt table is redrawn
                updateDebtSummaryCards();
            }
        });

        // Setup date filter
        setupDateFilter('#debtDateFrom', '#debtDateTo', debtTable, 0, '#clearDebtFilter');

        // Store original data
        window.debtOriginalData = data;

    } catch (error) {
        console.error('Error loading debt payments:', error);
        if (debtTable) {
            debtTable.destroy();
            debtTable = null;
        }
        $('#debtTable').html(`<tr><td colspan="7" class="text-danger text-center">هەڵە لە بارکردنی زانیاریەکان</td></tr>`);
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

// Update summary cards based on filtered debt data
function updateDebtSummaryCards() {
    // Debt payments don't affect the main summary cards (our debt calculation)
    // But we could update a debt-specific count if needed in the future
    // For now, we'll just refresh the full summary cards
    if (typeof loadSummaryCards === 'function') {
        const dateFromInput = document.querySelector('#debtDateFrom');
        const dateToInput = document.querySelector('#debtDateTo');

        const dateFrom = dateFromInput ? dateFromInput.value : null;
        const dateTo = dateToInput ? dateToInput.value : null;

        loadSummaryCards(dateFrom, dateTo);
    }
}

document.addEventListener('DOMContentLoaded', loadDebtPayments);

window.loadDebtTable = loadDebtPayments;
window.openEditDebtModal = openEditDebtModal;
