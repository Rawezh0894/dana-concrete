// Detailed Car Expenses Export Functions
// ئیکسپۆرتی وردەکاری خەرجی سەیارەکان بۆ Excel

// Function to export detailed car expenses to Excel
function exportOtherExpensesDetailedToExcel() {
    // Show loading message
    Swal.fire({
        title: 'چاوەڕوان بە...',
        text: 'وردەکاری خەرجی سەیارەکان بەرەو Excel دەنێردرێت',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Get current filter values
    const dateFrom = document.getElementById('dateFrom')?.value || '';
    const dateTo = document.getElementById('dateTo')?.value || '';
    const monthFilter = document.getElementById('monthFilter')?.value || '';
    const carFilter = document.getElementById('carFilter')?.value || '';
    const employeeFilter = document.getElementById('employeeFilter')?.value || '';
    const personFilter = document.getElementById('personFilter')?.value || '';
    
    // Get expense type filters
    const expenseTypeOther = document.getElementById('expenseTypeOther')?.checked || false;
    const expenseTypeMaterial = document.getElementById('expenseTypeMaterial')?.checked || false;
    const expenseTypeGas = document.getElementById('expenseTypeGas')?.checked || false;
    const expenseTypeKhwardnga = document.getElementById('filter_expense_type_khwardnga')?.checked || false;
    const expenseTypeOffice = document.getElementById('filter_expense_type_office')?.checked || false;

    // Fetch data from API
    fetch('../process/other_expenses/get_car_expenses_detailed.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            date_from: dateFrom,
            date_to: dateTo,
            month_filter: monthFilter,
            car_filter: carFilter,
            employee_filter: employeeFilter,
            person_filter: personFilter,
            expense_type_other: expenseTypeOther ? '1' : '0',
            expense_type_material: expenseTypeMaterial ? '1' : '0',
            expense_type_gas: expenseTypeGas ? '1' : '0',
            expense_type_khwardnga: expenseTypeKhwardnga ? '1' : '0',
            expense_type_office: expenseTypeOffice ? '1' : '0',
            export: 'excel'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            generateCarExpensesExcel(data.data);
            Swal.close();
        } else {
            Swal.fire('هەڵە!', data.error || 'هەڵەیەک ڕویدا لە وەرگرتنی داتا', 'error');
        }
    })
    .catch(error => {
        console.error('Export error:', error);
        Swal.fire('هەڵە!', 'هەڵەیەک ڕویدا لە ئیکسپۆرتی داتا', 'error');
    });
}

