/* Kamelya Service Worker — static önbellek + API network-first + offline geri dönüş. */
var STATIK = 'kamelya-statik-v1';
var STATIK_DOSYALAR = [
  '/assets/css/tasarim-sistemi.css',
  '/assets/js/ana.js',
  '/assets/js/hesaplama-araci.js',
  '/offline.html'
];

self.addEventListener('install', function (o) {
  o.waitUntil(caches.open(STATIK).then(function (k) { return k.addAll(STATIK_DOSYALAR); }));
});

self.addEventListener('activate', function (o) {
  o.waitUntil(
    caches.keys().then(function (adlar) {
      return Promise.all(adlar.filter(function (a) { return a !== STATIK; }).map(function (a) { return caches.delete(a); }));
    })
  );
});

self.addEventListener('fetch', function (o) {
  var url = new URL(o.request.url);
  if (o.request.method !== 'GET') return;
  if (url.pathname.indexOf('/api/') !== -1) {
    /* API: network-first, 1 saat runtime önbellek. */
    o.respondWith(
      fetch(o.request).then(function (y) {
        var kopya = y.clone();
        caches.open('kamelya-api-v1').then(function (k) { k.put(o.request, kopya); });
        return y;
      }).catch(function () { return caches.match(o.request); })
    );
    return;
  }
  o.respondWith(
    caches.match(o.request).then(function (eslesme) {
      return eslesme || fetch(o.request).catch(function () {
        if (o.request.mode === 'navigate') return caches.match('/offline.html');
        throw new Error('cevrimdisi');
      });
    })
  );
});
