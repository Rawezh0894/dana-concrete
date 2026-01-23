// Global Notification System
// This file handles notifications across all pages and in background

// Global variables
let userHasInteracted = false;
let audioContext = null;
let audioBuffer = null;
let audioEnabled = false; // Disabled by default
let notificationInterval = null;
let isPageVisible = true;

// Check if page is visible
function checkPageVisibility() {
    isPageVisible = !document.hidden;
    // console.log('📄 Page visibility changed:', isPageVisible ? 'visible' : 'hidden');
}

// Initialize audio context - Disabled
function initializeAudioContext() {
    /* try {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (AudioContextClass) {
            audioContext = new AudioContextClass();
            // console.log('✅ Global Web Audio API context initialized');
            // loadAudioBuffer(); // Disabled
        }
    } catch (error) {
        // console.warn('⚠️ Could not initialize global Web Audio API:', error);
    } */
}

// Load audio file into buffer - Disabled
function loadAudioBuffer() {
    /* if (!audioContext) return;

    fetch('../assets/sounds/notification.mp3')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.arrayBuffer();
        })
        .then(arrayBuffer => audioContext.decodeAudioData(arrayBuffer))
        .then(buffer => {
            audioBuffer = buffer;
            // console.log('✅ Global audio file loaded into buffer');
        })
        .catch(error => {
            // console.error('❌ Error loading global audio buffer:', error);
            loadAlternativeAudio();
        }); */
}

// Try alternative audio sources - Disabled
function loadAlternativeAudio() {
    /* const alternativePaths = [
        '../assets/sounds/notification.mp3',
        './assets/sounds/notification.mp3',
        '/assets/sounds/notification.mp3',
        'assets/sounds/notification.mp3'
    ];

    for (let i = 1; i < alternativePaths.length; i++) {
        const path = alternativePaths[i];
        fetch(path)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.arrayBuffer();
            })
            .then(arrayBuffer => audioContext.decodeAudioData(arrayBuffer))
            .then(buffer => {
                audioBuffer = buffer;
                // console.log(`✅ Global audio file loaded from alternative path: ${path}`);
                return;
            })
            .catch(error => {
                // console.warn(`⚠️ Failed to load global audio from ${path}:`, error);
            });
    } */
}

// Create beep sound as fallback - Disabled
function createBeepSound() {
    return false;
}

// Play notification sound using Web Audio API - Disabled
function playNotificationSoundWebAudio() {
    return false;
}

// Play notification sound using HTML5 Audio - Disabled
function playNotificationSoundHTML5() {
    return false;
}

// Main function to play notification sound - Disabled
function playGlobalNotificationSound() {
    /* // console.log('🎵 Attempting to play global notification sound...');
    if (!userHasInteracted) return;

    // Ensure audio context is resumed
    if (audioContext && audioContext.state === 'suspended') {
        audioContext.resume().then(() => {
            if (playNotificationSoundWebAudio()) {
                return;
            }
            playNotificationSoundHTML5();
        }).catch(error => {
            playNotificationSoundHTML5();
        });
    } else {
        if (playNotificationSoundWebAudio()) {
            return;
        }
        playNotificationSoundHTML5();
    } */
}

// Force enable audio - Disabled
function forceEnableGlobalAudio() {
    userHasInteracted = true;
    /* if (!audioContext) {
        initializeAudioContext();
    }

    if (audioContext && audioContext.state === 'suspended') {
        audioContext.resume().catch(error => {});
    } */
}

// Mark user interaction - Modified to reduce logs
function markGlobalUserInteraction() {
    userHasInteracted = true;
}

// Check if current page is notes page
function isNotesPage() {
    return window.location.pathname.includes('notes.php') ||
        window.location.pathname.includes('/notes') ||
        document.title.includes('تێبینیەکان');
}

// Update unread notes badge globally
function updateGlobalUnreadNotesBadge() {
    fetch('../process/notes/get_unread_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById('unread-notes-badge');
                if (badge) {
                    const count = data.unread_count;
                    if (count > 0) {
                        badge.textContent = count;
                        badge.style.display = 'inline';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => {
            // console.error('Error fetching global unread notes count:', error);
        });
}

// Initialize global notification system
function initializeGlobalNotifications() {
    // Register service worker for background notifications
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('../assets/js/notification_sw.js')
            .then((registration) => {
                // Service worker registration success
            })
            .catch((error) => {
                // Service worker registration failed
            });
    }

    // Check page visibility
    checkPageVisibility();

    // Add visibility change listener
    document.addEventListener('visibilitychange', checkPageVisibility);

    // Add user interaction listeners
    document.addEventListener('click', function (e) {
        markGlobalUserInteraction();
    });
    document.addEventListener('keydown', function (e) {
        markGlobalUserInteraction();
    });
    document.addEventListener('touchstart', function (e) {
        markGlobalUserInteraction();
    });

    // Update badge initially and every 10 seconds
    updateGlobalUnreadNotesBadge();
    notificationInterval = setInterval(updateGlobalUnreadNotesBadge, 10000);
}

// Listen for custom events
document.addEventListener('noteAdded', function () {
    updateGlobalUnreadNotesBadge();
});

document.addEventListener('noteMarkedAsRead', function () {
    updateGlobalUnreadNotesBadge();
});

document.addEventListener('noteDeleted', function () {
    updateGlobalUnreadNotesBadge();
});

document.addEventListener('noteUpdated', function () {
    updateGlobalUnreadNotesBadge();
});

document.addEventListener('noteAddedWithSound', function () {
    // Sound playback disabled as per user request
    updateGlobalUnreadNotesBadge();
});

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeGlobalNotifications);

// Export functions for global use
window.playGlobalNotificationSound = playGlobalNotificationSound;
window.forceEnableGlobalAudio = forceEnableGlobalAudio;
window.markGlobalUserInteraction = markGlobalUserInteraction;
window.updateGlobalUnreadNotesBadge = updateGlobalUnreadNotesBadge;
window.isNotesPage = isNotesPage;
