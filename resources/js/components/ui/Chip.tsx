import type { MouseEvent, ReactNode } from 'react';
import { kelas } from '@/lib/kelas';
import { fokusKelompok, umpanTekanKelompok } from './gaya';
import { Icon } from './Icon';

const pembungkus = 'group inline-flex min-h-11 min-w-11 max-w-full items-center justify-center rounded-full focus:outline-hidden';
const bentuk = 'inline-flex min-h-9 max-w-full items-center gap-1.5 rounded-full border px-3.5 py-1 text-left text-kecil';

interface ChipPilihanProps {
    dipilih: boolean;
    onUbah: (dipilih: boolean) => void;
    className?: string;
    children: ReactNode;
}

/**
 * Chip pilihan: bentuk 36 px di dalam target sentuh 44 px (spec v3 bagian 8.4, W1-K5).
 * Terpilih ditandai empat cara, bukan warna saja: centang, tebal huruf, garis merek, dan
 * aria-pressed (WCAG 1.4.1). Label panjang membungkus, tidak dipotong.
 */
export function ChipPilihan({ dipilih, onUbah, className, children }: ChipPilihanProps) {
    return (
        <button type="button" aria-pressed={dipilih} onClick={() => onUbah(!dipilih)} className={kelas(pembungkus, className)}>
            <span
                className={kelas(
                    bentuk,
                    fokusKelompok,
                    umpanTekanKelompok,
                    dipilih ? 'border-brand-700 bg-brand-100 font-bold text-brand-900' : 'border-control bg-surface font-semibold text-primary group-hover:bg-surface-sunken',
                )}
            >
                {dipilih ? <Icon name="check" size={16} /> : null}
                <span className="min-w-0 wrap-break-word">{children}</span>
            </span>
        </button>
    );
}

interface ChipLepasProps {
    /** Menerima event kliknya: event.detail === 0 berarti chip dilepas lewat papan ketik. */
    onLepas: (e: MouseEvent<HTMLButtonElement>) => void;
    /** Supaya pemanggil dapat memfokus chip ini lagi, misalnya sesudah Urungkan. */
    id?: string;
    className?: string;
    children: ReactNode;
}

/**
 * Menyerahkan fokus sebelum chip yang sedang difokus hilang: ke chip sesudahnya dalam grup,
 * lalu ke chip sebelumnya, lalu ke wadah grup itu sendiri. Tanpa ini fokus jatuh ke body
 * ketika tombolnya dilepas, dan pengguna papan ketik kehilangan tempatnya (WCAG 2.4.3).
 */
function serahkanFokus(tombol: HTMLButtonElement) {
    const grup = tombol.parentElement;

    if (!grup) {
        return;
    }

    const chip = Array.from(grup.querySelectorAll<HTMLElement>(':scope > [data-chip-lepas]'));
    const posisi = chip.indexOf(tombol);
    const tetangga = chip[posisi + 1] ?? chip[posisi - 1];

    if (tetangga) {
        tetangga.focus();

        return;
    }

    if (!grup.hasAttribute('tabindex')) {
        grup.tabIndex = -1;
    }

    grup.focus();
}

/**
 * Saringan aktif yang dapat dilepas; pembaca layar mendengar "Lepas saringan" lalu labelnya.
 * Chip sejenis adalah anak langsung satu wadah grup, dan wadah itu tetap ada sesudah chip
 * terakhir dilepas: di sanalah fokus mendarat bila tidak ada chip lain.
 */
export function ChipLepas({ onLepas, id, className, children }: ChipLepasProps) {
    return (
        <button
            type="button"
            id={id}
            data-chip-lepas=""
            onClick={(e) => {
                if (document.activeElement === e.currentTarget) {
                    serahkanFokus(e.currentTarget);
                }

                onLepas(e);
            }}
            className={kelas(pembungkus, className)}
        >
            <span className={kelas(bentuk, fokusKelompok, umpanTekanKelompok, 'border-brand-700 bg-brand-100 font-bold text-brand-900')}>
                <span className="sr-only">Lepas saringan </span>
                <span className="min-w-0 wrap-break-word">{children}</span>
                <Icon name="x" size={16} />
            </span>
        </button>
    );
}
