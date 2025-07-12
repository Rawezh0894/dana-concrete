document.addEventListener('DOMContentLoaded', function () {
    // Fill modal with user data
    document.body.addEventListener('click', function (e) {
        if (e.target.closest('.edit-user-btn')) {
            const btn = e.target.closest('.edit-user-btn');
            document.getElementById('editUserId').value = btn.getAttribute('data-id');
            document.getElementById('editUsername').value = btn.getAttribute('data-username');
            document.getElementById('editRole').value = btn.getAttribute('data-role');
            document.getElementById('editPassword').value = '';
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editUserModal'));
            modal.show();
        }
    });
    // Handle update submit
    const editUserForm = document.getElementById('editUserForm');
    if (editUserForm) {
        editUserForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(editUserForm);
            fetch('../process/users/update_user.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editUserModal'));
                if (data.success) {
                    modal.hide();
                    loadUsers();
                }
                swalAlert(data.success ? 'success' : 'error', data.message);
            })
            .catch(() => swalAlert('error', 'هەڵەیەک ڕویدا!'));
        });
    }
});
