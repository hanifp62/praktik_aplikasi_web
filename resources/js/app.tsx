import './dasar.js';
import { createInertiaApp, router } from '@inertiajs/react';

/*
 * Pintu masuk halaman React (Inertia 3). Halaman ditemukan otomatis oleh plugin Vite
 * dari resources/js/pages. Judul tab: judul halaman lalu nama merek, dan nama merek saja
 * bila halaman tidak menyebut judul.
 */
const nama = document.querySelector<HTMLMetaElement>('meta[name="application-name"]')?.content ?? 'Memount';

createInertiaApp({
    title: (judul) => (!judul ? nama : judul.includes(nama) ? judul : `${judul} · ${nama}`),
});

// Lihat catatan tema dan Tampilan di dasar.js: <html> tidak ikut dirender ulang oleh
// kunjungan Inertia, jadi atributnya disamakan dengan cookie pada setiap navigasi.
router.on('navigate', () => [window.terapkanTema, window.terapkanTampilan].forEach((terapkan) => terapkan()));
