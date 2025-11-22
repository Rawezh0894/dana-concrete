function loadCustomers() {
    TableController.showLoading('#customerTable', ['#', 'name', 'mobile1', 'mobile2', 'opening_debt_usd', 'opening_debt_iqd', 'is_recipient', 'actions']);
    
    $.get('../process/customer/select_customer.php', function(response) {
        if (response.success && response.data) {
            const data = response.data.map((customer, index) => ({
                '#': index + 1,
                name: customer.name,
                mobile1: customer.mobile1,
                mobile2: customer.mobile2 || '-',
                opening_debt_usd: Number(customer.opening_debt_usd || 0).toLocaleString('en-US') + ' $',
                opening_debt_iqd: Number(customer.opening_debt_iqd || 0).toLocaleString('en-US') + ' د.ع',
                is_recipient: parseInt(customer.is_recipient || 0) === 1 
                    ? '<span class="badge bg-success"><i class="fas fa-check"></i> بەڵێ</span>' 
                    : '<span class="badge bg-secondary"><i class="fas fa-times"></i> نەخێر</span>',
                actions: `
                    <button class="btn btn-sm btn-primary edit-customer-btn" 
                            data-id="${customer.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-customer-btn" data-id="${customer.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                    <a href="customer_profile.php?id=${customer.id}" class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i>
                    </a>
                `
            }));
            
            TableController.renderWithPagination('#customerTable', data, ['#', 'name', 'mobile1', 'mobile2', 'opening_debt_usd', 'opening_debt_iqd', 'is_recipient', 'actions'], { pageSize: 10 });
        } else {
            TableController.showError('#customerTable', 'هەڵە لە وەرگرتنی داتا');
        }
    }, 'json').fail(function() {
        TableController.showError('#customerTable', 'هەڵە لە پەیوەندی داتابەیس');
    });
}

// Load customers when page loads
$(document).ready(function() {
    loadCustomers();
});
