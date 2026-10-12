import { useEffect, useId, useRef, type MouseEvent, type ReactNode, type RefObject } from 'react';
import { Button } from './Button';
import { TombolIkon } from './TombolIkon';

/**
 * Dialog asli dengan showModal() (spec v3 bagian 8.5, W1-K6): fokus terkurung di dalam,
 * halaman di belakangnya inert, dan Escape menutup, semuanya bawaan peramban. Ketika
 * tertutup dengan cara apa pun DAN fokus memang akan hilang (lihat saatTutup di bawah),
 * fokus kembali ke pemicunya; bila pemicu sudah hilang dari DOM, fokus jatuh ke isi utama
 * #konten, bukan ke body.
 *
 * Pemicunya ref eksplisit dari pemanggil bila ada, atau unsur yang difokus saat lapisan
 * dibuka. Body tidak dihitung sebagai pemicu: Safari tidak memfokus tombol yang diklik, jadi
 * yang difokus saat itu body, dan mengembalikan fokus ke sana sama dengan kehilangannya.
 */
function useDialogAsli(terbuka: boolean, onTutup: () => void, pemicuDariPemanggil?: RefObject<HTMLElement | null>) {
    const ref = useRef<HTMLDialogElement>(null);
    const pemicu = useRef<HTMLElement | null>(null);
    const pemicuEksplisit = useRef(pemicuDariPemanggil);
    const onTutupTerakhir = useRef(onTutup);

    useEffect(() => {
        onTutupTerakhir.current = onTutup;
        pemicuEksplisit.current = pemicuDariPemanggil;
    }, [onTutup, pemicuDariPemanggil]);

    useEffect(() => {
        const dialog = ref.current;

        if (!dialog) {
            return;
        }

        if (terbuka && !dialog.open) {
            const aktif = document.activeElement;
            pemicu.current = aktif instanceof HTMLElement && aktif !== document.body ? aktif : null;
            dialog.showModal();
        } else if (!terbuka && dialog.open) {
            dialog.close();
        }
    }, [terbuka]);

    useEffect(() => {
        const dialog = ref.current;

        if (!dialog) {
            return;
        }

        /**
         * Merebut fokus HANYA bila fokus memang akan hilang: aktif saat ini ada di dalam
         * dialog yang baru ditutup (tujuan wajarnya, misalnya tombol "Tutup" yang baru
         * diklik), atau sudah jatuh ke body (ruling W6-5p putaran perbaikan 3). Fokus yang
         * sudah dipindah dengan sengaja ke luar dialog oleh pemanggil (misalnya kabarBatal di
         * Show.tsx, ruling W6-5o) dibiarkan: sebelum perbaikan ini, saatTutup merebutnya tanpa
         * syarat sesaat sesudah pemanggil memindahkannya, membuat fokus pengguna berbalik ke
         * tombol pemicu padahal halaman sudah menampilkan wilayah penolakan (tinjauan ulang
         * putaran 2 membuktikan race ini di peramban lewat intersepsi .focus() nyata).
         */
        const saatTutup = () => {
            const aktif = document.activeElement;

            if (dialog.contains(aktif) || aktif === document.body) {
                const tujuan = pemicuEksplisit.current?.current ?? pemicu.current;

                if (tujuan?.isConnected) {
                    tujuan.focus();
                } else {
                    document.getElementById('konten')?.focus({ preventScroll: true });
                }
            }

            onTutupTerakhir.current();
        };

        dialog.addEventListener('close', saatTutup);

        return () => dialog.removeEventListener('close', saatTutup);
    }, []);

    return ref;
}

/** Klik pada tirai (di luar isi dialog) menutupnya, seperti yang diharapkan pengguna ponsel. */
function tutupBilaTirai(e: MouseEvent<HTMLDialogElement>) {
    if (e.target === e.currentTarget) {
        e.currentTarget.close();
    }
}

interface LapisanProps {
    terbuka: boolean;
    onTutup: () => void;
    judul: string;
    keterangan?: string;
    /** Tombol aksi di kaki lapisan, misalnya "Terapkan" atau "Hapus". */
    aksi?: ReactNode;
    /**
     * Tujuan fokus sesudah lapisan ditutup, bila pemanggil tahu pemicunya. Diperlukan untuk
     * tombol yang tidak menerima fokus saat diklik (Safari); tanpanya dipakai unsur yang
     * difokus saat lapisan dibuka.
     */
    pemicu?: RefObject<HTMLElement | null>;
    children: ReactNode;
}

