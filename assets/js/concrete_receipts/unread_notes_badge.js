// Flag to track if user has interacted with the page
let userHasInteracted = false;
let audioContext = null;
let audioBuffer = null;
let audioEnabled = true; // Always enabled by default





// Function to mark user interaction
function markUserInteraction() {
    userHasInteracted = true;
    console.log('✅ User interaction detected - audio can now play');
    console.log('User interaction flag set to:', userHasInteracted);
    
    // Initialize audio context on first interaction
    if (!audioContext) {
        initializeAudioContext();
    }
    
    // Try to resume audio context immediately
    if (audioContext && audioContext.state === 'suspended') {
        audioContext.resume().then(() => {
            console.log('✅ Audio context resumed on user interaction');
        }).catch(error => {
            console.warn('⚠️ Could not resume audio context:', error);
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
            console.log('🔇 Silent sound played to unlock audio on user interaction');
        }
    } catch (error) {
        console.warn('⚠️ Could not play silent sound on user interaction:', error);
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
    
    // Try to resume audio context immediately
    if (audioContext && audioContext.state === 'suspended') {
        audioContext.resume().then(() => {
            console.log('✅ Audio context resumed successfully');
        }).catch(error => {
            console.warn('⚠️ Could not resume audio context:', error);
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
            console.log('🔇 Silent sound played to unlock audio');
        }
    } catch (error) {
        console.warn('⚠️ Could not play silent sound:', error);
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
    
    // Check if user has interacted with the page
    if (!userHasInteracted) {
        console.log('⚠️ User has not interacted with the page yet. Audio will not play.');
        console.log('ℹ️ Try clicking somewhere on the page first, then test again.');
        return;
    }
    
    // Ensure audio context is resumed
    if (audioContext && audioContext.state === 'suspended') {
        audioContext.resume().then(() => {
            console.log('✅ Audio context resumed, now playing sound...');
            // Try Web Audio API first (more reliable)
            if (playNotificationSoundWebAudio()) {
                return;
            }
            // Fallback to HTML5 Audio
            playNotificationSoundHTML5();
        }).catch(error => {
            console.warn('⚠️ Could not resume audio context:', error);
            // Try HTML5 Audio as fallback
            playNotificationSoundHTML5();
        });
    } else {
        // Try Web Audio API first (more reliable)
        if (playNotificationSoundWebAudio()) {
            return;
        }
        // Fallback to HTML5 Audio
        playNotificationSoundHTML5();
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
                        console.log('📈 Note count increased, playing notification sound...');
                        // Force enable audio and play sound immediately
                        forceEnableAudio();
                        // Try to play sound with multiple attempts
                        setTimeout(() => {
                            playNotificationSound();
                        }, 50);
                        setTimeout(() => {
                            playNotificationSound();
                        }, 200);
                        setTimeout(() => {
                            playNotificationSound();
                        }, 500);
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
    
    // Automatically enable audio on page load
    forceEnableAudio();
    
    // Try to unlock audio immediately
    setTimeout(() => {
        forceEnableAudio();
        if (audioContext && audioContext.state === 'suspended') {
            audioContext.resume();
        }
    }, 100);
    
    setTimeout(() => {
        forceEnableAudio();
        if (audioContext && audioContext.state === 'suspended') {
            audioContext.resume();
        }
    }, 1000);
    
    // Add user interaction listeners with immediate audio enable
    document.addEventListener('click', function(e) {
        console.log('🖱️ Click detected on:', e.target);
        markUserInteraction();
        // Try to play a silent sound to unlock audio
        if (audioContext && audioContext.state === 'suspended') {
            audioContext.resume();
        }
    });
    document.addEventListener('keydown', function(e) {
        console.log('⌨️ Keydown detected');
        markUserInteraction();
        // Try to play a silent sound to unlock audio
        if (audioContext && audioContext.state === 'suspended') {
            audioContext.resume();
        }
    });
    document.addEventListener('touchstart', function(e) {
        console.log('👆 Touch detected');
        markUserInteraction();
        // Try to play a silent sound to unlock audio
        if (audioContext && audioContext.state === 'suspended') {
            audioContext.resume();
        }
    });
    
    console.log('✅ Event listeners added for user interaction');
    
    // Update badge every 10 seconds (reduced from 30 seconds)
    setInterval(updateUnreadNotesBadge, 10000);
});

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
    // Force enable audio and play sound immediately
    forceEnableAudio();
    // Try to play sound with multiple attempts
    setTimeout(() => {
        playNotificationSound();
    }, 50);
    setTimeout(() => {
        playNotificationSound();
    }, 200);
    setTimeout(() => {
        playNotificationSound();
    }, 500);
});

// Export functions for manual updates
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
    
    // Try to play sound with multiple attempts
    console.log('🎵 Attempting to play sound...');
    playNotificationSound();
    setTimeout(() => {
        playNotificationSound();
    }, 100);
    setTimeout(() => {
        playNotificationSound();
    }, 300);
}; 