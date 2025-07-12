document.addEventListener('DOMContentLoaded', function () {
    const addUserForm = document.getElementById('addUserForm');
    if (addUserForm) {
        addUserForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(addUserForm);
            fetch('../process/users/add_user.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addUserModal'));
                if (data.success) {
                    addUserForm.reset();
                    modal.hide();
                    loadUsers();
                }
                swalAlert(data.success ? 'success' : 'error', data.message);
            })
            .catch(() => swalAlert('error', 'هەڵەیەک ڕویدا!'));
        });
    }
});
