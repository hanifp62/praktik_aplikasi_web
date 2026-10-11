import { pack } from '@/routes/trips';
import { alurUnduhPaket, GagalPaket, GagalShell, pastikanShell, type KemajuanPaket } from './alur';
import * as periksa from './periksa';
import { alatShell } from './shellPaket';
import { bacaBagian, buka, gantiVersi, sha256Hex, simpanKunci, tulisBagian, type ManifestPaket, type RekamanPaket } from './simpan';

/**
 * Unduh dan verifikasi paket lapangan di halaman, bukan di service worker (spec v3 bagian 10.2;
 * rencana induk W6 butir 5; ruling W6-R9). Modul ini hanya merangkai alur.ts (urutan unduh dan
 * kesiapan shell, diuji Node) dengan simpan.ts, periksa.ts, fetch, dan shellPaket.ts (service
 * worker dan Cache Storage peramban).
 */

export { GagalPaket, GagalShell };
export type { KemajuanPaket };

const MINTA_JSON = { headers: { Accept: 'application/json' }, credentials: 'same-origin' } satisfies RequestInit;

export async function ambilManifest(url: string): Promise<ManifestPaket> {
    const respons = await fetch(url, MINTA_JSON);

    if (!respons.ok) {
        throw new GagalPaket(periksa.kalimatHttp(respons.status), respons.status);
    }

    try {
        return (await respons.json()) as ManifestPaket;
    } catch {
        throw new GagalPaket(periksa.kalimatHttp(500));
    }
}

/** Isi paket dan sisa ruang sebelum mengunduh; navigator.storage.estimate() boleh tidak ada (PT10-6). */
export async function cekRuang(manifest: ManifestPaket): Promise<{ boleh: boolean; kalimat: string }> {
    const perkiraan = await navigator.storage?.estimate?.().catch(() => null);

    return periksa.penilaianRuang(manifest.ukuran, perkiraan ?? null);
}

/**
 * Meminta penyimpanan menetap. Dipanggil pada ketukan unduh dan perbarui sebelum menunggu apa
 * pun (peramban mensyaratkan gestur, PT10-18 bagian b); false berarti peramban boleh menghapus
 * paket saat ruang menipis, dan pendaki diberi tahu.
 */
export async function mintaMenetap(): Promise<boolean> {
    return (await navigator.storage?.persist?.().catch(() => false)) ?? false;
}

export async function unduhPaket(kunci: string, manifest: ManifestPaket, kabar: (k: KemajuanPaket) => void): Promise<RekamanPaket> {
    const db = await buka(kunci);

    return alurUnduhPaket(
        manifest,
        {
            ambilManifest: () => ambilManifest(pack.url(manifest.trip.id)),
            ambilBagian: (url) => fetch(url, MINTA_JSON),
            sha256: sha256Hex,
            bacaBagian: (tripId, versi) => bacaBagian(db, tripId, versi),
            tulisBagian: (rekaman) => tulisBagian(db, rekaman),
            gantiVersi: (rekaman) => gantiVersi(db, rekaman),
            pastikanShell: () => pastikanShell(alatShell()),
            simpanKunci: () => simpanKunci(kunci),
            sekarang: () => new Date(),
            periksa,
        },
        kabar,
    );
}

/** Kalimat untuk pendaki dari galat apa pun; `jaringan` untuk fetch yang putus (PT10-7). */
export function pesanGalat(galat: unknown, jaringan: string): string {
    return galat instanceof GagalPaket ? galat.message : periksa.kalimatGalatUnduh(galat, jaringan);
}

/** iPhone dan iPad yang membuka dari tab peramban, bukan dari Layar Utama (periksa.perluLayarUtama, PT10-18). */
export function tabSafariIphone(): boolean {
    const terpasang = window.matchMedia?.('(display-mode: standalone)').matches || (navigator as Navigator & { standalone?: boolean }).standalone === true;

    return periksa.perluLayarUtama(navigator.userAgent, navigator.maxTouchPoints ?? 0, terpasang);
}
