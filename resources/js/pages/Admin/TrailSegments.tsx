import { router, useForm, usePage } from '@inertiajs/react';
import { useRef, useState, type ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import { FormulirAdmin, PilihanAdmin, TeksPanjangAdmin, useKonfirmasiAdmin, type Pilihan } from '@/components/admin/FormulirAdmin';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { Checkbox, FormField, InputError } from '@/components/ui/Field';
import { fokusTerlihat } from '@/components/ui/gaya';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { kelas } from '@/lib/kelas';
import { contribute } from '@/routes';
import { trails as daftarJalur } from '@/routes/admin';
import { destroy, move, store, update } from '@/routes/trails/segments';
import type { PropsBersama } from '@/types';

interface IsianSegmen {
    name: string;
    sequence: string;
    description: string;
    distance_km: string;
    elevation_gain_m: string;
    technical_demand: string;
    terrain_character: string[];
}

interface BarisSegmen {
    id: number;
    name: string;
    title: string;
    description: string | null;
    distance: string | null;
    elevationGain: string | null;
    technicalDemand: string | null;
    terrainLabels: string[];
    checkpointsCount: number;
    isian: IsianSegmen;
}

interface Props extends PropsBersama {
    trail: { id: number; name: string };
    segments: BarisSegmen[];
    nextSequence: string;
    demands: Pilihan[];
    terrains: Pilihan[];
}

const idArah = (id: number, arah: -1 | 1): string => `${arah < 0 ? 'naik' : 'turun'}-segmen-${id}`;
const idHapus = (id: number): string => `hapus-segmen-${id}`;
const ID_JUDUL_DAFTAR = 'judul-daftar-segmen';

const kosong = (urutan: string): IsianSegmen => ({
    name: '',
    sequence: urutan,
    description: '',
    distance_km: '',
    elevation_gain_m: '',
    technical_demand: '',
    terrain_character: [],
});

/**
 * FR-16 dan PRD §92: tambah, ubah, urutkan, dan hapus segmen jalur.
 * Terbuka bagi admin dan pemandu bersertifikat jenjang Ahli untuk kawasannya sendiri.
 */
export default function TrailSegments() {
    const { auth, flash, trail, segments, nextSequence, demands, terrains } = usePage<Props>().props;
    const [sunting, setSunting] = useState<number | null>(null);
    const sedangPindah = useRef(false);
    const form = useForm<IsianSegmen>(kosong(nextSequence));
    const { konfirmasiLalu, dialogKonfirmasi } = useKonfirmasiAdmin();
    const admin = auth.user?.isAdmin ?? false;

    const ubah = (baris: BarisSegmen) => {
        form.clearErrors();
        form.setData(baris.isian);
        setSunting(baris.id);
    };

    const batal = () => {
        form.clearErrors();
        form.setData(kosong(nextSequence));
        setSunting(null);
    };

    const ubahMedan = (nilai: string, dipilih: boolean) =>
        form.setData('terrain_character', dipilih ? [...form.data.terrain_character, nilai] : form.data.terrain_character.filter((t) => t !== nilai));

    const simpan = () => {
        const opsi = {
            preserveScroll: true,
            onSuccess: (halaman: { props: Record<string, unknown> }) => {
                form.setData(kosong(String(halaman.props.nextSequence ?? '')));
                setSunting(null);
            },
        };

        if (sunting === null) {
            form.post(store.url({ trail: trail.id }), opsi);
        } else {
            form.put(update.url({ trail: trail.id, segment: sunting }), opsi);
        }
    };

    const pindah = (id: number, arah: -1 | 1) => {
        if (sedangPindah.current) {
            return;
        }

        sedangPindah.current = true;
        router.post(
            move.url({ trail: trail.id, segment: id }),
            { arah },
            {
                preserveScroll: true,
                onSuccess: () => {
                    const aktif = document.activeElement;

                    if (aktif !== null && aktif !== document.body && aktif.id !== 'konten') {
                        return;
                    }

                    const sama = document.getElementById(idArah(id, arah));
                    const lain = document.getElementById(idArah(id, arah < 0 ? 1 : -1));

                    (sama instanceof HTMLButtonElement && !sama.disabled ? sama : lain)?.focus();
                },
                onFinish: () => {
                    sedangPindah.current = false;
                },
            },
        );
    };

    const hapus = (id: number) => {
        const posisi = segments.findIndex((s) => s.id === id);
        const tujuan = posisi < 0 ? [] : [segments[posisi + 1], segments[posisi - 1]].flatMap((s) => (s ? [idHapus(s.id)] : []));

        konfirmasiLalu('Hapus segmen jalur ini? Pos yang tertaut ke segmen ini tetap tersimpan tanpa segmen.', () =>
            router.delete(destroy.url({ trail: trail.id, segment: id }), {
                preserveScroll: true,
                onSuccess: () => {
                    const aktif = document.activeElement;

                    if (document.getElementById(idHapus(id)) !== null || (aktif !== null && aktif !== document.body && aktif.id !== 'konten')) {
                        return;
                    }

                    [...tujuan, ID_JUDUL_DAFTAR].map((t) => document.getElementById(t)).find((unsur) => unsur !== null)?.focus();
                },
            }),
        );
    };

    return (
        <div className="py-8">
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    induk={admin ? { href: daftarJalur.url(), label: 'Kelola Jalur' } : { href: contribute.url(), label: 'Kontribusi Data Jalur' }}
                    title="Kelola Segmen Jalur"
                    description={trail.name}
                />

                {admin ? <AdminNav /> : null}

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}

                <FormulirAdmin
                    key={String(sunting)}
                    className="mb-6"
                    judul={sunting === null ? 'Tambah segmen jalur' : 'Ubah segmen jalur'}
                    onSubmit={simpan}
                    processing={form.processing}
                    errors={form.errors}
                    labelSimpan="Simpan segmen"
                    onBatal={sunting === null ? undefined : batal}
                    fokusAwal={sunting === null ? undefined : 'name'}
                >
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <FormField name="name" label="Nama segmen" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} error={form.errors.name} />
                        <FormField name="sequence" type="number" min={1} label="Urutan" value={form.data.sequence} onChange={(e) => form.setData('sequence', e.target.value)} error={form.errors.sequence} />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <FormField name="distance_km" type="number" step="any" min={0} label="Jarak (km)" value={form.data.distance_km} onChange={(e) => form.setData('distance_km', e.target.value)} error={form.errors.distance_km} />
                        <FormField name="elevation_gain_m" type="number" min={0} label="Tanjakan (m)" value={form.data.elevation_gain_m} onChange={(e) => form.setData('elevation_gain_m', e.target.value)} error={form.errors.elevation_gain_m} />
                        <PilihanAdmin name="technical_demand" label="Tuntutan teknis" placeholder="Belum diketahui" options={demands} value={form.data.technical_demand} onChange={(v) => form.setData('technical_demand', v)} error={form.errors.technical_demand} />
                    </div>

                    {terrains.length > 0 ? (
                        <fieldset aria-describedby={form.errors.terrain_character ? 'terrain_character-error' : undefined}>
                            <legend className="block text-sm font-medium text-secondary">Karakter medan</legend>
                            <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                {terrains.map((opsi) => (
                                    <label key={opsi.value} className="flex min-h-11 items-center gap-2 rounded-control border border-subtle px-3 py-2 text-sm">
                                        <Checkbox
                                            value={opsi.value}
                                            checked={form.data.terrain_character.includes(opsi.value)}
                                            onChange={(e) => ubahMedan(opsi.value, e.target.checked)}
                                        />
                                        {opsi.label}
                                    </label>
                                ))}
                            </div>
                            <InputError id="terrain_character-error" messages={form.errors.terrain_character} className="mt-2" />
                        </fieldset>
                    ) : null}

                    <TeksPanjangAdmin name="description" label="Deskripsi segmen" rows={2} value={form.data.description} onChange={(v) => form.setData('description', v)} error={form.errors.description} />
                </FormulirAdmin>

                <Card className="mb-6">
                    <h2 id={ID_JUDUL_DAFTAR} tabIndex={-1} className={kelas('rounded-kecil text-bagian text-primary', fokusTerlihat)}>
                        Daftar segmen jalur
                    </h2>
                    <div className="mt-3">
                        {segments.length === 0 ? (
                            <EmptyState
                                jenis="pertama"
                                title="Belum ada segmen jalur tercatat"
                                description="Bagi jalur menjadi ruas berurutan agar laporan kondisi, status pembatasan per ruas, dan pos sepanjang jalur dapat ditautkan secara tepat."
                            />
                        ) : (
                            <ol className="space-y-2">
                                {segments.map((s, i) => (
                                    <li key={s.id} className="flex flex-wrap items-start justify-between gap-3 rounded-control border border-subtle px-3 py-3 text-sm">
                                        <div className="min-w-0">
                                            <p className="font-medium text-primary">
                                                {s.title}
                                                {s.distance ? <span className="font-normal text-secondary"> · {s.distance}</span> : null}
                                                {s.elevationGain ? <span className="font-normal text-secondary"> · {s.elevationGain}</span> : null}
                                                {s.technicalDemand ? <span className="font-normal text-secondary"> · Teknis {s.technicalDemand}</span> : null}
                                            </p>
                                            <p className="mt-0.5 text-xs text-muted">
                                                {s.checkpointsCount} pos tertaut
                                                {s.terrainLabels.length > 0 ? ` · Medan: ${s.terrainLabels.join(', ')}` : ''}
                                            </p>
                                            {s.description ? <p className="mt-1 text-xs text-secondary">{s.description}</p> : null}
                                        </div>
                                        <span className="flex flex-wrap gap-2">
                                            <Button id={idArah(s.id, -1)} variant="secondary" size="sm" disabled={i === 0} onClick={() => pindah(s.id, -1)} aria-label={`Naikkan urutan ${s.name}`}>Naik</Button>
                                            <Button id={idArah(s.id, 1)} variant="secondary" size="sm" disabled={i === segments.length - 1} onClick={() => pindah(s.id, 1)} aria-label={`Turunkan urutan ${s.name}`}>Turun</Button>
                                            <Button variant="secondary" size="sm" onClick={() => ubah(s)} aria-label={`Ubah ${s.name}`}>Ubah</Button>
                                            <Button id={idHapus(s.id)} variant="danger" size="sm" onClick={() => hapus(s.id)} aria-label={`Hapus ${s.name}`}>Hapus</Button>
                                        </span>
                                    </li>
                                ))}
                            </ol>
                        )}
                    </div>
                </Card>

                {admin ? (
                    <Button variant="secondary" href={daftarJalur.url()}>Kembali ke daftar jalur</Button>
                ) : (
                    <Button variant="secondary" href={contribute.url()}>Kembali ke halaman kontribusi</Button>
                )}
                {dialogKonfirmasi}
            </div>
        </div>
    );
}

TrailSegments.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
