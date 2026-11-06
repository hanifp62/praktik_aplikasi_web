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

// Di Vercel, permintaan di atas 4,5 MB ditolak platform (413) sebelum sampai ke server, jadi tidak ada galat validasi
// untuk ditampilkan; tanpa penangkap ini Inertia memperlihatkan halaman galat platform berbahasa Inggris di dalam
// dialog. Formulir yang memeriksa ukurannya sendiri (laporan kondisi) menangani 413 lebih dulu di dekat kontrolnya.
router.on('httpException', (event) => {
    if (event.detail.response.status === 413) {
        event.preventDefault();
        window.alert('Berkas terlalu besar untuk dikirim. Pilih berkas yang lebih kecil, lalu coba lagi.');
    }
});
