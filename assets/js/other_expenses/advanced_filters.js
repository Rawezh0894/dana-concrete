// Advanced Filters for Other Expenses
class AdvancedFilters {
    constructor() {
        this.filters = {
            dateFrom: '',
            dateTo: '',
            month: '',
            year: '',
            car: '',
            employee: '',
            person: '',
            paymentType: '',
            expenseTypes: [], // Changed to array for multiple selection
            amountFromIqd: '',
            amountToIqd: '',
            amountFromUsd: '',
            amountToUsd: ''
        };
        this.debounceTimer = null;
        this.init();
    }

    init() {
        this.setupEventListeners();

        this.populateEntityFilters();
        this.setDefaultDates();
    }

    setupEventListeners() {
        // Filter buttons
        document.getElementById('clearFilters')?.addEventListener('click', () => this.clearFilters());
        document.getElementById('exportReport')?.addEventListener('click', () => this.exportReport());


        // Date filters - auto apply with debouncing
        document.getElementById('dateFrom')?.addEventListener('change', (e) => {
            this.filters.dateFrom = e.target.value;
            this.updateMonthFilter();
            this.debouncedApplyFilters();
        });

        document.getElementById('dateTo')?.addEventListener('change', (e) => {
            this.filters.dateTo = e.target.value;
            this.updateMonthFilter();
            this.debouncedApplyFilters();
        });

        document.getElementById('monthFilter')?.addEventListener('change', (e) => {
            this.filters.month = e.target.value;
            this.updateDateRangeFromMonth();
            this.debouncedApplyFilters();
        });



        // Entity filters - auto apply with debouncing
        $('#carFilter').on('change', (e) => {
            this.filters.car = e.target.value;
            this.debouncedApplyFilters();
        });

        $('#employeeFilter').on('change', (e) => {
            this.filters.employee = e.target.value;
            this.debouncedApplyFilters();
        });

        $('#personFilter').on('change', (e) => {
            this.filters.person = e.target.value;
            this.debouncedApplyFilters();
        });

        $('#paymentTypeFilter').on('change', (e) => {
            this.filters.paymentType = e.target.value;
            this.debouncedApplyFilters();
        });

        // Expense type checkboxes - auto apply with debouncing
        document.getElementById('expenseTypeOther')?.addEventListener('change', (e) => {
            this.updateExpenseTypeFilters();
            this.debouncedApplyFilters();
        });
        document.getElementById('expenseTypeMaterial')?.addEventListener('change', (e) => {
            this.updateExpenseTypeFilters();
            this.debouncedApplyFilters();
        });
        document.getElementById('expenseTypeGas')?.addEventListener('change', (e) => {
            this.updateExpenseTypeFilters();
            this.debouncedApplyFilters();
        });


    }

    setDefaultDates() {
        const today = new Date();
        const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);

        document.getElementById('dateFrom').value = this.formatDate(firstDayOfMonth);
        document.getElementById('dateTo').value = this.formatDate(today);

