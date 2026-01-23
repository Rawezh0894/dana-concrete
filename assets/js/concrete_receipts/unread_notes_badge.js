// Flag to track if user has interacted with the page
let userHasInteracted = false;
let audioContext = null;
let audioBuffer = null;
let audioEnabled = false; // Disabled as per user request

// Function to mark user interaction
function markUserInteraction() {
    userHasInteracted = true;
}

// Function to initialize Web Audio API context - Disabled
function initializeAudioContext() {
}

// Function to load audio file into buffer - Disabled
function loadAudioBuffer() {
}

// Function to try alternative audio sources - Disabled
function loadAlternativeAudio() {
}

// Function to force enable audio - Disabled
function forceEnableAudio() {
    userHasInteracted = true;
}

// Function to check browser audio support - Disabled
function checkBrowserAudioSupport() {
}

// Function to check if audio file exists - Disabled
function checkAudioFile() {
}

// Function to create a simple beep sound as fallback - Disabled
function createBeepSound() {
    return false;
}

// Function to play notification sound using Web Audio API - Disabled
function playNotificationSoundWebAudio() {
    return false;
}

// Function to play notification sound using HTML5 Audio - Disabled
function playNotificationSoundHTML5() {
    return false;
}

// Function to play notification sound with fallback - Disabled
function playNotificationSound() {
}

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
            // Silence error
        });
}

// Update badge on page load
document.addEventListener('DOMContentLoaded', function () {
    updateUnreadNotesBadge();

    // Add user interaction listeners
    document.addEventListener('click', function (e) {
        markUserInteraction();
    });
    document.addEventListener('keydown', function (e) {
        markUserInteraction();
    });
    document.addEventListener('touchstart', function (e) {
        markUserInteraction();
    });

    // Update badge every 10 seconds
    setInterval(updateUnreadNotesBadge, 10000);
});

// Listen for custom events from other parts of the application
document.addEventListener('noteAdded', function () {
    updateUnreadNotesBadge();
});

document.addEventListener('noteMarkedAsRead', function () {
    updateUnreadNotesBadge();
});

document.addEventListener('noteDeleted', function () {
    updateUnreadNotesBadge();
});

document.addEventListener('noteUpdated', function () {
    updateUnreadNotesBadge();
});

document.addEventListener('noteAddedWithSound', function () {
    updateUnreadNotesBadge();
});

// Export functions for manual updates
window.updateUnreadNotesBadge = updateUnreadNotesBadge;
window.playNotificationSound = playNotificationSound;
window.markUserInteraction = markUserInteraction;
window.forceEnableAudio = forceEnableAudio;