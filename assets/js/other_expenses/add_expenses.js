let submittingExpense = false;
const addExpenseForm = document.getElementById('addExpenseForm');
if (addExpenseForm) {
    addExpenseForm.onsubmit = async function(e) {
        if (submittingExpense) return false;
        submittingExpense = true;
        e.preventDefault();
        const invoiceNumber = document.getElementById('invoice_number').value.trim();
        if (invoiceNumber) {
            // Check for duplicate in current table (client-side)
            const table = document.getElementById('otherExpensesTable');
            let duplicate = false;
            if (table) {
                for (let row of table.tBodies[0].rows) {
                    if (row.cells[7] && row.cells[7].innerText.trim() === invoiceNumber) {
                        duplicate = true;
                        break;
                    }
                }
            }
            if (duplicate) {
                Swal.fire('هەڵە!', 'ئەم ژمارەی پسوڵەیە پێشتر تۆمارکراوە!', 'error');
                submittingExpense = false;
                return;
            }
        }
        const formData = new FormData(addExpenseForm);
        try {
            const res = await fetch('../process/other_expenses/add_expenses.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire('سەرکەوتوو!', 'خەرجی تر زیادکرا', 'success');
                var modal = bootstrap.Modal.getInstance(document.getElementById('addExpenseModal'));
                modal.hide();
                if (typeof loadOtherExpenses === 'function') loadOtherExpenses();
                addExpenseForm.reset();
            } else {
                Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
            }
        } catch (err) {
            Swal.fire('هەڵە!', 'هەڵەیەک ڕویدا', 'error');
        }
        submittingExpense = false;
    }
}

function populateSelect(url, selectId) {
    fetch(url)
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById(selectId);
            select.innerHTML = '<option value="">-- هەلبژێرە --</option>';
            data.forEach(item => {
                select.innerHTML += `<option value="${item.id}">${item.name}</option>`;
            });
        });
}

const addExpenseModal = document.getElementById('addExpenseModal');
if (addExpenseModal) {
    addExpenseModal.addEventListener('show.bs.modal', function () {
        populateSelect('../process/other_expenses/select_persons.php', 'person_id');
        populateSelect('../process/other_expenses/select_employees.php', 'employee_id');
        populateSelect('../process/other_expenses/select_cars.php', 'car_id');
    });
}

let submittingPerson = false;
const addPersonForm = document.getElementById('addPersonForm');
if (addPersonForm) {
    addPersonForm.onsubmit = async function(e) {
        if (submittingPerson) return false;
        submittingPerson = true;
        e.preventDefault();
        const formData = new FormData(addPersonForm);
        try {
            const res = await fetch('../process/other_expenses/add_person.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire('سەرکەوتوو!', 'کەس زیادکرا', 'success');
                var modal = bootstrap.Modal.getInstance(document.getElementById('addPersonModal'));
                modal.hide();
                // Add new person to select
                const personSelect = document.getElementById('person_id');
                const option = document.createElement('option');
                option.value = data.id;
                option.textContent = data.name;
                option.selected = true;
                personSelect.appendChild(option);
                addPersonForm.reset();
            } else {
                Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
            }
        } catch (err) {
            Swal.fire('هەڵە!', 'هەڵەیەک ڕویدا', 'error');
        }
        submittingPerson = false;
    }
}