        this.filters.dateFrom = this.formatDate(firstDayOfMonth);
        this.filters.dateTo = this.formatDate(today);
    }

    updateExpenseTypeFilters() {
        this.filters.expenseTypes = [];
        const checkboxes = [
            'expenseTypeOther',
            'expenseTypeMaterial',
            'expenseTypeGas'
        ];

        checkboxes.forEach(id => {
            const checkbox = document.getElementById(id);
            if (checkbox && checkbox.checked) {
                this.filters.expenseTypes.push(checkbox.value);
            }
        });
    }

    debouncedApplyFilters() {
        // Clear existing timer
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }

        // Set new timer
        this.debounceTimer = setTimeout(() => {
            this.applyFilters();
        }, 300); // 300ms delay
    }



    async populateEntityFilters() {
        try {
            // Populate car filter
            const carsResponse = await fetch('../process/car/select_car.php');
            const cars = await carsResponse.json();
            this.populateSelect('carFilter', cars, 'id', 'name');

            // Populate employee filter
            const employeesResponse = await fetch('../process/employee/select_employee.php');
            const employeesData = await employeesResponse.json();
            // Handle new response format with employees and summary
            const employees = employeesData.employees || employeesData;
            this.populateSelect('employeeFilter', employees, 'id', 'name');

            // Populate person filter
            const personsResponse = await fetch('../process/other_expenses/select_persons.php');
            const persons = await personsResponse.json();
            this.populateSelect('personFilter', persons, 'id', 'name');

        } catch (error) {
            console.error('Error populating entity filters:', error);
        }
    }

    populateSelect(selectId, data, valueKey, textKey) {
        const select = document.getElementById(selectId);
        if (!select) return;

        // Keep the first option (default)
        const defaultOption = select.querySelector('option');
        select.innerHTML = '';
        if (defaultOption) {
            select.appendChild(defaultOption);
        }

        // Ensure data is an array
        if (!Array.isArray(data)) {
            console.warn(`Data for ${selectId} is not an array:`, data);
            return;
        }

        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueKey];
            option.textContent = item[textKey];
            select.appendChild(option);
        });

        // Trigger Select2 change event if initialized
        if ($(select).hasClass('select2-hidden-accessible')) {
            $(select).trigger('change');
        }
        // Trigger native change event for any vanilla listeners
        select.dispatchEvent(new Event('change'));
    }

    updateMonthFilter() {
        if (this.filters.dateFrom && this.filters.dateTo) {
            const fromDate = new Date(this.filters.dateFrom);
            const toDate = new Date(this.filters.dateTo);

            // If both dates are in the same month, set month filter
            if (fromDate.getFullYear() === toDate.getFullYear() &&
                fromDate.getMonth() === toDate.getMonth()) {
                const monthValue = `${fromDate.getFullYear()}-${String(fromDate.getMonth() + 1).padStart(2, '0')}`;
                document.getElementById('monthFilter').value = monthValue;
                this.filters.month = monthValue;
            } else {
                document.getElementById('monthFilter').value = '';
                this.filters.month = '';
            }
        }
    }

    updateDateRangeFromMonth() {
        if (this.filters.month) {
            const [year, month] = this.filters.month.split('-');
            const firstDay = new Date(parseInt(year), parseInt(month) - 1, 1);
            const lastDay = new Date(parseInt(year), parseInt(month), 0);

            document.getElementById('dateFrom').value = this.formatDate(firstDay);
            document.getElementById('dateTo').value = this.formatDate(lastDay);

            this.filters.dateFrom = this.formatDate(firstDay);
            this.filters.dateTo = this.formatDate(lastDay);
        }
    }



    formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    async applyFilters() {
        try {
            console.log('Applying filters:', this.filters);

            // Build query string
            const queryParams = new URLSearchParams();
            Object.entries(this.filters).forEach(([key, value]) => {
                if (value) {
                    if (key === 'expenseTypes' && Array.isArray(value) && value.length > 0) {
                        value.forEach(type => {
                            queryParams.append('expenseTypes[]', type);
                        });
                    } else if (key !== 'expenseTypes') {
                        queryParams.append(key, value);
                    }
                }
            });

            // Reload AG Grid with filtered data
            window.currentFilters = queryParams.toString();
            if (typeof reloadOtherExpenses === 'function') {
                reloadOtherExpenses();
            } else if (typeof loadOtherExpenses === 'function') {
                await loadOtherExpenses();
            }

        } catch (error) {
            console.error('Error applying filters:', error);
            this.showError('هەڵەیەک ڕویدا لە جێبەجێکردنی فلتەرەکان');
        }
    }

    // displayFilteredData and createExpenseRow removed - DataTables handles this now

    async updateSummaryCards(summary) {
        if (summary) {
            // Get USD rate from the exchange rate input field in the add modal
            let usdRate = 139250; // fallback default
            const exchangeRateInput = document.getElementById('exchange_rate');
            if (exchangeRateInput && exchangeRateInput.value) {
                usdRate = parseFloat(exchangeRateInput.value);
                console.log('Using exchange rate from input field:', usdRate);
            } else {
                // Fallback to API if input field is empty
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

            function formatUSD(num) {
                return num ? `$${Number(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : '$0.00';
            }

            function iqdToUsd(iqd) {
                return usdRate && iqd ? (parseFloat(iqd) / (usdRate / 100)) : 0;
            }

            // Calculate totals using the same logic as select_expenses.js
            const totalCarMaterialCostIQD = parseFloat(summary.total_car_material_cost_iqd || 0);
            const totalCarMaterialCostUSD = parseFloat(summary.total_car_material_cost_usd || 0);
            const totalCarGasCost = parseFloat(summary.total_car_gas_cost || 0);
            const totalOtherExpensesIQD = parseFloat(summary.total_other_expenses_iqd || 0);
            const totalOtherExpensesUSD = parseFloat(summary.total_other_expenses_usd || 0);

            // Convert IQD to USD for display (same formula as select_expenses.js)
            const totalCarMaterialCostUSDConverted = iqdToUsd(totalCarMaterialCostIQD) + totalCarMaterialCostUSD;
            const totalCarGasCostUSD = iqdToUsd(totalCarGasCost);
            const totalOtherExpensesUSDConverted = iqdToUsd(totalOtherExpensesIQD) + totalOtherExpensesUSD;
            const totalCarExpensesUSD = totalCarMaterialCostUSDConverted + totalCarGasCostUSD;
            const totalAllExpensesUSD = totalOtherExpensesUSDConverted + totalCarExpensesUSD;

            // Calculate total IQD and USD expenses
            const totalExpensesIQD = totalCarMaterialCostIQD + totalCarGasCost + totalOtherExpensesIQD;
            const totalExpensesUSD = totalCarMaterialCostUSD + totalOtherExpensesUSD;

            function formatIQD(num) {
                return num ? `${Number(num).toLocaleString('en-US')} د.ع` : '0 د.ع';
            }

            // Update car expense cards (including new IQD and USD total cards)
            const elements = {
                'totalCarMaterialCost': formatUSD(totalCarMaterialCostUSDConverted),
                'totalCarGasCost': formatUSD(totalCarGasCostUSD),
                'totalOtherExpenses': formatUSD(totalOtherExpensesUSDConverted),
                'totalCarExpenses': formatUSD(totalAllExpensesUSD),
                'totalExpensesIQD': formatIQD(totalExpensesIQD),
                'totalExpensesUSD': formatUSD(totalExpensesUSD)
            };

            // Safely update each element
            Object.entries(elements).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element) {
                    element.textContent = value;
                }
            });

            // Update USD exchange rate card
            const usdRateElement = document.getElementById('usdExchangeRate');
            if (usdRateElement) {
                usdRateElement.textContent = `${Number(usdRate).toLocaleString('en-US')} د.ع`;
            }
        }
    }

    clearFilters() {
        // Reset all filter values
        Object.keys(this.filters).forEach(key => {
            if (key === 'expenseTypes') {
                this.filters[key] = [];
            } else {
                this.filters[key] = '';
            }
        });

        // Clear form inputs
        const formElements = [
            'dateFrom', 'dateTo', 'monthFilter', 'carFilter', 'employeeFilter', 'personFilter', 'paymentTypeFilter'
        ];

        formElements.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.value = '';
                // Trigger Select2 change event
                if ($(element).hasClass('select2-hidden-accessible')) {
                    $(element).trigger('change');
                }
            }
        });

        // Clear expense type checkboxes
        const checkboxes = [
            'expenseTypeOther', 'expenseTypeMaterial', 'expenseTypeGas'
        ];

        checkboxes.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.checked = false;
            }
        });

        // Set default dates
        this.setDefaultDates();

        // Clear current filters and reload
        window.currentFilters = '';
        if (typeof reloadOtherExpenses === 'function') {
            reloadOtherExpenses();
        } else if (typeof loadOtherExpenses === 'function') {
            loadOtherExpenses();
        }

        this.showSuccess('فلتەرەکان سڕایەوە');
    }

    async exportReport() {
        try {
            const queryParams = new URLSearchParams();
            Object.entries(this.filters).forEach(([key, value]) => {
                if (value) {
                    if (key === 'expenseTypes' && Array.isArray(value) && value.length > 0) {
                        // Handle array of expense types
                        value.forEach(type => {
                            queryParams.append('expenseTypes[]', type);
                        });
                    } else if (key !== 'expenseTypes') {
                        queryParams.append(key, value);
                    }
                }
            });

            const response = await fetch(`../process/other_expenses/export_report.php?${queryParams}`);
            const blob = await response.blob();

            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `other_expenses_report_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            this.showSuccess('ڕاپۆرت بە سەرکەوتوویی داگرا');
        } catch (error) {
            console.error('Error exporting report:', error);
            this.showError('هەڵەیەک ڕویدا لە داگرتنی ڕاپۆرت');
        }
    }



    showLoading() {
        // Show loading indicator in the table area
        const table = document.getElementById('otherExpensesTable');
        if (table) {
            const loadingRow = document.createElement('tr');
            loadingRow.id = 'loadingRow';
            loadingRow.innerHTML = `
                <td colspan="25" class="text-center py-4">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="fas fa-spinner fa-spin me-3" style="color: var(--seafoam-green); font-size: 1.2rem;"></i>
                        <span style="color: var(--seafoam-green); font-weight: 500;">جێبەجێکردنی فلتەر...</span>
                    </div>
                </td>
            `;
            table.querySelector('tbody').appendChild(loadingRow);
        }
    }

    hideLoading() {
        // Remove loading indicator
        const loadingRow = document.getElementById('loadingRow');
        if (loadingRow) {
            loadingRow.remove();
        }
    }

    showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'سەرکەوتوو!',
            text: message,
            timer: 2000,
            showConfirmButton: false
        });
    }

    showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'هەڵە!',
            text: message
        });
    }
}

// Initialize advanced filters when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.advancedFilters = new AdvancedFilters();
}); 