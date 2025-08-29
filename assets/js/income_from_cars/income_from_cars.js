$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        dir: 'rtl'
    });

    // Load initial data
    loadData();
    loadFilters();

    // Charts are not available in the new API structure
    // let carsChart = null;
    // let driversChart = null;

    async function loadData() {
        const filters = {
            from_date: $('#fromDate').val(),
            to_date: $('#toDate').val(),
            mixer_car: $('#mixerCarFilter').val(),
            mixer_driver: $('#mixerDriverFilter').val(),
            pump_car: $('#pumpCarFilter').val(),
            pump_driver: $('#pumpDriverFilter').val(),
            customer: $('#customerFilter').val()
        };

        try {
            const response = await fetch('../process/income_from_cars/get_informations.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(filters)
            });
            const result = await response.json();

            if (result.success) {
                displayData(result.data.receipts || []);
                updateSummary(result.data.summary || {});
                // Note: Charts data is not available in the new API structure
                // updateCharts(result.charts);
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
            const cars = await carsResponse.json();
            if (cars && cars.length > 0) {
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
            if (driversResult && driversResult.employees && driversResult.employees.length > 0) {
                const drivers = driversResult.employees;
                $('#mixerDriverFilter, #pumpDriverFilter').each(function() {
                    $(this).empty().append('<option value="">هەموو شۆفێران</option>');
                    drivers.forEach(driver => {
                        $(this).append(`<option value="${driver.id}">${driver.name}</option>`);
                    });
                });
            }

            // Load customers
            const customersResponse = await fetch('../process/customer/select_customer.php');
            const customers = await customersResponse.json();
            if (customers && customers.length > 0) {
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
        // Check if data is an array and has content
        if (!Array.isArray(data) || data.length === 0) {
            // Clear table and show no data message
            $('#incomeTable tbody').empty();
            $('#incomeTable tbody').append('<tr><td colspan="9" class="text-center">هیچ داتایەک نەدۆزرایەوە</td></tr>');
            $('#tableInfo').text('کۆی: 0 تۆمار');
            return;
        }

        const columns = [
            'receipt_number', 'customer_name', 'location', 'meter_amount', 
            'mixer_car_name', 'mixer_driver_name', 'pump_car_name', 
            'pump_driver_name', 'created_at', 'receiver_name'
        ];

        const mappedData = data.map(row => ({
            receipt_number: row.receipt_number || '-',
            customer_name: row.customer_name || '-',
            location: row.location || '-',
            meter_amount: row.meter_amount ? `${row.meter_amount} م³` : '-',
            mixer_car_name: row.mixer_car_name || '-',
            mixer_driver_name: row.mixer_driver_name || '-',
            pump_car_name: row.pump_car_name || '-',
            pump_driver_name: row.pump_driver_name || '-',
            created_at: row.created_at ? formatDate(row.created_at) : '-',
            receiver_name: row.receiver_name || '-'
        }));

        TableController.renderWithPagination('#incomeTable', mappedData, columns, { pageSize: 10 });
        
        // Update table info
        $('#tableInfo').text(`کۆی: ${data.length} تۆمار`);
    }

    function updateSummary(summary) {
        $('#totalCars').text(summary.total_cars || 0);
        $('#totalDrivers').text(summary.total_drivers || 0);
        $('#totalMeters').text((summary.total_meters || 0).toLocaleString('ku-IQ'));
        $('#totalReceipts').text(summary.total_receipts || 0);
    }

    // Charts are not available in the new API structure
    // function updateCharts(charts) {
    //     updateCarsChart(charts.cars);
    //     updateDriversChart(charts.drivers);
    // }

    // function updateCarsChart(carsData) {
    //     const ctx = document.getElementById('carsChart').getContext('2d');
        
    //     if (carsChart) {
    //         carsChart.destroy();
    //     }

    //     const labels = Object.values(carsData).map(car => car.name);
    //     const data = Object.values(carsData).map(car => car.meters);

    //     carsChart = new Chart(ctx, {
    //         type: 'bar',
    //         data: {
    //             labels: labels,
    //             datasets: [{
    //                 label: 'مەتر سێج',
    //                 data: data,
    //                 backgroundColor: 'rgba(102, 126, 234, 0.8)',
    //                 borderColor: 'rgba(102, 126, 234, 1)',
    //                 borderWidth: 1
    //     }]
    //     },
    //     options: {
    //         responsive: true,
    //         maintainAspectRatio: false,
    //         plugins: {
    //             legend: {
    //                 display: false
    //             },
    //             tooltip: {
    //                 callbacks: {
    //                     label: function(context) {
    //                         return `${context.label}: ${context.parsed.y.toLocaleString('ku-IQ')} م³`;
    //                     }
    //                 }
    //             }
    //         },
    //         scales: {
    //             x: {
    //                 ticks: {
    //                     maxRotation: 45,
    //                     minRotation: 0
    //                 }
    //             },
    //             y: {
    //                 beginAtZero: true,
    //                 ticks: {
    //                     callback: function(value) {
    //                         return value.toLocaleString('ku-IQ') + ' م³';
    //                 }
    //             }
    //         }
    //     }
    //     });
    // }

    // function updateDriversChart(driversData) {
    //     const ctx = document.getElementById('driversChart').getContext('2d');
        
    //     if (driversChart) {
    //         driversChart.destroy();
    //     }

    //     const labels = Object.values(driversData).map(driver => driver.name);
    //     const data = Object.values(driversData).map(driver => driver.meters);

    //     driversChart = new Chart(ctx, {
    //         type: 'doughnut',
    //         data: {
    //             labels: labels,
    //             datasets: [{
    //                 data: data,
    //                 backgroundColor: [
    //                     '#FF6384',
    //                     '#36A2EB',
    //                     '#FFCE56',
    //                     '#4BC0C0',
    //                     '#9966FF',
    //                     '#FF9F40',
    //                     '#FF6384',
    //                     '#C9CBCF',
    //                     '#4BC0C0',
    //                     '#FF6384'
    //                 ]
    //             }]
    //         },
    //         options: {
    //             responsive: true,
    //             maintainAspectRatio: false,
    //             plugins: {
    //                 legend: {
    //                     position: 'bottom',
    //                     labels: {
    //                         padding: 20,
    //                         usePointStyle: true
    //                 }
    //             },
    //             tooltip: {
    //                 callbacks: {
    //                     label: function(context) {
    //                         const total = context.dataset.data.reduce((a, b) => a + b, 0);
    //                         const label = ((context.parsed / total) * 100).toFixed(1);
    //                         return `${context.label}: ${context.parsed.toLocaleString('ku-IQ')} م³ (${label}%)`;
    //                     }
    //                 }
    //             }
    //         }
    //     });
    // }

    function formatDate(dateString) {
        if (!dateString) return '-';
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return '-';
            return date.toLocaleDateString('ku-IQ');
        } catch (error) {
            return '-';
        }
    }

    // Export to Excel function
    window.exportToExcel = function() {
        const filters = {
            from_date: $('#fromDate').val(),
            to_date: $('#toDate').val(),
            mixer_car: $('#mixerCarFilter').val(),
            mixer_driver: $('#mixerDriverFilter').val(),
            pump_car: $('#pumpCarFilter').val(),
            pump_driver: $('#pumpDriverFilter').val(),
            customer: $('#customerFilter').val()
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
