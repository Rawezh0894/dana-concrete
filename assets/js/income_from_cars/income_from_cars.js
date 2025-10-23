$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        dir: 'rtl'
    });

    // Charts
    let carsChart = null;
    let driversChart = null;
    
    // Cache for filter data
    const filterCache = {
        cars: null,
        drivers: null,
        customers: null,
        lastUpdated: null
    };
    
    // Loading states
    let isLoadingData = false;
    let isLoadingFilters = false;

    async function loadData(page = 1, limit = 50) {
        if (isLoadingData) return;
        
        isLoadingData = true;
        showLoadingStates();
        
        const filters = {
            mixer_car_id: $('#mixerCarFilter').val(),
            mixer_driver_id: $('#mixerDriverFilter').val(),
            pump_car_id: $('#pumpCarFilter').val(),
            pump_driver_id: $('#pumpDriverFilter').val(),
            customer_id: $('#customerFilter').val(),
            from_date: $('#fromDate').val(),
            to_date: $('#toDate').val(),
            page: page,
            limit: limit
        };

        try {
            const response = await fetch(`../process/income_from_cars/get_informations.php?${new URLSearchParams(filters)}`);
            const result = await response.json();

            if (result.success) {
                displayData(result.data, result.pagination);
                updateSummary(result.summary);
                
                // Load charts only on first page load or when filters change
                if (page === 1) {
                    updateCharts(result.charts);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: result.error || 'هەڵەیەک ڕویدا!'
                });
            }
        } catch (error) {
            console.error('Error loading data:', error);
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'هەڵەیەک لە وەرگرتنی زانیاریەکان ڕویدا!'
            });
        } finally {
            isLoadingData = false;
            hideLoadingStates();
        }
    }

    async function loadFilters() {
        if (isLoadingFilters) return;
        
        // Check cache first (valid for 5 minutes)
        const cacheValid = filterCache.lastUpdated && 
            (Date.now() - filterCache.lastUpdated) < 300000; // 5 minutes
        
        if (cacheValid && filterCache.cars && filterCache.drivers && filterCache.customers) {
            populateFiltersFromCache();
            return;
        }
        
        isLoadingFilters = true;
        showFilterLoadingStates();
        
        try {
            // Load all filters in parallel
            const [carsResponse, driversResponse, customersResponse] = await Promise.all([
                fetch('../process/car/select_car.php'),
                fetch('../process/employee/select_employee.php'),
                fetch('../process/customer/select_customer.php')
            ]);
            
            const [cars, driversResult, customers] = await Promise.all([
                carsResponse.json(),
                driversResponse.json(),
                customersResponse.json()
            ]);
            
            // Cache the results
            filterCache.cars = cars;
            filterCache.drivers = driversResult.employees || [];
            filterCache.customers = customers;
            filterCache.lastUpdated = Date.now();
            
            populateFiltersFromCache();
            
        } catch (error) {
            console.error('Error loading filters:', error);
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'هەڵەیەک لە وەرگرتنی فلتەرەکان ڕویدا!'
            });
        } finally {
            isLoadingFilters = false;
            hideFilterLoadingStates();
        }
    }
    
    function populateFiltersFromCache() {
        // Populate cars
        if (filterCache.cars && filterCache.cars.length > 0) {
            $('#mixerCarFilter, #pumpCarFilter').each(function() {
                $(this).empty().append('<option value="">هەموو سەیارەکان</option>');
                filterCache.cars.forEach(car => {
                    $(this).append(`<option value="${car.id}">${car.name}</option>`);
                });
            });
        }
        
        // Populate drivers
        if (filterCache.drivers && filterCache.drivers.length > 0) {
            $('#mixerDriverFilter, #pumpDriverFilter').each(function() {
                $(this).empty().append('<option value="">هەموو شۆفێران</option>');
                filterCache.drivers.forEach(driver => {
                    $(this).append(`<option value="${driver.id}">${driver.name}</option>`);
                });
            });
        }
        
        // Populate customers
        if (filterCache.customers && filterCache.customers.length > 0) {
            $('#customerFilter').empty().append('<option value="">هەموو کڕیارەکان</option>');
            filterCache.customers.forEach(customer => {
                $('#customerFilter').append(`<option value="${customer.id}">${customer.name}</option>`);
            });
        }
        
        // Reinitialize Select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            dir: 'rtl'
        });
    }

    function displayData(data, pagination) {
        const columns = [
            'receipt_number', 'customer_name', 'location', 'meter_amount', 
            'mixer_car_name', 'mixer_driver_name', 'pump_car_name', 
            'pump_driver_name', 'created_at', 'receiver_name'
        ];

        const mappedData = data.map(row => ({
            receipt_number: row.receipt_number,
            customer_name: row.customer_name || '-',
            location: row.location,
            meter_amount: `${row.meter_amount} م³`,
            mixer_car_name: row.mixer_car_name || '-',
            mixer_driver_name: row.mixer_driver_name || '-',
            pump_car_name: row.pump_car_name || '-',
            pump_driver_name: row.pump_driver_name || '-',
            created_at: formatDate(row.created_at),
            receiver_name: row.receiver_name || '-'
        }));

        TableController.renderWithPagination('#incomeTable', mappedData, columns, { 
            pageSize: pagination.per_page,
            currentPage: pagination.current_page,
            onRenderComplete: () => {
                // Update table info with pagination data
                $('#tableInfo').text(`کۆی: ${pagination.total_records} تۆمار`);
            }
        });
    }
    
    function showLoadingStates() {
        // Show skeleton for summary cards
        $('#summary-cards .card-body').each(function() {
            $(this).html('<div class="skeleton-loader"></div>');
        });
        
        // Show skeleton for charts
        $('#carsChart, #driversChart').each(function() {
            $(this).parent().html('<div class="chart-skeleton"></div>');
        });
        
        // Show loading for table
        TableController.showLoading('#incomeTable', [
            'receipt_number', 'customer_name', 'location', 'meter_amount', 
            'mixer_car_name', 'mixer_driver_name', 'pump_car_name', 
            'pump_driver_name', 'created_at', 'receiver_name'
        ]);
    }
    
    function hideLoadingStates() {
        // Remove skeleton loaders
        $('.skeleton-loader, .chart-skeleton').remove();
    }
    
    function showFilterLoadingStates() {
        $('.select2').each(function() {
            $(this).next('.select2-container').addClass('loading');
        });
    }
    
    function hideFilterLoadingStates() {
        $('.select2-container').removeClass('loading');
    }

    function updateSummary(summary) {
        $('#totalCars').text(summary.totalCars);
        $('#totalDrivers').text(summary.totalDrivers);
        $('#totalMeters').text(summary.totalMeters.toLocaleString('ku-IQ'));
        $('#totalReceipts').text(summary.totalReceipts);
    }

    function updateCharts(charts) {
        updateCarsChart(charts.cars);
        updateDriversChart(charts.drivers);
    }

    function updateCarsChart(carsData) {
        const ctx = document.getElementById('carsChart').getContext('2d');
        
        if (carsChart) {
            carsChart.destroy();
        }

        const labels = Object.values(carsData).map(car => car.name);
        const data = Object.values(carsData).map(car => car.meters);

        carsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'مەتر سێج',
                    data: data,
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.parsed.y.toLocaleString('ku-IQ')} م³`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('ku-IQ') + ' م³';
                            }
                        }
                    }
                }
            }
        });
    }

    function updateDriversChart(driversData) {
        const ctx = document.getElementById('driversChart').getContext('2d');
        
        if (driversChart) {
            driversChart.destroy();
        }

        const labels = Object.values(driversData).map(driver => driver.name);
        const data = Object.values(driversData).map(driver => driver.meters);

        driversChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40',
                        '#FF6384',
                        '#C9CBCF',
                        '#4BC0C0',
                        '#FF6384'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed.toLocaleString('ku-IQ')} م³ (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('ku-IQ');
    }

    // Export to Excel function
    window.exportToExcel = function() {
        const filters = {
            mixer_car_id: $('#mixerCarFilter').val(),
            mixer_driver_id: $('#mixerDriverFilter').val(),
            pump_car_id: $('#pumpCarFilter').val(),
            pump_driver_id: $('#pumpDriverFilter').val(),
            customer_id: $('#customerFilter').val(),
            from_date: $('#fromDate').val(),
            to_date: $('#toDate').val()
        };

        // Create a temporary form to submit the export request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../process/income_from_cars/export_excel.php';
        form.target = '_blank';

        // Add filter parameters as hidden inputs
        Object.keys(filters).forEach(key => {
            if (filters[key]) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = filters[key];
                form.appendChild(input);
            }
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    };

    // Initialize page
    async function initializePage() {
        try {
            // Load filters first (cached)
            await loadFilters();
            
            // Then load initial data
            await loadData(1, 50);
        } catch (error) {
            console.error('Error initializing page:', error);
        }
    }
    
    // Initialize page on load
    initializePage();
    
    // Make loadData available globally
    window.loadData = () => loadData(1, 50);
});
