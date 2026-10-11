import { Button } from '@/components/ui/Button';
import { ChipLepas } from '@/components/ui/Chip';
import { useFokusSesudahRender } from '@/components/ui/ToastUrungkan';
import type { LayananKerangka } from '@/layouts/Kerangka';

export interface Tafsiran {
    chip: Array<{ kunci: string; label: string }>;
    dilepas: string[];
    catatan: string | null;
}

interface TafsiranKalimatProps {
    tafsiran: Tafsiran;
    /** Mengirim daftar tafsiran yang dilepas ke server (kunjungan Inertia dengan preserveState). */
    onAbaikan: (abaikan: string[]) => void;
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
 * Melepas chip dari papan ketik memindah fokus ke Urungkan (W1-F4), dan Urungkan memfokus chip
 * yang dikembalikan, bukan body.
 */
export function TafsiranKalimat({ tafsiran, onAbaikan, tampilkanToast, awalanId }: TafsiranKalimatProps) {
    const idChip = (kunci: string) => `${awalanId}-tafsiran-${kunci}`;
    const fokusSesudahRender = useFokusSesudahRender();

    if (tafsiran.chip.length === 0 && tafsiran.dilepas.length === 0) {
        return tafsiran.catatan ? <p className="text-kecil text-secondary">{tafsiran.catatan}</p> : null;
    }

    const lepas = (kunci: string, label: string, dariPapanKetik: boolean) => {
        const sebelum = tafsiran.dilepas;
        onAbaikan([...sebelum, kunci]);
        tampilkanToast(
            `Tafsiran "${label}" dilepas.`,
            () => {
                onAbaikan(sebelum);
                fokusSesudahRender(idChip(kunci));
            },
            { fokus: dariPapanKetik },
        );
    };

    return (
        <div className="space-y-2">
            <p className="text-kecil text-secondary">Kalimat Anda dibaca sebagai:</p>
            <div className="flex flex-wrap items-center gap-1">
                {tafsiran.chip.map((c) => (
                    <ChipLepas key={c.kunci} id={idChip(c.kunci)} onLepas={(e) => lepas(c.kunci, c.label, e.detail === 0)}>
                        {c.label}
                    </ChipLepas>
                ))}
                {tafsiran.dilepas.length > 0 ? (
                    <Button variant="ghost" size="sm" onClick={() => onAbaikan([])}>
                        Kembalikan tafsiran kalimat
                    </Button>
                ) : null}
            </div>
        </div>
    );
}
