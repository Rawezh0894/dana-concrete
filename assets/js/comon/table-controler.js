// Table Controller Utility
// Usage: TableController.render(tableSelector, data, columns)

const TableController = {
    render: function(tableSelector, data, columns, options = {}) {
        const tbody = document.querySelector(tableSelector + ' tbody');
        if (!tbody) return;
        tbody.innerHTML = '';
        if (!data || data.length === 0) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = columns.length;
            td.className = 'table-empty-state';
            td.innerHTML = '<i class="bi bi-inbox"></i><br>هیچ زانیارییەک نەدۆزرایەوە';
            tr.appendChild(td);
            tbody.appendChild(tr);
            return;
        }
        data.forEach((row, idx) => {
            const tr = document.createElement('tr');
            columns.forEach(col => {
                const td = document.createElement('td');
                if (col === '#') {
                    td.textContent = (options.rowOffset ? options.rowOffset : 0) + idx + 1;
                } else if (col === 'actions') {
                    td.innerHTML = row[col] !== undefined ? row[col] : '';
                } else if (["admin", "user", "accountant", "manager"].includes(col)) {
                    td.innerHTML = row[col] !== undefined ? row[col] : '';
                } else if (col === 'price_usd') {
                    const val = parseFloat(row[col]);
                    td.textContent = (val && val !== 0) ? ('$' + val.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})) : '-';
                } else if (col === 'price_iqd') {
                    const val = parseFloat(row[col]);
                    td.textContent = (val && val !== 0) ? (val.toLocaleString('en-US', {minimumFractionDigits:0, maximumFractionDigits:0}) + ' د.ع') : '-';
                } else if (col === 'adjustment') {
                    const val = parseFloat(row[col]);
                    td.textContent = (val && val !== 0) ? (val.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + ' Kg') : '-';
                } else {
                    // Always render a cell, even if row[col] is undefined/null/empty
                    td.textContent = (typeof row[col] !== 'undefined' && row[col] !== null && row[col] !== '') ? row[col] : '-';
                }
                tr.appendChild(td);
            });
            // Row select highlight
            tr.onclick = function() {
                tbody.querySelectorAll('tr').forEach(row => row.classList.remove('selected'));
                tr.classList.add('selected');
            };
            tbody.appendChild(tr);
        });
    },
    showLoading: function(tableSelector, columns) {
        const tbody = document.querySelector(tableSelector + ' tbody');
        if (!tbody) return;
        tbody.innerHTML = '';
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = columns.length;
        td.className = 'text-center text-muted';
        td.innerHTML = '<span class="spinner-border spinner-border-sm"></span> چاوەڕوان بە...';
        tr.appendChild(td);
        tbody.appendChild(tr);
    },
    renderWithColumnSearch: function(tableSelector, data, columns) {
        const table = document.querySelector(tableSelector);
        if (!table) return;
        const thead = table.querySelector('thead');
        const tbody = table.querySelector('tbody');
        if (!thead || !tbody) return;

        // Only render header with search inputs if not already present
        let headerRow = thead.querySelector('tr');
        if (!headerRow) return;
        // Remove any previous search inputs
        headerRow.querySelectorAll('.table-search-input').forEach(e => e.remove());

        columns.forEach((col, idx) => {
            const th = headerRow.children[idx];
            if (!th) return;
            // Remove all old <br> and .table-search-input from th
            Array.from(th.querySelectorAll('br, .table-search-input')).forEach(e => e.remove());
            if (col !== 'actions' && col !== '#' && !["admin", "user", "accountant", "manager"].includes(col)) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm table-search-input';
                input.placeholder = 'گەڕان...';
                input.setAttribute('data-col', col);
                th.appendChild(document.createElement('br'));
                th.appendChild(input);
            }
        });

        // Filtering logic
        function filterData() {
            const filters = {};
            thead.querySelectorAll('.table-search-input').forEach(input => {
                filters[input.getAttribute('data-col')] = input.value.trim().toLowerCase();
            });
            const filtered = data.filter(row => {
                return Object.entries(filters).every(([col, val]) => {
                    if (!val) return true;
                    return (row[col] + '').toLowerCase().includes(val);
                });
            });
            TableController.render(tableSelector, filtered, columns);
        }
        thead.querySelectorAll('.table-search-input').forEach(input => {
            input.oninput = filterData;
        });
        // Initial render
        TableController.render(tableSelector, data, columns);
    },
    renderWithPagination: function(tableSelector, data, columns, options = {}) {
        const pageSize = options.pageSize || 10;
        let currentPage = typeof options.currentPage === 'number' ? options.currentPage : 1;
        const table = document.querySelector(tableSelector);
        if (!table) return;
        const thead = table.querySelector('thead');
        const tbody = table.querySelector('tbody');
        if (!thead || !tbody) return;

        // --- Page size selector (top right) ---
        let sizeSelect = table.parentElement.querySelector('.table-page-size');
        if (!sizeSelect) {
            sizeSelect = document.createElement('select');
            sizeSelect.className = 'table-page-size';
            sizeSelect.style.float = 'right';
            sizeSelect.style.marginBottom = '8px';
            [5, 10, 20, 50, 100].forEach(size => {
                const opt = document.createElement('option');
                opt.value = size;
                opt.textContent = size + ' / پەڕ';
                if (size === pageSize) opt.selected = true;
                sizeSelect.appendChild(opt);
            });
            sizeSelect.onchange = function() {
                options.pageSize = parseInt(this.value);
                options.currentPage = 1;
                TableController.renderWithPagination(tableSelector, data, columns, options);
            };
            table.parentElement.insertBefore(sizeSelect, table);
        }
        // Update selected value if changed
        sizeSelect.value = pageSize;

        // Only render header with search inputs if not already present
        let headerRow = thead.querySelector('tr');
        if (!headerRow) return;
        headerRow.querySelectorAll('.table-search-input').forEach(e => e.remove());
        columns.forEach((col, idx) => {
            const th = headerRow.children[idx];
            if (!th) return;
            // Remove all old <br> and .table-search-input from th
            Array.from(th.querySelectorAll('br, .table-search-input')).forEach(e => e.remove());
            if (col !== 'actions' && col !== '#' && !["admin", "user", "accountant", "manager"].includes(col)) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm table-search-input';
                input.placeholder = 'گەڕان...';
                input.setAttribute('data-col', col);
                th.appendChild(document.createElement('br'));
                th.appendChild(input);
            }
        });

        // Filtering logic
        function getFilteredData() {
            const filters = {};
            thead.querySelectorAll('.table-search-input').forEach(input => {
                filters[input.getAttribute('data-col')] = input.value.trim().toLowerCase();
            });
            return data.filter(row => {
                return Object.entries(filters).every(([col, val]) => {
                    if (!val) return true;
                    return (row[col] + '').toLowerCase().includes(val);
                });
            });
        }

        function renderPage(page) {
            const filtered = getFilteredData();
            const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
            if (page > totalPages) page = totalPages;
            if (page < 1) page = 1;
            currentPage = page;
            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;
            const pageData = filtered.slice(start, end);
            TableController.render(tableSelector, pageData, columns, { rowOffset: start });
            renderPaginationControls(totalPages);
        }

        function renderPaginationControls(totalPages) {
            let pagination = table.parentElement.querySelector('.table-pagination');
            if (!pagination) {
                pagination = document.createElement('div');
                pagination.className = 'table-pagination';
                table.parentElement.appendChild(pagination);
            }
            pagination.innerHTML = '';
            // Prev button with SVG
            const prev = document.createElement('button');
            prev.className = 'btn btn-sm btn-outline-secondary mx-1';
            prev.setAttribute('aria-label', 'پەڕەی پێشوو');
            prev.innerHTML = '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 15L8 10L13 5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            prev.disabled = currentPage === 1;
            prev.onclick = () => TableController.renderWithPagination(tableSelector, data, columns, { ...options, currentPage: currentPage - 1 });
            pagination.appendChild(prev);
            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || Math.abs(i - currentPage) <= 2) {
                    const btn = document.createElement('button');
                    btn.className = 'btn btn-sm' + (i === currentPage ? ' btn-success active' : ' btn-outline-secondary');
                    btn.textContent = i;
                    btn.setAttribute('aria-label', 'پەڕەی ' + i);
                    if (i === currentPage) {
                        btn.style.transition = 'transform 0.18s';
                        btn.style.transform = 'scale(1.08)';
                    }
                    btn.onclick = () => TableController.renderWithPagination(tableSelector, data, columns, { ...options, currentPage: i });
                    pagination.appendChild(btn);
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    const span = document.createElement('span');
                    span.textContent = '...';
                    pagination.appendChild(span);
                }
            }
            // Next button with SVG
            const next = document.createElement('button');
            next.className = 'btn btn-sm btn-outline-secondary mx-1';
            next.setAttribute('aria-label', 'پەڕەی دواتر');
            next.innerHTML = '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 5L12 10L7 15" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            next.disabled = currentPage === totalPages;
            next.onclick = () => TableController.renderWithPagination(tableSelector, data, columns, { ...options, currentPage: currentPage + 1 });
            pagination.appendChild(next);
        }

        thead.querySelectorAll('.table-search-input').forEach(input => {
            input.oninput = () => renderPage(1);
        });
        renderPage(currentPage);
    }
};
