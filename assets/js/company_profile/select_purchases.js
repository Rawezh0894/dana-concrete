let purchasesTable = null;

function loadPurchases() {
    // Destroy existing table if it exists
    if (purchasesTable) {
        purchasesTable.destroy();
        purchasesTable = null;
        $('#purchasesTable').empty();
    }
    
    const url = new URL('../process/company_profile/select_purchases.php', window.location.href);
    url.searchParams.append('id', COMPANY_ID);
    if (typeof currentFilters !== 'undefined') {
        if (currentFilters.from_date) url.searchParams.append('from_date', currentFilters.from_date);
        if (currentFilters.to_date) url.searchParams.append('to_date', currentFilters.to_date);
    }
    
    fetch(url)
        .then(res => res.json())
        .then(purchases => {
            if (purchases.error) {
                $('#purchasesTable').html(`<tr><td colspan="18" class="text-danger text-center">${purchases.error}</td></tr>`);
                return;
            }
            
            if (!purchases || purchases.length === 0) {
                $('#purchasesTable').html(`<tr><td colspan="18" class="text-muted text-center">هیچ زانیارییەک نەدۆزرایەوە</td></tr>`);
                return;
            }
            
            function formatNumber(n) {
                if (n === null || n === undefined || n === '') return '';
                return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }
            
            function formatUSD(n) {
                if (!n || isNaN(n)) return '';
                return formatNumber(Number(n).toFixed(2)) + ' $';
            }
            
            function formatIQD(n) {
                if (!n || isNaN(n)) return '';
                return formatNumber(Number(n).toFixed(0)) + ' د.ع';
            }
            
            // Prepare data for DataTables
            const tableData = purchases.map((row) => [
                row.company_name || '',
                row.location_name || row.location || '',
                row.driver_name || row.driver || '',
                row.invoice_number || '',
                row.material_name || '',
                row.date || '',
                row.payment_type || '',
                row.type || '',
                formatNumber(row.kg),
                formatUSD(row.price_per_kg_usd),
                formatIQD(row.price_per_kg_iqd),
                row.type === 'دینار' ? formatIQD(row.price) : (row.type === 'دۆلار' ? formatUSD(row.price) : formatNumber(row.price)),
                formatIQD(row.amount_iqd),
                formatNumber(row.exchange_rate),
                formatUSD(row.paid_usd),
                formatIQD(row.paid_iqd),
                formatUSD(row.remaining_usd),
                formatIQD(row.remaining_iqd)
            ]);
            
            // Initialize DataTable
            purchasesTable = new DataTable('#purchasesTable', {
                data: tableData,
                columns: [
                    { title: 'کۆمپانیا' },
                    { title: 'شوێن' },
                    { title: 'شۆفێر' },
                    { title: 'ژمارەی پسوڵە' },
                    { title: 'مەواد' },
                    { title: 'بەروار' },
                    { title: 'جۆری پارەدان' },
                    { title: 'جۆری دراو' },
                    { title: 'کیلۆگرام' },
                    { title: 'نرخی یەک کیلۆ بە دۆلار' },
                    { title: 'نرخی یەک کیلۆ بە دینار' },
                    { title: 'نرخ' },
                    { title: 'بڕی پارە بە دینار' },
                    { title: 'نرخی 100 دۆلار بە دینار' },
                    { title: 'پارەی دراو بە دۆلار' },
                    { title: 'پارەی دراو بە دینار' },
                    { title: 'پارەی ماوە بە دۆلار' },
                    { title: 'پارەی ماوە بە دینار' }
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
                order: [[5, 'desc']], // Sort by date descending
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
            console.error('Error loading purchases:', error);
            $('#purchasesTable').html(`<tr><td colspan="18" class="text-danger text-center">هەڵە لە بارکردنی زانیاریەکان</td></tr>`);
        });
}

// Load purchases when tab is shown
$(document).on('shown.bs.tab', 'button[data-bs-target="#purchases"]', function() {
    if (!purchasesTable) {
        loadPurchases();
    }
});

// Also load on page ready if purchases tab is active
$(document).ready(function() {
    if ($('#purchases').hasClass('active')) {
        loadPurchases();
    }
});
