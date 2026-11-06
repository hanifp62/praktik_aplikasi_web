{{--
    Versi cache mengikuti manifest build, sakelar paket, teks halaman luring (PT9-16 bagian b), dan
    ketiga daftar simpanan (ruling W6-9g): daftar yang berubah dengan nama cache yang sama akan
    terpasang ke cache yang sama, tanpa penanda di cache shell lain, sehingga berkas paket yang baru
    tidak pernah dibawa.
--}}
@php($manifest = \App\Support\AsetLuring::manifest())
@php($paketAktif = (bool) config('hiking.field_pack.enabled'))
@php($berkasShell = \App\Support\AsetLuring::daftar($manifest))
@php($berkasPaket = \App\Support\AsetLuring::daftarPaket($manifest, $paketAktif))
@php($huruf = $paketAktif ? \App\Support\AsetLuring::HURUF : [])
const SHELL = 'shell-{{ substr(md5(json_encode([$manifest, $paketAktif, md5_file(resource_path('views/offline.blade.php')), $berkasShell, $berkasPaket, $huruf])), 0, 8) }}';
const AWALAN_SHELL = 'shell-';

/*
 * Kebijakannya (spec v3 bagian 10.4): shell boleh disimpan; isi dari server tidak pernah
 * disajikan ulang dari cache; isi paket lapangan hanya berasal dari unduhan yang dipicu
 * pendaki, disimpan halaman di IndexedDB dan cache pack-*, dan selalu tampil dengan umurnya.
 *
 * Menyajikan halaman dari cache berarti menampilkan status jalur dan prakiraan cuaca lama
 * seolah kini. PRD §94 dan §95 melarang persis itu, dan pada produk keselamatan status "BUKA"
 * yang basi jauh lebih berbahaya daripada halaman yang gagal terbuka. Maka dokumen selalu
 * diambil dari jaringan; ketika jaringannya tidak ada, yang dibuka adalah shell luring.
 */
const SHELL_FILES = @json($berkasShell);

/*
 * Shell paket (ruling W6-9g): entri shell luring beserta impornya, MapLibre, dan perekam jejak,
 * kosong selama paket lapangan mati. Tidak disimpan setiap pemasangan, karena pengunjung yang
 * tidak pernah mengunduh paket tidak perlu membayar ratusan kilobita untuk peta. Disimpan saat
 * pendaki mengunduh paket (pesan simpan-shell-paket), lalu dibawa setiap versi berikutnya selama
 * penanda ada. Penanda hanya berarti "perangkat ini pernah menyiapkan shell paket", tanpa
 * identitas apa pun; Tugas 10 memeriksanya sebelum "Siap luring", Tugas 11 menghapusnya saat
 * keluar.
 */
const SHELL_PAKET = @json($berkasPaket);
const PENANDA_PAKET = '/__memount-shell-paket';

// Huruf disimpan satu per satu sesudah SHELL_PAKET: addAll semua-atau-tidak, dan huruf yang
// gagal diambil tidak boleh menggagalkan penyimpanan shell paket (PT9-16 bagian c).
const HURUF = @json($huruf);

// Saat paket lapangan dibuka, dokumen yang tidak menjawab dalam waktu ini selagi sinyal lemah
// jatuh ke shell luring, tempat paket di perangkat dapat dibuka (ruling W6-9f). Saat mati, 0:
// menunggu jaringan seperti sebelum W6.
const BATAS_DOKUMEN_MS = {{ $paketAktif ? 8000 : 0 }};

// Penanda ditulis paling akhir, jadi penanda yang ada berarti SHELL_PAKET sudah utuh di cache itu.
const simpanShellPaket = (cache) => cache.addAll(SHELL_PAKET)
    .then(() => Promise.all(HURUF.map((url) => cache.add(url).catch(() => {}))))
    .then(() => cache.put(PENANDA_PAKET, new Response('')));

const adaPenandaLama = () => caches.keys()
    .then((kunci) => Promise.all(kunci
        .filter((k) => k.startsWith(AWALAN_SHELL) && k !== SHELL)
        .map((k) => caches.open(k).then((cache) => cache.match(PENANDA_PAKET)))))
    .then((hasil) => hasil.some((respons) => respons !== undefined));

