// Flag to track if user has interacted with the page
let userHasInteracted = false;
let audioContext = null;
let audioBuffer = null;
let audioReadyNotificationShown = false;
let audioEnabled = localStorage.getItem('audioNotificationsEnabled') !== 'false'; // Default to true

// Function to toggle audio notifications
function toggleAudioNotifications() {
    audioEnabled = !audioEnabled;
    localStorage.setItem('audioNotificationsEnabled', audioEnabled);
    
    const status = audioEnabled ? 'enabled' : 'disabled';
    console.log(`🔊 Audio notifications ${status}`);
    
    // Update button status
    updateAudioButtonStatus();
    
    // Show feedback to user
    const message = audioEnabled ? 'زەنگەکان چالاک کراون' : 'زەنگەکان ناچالاک کراون';
    showStatusMessage(message, audioEnabled ? 'success' : 'warning');
}

// Function to show status message
function showStatusMessage(message, type = 'info') {
    const colors = {
        success: '#28a745',
        warning: '#ffc107',
        error: '#dc3545',
        info: '#17a2b8'
    };
    
    const notification = document.createElement('div');
    notification.innerHTML = `
        <div style="
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${colors[type]};
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 14px;
            z-index: 9999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease-out;
        ">
            <i class="fas fa-${type === 'success' ? 'check' : type === 'warning' ? 'exclamation-triangle' : 'info'}-circle me-2"></i>
            ${message}
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }, 3000);
}

// Function to show audio ready notification
function showAudioReadyNotification() {
    if (audioReadyNotificationShown) return;
    
    // Create a subtle notification
    const notification = document.createElement('div');
    notification.id = 'audio-ready-notification';
    notification.innerHTML = `
        <div style="
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 14px;
            z-index: 9999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease-out;
        ">
            <i class="fas fa-volume-up me-2"></i>
            زەنگەکە ئێستا دەکرێت بەکاربهێنرێت
        </div>
    `;
    
    // Add CSS animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }, 3000);
    
    audioReadyNotificationShown = true;
}

// Function to mark user interaction
function markUserInteraction() {
    userHasInteracted = true;
    console.log('✅ User interaction detected - audio can now play');
    console.log('User interaction flag set to:', userHasInteracted);
    
    // Initialize audio context on first interaction
    if (!audioContext) {
        initializeAudioContext();
    }
    
    // Show notification that audio is ready
    if (!audioReadyNotificationShown) {
        showAudioReadyNotification();
    }
}

// Function to initialize Web Audio API context
function initializeAudioContext() {
    try {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (AudioContextClass) {
            audioContext = new AudioContextClass();
            console.log('✅ Web Audio API context initialized');
            
            // Load audio file into buffer
            loadAudioBuffer();
        }
    } catch (error) {
        console.warn('⚠️ Could not initialize Web Audio API:', error);
    }
}

// Function to load audio file into buffer
function loadAudioBuffer() {
    if (!audioContext) return;
    
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
            console.log('✅ Audio file loaded into buffer');
        })
        .catch(error => {
            console.error('❌ Error loading audio buffer:', error);
            // Try alternative audio file path
            console.log('🔄 Trying alternative audio path...');
            loadAlternativeAudio();
        });
}

// Function to try alternative audio sources
function loadAlternativeAudio() {
    const alternativePaths = [
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
                console.log(`✅ Audio file loaded from alternative path: ${path}`);
                return;
            })
            .catch(error => {
                console.warn(`⚠️ Failed to load audio from ${path}:`, error);
                if (i === alternativePaths.length - 1) {
                    console.error('❌ All audio file paths failed. Audio notifications will be disabled.');
                }
            });
    }
}

// Function to force enable audio (for testing and when notes are added)
function forceEnableAudio() {
    userHasInteracted = true;
    console.log('🔧 Audio forcefully enabled');
    console.log('User interaction flag set to:', userHasInteracted);
    
    // Initialize audio context if not already done
    if (!audioContext) {
        initializeAudioContext();
    }
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

// Function to create a simple beep sound as fallback
function createBeepSound() {
    if (!audioContext) return false;
    
    try {
        // Create oscillator for beep sound
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        // Configure oscillator
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime); // 800 Hz
        oscillator.type = 'sine';
        
        // Configure gain (volume)
        gainNode.gain.setValueAtTime(0.1, audioContext.currentTime); // Low volume
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
        
        // Connect nodes
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        // Play beep
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.5);
        
        console.log('🔊 Beep sound played as fallback');
        return true;
    } catch (error) {
        console.error('❌ Error creating beep sound:', error);
        return false;
    }
}

// Function to play notification sound using Web Audio API
function playNotificationSoundWebAudio() {
    if (!audioContext) {
        console.log('⚠️ Web Audio API not available, falling back to HTML5 Audio');
        return false;
    }
    
    try {
        // Resume audio context if suspended
        if (audioContext.state === 'suspended') {
            audioContext.resume();
        }
        
        // Try to play the loaded audio buffer first
        if (audioBuffer) {
            const source = audioContext.createBufferSource();
            source.buffer = audioBuffer;
            source.connect(audioContext.destination);
            source.start(0);
            
            console.log('✅ Notification sound played using Web Audio API');
            return true;
        } else {
            // Fallback to beep sound
            console.log('🔄 Audio buffer not loaded, using beep sound fallback');
            return createBeepSound();
        }
    } catch (error) {
        console.error('❌ Error playing sound with Web Audio API:', error);
        return false;
    }
}

// Function to play notification sound using HTML5 Audio
function playNotificationSoundHTML5() {
    const audio = document.getElementById('notificationSound');
    
    if (!audio) {
        console.error('❌ Audio element not found!');
        return false;
    }
    
    // Reset audio to beginning
    audio.currentTime = 0;
    
    // Play the sound
    const playPromise = audio.play();
    
    if (playPromise !== undefined) {
        playPromise
            .then(() => {
                console.log('✅ Notification sound played using HTML5 Audio');
                return true;
            })
            .catch(error => {
                console.error('❌ Could not play notification sound with HTML5 Audio:', error);
                return false;
            });
    }
    
    return false;
}

// Function to play notification sound with fallback
function playNotificationSound() {
    console.log('🎵 Attempting to play notification sound...');
    console.log('User has interacted:', userHasInteracted);
    console.log('Audio enabled:', audioEnabled);
    
    // Check if audio is enabled
    if (!audioEnabled) {
        console.log('🔇 Audio notifications are disabled');
        return;
    }
    
    // Check if user has interacted with the page
    if (!userHasInteracted) {
        console.log('⚠️ User has not interacted with the page yet. Audio will not play.');
        console.log('ℹ️ Try clicking somewhere on the page first, then test again.');
        return;
    }
    
    // Try Web Audio API first (more reliable)
    if (playNotificationSoundWebAudio()) {
        return;
    }
    
    // Fallback to HTML5 Audio
    playNotificationSoundHTML5();
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
                        console.log('📈 Note count increased, playing notification sound...');
                        forceEnableAudio();
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
    
    // Update audio button status
    updateAudioButtonStatus();
    
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
    
    // Update badge every 10 seconds (reduced from 30 seconds)
    setInterval(updateUnreadNotesBadge, 10000);
});

// Function to update audio button status
function updateAudioButtonStatus() {
    const audioButtons = document.querySelectorAll('button[onclick*="toggleAudioNotifications"], button[onclick*="testNotificationSound"]');
    audioButtons.forEach(button => {
        if (button.onclick.toString().includes('toggleAudioNotifications')) {
            const icon = button.querySelector('i');
            const text = button.textContent;
            
            if (audioEnabled) {
                icon.className = 'fas fa-bell me-1';
                button.textContent = text.replace(/زەنگەکان.*/, 'زەنگەکان چالاکن');
                button.className = button.className.replace('btn-secondary', 'btn-info');
            } else {
                icon.className = 'fas fa-bell-slash me-1';
                button.textContent = text.replace(/زەنگەکان.*/, 'زەنگەکان ناچالاکن');
                button.className = button.className.replace('btn-info', 'btn-secondary');
            }
        }
    });
}

// Listen for custom events from other parts of the application
document.addEventListener('noteAdded', function() {
    console.log('📝 Note added event received');
    // Update badge when a new note is added
    updateUnreadNotesBadge();
});

document.addEventListener('noteMarkedAsRead', function() {
    console.log('👁️ Note marked as read event received');
    // Update badge when a note is marked as read
    updateUnreadNotesBadge();
});

document.addEventListener('noteDeleted', function() {
    console.log('🗑️ Note deleted event received');
    // Update badge when a note is deleted
    updateUnreadNotesBadge();
});

document.addEventListener('noteUpdated', function() {
    console.log('✏️ Note updated event received');
    // Update badge when a note is updated
    updateUnreadNotesBadge();
});

document.addEventListener('noteAddedWithSound', function() {
    console.log('🔊 Note added with sound event received');
    // Force enable audio and play sound
    forceEnableAudio();
    playNotificationSound();
});

// Export functions for manual updates
// Export functions for manual updates
window.updateUnreadNotesBadge = updateUnreadNotesBadge;
window.playNotificationSound = playNotificationSound;
window.markUserInteraction = markUserInteraction;
window.forceEnableAudio = forceEnableAudio;
window.toggleAudioNotifications = toggleAudioNotifications;
window.showStatusMessage = showStatusMessage;
window.updateAudioButtonStatus = updateAudioButtonStatus;

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