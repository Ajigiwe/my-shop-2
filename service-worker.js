const CACHE_NAME = 'aso-online-market-cache-v2';
const OFFLINE_URL = 'offline.html';

const ASSETS_TO_CACHE = [
    './',
    'offline.html',
    'assets/css/aso.css',
    'assets/images/logo2-rounded.png',
    'assets/images/logo-v3.png',
    'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,200;1,300;1,400;1,500;1,600;1,700;1,800&family=Outfit:wght@300;400;500;600;700;800;900&display=swap'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(ASSETS_TO_CACHE);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cache => {
                    if (cache !== CACHE_NAME) {
                        return caches.delete(cache);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    // Dynamic pages (like checkout, admin, cart, login, verify_payment, etc.)
    // should use Network First to avoid serving stale order/payment data.
    const isDynamicPage = url.pathname.endsWith('.php') || url.pathname.endsWith('/') || url.pathname === '';
    
    if (isDynamicPage) {
        event.respondWith(
            fetch(event.request)
                .catch(() => {
                    return caches.match(event.request)
                        .then(response => {
                            return response || caches.match(OFFLINE_URL);
                        });
                })
        );
    } else {
        // Static assets (images, fonts, stylesheets, etc.)
        // use Stale-While-Revalidate.
        event.respondWith(
            caches.match(event.request).then(cachedResponse => {
                if (cachedResponse) {
                    // Fetch in background to update cache
                    fetch(event.request).then(networkResponse => {
                        if (networkResponse.status === 200) {
                            caches.open(CACHE_NAME).then(cache => {
                                cache.put(event.request, networkResponse);
                            });
                        }
                    }).catch(() => {/* Ignore network errors */});
                    return cachedResponse;
                }

                return fetch(event.request).then(networkResponse => {
                    if (networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(event.request, responseToCache);
                        });
                    }
                    return networkResponse;
                }).catch(() => {
                    if (event.request.headers.get('accept') && event.request.headers.get('accept').includes('text/html')) {
                        return caches.match(OFFLINE_URL);
                    }
                });
            })
        );
    }
});
