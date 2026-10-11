import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { track as unggahJejak } from '@/routes/hike';
import { status as periksaJejak } from '@/routes/hike/track';
import type { PropsBersama } from '@/types';
import { propsDariServer } from './keluar';
import { AWALAN_JEJAK, JEJAK_LAMA } from './keluarMurni';
import { alurKirimJejak, bolehLanjut, bolehMulai, buatIngatan, buatPutaran, type JawabanPengirim, type KunciTitik, type TitikKirim, type TitikPerangkat } from './kirimJejakMurni';
import { kunciTertutup } from './simpan';

/**
 * Pengirim jejak pendakian (ruling W6-P2, W6-P2a, W6-P2b, W6-11e). Keputusannya ada di
 * kirimJejakMurni.ts dan diuji di Node; berkas ini menyambungkannya ke props Inertia, fetch, dan
 * fungsi berbasis nama di jejak.js.
 *
 * Dipasang hanya di AppLayout (usePengirimJejak), jadi mode lapangan dan shell luring, yang
 * memakai TataLetakPendakian, tidak pernah mengirim (I6). Putaran dimulai saat halaman AppLayout
 * dibuka atau berpindah, saat peramban kembali daring, dan saat tab terlihat lagi, hanya bila:
 * props `perangkat` ada, `pendakianAktif` null, peramban daring, kunci perangkat tidak ditutup
 * (W6-11d), dan props halaman jawaban server, bukan pulihan riwayat (W6-11g). Setiap sesi
 * ditanyakan ke server lebih dulu (GET hike.track.status, tanpa lokasi); titik baru diunggah
 * sesudah jawaban `kirim: true`, dengan token XSRF dari cookie (pola antrean.ts). URL dari
 * Wayfinder, bukan dari rekaman sesi paket.
 *
 * Tanpa basis data jejak di perangkat, jejak.js tidak dimuat dan tidak ada permintaan jaringan.
 * Keberadaannya dibaca dari indexedDB.databases(); peramban tanpa API itu memuat jejak.js untuk
 * memeriksanya lewat pembukaan yang tidak membuat basis data (adaBasisDataJejak).
 *
 * Sesudah setiap kelompok yang selesai, peristiwa `memount:jejak` di window membawa sesi, sisa
 * titiknya di perangkat, dan jumlah yang diterima server; ringkasan pendakian membacanya.
 */

interface ModulJejak {
    adaBasisDataJejak(nama: string): Promise<boolean>;
    daftarSesi(nama: string): Promise<number[]>;
    bacaTitikSesi(nama: string, sesi: number, batas?: number): Promise<TitikPerangkat[]>;
    jumlahTitikSesi(nama: string, sesi: number): Promise<number>;
    hapusTitikKunci(nama: string, kunci: KunciTitik[]): Promise<void>;
    hapusTitikSesi(nama: string, sesi: number): Promise<void>;
}

/** Detail peristiwa `memount:jejak`. */
export interface KabarJejak {
    sesi: number;
    /** Titik sesi ini yang tersisa di perangkat; null bila tidak terbaca. */
    sisa: number | null;
    /** Titik kelompok ini yang diterima server. */
    diterima: number;
}

export const PERISTIWA_JEJAK = 'memount:jejak';

function muatJejak(): Promise<ModulJejak> {
    return typeof window.muatJejak === 'function' ? (window.muatJejak() as Promise<ModulJejak>) : Promise.reject(new Error('Perekam jejak tidak tersedia.'));
}

/**
 * Nama basis data jejak pengguna ini yang ADA di perangkat: milik lebih dulu, lalu basis data
 * lama tanpa pemilik (W6-11e). Tidak pernah membuat basis data.
 */
