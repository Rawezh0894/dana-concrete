let debtTable = null;

function formatDebtAmount(val, currency) {
    if (!val || isNaN(val)) return '-';
    const n = Number(val).toLocaleString('en-US');
    if (currency === 'usd') return n + ' $';
    if (currency === 'iqd') return n + ' د.ع';
    return n;
}

function loadDebts() {
    // Destroy existing table if it exists
    if (debtTable) {
        debtTable.destroy();
        debtTable = null;
        $('#debtTable').empty();
    }
    
    const url = new URL('../process/company_profile/select_debt.php', window.location.href);
    url.searchParams.append('company_id', COMPANY_ID);
    if (typeof currentFilters !== 'undefined') {
        if (currentFilters.from_date) url.searchParams.append('from_date', currentFilters.from_date);
        if (currentFilters.to_date) url.searchParams.append('to_date', currentFilters.to_date);
    }
    
    fetch(url)
        .then(res => res.json())
        .then(debts => {
            if (!debts || debts.length === 0) {
                $('#debtTable').html(`<tr><td colspan="8" class="text-muted text-center">هیچ زانیارییەک نەدۆزرایەوە</td></tr>`);
                return;
            }
            // Prepare data for DataTables
            const tableData = debts.map((debt) => [
                debt.date,
                Number(debt.amount_usd).toLocaleString('en-US') + ' $',
                Number(debt.amount_iqd).toLocaleString('en-US') + ' د.ع',
                Number(debt.discount_usd || 0).toLocaleString('en-US') + ' $',
                Number(debt.discount_iqd || 0).toLocaleString('en-US') + ' د.ع',
                Number(debt.change_back_usd || 0).toLocaleString('en-US') + ' $',
                Number(debt.change_back_iqd || 0).toLocaleString('en-US') + ' د.ع',
                Number(debt.dollar_rate).toLocaleString('en-US') + ' د.ع',
                debt.note || '',
                `
                    <button class="btn btn-sm btn-primary me-1 edit-debt-btn"
                        data-id="${debt.id}"
                        data-date="${debt.date}"
                        data-amount_usd="${debt.amount_usd}"
                        data-amount_iqd="${debt.amount_iqd}"
                        data-discount_usd="${debt.discount_usd || 0}"
                        data-discount_iqd="${debt.discount_iqd || 0}"
                        data-change_back_usd="${debt.change_back_usd || 0}"
                        data-change_back_iqd="${debt.change_back_iqd || 0}"
                        data-dollar_rate="${debt.dollar_rate}"
                        data-note="${debt.note || ''}"
                        title="دەستکاری">
                        <i class="fa fa-pen"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-debt" data-id="${debt.id}" title="سڕینەوە">
                        <i class="fa fa-trash"></i>
                    </button>
                `
            ]);
            
            // Initialize DataTable
            debtTable = new DataTable('#debtTable', {
                data: tableData,
                columns: [
                    { title: 'بەروار' },
                    { title: 'بڕی دۆلار' },
                    { title: 'بڕی دینار' },
                    { title: 'داشکاندن ($)' },
                    { title: 'داشکاندن (د.ع)' },
                    { title: 'باقی ($)' },
                    { title: 'باقی (د.ع)' },
                    { title: 'نرخی دۆلار' },
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
                    },
                    "aria": {
                        "sortAscending": ": چالاککردن بۆ ڕیزکردنی ستون بەپێی زیادبوون",
                        "sortDescending": ": چالاککردن بۆ ڕیزکردنی ستون بەپێی کەمبوون"
                    }
                },
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[0, 'desc']], // Sort by date descending
                orderMulti: true, // Enable multi-column sorting (hold Shift while clicking headers)
                dom: 'Bfrtip', // Buttons, filter, table, info, pagination
                buttons: [
                    {
                        extend: 'copy',
                        text: 'لەبەرگرتنەوە',
                        className: 'btn btn-sm btn-outline-secondary'
                    },
                    {
                        extend: 'csv',
                        text: 'CSV',
                        className: 'btn btn-sm btn-outline-secondary'
                    },
                    {
                        extend: 'excel',
                        text: 'Excel',
                        className: 'btn btn-sm btn-outline-success'
                    },
                    {
                        extend: 'print',
                        text: 'پرینت',
                        className: 'btn btn-sm btn-outline-primary'
                    }
                ],
                initComplete: function() {
                    // Add individual column search inputs
                    this.api().columns().every(function() {
                        const column = this;
                        const header = $(column.header());
                        
                        // Skip adding search to actions column
                        if (header.text().includes('کردارەکان')) {
                            return;
                        }
                        
                        // Create search input
                        const searchInput = $('<input>')
                            .attr('type', 'text')
                            .attr('placeholder', 'فلتەر...')
                            .addClass('form-control form-control-sm mt-1 column-filter')
                            .css({
                                'width': '100%',
                                'padding': '0.25rem 0.5rem',
                                'border': '1px solid #ced4da',
                                'border-radius': '0.25rem'
                            });
                        
                        // Add search input to header
                        header.append(searchInput);
                        
                        // Apply search on keyup (Excel-like contains filter)
                        searchInput.on('keyup change', function() {
                            column.search(this.value).draw();
                        });
                    });
                }
            });
        })
        .catch(error => {
            console.error('Error loading debts:', error);
            $('#debtTable').html(`<tr><td colspan="7" class="text-danger text-center">هەڵە لە بارکردنی زانیاریەکان</td></tr>`);
        });
}

// Auto-load on tab show
$(document).on('shown.bs.tab', 'button[data-bs-target="#debt"]', loadDebts);

// Also load on page ready if debt tab is active
$(function() {
    if ($('#debt').hasClass('active')) loadDebts();
});
