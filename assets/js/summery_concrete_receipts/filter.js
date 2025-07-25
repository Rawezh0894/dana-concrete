// Filter functionality for concrete receipts summary

$(document).ready(function() {
    // Initialize filters
    initializeFilters();
    
    // Set up event listeners
    setupFilterEvents();
});

// Initialize filters
function initializeFilters() {
    // Set today's date as default
    const today = new Date().toISOString().split('T')[0];
    $('#filter_date_from').val(today);
    $('#filter_date_to').val(today);
}

// Set up filter event listeners
function setupFilterEvents() {
    // Customer filter change
    $('#filter_customer_id').on('change', function() {
        loadSummaryData();
    });
    
    // Formula filter change
    $('#filter_formula_id').on('change', function() {
        loadSummaryData();
    });
    
    // Date filters change
    $('#filter_date_from, #filter_date_to').on('change', function() {
        validateDateRange();
        loadSummaryData();
    });
    
    // Today filter button
    $('#filter_today').on('click', function() {
        setTodayFilter();
        loadSummaryData();
    });
    
    // Reset filter button
    $('#filter_reset').on('click', function() {
        resetFilters();
        loadSummaryData();
    });
}

// Validate date range
function validateDateRange() {
    const dateFrom = $('#filter_date_from').val();
    const dateTo = $('#filter_date_to').val();
    
    if (dateFrom && dateTo && dateFrom > dateTo) {
        showWarning('بەرواری دەستپێک نابێت لە بەرواری کۆتایی گەورەتر بێت');
        $('#filter_date_to').val(dateFrom);
    }
}

// Set today filter
function setTodayFilter() {
    const today = new Date().toISOString().split('T')[0];
    $('#filter_date_from').val(today);
    $('#filter_date_to').val(today);
    $('#filter_customer_id').val('');
    $('#filter_formula_id').val('');
}

// Reset all filters
function resetFilters() {
    $('#filter_customer_id').val('');
    $('#filter_formula_id').val('');
    $('#filter_date_from').val('');
    $('#filter_date_to').val('');
}

// Show warning message
function showWarning(message) {
    Swal.fire({
        icon: 'warning',
        title: 'ئاگاداری',
        text: message,
        confirmButtonText: 'باشە'
    });
}

// Advanced filtering options
function applyAdvancedFilters() {
    const filters = getAdvancedFilters();
    
    $.ajax({
        url: '../process/summery_concrete_receipts/get_informations.php',
        type: 'POST',
        data: filters,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateSummaryCards(response.summary);
                updateSummaryTable(response.customerSummary);
            } else {
                showError('هەڵە لە وەرگرتنی داتا: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            showError('هەڵە لە پەیوەندی بە سێرڤەر: ' + error);
        }
    });
}

// Get advanced filters
function getAdvancedFilters() {
    return {
        customer_id: $('#filter_customer_id').val(),
        formula_id: $('#filter_formula_id').val(),
        date_from: $('#filter_date_from').val(),
        date_to: $('#filter_date_to').val(),
        min_amount: $('#filter_min_amount').val(),
        max_amount: $('#filter_max_amount').val(),
        location: $('#filter_location').val()
    };
}

// Export filtered data
function exportFilteredData() {
    const filters = getFilters();
    const params = new URLSearchParams(filters);
    
    // Show loading
    Swal.fire({
        title: 'هەناردەکردن...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Download file
    const link = document.createElement('a');
    link.href = `../process/summery_concrete_receipts/export_excel.php?${params.toString()}`;
    link.download = 'concrete_receipts_summary.xlsx';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Hide loading
    Swal.close();
}

// Print filtered data
function printFilteredData() {
    // Create print-friendly version
    const printWindow = window.open('', '_blank');
    const filters = getFilters();
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html dir="rtl">
        <head>
            <title>پوختەی پسووڵەکانی کۆنکرێت</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
                th { background-color: #f2f2f2; }
                .header { text-align: center; margin-bottom: 20px; }
                .summary { margin-bottom: 20px; }
                @media print { .no-print { display: none; } }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>پوختەی پسووڵەکانی کۆنکرێت</h2>
                <p>بەروار: ${new Date().toLocaleDateString('ku-IQ')}</p>
            </div>
            <div class="summary">
                <p><strong>کۆی گشتی پسووڵەکان:</strong> <span id="print_total_receipts">0</span></p>
                <p><strong>کۆی گشتی بڕی مەتر سێجا:</strong> <span id="print_total_meter">0</span> م³</p>
                <p><strong>کۆی کڕیاران:</strong> <span id="print_total_customers">0</span></p>
            </div>
            <div id="print_table_content">
                <!-- Table content will be loaded here -->
            </div>
        </body>
        </html>
    `);
    
    // Load data for print
    $.ajax({
        url: '../process/summery_concrete_receipts/get_informations.php',
        type: 'POST',
        data: filters,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                printWindow.document.getElementById('print_total_receipts').textContent = response.summary.total_receipts || 0;
                printWindow.document.getElementById('print_total_meter').textContent = (response.summary.total_meter_cubic || 0).toFixed(2);
                printWindow.document.getElementById('print_total_customers').textContent = response.summary.total_customers || 0;
                
                // Generate table content
                let tableContent = `
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ناوی کڕیار</th>
                                <th>کۆی پسووڵەکان</th>
                                <th>کۆی بڕی مەتر سێجا</th>
                                <th>فۆرمۆلاکان</th>
                                <th>شوێنەکان</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                response.customerSummary.forEach((customer, index) => {
                    tableContent += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${customer.customer_name}</td>
                            <td>${customer.total_receipts}</td>
                            <td>${parseFloat(customer.total_meter_cubic).toFixed(2)} م³</td>
                            <td>${customer.formulas}</td>
                            <td>${customer.locations}</td>
                        </tr>
                    `;
                });
                
                tableContent += '</tbody></table>';
                printWindow.document.getElementById('print_table_content').innerHTML = tableContent;
                
                printWindow.print();
            }
        },
        error: function() {
            printWindow.close();
            showError('هەڵە لە پرینتکردن');
        }
    });
}
