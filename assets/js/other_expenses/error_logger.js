// Error Logger and Debug Utilities for Other Expenses Module
// This file provides comprehensive error handling and logging capabilities

// Global error logger object
window.ErrorLogger = {
    // Log levels
    LEVELS: {
        DEBUG: 0,
        INFO: 1,
        WARN: 2,
        ERROR: 3,
        FATAL: 4
    },

    // Current log level (can be changed dynamically)
    currentLevel: 2, // WARN level by default

    // Log a message with specified level
    log: function (level, message, data = null) {
        if (level >= this.currentLevel) {
            const timestamp = new Date().toISOString();
            const logEntry = {
                timestamp: timestamp,
                level: this.getLevelName(level),
                message: message,
                data: data,
                url: window.location.href,
                userAgent: navigator.userAgent
            };

            // Console output based on level
            switch (level) {
                case this.LEVELS.DEBUG:
                    console.log(`[DEBUG] ${message}`, data);
                    break;
                case this.LEVELS.INFO:
                    console.info(`[INFO] ${message}`, data);
                    break;
                case this.LEVELS.WARN:
                    console.warn(`[WARN] ${message}`, data);
                    break;
                case this.LEVELS.ERROR:
                    console.error(`[ERROR] ${message}`, data);
                    break;
                case this.LEVELS.FATAL:
                    console.error(`[FATAL] ${message}`, data);
                    break;
            }

            // Store in localStorage for debugging (limit to last 100 entries)
            this.storeLog(logEntry);
        }
    },

    // Get level name
    getLevelName: function (level) {
        for (let [name, value] of Object.entries(this.LEVELS)) {
            if (value === level) return name;
        }
        return 'UNKNOWN';
    },

    // Store log entry in localStorage
    storeLog: function (logEntry) {
        try {
            const logs = JSON.parse(localStorage.getItem('otherExpensesLogs') || '[]');
            logs.push(logEntry);

            // Keep only last 100 entries
            if (logs.length > 100) {
                logs.splice(0, logs.length - 100);
            }

            localStorage.setItem('otherExpensesLogs', JSON.stringify(logs));
        } catch (error) {
            console.error('Failed to store log entry:', error);
        }
    },

    // Convenience methods
    debug: function (message, data = null) {
        this.log(this.LEVELS.DEBUG, message, data);
    },

    info: function (message, data = null) {
        this.log(this.LEVELS.INFO, message, data);
    },

    warn: function (message, data = null) {
        this.log(this.LEVELS.WARN, message, data);
    },

    error: function (message, data = null) {
        this.log(this.LEVELS.ERROR, message, data);
    },

    fatal: function (message, data = null) {
        this.log(this.LEVELS.FATAL, message, data);
    },

    // Get all stored logs
    getLogs: function () {
        try {
            return JSON.parse(localStorage.getItem('otherExpensesLogs') || '[]');
        } catch (error) {
            console.error('Failed to retrieve logs:', error);
            return [];
        }
    },

    // Clear all stored logs
    clearLogs: function () {
        try {
            localStorage.removeItem('otherExpensesLogs');
            console.info('Logs cleared successfully');
        } catch (error) {
            console.error('Failed to clear logs:', error);
        }
    },

    // Export logs as JSON
    exportLogs: function () {
        const logs = this.getLogs();
        const blob = new Blob([JSON.stringify(logs, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `other-expenses-logs-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    },

    // Set log level
    setLevel: function (level) {
        if (typeof level === 'string') {
            level = this.LEVELS[level.toUpperCase()];
        }
        if (level !== undefined) {
            this.currentLevel = level;
            this.info(`Log level set to: ${this.getLevelName(level)}`);
        }
    }
};

// Global error handler for uncaught exceptions
window.addEventListener('error', function (event) {
    ErrorLogger.fatal('Uncaught JavaScript error', {
        message: event.message,
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno,
        error: event.error ? event.error.stack : null
    });
});

// Global promise rejection handler
window.addEventListener('unhandledrejection', function (event) {
    ErrorLogger.fatal('Unhandled promise rejection', {
        reason: event.reason,
        promise: event.promise
    });
});

// AJAX error interceptor
const originalFetch = window.fetch;
window.fetch = function (...args) {
    return originalFetch.apply(this, args)
        .then(response => {
            if (!response.ok) {
                ErrorLogger.error('HTTP request failed', {
                    url: args[0],
                    status: response.status,
                    statusText: response.statusText
                });
            }
            return response;
        })
        .catch(error => {
            ErrorLogger.error('Fetch request failed', {
                url: args[0],
                error: error.message,
                stack: error.stack
            });
            throw error;
        });
};

// Form validation error logger
window.logFormValidationError = function (formId, fieldName, errorMessage) {
    ErrorLogger.warn('Form validation error', {
        formId: formId,
        fieldName: fieldName,
        errorMessage: errorMessage
    });
};

// Database operation error logger
window.logDatabaseError = function (operation, table, error) {
    ErrorLogger.error('Database operation failed', {
        operation: operation,
        table: table,
        error: error.message || error,
        timestamp: new Date().toISOString()
    });
};

// User action logger
window.logUserAction = function (action, details = {}) {
    ErrorLogger.info('User action performed', {
        action: action,
        details: details,
        timestamp: new Date().toISOString()
    });
};

// Initialize error logger
document.addEventListener('DOMContentLoaded', function () {
    /* ErrorLogger.info('Error logger initialized', {
        currentLevel: ErrorLogger.getLevelName(ErrorLogger.currentLevel),
        userAgent: navigator.userAgent,
        url: window.location.href
    }); */
});

// Debug utilities
window.debugOtherExpenses = {
    // Show current form state
    showFormState: function (formId) {
        const form = document.getElementById(formId);
        if (!form) {
            ErrorLogger.warn('Form not found', { formId: formId });
            return;
        }

        const formData = new FormData(form);
        const state = {};
        for (let [key, value] of formData.entries()) {
            state[key] = value;
        }

        ErrorLogger.debug('Form state', { formId: formId, state: state });
        return state;
    },

    // Show all form states
    showAllFormStates: function () {
        const forms = ['addExpenseForm', 'editExpenseForm'];
        forms.forEach(formId => {
            this.showFormState(formId);
        });
    },

    // Test API endpoints
    testAPI: function (endpoint) {
        ErrorLogger.info('Testing API endpoint', { endpoint: endpoint });

        fetch(endpoint)
            .then(response => {
                ErrorLogger.info('API test response', {
                    endpoint: endpoint,
                    status: response.status,
                    ok: response.ok
                });
                return response.json();
            })
            .then(data => {
                ErrorLogger.debug('API test data', { endpoint: endpoint, data: data });
            })
            .catch(error => {
                ErrorLogger.error('API test failed', { endpoint: endpoint, error: error.message });
            });
    },

    // Test all API endpoints
    testAllAPIs: function () {
        const endpoints = [
            '../process/other_expenses/select_expenses.php',
            '../process/other_expenses/select_materials.php',
            '../process/other_expenses/select_persons.php',
            '../process/other_expenses/select_employees.php',
            '../process/other_expenses/select_cars.php'
        ];

        endpoints.forEach(endpoint => {
            this.testAPI(endpoint);
        });
    },

    // Show current page state
    showPageState: function () {
        const state = {
            url: window.location.href,
            forms: {},
            modals: {},
            tables: {}
        };

        // Check forms
        ['addExpenseForm', 'editExpenseForm'].forEach(formId => {
            const form = document.getElementById(formId);
            state.forms[formId] = {
                exists: !!form,
                visible: form ? form.offsetParent !== null : false
            };
        });

        // Check modals
        ['addExpenseModal', 'editExpenseModal'].forEach(modalId => {
            const modal = document.getElementById(modalId);
            state.modals[modalId] = {
                exists: !!modal,
                visible: modal ? modal.classList.contains('show') : false
            };
        });

        // Check tables
        const table = document.getElementById('otherExpensesTable');
        state.tables.otherExpensesTable = {
            exists: !!table,
            rowCount: table ? table.rows.length : 0
        };

        ErrorLogger.info('Current page state', state);
        return state;
    }
};

// Make debug utilities available globally
window.ErrorLogger.debug = ErrorLogger.debug;
window.ErrorLogger.info = ErrorLogger.info;
window.ErrorLogger.warn = ErrorLogger.warn;
window.ErrorLogger.error = ErrorLogger.error;
window.ErrorLogger.fatal = ErrorLogger.fatal;
window.ErrorLogger.getLogs = ErrorLogger.getLogs;
window.ErrorLogger.clearLogs = ErrorLogger.clearLogs;
window.ErrorLogger.exportLogs = ErrorLogger.exportLogs;
window.ErrorLogger.setLevel = ErrorLogger.setLevel; 