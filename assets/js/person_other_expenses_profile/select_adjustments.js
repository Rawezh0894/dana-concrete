let adjustmentTable = null;

function formatNumber(num) {
    return Number(num).toLocaleString('en-US');
}

function formatUSD(num) {
    return num ? `$${formatNumber(num)}` : '$0';
}

function formatIQD(num) {
    return num ? `${formatNumber(num)} د.ع` : '0 د.ع';
}

async function loadAdjustments() {
    try {
        if (adjustmentTable) {
            adjustmentTable.destroy();
            adjustmentTable = null;
            $('#adjustmentTable').empty();
        }

        const res = await fetch(`../process/person_other_expenses_profile/select_adjustments.php?person_id=${PERSON_ID}`);

        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }

        const data = await res.json();

        if (!data || data.length === 0) {
            adjustmentTable = new DataTable('#adjustmentTable', {
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
                scrollX: true,
                pageLength: 10,
                order: [[0, 'desc']]
            });
            return;
        }

        const tableData = data.map((row) => {
            return [
                row.date || '',
                formatUSD(row.amount_usd),
                formatIQD(row.amount_iqd),
                row.note || '',
                `
                <button class="btn btn-sm btn-danger delete-adjustment" data-id="${row.id}">
                    <i class="fa fa-trash"></i>
                </button>
            `
            ];
        });

        adjustmentTable = new DataTable('#adjustmentTable', {
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
            scrollX: true,
            pageLength: 10,
            order: [[0, 'desc']],
            rowCallback: function (row) {
                $(row).find('button.delete-adjustment').off('click').on('click', function () {
                    const id = $(this).data('id');
                    if (id) {
                        deleteAdjustment(id);
                    }
                });
            }
        });

    } catch (error) {
        console.error('Error loading adjustments:', error);
        $('#adjustmentTable').html(`<tr><td colspan="5" class="text-danger text-center">هەڵە لە بارکردنی زانیاریەکان</td></tr>`);
    }
}

document.addEventListener('DOMContentLoaded', loadAdjustments);
window.loadAdjustmentTable = loadAdjustments;
