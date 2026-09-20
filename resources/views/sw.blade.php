{{-- Versi cache mengikuti manifest build: aset berganti, cache lama ikut dibuang. --}}
@php($versi = is_file(public_path('build/manifest.json')) ? substr(md5_file(public_path('build/manifest.json')), 0, 8) : 'dev')
const SHELL = 'shell-{{ $versi }}';

/*
 * Kebijakannya satu kalimat: shell boleh disimpan, isi tidak.
 *
 * Menyajikan halaman dari cache berarti menampilkan status jalur dan prakiraan cuaca
 * lama seolah kini. PRD §94 dan §95 melarang persis itu, dan pada produk keselamatan
 * status "BUKA" yang basi jauh lebih berbahaya daripada halaman yang gagal terbuka.
 *
 * Maka dokumen selalu diambil dari jaringan. Ketika jaringannya tidak ada, yang
 * ditampilkan adalah halaman luring yang mengaku tidak tahu, bukan salinan lama.
 */
const SHELL_FILES = [
    '/offline',
    '/icons/app-192.png',
    '/icons/app-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(SHELL).then((cache) => cache.addAll(SHELL_FILES)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((kunci) => Promise.all(kunci.filter((k) => k !== SHELL).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) {
        return;
    }

    // Halaman selalu dari jaringan. Gagal berarti luring, dan luring dijawab dengan
    // pengakuan, bukan dengan salinan lama.
    if (request.destination === 'document') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline'))
        );

        return;
    }

    // Aset berversi aman disimpan: namanya berubah setiap kali isinya berubah.
    if (['style', 'script', 'font', 'image'].includes(request.destination)) {
        event.respondWith(
            caches.match(request).then((tersimpan) => tersimpan || fetch(request).then((respons) => {
                if (respons.ok) {
                    const salinan = respons.clone();
                    caches.open(SHELL).then((cache) => cache.put(request, salinan));
                }

                return respons;
            }))
        );
    }
});
