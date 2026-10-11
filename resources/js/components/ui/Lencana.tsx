import type { ReactNode } from 'react';
import { kelas } from '@/lib/kelas';
import { Icon, type NamaIkon } from './Icon';

export type NadaLencana = 'netral' | 'merek' | 'peringatan' | 'bahaya' | 'komunitas' | 'gelap' | 'cocok' | 'perlu' | 'kurang' | 'belum';

/** Isian dan teks setiap nada, berpasangan dengan rasio yang dihitung ColourContrastTest; garisnya terpisah. */
const nada: Record<NadaLencana, { isi: string; garis: string }> = {
    netral: { isi: 'bg-surface-sunken text-secondary', garis: 'border-control' },
    merek: { isi: 'bg-brand-100 text-brand-900', garis: 'border-brand-300' },
    peringatan: { isi: 'bg-warn-100 text-warn-900', garis: 'border-warn-300' },
    bahaya: { isi: 'bg-danger-100 text-danger-900', garis: 'border-danger-300' },
    komunitas: { isi: 'bg-community-50 text-community-900', garis: 'border-community-500' },
    gelap: { isi: 'bg-surface-inverse text-inverse', garis: 'border-surface-inverse' },
    cocok: { isi: 'bg-fit-cocok text-fit-cocok-teks', garis: 'border-fit-cocok-teks' },
    perlu: { isi: 'bg-fit-perlu text-fit-perlu-teks', garis: 'border-fit-perlu-teks' },
    kurang: { isi: 'bg-fit-kurang text-fit-kurang-teks', garis: 'border-fit-kurang-teks' },
    belum: { isi: 'bg-fit-belum text-fit-belum-teks', garis: 'border-fit-belum-teks' },
};

interface LencanaProps {
    nada?: NadaLencana;
    bentuk?: 'pil' | 'kotak';
    /** Garis putus-putus menandai asal komunitas atau data contoh, bukan hiasan. */
    garis?: 'tanpa' | 'padat' | 'putus';
    ikon?: NamaIkon;
    className?: string;
    children: ReactNode;
}

/**
 * Lencana: keadaan nyata yang pendek (kecocokan, status, sumber, Data contoh), bukan hiasan
 * (antislop R-09). Huruf kalimat tebal 13 px, bukan kapital bertracking lebar (spec v3
 * bagian 8.2). Arti tidak pernah dibawa warna saja: kata di dalamnya selalu menyebut
 * keadaannya. Label panjang membungkus, tidak dipotong.
 */
export function Lencana({ nada: n = 'netral', bentuk = 'kotak', garis = 'tanpa', ikon, className, children }: LencanaProps) {
    return (
        <span
            className={kelas(
                'inline-flex max-w-full items-center gap-1 border text-meta font-bold',
                bentuk === 'pil' ? 'rounded-full px-2.5 py-0.5' : 'rounded-lencana px-2 py-0.5',
                garis === 'tanpa' ? 'border-transparent' : nada[n].garis,
                garis === 'putus' && 'border-dashed',
                nada[n].isi,
                className,
            )}
        >
            {ikon ? <Icon name={ikon} size={16} /> : null}
            <span className="min-w-0 break-words">{children}</span>
        </span>
    );
}