// Pemasangan menyimpan shell kecil saja. Shell paket dibawa hanya bila perangkat ini pernah
// menyiapkannya untuk versi lain. Bila pembawaan gagal, pemasangan gagal dan versi lama tetap
// aktif bersama shell paketnya, sehingga keadaannya tetap konsisten (ruling W6-9g).
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(SHELL)
            .then((cache) => cache.addAll(SHELL_FILES)
                .then(() => (SHELL_PAKET.length > 0 ? adaPenandaLama() : false))
                .then((bawa) => (bawa ? simpanShellPaket(cache) : null)))
            .then(() => self.skipWaiting())
    );
});

// Kontrak dengan siapkanShellPaket Tugas 10: pesan { jenis: 'simpan-shell-paket' } dengan port
// MessageChannel, dibalas { ok: true } sesudah penanda tersimpan, atau { ok: false } selama paket
// mati (SHELL_PAKET kosong) atau bila penyimpanan gagal. Hanya aset build berversi dan huruf yang
// disimpan; tidak ada respons halaman atau data pengguna.
//
// Bila penanda sudah ada di SHELL, shell paket versi ini sudah utuh (penanda ditulis paling akhir),
// jadi pesan langsung dibalas { ok: true } tanpa mengunduh ulang: setiap unduh atau segarkan paket
// mengirim pesan ini, dan di sinyal lemah basecamp itu tidak boleh menarik ulang sekitar 409.845
// bita gzip (ruling W6-9o, amandemen W6-9g). Pemeriksaan skrip shell oleh Tugas 10 tetap menjadi
// jaring kedua.
self.addEventListener('message', (event) => {
    if (event.data?.jenis !== 'simpan-shell-paket') {
        return;
    }

    const balas = (ok) => event.ports[0]?.postMessage({ ok });

    if (SHELL_PAKET.length === 0) {
        balas(false);

        return;
    }

    event.waitUntil(
        caches.open(SHELL)
            .then((cache) => cache.match(PENANDA_PAKET).then((ada) => (ada ? null : simpanShellPaket(cache))))
            .then(() => balas(true), () => balas(false))
    );
});

// Hanya shell versi lama yang dibuang. Cache paket (pack-*) dan cache lain milik halaman tidak
// pernah disentuh deploy: paket hanya dihapus halaman (keluar akun, ganti akun, atau versi
// baru yang sudah lolos verifikasi).
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((kunci) => Promise.all(kunci.filter((k) => k.startsWith(AWALAN_SHELL) && k !== SHELL).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

// Permintaan navigasi tidak dibangun ulang dengan init: Request dengan init mengubah mode
// navigate, jadi batas waktu memakai Promise.race, bukan AbortController (ruling W6-9f).
const batasWaktu = (ms) => new Promise((_, tolak) => {
    setTimeout(() => tolak(new Error('Dokumen tidak menjawab.')), ms);
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Halaman selalu dari jaringan. Gagal berarti luring, dan luring dijawab dengan shell
    // luring, bukan dengan salinan lama halaman itu.
    if (request.destination === 'document') {
        event.respondWith(
            BATAS_DOKUMEN_MS === 0
                ? fetch(request).catch(() => caches.match('/offline'))
                : Promise.race([fetch(request), batasWaktu(BATAS_DOKUMEN_MS)]).catch(() => caches.match('/offline'))
        );

        return;
    }

    // Hanya aset berversi: build Vite (nama berhash), ikon, dan huruf. Respons Inertia, JSON,
    // manifest paket, dan foto laporan selalu dari jaringan. Penyimpanan aset baru dibungkus
    // waitUntil supaya service worker tidak berhenti sebelum tersimpan (PT9-16 bagian a).
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/') || url.pathname.startsWith('/fonts/')) {
        event.respondWith(
            caches.match(request).then((tersimpan) => tersimpan || fetch(request).then((respons) => {
                if (respons.ok) {
                    const salinan = respons.clone();
                    event.waitUntil(caches.open(SHELL).then((cache) => cache.put(request, salinan)));
                }

                return respons;
            }))
        );
    }
});
