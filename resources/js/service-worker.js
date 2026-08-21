import { cleanupOutdatedCaches, precacheAndRoute } from 'workbox-precaching';

self.skipWaiting();
cleanupOutdatedCaches();
precacheAndRoute(self.__WB_MANIFEST);

// Handle incoming Web Push notifications
self.addEventListener('push', (event) => {
    let payload = {
        title: 'FF Spotless',
        body: 'Anda mempunyai notifikasi baharu.',
        icon: '/icons/ff-spotless-icon.svg',
        badge: '/icons/ff-spotless-icon.svg',
        url: '/',
        data: {},
    };

    if (event.data) {
        try {
            const data = event.data.json();
            payload = {
                ...payload,
                ...data,
            };
        } catch (e) {
            payload.body = event.data.text();
        }
    }

    const targetUrl = payload.url || (payload.data && payload.data.url) || '/';

    const options = {
        body: payload.body,
        icon: payload.icon || '/icons/ff-spotless-icon.svg',
        badge: payload.badge || '/icons/ff-spotless-icon.svg',
        tag: payload.tag || 'ffspotless-notification',
        vibrate: payload.vibrate || [200, 100, 200],
        data: {
            url: targetUrl,
            ...payload.data,
        },
        renotify: true,
        requireInteraction: false,
    };

    event.waitUntil(
        self.registration.showNotification(payload.title, options)
    );
});

// Handle notification click to navigate to the specific checklist date or admin view
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                if ('focus' in client) {
                    if ('navigate' in client) {
                        return client.navigate(targetUrl).then(() => client.focus());
                    }
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
