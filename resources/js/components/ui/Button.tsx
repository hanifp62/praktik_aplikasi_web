import { Tautan } from './Tautan';
import type { ButtonHTMLAttributes, ReactNode } from 'react';
import { kelas } from '@/lib/kelas';
import { umpanTekan } from './gaya';

type Varian = 'primary' | 'secondary' | 'danger' | 'ghost';

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: Varian;
    size?: 'md' | 'sm';
    href?: string;
    external?: boolean;
    processing?: boolean;
    children: ReactNode;
}

/**
 * Satu-satunya definisi tombol aplikasi: min-h-11 di kelas dasar untuk target sentuh
 * (PRD §87), focus-visible:ring untuk fokus terlihat, dan varian yang rasionya sudah
 * dihitung ColourContrastTest. brand-700 sebagai isian dengan teks on-brand, bukan
 * brand-600 yang gagal AA.
 *
 * Bentuk v3 (spec v3 bagian 8.4 dan 8.6): radius tombol 16 px, label tebal 700 yang
 * membungkus di tengah bila panjang alih-alih terpotong, dan tekan yang menyusut ke 0,98
 * selama 120 ms. Label tanpa panah hiasan (spec v3 bagian 8.8).
 */
export function Button({
    variant = 'primary',
    size = 'md',
    href,
    external = false,
    processing = false,
    className,
    children,
    type = 'button',
    disabled,
    ...rest
}: ButtonProps) {
    const ukuran = size === 'sm' ? 'px-4 py-2 text-kecil' : 'px-5 py-3 text-isi';

    const gaya: Record<Varian, string> = {
        primary: 'bg-brand-700 text-on-brand hover:bg-brand-800 focus-visible:ring-brand-600',
        secondary: 'border border-control bg-surface text-primary hover:bg-surface-sunken focus-visible:ring-brand-600',
        danger: 'bg-danger-600 text-white hover:bg-danger-700 focus-visible:ring-danger-600',
        ghost: 'text-brand-ink underline underline-offset-2 hover:text-brand-900 focus-visible:ring-brand-600',
    };

    const semua = kelas(
        'inline-flex items-center justify-center gap-2 rounded-tombol min-h-11 text-center font-bold',
        'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
        umpanTekan,
        ukuran,
        gaya[variant],
        className,
    );

    if (href) {
        // Atribut tombol lain tidak berlaku pada tautan, tetapi nama aksesibel harus
        // ikut: "Checkpoint" yang berulang di setiap baris tabel perlu nama jalurnya.
        const nama = { 'aria-label': rest['aria-label'], 'aria-describedby': rest['aria-describedby'], title: rest.title };

        // Situs lain dibuka di tab baru seperti setiap tautan resmi lain (TenggatTerdekat), tanpa
        // window.opener, dan pembaca layar diberi tahu sebelum tabnya berpindah.
        return external ? (
            <a href={href} target="_blank" rel="noopener noreferrer" className={semua} {...nama}>
                {children}
                <span className="sr-only"> (membuka tab baru)</span>
            </a>
        ) : (
            <Tautan href={href} className={semua} {...nama}>
                {children}
            </Tautan>
        );
    }

    return (
        <button type={type} className={semua} disabled={disabled || processing} aria-busy={processing || undefined} {...rest}>
            {processing ? (
                <span className="inline-flex items-center gap-2">
                    <svg className="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
                    </svg>
                    <span className="sr-only">Memproses</span>
                </span>
            ) : null}
            {children}
        </button>
    );
}
