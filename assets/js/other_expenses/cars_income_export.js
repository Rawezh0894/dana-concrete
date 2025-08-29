// Cars Income Export Functions
// ئیکسپۆرتی داهاتی سەیارەکان بۆ Excel

// Function to get USD exchange rate from API
async function getUsdExchangeRate() {
    try {
        const response = await fetch('../process/reporst/get_information.php');
        const data = await response.json();
        if (data.success && data.data && data.data.usd_iqd_rate) {
            return data.data.usd_iqd_rate;
        }
        return 139250; // Default fallback value
    } catch (error) {
        console.error('Error fetching USD rate:', error);
        return 139250; // Default fallback value
    }
}

// Function to convert IQD to USD
function convertIqdToUsd(iqdAmount, exchangeRate) {
    if (!exchangeRate || exchangeRate <= 0) return 0;
    return (iqdAmount / (exchangeRate / 100));
}

// Function to calculate cars income and export to Excel
async function exportCarsIncomeExcel() {
    try {
        // Show loading
        Swal.fire({
            title: 'چاوەڕوان بە...',
            text: 'داتای داهاتی سەیارەکان وەردەگرێتەوە',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Get USD exchange rate
        const usdRate = await getUsdExchangeRate();
        
        // Get cars data
        const carsResponse = await fetch('../process/car/select_car.php');
        const carsData = await carsResponse.json();
        
        // Get concrete receipts data (concrete delivered by each car)
        const receiptsResponse = await fetch('../process/concrete_receipts/select_concrete_receipts.php');
        const receiptsData = await receiptsResponse.json();
        
        // Get other expenses data
        const expensesResponse = await fetch('../process/other_expenses/select_expenses.php');
        const expensesData = await expensesResponse.json();
        
        // Check response structure and handle different formats
        let carsArray, receiptsArray, expensesArray;
        
        // Handle cars data (can be direct array or {success: true, data: [...]})
        if (Array.isArray(carsData)) {
            carsArray = carsData;
        } else if (carsData.success && Array.isArray(carsData.data)) {
            carsArray = carsData.data;
        } else {
            throw new Error('هەڵە لە داتای سەیارەکان - تکایە پشکنە');
        }
        
        // Handle receipts data
        if (receiptsData.success && Array.isArray(receiptsData.data)) {
            receiptsArray = receiptsData.data;
        } else {
            throw new Error('هەڵە لە داتای پسوڵەکان - تکایە پشکنە');
        }
        
        // Handle expenses data
        if (expensesData.success && Array.isArray(expensesData.expenses)) {
            expensesArray = expensesData.expenses;
        } else {
            throw new Error('هەڵە لە داتای خەرجیەکان - تکایە پشکنە');
        }
        
        // Process data
        console.log('Processing data with USD rate:', usdRate);
        console.log('Cars array:', carsArray);
        console.log('Receipts array:', receiptsArray);
        console.log('Expenses array:', expensesArray);
        
        const carsIncomeData = processCarsIncomeData(
            carsArray, 
            receiptsArray, 
            expensesArray, 
            usdRate
        );
        
        // Export to Excel
        exportCarsIncomeToExcel(carsIncomeData);
        
        Swal.fire('سەرکەوتوو!', 'داتای داهاتی سەیارەکان دانەدرا بۆ Excel', 'success');
        
    } catch (error) {
        console.error('Export error:', error);
        Swal.fire('هەڵە!', 'هەڵە لە دانەدانی داتا: ' + error.message, 'error');
    }
}

// Function to process cars income data
function processCarsIncomeData(cars, receipts, expenses, usdRate) {
    const carsIncome = [];
    
    cars.forEach(car => {
        const carId = car.id;
        const carName = car.name;
        
        // Calculate concrete delivered by this car (mixer car)
        const concreteDelivered = receipts.filter(receipt => 
            receipt.mixer_car_id == carId
        ).reduce((total, receipt) => {
            return total + parseFloat(receipt.meter_amount || 0);
        }, 0);
        
        // Calculate income from concrete (5$ per cubic meter)
        const concreteIncome = concreteDelivered * 5;
        
        // Calculate expenses for this car
        const carExpenses = expenses.filter(expense => 
            expense.car_id == carId
        );
        
        // Gas expenses (all in IQD)
        const gasExpenses = carExpenses.filter(expense => 
            expense.expense_type === 'بەکارهێنانی گاز'
        ).reduce((total, expense) => {
            return total + parseFloat(expense.gas_total_cost || 0);
        }, 0);
        
        // Convert gas expenses to USD
        const gasExpensesUsd = convertIqdToUsd(gasExpenses, usdRate);
        
        // Material expenses (warehouse usage)
        const materialExpenses = carExpenses.filter(expense => 
            expense.expense_type === 'بەکارهێنانی کاڵای کۆگا'
        ).reduce((total, expense) => {
            let expenseUsd = parseFloat(expense.amount_usd || 0);
            let expenseIqd = parseFloat(expense.amount_iqd || 0);
            
            // Convert IQD to USD
            if (expenseIqd > 0) {
                expenseUsd += convertIqdToUsd(expenseIqd, usdRate);
            }
            
            return total + expenseUsd;
        }, 0);
        
        // Other expenses
        const otherExpenses = carExpenses.filter(expense => 
            expense.expense_type === 'خەرجی تر'
        ).reduce((total, expense) => {
            let expenseUsd = parseFloat(expense.amount_usd || 0);
            let expenseIqd = parseFloat(expense.amount_iqd || 0);
            
            // Convert IQD to USD
            if (expenseIqd > 0) {
                expenseUsd += convertIqdToUsd(expenseIqd, usdRate);
            }
            
            return total + expenseUsd;
        }, 0);
        
        // Calculate totals
        const totalWithoutGas = materialExpenses + otherExpenses;
        const totalExpenses = gasExpensesUsd + materialExpenses + otherExpenses;
        const netIncome = concreteIncome - totalExpenses;
        
        carsIncome.push({
            car_name: carName,
            gas_expenses_usd: gasExpensesUsd,
            material_expenses_usd: materialExpenses,
            other_expenses_usd: otherExpenses,
            total_without_gas: totalWithoutGas,
            total_expenses: totalExpenses,
            concrete_income: concreteIncome,
            net_income: netIncome,
            concrete_meters: concreteDelivered
        });
    });
    
    return carsIncome;
}

// Function to export cars income data to Excel
function exportCarsIncomeToExcel(carsIncomeData) {
    // Create workbook
    const wb = XLSX.utils.book_new();
    
    // Prepare data for Excel
    const excelData = carsIncomeData.map(car => [
        car.car_name,                    // سەیارەکان
        car.gas_expenses_usd,            // کۆی نرخی بەکارهێنانی گاز (خەرجی)
        car.material_expenses_usd,       // کۆی نرخی بەکارهێنانی کاڵای کۆگا (خەرجی)
        car.other_expenses_usd,          // خەرجی تر (خەرجی)
        car.total_without_gas,           // کۆی گشتی بەبێ گاز
        car.total_expenses,              // کۆی گشتی
        car.concrete_income,             // داهاتی کۆنکرێت (م³ × 5$)
        car.net_income,                  // داهاتی سەیارەکان (داهات - خەرجی)
        car.concrete_meters              // مەتری سێجای بارکراو
    ]);
    
    // Add headers
    const headers = [
        'سەیارەکان',
        'کۆی نرخی بەکارهێنانی گاز (خەرجی) - USD',
        'کۆی نرخی بەکارهێنانی کاڵای کۆگا (خەرجی) - USD',
        'خەرجی تر (خەرجی) - USD',
        'کۆی گشتی بەبێ گاز - USD',
        'کۆی گشتی - USD',
        'داهاتی کۆنکرێت - USD',
        'داهاتی سەیارەکان - USD',
        'مەتری سێجای بارکراو - م³'
    ];
    
    excelData.unshift(headers);
    
    // Create worksheet
    const ws = XLSX.utils.aoa_to_sheet(excelData);
    
    // Set column widths
    const colWidths = [
        { wch: 20 }, // سەیارەکان
        { wch: 25 }, // گاز
        { wch: 30 }, // کاڵای کۆگا
        { wch: 20 }, // خەرجی تر
        { wch: 25 }, // کۆی بەبێ گاز
        { wch: 20 }, // کۆی گشتی
        { wch: 25 }, // داهاتی کۆنکرێت
        { wch: 25 }, // داهاتی سەیارەکان
        { wch: 25 }  // مەتری سێجا
    ];
    ws['!cols'] = colWidths;
    
    // Add worksheet to workbook
    XLSX.utils.book_append_sheet(wb, ws, 'داهاتی سەیارەکان');
    
    // Generate filename with current date
    const currentDate = new Date().toISOString().split('T')[0];
    const filename = `داهاتی_سەیارەکان_${currentDate}.xlsx`;
    
    // Save file
    XLSX.writeFile(wb, filename);
}

// Function to export summary cards data
async function exportCarsIncomeSummaryExcel() {
    try {
        // Show loading
        Swal.fire({
            title: 'چاوەڕوان بە...',
            text: 'داتای کورتەی داهاتی سەیارەکان وەردەگرێتەوە',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Get USD exchange rate
        const usdRate = await getUsdExchangeRate();
        
        // Get summary data
        const summaryData = await getCarsIncomeSummary(usdRate);
        
        // Export to Excel
        exportSummaryToExcel(summaryData);
        
        Swal.fire('سەرکەوتوو!', 'داتای کورتەی داهاتی سەیارەکان دانەدرا بۆ Excel', 'success');
        
    } catch (error) {
        console.error('Export summary error:', error);
        Swal.fire('هەڵە!', 'هەڵە لە دانەدانی داتا: ' + error.message, 'error');
    }
}

// Function to get cars income summary
async function getCarsIncomeSummary(usdRate) {
    // Get all required data
    const [cars, receipts, expenses] = await Promise.all([
        fetch('../process/car/select_car.php').then(r => r.json()),
        fetch('../process/concrete_receipts/select_concrete_receipts.php').then(r => r.json()),
        fetch('../process/other_expenses/select_expenses.php').then(r => r.json())
    ]);
    
    // Handle different response structures
    let carsArray, receiptsArray, expensesArray;
    
    // Handle cars data
    if (Array.isArray(cars)) {
        carsArray = cars;
    } else if (cars.success && Array.isArray(cars.data)) {
        carsArray = cars.data;
    } else {
        throw new Error('هەڵە لە داتای سەیارەکان - تکایە پشکنە');
    }
    
    // Handle receipts data
    if (receipts.success && Array.isArray(receipts.data)) {
        receiptsArray = receipts.data;
    } else {
        throw new Error('هەڵە لە داتای پسوڵەکان - تکایە پشکنە');
    }
    
    // Handle expenses data
    if (expenses.success && Array.isArray(expenses.expenses)) {
        expensesArray = expenses.expenses;
    } else {
        throw new Error('هەڵە لە داتای خەرجیەکان - تکایە پشکنە');
    }
    
    // Calculate totals
    const totalConcreteMeters = receiptsArray.reduce((total, receipt) => {
        return total + parseFloat(receipt.meter_amount || 0);
    }, 0);
    
    const totalConcreteIncome = totalConcreteMeters * 5;
    
    const totalGasExpenses = expensesArray
        .filter(expense => expense.expense_type === 'بەکارهێنانی گاز')
        .reduce((total, expense) => {
            return total + parseFloat(expense.gas_total_cost || 0);
        }, 0);
    
    const totalGasExpensesUsd = convertIqdToUsd(totalGasExpenses, usdRate);
    
    const totalMaterialExpenses = expensesArray
        .filter(expense => expense.expense_type === 'بەکارهێنانی کاڵای کۆگا')
        .reduce((total, expense) => {
            let expenseUsd = parseFloat(expense.amount_usd || 0);
            let expenseIqd = parseFloat(expense.amount_iqd || 0);
            
            if (expenseIqd > 0) {
                expenseUsd += convertIqdToUsd(expenseIqd, usdRate);
            }
            
            return total + expenseUsd;
        }, 0);
    
    const totalOtherExpenses = expensesArray
        .filter(expense => expense.expense_type === 'خەرجی تر')
        .reduce((total, expense) => {
            let expenseUsd = parseFloat(expense.amount_usd || 0);
            let expenseIqd = parseFloat(expense.amount_iqd || 0);
            
            if (expenseIqd > 0) {
                expenseUsd += convertIqdToUsd(expenseIqd, usdRate);
            }
            
            return total + expenseUsd;
        }, 0);
    
    const totalExpenses = totalGasExpensesUsd + totalMaterialExpenses + totalOtherExpenses;
    const netIncome = totalConcreteIncome - totalExpenses;
    
    return {
        total_cars: carsArray.length,
        total_concrete_meters: totalConcreteMeters,
        total_concrete_income: totalConcreteIncome,
        total_gas_expenses_usd: totalGasExpensesUsd,
        total_material_expenses_usd: totalMaterialExpenses,
        total_other_expenses_usd: totalOtherExpenses,
        total_expenses: totalExpenses,
        net_income: netIncome,
        usd_exchange_rate: usdRate
    };
}

// Function to export summary to Excel
function exportSummaryToExcel(summaryData) {
    // Create workbook
    const wb = XLSX.utils.book_new();
    
    // Prepare summary data
    const summaryRows = [
        ['بەهای داتا', 'بڕ', 'یەکە'],
        ['کۆی سەیارەکان', summaryData.total_cars, 'سەیارە'],
        ['کۆی مەتری سێجای بارکراو', summaryData.total_concrete_meters, 'م³'],
        ['داهاتی کۆنکرێت (5$ × م³)', summaryData.total_concrete_income, 'USD'],
        ['کۆی خەرجی گاز', summaryData.total_gas_expenses_usd, 'USD'],
        ['کۆی خەرجی کاڵای کۆگا', summaryData.total_material_expenses_usd, 'USD'],
        ['کۆی خەرجی تر', summaryData.total_other_expenses_usd, 'USD'],
        ['کۆی گشتی خەرجی', summaryData.total_expenses, 'USD'],
        ['داهاتی خاوێن', summaryData.net_income, 'USD'],
        ['نرخی 100 دۆلار', summaryData.usd_exchange_rate, 'دینار']
    ];
    
    // Create worksheet
    const ws = XLSX.utils.aoa_to_sheet(summaryRows);
    
    // Set column widths
    ws['!cols'] = [
        { wch: 35 }, // بەهای داتا
        { wch: 20 }, // بڕ
        { wch: 15 }  // یەکە
    ];
    
    // Add worksheet to workbook
    XLSX.utils.book_append_sheet(wb, ws, 'کورتەی داهاتی سەیارەکان');
    
    // Generate filename
    const currentDate = new Date().toISOString().split('T')[0];
    const filename = `کورتەی_داهاتی_سەیارەکان_${currentDate}.xlsx`;
    
    // Save file
    XLSX.writeFile(wb, filename);
}

// Export functions to global scope
window.exportCarsIncomeExcel = exportCarsIncomeExcel;
window.exportCarsIncomeSummaryExcel = exportCarsIncomeSummaryExcel;
