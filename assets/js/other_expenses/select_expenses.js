let otherExpensesTable = null;

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
        console.log('Loading other expenses...');
        
        // Destroy existing table if it exists
        if (otherExpensesTable) {
            otherExpensesTable.destroy();
            otherExpensesTable = null;
            $('#otherExpensesTable').empty();
        }
        
        // Get USD rate
        let usdRate = 139250;
        const exchangeRateInput = document.getElementById('exchange_rate');
        if (exchangeRateInput && exchangeRateInput.value) {
            usdRate = parseFloat(exchangeRateInput.value);
        } else {
            try {
                const rateRes = await fetch('../process/purchase_materilas/get_usd_rate.php');
                const rateData = await rateRes.json();
                if (rateData.success && rateData.rate) {
                    usdRate = parseFloat(rateData.rate);
                } else if (rateData.default_rate) {
                    usdRate = parseFloat(rateData.default_rate);
                }
            } catch (e) {
                // fallback to default
            }
        }

        const monthFilter = document.getElementById('monthFilter');
        
        const res = await fetch('../process/other_expenses/select_expenses.php');
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const result = await res.json();
        
        let data;
        if (Array.isArray(result)) {
            data = result;
        } else if (result.success && Array.isArray(result.expenses)) {
            data = result.expenses;
        } else {
            console.error('Unexpected data format:', result);
            $('#otherExpensesTable').html(`<tr><td colspan="26" class="text-muted text-center">هیچ زانیارییەک نەدۆزرایەوە</td></tr>`);
            return;
        }
        
        window.otherExpensesData = data;
        
        function iqdToUsd(iqd) {
            return usdRate && iqd ? (parseFloat(iqd) / (usdRate / 100)) : 0;
        }
        
        let filtered = data;
        if (monthFilter && monthFilter.value) {
            const [year, month] = monthFilter.value.split('-');
            filtered = data.filter(row => row.date && row.date.startsWith(`${year}-${month}`));
        }
        
        // Calculate totals
        let totalCarMaterialCostIQD = 0, totalCarMaterialCostUSD = 0, totalCarGasCost = 0;
        let totalOtherExpensesIQD = 0, totalOtherExpensesUSD = 0;
        
        filtered.forEach(row => {
            if (row.car_id && row.expense_type === 'بەکارهێنانی کاڵای کۆگا') {
                totalCarMaterialCostIQD += parseFloat(row.material_purchase_price_iqd || 0) * parseFloat(row.material_quantity || 0);
                totalCarMaterialCostUSD += parseFloat(row.material_purchase_price_usd || 0) * parseFloat(row.material_quantity || 0);
            }
            
            if (row.car_id && row.expense_type === 'بەکارهێنانی گاز') {
                totalCarGasCost += parseFloat(row.gas_total_cost || 0);
            }
            
            if (!row.car_id || (row.expense_type !== 'بەکارهێنانی کاڵای کۆگا' && row.expense_type !== 'بەکارهێنانی گاز')) {
                if (row.currency_type === 'دۆلار') {
                    totalOtherExpensesUSD += parseFloat(row.amount_usd || 0);
                } else {
                    totalOtherExpensesIQD += parseFloat(row.amount_iqd || 0);
                }
            }
        });
        
        const totalCarMaterialCostUSDConverted = totalCarMaterialCostIQD / (usdRate / 100) + totalCarMaterialCostUSD;
        const totalCarGasCostUSD = totalCarGasCost / (usdRate / 100);
        const totalOtherExpensesUSDConverted = totalOtherExpensesIQD / (usdRate / 100) + totalOtherExpensesUSD;
        const totalCarExpensesUSD = totalCarMaterialCostUSDConverted + totalCarGasCostUSD;
        const totalAllExpensesUSD = totalOtherExpensesUSDConverted + totalCarExpensesUSD;
        
        document.getElementById('totalCarMaterialCost').innerHTML = `${formatUSD(totalCarMaterialCostUSDConverted)}`;
        document.getElementById('totalCarGasCost').innerHTML = `${formatUSD(totalCarGasCostUSD)}`;
        document.getElementById('totalOtherExpenses').innerHTML = `${formatUSD(totalOtherExpensesUSDConverted)}`;
        document.getElementById('totalCarExpenses').innerHTML = `${formatUSD(totalAllExpensesUSD)}`;
        document.getElementById('usdExchangeRate').innerHTML = `${formatNumber(usdRate)} د.ع`;
        
        if (!filtered || filtered.length === 0) {
            $('#otherExpensesTable').html(`<tr><td colspan="26" class="text-muted text-center">هیچ زانیارییەک نەدۆزرایەوە</td></tr>`);
            return;
        }
        
        const tableData = filtered.map((row) => [
            row.purpose || '',
            row.person_name || '',
            row.employee_name || '',
            row.car_name || '',
            row.gas_liters ? formatNumber(row.gas_liters) : '',
            row.expense_type || '',
            row.material_name || '',
            row.material_quantity ? formatNumber(row.material_quantity) : '',
            row.material_purchase_price_iqd ? formatIQD(row.material_purchase_price_iqd) : '',
            row.material_purchase_price_usd ? formatUSD(row.material_purchase_price_usd) : '',
            row.material_total_cost ? formatNumber(row.material_total_cost) : '',
            row.gas_purchase_price_input ? formatIQD(row.gas_purchase_price_input) : '',
            row.gas_total_cost ? formatNumber(row.gas_total_cost) : '',
            row.payment_type || '',
            row.currency_type || '',
            row.invoice_number || '',
            row.amount_iqd ? formatIQD(row.amount_iqd) : '',
            row.amount_usd ? formatUSD(row.amount_usd) : '',
            row.paid_iqd ? formatIQD(row.paid_iqd) : '',
            row.paid_usd ? formatUSD(row.paid_usd) : '',
            row.exchange_rate ? formatNumber(row.exchange_rate) : '',
            row.remaining_iqd ? formatIQD(row.remaining_iqd) : '',
            row.remaining_usd ? formatUSD(row.remaining_usd) : '',
            row.date || '',
            `<button class="btn btn-sm btn-danger delete-expense" data-id="${row.id}"><i class="fa fa-trash"></i></button> <button class="btn btn-sm btn-primary edit-expense" data-id="${row.id}"><i class="fa fa-edit"></i></button>`,
            row.id
        ]);
        
        otherExpensesTable = new DataTable('#otherExpensesTable', {
            data: tableData,
            columns: [
                { title: 'مەبەست' },
                { title: 'کەس' },
                { title: 'کارمەند' },
                { title: 'سەیارە' },
                { title: 'بڕی گاز (لیتر)' },
                { title: 'جۆری خەرجی' },
                { title: 'کاڵا لە کۆگا' },
                { title: 'بڕی عەدەدی کاڵا' },
                { title: 'نرخی کڕینی کaڵa بە دینار' },
                { title: 'نrخی کڕینی کaڵa بە دۆlار' },
                { title: 'کۆی نرخی کاڵای بەکارهاتuو' },
                { title: 'ئینپوتی نرخی کڕینی گاز' },
                { title: 'کۆی نرخی گازی بەکارهاتuو' },
                { title: 'جۆری مامەڵە' },
                { title: 'جۆری پارە' },
                { title: 'ژمارەی وەسڵ' },
                { title: 'بڕی دینار' },
                { title: 'بڕی دۆlار' },
                { title: 'پارەی دراو دینار' },
                { title: 'پارەی دراو دۆlار' },
                { title: 'نرخی 100 دۆlار' },
                { title: 'ماوە دینار' },
                { title: 'ماuە دۆلار' },
                { title: 'بەروار' },
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
            order: [[23, 'desc']],
            orderMulti: true,
            dom: 'Bfrtip',
            buttons: [
                { extend: 'copy', text: 'لەبەرگرتنەوە', className: 'btn btn-sm btn-outline-secondary' },
                { extend: 'csv', text: 'CSV', className: 'btn btn-sm btn-outline-secondary' },
                { extend: 'excel', text: 'Excel', className: 'btn btn-sm btn-outline-success' },
                { extend: 'print', text: 'پرینت', className: 'btn btn-sm btn-outline-primary' }
            ],
            rowCallback: function(row, data) {
                const rowId = data[25];
                $(row).find('button.delete-expense').attr('data-id', rowId).off('click').on('click', function() {
                    if (typeof deleteExpense === 'function') deleteExpense(rowId);
                });
                $(row).find('button.edit-expense').attr('data-id', rowId).off('click').on('click', async function() {
                    if (typeof openEditModalById === 'function') await openEditModalById(rowId);
                });
            },
            initComplete: function() {
                this.api().columns().every(function() {
                    const column = this;
                    const header = $(column.header());
                    if (header.text().includes('کردارەکان')) return;
                    
                    const searchInput = $('<input>')
                        .attr('type', 'text')
                        .attr('placeholder', 'فلتەر...')
                        .addClass('form-control form-control-sm mt-1 column-filter')
                        .css({ 'width': '100%', 'padding': '0.25rem 0.5rem', 'border': '1px solid #ced4da', 'border-radius': '0.25rem' });
                    header.append(searchInput);
                    searchInput.on('keyup change', function() {
                        column.search(this.value).draw();
                    });
                });
            }
        });
        
    } catch (err) {
        console.error('Error loading other expenses:', err);
        $('#otherExpensesTable').html(`<tr><td colspan="26" class="text-danger text-center">هەڵە لە بارکردنی زانیاریەکان</td></tr>`);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadOtherExpenses();
    const monthFilter = document.getElementById('monthFilter');
    if (monthFilter) {
        monthFilter.addEventListener('change', function() {
            if (otherExpensesTable) loadOtherExpenses();
        });
    }
});

