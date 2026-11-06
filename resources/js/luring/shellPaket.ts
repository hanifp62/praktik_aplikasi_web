import { pantauShellPaket, pulihkanShellSesudahAktif, siapkanShellPaket, type AlatShell, type HasilShell, type WadahSw } from './alur';
import { periksaShellLuring } from './periksa';

/**
 * Shell luring di peramban ini: service worker dan Cache Storage untuk alur.ts (ruling W6-9b,
 * W6-9g, W6-9n, W6-10d, W6-10e, W6-10g). Terpisah dari paket.ts supaya mode lapangan daring, yang
 * hanya memeriksa shell, tidak ikut memuat alur unduh dan rute paket (tinjauan Tugas 10 m-1).
 */

/** navigator.serviceWorker; tidak ada di konteks tidak aman dan sebagian jendela privat. */
export function wadahSw(): WadahSw | undefined {
    return 'serviceWorker' in navigator ? navigator.serviceWorker : undefined;
}

export function alatShell(): AlatShell {
    return {
        siapkan: () => siapkanShellPaket(wadahSw()),
        periksa: () => periksaShellLuring(typeof caches === 'undefined' ? undefined : caches),
    };
}

/**
 * Penyembuhan diri shell paket untuk muat daring yang melihat paket "Siap luring" (ruling W6-9n),
 * sesudah pengendali yang masih "activating" selesai diaktifkan (ruling W6-10g): dipakai pemeriksaan
 * saat dimuat, pemeriksaan ulang aksi sesudah pengendali berganti, dan paket lama sesudah perbarui.
 */
export function pulihkanShellPaket(): Promise<HasilShell> {
    return pulihkanShellSesudahAktif(wadahSw(), alatShell());
}

/** Pemeriksaan ulang setiap controllerchange selama halaman pemegang paket terbuka (ruling W6-10e, W6-10g). */
export function pantauShellPaketDiSini(kabar: { mulai?: () => void; perlu?: () => boolean; hasil: (hasil: HasilShell) => void }): () => void {
    return pantauShellPaket(wadahSw(), alatShell(), kabar);
}