async function basisDataAda(kunci: string): Promise<string[]> {
    const calon = [`${AWALAN_JEJAK}${kunci}`, JEJAK_LAMA];

    if (typeof indexedDB === 'undefined') {
        return [];
    }

    if (typeof indexedDB.databases === 'function') {
        try {
            const ada = new Set((await indexedDB.databases()).map((d) => d.name));

            return calon.filter((nama) => ada.has(nama));
        } catch {
            // Peramban yang menolak daftar basis data jatuh ke pemeriksaan tanpa membuat.
        }
    }

    const jejak = await muatJejak();
    const ada = await Promise.all(calon.map((nama) => jejak.adaBasisDataJejak(nama)));

    return calon.filter((_, i) => ada[i]);
}

/**
 * Titik satu sesi di perangkat ini, di kedua basis data jejak; null bila tidak dapat dibaca atau
 * kuncinya sudah ditutup di tab ini. Tanpa basis data jejak hasilnya 0 tanpa memuat jejak.js.
 */
export async function hitungJejakPerangkat(kunci: string, sesi: number): Promise<number | null> {
    if (kunciTertutup(kunci)) {
        return null;
    }

    try {
        const nama = await basisDataAda(kunci);

        if (nama.length === 0) {
            return 0;
        }

        const jejak = await muatJejak();
        const jumlah = await Promise.all(nama.map((n) => jejak.jumlahTitikSesi(n, sesi)));

        return jumlah.reduce((a, b) => a + b, 0);
    } catch {
        return null;
    }
}

/** Menghapus semua titik satu sesi dari kedua basis data jejak perangkat ini; selesai sesudah tersimpan. */
export async function hapusJejakPerangkat(kunci: string, sesi: number): Promise<void> {
    if (kunciTertutup(kunci)) {
        return;
    }

    const nama = await basisDataAda(kunci);

    if (nama.length === 0) {
        return;
    }

    const jejak = await muatJejak();

    await Promise.all(nama.map((n) => jejak.hapusTitikSesi(n, sesi)));
}

function tokenXsrf(): string {
    const cocok = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return cocok?.[1] ? decodeURIComponent(cocok[1]) : '';
}

function retryAfterMs(respons: Response): number | null {
    const detik = Number(respons.headers.get('Retry-After'));

    return Number.isFinite(detik) && detik > 0 ? detik * 1000 : null;
}

async function minta(url: string, init: RequestInit): Promise<Response | null> {
    try {
        return await fetch(url, {
            ...init,
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...init.headers },
        });
    } catch {
        return null;
    }
}

async function periksa(sesi: number): Promise<JawabanPengirim> {
    const respons = await minta(periksaJejak.url(sesi), { method: 'GET' });

    if (respons === null) {
        return { status: null };
    }

    if (!respons.ok) {
        return { status: respons.status, retryAfterMs: retryAfterMs(respons) };
    }

    try {
        const isi = (await respons.json()) as { kirim?: unknown };

        return { status: respons.status, kirim: isi.kirim === true };
    } catch {
        // Jawaban yang terputus di tengah tidak dapat dibaca: sama dengan galat jaringan.
        return { status: null };
    }
}

async function unggah(sesi: number, titik: TitikKirim[]): Promise<JawabanPengirim> {
    const respons = await minta(unggahJejak.url(sesi), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': tokenXsrf() },
        body: JSON.stringify({ points: titik }),
    });

    return respons === null ? { status: null } : { status: respons.status, retryAfterMs: retryAfterMs(respons) };
}

const ingatan = buatIngatan();

/** Jumlah AppLayout yang terpasang; nol di mode lapangan dan shell luring. */
let terpasang = 0;

/** Props terbaru halaman AppLayout yang terpasang. */
let propsTerbaru: { kunci: string | null; pendakianAktif: boolean } = { kunci: null, pendakianAktif: false };

/** Gerbang mulai putaran (bolehMulai di kirimJejakMurni.ts, diuji di Node; ruling W6-P2q). */
function gerbangTerbaru(): boolean {
    const { kunci, pendakianAktif } = propsTerbaru;

    return (
        typeof indexedDB !== 'undefined' &&
        bolehMulai({
            terpasang: terpasang > 0,
            adaPerangkat: kunci !== null,
            pendakianAktif,
            daring: navigator.onLine !== false,
            kunciTertutup: kunci !== null && kunciTertutup(kunci),
            propsDariServer: propsDariServer(),
        })
    );
}

