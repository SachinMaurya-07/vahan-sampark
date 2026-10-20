self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));

self.addEventListener('push', event => {
  let data = {};
  try { data = event.data ? event.data.json() : {}; } catch (_) {}
  const title = data.title || 'Incoming private call';
  const options = {
    body: data.body || 'Someone is calling your vehicle.',
    tag: data.tag || 'car-webcall-incoming',
    renotify: true,
    requireInteraction: true,
    data: { url: data.url || '/' },
    actions: [
      { action: 'open', title: 'Open call' },
      { action: 'dismiss', title: 'Dismiss' }
    ]
  };

  event.waitUntil((async () => {
    const clientsList = await self.clients.matchAll({type:'window', includeUncontrolled:true});
    const visible = clientsList.some(c => c.visibilityState === 'visible');
    if (!visible) await self.registration.showNotification(title, options);
  })());
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  if (event.action === 'dismiss') return;
  const url = event.notification.data?.url || '/';
  event.waitUntil((async () => {
    const list = await self.clients.matchAll({type:'window', includeUncontrolled:true});
    for (const client of list) {
      if ('focus' in client) {
        await client.focus();
        if ('navigate' in client && client.url !== url) await client.navigate(url);
        return;
      }
    }
    if (self.clients.openWindow) await self.clients.openWindow(url);
  })());
});
