// Database Backup Management JavaScript
function showAlert(message, type = 'success') {
    const alertContainer = document.getElementById('alertContainer');
    const alertId = 'alert-' + Date.now();
    
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-custom alert-dismissible fade show" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    alertContainer.insertAdjacentHTML('beforeend', alertHtml);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

function showProgress(show = true) {
    const container = document.getElementById('progressContainer');
    container.style.display = show ? 'block' : 'none';
}

function updateProgress(percent) {
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    
    progressBar.style.width = percent + '%';
    progressText.textContent = percent + '%';
}

function createBackup() {
    showProgress(true);
    updateProgress(0);
    
    // Simulate progress for better UX
    simulateProgress();
    
    fetch('../process/backup/create_backup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'create_backup'
        })
    })
    .then(response => response.json())
    .then(data => {
        updateProgress(100);
        
        setTimeout(() => {
            showProgress(false);
            if (data.success) {
                showAlert('باک ئەپ بە سەرکەوتوویی دروستکرا!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showAlert('هەڵەیەک ڕوویدا: ' + data.message, 'danger');
            }
        }, 1000);
    })
    .catch(error => {
        showProgress(false);
        showAlert('هەڵەیەک ڕوویدا: ' + error.message, 'danger');
    });
}

function downloadBackup(filename) {
    window.open('../process/backup/download_backup.php?file=' + encodeURIComponent(filename), '_blank');
}

function restoreBackup(filename) {
    if (confirm('ئایا دڵنیایت کە دەتەوێت داتابەیسەکە بگەڕێنیتەوە؟ ئەم کارە داتاکانی ئێستا دەسڕێتەوە!')) {
        showProgress(true);
        updateProgress(0);
        
        // Simulate progress
        simulateProgress();
        
        fetch('../process/backup/restore_backup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'restore_backup',
                filename: filename
            })
        })
        .then(response => response.json())
        .then(data => {
            updateProgress(100);
            
            setTimeout(() => {
                showProgress(false);
                if (data.success) {
                    showAlert('داتابەیس بە سەرکەوتوویی گەڕێندرایەوە!', 'success');
                } else {
                    showAlert('هەڵەیەک ڕوویدا: ' + data.message, 'danger');
                }
            }, 1000);
        })
        .catch(error => {
            showProgress(false);
            showAlert('هەڵەیەک ڕوویدا: ' + error.message, 'danger');
        });
    }
}

function deleteBackup(filename) {
    if (confirm('ئایا دڵنیایت کە دەتەوێت ئەم باک ئەپە بسڕیتەوە؟')) {
        fetch('../process/backup/delete_backup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete_backup',
                filename: filename
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('باک ئەپ بە سەرکەوتوویی سڕایەوە!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showAlert('هەڵەیەک ڕوویدا: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            showAlert('هەڵەیەک ڕوویدا: ' + error.message, 'danger');
        });
    }
}

function updateAutoBackupSettings() {
    const enabled = document.getElementById('autoBackupEnabled').checked;
    const interval = document.getElementById('backupInterval').value;
    
    fetch('../process/backup/update_auto_backup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_settings',
            enabled: enabled,
            interval: interval
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('ڕێکخستنەکان بە سەرکەوتوویی نوێکرانەوە!', 'success');
        } else {
            showAlert('هەڵەیەک ڕوویدا: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        showAlert('هەڵەیەک ڕوویدا: ' + error.message, 'danger');
    });
}

// Simulate progress for better UX
function simulateProgress() {
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 90) progress = 90;
        updateProgress(Math.floor(progress));
        
        if (progress >= 90) {
            clearInterval(interval);
        }
    }, 200);
}

// Initialize page when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize backup list controls
    initializeBackupListControls();
    
    // Initialize Excel export controls
    initializeExcelExportControls();
    
    console.log('Database Backup page loaded successfully');
});

// Backup List Controls
let currentViewMode = 'list'; // 'list' or 'grid'
let currentPage = 1;
let itemsPerPage = 10;
let allBackups = [];
let filteredBackups = [];

function initializeBackupListControls() {
    // Get all backup items
    allBackups = Array.from(document.querySelectorAll('.backup-item')).map(item => ({
        element: item,
        filename: item.dataset.filename,
        created: parseInt(item.dataset.created),
        size: parseInt(item.dataset.size),
        type: item.dataset.type
    }));
    
    filteredBackups = [...allBackups];
    
    // Add event listeners
    document.getElementById('backupSearch').addEventListener('input', filterBackups);
    document.getElementById('backupSort').addEventListener('change', sortBackups);
    document.getElementById('backupFilter').addEventListener('change', filterBackups);
    
    // Add checkbox change listeners
    document.querySelectorAll('.backup-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedBackups);
    });
    
    // Update display
    updateBackupDisplay();
}

