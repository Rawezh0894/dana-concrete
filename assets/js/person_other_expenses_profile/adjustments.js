// Adjustments Management for Person Other Expenses Profile
$(document).ready(function() {
    // When the adjustment tab is clicked, refresh the table
    $('#adjustment-tab').on('shown.bs.tab', function () {
        if (typeof loadAdjustmentTable === 'function') {
            loadAdjustmentTable();
        }
    });
});
