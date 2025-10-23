$(document).ready(function() {
    // Performance optimization: Initialize components
    let isInitialized = false;
    let cachedData = null;
    let lastLoadTime = 0;
    const CACHE_DURATION = 60000; // 60 seconds cache for better performance
    
    // Initialize Select2 with performance optimizations
    $('.select2').select2({
        theme: 'bootstrap-5',
        dir: 'rtl',
        minimumInputLength: 0,
        cache: true
    });

    // Charts
    let carsChart = null;
    let driversChart = null;
    
    // Performance monitoring
    const performanceMonitor = {
        startTime: 0,
        endTime: 0,
        logPerformance: function(operation) {
            console.log(`${operation} took: ${this.endTime - this.startTime}ms`);
        }
    };

    // Optimized data loading with caching and performance monitoring
    async function loadData(forceRefresh = false) {
        performanceMonitor.startTime = performance.now();
        
        // Check cache first
        const currentTime = Date.now();
        const filters = {
            mixer_car_id: $('#mixerCarFilter').val(),
            mixer_driver_id: $('#mixerDriverFilter').val(),
            pump_car_id: $('#pumpCarFilter').val(),
            pump_driver_id: $('#pumpDriverFilter').val(),
            customer_id: $('#customerFilter').val(),
            from_date: $('#fromDate').val(),
            to_date: $('#toDate').val()
        };
        
        const filterKey = JSON.stringify(filters);
        
        // Use cache if available and not expired
        if (!forceRefresh && cachedData && cachedData.filterKey === filterKey && 
            (currentTime - lastLoadTime) < CACHE_DURATION) {
            console.log('Using cached data');
            displayData(cachedData.data);
            updateSummary(cachedData.summary);
            updateCharts(cachedData.charts);
            performanceMonitor.endTime = performance.now();
            performanceMonitor.logPerformance('Cached data load');
            return;
        }

        try {
            // Show loading state
            showLoadingState();
            
            const response = await fetch(`../process/income_from_cars/get_informations.php?${new URLSearchParams(filters)}`);
            const result = await response.json();

            if (result.success) {
                // Cache the result
                cachedData = {
                    data: result.data,
                    summary: result.summary,
                    charts: result.charts,
                    filterKey: filterKey
                };
                lastLoadTime = currentTime;
                
                displayData(result.data);
                updateSummary(result.summary);
                updateCharts(result.charts);
                
                performanceMonitor.endTime = performance.now();
                performanceMonitor.logPerformance('Fresh data load');
            } else {
                hideLoadingState();
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: result.error || 'هەڵەیەک ڕویدا!'
                });
            }
        } catch (error) {
            hideLoadingState();
            console.error('Error loading data:', error);
            Swal.fire({
                icon: 'error',
                title: 'هەڵە',
                text: 'هەڵەیەک لە وەرگرتنی زانیاریەکان ڕویدا!'
            });
        }
    }

    // Optimized filter loading with parallel requests
    async function loadFilters() {
        performanceMonitor.startTime = performance.now();
        
        try {
            // Load all filters in parallel for better performance
            const [carsResponse, driversResponse, customersResponse] = await Promise.all([
                fetch('../process/car/select_car.php'),
                fetch('../process/employee/select_employee.php'),
                fetch('../process/customer/select_customer.php')
            ]);

            // Process cars
            const cars = await carsResponse.json();
            if (cars && cars.length > 0) {
                const carOptions = cars.map(car => `<option value="${car.id}">${car.name}</option>`).join('');
                $('#mixerCarFilter, #pumpCarFilter').each(function() {
                    $(this).empty().append('<option value="">هەموو سەیارەکان</option>' + carOptions);
                });
            }

            // Process drivers
            const driversResult = await driversResponse.json();
            if (driversResult && driversResult.employees && driversResult.employees.length > 0) {
                const driverOptions = driversResult.employees.map(driver => 
                    `<option value="${driver.id}">${driver.name}</option>`
                ).join('');
                $('#mixerDriverFilter, #pumpDriverFilter').each(function() {
                    $(this).empty().append('<option value="">هەموو شۆفێران</option>' + driverOptions);
                });
            }

            // Process customers
            const customersResult = await customersResponse.json();
            if (customersResult && customersResult.success && customersResult.data && customersResult.data.length > 0) {
                const customerOptions = customersResult.data.map(customer => 
                    `<option value="${customer.id}">${customer.name}</option>`
                ).join('');
                $('#customerFilter').empty().append('<option value="">هەموو کڕیارەکان</option>' + customerOptions);
            }

            // Reinitialize Select2 with performance optimizations
            $('.select2').select2({
                theme: 'bootstrap-5',
                dir: 'rtl',
                minimumInputLength: 0,
                cache: true
            });

            performanceMonitor.endTime = performance.now();
            performanceMonitor.logPerformance('Filters load');

        } catch (error) {
            console.error('Error loading filters:', error);
            performanceMonitor.endTime = performance.now();
            performanceMonitor.logPerformance('Filters load (error)');
        }
    }

    // Optimized data display with enhanced table controller
    function displayData(data) {
        performanceMonitor.startTime = performance.now();
        
        const columns = [
            'receipt_number', 'customer_name', 'location', 'meter_amount', 
            'mixer_car_name', 'mixer_driver_name', 'pump_car_name', 
            'pump_driver_name', 'created_at', 'receiver_name'
        ];

        // Optimize data mapping
        const mappedData = data.map(row => ({
            receipt_number: row.receipt_number,
            customer_name: row.customer_name || '-',
            location: row.location || '-',
            meter_amount: `${parseFloat(row.meter_amount || 0).toFixed(2)} م³`,
            mixer_car_name: row.mixer_car_name || '-',
            mixer_driver_name: row.mixer_driver_name || '-',
            pump_car_name: row.pump_car_name || '-',
            pump_driver_name: row.pump_driver_name || '-',
            created_at: formatDate(row.created_at),
            receiver_name: row.receiver_name || '-'
        }));

        // Use enhanced table controller with performance optimizations
        TableController.renderWithPagination('#incomeTable', mappedData, columns, { 
            pageSize: 25, // Increased page size for better performance
            onRenderComplete: function() {
                hideLoadingState();
                performanceMonitor.endTime = performance.now();
                performanceMonitor.logPerformance('Table render');
            }
        });
        
        // Update table info
        $('#tableInfo').text(`کۆی: ${data.length} تۆمار`);
    }

    function updateSummary(summary) {
        $('#totalCars').text(summary.totalCars);
        $('#totalDrivers').text(summary.totalDrivers);
        $('#totalMeters').text(summary.totalMeters.toLocaleString('ku-IQ'));
        $('#totalReceipts').text(summary.totalReceipts);
    }

    // Optimized chart updates with performance monitoring
    function updateCharts(charts) {
        performanceMonitor.startTime = performance.now();
        
        // Use requestAnimationFrame for smooth chart updates
        requestAnimationFrame(() => {
            updateCarsChart(charts.cars);
            updateDriversChart(charts.drivers);
            
            performanceMonitor.endTime = performance.now();
            performanceMonitor.logPerformance('Charts update');
        });
    }
    
    // Loading state management
    function showLoadingState() {
        $('#incomeTable tbody').html(`
            <tr>
                <td colspan="10" class="text-center text-muted">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        چاوەڕوان بە...
                    </div>
                </td>
            </tr>
        `);
    }
    
    function hideLoadingState() {
        // Loading state will be replaced by table data
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

    // Optimized export function with loading state
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

        // Show loading state for export
        Swal.fire({
            title: 'چاوەڕوان بە...',
            text: 'فایلەکە دانەدەرێت',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

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
        
        // Close loading after a short delay
        setTimeout(() => {
            Swal.close();
        }, 2000);
    };

    // Initialize page with optimized loading
    async function initializePage() {
        if (isInitialized) return;
        
        performanceMonitor.startTime = performance.now();
        
        try {
            // Load filters first, then data
            await loadFilters();
            await loadData();
            
            isInitialized = true;
            performanceMonitor.endTime = performance.now();
            performanceMonitor.logPerformance('Page initialization');
            
        } catch (error) {
            console.error('Error initializing page:', error);
        }
    }

    // Initialize page
    initializePage();

    // Make functions available globally
    window.loadData = loadData;
    window.refreshData = () => loadData(true); // Force refresh
});
