/*
 * MapLibre dimuat saat dibutuhkan, bukan di setiap halaman.
 *
 * Pustakanya sekitar 1,6 MB (MapLibre 6: modul 1,1 MB dan pekerja 0,5 MB) sementara peta hanya
 * digambar empat komponen: Jelajah, rincian jalur, mode lapangan, dan Progres. Memuatnya di halaman masuk, dasbor, dan
 * daftar jalur berarti menagih ratusan kilobita kepada pengguna yang tidak akan melihat peta
 * sama sekali, padahal PRD §88 menempatkan ponsel sebagai platform utama.
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

/*
 * Tema tampilan.
 *
 * Server merender data-theme pada <html> dari cookie, tetapi hanya pada muat penuh.
 * Kunjungan Inertia mengganti komponen halaman, bukan <html>, sehingga sesudah memilih
 * tema atau menekan tombol kembali atributnya tertinggal. app.tsx karena itu memanggil
 * terapkanTema pada setiap navigasi, dan cookie (bukan props halaman di riwayat, yang
 * membawa pilihan lama) menjadi sumber kebenarannya.
 */
const temaDariCookie = () => document.cookie.match(/(?:^|; )tema=(light|dark)(?:;|$)/)?.[1] ?? null;

const terapkanTema = () => {
    const tema = temaDariCookie();

    if (tema) {
        document.documentElement.dataset.theme = tema;
    } else {
        delete document.documentElement.dataset.theme;
    }
};

window.terapkanTema = terapkanTema;

/*
 * Tampilan: Efek transparan dan Gerak (Profil). Server merender data-efek="solid" dan
 * data-gerak="kurangi" dari cookie; di sini keduanya disamakan lagi setelah kunjungan
 * Inertia, sama dengan tema. Kaca (data-efek="penuh") hanya menyala bila pengguna tidak
 * memilih Solid, sistem tidak meminta kurangi transparansi, dan perangkat melaporkan
 * memori sedikitnya 4 GB. Peramban yang tidak melaporkan memori (Safari, Firefox)
 * tetap solid: arah yang salah di sini hanya kehilangan hiasan, tidak kehilangan
 * keterbacaan (riset docs/riset/2026-09-25-uiux/01 §4.1).
 */
const cookieTampilan = (nama, nilai) => new RegExp(`(?:^|; )${nama}=${nilai}(?:;|$)`).test(document.cookie);

const perangkatMampuKaca = () =>
    (navigator.deviceMemory ?? 0) >= 4 && !window.matchMedia?.('(prefers-reduced-transparency: reduce)').matches;

const terapkanTampilan = () => {
    const akar = document.documentElement;

    if (cookieTampilan('efek', 'solid')) {
        akar.dataset.efek = 'solid';
    } else if (perangkatMampuKaca()) {
        akar.dataset.efek = 'penuh';
    } else {
        delete akar.dataset.efek;
    }

    if (cookieTampilan('gerak', 'kurangi')) {
        akar.dataset.gerak = 'kurangi';
    } else {
        delete akar.dataset.gerak;
    }
};

window.terapkanTampilan = terapkanTampilan;
terapkanTampilan();

/*
 * Apakah tema gelap sedang berlaku, memperhitungkan pilihan eksplisit maupun sistem.
 * Dipakai peta untuk meredupkan basemap terang di halaman gelap.
 */
window.temaGelapAktif = () => {
    const eksplisit = document.documentElement.dataset.theme;

    if (eksplisit === 'dark' || eksplisit === 'light') {
        return eksplisit === 'dark';
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

let pemuatan = null;

/*
 * MapLibre 6 hanya ESM tanpa ekspor default, jadi yang dipakai namespace modulnya. Pengurai
 * tile-nya berjalan di web worker dari berkas build sendiri: `?worker&url` membuat Vite
 * membundel maplibre-gl-worker.mjs dan memberi URL-nya, dan setWorkerUrl dipanggil sebelum peta
 * pertama dibuat (panduan instalasi MapLibre 6 untuk Vite). Tanpanya MapLibre mencari pekerja
 * di sebelah potongan bundel, tempat berkas itu tidak ada.
 */
window.muatPeta = () => {
    pemuatan ??= Promise.all([
        import('maplibre-gl'),
        import('maplibre-gl/dist/maplibre-gl.css'),
        import('maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url'),
    ]).then(([modul, , pekerja]) => {
        modul.setWorkerUrl(pekerja.default);
        window.maplibregl = modul;

        return modul;
    });

    return pemuatan;
};

/**
 * Teks spesifier impor pertama di dalam fungsi `impor`, atau null. Spesifier itu wajib ada di
 * teks fungsinya sebagai literal supaya impornya berjalan, juga sesudah bundler menulisnya
 * menjadi nama potongan berhash; Function.prototype.toString mengembalikan teks sumber fungsi.
 */
export function spesifierImpor(impor) {
    const cocok = String(impor).match(/import\(\s*(['"`])([^'"`]+?)\1\s*\)/);

    return cocok ? cocok[2] : null;
}

/**
 * Pemuat modul yang tidak menyimpan kegagalan (ruling W6-P2n). Janji yang berhasil dipakai
 * bersama; janji yang ditolak dikosongkan, dan panggilan berikutnya memanggil `imporUlang(n)`
 * dengan n percobaan ulang ke berapa.
 */
export function pemuatUlang(impor, imporUlang) {
    let janji = null;
    let gagal = 0;

    return () => {
        if (janji === null) {
            const ke = gagal;

            janji = (ke === 0 ? impor() : imporUlang(ke)).catch((galat) => {
                janji = null;
                gagal = ke + 1;

                throw galat;
            });
        }

        return janji;
    };
}

/*
 * Perekam jejak dimuat hanya saat dipakai: perekam di mode pendakian, pengirim dan ringkasan bila
 * perangkat memegang jejak, dan pertanyaan sebelum keluar. Pola yang sama dengan peta: modul yang
 * tidak dipakai tidak boleh menagih bita kepada siapa pun.
 *
 * Impor yang gagal (sinyal hilang tepat saat itu) tidak mengunci perekam dan pengirim sampai muat
 * penuh (ruling W6-P2n). Peramban menyimpan kegagalan impor per URL di peta modul dokumen
 * (terbukti di Chrome, tinjauan Tugas 11): mengulang import yang sama tetap gagal walau sudah
 * daring. Karena itu percobaan ulang meminta URL potongan yang sama dengan penanda ?ulang=n, yang
 * berbeda per percobaan dan karena itu benar-benar diminta lagi. URL-nya dibaca dari teks fungsi
 * impor itu sendiri (spesifierImpor) dan diselesaikan terhadap potongan ini. Bila tidak terbaca,
 * impor yang sama diulang. Hanya satu salinan modul yang pernah berhasil dimuat di satu dokumen,
 * karena setiap pemakai memuatnya lewat window.muatJejak.
 */
const imporJejak = () => import('./jejak.js');
const spesifierJejak = spesifierImpor(imporJejak);

window.muatJejak = pemuatUlang(imporJejak, (ke) =>
    spesifierJejak === null ? imporJejak() : import(/* @vite-ignore */ `${new URL(spesifierJejak, import.meta.url).href}?ulang=${ke}`),
);
