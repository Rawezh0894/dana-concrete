// Service Worker for Background Notifications
const CACHE_NAME = 'dana-concrete-notifications-v2'; // Increment version to clear old caches

// Install event
self.addEventListener('install', (event) => {
    // console.log('🔧 Service Worker installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                // console.log('✅ Cache opened');
                return cache.addAll([
                    // Assets to cache if needed
                ]);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event
self.addEventListener('activate', (event) => {
    // console.log('🚀 Service Worker activating...');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        // console.log('🗑️ Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event
self.addEventListener('fetch', (event) => {
    // Simplified fetch handler
});

// Background sync for notifications
self.addEventListener('sync', (event) => {
    if (event.tag === 'background-notification-sync') {
        event.waitUntil(checkForNewNotes());
    }
});

// Check for new notes in background
async function checkForNewNotes() {
    try {
        const response = await fetch('../process/notes/get_unread_count.php');
        const data = await response.json();

        if (data.success && data.unread_count > 0) {
            // Check if user is currently on notes page
            const clients = await self.clients.matchAll();
            let isOnNotesPage = false;

            for (const client of clients) {
                if (client.url.includes('notes.php') ||
                    client.url.includes('/notes') ||
                    client.title.includes('تێبینیەکان')) {
                    isOnNotesPage = true;
                    break;
                }
            }

            // Only show notification if not on notes page
            if (!isOnNotesPage) {
                self.registration.showNotification('تێبینی نوێ', {
                    body: `تێبینی نوێ هەیە (${data.unread_count})`,
                    icon: '../assets/images/logo.png',
                    badge: '../assets/images/logo.png',
                    tag: 'new-note-notification',
                    requireInteraction: true,
                    actions: [
                        {
                            action: 'view',
                            title: 'بینین'
                        },
                        {
                            action: 'dismiss',
                            title: 'داخستن'
                        }
                    ]
                });
            }
        }
    } catch (error) {
        // Silent fail for background check
    }
}

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'view') {
        event.waitUntil(
            clients.openWindow('../pages/notes.php')
        );
    }
});

// Handle push notifications
self.addEventListener('push', (event) => {
    const options = {
        body: 'تێبینی نوێ هەیە',
        icon: '../assets/images/logo.png',
        badge: '../assets/images/logo.png',
        tag: 'push-notification',
        requireInteraction: true,
        actions: [
            {
                action: 'view',
                title: 'بینین'
            },
            {
                action: 'dismiss',
                title: 'داخستن'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('دانا کۆنکرێت', options)
    );
});

// Periodic background sync (if supported)
self.addEventListener('periodicsync', (event) => {
    if (event.tag === 'check-notes') {
        event.waitUntil(checkForNewNotes());
    }
}); 