// Global Notification System
// This file handles notifications across all pages and in background

// Global variables
let userHasInteracted = false;
let audioContext = null;
let audioBuffer = null;
let audioEnabled = true;
let notificationInterval = null;
let isPageVisible = true;

// Check if page is visible
function checkPageVisibility() {
    isPageVisible = !document.hidden;
    console.log('📄 Page visibility changed:', isPageVisible ? 'visible' : 'hidden');
}

// Initialize audio context
function initializeAudioContext() {
    try {
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (AudioContextClass) {
            audioContext = new AudioContextClass();
            console.log('✅ Global Web Audio API context initialized');
            loadAudioBuffer();
        }
    } catch (error) {
        console.warn('⚠️ Could not initialize global Web Audio API:', error);
    }
}

// Load audio file into buffer
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
            console.log('✅ Global audio file loaded into buffer');
        })
        .catch(error => {
            console.error('❌ Error loading global audio buffer:', error);
            loadAlternativeAudio();
        });
}

// Try alternative audio sources
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
                console.log(`✅ Global audio file loaded from alternative path: ${path}`);
                return;
            })
            .catch(error => {
                console.warn(`⚠️ Failed to load global audio from ${path}:`, error);
            });
    }
}

// Create beep sound as fallback
function createBeepSound() {
    if (!audioContext) return false;
    
    try {
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.5);
        
        console.log('🔊 Global beep sound played as fallback');
        return true;
    } catch (error) {
        console.error('❌ Error creating global beep sound:', error);
        return false;
    }
}

// Play notification sound using Web Audio API
function playNotificationSoundWebAudio() {
    if (!audioContext) {
        console.log('⚠️ Global Web Audio API not available, falling back to HTML5 Audio');
        return false;
    }
    
    try {
        if (audioContext.state === 'suspended') {
            audioContext.resume();
        }
        
        if (audioBuffer) {
            const source = audioContext.createBufferSource();
            source.buffer = audioBuffer;
            source.connect(audioContext.destination);
            source.start(0);
            
            console.log('✅ Global notification sound played using Web Audio API');
            return true;
        } else {
            console.log('🔄 Global audio buffer not loaded, using beep sound fallback');
            return createBeepSound();
        }
    } catch (error) {
        console.error('❌ Error playing global sound with Web Audio API:', error);
        return false;
    }
}

// Play notification sound using HTML5 Audio
function playNotificationSoundHTML5() {
    const audio = document.getElementById('notificationSound');
    
    if (!audio) {
        console.error('❌ Global audio element not found!');
        return false;
    }
    
    audio.currentTime = 0;
    
    const playPromise = audio.play();
    
    if (playPromise !== undefined) {
        playPromise
            .then(() => {
                console.log('✅ Global notification sound played using HTML5 Audio');
                return true;
            })
            .catch(error => {
                console.error('❌ Could not play global notification sound with HTML5 Audio:', error);
                return false;
            });
    }
    
    return false;
}

// Main function to play notification sound
function playGlobalNotificationSound() {
    console.log('🎵 Attempting to play global notification sound...');
    console.log('User has interacted:', userHasInteracted);
    console.log('Page visible:', isPageVisible);
    
    if (!userHasInteracted) {
        console.log('⚠️ User has not interacted with the page yet. Audio will not play.');
        return;
    }
    
    // Ensure audio context is resumed
    if (audioContext && audioContext.state === 'suspended') {
        audioContext.resume().then(() => {
            console.log('✅ Global audio context resumed, now playing sound...');
            if (playNotificationSoundWebAudio()) {
                return;
            }
            playNotificationSoundHTML5();
        }).catch(error => {
            console.warn('⚠️ Could not resume global audio context:', error);
            playNotificationSoundHTML5();
        });
    } else {
        if (playNotificationSoundWebAudio()) {
            return;
        }
        playNotificationSoundHTML5();
    }
}

// Force enable audio
function forceEnableGlobalAudio() {
    userHasInteracted = true;
    console.log('🔧 Global audio forcefully enabled');
    
    if (!audioContext) {
        initializeAudioContext();
    }
    
    if (audioContext && audioContext.state === 'suspended') {
        audioContext.resume().then(() => {
            console.log('✅ Global audio context resumed successfully');
        }).catch(error => {
            console.warn('⚠️ Could not resume global audio context:', error);
        });
    }
    
    // Try to play a silent sound to unlock audio
    try {
        if (audioContext && audioContext.state === 'running') {
            const silentOscillator = audioContext.createOscillator();
            const silentGain = audioContext.createGain();
            silentGain.gain.setValueAtTime(0, audioContext.currentTime);
            silentOscillator.connect(silentGain);
            silentGain.connect(audioContext.destination);
            silentOscillator.start(audioContext.currentTime);
            silentOscillator.stop(audioContext.currentTime + 0.001);
            console.log('🔇 Global silent sound played to unlock audio');
        }
    } catch (error) {
        console.warn('⚠️ Could not play global silent sound:', error);
    }
}

