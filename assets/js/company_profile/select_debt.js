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
                $('#debtTable').html(`<tr><td colspan="7" class="text-muted text-center">هیچ زانیارییەک نەدۆزرایەوە</td></tr>`);
                return;
            }
            // Prepare data for DataTables
            const tableData = debts.map((debt) => [
                debt.date,
                Number(debt.amount_usd).toLocaleString('en-US') + ' $',
                Number(debt.amount_iqd).toLocaleString('en-US') + ' د.ع',
                Number(debt.discount_usd || 0).toLocaleString('en-US') + ' $',
                Number(debt.dollar_rate).toLocaleString('en-US') + ' د.ع',
                debt.note || '',
                `
                    <button class="btn btn-sm btn-primary me-1 edit-debt-btn"
                        data-id="${debt.id}"
                        data-date="${debt.date}"
                        data-amount_usd="${debt.amount_usd}"
                        data-amount_iqd="${debt.amount_iqd}"
                        data-discount_usd="${debt.discount_usd || 0}"
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
                    { title: 'داشکاندن (دۆلار)' },
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
                    const api = this.api();
                    api.columns().every(function(index) {
                        const column = this;
                        const header = $(column.header());
                        if (header.text().includes('کردارەکان')) return;
                        
                        const filterBtn = $('<button>').html('<i class="fas fa-filter"></i>')
                            .addClass('btn btn-sm btn-outline-secondary column-filter-btn')
                            .css({'position':'absolute','left':'5px','top':'5px','padding':'2px 6px','font-size':'0.7rem'})
                            .attr('data-column', index).attr('title', 'فلتەر');
                        header.css('position', 'relative').prepend(filterBtn);
                        
                        const dropdown = $('<div>').addClass('dropdown-menu column-filter-menu')
                            .css({'max-height':'300px','overflow-y':'auto','min-width':'200px','max-width':'400px'})
                            .attr('data-column', index);
                        
                        function populateDropdown() {
                            dropdown.empty();
                            const searchBox = $('<input>').attr('type', 'text').addClass('form-control form-control-sm m-2').attr('placeholder', 'گەڕان...').css('width', 'calc(100% - 1rem)');
                            dropdown.append(searchBox);
                            const uniqueValues = column.data().unique().sort();
                            dropdown.append($('<div>').addClass('dropdown-item checkbox-item').html('<label class="d-flex align-items-center m-0 w-100"><input type="checkbox" class="me-2 filter-checkbox select-all" checked> <strong>هەموو</strong></label>').css({'cursor':'pointer'}));
                            dropdown.append($('<div>').addClass('dropdown-divider'));
                            uniqueValues.each(function(value) {
                                if (value && value.toString().trim() !== '') {
                                    dropdown.append($('<div>').addClass('dropdown-item checkbox-item').html('<label class="d-flex align-items-center m-0 w-100"><input type="checkbox" class="me-2 filter-checkbox" value="' + value + '" checked> ' + value + '</label>').css({'cursor':'pointer'}));
                                }
                            });
                            searchBox.on('keyup', function() {
                                const filter = $(this).val().toLowerCase();
                                dropdown.find('.checkbox-item').each(function() {
                                    $(this).toggle($(this).text().toLowerCase().includes(filter));
                                });
                            });
                        }
                        
                        dropdown.on('click', '.filter-checkbox', e => e.stopPropagation());
                        dropdown.on('change', '.select-all', function() {
                            const isChecked = $(this).is(':checked');
                            dropdown.find('.filter-checkbox:not(.select-all)').prop('checked', isChecked);
                            applyFilter();
                        });
                        dropdown.on('change', '.filter-checkbox:not(.select-all)', function() {
                            const allChecked = dropdown.find('.filter-checkbox:not(.select-all):checked').length === dropdown.find('.filter-checkbox:not(.select-all)').length;
                            dropdown.find('.select-all').prop('checked', allChecked);
                            applyFilter();
                        });
                        
                        function applyFilter() {
                            const checkedValues = [];
                            dropdown.find('.filter-checkbox:not(.select-all):checked').each(function() { checkedValues.push($(this).val()); });
                            if (checkedValues.length === 0 || checkedValues.length === dropdown.find('.filter-checkbox:not(.select-all)').length) {
                                column.search('').draw();
                                filterBtn.removeClass('active').css('background-color', '');
                            } else {
                                column.search('^' + checkedValues.join('|') + '$', true, false).draw();
                                filterBtn.addClass('active').css('background-color', '#20b2aa');
                            }
                        }
                        
                        filterBtn.on('click', function(e) {
                            e.stopPropagation();
                            $('.column-filter-menu').not(dropdown).removeClass('show');
                            dropdown.toggleClass('show');
                            if (dropdown.hasClass('show')) {
                                populateDropdown();
                                $(document).one('click', () => dropdown.removeClass('show'));
                            }
                        });
                        $('body').append(dropdown);
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
