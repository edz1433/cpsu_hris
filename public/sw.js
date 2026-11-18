// Service worker for the installable HRIS app.
// Network only: HR pages are never cached. The one cached file is the offline page,
// shown when the server can't be reached (e.g. Apache stopped or no connection).
const CACHE = 'hris-offline-v1';
const OFFLINE_URL = new URL('offline.html', self.registration.scope).href;

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.add(OFFLINE_URL)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate') return;

    event.respondWith(
        fetch(event.request).catch(() => caches.match(OFFLINE_URL))
    );
});

// Web push: payload is {title, body, url, tag} from App\Services\WebPush\NotificationPush.
self.addEventListener('push', (event) => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { body: event.data ? event.data.text() : '' };
    }

    const icon = new URL('icons/icon-192.png', self.registration.scope).href;

    event.waitUntil(
        self.registration.showNotification(data.title || 'CPSU HRIS', {
            body: data.body || '',
            icon: icon,
            badge: icon,
            tag: data.tag || undefined,
            renotify: !!data.tag,
            data: { url: data.url || self.registration.scope },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    // Only ever open pages of this app.
    let target = new URL(event.notification.data && event.notification.data.url || self.registration.scope, self.registration.scope);
    if (target.origin !== self.location.origin) target = new URL(self.registration.scope);

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
            const open = windows.find((w) => w.url.startsWith(self.registration.scope));
            if (open) {
                return open.focus().then((w) => (w || open).navigate(target.href)).catch(() => self.clients.openWindow(target.href));
            }
            return self.clients.openWindow(target.href);
        })
    );
});
