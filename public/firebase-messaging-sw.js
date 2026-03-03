// Firebase Messaging Service Worker
// Handles background push notifications when the tab is not active.

importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js');

// Read Firebase config passed as a query param when registering the SW.
// e.g., navigator.serviceWorker.register('/firebase-messaging-sw.js?config=<base64json>')
let firebaseConfig = {};
try {
    const urlParams = new URL(self.location.href).searchParams;
    const configParam = urlParams.get('config');
    if (configParam) {
        firebaseConfig = JSON.parse(atob(configParam));
    }
} catch (e) {
    console.error('[FCM SW] Failed to parse Firebase config:', e);
}

if (firebaseConfig.apiKey) {
    firebase.initializeApp(firebaseConfig);

    const messaging = firebase.messaging();

    messaging.onBackgroundMessage(function (payload) {
        const title = payload.notification?.title || 'Notification';
        const body = payload.notification?.body || '';
        const data = payload.data || {};

        const options = {
            body: body,
            icon: '/favicon.jpg',
            badge: '/favicon.jpg',
            data: data,
            requireInteraction: false,
            tag: data.type || 'default',
        };

        self.registration.showNotification(title, options);
    });

    self.addEventListener('notificationclick', function (event) {
        event.notification.close();

        const url = event.notification.data?.url;
        if (url) {
            event.waitUntil(
                clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
                    for (const client of clientList) {
                        if (client.url.includes(url) && 'focus' in client) {
                            return client.focus();
                        }
                    }
                    if (clients.openWindow) {
                        return clients.openWindow(url);
                    }
                })
            );
        }
    });
}
