// Flag to track if user has interacted with the page
let userHasInteracted = false;

// Function to mark user interaction
function markUserInteraction() {
    userHasInteracted = true;
    console.log('✅ User interaction detected - audio can now play');
    console.log('User interaction flag set to:', userHasInteracted);
}

// Function to force enable audio (for testing and when notes are added)
function forceEnableAudio() {
    userHasInteracted = true;
    console.log('🔧 Audio forcefully enabled');
    console.log('User interaction flag set to:', userHasInteracted);
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
    console.log('🎵 Attempting to play notification sound...');
    console.log('User has interacted:', userHasInteracted);
    
    const audio = document.getElementById('notificationSound');
    
    if (!audio) {
        console.error('❌ Audio element not found!');
        return;
    }
    
    console.log('✅ Audio element found:', audio);
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
        console.log('🔄 Audio not loaded yet, trying to load...');
        audio.load();
    }
    
    // Reset audio to beginning
    audio.currentTime = 0;
    
    console.log('🎯 About to play audio...');
    
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
                
                // Only try alternative approach if it's not a user interaction error
                if (error.name !== 'NotAllowedError') {
                    console.log('🔄 Trying alternative approach...');
                    audio.muted = false;
                    audio.volume = 1;
                    audio.play().catch(e => {
                        console.error('❌ Alternative approach also failed:', e);
                    });
                } else {
                    console.log('ℹ️ Skipping alternative approach due to user interaction requirement');
                }
            });
    } else {
        console.log('⚠️ Audio play() returned undefined');
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
                    
                    // Play sound if count increased (new note added) AND user has interacted
                    if (count > previousCount && userHasInteracted) {
                        console.log('📈 Note count increased, playing notification sound...');
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
    console.log('🚀 DOM Content Loaded - Setting up audio system...');
    
    updateUnreadNotesBadge();
    
    // Check browser audio support
    checkBrowserAudioSupport();
    
    // Check audio file on page load
    checkAudioFile();
    
    // Add user interaction listeners
    document.addEventListener('click', function(e) {
        console.log('🖱️ Click detected on:', e.target);
        markUserInteraction();
    });
    document.addEventListener('keydown', function(e) {
        console.log('⌨️ Keydown detected');
        markUserInteraction();
    });
    document.addEventListener('touchstart', function(e) {
        console.log('👆 Touch detected');
        markUserInteraction();
    });
    
    console.log('✅ Event listeners added for user interaction');
    
    // Update badge every 5 seconds (reduced from 30 seconds)
    setInterval(updateUnreadNotesBadge, 5000);
});

// Listen for custom events from other parts of the application
document.addEventListener('noteAdded', function() {
    console.log('📝 Note added event received');
    // Update badge immediately when a new note is added
    setTimeout(() => {
        updateUnreadNotesBadge();
    }, 1000); // Update after 1 second to ensure database is updated
});

document.addEventListener('noteMarkedAsRead', function() {
    console.log('👁️ Note marked as read event received');
    // Update badge immediately when a note is marked as read
    setTimeout(() => {
        updateUnreadNotesBadge();
    }, 500); // Update after 0.5 seconds
});

document.addEventListener('noteDeleted', function() {
    console.log('🗑️ Note deleted event received');
    // Update badge immediately when a note is deleted
    setTimeout(() => {
        updateUnreadNotesBadge();
    }, 500); // Update after 0.5 seconds
});

document.addEventListener('noteUpdated', function() {
    console.log('✏️ Note updated event received');
    // Update badge immediately when a note is updated
    setTimeout(() => {
        updateUnreadNotesBadge();
    }, 500); // Update after 0.5 seconds
});

document.addEventListener('noteAddedWithSound', function() {
    console.log('🔊 Note added with sound event received');
    // Force enable audio and play sound immediately
    forceEnableAudio();
    playNotificationSound();
    
    // Update badge immediately
    setTimeout(() => {
        updateUnreadNotesBadge();
    }, 1000); // Update after 1 second to ensure database is updated
});

// Export functions for manual updates
window.updateUnreadNotesBadge = updateUnreadNotesBadge;
window.playNotificationSound = playNotificationSound;
window.markUserInteraction = markUserInteraction;
window.forceEnableAudio = forceEnableAudio;

// Test function for debugging
window.testNotificationSound = function() {
    console.log('🧪 Testing notification sound...');
    console.log('Current user interaction state:', userHasInteracted);
    
    checkBrowserAudioSupport();
    checkAudioFile();
    
    // Force enable audio for testing
    console.log('🔧 Force enabling audio for test...');
    forceEnableAudio();
    
    // Try to play sound after a short delay
    setTimeout(() => {
        console.log('🎵 Attempting to play sound after force enable...');
        playNotificationSound();
    }, 100);
}; 