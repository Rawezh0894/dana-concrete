// Function to update unread notes badge
function updateUnreadNotesBadge() {
    fetch('../process/notes/get_unread_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById('unread-notes-badge');
                const count = data.unread_count;
                
                if (count > 0) {
                    badge.textContent = count;
                    badge.style.display = 'inline';
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

// Export function for manual updates
window.updateUnreadNotesBadge = updateUnreadNotesBadge; 