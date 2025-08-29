// Excel Export Functions for Income from Cars
// ئیکسپۆرتی Excel بۆ داهاتی سەیارەکان

// Function to export income from cars to Excel with detailed breakdown
function exportIncomeFromCarsToExcel() {
    // Show loading message
    Swal.fire({
        title: 'چاوەڕوان بە...',
        text: 'داتا بەرەو Excel دەنێردرێت',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Get current filter values
    const fromDate = document.getElementById('fromDate')?.value || '';
    const toDate = document.getElementById('toDate')?.value || '';
    const mixerCar = document.getElementById('mixerCarFilter')?.value || '';
    const mixerDriver = document.getElementById('mixerDriverFilter')?.value || '';
    const pumpCar = document.getElementById('pumpCarFilter')?.value || '';
    const pumpDriver = document.getElementById('pumpDriverFilter')?.value || '';
    const customer = document.getElementById('customerFilter')?.value || '';

    // Fetch data from API
    fetch('../process/income_from_cars/get_informations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            from_date: fromDate,
            to_date: toDate,
            mixer_car: mixerCar,
            mixer_driver: mixerDriver,
            pump_car: pumpCar,
            pump_driver: pumpDriver,
            customer: customer,
            export: 'excel'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            generateIncomeFromCarsExcel(data.data);
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

// Function to generate Excel file for income from cars
function generateIncomeFromCarsExcel(data) {
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
    
    // Create worksheet
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
    
    // Add worksheet to workbook
    XLSX.utils.book_append_sheet(wb, ws, 'داهاتی سەیارەکان');
    
    // Generate filename with current date
    const currentDate = new Date().toISOString().split('T')[0];
    const filename = `داهاتی_سەیارەکان_${currentDate}.xlsx`;
    
    // Download the file
    XLSX.writeFile(wb, filename);
    
    // Show success message
    Swal.fire('سەرکەوتوو!', 'فایلەکە بە سەرکەوتوویی داگرا', 'success');
}

// Function to export basic data to Excel (existing function)
function exportToExcel() {
    // Show loading message
    Swal.fire({
        title: 'چاوەڕوان بە...',
        text: 'داتا بەرەو Excel دەنێردرێت',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Get current filter values
    const fromDate = document.getElementById('fromDate')?.value || '';
    const toDate = document.getElementById('toDate')?.value || '';
    const mixerCar = document.getElementById('mixerCarFilter')?.value || '';
    const mixerDriver = document.getElementById('mixerDriverFilter')?.value || '';
    const pumpCar = document.getElementById('pumpCarFilter')?.value || '';
    const pumpDriver = document.getElementById('pumpDriverFilter')?.value || '';
    const customer = document.getElementById('customerFilter')?.value || '';

    // Fetch data from API
    fetch('../process/income_from_cars/get_informations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            from_date: fromDate,
            to_date: toDate,
            mixer_car: mixerCar,
            mixer_driver: mixerDriver,
            pump_car: pumpCar,
            pump_driver: pumpDriver,
            customer: customer,
            export: 'basic'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            generateBasicExcel(data.data);
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

// Function to generate basic Excel file
function generateBasicExcel(data) {
    // Create workbook and worksheet
    const wb = XLSX.utils.book_new();
    
    // Prepare data for Excel
    const excelData = [];
    
    // Add header row
    excelData.push([
        'ژمارەی پسوڵە',
        'کڕیار',
        'شوێن',
        'بڕ (م³)',
        'سەیارەی میکسەر',
        'شۆفێری میکسەر',
        'سەیارەی پۆمپ',
        'شۆفێری پۆمپ',
        'بەروار',
        'وەرگر'
    ]);
    
    // Add data rows
    if (data.receipts && Array.isArray(data.receipts)) {
        data.receipts.forEach(receipt => {
            excelData.push([
                receipt.receipt_number || '',
                receipt.customer_name || '',
                receipt.location || '',
                receipt.meter_amount || '',
                receipt.mixer_car_name || '',
                receipt.mixer_driver_name || '',
                receipt.pump_car_name || '',
                receipt.pump_driver_name || '',
                receipt.created_at || '',
                receipt.receiver_name || ''
            ]);
        });
    }
    
    // Create worksheet
    const ws = XLSX.utils.aoa_to_sheet(excelData);
    
    // Set column widths
    const colWidths = [
        { wch: 15 }, // ژمارەی پسوڵە
        { wch: 20 }, // کڕیار
        { wch: 20 }, // شوێن
        { wch: 15 }, // بڕ (م³)
        { wch: 20 }, // سەیارەی میکسەر
        { wch: 20 }, // شۆفێری میکسەر
        { wch: 20 }, // سەیارەی پۆمپ
        { wch: 20 }, // شۆفێری پۆمپ
        { wch: 15 }, // بەروار
        { wch: 20 }  // وەرگر
    ];
    ws['!cols'] = colWidths;
    
    // Add worksheet to workbook
    XLSX.utils.book_append_sheet(wb, ws, 'داهاتی سەیارەکان');
    
    // Generate filename with current date
    const currentDate = new Date().toISOString().split('T')[0];
    const filename = `داهاتی_سەیارەکان_پوختە_${currentDate}.xlsx`;
    
    // Download the file
    XLSX.writeFile(wb, filename);
    
    // Show success message
    Swal.fire('سەرکەوتوو!', 'فایلەکە بە سەرکەوتوویی داگرا', 'success');
}
