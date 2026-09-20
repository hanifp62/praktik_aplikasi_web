/*
 * MapLibre dimuat saat dibutuhkan, bukan di setiap halaman.
 *
 * Pustakanya 787 kB sementara satu-satunya halaman yang menggambar peta adalah mode
 * pendakian. Memuatnya di halaman masuk, dasbor, dan daftar jalur berarti menagih
 * ratusan kilobita kepada pengguna yang tidak akan melihat peta sama sekali, padahal
 * PRD §88 menempatkan ponsel sebagai platform utama.
 *
 * Tetap di-bundle lewat Vite, bukan dari CDN: mode pendakian dipakai di lapangan tempat
 * sinyal tipis, dan bergantung pada host pihak ketiga persis pada saat itu adalah
 * kegagalan yang dapat dihindari.
 */
/*
 * Service worker didaftarkan agar aplikasi dapat dipasang ke layar utama (PRD §106).
 *
 * Yang disimpannya hanya shell. Halaman selalu diambil dari jaringan, karena menyajikan
 * status jalur dan prakiraan cuaca dari cache berarti menampilkan keadaan lama seolah
 * kini, dan §94 serta §95 melarang persis itu.
 */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Pemasangan yang gagal tidak boleh mengganggu apa pun: aplikasi tetap
            // berjalan penuh tanpa service worker.
        });
    });
}

let pemuatan = null;

window.muatPeta = () => {
    pemuatan ??= Promise.all([
        import('maplibre-gl'),
        import('maplibre-gl/dist/maplibre-gl.css'),
    ]).then(([modul]) => {
        window.maplibregl = modul.default;

        return modul.default;
    });

    return pemuatan;
};

/*
 * Perekam jejak dimuat hanya oleh pendaki yang menyalakannya, dan hanya di mode
 * pendakian. Pola yang sama dengan peta: modul yang tidak dipakai tidak boleh menagih
 * bita kepada siapa pun.
 */
let pemuatanJejak = null;

window.muatJejak = () => {
    pemuatanJejak ??= import('./jejak.js');

    return pemuatanJejak;
};
