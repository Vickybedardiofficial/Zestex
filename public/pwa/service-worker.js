self.addEventListener('install', (event) => {
	self.skipWaiting();
});

self.addEventListener('activate', (event) => {
	event.waitUntil((async () => {
		const keys = await caches.keys();
		await Promise.all(keys.map((key) => caches.delete(key)));
		await self.clients.claim();
	})());
});

// Always hit network to avoid stale asset/html issues in local/dev setups.
self.addEventListener('fetch', (event) => {
	event.respondWith(fetch(event.request));
});