// Mark user interaction
function markGlobalUserInteraction() {
    userHasInteracted = true;
    console.log('✅ Global user interaction detected - audio can now play');
    
    if (!audioContext) {
        initializeAudioContext();
    }
    
    if (audioContext && audioContext.state === 'suspended') {
        audioContext.resume().then(() => {
            console.log('✅ Global audio context resumed on user interaction');
        }).catch(error => {
            console.warn('⚠️ Could not resume global audio context:', error);
        });
    }
    
    // Try to play a silent sound to unlock audio
    try {
        if (audioContext && audioContext.state === 'running') {
            const silentOscillator = audioContext.createOscillator();
            const silentGain = audioContext.createGain();
            silentGain.gain.setValueAtTime(0, audioContext.currentTime);
            silentOscillator.connect(silentGain);
            silentGain.connect(audioContext.destination);
            silentOscillator.start(audioContext.currentTime);
            silentOscillator.stop(audioContext.currentTime + 0.001);
            console.log('🔇 Global silent sound played to unlock audio on user interaction');
        }
    } catch (error) {
        console.warn('⚠️ Could not play global silent sound on user interaction:', error);
    }
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
                    const previousCount = parseInt(badge.textContent) || 0;
                    
                    if (count > 0) {
                        badge.textContent = count;
                        badge.style.display = 'inline';
                        
                        // Play sound if count increased (new note added)
                        if (count > previousCount) {
                            console.log('📈 Global note count increased, playing notification sound...');
                            forceEnableGlobalAudio();
                            
                            // Try to play sound with multiple attempts
                            setTimeout(() => {
                                playGlobalNotificationSound();
                            }, 50);
                            setTimeout(() => {
                                playGlobalNotificationSound();
                            }, 200);
                            setTimeout(() => {
                                playGlobalNotificationSound();
                            }, 500);
                        }
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error fetching global unread notes count:', error);
        });
}

// Initialize global notification system
function initializeGlobalNotifications() {
    console.log('🚀 Initializing global notification system...');
    
    // Register service worker for background notifications
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('../assets/js/notification_sw.js')
            .then((registration) => {
                console.log('✅ Service Worker registered successfully:', registration);
                
                // Request notification permission
                if ('Notification' in window) {
                    Notification.requestPermission().then((permission) => {
                        console.log('🔔 Notification permission:', permission);
                    });
                }
            })
            .catch((error) => {
                console.error('❌ Service Worker registration failed:', error);
            });
    }
    
    // Check page visibility
    checkPageVisibility();
    
    // Add visibility change listener
    document.addEventListener('visibilitychange', checkPageVisibility);
    
    // Add user interaction listeners
    document.addEventListener('click', function(e) {
        console.log('🖱️ Global click detected on:', e.target);
        markGlobalUserInteraction();
    });
    document.addEventListener('keydown', function(e) {
        console.log('⌨️ Global keydown detected');
        markGlobalUserInteraction();
    });
    document.addEventListener('touchstart', function(e) {
        console.log('👆 Global touch detected');
        markGlobalUserInteraction();
    });
    
    // Automatically enable audio on page load
    forceEnableGlobalAudio();
    
    // Try to unlock audio immediately
    setTimeout(() => {
        forceEnableGlobalAudio();
        if (audioContext && audioContext.state === 'suspended') {
            audioContext.resume();
        }
    }, 100);
    
    setTimeout(() => {
        forceEnableGlobalAudio();
        if (audioContext && audioContext.state === 'suspended') {
            audioContext.resume();
        }
    }, 1000);
    
    // Update badge every 10 seconds
    updateGlobalUnreadNotesBadge();
    notificationInterval = setInterval(updateGlobalUnreadNotesBadge, 10000);
    
    console.log('✅ Global notification system initialized');
}

// Listen for custom events
document.addEventListener('noteAdded', function() {
    console.log('📝 Global note added event received');
    updateGlobalUnreadNotesBadge();
});

document.addEventListener('noteMarkedAsRead', function() {
    console.log('👁️ Global note marked as read event received');
    updateGlobalUnreadNotesBadge();
});

document.addEventListener('noteDeleted', function() {
    console.log('🗑️ Global note deleted event received');
    updateGlobalUnreadNotesBadge();
});

document.addEventListener('noteUpdated', function() {
    console.log('✏️ Global note updated event received');
    updateGlobalUnreadNotesBadge();
});

document.addEventListener('noteAddedWithSound', function() {
    console.log('🔊 Global note added with sound event received');
    forceEnableGlobalAudio();
    
    // Try to play sound with multiple attempts
    setTimeout(() => {
        playGlobalNotificationSound();
    }, 50);
    setTimeout(() => {
        playGlobalNotificationSound();
    }, 200);
    setTimeout(() => {
        playGlobalNotificationSound();
    }, 500);
});

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeGlobalNotifications);

// Export functions for global use
window.playGlobalNotificationSound = playGlobalNotificationSound;
window.forceEnableGlobalAudio = forceEnableGlobalAudio;
window.markGlobalUserInteraction = markGlobalUserInteraction;
window.updateGlobalUnreadNotesBadge = updateGlobalUnreadNotesBadge; 