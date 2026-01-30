document.addEventListener('DOMContentLoaded', function () {
    loadPermissions();
    setupAddRole();
});

function loadPermissions() {
    fetch('../process/users/select_permissions.php')
        .then(res => res.json())
        .then(data => {
            const roles = data.roles;
            const perms = data.permissions;

            // 1. Update Table Header
            const thead = document.querySelector('#permissionsTable thead tr');
            thead.innerHTML = `
                <th>ناوی دەسەڵات</th>
                <th>ڕوونکردنەوە</th>
                ${roles.map(role => `<th class="text-capitalize">${role}</th>`).join('')}
            `;

            // 2. Transform data for TableController
            const tableData = perms.map(perm => {
                const row = {
                    name: perm.name,
                    description: perm.description || ''
                };
                roles.forEach(role => {
                    row[role] = createCheckbox(role, perm.id, perm[role]);
                });
                return row;
            });

            // Define columns for the table
            const columns = ['name', 'description', ...roles];

            // Use TableController with pagination and search
            TableController.renderWithPagination('#permissionsTable', tableData, columns, {
                pageSize: 15,
                currentPage: 1
            });

            // 3. Populate User Modals while we have the roles
            populateRoleSelects(roles);
        });
}

function populateRoleSelects(roles) {
    const addRoleSelect = document.getElementById('addRole');
    const editRoleSelect = document.getElementById('editRole');

    if (addRoleSelect) {
        addRoleSelect.innerHTML = roles.map(role => `<option value="${role}">${role}</option>`).join('');
    }
    if (editRoleSelect) {
        editRoleSelect.innerHTML = roles.map(role => `<option value="${role}">${role}</option>`).join('');
    }
}

function createCheckbox(role, permissionId, isChecked) {
    return `<input type="checkbox" class="perm-checkbox" data-role="${role}" data-id="${permissionId}" ${isChecked ? 'checked' : ''}>`;
}

function setupAddRole() {
    const form = document.getElementById('addRoleForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(form);

        fetch('../process/users/add_role.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    swalAlert('success', data.message);
                    bootstrap.Modal.getInstance(document.getElementById('addRoleModal')).hide();
                    form.reset();
                    loadPermissions(); // Reload everything
                } else {
                    swalAlert('error', data.message);
                }
            })
            .catch(() => swalAlert('error', 'هەڵەیەک ڕوویدا!'));
    });
}

document.body.addEventListener('change', function (e) {
    if (e.target.classList.contains('perm-checkbox')) {
        const role = e.target.getAttribute('data-role');
        const permission_id = e.target.getAttribute('data-id');
        const value = e.target.checked ? 1 : 0;
        fetch('../process/users/update_role_permissions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `role=${role}&permission_id=${permission_id}&value=${value}`
        })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    swalAlert('error', data.message);
                    e.target.checked = !e.target.checked; // Revert checkbox
                } else {
                    // Optional: success toast
                }
            })
            .catch(() => {
                swalAlert('error', 'هەڵەیەک ڕویدا!');
                e.target.checked = !e.target.checked; // Revert checkbox
            });
    }
});
