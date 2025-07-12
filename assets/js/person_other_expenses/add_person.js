const addPersonForm = document.getElementById('addPersonForm');
if (addPersonForm) {
    addPersonForm.onsubmit = async function(e) {
        e.preventDefault();
        const formData = new FormData(addPersonForm);
        const opening_debt_usd = $("#person_opening_debt_usd").val();
        const opening_debt_iqd = $("#person_opening_debt_iqd").val();
        formData.append('opening_debt_usd', opening_debt_usd);
        formData.append('opening_debt_iqd', opening_debt_iqd);
        const res = await fetch('../process/person_other_expenses/add_person.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire('سەرکەوتوو!', 'کەس زیادکرا', 'success');
            var modal = bootstrap.Modal.getInstance(document.getElementById('addPersonModal'));
            modal.hide();
            if (typeof loadPersons === 'function') loadPersons();
            addPersonForm.reset();
        } else {
            Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
        }
    }
}
