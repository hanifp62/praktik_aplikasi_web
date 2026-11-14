import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { InputError, Label, TextInput } from '@/components/ui/Field';
import { Icon } from '@/components/ui/Icon';
import { LembarBawah } from '@/components/ui/Lembar';
import { kelas } from '@/lib/kelas';
import type { BulanTarget, ButirTarget, IsianTarget, KabarProgres, TargetPribadiData } from './types';

const isianTarget: Array<{ kunci: keyof IsianTarget; label: string }> = [
    { kunci: 'tanjakan_m', label: 'Tanjakan per bulan (m)' },
    { kunci: 'jarak_km', label: 'Jarak per bulan (km)' },
    { kunci: 'pendakian', label: 'Pendakian per bulan' },
];

const kotakBulan: Record<BulanTarget['keadaan'], string> = {
    ada: 'bg-brand-700',
    batal: 'border-2 border-dashed border-brand-700',
    kosong: 'bg-surface-sunken',
    nanti: 'border border-subtle',
};

/**
 * Target pribadi bulanan (FR-25, spec v3 bagian 6.14, prototype 13): capaian bulan berjalan
 * dibanding target yang dipasang pendaki sendiri (R-015). Capaian ditulis sebagai angka "N / M",
 * tidak pernah persen; yang belum tercapai disebut angkanya tanpa teguran, dan tidak ada
 * rentetan. Target tidak mengubah label kecocokan maupun tingkat pengalaman (BR-11). Kabar
 * simpanan dibacakan di wilayah status yang selalu terpasang di bagian ini.
 */
export function TargetPribadi({ target, kabar }: { target: TargetPribadiData; kabar: KabarProgres | null }) {
    const [terbuka, setTerbuka] = useState(false);
    const form = useForm<IsianTarget>(target.awal);

    const buka = () => {
        form.setData(target.awal);
        form.clearErrors();
        setTerbuka(true);
    };

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.put(target.ubah, { preserveScroll: true, onSuccess: () => setTerbuka(false) });
    };

    return (
        <section aria-labelledby="judul-target-pribadi">
            <div className="flex items-baseline justify-between gap-3">
                <h2 id="judul-target-pribadi" className="text-bagian text-primary">
                    Target pribadi
                </h2>
                <button
                    type="button"
                    onClick={buka}
                    className="inline-flex min-h-11 items-center px-1 text-kecil font-bold text-brand-ink underline-offset-2 hover:underline focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600"
                >
                    {target.daftar.length > 0 ? 'Ubah target' : 'Pasang target'}
                </button>
            </div>
            <p role="status" className={kabar?.bagian === 'target' ? 'mt-2 rounded-kartu border border-brand-300 bg-brand-100 p-3 text-kecil text-brand-900' : undefined}>
                {kabar?.bagian === 'target' ? kabar.teks : ''}
            </p>

            <div className="mt-3 rounded-kartu border border-subtle bg-surface p-4">
                {target.daftar.length === 0 ? (
                    <p className="text-isi text-secondary">Belum ada target. Target bulanan untuk tanjakan, jarak, atau jumlah pendakian dibandingkan hanya dengan pendakian Anda sendiri.</p>
                ) : (
                    <ul className="space-y-5">
                        {target.daftar.map((t) => (
                            <ButirTargetBulan key={t.metrik} target={t} />
                        ))}
                    </ul>
                )}
                <p className="mt-4 border-t border-subtle pt-3 text-meta text-muted">{target.aturan}</p>
            </div>

            <LembarBawah
                terbuka={terbuka}
                onTutup={() => setTerbuka(false)}
                judul="Target pribadi"
                keterangan={`Per bulan, mulai ${target.bulan}. Kosongkan untuk tidak memasang target itu.`}
            >
                <form onSubmit={kirim} noValidate className="space-y-4">
                    {isianTarget.map((i) => (
                        <div key={i.kunci}>
                            <Label htmlFor={`target-${i.kunci}`}>{i.label}</Label>
                            <TextInput
                                id={`target-${i.kunci}`}
                                inputMode="numeric"
                                value={form.data[i.kunci]}
                                onChange={(e) => form.setData(i.kunci, e.target.value)}
                                aria-invalid={form.errors[i.kunci] ? true : undefined}
                                aria-describedby={form.errors[i.kunci] ? `target-${i.kunci}-galat` : undefined}
                                className="tabular-nums"
                            />
                            <InputError id={`target-${i.kunci}-galat`} messages={form.errors[i.kunci]} className="mt-2" />
                        </div>
                    ))}
                    <Button type="submit" processing={form.processing}>
                        Simpan target
                    </Button>
                </form>
            </LembarBawah>
        </section>
    );
}

function ButirTargetBulan({ target }: { target: ButirTarget }) {
    return (
        <li>
            <p className="flex flex-wrap items-baseline justify-between gap-x-3 text-kecil">
                <span className="font-bold text-primary">{target.judul}</span>
                <span className="tabular-nums text-primary">
                    {target.capaian} / {target.sasaran}
                </span>
            </p>
            {target.bulan ? (
                <ol aria-label="Pendakian per bulan" className="mt-2 flex gap-1">
                    {target.bulan.map((b) => (
                        <li key={b.nama} className="flex min-w-0 flex-1 flex-col items-center gap-1">
                            <span aria-hidden="true" className={kelas('block h-2.5 w-full rounded-lencana', kotakBulan[b.keadaan])} />
                            <span aria-hidden="true" className="text-label text-muted">
                                {b.pendek.charAt(0)}
                            </span>
                            <span className="sr-only">
                                {b.nama}: {b.teks}
                            </span>
                        </li>
                    ))}
                </ol>
            ) : (
                <span aria-hidden="true" className="mt-2 block h-2.5 overflow-hidden rounded-full bg-surface-sunken">
                    <span className="block h-full rounded-full bg-brand-700" style={{ width: `${Math.round(target.rasio * 100)}%` }} />
                </span>
            )}
            <p className={kelas('mt-2 inline-flex items-center gap-1 text-meta', target.tercapai ? 'font-bold text-brand-ink' : 'text-secondary')}>
                {target.kalimat}
                {target.tercapai ? <Icon name="check" size={16} /> : null}
            </p>
            {target.catatan ? <p className="mt-1 text-meta text-muted">{target.catatan}</p> : null}
            {target.keterangan ? <p className="mt-1 text-meta text-secondary">{target.keterangan}</p> : null}
        </li>
    );
}

