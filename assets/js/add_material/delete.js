$(document).on('click', '.delete-btn', function() {
    var $btn = $(this);
    var id = $btn.data('id');
    Swal.fire({
        title: 'دڵنیایت؟',
        text: 'ئەم کاڵایە سڕدرێتەوە؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'بەڵێ',
        cancelButtonText: 'نەخێر'
    }).then((result) => {
        if (result.isConfirmed) {
            $btn.prop('disabled', true);
            $.post('../process/add_material/delete.php', {id: id}, function(res) {
                if (res.trim() === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو',
                        text: 'کاڵا بە سەرکەوتوویی سڕایەوە!',
                        confirmButtonText: 'باشە'
                    }).then(() => {
                        if (typeof loadMaterials === 'function') loadMaterials();
                        // Trigger custom event for summary cards update
                        $(document).trigger('materialDeleted');
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'سڕینەوە سەرکەوتوو نەبوو!',
                        confirmButtonText: 'باشە'
                    });
                }
                $btn.prop('disabled', false);
            }).fail(function() {
                $btn.prop('disabled', false);
            });
        }
    });
});
