$(document).ready(function() {
    // Get filters from form
    function getFilters() {
        return {
            customer_id: $('#filter_customer_id').val(),
            formula_id: $('#filter_formula_id').val(),
            date_from: $('#filter_date_from').val(),
            date_to: $('#filter_date_to').val()
        };
    }





    // Show customer details
    $(document).on('click', '.view-details-btn', function() {
        const customerId = $(this).data('customer-id');
        const customerName = $(this).data('customer-name');
        
        loadCustomerDetails(customerId, customerName);
    });

    // Load customer details
    function loadCustomerDetails(customerId, customerName) {
        $.ajax({
            url: '../process/summery_concrete_receipts/get_customer_details.php',
            type: 'GET',
            data: { customer_id: customerId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayCustomerDetails(customerName, response.details);
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

    // Display customer details in modal
    function displayCustomerDetails(customerName, details) {
        let content = `
            <h4>وردەکاری: ${customerName}</h4>
            <div class="table-responsive mt-3">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ژمارەی پسووڵە</th>
                            <th>شوێن</th>
                            <th>وەرگر</th>
                            <th>بڕی مەتر سێجا</th>
                            <th>فۆرمۆلا</th>
                            <th>بەروار</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        if (details.length === 0) {
            content += '<tr><td colspan="6" class="text-center">هیچ پسووڵەیەک نییە</td></tr>';
        } else {
            details.forEach(detail => {
                content += `
                    <tr>
                        <td>${detail.receipt_number}</td>
                        <td>${detail.location || '-'}</td>
                        <td>${detail.receiver_name || '-'}</td>
                        <td>${parseFloat(detail.meter_amount).toFixed(2)}</td>
                        <td>${detail.formula_name || '-'}</td>
                        <td>${formatDate(detail.created_at)}</td>
                    </tr>
                `;
            });
        }

        content += `
                    </tbody>
                </table>
            </div>
        `;

        $('#customerDetailsContent').html(content);
    }

    // Format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('ku-IQ');
    }

    // Auto-load data when filters change
    $('#filter_customer_id, #filter_formula_id').change(function() {
        loadSummaryData();
    });
});
