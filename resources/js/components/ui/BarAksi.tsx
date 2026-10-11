import { useEffect, useRef, type ReactNode } from 'react';

interface BarAksiProps {
    /** Nama wilayah bagi pembaca layar, misalnya "Aksi rencana trip". */
    label: string;
    /** Ringkasan keadaan yang berubah, misalnya "2 jalur ditimbang"; diumumkan ketika berubah. */
    ringkasan?: ReactNode;
    children: ReactNode;
}

/**
 * Bar aksi melayang di atas navigasi bawah (spec v3 bagian 8.5): satu elevasi
 * shadow-overlay dan material chrome. Tombolnya membungkus ke baris baru di layar sempit.
 * Satu bar per layar.
 *
 * Selama terpasang, bar menulis tingginya ke --bar-aksi di <html>, dan menghapusnya saat
 * dilepas. Pengganjal di bawah isi dan jarak bawah WadahToast membaca variabel itu, jadi
 * isi terakhir halaman tidak tertutup dan toast melayang di atas bar, bukan menutupi
 * tombolnya, berapa pun tinggi bar yang membungkus.
 */
export function BarAksi({ label, ringkasan, children }: BarAksiProps) {
    const bar = useRef<HTMLElement>(null);

    useEffect(() => {
        const el = bar.current;

        if (!el) {
            return;
        }

        const akar = document.documentElement;
        const pengamat = new ResizeObserver(() => akar.style.setProperty('--bar-aksi', `${Math.ceil(el.getBoundingClientRect().height)}px`));

        pengamat.observe(el);

        return () => {
            pengamat.disconnect();
            akar.style.removeProperty('--bar-aksi');
        };
    }, []);

    return (
        <>
            <div aria-hidden="true" className="h-[calc(var(--bar-aksi,8rem)+1.5rem)]" />
            <section ref={bar} aria-label={label} className="pointer-events-none fixed inset-x-0 bottom-[calc(var(--nav-bawah,env(safe-area-inset-bottom))+0.75rem)] z-30 px-4">
                <div className="chrome pointer-events-auto mx-auto flex max-w-xl flex-col gap-3 rounded-utama border p-3 shadow-overlay sm:flex-row sm:items-center">
                    {ringkasan ? (
                        <div aria-live="polite" className="min-w-0 flex-1 text-kecil text-secondary">
                            {ringkasan}
                        </div>
                    ) : null}
                    <div className="flex flex-wrap gap-2 sm:justify-end">{children}</div>
                </div>
            </section>
        </>
    );
}
