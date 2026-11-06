import { useForm } from '@inertiajs/react';
import { useRef, useState, type FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { InputError, Label, TextInput } from '@/components/ui/Field';
import { LembarBawah } from '@/components/ui/Lembar';
import { useFokusSesudahRender } from '@/components/ui/ToastUrungkan';
import type { AngkaRingkasan } from './ringkasan';

const butir: Array<{ kunci: 'jarak' | 'naik' | 'waktu' | 'tertinggi'; judul: string }> = [
    { kunci: 'jarak', judul: 'Jarak' },
    { kunci: 'naik', judul: 'Tanjakan' },
    { kunci: 'waktu', judul: 'Waktu bergerak' },
    { kunci: 'tertinggi', judul: 'Titik tertinggi' },
];

/** Bilangan bulat tanpa tanda, atau kosong (artinya sama dengan sebelumnya: lihat kirim()). */
const bulat = (s: string) => s === '' || /^\d+$/.test(s);

/**
 * Angka pendakian (spec v3 bagian 6.13, prototype 12): empat angka pada satu permukaan, sumbernya
 * disebut di bawahnya, dan "Ubah angka" bila pendaki punya catatan yang lebih tepat. Yang belum
 * ada ditulis "Belum ada", tidak pernah nol (R-007).
 *
 * Fokus sesudah aksi (ruling W6-6e, I-3 putaran perbaikan 1): dialog ini tetap terpasang pada
 * galat validasi (hanya onSuccess yang menutupnya), jadi fokus ke isian galat pertama dipindah
 * di onError lewat useFokusSesudahRender, bukan efek saat dipasang.
 *
 * Ukuran angka (ruling W6-6h): di bawah 400 px kolom grid hanya sekitar 112 px, dan pada 28 px
 * pasangan terikat seperti "↑1.650 m" lebih lebar dari kolomnya sehingga tetap terpatah. Di
 * bawah 400 px angka memakai text-judul-kartu (23 px), lalu text-judul mulai 400 px, pola yang
 * sama dengan AngkaNaik di halaman jalur. wrap-break-word tinggal sebagai jaring terakhir.
 */
export function AngkaPendakian({ angka }: { angka: AngkaRingkasan }) {
    const [terbuka, setTerbuka] = useState(false);
    const pemicu = useRef<HTMLButtonElement>(null);
    const fokusKe = useFokusSesudahRender();
    const [galatWaktuKlien, setGalatWaktuKlien] = useState<string | undefined>(undefined);
    const awalJam = angka.awal.waktu_menit === null ? '' : String(Math.floor(angka.awal.waktu_menit / 60));
    const awalMenit = angka.awal.waktu_menit === null ? '' : String(angka.awal.waktu_menit % 60);
    const form = useForm({
        jarak_km: angka.awal.jarak_km === null ? '' : String(angka.awal.jarak_km),
        naik_m: angka.awal.naik_m === null ? '' : String(angka.awal.naik_m),
        jam: awalJam,
        menit: awalMenit,
        tertinggi_m: angka.awal.tertinggi_m === null ? '' : String(angka.awal.tertinggi_m),
    });

    // Galat waktu_menit milik isian gabungan jam dan menit, jadi tidak punya kunci di data formulir.
    const galat = form.errors as Record<string, string | undefined>;
    const galatWaktu = galatWaktuKlien ?? galat.waktu_menit;

    const kirim = (e: FormEvent) => {
        e.preventDefault();

        // Jam dan Menit hanya bilangan bulat (ruling W6-6i, M-4): "8,5" dulu menjadi NaN lalu
        // JSON.stringify mengirimnya sebagai null, sehingga waktu bergerak yang sudah tercatat
        // diam-diam menjadi "Belum ada" (R-007). Diperiksa di klien sebelum kirim, bukan
        // diserahkan ke server, supaya pendaki langsung tahu isiannya ditolak, bukan diterima
        // lalu dibuang. Kosong tetap berarti sama seperti sebelumnya: kedua isian kosong berarti
        // waktu tidak diketahui (null); satu isian kosong dibaca 0 (perilaku ini tidak baru).
        if (!bulat(form.data.jam) || !bulat(form.data.menit)) {
            setGalatWaktuKlien('Isi dengan bilangan bulat, misalnya 8.');
            fokusKe('angka-jam');

            return;
        }

        setGalatWaktuKlien(undefined);
        form.transform((d) => ({
            jarak_km: d.jarak_km === '' ? null : d.jarak_km.replace(',', '.'),
            naik_m: d.naik_m === '' ? null : d.naik_m,
            waktu_menit: d.jam === '' && d.menit === '' ? null : Number(d.jam || 0) * 60 + Number(d.menit || 0),
            tertinggi_m: d.tertinggi_m === '' ? null : d.tertinggi_m,
        }));
        form.put(angka.ubah, {
            preserveScroll: true,
            onSuccess: () => setTerbuka(false),
            onError: (errors) => {
                const urutan: Array<[string, string]> = [
                    ['jarak_km', 'angka-jarak'],
                    ['naik_m', 'angka-naik'],
                    ['waktu_menit', 'angka-jam'],
                    ['tertinggi_m', 'angka-tertinggi'],
                ];
                fokusKe(urutan.find(([kunci]) => Boolean(errors[kunci]))?.[1] ?? 'angka-jarak');
            },
        });
    };

    return (
        <section aria-labelledby="judul-angka" className="rounded-utama bg-surface-inverse p-5 text-inverse">
            <h2 id="judul-angka" className="sr-only">
                Angka pendakian
            </h2>
            <dl className="grid grid-cols-2 gap-x-6 gap-y-5">
                {butir.map((b) => (
                    <div key={b.kunci} className="min-w-0">
                        <dt className="text-kecil opacity-90">{b.judul}</dt>
                        <dd className={angka[b.kunci] === null ? 'mt-1 text-isi font-bold' : 'mt-1 wrap-break-word text-judul-kartu tabular-nums min-[400px]:text-judul'}>{angka[b.kunci] ?? 'Belum ada'}</dd>
                    </div>
                ))}
            </dl>
            <div className="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-subtle pt-4">
                <p className="min-w-0 flex-1 text-kecil">{angka.keterangan}</p>
                <button
                    ref={pemicu}
                    type="button"
                    onClick={() => setTerbuka(true)}
                    className="inline-flex min-h-11 items-center rounded-tombol border border-current px-4 text-kecil font-bold focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2"
                >
                    Ubah angka
                </button>
            </div>

            <LembarBawah
                terbuka={terbuka}
                onTutup={() => setTerbuka(false)}
                pemicu={pemicu}
                judul="Ubah angka pendakian"
                keterangan="Isi dari catatan Anda sendiri, misalnya jam tangan atau aplikasi lain. Kosongkan yang tidak Anda ketahui."
            >
                <form onSubmit={kirim} className="space-y-4">
                    <Isian id="angka-jarak" label="Jarak (km)" nilai={form.data.jarak_km} onUbah={(v) => form.setData('jarak_km', v)} galat={form.errors.jarak_km} desimal />
                    <Isian id="angka-naik" label="Tanjakan (m)" nilai={form.data.naik_m} onUbah={(v) => form.setData('naik_m', v)} galat={form.errors.naik_m} />
                    <fieldset aria-describedby={galatWaktu ? 'angka-waktu-galat' : undefined}>
                        <legend className="text-isi font-bold text-primary">Waktu bergerak</legend>
                        <div className="mt-2 flex gap-3">
                            <Isian
                                id="angka-jam"
                                label="Jam"
                                nilai={form.data.jam}
                                onUbah={(v) => form.setData('jam', v)}
                                invalid={Boolean(galatWaktu)}
                                describedBy={galatWaktu ? 'angka-waktu-galat' : undefined}
                            />
                            <Isian
                                id="angka-menit"
                                label="Menit"
                                nilai={form.data.menit}
                                onUbah={(v) => form.setData('menit', v)}
                                invalid={Boolean(galatWaktu)}
                                describedBy={galatWaktu ? 'angka-waktu-galat' : undefined}
                            />
                        </div>
                        <InputError id="angka-waktu-galat" messages={galatWaktu} className="mt-2" />
                    </fieldset>
                    <Isian id="angka-tertinggi" label="Titik tertinggi (m)" nilai={form.data.tertinggi_m} onUbah={(v) => form.setData('tertinggi_m', v)} galat={form.errors.tertinggi_m} />
                    <Button type="submit" processing={form.processing}>
                        Simpan angka
                    </Button>
                </form>
            </LembarBawah>
        </section>
    );
}

function Isian({
    id,
    label,
    nilai,
    onUbah,
    galat,
    desimal = false,
    invalid = false,
    describedBy,
}: {
    id: string;
    label: string;
    nilai: string;
    onUbah: (nilai: string) => void;
    galat?: string;
    desimal?: boolean;
    /** Galat milik isian gabungan (waktu_menit): tanda invalid tanpa InputError sendiri, lihat describedBy. */
    invalid?: boolean;
    /** Override id wilayah galat, untuk isian gabungan yang membaca satu InputError bersama (ruling W6-6e). */
    describedBy?: string;
}) {
    return (
        <div className="min-w-0 flex-1">
            <Label htmlFor={id}>{label}</Label>
            <TextInput
                id={id}
                inputMode={desimal ? 'decimal' : 'numeric'}
                value={nilai}
                onChange={(e) => onUbah(e.target.value)}
                aria-invalid={galat || invalid ? true : undefined}
                aria-describedby={describedBy ?? (galat ? `${id}-galat` : undefined)}
                className="mt-1 w-full tabular-nums"
            />
            {galat !== undefined ? <InputError id={`${id}-galat`} messages={galat} className="mt-2" /> : null}
        </div>
    );
}
