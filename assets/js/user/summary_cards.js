// Summary Cards Data Loading for Users Page
$(document).ready(function() {
    loadSummaryCardsData();
    
    // Refresh summary cards when users are updated
    $(document).on('userAdded userUpdated userDeleted', function() {
        loadSummaryCardsData();
    });
});

function loadSummaryCardsData() {
    $.ajax({
        url: '../process/users/get_summary_stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update summary cards
                $('#total-users').text(response.data.total_users);
                $('#total-admins').text(response.data.total_admins);
                $('#total-managers').text(response.data.total_managers);
            } else {
                console.error('Error loading summary data:', response.message);
                // Set default values
                $('#total-users').text('0');
                $('#total-admins').text('0');
                $('#total-managers').text('0');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            // Set default values on error
            $('#total-users').text('0');
            $('#total-admins').text('0');
            $('#total-managers').text('0');
        }
    });
} 