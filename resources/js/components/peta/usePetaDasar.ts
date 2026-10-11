import { usePage } from '@inertiajs/react';
import { useEffect, useEffectEvent, useRef, useState, type RefObject } from 'react';
import type { PropsBersama } from '@/types';
import { warnaToken } from './warnaToken';

export type Peta = import('maplibre-gl').Map;
export type Pustaka = typeof import('maplibre-gl');
/** Satu titik [bujur, lintang], urutan GeoJSON. */
export type Titik = [number, number];

/**
 * Peta dasar mengikuti tema: basemap topografi diredupkan di tema gelap, tidak dihitamkan
 * (DK-18, M1), karena kontur adalah informasi keselamatan; kanvas polos tanpa basemap (paket
 * luring) memakai warna latar tema. Dipakai saat peta dimuat dan saat tema lapangan dibalik
 * (DK-49).
 */
export function ikutkanTemaPetaDasar(peta: Peta, gelap: boolean): void {
    if (peta.getLayer('latar')) {
        peta.setPaintProperty('latar', 'background-color', warnaToken('--surface-sunken', 'rgb(238, 234, 225)'));
    }

    if (peta.getLayer('basemap')) {
        peta.setPaintProperty('basemap', 'raster-brightness-max', gelap ? 0.78 : 1);
        peta.setPaintProperty('basemap', 'raster-saturation', gelap ? -0.25 : 0);
    }
}

/**
 * Teks antarmuka MapLibre dalam bahasa halaman. Bawaannya berbahasa Inggris ("Zoom in",
 * "Map marker", "Close popup"), dan pembaca layar yang bersuara Indonesia melafalkannya
 * dengan keliru (WCAG 3.1.2). Satuan skala dibiarkan bawaan.
 */
const TEKS_PETA: Record<string, string> = {
    'Map.Title': 'Peta interaktif',
    'Marker.Title': 'Penanda peta',
    'Popup.Close': 'Tutup keterangan',
    'NavigationControl.ZoomIn': 'Perbesar',
    'NavigationControl.ZoomOut': 'Perkecil',
    'NavigationControl.ResetBearing': 'Kembalikan arah ke utara',
    'GeolocateControl.FindMyLocation': 'Temukan lokasi saya',
    'GeolocateControl.LocationNotAvailable': 'Lokasi tidak tersedia',
    'FullscreenControl.Enter': 'Layar penuh',
    'FullscreenControl.Exit': 'Keluar dari layar penuh',
    'TerrainControl.Enable': 'Tampilkan relief',
    'TerrainControl.Disable': 'Sembunyikan relief',
    'CooperativeGesturesHandler.WindowsHelpText': 'Tekan Ctrl sambil menggulir untuk memperbesar peta',
    'CooperativeGesturesHandler.MacHelpText': 'Tekan ⌘ sambil menggulir untuk memperbesar peta',
    'CooperativeGesturesHandler.MobileHelpText': 'Geser peta dengan dua jari',
    'LogoControl.Title': 'Logo MapLibre',
};

interface OpsiPetaDasar {
    /** Titik pembuka (zoom 12) sebelum isi dibingkai. Tanpanya peta dibuka pada Indonesia (zoom 4). */
    pusat?: Titik | null;
    /** Ringkasan isi yang digambar. Peta dibangun ulang hanya bila nilainya berubah; tanpanya, sekali. */
    kunci?: string;
    /** Begitu peta ada, sebelum basemap selesai dimuat. */
    saatDibuat?: (peta: Peta, maplibregl: Pustaka) => void;
    /** Sesudah basemap dimuat: tempat source, layer, dan penanda. */
    saatSiap?: (peta: Peta, maplibregl: Pustaka) => void;
    /** Kendali perbesar bawaan MapLibre (29 px). Mode lapangan memasang kendali 48 px sendiri. */
    kendaliBawaan?: boolean;
}

/**
 * Dasar bersama setiap peta MapLibre. Komponen peta hanya menambahkan isinya lewat
 * saatSiap; yang lain dipegang di sini, sekali:
 *
 * - MapLibre dimuat malas lewat window.muatPeta (dasar.js), jadi 787 kB hanya ditagih
 *   di halaman yang menggambar peta.
 * - Basemap dibangun dari props bersama `peta` (config/hiking.php), tidak ditulis di
 *   komponen, sehingga sumber tile dan atribusinya satu.
 * - Basemap topografi diredupkan di tema gelap, tidak dihitamkan (DK-18, M1): kontur
 *   adalah informasi keselamatan.
 * - Wadah dapat tersembunyi atau berubah ukuran sesudah peta dibuat (tampilan Daftar di
 *   ponsel). MapLibre melewatkan laporan ukuran pertamanya, jadi ukuran dibaca ulang di
 *   sini setiap kali wadah berubah.
 * - Kegagalan diakui (gagal, dan galatnya ke konsol), bukan dibiarkan sebagai wadah
 *   abu-abu yang diam: itu terbaca sebagai aplikasi rusak justru saat sinyal tipis.
 */