// Function to generate Excel file for car expenses
function generateCarExpensesExcel(data) {
    // Create workbook and worksheet
    const wb = XLSX.utils.book_new();
    
    // Prepare data for Excel
    const excelData = [];
    
    // Add header row
    excelData.push([
        'سەیارەکان',
        'کۆی نرخی بەکارهێنانی گاز (خەرجی)',
        'کۆی نرخی بەکارهێنانی کاڵای کۆگا (خەرجی)',
        'خەرجی تر (خەرجی)',
        'کۆی گشتی بەبێ گاز',
        'کۆی گشتی',
        'کۆنکرێتی بارکراو (م³)',
        'داهاتی سەیارەکان'
    ]);
    
    // Process each car's data
    if (data.cars && Array.isArray(data.cars)) {
        data.cars.forEach(car => {
            const gasCost = parseFloat(car.gas_cost || 0);
            const materialCost = parseFloat(car.material_cost || 0);
            const otherExpenses = parseFloat(car.other_expenses || 0);
            const totalWithoutGas = materialCost + otherExpenses;
            const totalWithGas = gasCost + materialCost + otherExpenses;
            const concreteVolume = parseFloat(car.concrete_volume || 0);
            
            // Calculate income: (concrete volume × 5) - total expenses
            const income = (concreteVolume * 5) - totalWithGas;
            
            excelData.push([
                car.car_name || 'نامەڵەم',
                gasCost.toFixed(2),
                materialCost.toFixed(2),
                otherExpenses.toFixed(2),
                totalWithoutGas.toFixed(2),
                totalWithGas.toFixed(2),
                concreteVolume.toFixed(2),
                income.toFixed(2)
            ]);
        });
    }
    
    // Add summary row
    if (data.summary) {
        const summary = data.summary;
        excelData.push([]); // Empty row
        excelData.push([
            'کۆی گشتی',
            summary.total_gas_cost?.toFixed(2) || '0.00',
            summary.total_material_cost?.toFixed(2) || '0.00',
            summary.total_other_expenses?.toFixed(2) || '0.00',
            summary.total_without_gas?.toFixed(2) || '0.00',
            summary.total_with_gas?.toFixed(2) || '0.00',
            summary.total_concrete_volume?.toFixed(2) || '0.00',
            summary.total_income?.toFixed(2) || '0.00'
        ]);
    }
    
    // Add detailed breakdown sheet
    if (data.detailed_expenses && Array.isArray(data.detailed_expenses)) {
        const detailedData = [];
        
        // Add header for detailed sheet
        detailedData.push([
            'سەیارە',
            'جۆری خەرجی',
            'بڕی گاز (لیتر)',
            'نرخی گاز',
            'کاڵای کۆگا',
            'بڕی کاڵا',
            'نرخی کاڵا',
            'خەرجی تر',
            'بەروار',
            'کۆی خەرجی'
        ]);
        
        // Add detailed rows
        data.detailed_expenses.forEach(expense => {
            detailedData.push([
                expense.car_name || '',
                expense.expense_type || '',
                expense.gas_liters || '0',
                expense.gas_total_cost || '0',
                expense.material_name || '',
                expense.material_quantity || '0',
                expense.material_total_cost || '0',
                expense.amount_iqd || expense.amount_usd || '0',
                expense.date || '',
                expense.total_cost || '0'
            ]);
        });
        
        // Create detailed worksheet
        const detailedWs = XLSX.utils.aoa_to_sheet(detailedData);
        
        // Set column widths for detailed sheet
        const detailedColWidths = [
            { wch: 20 }, // سەیارە
            { wch: 25 }, // جۆری خەرجی
            { wch: 20 }, // بڕی گاز (لیتر)
            { wch: 20 }, // نرخی گاز
            { wch: 25 }, // کاڵای کۆگا
            { wch: 20 }, // بڕی کاڵا
            { wch: 20 }, // نرخی کاڵا
            { wch: 20 }, // خەرجی تر
            { wch: 15 }, // بەروار
            { wch: 20 }  // کۆی خەرجی
        ];
        detailedWs['!cols'] = detailedColWidths;
        
        // Add detailed worksheet to workbook
        XLSX.utils.book_append_sheet(wb, detailedWs, 'وردەکاری خەرجی');
    }
    
    // Create main worksheet
    const ws = XLSX.utils.aoa_to_sheet(excelData);
    
    // Set column widths
    const colWidths = [
        { wch: 20 }, // سەیارەکان
        { wch: 25 }, // کۆی نرخی بەکارهێنانی گاز (خەرجی)
        { wch: 30 }, // کۆی نرخی بەکارهێنانی کاڵای کۆگا (خەرجی)
        { wch: 20 }, // خەرجی تر (خەرجی)
        { wch: 25 }, // کۆی گشتی بەبێ گاز
        { wch: 20 }, // کۆی گشتی
        { wch: 25 }, // کۆنکرێتی بارکراو (م³)
        { wch: 20 }  // داهاتی سەیارەکان
    ];
    ws['!cols'] = colWidths;
    
    // Add main worksheet to workbook
    XLSX.utils.book_append_sheet(wb, ws, 'داهاتی سەیارەکان');
    
    // Generate filename with current date
    const currentDate = new Date().toISOString().split('T')[0];
    const filename = `وردەکاری_خەرجی_سەیارەکان_${currentDate}.xlsx`;
    
    // Download the file
    XLSX.writeFile(wb, filename);
    
    // Show success message
    Swal.fire('سەرکەوتوو!', 'فایلەکە بە سەرکەوتوویی داگرا', 'success');
}