/** Syarat sebelum setiap permintaan di tengah putaran (bolehLanjut di kirimJejakMurni.ts; ruling W6-P2q). */
function masihBoleh(kunci: string): boolean {
    return bolehLanjut({
        terpasang: terpasang > 0,
        kunciPutaran: kunci,
        kunciHalaman: propsTerbaru.kunci,
        pendakianAktif: propsTerbaru.pendakianAktif,
        daring: navigator.onLine !== false,
        kunciTertutup: kunciTertutup(kunci),
    });
}

function jalankanPutaran() {
    const kunci = propsTerbaru.kunci as string;
    let modul: Promise<ModulJejak> | null = null;
    const jejak = () => (modul ??= muatJejak());

    return alurKirimJejak({
        basisData: () => basisDataAda(kunci),
        daftarSesi: async (nama) => (await jejak()).daftarSesi(nama),
        bacaKelompok: async (nama, sesi, batas) => (await jejak()).bacaTitikSesi(nama, sesi, batas),
        hapusKunci: async (nama, kunciTitik) => (await jejak()).hapusTitikKunci(nama, kunciTitik),
        sisa: (sesi) => hitungJejakPerangkat(kunci, sesi),
        periksa,
        unggah,
        ingatan,
        sekarang: () => Date.now(),
        masihBoleh: () => masihBoleh(kunci),
        kabar: (sesi, sisa, diterima) => {
            window.dispatchEvent(new CustomEvent<KabarJejak>(PERISTIWA_JEJAK, { detail: { sesi, sisa, diterima } }));
        },
    });
}

const putaran = buatPutaran({
    jalankan: jalankanPutaran,
    gerbang: gerbangTerbaru,
    sekarang: () => Date.now(),
    jadwalkan: (panggil, ms) => {
        setTimeout(panggil, ms);
    },
});

/** Selesai sesudah putaran pengirim yang sedang berjalan di tab ini selesai; tidak pernah ditolak. */
export function tungguPengirim(): Promise<void> {
    return putaran.tunggu();
}

/** Sesi ini tidak dikirim lagi dari tab ini sampai muat penuh: pendaki sedang menghapus jejaknya (W6-P2h). */
export function tandaiJejakDihapus(sesi: number): void {
    ingatan.tandaiDihapus(sesi);
}

/** Keputusan "belum sekarang" dilupakan; dipanggil sesudah Profil menyimpan rekam jejak (tabel 2.4). */
export function lupakanKeputusanJejak(): void {
    ingatan.lupakanSementara();
}

/**
 * Dipasang sekali di AppLayout. Pemicu: halaman dibuka atau berpindah (url), `online`, dan
 * `visibilitychange` ke terlihat. Keputusan "belum berakhir" dan "pendakian lain" dilupakan begitu
 * pendakianAktif berubah dari berisi ke null.
 */
export function usePengirimJejak(): void {
    const { url, props } = usePage<PropsBersama>();
    const kunci = props.perangkat?.kunci ?? null;
    const pendakianAktif = props.pendakianAktif != null;
    const berlangsungSebelumnya = useRef(pendakianAktif);

    useEffect(() => {
        terpasang += 1;

        return () => {
            terpasang -= 1;
        };
    }, []);

    useEffect(() => {
        if (berlangsungSebelumnya.current && !pendakianAktif) {
            lupakanKeputusanJejak();
        }

        berlangsungSebelumnya.current = pendakianAktif;
    }, [pendakianAktif]);

    useEffect(() => {
        propsTerbaru = { kunci, pendakianAktif };

        const coba = () => {
            putaran.coba();
        };
        const saatTerlihat = () => {
            if (document.visibilityState === 'visible') {
                coba();
            }
        };

        coba();
        window.addEventListener('online', coba);
        document.addEventListener('visibilitychange', saatTerlihat);

        return () => {
            window.removeEventListener('online', coba);
            document.removeEventListener('visibilitychange', saatTerlihat);
        };
    }, [url, kunci, pendakianAktif]);
}
