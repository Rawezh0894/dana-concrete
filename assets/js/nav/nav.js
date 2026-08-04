document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
    if (link.href === window.location.href) {
        link.classList.add('active');
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Enable horizontal scrolling for all DataTables globally
    if (typeof $.fn.dataTable !== 'undefined') {
        $.extend(true, $.fn.dataTable.defaults, {
            scrollX: true
        });
    }

    // Add horizontal scroll to standard bare tables
    document.querySelectorAll('table').forEach(table => {
        // Skip if it's inside an ag-grid container
        if (table.closest('.ag-theme-alpine') || table.closest('.ag-grid-container')) return;
        
        // Skip if already wrapped in table-responsive (or DataTables wrapper)
        if (table.parentElement && (table.parentElement.classList.contains('table-responsive') || table.parentElement.classList.contains('dataTables_scrollBody'))) return;
        
        // Wrap table in div.table-responsive
        const wrapper = document.createElement('div');
        wrapper.className = 'table-responsive';
        // Add inline styles as fallback
        wrapper.style.overflowX = 'auto';
        wrapper.style.webkitOverflowScrolling = 'touch';
        wrapper.style.width = '100%';
        
        // Insert wrapper before table in the DOM tree, then move table into it
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
    });
});
