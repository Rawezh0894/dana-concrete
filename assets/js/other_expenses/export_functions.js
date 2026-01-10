// Excel Export Functions for Other Expenses System
// This file contains all export functionality without jQuery dependencies

// Function to export other expenses detailed data to Excel
// Try AG Grid export first, fallback to backend export
function exportOtherExpensesToExcel() {
    // Try AG Grid export first if available
    if (typeof otherExpensesGridApi !== 'undefined' && otherExpensesGridApi) {
        otherExpensesGridApi.exportDataAsCsv({
            fileName: 'other_expenses_' + new Date().toISOString().split('T')[0] + '.csv',
            columnSeparator: ','
        });
        return;
    }
    
    // Fallback to backend export
    console.log('Export function called (backend fallback)');
    
    // Get current filter values using vanilla JavaScript
    const dateFrom = document.getElementById('dateFrom')?.value || '';
    const dateTo = document.getElementById('dateTo')?.value || '';
    const monthFilter = document.getElementById('monthFilter')?.value || '';
    const carFilter = document.getElementById('carFilter')?.value || '';
    const employeeFilter = document.getElementById('employeeFilter')?.value || '';
    const personFilter = document.getElementById('personFilter')?.value || '';
    
    // Get expense type filters
    const expenseTypes = [];
    if (document.getElementById('expenseTypeOther')?.checked) expenseTypes.push('خەرجی تر');
    if (document.getElementById('expenseTypeMaterial')?.checked) expenseTypes.push('بەکارهێنانی کاڵای کۆگا');
    if (document.getElementById('expenseTypeGas')?.checked) expenseTypes.push('بەکارهێنانی گاز');
    if (document.getElementById('filter_expense_type_khwardnga')?.checked) expenseTypes.push('خواردنگە');
    if (document.getElementById('filter_expense_type_office')?.checked) expenseTypes.push('ئۆفیس');
    
    console.log('Filters collected:', { dateFrom, dateTo, monthFilter, carFilter, employeeFilter, personFilter, expenseTypes });
    
    // Create form data
    const formData = new FormData();
    formData.append('dateFrom', dateFrom);
    formData.append('dateTo', dateTo);
    formData.append('monthFilter', monthFilter);
    formData.append('carFilter', carFilter);
    formData.append('employeeFilter', employeeFilter);
    formData.append('personFilter', personFilter);
    expenseTypes.forEach(type => formData.append('expenseTypes[]', type));
    
    // Show loading message
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'چاوەڕوان بە...',
            text: 'خەملێنراوە بۆ ئیکسپۆرتکردن',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }
    
    // Make AJAX request to export
    fetch('../process/other_expenses/export_excel.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.blob();
        }
        throw new Error('Network response was not ok');
    })
    .then(blob => {
        // Create download link
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `خەرجی_تر_${new Date().toISOString().split('T')[0]}.xls`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        // Show success message
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'سەرکەوتوو!',
                text: 'فایلەکە بە سەرکەوتوویی ئیکسپۆرت کرا',
                timer: 2000,
                showConfirmButton: false
            });
        }
    })
    .catch(error => {
        console.error('Export error:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە!',
                text: 'هەڵەیەک لە ئیکسپۆرتکردن هەیە. تکایە دواتر هەوڵ بدەوە'
            });
        }
    });
}

// Function to export other expenses summary to Excel
function exportOtherExpensesSummaryToExcel() {
    console.log('Summary export function called');
    
    // Get current filter values using vanilla JavaScript
    const dateFrom = document.getElementById('dateFrom')?.value || '';
    const dateTo = document.getElementById('dateTo')?.value || '';
    const monthFilter = document.getElementById('monthFilter')?.value || '';
    const carFilter = document.getElementById('carFilter')?.value || '';
    const employeeFilter = document.getElementById('employeeFilter')?.value || '';
    const personFilter = document.getElementById('personFilter')?.value || '';
    
    // Get expense type filters
    const expenseTypes = [];
    if (document.getElementById('expenseTypeOther')?.checked) expenseTypes.push('خەرجی تر');
    if (document.getElementById('expenseTypeMaterial')?.checked) expenseTypes.push('بەکارهێنانی کاڵای کۆگا');
    if (document.getElementById('expenseTypeGas')?.checked) expenseTypes.push('بەکارهێنانی گاز');
    if (document.getElementById('filter_expense_type_khwardnga')?.checked) expenseTypes.push('خواردنگە');
    if (document.getElementById('filter_expense_type_office')?.checked) expenseTypes.push('ئۆفیس');
    
    console.log('Summary filters collected:', { dateFrom, dateTo, monthFilter, carFilter, employeeFilter, personFilter, expenseTypes });
    
    // Create form data
    const formData = new FormData();
    formData.append('dateFrom', dateFrom);
    formData.append('dateTo', dateTo);
    formData.append('monthFilter', monthFilter);
    formData.append('carFilter', carFilter);
    formData.append('employeeFilter', employeeFilter);
    formData.append('personFilter', personFilter);
    expenseTypes.forEach(type => formData.append('expenseTypes[]', type));
    formData.append('export_type', 'summary');
    
    // Show loading message
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'چاوەڕوان بە...',
            text: 'خەملێنراوە بۆ ئیکسپۆرتی کورتەی خەرجی تر',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }
    
    // Make AJAX request to export summary
    fetch('../process/other_expenses/export_excel.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.blob();
        }
        throw new Error('Network response was not ok');
    })
    .then(blob => {
        // Create download link
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `کورتەی_خەرجی_تر_${new Date().toISOString().split('T')[0]}.xls`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        // Show success message
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'سەرکەوتوو!',
                text: 'کورتەی خەرجی تر بە سەرکەوتوویی ئیکسپۆرت کرا',
                timer: 2000,
                showConfirmButton: false
            });
        }
    })
    .catch(error => {
        console.error('Summary export error:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'هەڵە!',
                text: 'هەڵەیەک لە ئیکسپۆرتی کورتەکە هەیە. تکایە دواتر هەوڵ بدەوە'
            });
        }
    });
}

// Make functions globally available
window.exportOtherExpensesToExcel = exportOtherExpensesToExcel;
window.exportOtherExpensesSummaryToExcel = exportOtherExpensesSummaryToExcel;

// Log when file is loaded
console.log('Export functions loaded successfully');
