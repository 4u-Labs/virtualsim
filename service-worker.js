const CACHE_NAME = 'virtualsim-v2';
const ASSETS = [
    './',
    'index.php',
    'style-v1.css',
    'app.js',
    'manifest.json',
    'icon.png'
];

// Install Event - cache assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('[Service Worker] Salvando recursos no cache');
                return cache.addAll(ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate Event - clean old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.map(key => {
                    if (key !== CACHE_NAME) {
                        console.log('[Service Worker] Removendo cache antigo', key);
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event - cache-first with network fallback for assets, avoiding API routes
self.addEventListener('fetch', event => {
    // Não interceptar chamadas da API local ou webhooks
    if (event.request.url.includes('/api/') || event.request.url.includes('sms.php') || event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(cachedResponse => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(event.request).then(networkResponse => {
                    // Ignorar requisições externas como extensões do Chrome
                    if (!event.request.url.startsWith('http')) {
                        return networkResponse;
                    }
                    return caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, networkResponse.clone());
                        return networkResponse;
                    });
                });
            })
    );
});
