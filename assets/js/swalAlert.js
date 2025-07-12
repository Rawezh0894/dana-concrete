function swalAlert(title, message, icon) {
    Swal.fire({
        icon: icon, // 'success', 'error', 'warning', 'info', 'question'
        title: title,
        text: message,
        confirmButtonText: 'باشە'
    });
} 