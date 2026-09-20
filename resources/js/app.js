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
