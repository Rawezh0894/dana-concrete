// Flag to track if user has interacted with the page
let userHasInteracted = false;

// Function to mark user interaction
function markUserInteraction() {
    userHasInteracted = true;
    console.log('User interaction detected - audio can now play');
}

// Function to check browser audio capabilities
function checkBrowserAudioSupport() {
    console.log('Checking browser audio support...');
    
    // Check if HTML5 Audio is supported
    if (typeof Audio !== 'undefined') {
        console.log('✅ HTML5 Audio is supported');
    } else {
        console.error('❌ HTML5 Audio is not supported');
    }
    
    // Check if Web Audio API is supported
    if (typeof AudioContext !== 'undefined' || typeof webkitAudioContext !== 'undefined') {
        console.log('✅ Web Audio API is supported');
    } else {
        console.log('⚠️ Web Audio API is not supported');
    }
    
    // Check user interaction requirement
    console.log('ℹ️ Note: Audio playback may require user interaction in some browsers');
}

// Function to check if audio file exists
function checkAudioFile() {
    const audio = document.getElementById('notificationSound');
    if (!audio) {
        console.error('Audio element not found!');
        return;
    }
    
    console.log('Checking audio file...');
    console.log('Audio src:', audio.src);
    
    // Try to fetch the audio file
    fetch(audio.src)
        .then(response => {
            if (response.ok) {
                console.log('✅ Audio file exists and is accessible');
                console.log('File size:', response.headers.get('content-length'), 'bytes');
            } else {
                console.error('❌ Audio file not found or not accessible');
                console.log('Response status:', response.status);
            }
        })
        .catch(error => {
            console.error('❌ Error checking audio file:', error);
        });
}

// Function to play notification sound
function playNotificationSound() {
    console.log('Attempting to play notification sound...');
    console.log('User has interacted:', userHasInteracted);
    
    const audio = document.getElementById('notificationSound');
    
    if (!audio) {
        console.error('Audio element not found!');
        return;
    }
    
    console.log('Audio element found:', audio);
    console.log('Audio readyState:', audio.readyState);
    console.log('Audio src:', audio.src);
    
    // Check if user has interacted with the page
    if (!userHasInteracted) {
        console.log('⚠️ User has not interacted with the page yet. Audio will not play.');
        console.log('ℹ️ Try clicking somewhere on the page first, then test again.');
        return;
    }
    
    // Check if audio is loaded
    if (audio.readyState < 2) {
        console.log('Audio not loaded yet, trying to load...');
        audio.load();
    }
    
    // Reset audio to beginning
    audio.currentTime = 0;
    
    // Play the sound
    const playPromise = audio.play();
    
    if (playPromise !== undefined) {
        playPromise
            .then(() => {
                console.log('✅ Notification sound played successfully!');
            })
            .catch(error => {
                console.error('❌ Could not play notification sound:', error);
                console.log('Error name:', error.name);
                console.log('Error message:', error.message);
                
                // Try alternative approach
                console.log('🔄 Trying alternative approach...');
                audio.muted = false;
                audio.volume = 1;
                audio.play().catch(e => {
                    console.error('❌ Alternative approach also failed:', e);
                });
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
    
    // Check browser audio support
    checkBrowserAudioSupport();
    
    // Check audio file on page load
    checkAudioFile();
    
    // Add user interaction listeners
    document.addEventListener('click', markUserInteraction);
    document.addEventListener('keydown', markUserInteraction);
    document.addEventListener('touchstart', markUserInteraction);
    
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
window.markUserInteraction = markUserInteraction;

// Test function for debugging
window.testNotificationSound = function() {
    console.log('Testing notification sound...');
    checkBrowserAudioSupport();
    checkAudioFile();
    
    // Mark user interaction when test button is clicked
    markUserInteraction();
    
    // Try to play sound
    setTimeout(() => {
        playNotificationSound();
    }, 100);
}; 