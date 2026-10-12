import { Tautan } from './Tautan';
import { useEffect, useRef, type ButtonHTMLAttributes, type MouseEvent, type ReactNode, type RefObject } from 'react';
import { kelas } from '@/lib/kelas';
import { umpanTekan } from './gaya';

/**
 * Fokus kembali ke tombol yang diaktifkan pengguna sesudah aksinya selesai (ruling AF-2, audit E2E
 * I-2; daftar periksa butir 7). Tombol yang sedang difokus menjadi `disabled` selama `sibuk`, dan
 * peramban menjatuhkan fokusnya ke body tanpa pernah mengembalikannya. Semantik `disabled` tidak
 * diubah, jadi kiriman ganda tetap tercegah.
 *
 * `tandai` dipanggil di onClick, yang juga menangkap klik sintetis submit implisit (Enter di
 * isian). Saat `sibuk` kembali false sesudah sempat true, tanda itu dipakai dan dihapus: bila fokus
 * sedang di body atau tidak ada, dan tombolnya masih terpasang, fokus kembali ke tombol itu. Fokus
 * yang sudah dipindah dengan sengaja oleh pemanggil (misalnya ke isian galat atau ke Urungkan) tidak
 * pernah direbut. Tombol yang dilepas oleh aksinya sendiri tidak terpasang lagi; pemanggilnya
 * memberi tujuan fokus sendiri. `tandai(e)` dengan event juga mencatat tombol yang diklik, untuk
 * tombol yang bukan Button ini (TombolIkon di TombolSimpan).
 */
export function useFokusKembali(sibuk: boolean): { tombol: RefObject<HTMLButtonElement | null>; tandai: (e?: MouseEvent<HTMLButtonElement>) => void } {
    const tombol = useRef<HTMLButtonElement | null>(null);
    const ditekan = useRef(false);
    const sibukLalu = useRef(sibuk);

    useEffect(() => {
        if (sibuk === sibukLalu.current) {
            return;
        }

        sibukLalu.current = sibuk;

        if (sibuk || !ditekan.current) {
            return;
        }

        ditekan.current = false;
        const aktif = document.activeElement;

        if ((aktif === null || aktif === document.body) && tombol.current?.isConnected) {
            tombol.current.focus();
        }
    }, [sibuk]);

    const tandai = (e?: MouseEvent<HTMLButtonElement>) => {
        ditekan.current = true;

        if (e) {
            tombol.current = e.currentTarget;
        }
    };

    return { tombol, tandai };
}

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
 * (PRD §87), focus-visible:ring-2 untuk fokus terlihat, dan varian yang rasionya sudah
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
    onClick,
    ...rest
}: ButtonProps) {
    const { tombol, tandai } = useFokusKembali(processing);
    const ukuran = size === 'sm' ? 'px-4 py-2 text-kecil' : 'px-5 py-3 text-isi';

    const gaya: Record<Varian, string> = {
        primary: 'bg-brand-700 text-on-brand hover:bg-brand-800 focus-visible:ring-brand-600',
        secondary: 'border border-control bg-surface text-primary hover:bg-surface-sunken focus-visible:ring-brand-600',
        danger: 'bg-danger-600 text-white hover:bg-danger-700 focus-visible:ring-danger-600',
        ghost: 'text-brand-ink underline underline-offset-2 hover:text-brand-900 focus-visible:ring-brand-600',
    };

    const semua = kelas(
        'inline-flex items-center justify-center gap-2 rounded-tombol min-h-11 text-center font-bold',
        'focus:outline-hidden focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
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

    // ref dan onClick mendahului {...rest} yang tidak lagi membawa onClick: pengembalian fokus
    // useFokusKembali (ruling AF-2) tidak dapat ditimpa pemanggil.
    return (
        <button
            ref={tombol}
            type={type}
            className={semua}
            disabled={disabled || processing}
            aria-busy={processing || undefined}
            onClick={(e) => {
                tandai();
                onClick?.(e);
            }}
            {...rest}
        >
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
