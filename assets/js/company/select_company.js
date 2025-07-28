function loadCompanies() {
    TableController.showLoading('#companyTable', ['#', 'name', 'opening_debt_usd', 'opening_debt_iqd', 'currency_type', 'actions']);
    
    $.get('../process/company/select_company.php', function(response) {
        if (response.success && response.data) {
            const data = response.data.map((c, index) => ({
                '#': index + 1,
                name: c.name,
                opening_debt_usd: Number(c.opening_debt_usd).toLocaleString('en-US') + ' $',
                opening_debt_iqd: Number(c.opening_debt_iqd).toLocaleString('en-US') + ' د.ع',
                currency_type: c.currency_type,
                actions: `
                    <button class="btn btn-sm btn-primary edit-company-btn" 
                            data-id="${c.id}" 
                            data-name="${c.name}"
                            data-opening_debt_usd="${c.opening_debt_usd}"
                            data-opening_debt_iqd="${c.opening_debt_iqd}"
                            data-currency_type="${c.currency_type}"
                            data-bs-toggle="modal" 
                            data-bs-target="#editCompanyModal">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-company-btn" data-id="${c.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                    <a href="company_profile.php?id=${c.id}" class="btn btn-sm btn-info">
                        <i class="fas fa-eye"></i>
                    </a>
                `
            }));
            
            TableController.renderWithPagination('#companyTable', data, ['#', 'name', 'opening_debt_usd', 'opening_debt_iqd', 'currency_type', 'actions'], { pageSize: 10 });
        } else {
            TableController.showError('#companyTable', 'هەڵە لە وەرگرتنی داتا');
        }
    }, 'json').fail(function() {
        TableController.showError('#companyTable', 'هەڵە لە پەیوەندی داتابەیس');
    });
}

// Handle edit button clicks
$(document).on('click', '.edit-company-btn', function() {
    const btn = $(this);
    $('#editCompanyId').val(btn.data('id'));
    $('#editName').val(btn.data('name'));
    $('#editOpeningDebtUsd').val(btn.data('opening_debt_usd'));
    $('#editOpeningDebtIqd').val(btn.data('opening_debt_iqd'));
    $('#editCurrencyType').val(btn.data('currency_type'));
    
    // Trigger currency type change to enable/disable fields
    setTimeout(() => {
        handleEditCurrencyTypeChange();
    }, 100);
});

// Load companies when page loads
$(document).ready(function() {
    loadCompanies();
});
