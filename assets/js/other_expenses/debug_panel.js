// Debug Panel for Other Expenses Module
// This provides easy access to debugging tools from the browser console

// Create debug panel object
window.OtherExpensesDebug = {
    // Show debug information
    info: function() {
        console.group('🔍 Other Expenses Debug Info');
        console.log('📄 Current URL:', window.location.href);
        console.log('🕒 Page Load Time:', new Date().toLocaleString());
        console.log('🌐 User Agent:', navigator.userAgent);
        
        // Check if error logger is available
        if (window.ErrorLogger) {
            console.log('✅ Error Logger: Available');
            console.log('📊 Log Level:', ErrorLogger.getLevelName(ErrorLogger.currentLevel));
            console.log('📝 Log Count:', ErrorLogger.getLogs().length);
        } else {
            console.log('❌ Error Logger: Not Available');
        }
        
        // Check forms
        const forms = ['addExpenseForm', 'editExpenseForm'];
        forms.forEach(formId => {
            const form = document.getElementById(formId);
            console.log(`📋 ${formId}:`, form ? 'Found' : 'Not Found');
        });
        
        // Check modals
        const modals = ['addExpenseModal', 'editExpenseModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            console.log(`🪟 ${modalId}:`, modal ? 'Found' : 'Not Found');
        });
        
        // Check table
        const table = document.getElementById('otherExpensesTable');
        console.log('📊 Table:', table ? `Found (${table.rows.length} rows)` : 'Not Found');
        
        console.groupEnd();
    },

    // Test all functionality
    testAll: function() {
        console.group('🧪 Testing All Functionality');
        
        // Test API endpoints
        this.testAPIs();
        
        // Test forms
        this.testForms();
        
        // Test calculations
        this.testCalculations();
        
        console.groupEnd();
    },

    // Test API endpoints
    testAPIs: function() {
        console.group('🌐 Testing API Endpoints');
        
        const endpoints = [
            '../process/other_expenses/select_expenses.php',
            '../process/other_expenses/select_materials.php',
            '../process/other_expenses/select_persons.php',
            '../process/other_expenses/select_employees.php',
            '../process/other_expenses/select_cars.php'
        ];

        endpoints.forEach(endpoint => {
            fetch(endpoint)
                .then(response => {
                    console.log(`✅ ${endpoint}:`, response.status, response.statusText);
                })
                .catch(error => {
                    console.error(`❌ ${endpoint}:`, error.message);
                });
        });
        
        console.groupEnd();
    },

    // Test forms
    testForms: function() {
        console.group('📋 Testing Forms');
        
        const forms = ['addExpenseForm', 'editExpenseForm'];
        forms.forEach(formId => {
            const form = document.getElementById(formId);
            if (form) {
                console.log(`✅ ${formId}: Found`);
                const fields = form.querySelectorAll('input, select, textarea');
                console.log(`   Fields: ${fields.length}`);
            } else {
                console.log(`❌ ${formId}: Not Found`);
            }
        });
        
        console.groupEnd();
    },

    // Test calculations
    testCalculations: function() {
        console.group('🧮 Testing Calculations');
        
        // Test material total cost calculation
        if (typeof window.calculateMaterialTotalCost === 'function') {
            console.log('✅ calculateMaterialTotalCost: Available');
        } else {
            console.log('❌ calculateMaterialTotalCost: Not Available');
        }
        
        // Test gas total cost calculation
        if (typeof window.calculateGasTotalCost === 'function') {
            console.log('✅ calculateGasTotalCost: Available');
        } else {
            console.log('❌ calculateGasTotalCost: Not Available');
        }
        
        // Test material availability check
        if (typeof window.checkMaterialAvailability === 'function') {
            console.log('✅ checkMaterialAvailability: Available');
        } else {
            console.log('❌ checkMaterialAvailability: Not Available');
        }
        
        console.groupEnd();
    },

    // Show form data
    showFormData: function(formId) {
        const form = document.getElementById(formId);
        if (!form) {
            console.error(`Form ${formId} not found`);
            return;
        }

        const formData = new FormData(form);
        const data = {};
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }

        console.group(`📋 Form Data: ${formId}`);
        console.table(data);
        console.groupEnd();
    },

    // Show all form data
    showAllFormData: function() {
        const forms = ['addExpenseForm', 'editExpenseForm'];
        forms.forEach(formId => {
            this.showFormData(formId);
        });
    },

    // Simulate form submission
    simulateSubmit: function(formId) {
        const form = document.getElementById(formId);
        if (!form) {
            console.error(`Form ${formId} not found`);
            return;
        }

        console.log(`🚀 Simulating submit for ${formId}`);
        const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
        form.dispatchEvent(submitEvent);
    },

    // Test foreign key constraint handling
    testForeignKeyHandling: function() {
        console.group('🔗 Testing Foreign Key Constraint Handling');
        
        const form = document.getElementById('addExpenseForm');
        if (!form) {
            console.error('Add expense form not found');
            console.groupEnd();
            return;
        }

        // Test with empty foreign key fields
        console.log('Testing with empty foreign key fields...');
        
        // Set required field
        const expenseType = document.getElementById('expense_type');
        if (expenseType) {
            expenseType.value = 'خەرجی تر';
        }

        // Clear foreign key fields
        const personId = document.getElementById('person_id');
        const employeeId = document.getElementById('employee_id');
        const carId = document.getElementById('car_id');
        const materialId = document.getElementById('material_id');

        if (personId) personId.value = '';
        if (employeeId) employeeId.value = '';
        if (carId) carId.value = '';
        if (materialId) materialId.value = '';

        console.log('Foreign key fields cleared');
        console.log('Form state:', {
            expense_type: expenseType?.value,
            person_id: personId?.value,
            employee_id: employeeId?.value,
            car_id: carId?.value,
            material_id: materialId?.value
        });

        console.groupEnd();
    },

    // Test field visibility
    testFieldVisibility: function() {
        console.group('👁️ Testing Field Visibility');
        
        const expenseType = document.getElementById('expense_type');
        if (expenseType) {
            console.log('Current expense type:', expenseType.value);
            
            // Test visibility for each expense type
            const types = ['', 'بەکارهێنانی کاڵای کۆگا', 'بەکارهێنانی گاز', 'خەرجی تر'];
            types.forEach(type => {
                console.log(`Testing type: "${type}"`);
                expenseType.value = type;
                expenseType.dispatchEvent(new Event('change'));
            });
        } else {
            console.log('❌ expense_type field not found');
        }
        
        console.groupEnd();
    },

    // Show logs
    showLogs: function() {
        if (window.ErrorLogger) {
            const logs = ErrorLogger.getLogs();
            console.group('📝 Recent Logs');
            logs.slice(-10).forEach(log => {
                console.log(`[${log.level}] ${log.message}`, log.data);
            });
            console.groupEnd();
        } else {
            console.log('❌ Error Logger not available');
        }
    },

    // Clear logs
    clearLogs: function() {
        if (window.ErrorLogger) {
            ErrorLogger.clearLogs();
            console.log('✅ Logs cleared');
        } else {
            console.log('❌ Error Logger not available');
        }
    },

    // Export logs
    exportLogs: function() {
        if (window.ErrorLogger) {
            ErrorLogger.exportLogs();
            console.log('✅ Logs exported');
        } else {
            console.log('❌ Error Logger not available');
        }
    },

    // Set log level
    setLogLevel: function(level) {
        if (window.ErrorLogger) {
            ErrorLogger.setLevel(level);
            console.log(`✅ Log level set to: ${level}`);
        } else {
            console.log('❌ Error Logger not available');
        }
    },

    // Help function
    help: function() {
        console.group('❓ Other Expenses Debug Help');
        console.log('Available commands:');
        console.log('  OtherExpensesDebug.info() - Show basic info');
        console.log('  OtherExpensesDebug.testAll() - Test all functionality');
        console.log('  OtherExpensesDebug.testAPIs() - Test API endpoints');
        console.log('  OtherExpensesDebug.testForms() - Test forms');
        console.log('  OtherExpensesDebug.testCalculations() - Test calculations');
        console.log('  OtherExpensesDebug.showFormData(formId) - Show form data');
        console.log('  OtherExpensesDebug.showAllFormData() - Show all form data');
        console.log('  OtherExpensesDebug.simulateSubmit(formId) - Simulate form submit');
        console.log('  OtherExpensesDebug.testFieldVisibility() - Test field visibility');
        console.log('  OtherExpensesDebug.testForeignKeyHandling() - Test foreign key handling');
        console.log('  OtherExpensesDebug.showLogs() - Show recent logs');
        console.log('  OtherExpensesDebug.clearLogs() - Clear logs');
        console.log('  OtherExpensesDebug.exportLogs() - Export logs');
        console.log('  OtherExpensesDebug.setLogLevel(level) - Set log level');
        console.log('  OtherExpensesDebug.help() - Show this help');
        console.groupEnd();
    }
};

// Initialize debug panel
document.addEventListener('DOMContentLoaded', function() {
    // Make debug panel available globally
    window.debug = OtherExpensesDebug;
    
    // Log initialization
    if (window.ErrorLogger) {
        ErrorLogger.info('Debug panel initialized');
    }
    
    // Show help in console
    console.log('🔧 Other Expenses Debug Panel loaded!');
    console.log('Type OtherExpensesDebug.help() or debug.help() for available commands');
});

// Add keyboard shortcut for quick access (Ctrl+Shift+D)
document.addEventListener('keydown', function(event) {
    if (event.ctrlKey && event.shiftKey && event.key === 'D') {
        event.preventDefault();
        console.log('🔧 Debug panel activated via keyboard shortcut');
        OtherExpensesDebug.info();
    }
}); 