document.addEventListener('DOMContentLoaded', function () {
    document.body.addEventListener('click', function (e) {
        if (e.target.closest('.delete-user-btn')) {
            const btn = e.target.closest('.delete-user-btn');
            const userId = btn.getAttribute('data-id');
            Swal.fire({
                icon: 'warning',
                text: 'دڵنیایت دەتەوێت ئەم بەکارهێنەرە بسڕیتەوە؟',
                showCancelButton: true,
                confirmButtonText: 'باشە',
                cancelButtonText: 'پاشگەزبوونەوە'
            }).then(result => {
                if (result.isConfirmed) {
                    fetch('../process/users/delete_user.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'id=' + encodeURIComponent(userId)
                    })
                    .then(res => res.json())
                    .then(data => {
                        swalAlert(data.success ? 'success' : 'error', data.message);
                        if (data.success) loadUsers();
                    })
                    .catch(() => swalAlert('error', 'هەڵەیەک ڕویدا!'));
                }
            });
        }
    });
});
