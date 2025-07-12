document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
    if (link.href === window.location.href) {
        link.classList.add('active');
    }
});
