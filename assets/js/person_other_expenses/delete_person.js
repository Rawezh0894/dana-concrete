async function deletePerson(id) {
    const result = await Swal.fire({
        title: 'دڵنیایت؟',
        text: 'دەتەوێت ئەم کەسە بسڕیتەوە؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'بەڵێ، بسڕەوە',
        cancelButtonText: 'داخستن',
        reverseButtons: true
    });
    if (!result.isConfirmed) return;
    const formData = new FormData();
    formData.append('id', id);
    const res = await fetch('../process/person_other_expenses/delete_person.php', {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    if (data.success) {
        Swal.fire('سەرکەوتوو!', 'کەس سڕایەوە', 'success');
        if (typeof loadPersons === 'function') loadPersons();
    } else {
        Swal.fire('هەڵە!', data.msg || 'هەڵەیەک ڕویدا', 'error');
    }
}
// Attach to table after render
function attachDeletePersonEvents() {
    document.querySelectorAll('.delete-person').forEach(btn => {
        btn.onclick = function() {
            const id = this.dataset.id;
            deletePerson(id);
        };
    });
}
document.addEventListener('DOMContentLoaded', attachDeletePersonEvents);
// Or call attachDeletePersonEvents() after table render in select_person.js
