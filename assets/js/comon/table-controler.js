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
            
            // Apply duplicate styling if row is marked as duplicate
            if (row.is_duplicate) {
                tr.classList.add('duplicate-row');
            }
            
            columns.forEach(col => {
                const td = document.createElement('td');
                td.setAttribute('data-col', col);
                
                if (col === '#') {
                    td.textContent = (options.rowOffset ? options.rowOffset : 0) + idx + 1;
                } else if (col === 'actions') {
                    td.innerHTML = row[col] !== undefined ? row[col] : '';
                } else if (col === 'select') {
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
                } else if (col === 'total_prices') {
                    td.innerHTML = (row[col] !== undefined && row[col] !== null && row[col] !== '') ? row[col] : '-';
                } else {
                    td.innerHTML = (row[col] !== undefined && row[col] !== null && row[col] !== '') ? row[col] : '-';
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
            if (col !== 'actions' && col !== '#' && col !== 'select' && !["admin", "user", "accountant", "manager"].includes(col)) {
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
        const table = document.querySelector(tableSelector);
        if (!table) return;
        
        const thead = table.querySelector('thead');
        const tbody = table.querySelector('tbody');
        if (!thead || !tbody) return;

        let currentPage = options.currentPage || 1;
        let pageSize = options.pageSize || 10;

        // Create page size selector if not exists
        let sizeSelect = table.parentElement.querySelector('.page-size-selector');
        if (!sizeSelect) {
            sizeSelect = document.createElement('select');
            sizeSelect.className = 'form-select form-select-sm page-size-selector d-inline-block w-auto ms-2';
            sizeSelect.innerHTML = `
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            `;
            sizeSelect.value = pageSize;

            // Insert before the table
            table.parentElement.insertBefore(sizeSelect, table);
        }

        // Handle page size change
        sizeSelect.onchange = function() {
            pageSize = parseInt(this.value);
            currentPage = 1; // Reset to first page when changing size
            renderPage(currentPage);
        };

        // Only render header with search inputs if not already present
        let headerRow = thead.querySelector('tr');
        if (!headerRow) return;
        headerRow.querySelectorAll('.table-search-input').forEach(e => e.remove());
        columns.forEach((col, idx) => {
            const th = headerRow.children[idx];
            if (!th) return;
            // Remove all old <br> and .table-search-input from th
            Array.from(th.querySelectorAll('br, .table-search-input')).forEach(e => e.remove());
            if (col !== 'actions' && col !== '#' && col !== 'select' && !["admin", "user", "accountant", "manager"].includes(col)) {
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
            
            // Show "no data" message if filtered data is empty
            if (filtered.length === 0) {
                tbody.innerHTML = '';
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                td.colSpan = columns.length;
                td.className = 'table-empty-state text-center text-muted';
                td.innerHTML = '<i class="bi bi-inbox"></i><br>هیچ زانیارییەک نەدۆزرایەوە';
                tr.appendChild(td);
                tbody.appendChild(tr);
                
                // Clear pagination
                let pagination = table.parentElement.querySelector('.table-pagination');
                if (pagination) pagination.innerHTML = '';
                return;
            }
            
            TableController.render(tableSelector, pageData, columns, { rowOffset: start });
            renderPaginationControls(totalPages);
        }

        function renderPaginationControls(totalPages) {
            let pagination = table.parentElement.querySelector('.table-pagination');
            if (!pagination) {
                pagination = document.createElement('div');
                pagination.className = 'table-pagination mt-3';
                table.parentElement.appendChild(pagination);
            }
            pagination.innerHTML = '';
            
            // Show total records info
            const filtered = getFilteredData();
            const infoDiv = document.createElement('div');
            infoDiv.className = 'text-muted mb-2';
            infoDiv.innerHTML = `نوێنراوە: ${((currentPage - 1) * pageSize) + 1}-${Math.min(currentPage * pageSize, filtered.length)} لە ${filtered.length} زانیاری`;
            pagination.appendChild(infoDiv);
            
            // Prev button with SVG
            const prev = document.createElement('button');
            prev.className = 'btn btn-sm btn-outline-secondary mx-1';
            prev.setAttribute('aria-label', 'پەڕەی پێشوو');
            prev.innerHTML = '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 15L8 10L13 5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            prev.disabled = currentPage === 1;
            prev.onclick = () => renderPage(currentPage - 1);
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
                    btn.onclick = () => renderPage(i);
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
            next.onclick = () => renderPage(currentPage + 1);
            pagination.appendChild(next);
        }

        // Set up search input event listeners
        thead.querySelectorAll('.table-search-input').forEach(input => {
            input.oninput = () => renderPage(1);
        });
        
        // Initial render
        renderPage(currentPage);
    }
};