async function populateSelect(url, selectId, selectedId) {
    try {
        const res = await fetch(url);
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
        const data = await res.json();
        const select = document.getElementById(selectId);
        if (!select) return;
        select.innerHTML = '<option value="">-- هەلبژێرە --</option>';
        data.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.name;
            if (selectedId && String(item.id) === String(selectedId)) opt.selected = true;
            select.appendChild(opt);
        });
    } catch (err) {
        console.error('Error populating select:', err);
    }
}

window.openEditModalById = async function(id) {
    try {
        const dataSource = window.otherExpensesData || [];
        const row = dataSource.find(r => String(r.id) === String(id));
        if (!row) {
            console.error('openEditModalById: row not found', { id });
            return;
        }
        
        await populateSelect('../process/other_expenses/select_persons.php', 'edit_person_id', row.person_id);
        await populateSelect('../process/other_expenses/select_employees.php', 'edit_employee_id', row.employee_id);
        await populateSelect('../process/other_expenses/select_cars.php', 'edit_car_id', row.car_id);
        
        document.getElementById('edit_id').value = row.id;
        document.getElementById('edit_purpose').value = row.purpose;
        document.getElementById('edit_payment_type').value = row.payment_type;
        document.getElementById('edit_currency_type').value = row.currency_type;
        document.getElementById('edit_invoice_number').value = row.invoice_number;
        document.getElementById('edit_amount_iqd').value = row.amount_iqd;
        document.getElementById('edit_amount_usd').value = row.amount_usd;
        document.getElementById('edit_paid_iqd').value = row.paid_iqd;
        document.getElementById('edit_paid_usd').value = row.paid_usd;
        document.getElementById('edit_exchange_rate').value = row.exchange_rate;
        document.getElementById('edit_remaining_iqd').value = row.remaining_iqd;
        document.getElementById('edit_remaining_usd').value = row.remaining_usd;
        
        if (document.getElementById('edit_gas_liters')) {
            document.getElementById('edit_gas_liters').value = row.gas_liters || '';
        }
        
        if (document.getElementById('edit_expense_type')) {
            document.getElementById('edit_expense_type').value = row.expense_type || '';
            const event = new Event('change');
            document.getElementById('edit_expense_type').dispatchEvent(event);
            
            if (row.expense_type === 'بەکارهێنانی گاز') {
                setTimeout(() => { 
                    if (typeof populateGasPurchasePrice === 'function') populateGasPurchasePrice('edit'); 
                }, 100);
            }
        }
        
        if (document.getElementById('edit_material_id')) {
            populateSelect('../process/other_expenses/select_materials.php', 'edit_material_id', row.material_id);
            setTimeout(() => {
                if (row.material_id && typeof populateMaterialPrices === 'function') populateMaterialPrices(row.material_id, 'edit');
            }, 100);
        }
        
        if (document.getElementById('edit_material_quantity')) document.getElementById('edit_material_quantity').value = row.material_quantity || '';
        if (document.getElementById('edit_usage_unit_type')) document.getElementById('edit_usage_unit_type').value = row.usage_unit_type || '';
        if (document.getElementById('edit_material_purchase_price_iqd')) document.getElementById('edit_material_purchase_price_iqd').value = row.material_purchase_price_iqd || '';
        if (document.getElementById('edit_material_purchase_price_usd')) document.getElementById('edit_material_purchase_price_usd').value = row.material_purchase_price_usd || '';
        if (document.getElementById('edit_material_total_cost')) document.getElementById('edit_material_total_cost').value = row.material_total_cost || '';
        if (document.getElementById('edit_gas_purchase_price_input')) document.getElementById('edit_gas_purchase_price_input').value = row.gas_purchase_price_input || '';
        if (document.getElementById('edit_gas_total_cost')) document.getElementById('edit_gas_total_cost').value = row.gas_total_cost || '';
        
        document.getElementById('edit_date').value = row.date;
        
        const modal = new bootstrap.Modal(document.getElementById('editExpenseModal'));
        modal.show();
        
        if (typeof setupEditExpenseModal === 'function') setupEditExpenseModal();
    } catch (error) {
        console.error('openEditModalById failed', error);
    }
};

