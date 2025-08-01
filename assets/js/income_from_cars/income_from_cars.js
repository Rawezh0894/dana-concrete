$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        dir: 'rtl'
    });

    // Load initial data
    loadData();
    loadFilters();

    // Charts
    let carsChart = null;
    let driversChart = null;

    async function loadData() {
        const filters = {
            mixer_car_id: $('#mixerCarFilter').val(),
            mixer_driver_id: $('#mixerDriverFilter').val(),
            pump_car_id: $('#pumpCarFilter').val(),
            pump_driver_id: $('#pumpDriverFilter').val(),
            customer_id: $('#customerFilter').val(),
            from_date: $('#fromDate').val(),
            to_date: $('#toDate').val()
        };

        try {
            const response = await fetch(`../process/income_from_cars/get_informations.php?${new URLSearchParams(filters)}`);
            const result = await response.json();

            if (result.success) {
                displayData(result.data);
                updateSummary(result.summary);
                updateCharts(result.charts);
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
        }
    }

    async function loadFilters() {
        try {
            // Load cars
            const carsResponse = await fetch('../process/car/select_car.php');
            const carsResult = await carsResponse.json();
            if (carsResult.success) {
                const cars = carsResult.data;
                $('#mixerCarFilter, #pumpCarFilter').each(function() {
                    $(this).empty().append('<option value="">هەموو سەیارەکان</option>');
                    cars.forEach(car => {
                        $(this).append(`<option value="${car.id}">${car.name}</option>`);
                    });
                });
            }

            // Load drivers (employees)
            const driversResponse = await fetch('../process/employee/select_employee.php');
            const driversResult = await driversResponse.json();
            if (driversResult.success) {
                const drivers = driversResult.data;
                $('#mixerDriverFilter, #pumpDriverFilter').each(function() {
                    $(this).empty().append('<option value="">هەموو شۆفێران</option>');
                    drivers.forEach(driver => {
                        $(this).append(`<option value="${driver.id}">${driver.name}</option>`);
                    });
                });
            }

            // Load customers
            const customersResponse = await fetch('../process/customer/select_customer.php');
            const customersResult = await customersResponse.json();
            if (customersResult.success) {
                const customers = customersResult.data;
                $('#customerFilter').empty().append('<option value="">هەموو کڕیارەکان</option>');
                customers.forEach(customer => {
                    $('#customerFilter').append(`<option value="${customer.id}">${customer.name}</option>`);
                });
            }

            // Reinitialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                dir: 'rtl'
            });

        } catch (error) {
            console.error('Error loading filters:', error);
        }
    }

    function displayData(data) {
        const tbody = $('#incomeTable tbody');
        tbody.empty();

        if (data.length === 0) {
            tbody.append('<tr><td colspan="10" class="text-center">هیچ زانیارییەک نەدۆزرایەوە</td></tr>');
            return;
        }

        data.forEach(row => {
            const tr = $('<tr>');
            tr.append(`<td>${row.receipt_number}</td>`);
            tr.append(`<td>${row.customer_name || '-'}</td>`);
            tr.append(`<td>${row.location}</td>`);
            tr.append(`<td>${row.meter_amount} م³</td>`);
            tr.append(`<td>${row.mixer_car_name || '-'}</td>`);
            tr.append(`<td>${row.mixer_driver_name || '-'}</td>`);
            tr.append(`<td>${row.pump_car_name || '-'}</td>`);
            tr.append(`<td>${row.pump_driver_name || '-'}</td>`);
            tr.append(`<td>${formatDate(row.created_at)}</td>`);
            tr.append(`<td>${row.receiver_name || '-'}</td>`);
            tbody.append(tr);
        });
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
                    }
                },
                scales: {
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
                        position: 'bottom'
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

    // Make loadData available globally
    window.loadData = loadData;
});
