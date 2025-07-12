function loadUsers() {
    fetch('../process/users/select_user.php')
        .then(res => res.json())
        .then(users => {
            const tbody = document.querySelector('#usersTable tbody');
            tbody.innerHTML = '';
            users.forEach((user, idx) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${idx + 1}</td>
                    <td>${user.username}</td>
                    <td>${
                        user.role === 'admin' ? 'ئەدمین' :
                        user.role === 'user' ? 'بەکارهێنەر' :
                        user.role === 'accountant' ? 'موحاسیب' :
                        user.role === 'manager' ? 'بەڕێوەبەر' :
                        user.role // fallback to raw role
                    }</td>
                    <td>
                        <button class="btn btn-sm btn-primary me-1 edit-user-btn" data-id="${user.id}" data-username="${user.username}" data-role="${user.role}" title="دەستکاری">
                            <i class="fa fa-pen"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-user-btn" data-id="${user.id}" title="سڕینەوە">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        });
}

document.addEventListener('DOMContentLoaded', loadUsers);
// Make loadUsers globally available for add_user.js
window.loadUsers = loadUsers;
