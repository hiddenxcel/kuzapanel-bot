// KuzaPanel Bot Admin service worker.
// Served from bot.kuzapanel.com (document root = this public/ folder), so
// all paths here are root-relative ("/...").
const CACHE_VERSION = 'kp-bot-v1';
const CACHE_NAME = `${CACHE_VERSION}-${new Date().toISOString().slice(0, 10)}`;

// App-shell assets to pre-cache. Admin pages are NOT pre-cached here on
// purpose — they require a live session check, so they're only cached
// opportunistically (network-first) once actually visited.
const STATIC_ASSETS = [
  '/manifest.json',
  '/offline.html',
  '/admin/login.php'
];

const ASSET_EXTENSIONS = /\.(?:css|js|png|jpg|jpeg|gif|svg|ico|webp|woff2?|ttf|eot)$/i;

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) =>
      Promise.allSettled(
        STATIC_ASSETS.map((url) =>
          cache.add(url).catch((err) => console.warn('[SW] precache miss:', url, err))
        )
      )
    ).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys
          .filter((key) => key !== CACHE_NAME && key.startsWith('kp-bot-v'))
          .map((key) => caches.delete(key))
      )
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return; // CDNs (fonts, icons) hit network normally

  // Webhook endpoints must never be cached/intercepted.
  if (url.pathname.startsWith('/webhooks/')) return;

  // 1) Page navigations: network-first (always fresh admin data), fall back
  //    to last cached copy, then the offline page if nothing is cached.
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
          return response;
        })
        .catch(() =>
          caches.match(request).then((cached) => cached || caches.match('/offline.html'))
        )
    );
    return;
  }

  // 2) Static assets (css/js/images/fonts): stale-while-revalidate.
  const isStaticAsset = ASSET_EXTENSIONS.test(url.pathname) || url.pathname.includes('/assets/');

  if (isStaticAsset) {
    event.respondWith(
      caches.match(request).then((cached) => {
        const fetchPromise = fetch(request)
          .then((response) => {
            if (response && response.status === 200) {
              const clone = response.clone();
              caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
            }
            return response;
          })
          .catch(() => cached);
        return cached || fetchPromise;
      })
    );
  }
});

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
