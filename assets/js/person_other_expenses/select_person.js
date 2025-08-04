async function loadPersons() {
    const res = await fetch('../process/person_other_expenses/select_person.php');
    const data = await res.json();
    
    // Update summary cards
    if (data.summary) {
        updateSummaryCards(data.summary);
    }
    
    // Handle persons data (backward compatibility)
    const persons = data.persons || data;
    const tableData = persons.map((row, idx) => ({
        '#': idx + 1,
        name: row.name,
        opening_debt_usd: row.opening_debt_usd ?? 0,
        opening_debt_iqd: row.opening_debt_iqd ?? 0,
        actions: `
            <button class="btn btn-sm btn-warning edit-person"
                data-id="${row.id}"
                data-name="${row.name}"
                data-opening_debt_usd="${row.opening_debt_usd || 0}"
                data-opening_debt_iqd="${row.opening_debt_iqd || 0}">
                <i class="fa fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-danger delete-person" data-id="${row.id}"><i class="fa fa-trash"></i></button>
            <button class="btn btn-sm btn-info person-details" data-id="${row.id}"><i class="fa fa-user"></i></button>
        `
    }));
    TableController.renderWithPagination('#personTable', tableData, ['#', 'name', 'opening_debt_usd', 'opening_debt_iqd', 'actions']);
    // Attach events after rendering
    if (typeof attachEditPersonEvents === 'function') attachEditPersonEvents();
    if (typeof attachDeletePersonEvents === 'function') attachDeletePersonEvents();
    // Attach person-details button click
    document.querySelectorAll('.person-details').forEach(btn => {
        btn.onclick = function() {
            const id = this.dataset.id;
            window.location.href = `person_other_expenses_profile.php?id=${id}`;
        };
    });
}

function updateSummaryCards(summary) {
    // Format numbers
    const formatUSD = (amount) => `$${parseFloat(amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    const formatIQD = (amount) => `${parseFloat(amount || 0).toLocaleString('ar-EG')} دینار`;
    
    // Update USD card
    document.getElementById('totalDebtUSD').textContent = formatUSD(summary.total_debt_usd);
    document.getElementById('otherExpensesUSD').textContent = formatUSD(summary.other_expenses_debt.usd);
    document.getElementById('purchaseMaterialsUSD').textContent = formatUSD(summary.purchase_materials_debt.usd);
    document.getElementById('personsOpeningUSD').textContent = formatUSD(summary.persons_opening_debt.usd);
    
    // Update IQD card
    document.getElementById('totalDebtIQD').textContent = formatIQD(summary.total_debt_iqd);
    document.getElementById('otherExpensesIQD').textContent = formatIQD(summary.other_expenses_debt.iqd);
    document.getElementById('purchaseMaterialsIQD').textContent = formatIQD(summary.purchase_materials_debt.iqd);
    document.getElementById('personsOpeningIQD').textContent = formatIQD(summary.persons_opening_debt.iqd);
}

document.addEventListener('DOMContentLoaded', loadPersons);
