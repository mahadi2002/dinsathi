// দিনসাথী service worker — Web Push only. Core CRUD works without this file;
// it exists purely so a subscribed user can receive a reminder while the
// app tab isn't open. See docs/FEATURES.md for why the push payload itself
// is not yet signed/encrypted in production (PUSH_GATEWAY=mock by default).
self.addEventListener('install', function (event) {
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', function (event) {
  var data = { title: 'দিনসাথী', body: 'আপনার একটি রিমাইন্ডার আছে।', url: '/app' };
  try {
    if (event.data) { data = Object.assign(data, event.data.json()); }
  } catch (e) { /* non-JSON payload, fall back to defaults */ }

  event.waitUntil(
    self.registration.showNotification(data.title, {
      body: data.body,
      icon: '/assets/img/icon.svg',
      badge: '/assets/img/icon.svg',
      data: { url: data.url || '/app' },
    })
  );
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var url = (event.notification.data && event.notification.data.url) || '/app';
  event.waitUntil(
    clients.matchAll({ type: 'window' }).then(function (clientList) {
      for (var i = 0; i < clientList.length; i++) {
        if (clientList[i].url.indexOf(url) !== -1 && 'focus' in clientList[i]) {
          return clientList[i].focus();
        }
      }
      if (clients.openWindow) { return clients.openWindow(url); }
    })
  );
});
