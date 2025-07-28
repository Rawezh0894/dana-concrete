function swalAlert(title, message, icon) {
    Swal.fire({
        icon: icon, // 'success', 'error', 'warning', 'info', 'question'
        title: title,
        text: message,
        confirmButtonText: 'باشە'
    });
}

// Add showAlert function for compatibility
function showAlert(type, message) {
    let icon = 'info';
    let title = 'زانیاری';
    
    switch(type) {
        case 'success':
            icon = 'success';
            title = 'سەرکەوتوو';
            break;
        case 'error':
            icon = 'error';
            title = 'هەڵە';
            break;
        case 'warning':
            icon = 'warning';
            title = 'ئاگادارکردنەوە';
            break;
        case 'info':
            icon = 'info';
            title = 'زانیاری';
            break;
    }
    
    Swal.fire({
        icon: icon,
        title: title,
        text: message,
        confirmButtonText: 'باشە'
    });
} 