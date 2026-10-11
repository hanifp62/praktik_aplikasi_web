import type { ButtonHTMLAttributes } from 'react';
import { kelas } from '@/lib/kelas';
import { fokusTerlihat, umpanTekan } from './gaya';
import { Icon, type NamaIkon } from './Icon';

interface TombolIkonProps extends Omit<ButtonHTMLAttributes<HTMLButtonElement>, 'children' | 'aria-label'> {
    ikon: NamaIkon;
    /** Nama aksesibel, wajib: tombol ikon tanpa nama tidak terbaca pembaca layar (WCAG 4.1.2). */
    label: string;
    varian?: 'permukaan' | 'sumur' | 'polos';
    bentuk?: 'bulat' | 'kotak';
    /** 48 px untuk kendali utama; bawaan 44 px. */
    besar?: boolean;
}

const gaya = {
    permukaan: 'border border-subtle bg-surface text-primary hover:bg-surface-sunken',
    sumur: 'bg-surface-sunken text-primary hover:bg-brand-50',
    polos: 'text-secondary hover:bg-surface-sunken hover:text-primary',
};

/**
 * Tombol berisi satu ikon: 44 px (48 px bila besar), namanya teks sr-only dari prop label
 * yang wajib, bukan aria-label yang mudah terlupa dan tidak ikut diterjemahkan.
 */
export function TombolIkon({ ikon, label, varian = 'permukaan', bentuk = 'bulat', besar = false, type = 'button', className, ...rest }: TombolIkonProps) {
    return (
        <button
            type={type}
            className={kelas(
                'inline-flex shrink-0 items-center justify-center disabled:cursor-not-allowed disabled:opacity-50',
                besar ? 'h-12 w-12' : 'h-11 w-11',
                bentuk === 'bulat' ? 'rounded-full' : 'rounded-control',
                gaya[varian],
                fokusTerlihat,
                umpanTekan,
                className,
            )}
            {...rest}
        >
            <Icon name={ikon} size={besar ? 24 : 22} />
            <span className="sr-only">{label}</span>
        </button>
    );
}
