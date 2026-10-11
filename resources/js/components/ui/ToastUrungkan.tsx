import { useCallback, useEffect, useRef, useState } from 'react';
import { Icon } from './Icon';

export interface DataToast {
    id: number;
    pesan: string;
    onUrungkan: () => void;
    /** Pindahkan fokus ke Urungkan saat toast tampil; lihat OpsiToast. */
    fokus?: boolean;
}

export interface OpsiToast {
    /**
     * Setel bila aksinya dipicu papan ketik (event.detail === 0 pada klik), supaya Urungkan
     * terjangkau sebelum waktunya habis (WCAG 2.2.1), bukan puluhan kali Tab jauhnya.
     */
    fokus?: boolean;
}

/** Keadaan satu toast urungkan; toast baru menggantikan yang lama. */
export function useToastUrungkan() {
    const [toast, setToast] = useState<DataToast | null>(null);
    const tampilkan = useCallback((pesan: string, onUrungkan: () => void, opsi: OpsiToast = {}) => setToast({ id: Date.now(), pesan, onUrungkan, fokus: opsi.fokus }), []);
    const tutup = useCallback(() => setToast(null), []);

    return { toast, tampilkan, tutup };
}

/**
 * Memfokus unsur ber-id sesudah render berikutnya. Unsur yang dikembalikan Urungkan baru ada
 * sesudah render, jadi pemanggil menyebut id-nya di onUrungkan dan fokusnya menyusul.
 */
export function useFokusSesudahRender() {
    const [id, setId] = useState<string | null>(null);

    useEffect(() => {
        if (id === null) {
            return;
        }

        document.getElementById(id)?.focus();
        setId(null);
    }, [id]);

    return setId;
}

interface WadahToastProps {
    toast: DataToast | null;
    onTutup: () => void;
    /** Paling sedikit 8000 ms (WCAG 2.2.1); hitungannya berhenti selama disentuh penunjuk atau difokus. */
    durasiMs?: number;
}

/**
 * Wadah toast urungkan (spec v3 bagian 8.6 dan 8.8, W1-K7). Wilayah aria-live ada sejak
 * halaman dimuat supaya pesan yang masuk diumumkan. Toast bertahan 8 detik dan dapat
 * ditutup. Hitungannya berhenti selama penunjuk berada di atasnya atau fokus berada di
 * dalamnya: dua jeda yang terpisah, jadi penunjuk yang pergi tidak melanjutkan hitungan
 * selagi fokus masih di dalam. Urungkan membatalkan aksinya lalu menutup toast.
 *
 * Toast dengan opsi fokus memindah fokus ke Urungkan. Ketika toast hilang dengan fokus di
 * dalamnya, fokus kembali ke unsur yang difokus sebelum fokus masuk ke toast, atau ke
 * #konten bila unsur itu sudah tidak ada; tidak ke body. Pengembalian itu menunggu satu
 * bingkai dan dilewati bila pemanggil sudah memindahkan fokus, misalnya ke unsur yang
 * dikembalikan Urungkan. Melayang di atas navigasi bawah dan bar aksi (--bar-aksi), di
 * bawah dialog.
 */
export function WadahToast({ toast, onTutup, durasiMs = 8000 }: WadahToastProps) {
    return (
        <div aria-live="polite" aria-atomic="true" className="pointer-events-none fixed inset-x-0 bottom-[calc(var(--nav-bawah,env(safe-area-inset-bottom))+var(--bar-aksi,0px)+1rem)] z-40 flex justify-center px-4">
            {toast ? <Toast key={toast.id} toast={toast} onTutup={onTutup} durasiMs={durasiMs} /> : null}
        </div>
    );
}

/** Unsur di luar toast yang dapat menerima fokus kembali; body berarti tidak ada. */
function unsurLuar(unsur: EventTarget | null, wadah: HTMLElement | null): HTMLElement | null {
    return unsur instanceof HTMLElement && unsur !== document.body && !wadah?.contains(unsur) ? unsur : null;
}

function Toast({ toast, onTutup, durasiMs }: { toast: DataToast; onTutup: () => void; durasiMs: number }) {
    const [jedaPenunjuk, setJedaPenunjuk] = useState(false);
    const [jedaFokus, setJedaFokus] = useState(false);
    const wadah = useRef<HTMLDivElement>(null);
    const urungkan = useRef<HTMLButtonElement>(null);
    const sebelum = useRef<HTMLElement | null>(null);
    const fokusDiDalam = useRef(false);

    useEffect(() => {
        if (jedaPenunjuk || jedaFokus) {
            return;
        }

        const pewaktu = window.setTimeout(onTutup, durasiMs);

        return () => window.clearTimeout(pewaktu);
    }, [jedaPenunjuk, jedaFokus, durasiMs, onTutup]);

    useEffect(() => {
        if (toast.fokus) {
            sebelum.current ??= unsurLuar(document.activeElement, wadah.current);
            urungkan.current?.focus();
        }

        return () => {
            if (!fokusDiDalam.current) {
                return;
            }

            const tujuan = sebelum.current;

            window.requestAnimationFrame(() => {
                const aktif = document.activeElement;

                if (aktif && aktif !== document.body) {
                    return;
                }

                if (tujuan?.isConnected) {
                    tujuan.focus();
                } else {
                    document.getElementById('konten')?.focus({ preventScroll: true });
                }
            });
        };
    }, []);

    return (
        <div
            ref={wadah}
            onMouseEnter={() => setJedaPenunjuk(true)}
            onMouseLeave={() => setJedaPenunjuk(false)}
            onFocus={(e) => {
                fokusDiDalam.current = true;
                setJedaFokus(true);
                sebelum.current ??= unsurLuar(e.relatedTarget, wadah.current);
            }}
            onBlur={(e) => {
                if (e.currentTarget.contains(e.relatedTarget as Node | null)) {
                    return;
                }

                fokusDiDalam.current = false;
                setJedaFokus(false);
            }}
            className="toast-masuk pointer-events-auto flex w-full max-w-md items-center gap-2 rounded-tombol bg-surface-inverse py-2 pl-4 pr-2 text-inverse shadow-overlay"
        >
            <p className="min-w-0 flex-1 text-kecil font-semibold">{toast.pesan}</p>
            <button
                ref={urungkan}
                type="button"
                onClick={() => {
                    toast.onUrungkan();
                    onTutup();
                }}
                className="min-h-11 shrink-0 rounded-control px-3 text-kecil font-bold underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-inverse"
            >
                Urungkan
            </button>
            <button type="button" onClick={onTutup} className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-inverse">
                <Icon name="x" size={20} />
                <span className="sr-only">Tutup pemberitahuan</span>
            </button>
        </div>
    );
}