/**
 * Lembar bawah: naik dari tepi bawah di ponsel (sudut atas 26 px, pegangan hiasan), menjadi
 * panel tengah mulai sm. Isi yang panjang bergulir di dalam lembar; kaki aksi tetap terlihat.
 *
 * Margin letak memakai penanda penting Tailwind (!mt-auto, !mb-0, sm:!mb-auto; ruling AF-7,
 * audit E2E M-5). <dialog> dirender di tempat pemanggil, dan bila ia anak bukan-pertama wadah
 * space-y-*, aturan `.space-y-4 > :not([hidden]) ~ :not([hidden])` (spesifisitas tiga kelas)
 * mengalahkan mt-auto dan mb-auto (satu kelas): lembar menempel 16 px dari atas layar, bukan duduk
 * di bawah. Dialog tengah memakai m-auto! karena alasan yang sama.
 */
export function LembarBawah({ terbuka, onTutup, judul, keterangan, aksi, pemicu, children }: LapisanProps) {
    const ref = useDialogAsli(terbuka, onTutup, pemicu);
    const id = useId();

    return (
        <dialog
            ref={ref}
            aria-labelledby={`${id}-judul`}
            aria-describedby={keterangan ? `${id}-keterangan` : undefined}
            onClick={tutupBilaTirai}
            className="lembar mb-0! mt-auto! max-h-[90dvh] w-full max-w-none rounded-t-lembar border border-subtle bg-surface p-0 text-primary shadow-overlay sm:mb-auto! sm:max-w-lg sm:rounded-lembar"
        >
            <div className="flex max-h-[90dvh] flex-col">
                <div aria-hidden="true" className="mx-auto mt-2 h-1 w-10 shrink-0 rounded-full bg-control sm:hidden" />
                <div className="flex items-start justify-between gap-3 px-5 pt-3">
                    <div className="min-w-0 pt-2">
                        <h2 id={`${id}-judul`} className="text-judul-kartu text-primary">
                            {judul}
                        </h2>
                        {keterangan ? (
                            <p id={`${id}-keterangan`} className="mt-1 text-kecil text-secondary">
                                {keterangan}
                            </p>
                        ) : null}
                    </div>
                    <TombolIkon ikon="x" label="Tutup" varian="polos" onClick={() => ref.current?.close()} />
                </div>
                <div className="relative min-h-0 flex-1 overflow-y-auto px-5 py-4">{children}</div>
                {aksi ? <div className="flex flex-wrap justify-end gap-2 border-t border-subtle px-5 pb-[max(env(safe-area-inset-bottom),0.75rem)] pt-3">{aksi}</div> : null}
            </div>
        </dialog>
    );
}

/**
 * Dialog tengah untuk keputusan singkat, misalnya konfirmasi yang menyebut akibatnya. Tanpa
 * aksi dari pemanggil, kakinya berisi tombol Tutup.
 */
export function Dialog({ terbuka, onTutup, judul, keterangan, aksi, pemicu, children }: LapisanProps) {
    const ref = useDialogAsli(terbuka, onTutup, pemicu);
    const id = useId();

    return (
        <dialog
            ref={ref}
            aria-labelledby={`${id}-judul`}
            aria-describedby={keterangan ? `${id}-keterangan` : undefined}
            onClick={tutupBilaTirai}
            className="dialog-tengah m-auto! w-[calc(100%-2rem)] max-w-md rounded-utama border border-subtle bg-surface p-0 text-primary shadow-overlay"
        >
            <div className="px-5 pt-5">
                <h2 id={`${id}-judul`} className="text-judul-kartu text-primary">
                    {judul}
                </h2>
                {keterangan ? (
                    <p id={`${id}-keterangan`} className="mt-1 text-isi text-secondary">
                        {keterangan}
                    </p>
                ) : null}
            </div>
            <div className="px-5 py-4">{children}</div>
            <div className="flex flex-wrap justify-end gap-2 border-t border-subtle px-5 py-3">
                {aksi ?? (
                    <Button variant="secondary" onClick={() => ref.current?.close()}>
                        Tutup
                    </Button>
                )}
            </div>
        </dialog>
    );
}
