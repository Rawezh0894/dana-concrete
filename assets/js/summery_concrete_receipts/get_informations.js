// Load summary information when page loads
$(document).ready(function() {
    loadSummaryData();
});

// Function to load summary data
function loadSummaryData() {
    const filters = getFilters();
    
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

// Function to get current filters
function getFilters() {
    return {
        customer_id: $('#filter_customer_id').val(),
        formula_id: $('#filter_formula_id').val(),
        date_from: $('#filter_date_from').val(),
        date_to: $('#filter_date_to').val()
    };
}

// Function to update summary cards
function updateSummaryCards(summary) {
    $('#total_receipts').text(summary.total_receipts || 0);
    $('#total_meter_cubic').text((summary.total_meter_cubic || 0).toFixed(2));
    $('#total_customers').text(summary.total_customers || 0);
    $('#total_formulas').text(summary.total_formulas || 0);
}

// Function to update summary table
function updateSummaryTable(customerSummary) {
    const tbody = $('#summaryTable tbody');
    tbody.empty();
    
    if (customerSummary.length === 0) {
        tbody.append('<tr><td colspan="9" class="text-center">هیچ داتایەک نییە</td></tr>');
        return;
    }
    
    customerSummary.forEach((customer, index) => {
        const row = `
            <tr>
                <td>${index + 1}</td>
                <td>${escapeHtml(customer.customer_name)}</td>
                <td>${escapeHtml(customer.mobile1 || '-')}</td>
                <td>${customer.total_receipts}</td>
                <td>${parseFloat(customer.total_meter_cubic).toFixed(2)} م³</td>
                <td>${escapeHtml(customer.formulas)}</td>
                <td>${escapeHtml(customer.locations)}</td>
                <td>${escapeHtml(customer.receivers)}</td>
                <td>
                    <button class="btn btn-sm btn-info view-details" data-customer-id="${customer.customer_id}" title="بینینی وردەکاری">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

// Function to show customer details
function showCustomerDetails(customerId) {
    $.ajax({
        url: '../process/summery_concrete_receipts/get_customer_details.php',
        type: 'POST',
        data: { customer_id: customerId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayCustomerDetails(response.data);
                $('#customerDetailsModal').modal('show');
            } else {
                showError('هەڵە لە وەرگرتنی وردەکاری: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            showError('هەڵە لە پەیوەندی بە سێرڤەر: ' + error);
        }
    });
}

// Function to display customer details in modal
function displayCustomerDetails(data) {
    // Update customer info
    $('#modal_customer_name').text(data.customer_name);
    $('#modal_customer_phone').text(data.mobile1 || '-');
    $('#modal_total_receipts').text(data.total_receipts);
    $('#modal_total_meter').text(parseFloat(data.total_meter_cubic).toFixed(2) + ' م³');
    
    // Update receipts table
    const tbody = $('#modal_receipts_table');
    tbody.empty();
    
    if (data.receipts.length === 0) {
        tbody.append('<tr><td colspan="7" class="text-center">هیچ پسووڵەیەک نییە</td></tr>');
        return;
    }
    
    data.receipts.forEach((receipt, index) => {
        const row = `
            <tr>
                <td>${index + 1}</td>
                <td>${escapeHtml(receipt.receipt_number)}</td>
                <td>${receipt.date}</td>
                <td>${escapeHtml(receipt.location)}</td>
                <td>${escapeHtml(receipt.receiver_name || '-')}</td>
                <td>${parseFloat(receipt.meter_amount).toFixed(2)} م³</td>
                <td>${escapeHtml(receipt.formula_name)}</td>
            </tr>
        `;
        tbody.append(row);
    });
}

// Event handler for view details button
$(document).on('click', '.view-details', function() {
    const customerId = $(this).data('customer-id');
    showCustomerDetails(customerId);
});

// Function to export to Excel
function exportToExcel() {
    const filters = getFilters();
    const params = new URLSearchParams(filters);
    window.open(`../process/summery_concrete_receipts/export_excel.php?${params.toString()}`, '_blank');
}

// Function to print summary
function printSummary() {
    window.print();
}

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Function to show error message
function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'هەڵە',
        text: message,
        confirmButtonText: 'باشە'
    });
}
