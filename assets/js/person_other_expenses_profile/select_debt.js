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
                    { title: 'بڕی دینار' },
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
                responsive: true,
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
        
        const tableData = data.map((row) => [
            row.date || '',
            formatUSD(row.amount_usd || 0),
            formatIQD(row.amount_iqd || 0),
            row.note || '',
            `
                <button class="btn btn-sm btn-warning edit-debt" data-id="${row.id}" data-date="${row.date}" data-amount_usd="${row.amount_usd}" data-amount_iqd="${row.amount_iqd}" data-note="${row.note || ''}">
                    <i class="fa fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger delete-debt" data-id="${row.id}">
                    <i class="fa fa-trash"></i>
                </button>
            `
        ]);
        
        debtTable = new DataTable('#debtTable', {
            data: tableData,
            columns: [
                { title: 'بەروار' },
                { title: 'بڕی دۆلار' },
                { title: 'بڕی دینار' },
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
            responsive: true,
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
            rowCallback: function(row, data) {
                // Attach edit and delete event handlers
                $(row).find('button.edit-debt').off('click').on('click', function() {
        const btn = $(this);
        $('#edit_debt_id').val(btn.data('id'));
        $('#edit_debt_date').val(btn.data('date'));
        $('#edit_debt_amount_usd').val(btn.data('amount_usd'));
        $('#edit_debt_amount_iqd').val(btn.data('amount_iqd'));
        $('#edit_debt_note').val(btn.data('note'));
        $('#editDebtModal').modal('show');
    });
                
                $(row).find('button.delete-debt').off('click').on('click', function() {
                    if (typeof deleteDebt === 'function') {
                        deleteDebt($(this).data('id'));
                    }
                });
            }
        });
        
    } catch (error) {
        console.error('Error loading debt payments:', error);
        if (debtTable) {
            debtTable.destroy();
            debtTable = null;
        }
        $('#debtTable').html(`<tr><td colspan="5" class="text-danger text-center">هەڵە لە بارکردنی زانیاریەکان</td></tr>`);
    }
}

document.addEventListener('DOMContentLoaded', loadDebtPayments);

window.loadDebtTable = loadDebtPayments;
