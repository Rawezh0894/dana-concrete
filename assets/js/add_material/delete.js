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
                try {
                    // Try to parse as JSON first
                    var jsonResponse = JSON.parse(res);
                    
                    if (jsonResponse.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو',
                            text: jsonResponse.message || 'کاڵا بە سەرکەوتوویی سڕایەوە!',
                            confirmButtonText: 'باشە'
                        }).then(() => {
                            if (typeof loadMaterials === 'function') loadMaterials();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: jsonResponse.message || 'سڕینەوە سەرکەوتوو نەبوو!',
                            confirmButtonText: 'باشە'
                        });
                    }
                } catch (e) {
                    // If not JSON, treat as plain text
                    var responseText = '';
                    if (typeof res === 'string') {
                        responseText = res;
                    } else if (typeof res === 'object' && res !== null) {
                        // If it's an object, try to get a meaningful message
                        if (res.message) {
                            responseText = res.message;
                        } else if (res.error) {
                            responseText = res.error;
                        } else {
                            responseText = 'هەڵەی نەناسراو';
                        }
                    } else {
                        responseText = String(res);
                    }
                    
                    if (responseText.trim() === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'سەرکەوتوو',
                            text: 'کاڵا بە سەرکەوتوویی سڕایەوە!',
                            confirmButtonText: 'باشە'
                        }).then(() => {
                            if (typeof loadMaterials === 'function') loadMaterials();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'هەڵە',
                            text: 'سڕینەوە سەرکەوتوو نەبوو! ' + responseText,
                            confirmButtonText: 'باشە'
                        });
                    }
                }
                
                $btn.prop('disabled', false);
            }).fail(function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });
                
                var errorMessage = 'هەڵە لە پەیوەندی بە سێرڤەر';
                if (xhr.responseText) {
                    try {
                        var errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.message) {
                            errorMessage = errorResponse.message;
                        }
                    } catch (e) {
                        errorMessage = xhr.responseText;
                    }
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵەی AJAX',
                    text: errorMessage,
                    confirmButtonText: 'باشە'
                });
                
                $btn.prop('disabled', false);
            });
        }
    });
});