function filterBackups() {
    const searchTerm = document.getElementById('backupSearch').value.toLowerCase();
    const filterType = document.getElementById('backupFilter').value;
    
    filteredBackups = allBackups.filter(backup => {
        // Search filter
        const matchesSearch = backup.filename.toLowerCase().includes(searchTerm);
        
        // Type filter
        let matchesFilter = true;
        if (filterType === 'manual') {
            matchesFilter = backup.type === 'manual';
        } else if (filterType === 'auto') {
            matchesFilter = backup.type === 'auto';
        } else if (filterType === 'today') {
            const today = new Date();
            const backupDate = new Date(backup.created * 1000);
            matchesFilter = backupDate.toDateString() === today.toDateString();
        } else if (filterType === 'week') {
            const weekAgo = new Date();
            weekAgo.setDate(weekAgo.getDate() - 7);
            matchesFilter = backup.created >= weekAgo.getTime() / 1000;
        } else if (filterType === 'month') {
            const monthAgo = new Date();
            monthAgo.setMonth(monthAgo.getMonth() - 1);
            matchesFilter = backup.created >= monthAgo.getTime() / 1000;
        }
        
        return matchesSearch && matchesFilter;
    });
    
    // Apply sorting
    sortBackups();
}

function sortBackups() {
    const sortType = document.getElementById('backupSort').value;
    
    filteredBackups.sort((a, b) => {
        switch (sortType) {
            case 'newest':
                return b.created - a.created;
            case 'oldest':
                return a.created - b.created;
            case 'name_asc':
                return a.filename.localeCompare(b.filename);
            case 'name_desc':
                return b.filename.localeCompare(a.filename);
            case 'size_large':
                return b.size - a.size;
            case 'size_small':
                return a.size - b.size;
            default:
                return 0;
        }
    });
    
    updateBackupDisplay();
}

function updateBackupDisplay() {
    const backupList = document.getElementById('backupList');
    const showingCount = document.getElementById('showingCount');
    const totalCount = document.getElementById('totalCount');
    
    // Hide all items first
    allBackups.forEach(backup => {
        backup.element.style.display = 'none';
    });
    
    // Show filtered items
    filteredBackups.forEach(backup => {
        backup.element.style.display = 'block';
    });
    
    // Update counts
    showingCount.textContent = filteredBackups.length;
    totalCount.textContent = allBackups.length;
    
    // Update pagination
    updatePagination();
}

function updatePagination() {
    const paginationContainer = document.getElementById('paginationContainer');
    const pagination = document.getElementById('backupPagination');
    
    const totalPages = Math.ceil(filteredBackups.length / itemsPerPage);
    
    if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    }
    
    paginationContainer.style.display = 'flex';
    pagination.innerHTML = '';
    
    // Previous button
    const prevItem = document.createElement('li');
    prevItem.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevItem.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage - 1})">پێشوو</a>`;
    pagination.appendChild(prevItem);
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        const pageItem = document.createElement('li');
        pageItem.className = `page-item ${i === currentPage ? 'active' : ''}`;
        pageItem.innerHTML = `<a class="page-link" href="#" onclick="changePage(${i})">${i}</a>`;
        pagination.appendChild(pageItem);
    }
    
    // Next button
    const nextItem = document.createElement('li');
    nextItem.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextItem.innerHTML = `<a class="page-link" href="#" onclick="changePage(${currentPage + 1})">دواتر</a>`;
    pagination.appendChild(nextItem);
}

function changePage(page) {
    const totalPages = Math.ceil(filteredBackups.length / itemsPerPage);
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        updateBackupDisplay();
    }
}

function refreshBackupList() {
    // Add loading state
    const backupList = document.getElementById('backupList');
    backupList.classList.add('loading');
    
    // Simulate refresh
    setTimeout(() => {
        backupList.classList.remove('loading');
        location.reload();
    }, 1000);
}

