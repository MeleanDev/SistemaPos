const CACHE_NAME = 'pos-pwa-v1.0.0';
const OFFLINE_URL = '/offline.html';

const PRECACHE_ASSETS = [
    OFFLINE_URL,
    '/manifest.json',
    '/favicon.ico',
    '/icons/icon-72x72.png',
    '/icons/icon-96x96.png',
    '/icons/icon-128x128.png',
    '/icons/icon-144x144.png',
    '/icons/icon-152x152.png',
    '/icons/icon-192x192.png',
    '/icons/icon-384x384.png',
    '/icons/icon-512x512.png',
    '/icons/icon-maskable-192x192.png',
    '/icons/icon-maskable-512x512.png'
];

// Installation: Pre-cache offline shell & essential assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS);
        }).then(() => self.skipWaiting())
    );
});

// Activation: Clean up older cache versions and claim clients
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event handler with smart caching strategies
self.addEventListener('fetch', (event) => {
    const { request } = event;

    // 1. Ignore non-GET requests (POST, PUT, DELETE must go straight to server)
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // 2. Ignore non-HTTP(S) schemas (e.g. chrome-extension://)
    if (!url.protocol.startsWith('http')) {
        return;
    }

    // 3. Dynamic transactions and auth routes should always be network-only
    const networkOnlyPatterns = [
        '/logout',
        '/cambiar-empresa',
        '/pos/guardar',
        '/pos/devolucion',
        '/sanctum/',
        '/livewire/',
        '/api/'
    ];

    if (networkOnlyPatterns.some((pattern) => url.pathname.includes(pattern))) {
        return;
    }

    // 4. HTML Navigation Requests: Network-first, fallback to offline.html
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    // Update cache for this page if response is valid
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                })
                .catch(async () => {
                    // Try to serve from cache first
                    const cachedResponse = await caches.match(request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // Otherwise fallback to offline screen
                    const offlinePage = await caches.match(OFFLINE_URL);
                    return offlinePage || new Response('Sin conexión', { status: 503, headers: { 'Content-Type': 'text/plain' } });
                })
        );
        return;
    }

    // 5. Static Assets (CSS, JS, Fonts, Images): Stale-While-Revalidate / Cache-First
    const isStaticAsset = (
        url.pathname.startsWith('/estilos/') ||
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/icons/') ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js') ||
        url.pathname.endsWith('.woff2') ||
        url.pathname.endsWith('.woff') ||
        url.pathname.endsWith('.ttf') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.jpg') ||
        url.pathname.endsWith('.jpeg') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.ico')
    );

    if (isStaticAsset) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                const fetchPromise = fetch(request)
                    .then((networkResponse) => {
                        if (networkResponse && networkResponse.status === 200) {
                            const responseClone = networkResponse.clone();
                            caches.open(CACHE_NAME).then((cache) => {
                                cache.put(request, responseClone);
                            });
                        }
                        return networkResponse;
                    })
                    .catch(() => cachedResponse);

                return cachedResponse || fetchPromise;
            })
        );
        return;
    }

    // Default: Network with Cache Fallback
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});

// Listener for client messages (e.g. skip waiting when user confirms update)
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
