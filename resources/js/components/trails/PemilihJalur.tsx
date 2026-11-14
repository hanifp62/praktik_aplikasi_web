import { router, usePage } from '@inertiajs/react';
import { useEffect, useId, useRef, useState, type ReactNode } from 'react';
import { Button } from '@/components/ui/Button';
import { InputError, Label, TextInput } from '@/components/ui/Field';
import { kelas } from '@/lib/kelas';

/** Satu jalur katalog hasil pencarian (App\Services\Trails\CariJalur::baris). */
export interface JalurKatalog {
    id: number;
    nama: string;
    gunung: string | null;
    ringkas: string;
}

/**
 * Pencarian jalur katalog lewat muat ulang parsial prop `pilihanJalur` (hanya jalur terbit,
 * paling banyak delapan). Ketikan ditunda 300 ms dan muat ulang yang lebih lama dibatalkan,
 * supaya hasil yang tiba terakhir selalu milik ketikan terakhir; URL halaman tidak berubah.
 */
export function useCariJalur<T extends JalurKatalog = JalurKatalog>() {
    const { pilihanJalur } = usePage<{ pilihanJalur?: T[] }>().props;
    const [kata, setKata] = useState('');
    const [memuat, setMemuat] = useState(false);
    const tunda = useRef<number | null>(null);
    const batal = useRef<{ cancel: () => void } | null>(null);
    const nomor = useRef(0);

    useEffect(
        () => () => {
            if (tunda.current !== null) {
                window.clearTimeout(tunda.current);
            }
            batal.current?.cancel();
        },
        [],
    );

    const cari = (baru: string) => {
        setKata(baru);

        if (tunda.current !== null) {
            window.clearTimeout(tunda.current);
        }

        if (baru.trim().length < 2) {
            return;
        }

        tunda.current = window.setTimeout(() => {
            batal.current?.cancel();
            const id = ++nomor.current;

            router.reload({
                only: ['pilihanJalur'],
                data: { cari: baru.trim() },
                preserveUrl: true,
                onCancelToken: (token) => {
                    batal.current = token;
                },
                onStart: () => setMemuat(true),
                onFinish: () => {
                    if (id === nomor.current) {
                        setMemuat(false);
                    }
                },
            });
        }, 300);
    };

    return { kata, cari, memuat, hasil: kata.trim().length < 2 ? [] : (pilihanJalur ?? []) };
}

interface PemilihJalurProps<T extends JalurKatalog> {
    /** Judul grup, misalnya "Jalur" atau "Jalur terberat yang pernah Anda selesaikan". */
    label: string;
    dipilih: Pick<JalurKatalog, 'id' | 'nama'> | null;
    onPilih: (jalur: T | null) => void;
    /** Pilihan "jalurnya tidak ada di katalog" beserta isiannya, bila pemanggil menawarkannya. */
    manual?: { aktif: boolean; label: string; nama: string; onAktif: (aktif: boolean) => void; onNama: (nama: string) => void };
    galat?: string;
    /** Isi tambahan di bawah jalur terpilih, misalnya nilai turunan untuk dikonfirmasi. */
    children?: ReactNode;
}

/**
 * Pemilih satu jalur katalog: cari, pilih dari hasil, atau tulis nama bila jalurnya tidak ada
 * di katalog. Setiap hasil tombol 44 px yang menyebut nama, gunung, dan angka jalurnya; jumlah
 * hasil dibacakan di wilayah status yang selalu terpasang. Sesudah memilih, fokus pindah ke
 * "Ganti jalur", jadi pengguna papan ketik tidak kehilangan tempat.
 */
export function PemilihJalur<T extends JalurKatalog>({ label, dipilih, onPilih, manual, galat, children }: PemilihJalurProps<T>) {
    const id = useId();
    const { kata, cari, memuat, hasil } = useCariJalur<T>();
    const [fokusKe, setFokusKe] = useState<'ganti' | 'cari' | null>(null);

    // Fokus dipindah sesudah unsur tujuannya digambar: "Ganti jalur" sesudah memilih, kotak
    // cari sesudah mengganti.
    useEffect(() => {
        if (fokusKe !== null) {
            document.getElementById(`${id}-${fokusKe}`)?.focus();
            setFokusKe(null);
        }
    }, [fokusKe, id]);

    const status = kata.trim().length < 2 ? 'Ketik paling sedikit dua huruf nama gunung atau jalur.' : memuat ? 'Mencari jalur.' : hasil.length === 0 ? 'Tidak ada jalur katalog yang cocok.' : `${hasil.length} jalur ditemukan.`;

    return (
        <fieldset aria-describedby={galat ? `${id}-galat` : undefined}>
            <legend className="text-isi font-bold text-primary">{label}</legend>

            {dipilih && !manual?.aktif ? (
                <div className="mt-2 rounded-tombol border border-brand-700 bg-brand-50 p-3">
                    <p className="break-words text-isi font-bold text-primary">{dipilih.nama}</p>
                    {children}
                    <Button
                        id={`${id}-ganti`}
                        variant="secondary"
                        size="sm"
                        className="mt-3"
                        onClick={() => {
                            onPilih(null);
                            setFokusKe('cari');
                        }}
                    >
                        Ganti jalur
                    </Button>
                </div>
            ) : (
                <>
                    {!manual?.aktif ? (
                        <div className="mt-2">
                            <Label htmlFor={`${id}-cari`}>Cari di katalog</Label>
                            <TextInput
                                id={`${id}-cari`}
                                type="search"
                                autoComplete="off"
                                placeholder="Nama gunung atau jalur"
                                value={kata}
                                onChange={(e) => cari(e.target.value)}
                                aria-describedby={`${id}-status`}
                            />
                            <p id={`${id}-status`} role="status" className="mt-1 text-kecil text-secondary">
                                {status}
                            </p>
                            {hasil.length > 0 ? (
                                <ul className="mt-2 space-y-2">
                                    {hasil.map((j) => (
                                        <li key={j.id}>
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    onPilih(j);
                                                    setFokusKe('ganti');
                                                }}
                                                className={kelas(
                                                    'flex min-h-11 w-full flex-col items-start rounded-tombol border border-subtle bg-surface px-3 py-2 text-left hover:bg-surface-sunken',
                                                    'focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600',
                                                )}
                                            >
                                                <span className="break-words text-isi font-bold text-primary">{j.nama}</span>
                                                <span className="text-kecil text-secondary">{[j.gunung, j.ringkas].filter(Boolean).join(' · ')}</span>
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            ) : null}
                        </div>
                    ) : null}

                    {manual ? (
                        <div className="mt-3">
                            <label htmlFor={`${id}-manual`} className="flex min-h-11 cursor-pointer items-center gap-3 text-isi text-primary">
                                <input
                                    id={`${id}-manual`}
                                    type="checkbox"
                                    checked={manual.aktif}
                                    onChange={(e) => manual.onAktif(e.target.checked)}
                                    className="size-6 shrink-0 rounded-kecil border-2 border-control text-brand-700 checked:border-transparent focus:ring-brand-600"
                                />
                                {manual.label}
                            </label>
                            {manual.aktif ? (
                                <div className="mt-2">
                                    <Label htmlFor={`${id}-nama`}>Nama jalur</Label>
                                    <TextInput id={`${id}-nama`} value={manual.nama} onChange={(e) => manual.onNama(e.target.value)} maxLength={120} placeholder="Misalnya Lawu via Cemoro Sewu" />
                                </div>
                            ) : null}
                        </div>
                    ) : null}
                </>
            )}

            <InputError id={`${id}-galat`} messages={galat} className="mt-2" />
        </fieldset>
    );
}
