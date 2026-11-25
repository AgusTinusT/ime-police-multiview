// Service Worker for IME RP SASP Police Duty PWA
const CACHE_NAME = 'ime-police-pwa-v3';

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  // Allow normal network requests
});

self.addEventListener('push', (event) => {
  let payload = {};
  if (event.data) {
    try {
      payload = event.data.json();
    } catch {
      payload = { body: event.data.text() };
    }
  }

  const title = payload.title || 'Perwira Sedang LIVE Patroli!';
  const options = {
    body: payload.body || 'Siaran langsung patroli kepolisian baru saja dimulai.',
    icon: payload.icon || '/images/icons/icon-192.png',
    badge: payload.badge || '/images/icons/icon-192.png',
    tag: payload.tag || 'officer-live-notification',
    renotify: true,
    vibrate: [200, 100, 200],
    data: {
      url: payload.data?.url || '/dashboard',
    },
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const targetPath = event.notification.data?.url || '/dashboard';
  const targetUrl = new URL(targetPath, self.location.origin).href;

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
      for (const client of windowClients) {
        if (client.url === targetUrl && 'focus' in client) {
          return client.focus();
        }
      }
      if (self.clients.openWindow) {
        return self.clients.openWindow(targetUrl);
      }
    })
  );
});
