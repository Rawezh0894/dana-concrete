// Main assets JavaScript file
// This file can be used for any global asset-related functions

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('ku-Arab-IQ');
}

// Calculate depreciation (for reference)
function calculateStraightLineDepreciation(purchaseCost, salvageValue, usefulLifeYears) {
    if (usefulLifeYears <= 0) return 0;
    return (purchaseCost - salvageValue) / usefulLifeYears;
}

function calculateDecliningBalanceDepreciation(bookValue, depreciationRate) {
    return bookValue * (depreciationRate / 100);
}

function calculateUnitsOfProductionDepreciation(purchaseCost, salvageValue, totalUnits, unitsUsed) {
    if (totalUnits <= 0) return 0;
    const depreciationPerUnit = (purchaseCost - salvageValue) / totalUnits;
    return depreciationPerUnit * unitsUsed;
}
