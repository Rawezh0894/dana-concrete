// Function to play notification sound
function playNotificationSound() {
    const audio = document.getElementById('notificationSound');
    if (audio) {
        // Reset audio to beginning
        audio.currentTime = 0;
        // Play the sound
        audio.play().catch(error => {
            console.log('Could not play notification sound:', error);
        });
    }
}

// Function to update unread notes badge
function updateUnreadNotesBadge() {
    fetch('../process/notes/get_unread_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById('unread-notes-badge');
                const count = data.unread_count;
                const previousCount = parseInt(badge.textContent) || 0;
                
                if (count > 0) {
                    badge.textContent = count;
                    badge.style.display = 'inline';
                    
                    // Play sound if count increased (new note added)
                    if (count > previousCount) {
                        playNotificationSound();
                    }
                } else {
                    badge.style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error fetching unread notes count:', error);
        });
}

// Update badge on page load
document.addEventListener('DOMContentLoaded', function() {
    updateUnreadNotesBadge();
    
    // Update badge every 30 seconds
    setInterval(updateUnreadNotesBadge, 30000);
});

// Listen for custom events from other parts of the application
document.addEventListener('noteAdded', function() {
    // Update badge when a new note is added
    updateUnreadNotesBadge();
});

document.addEventListener('noteMarkedAsRead', function() {
    // Update badge when a note is marked as read
    updateUnreadNotesBadge();
});

document.addEventListener('noteDeleted', function() {
    // Update badge when a note is deleted
    updateUnreadNotesBadge();
});

document.addEventListener('noteUpdated', function() {
    // Update badge when a note is updated
    updateUnreadNotesBadge();
});

document.addEventListener('noteAddedWithSound', function() {
    playNotificationSound();
});

// Export functions for manual updates
window.updateUnreadNotesBadge = updateUnreadNotesBadge;
window.playNotificationSound = playNotificationSound; 