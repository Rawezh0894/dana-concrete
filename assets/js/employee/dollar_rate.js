// Dollar Rate functionality for Employee Page
$(document).ready(function() {
    // Load dollar rate when page loads
    fetchDollarRate();
    
    // Handle refresh button click
    $('#refreshDollarRate').on('click', function() {
        const $btn = $(this);
        const $icon = $btn.find('i');
        
        // Show loading state
        $icon.addClass('fa-spin');
        $btn.prop('disabled', true);
        
        fetchDollarRate();
        
        // Remove loading state after a short delay
        setTimeout(function() {
            $icon.removeClass('fa-spin');
            $btn.prop('disabled', false);
        }, 1000);
    });
});

// Function to fetch dollar rate from API
function fetchDollarRate() {
    const apiUrl = 'https://dinarapi.hediworks.site/api/get-price';
    const apiToken = 'S3gl9SVEkZ1Vvc93cCjsbLLmwDvgzk';
    const id = '8'; // 100 dollar ID
    
    // Show loading state on the card
    const $card = $('#dollar_rate');
    const originalValue = $card.text();
    $card.text('جێبەجێکردن...');
    
    $.ajax({
        url: `${apiUrl}?id=${id}&api_token=${apiToken}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response && response.value) {
                $('#dollar_rate').text(response.value.toLocaleString());
                console.log('Dollar rate fetched successfully:', response.value);
                // Show success notification
                Swal.fire({
                    icon: 'success',
                    title: 'نرخی دۆلار نوێکرایەوە',
                    text: `نرخی ١٠٠ دۆلار: ${response.value.toLocaleString()} دینار`,
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                console.warn('No value data in API response:', response);
                // Restore original value if API doesn't return value
                $('#dollar_rate').text(originalValue);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching dollar rate:', error);
            console.error('Response:', xhr.responseText);
            // Restore original value
            $('#dollar_rate').text(originalValue);
            // Show error notification
            Swal.fire({
                icon: 'error',
                title: 'هەڵە لە وەرگرتنی نرخی دۆلار',
                text: 'نەتوانرا نرخی دۆلار وەربگیرێت',
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }
    });
} 