export function usePetaDasar(wadahRef: RefObject<HTMLDivElement | null>, opsi: OpsiPetaDasar = {}) {
    const { peta: konfigurasi } = usePage<PropsBersama>().props;
    const peta = useRef<Peta | null>(null);
    const pustaka = useRef<Pustaka | null>(null);
    const [siap, setSiap] = useState(false);
    const [gagal, setGagal] = useState(false);

    // Props Inertia datang sebagai objek baru di setiap kunjungan. Yang dibandingkan
    // isinya, supaya peta tidak dibangun ulang (dan pembaca kehilangan posisinya)
    // ketika isinya sama.
    const kunci = JSON.stringify([konfigurasi, opsi.kunci ?? null]);

    // Opsi dibaca saat peta dibuat, jadi pemanggil boleh memberi fungsi baru di setiap render.
    const bangun = useEffectEvent((el: HTMLDivElement, maplibregl: Pustaka) => {
        const { pusat, saatDibuat, saatSiap } = opsi;
        const petaIni = new maplibregl.Map({
            container: el,
            // Atribusi tampil sebagai teks di WadahPeta. Kontrol bawaan merender HTML atribusi
            // lewat innerHTML, dan sanitasinya dapat dilewati (CVE-2026-85061, sampai 6.4.0).
            attributionControl: false,
            // Tanpa sumber tile (paket lapangan luring tahap 1, spec v3 bagian 10.5) kanvasnya
            // polos: hanya latar, tanpa permintaan jaringan apa pun.
            style:
                konfigurasi.styleUrl ||
                ((konfigurasi.rasterTiles ?? []).length === 0
                    ? { version: 8, sources: {}, layers: [{ id: 'latar', type: 'background', paint: { 'background-color': warnaToken('--surface-sunken', 'rgb(238, 234, 225)') } }] }
                    : {
                          version: 8,
                          sources: {
                              basemap: {
                                  type: 'raster',
                                  tiles: konfigurasi.rasterTiles ?? [],
                                  tileSize: 256,
                                  maxzoom: konfigurasi.maxZoom ?? 17,
                                  attribution: konfigurasi.attribution ?? '',
                              },
                          },
                          layers: [{ id: 'basemap', type: 'raster', source: 'basemap' }],
                      }),
            center: pusat ?? [113, -2],
            zoom: pusat ? 12 : 4,
            maxZoom: konfigurasi.maxZoom ?? 17,
            locale: TEKS_PETA,
        });

        peta.current = petaIni;
        pustaka.current = maplibregl;

        if (opsi.kendaliBawaan ?? true) {
            petaIni.addControl(new maplibregl.NavigationControl(), 'top-right');
        }

        // Gaya dari URL yang gagal diambil (server mati, atau luring tanpa salinan) tidak
        // pernah memicu 'load', dan kotaknya diam kosong. Galat sebelum 'style.load'
        // pasti galat gaya: tile belum diminta sebelum gayanya terbaca. Galat tile
        // sesudahnya diabaikan karena petanya tetap terbaca.
        if (konfigurasi.styleUrl) {
            let gayaTerbaca = false;
            petaIni.once('style.load', () => {
                gayaTerbaca = true;
            });
            petaIni.on('error', (e) => {
                if (!gayaTerbaca && peta.current === petaIni) {
                    console.error('[peta] gaya peta gagal dimuat', e.error);
                    setGagal(true);
                }
            });
        }

        petaIni.on('load', () => {
            if (peta.current !== petaIni) {
                return;
            }

            ikutkanTemaPetaDasar(petaIni, window.temaGelapAktif?.() ?? false);

            saatSiap?.(petaIni, maplibregl);
            setSiap(true);
        });

        saatDibuat?.(petaIni, maplibregl);
    });

    useEffect(() => {
        const el = wadahRef.current;

        if (!el) {
            return;
        }

        let batal = false;

        window
            .muatPeta()
            .then((maplibregl) => {
                if (!batal) {
                    bangun(el, maplibregl);
                }
            })
            .catch((galat: unknown) => {
                if (!batal) {
                    console.error('[peta] gagal dimuat', galat);
                    setGagal(true);
                }
            });

        const pengamat = new ResizeObserver(() => peta.current?.resize());
        pengamat.observe(el);

        return () => {
            batal = true;
            pengamat.disconnect();
            peta.current?.remove();
            peta.current = null;
            setSiap(false);
        };
    }, [wadahRef, kunci]);

    return { peta, pustaka, siap, gagal };
}

/** Kotak pembingkai sekumpulan titik. */
export function kotakDari(maplibregl: Pustaka, titik: Titik[]) {
    return titik.reduce((kotak, t) => kotak.extend(t), new maplibregl.LngLatBounds());
}

/**
 * Penanda dengan popup teks. setText, bukan setHTML: nama pos dan gunung dapat berasal
 * dari masukan pengguna.
 */
export function tambahPenanda(peta: Peta, maplibregl: Pustaka, titik: { lng: number; lat: number; label: string }, warna?: string) {
    const tanda = new maplibregl.Marker(warna ? { color: warna } : undefined).setLngLat([titik.lng, titik.lat]);

    if (titik.label) {
        tanda.setPopup(new maplibregl.Popup({ offset: 24 }).setText(titik.label));
    }

    tanda.addTo(peta);
}
