async function loadPersons() {
    const res = await fetch('../process/person_other_expenses/select_person.php');
    const data = await res.json();
    const tableData = data.map((row, idx) => ({
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
document.addEventListener('DOMContentLoaded', loadPersons);