function toggleViewMode() {
    const backupList = document.getElementById('backupList');
    const viewModeIcon = document.getElementById('viewModeIcon');
    
    if (currentViewMode === 'list') {
        currentViewMode = 'grid';
        backupList.classList.add('grid-view');
        viewModeIcon.className = 'fas fa-list';
    } else {
        currentViewMode = 'list';
        backupList.classList.remove('grid-view');
        viewModeIcon.className = 'fas fa-th';
    }
}

function updateSelectedBackups() {
    const checkboxes = document.querySelectorAll('.backup-checkbox:checked');
    const deleteBtn = document.getElementById('deleteSelectedBtn');
    
    if (checkboxes.length > 0) {
        deleteBtn.style.display = 'inline-block';
        
        // Highlight selected items
        checkboxes.forEach(checkbox => {
            checkbox.closest('.backup-item').classList.add('selected');
        });
        
        // Remove highlight from unchecked items
        document.querySelectorAll('.backup-checkbox:not(:checked)').forEach(checkbox => {
            checkbox.closest('.backup-item').classList.remove('selected');
        });
    } else {
        deleteBtn.style.display = 'none';
        
        // Remove all highlights
        document.querySelectorAll('.backup-item').forEach(item => {
            item.classList.remove('selected');
        });
    }
}

function deleteSelectedBackups() {
    const selectedCheckboxes = document.querySelectorAll('.backup-checkbox:checked');
    const selectedFiles = Array.from(selectedCheckboxes).map(cb => cb.value);
    
    if (selectedFiles.length === 0) {
        showAlert('هیچ فایلێک هەڵنەبژاردووە', 'warning');
        return;
    }
    
    if (confirm(`ئایا دڵنیایت کە دەتەوێت ${selectedFiles.length} باک ئەپ بسڕیتەوە؟`)) {
        let completed = 0;
        let errors = 0;
        
        selectedFiles.forEach(filename => {
            fetch('../process/backup/delete_backup.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete_backup',
                    filename: filename
                })
            })
            .then(response => response.json())
            .then(data => {
                completed++;
                if (!data.success) {
                    errors++;
                }
                
                if (completed === selectedFiles.length) {
                    if (errors === 0) {
                        showAlert('هەموو باک ئەپەکان بە سەرکەوتوویی سڕانەوە!', 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showAlert(`${completed - errors} باک ئەپ سڕانەوە، ${errors} هەڵە هەیە`, 'warning');
                    }
                }
            })
            .catch(error => {
                completed++;
                errors++;
                if (completed === selectedFiles.length) {
                    showAlert('هەڵەیەک ڕوویدا لە سڕینەوەی باک ئەپەکان', 'danger');
                }
            });
        });
    }
}

// Excel Export Functions
function initializeExcelExportControls() {
    // Add event listeners for export buttons
    const exportButtons = document.querySelectorAll('[data-export-type]');
    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            const exportType = this.dataset.exportType;
            const tableName = this.dataset.tableName || '';
            exportToExcel(exportType, tableName);
        });
    });
}

function exportToExcel(exportType, tableName = '') {
    showProgress(true);
    updateProgress(0);
    
    // Simulate progress for better UX
    simulateProgress();
    
    fetch('../process/backup/export_excel.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'export_excel',
            export_type: exportType,
            table_name: tableName
        })
    })
    .then(response => response.json())
    .then(data => {
        updateProgress(100);
        
        setTimeout(() => {
            showProgress(false);
            if (data.success) {
                showAlert('فایلەکەی Excel بە سەرکەوتوویی دروستکرا!', 'success');
                
                // Download the file
                downloadExcelFile(data.file_path, data.filename);
            } else {
                showAlert('هەڵەیەک ڕوویدا: ' + data.message, 'danger');
            }
        }, 1000);
    })
    .catch(error => {
        showProgress(false);
        showAlert('هەڵەیەک ڕوویدا: ' + error.message, 'danger');
    });
}

function downloadExcelFile(filePath, filename) {
    // Create a temporary link to download the file
    const link = document.createElement('a');
    link.href = filePath;
    link.download = filename;
    link.style.display = 'none';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Clean up the file after download
    setTimeout(() => {
        fetch('../process/backup/delete_export.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete_export',
                file_path: filePath
            })
        });
    }, 5000);
}

function exportTableData(tableName) {
    exportToExcel('table', tableName);
}

function exportAllTables() {
    exportToExcel('all_tables');
}

function exportSalesReport() {
    exportToExcel('sales_report');
}

function exportCustomersReport() {
    exportToExcel('customers_report');
}

function exportMaterialsReport() {
    exportToExcel('materials_report');
}
