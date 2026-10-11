import { Button } from '@/components/ui/Button';
import { ChipLepas } from '@/components/ui/Chip';
import type { LayananKerangka } from '@/layouts/Kerangka';

export interface Tafsiran {
    chip: Array<{ kunci: string; label: string }>;
    dilepas: string[];
    catatan: string | null;
}

interface TafsiranKalimatProps {
    tafsiran: Tafsiran;
    /**
     * Mengirim daftar tafsiran yang dilepas ke server (kunjungan Inertia dengan preserveState).
     * `onSuccess` berjalan sesudah jawaban server dirender, saat chip yang dikembalikan sudah ada.
     */
    onAbaikan: (abaikan: string[], onSuccess?: () => void) => void;
    /** tampilkanToast milik halaman (ruling W2-5i): Urungkan bertahan selama halaman terpasang. */
    tampilkanToast: LayananKerangka['tampilkanToast'];
    /**
     * Awalan id chip per instans. Jelajah memasang dua instans (panel layar lebar dan ponsel);
     * tanpa awalan berbeda, getElementById menemukan chip di panel yang tersembunyi di ponsel
     * dan fokus sesudah Urungkan hilang (T4-M3 tinjauan akhir W3, WCAG 2.4.3).
     */
    awalanId: string;
}

/**
 * Tafsiran kalimat pencarian (FR-27, spec v3 bagian 6.2): pembaca selalu melihat bagaimana
 * kalimatnya dibaca, sebagai chip yang dapat dilepas satu per satu dengan urungkan. Tafsiran
 * yang sudah dilepas dapat dikembalikan sekaligus. Kalimat yang tidak dikenali seluruhnya
 * disebut dengan jujur: ia dicari sebagai kata kunci biasa.
 *
 * Melepas chip dari papan ketik memindah fokus ke Urungkan (W1-F4). Urungkan memfokus chip yang
 * dikembalikan, dan "Kembalikan tafsiran kalimat", yang lepas bersama aksinya, memfokus chip
 * pertama yang dikembalikan. Chip itu baru ada sesudah jawaban server, jadi fokus dipindah di
 * onSuccess kunjungan, bukan pada render berikutnya, dan hanya bila fokus ada di body, #konten,
 * atau di dalam grup chip ini (TC-m3 tinjauan akhir, TM-1 cek peramban).
 */
export function TafsiranKalimat({ tafsiran, onAbaikan, tampilkanToast, awalanId }: TafsiranKalimatProps) {
    const idChip = (kunci: string) => `${awalanId}-tafsiran-${kunci}`;
    const idGrup = `${awalanId}-tafsiran-grup`;

    // Chip yang dilepas menyerahkan fokus ke tetangganya atau ke wadah grup (Chip.tsx), dan toast
    // mengembalikan fokus ke sana sesudah Urungkan. Fokus di dalam grup ini karena itu boleh
    // dipindah ke chip yang dikembalikan; fokus yang dipindah pengguna ke luar grup tidak direbut
    // (TM-1 cek peramban TC-m3).
    const fokusChip = (kunci: string | undefined) => () => {
        const aktif = document.activeElement;
        const diGrup = aktif !== null && document.getElementById(idGrup)?.contains(aktif) === true;

        if (kunci !== undefined && (aktif === null || aktif === document.body || aktif.id === 'konten' || diGrup)) {
            document.getElementById(idChip(kunci))?.focus();
        }
    };

    if (tafsiran.chip.length === 0 && tafsiran.dilepas.length === 0) {
        return tafsiran.catatan ? <p className="text-kecil text-secondary">{tafsiran.catatan}</p> : null;
    }

    const lepas = (kunci: string, label: string, dariPapanKetik: boolean) => {
        const sebelum = tafsiran.dilepas;
        onAbaikan([...sebelum, kunci]);
        tampilkanToast(
            `Tafsiran "${label}" dilepas.`,
            () => onAbaikan(sebelum, fokusChip(kunci)),
            { fokus: dariPapanKetik },
        );
    };

    return (
        <div className="space-y-2">
            <p className="text-kecil text-secondary">Kalimat Anda dibaca sebagai:</p>
            <div id={idGrup} className="flex flex-wrap items-center gap-1">
                {tafsiran.chip.map((c) => (
                    <ChipLepas key={c.kunci} id={idChip(c.kunci)} onLepas={(e) => lepas(c.kunci, c.label, e.detail === 0)}>
                        {c.label}
                    </ChipLepas>
                ))}
                {tafsiran.dilepas.length > 0 ? (
                    <Button variant="ghost" size="sm" onClick={() => onAbaikan([], fokusChip(tafsiran.dilepas[0]))}>
                        Kembalikan tafsiran kalimat
                    </Button>
                ) : null}
            </div>
        </div>
    );
}
