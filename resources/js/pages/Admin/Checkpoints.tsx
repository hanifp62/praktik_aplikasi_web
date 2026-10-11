import { router, useForm, usePage } from '@inertiajs/react';
import { useRef, useState, type ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import { FormulirAdmin, PilihanAdmin, TeksPanjangAdmin, konfirmasiLalu, type Pilihan } from '@/components/admin/FormulirAdmin';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { FormField } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { contribute } from '@/routes';
import { trails as daftarJalur } from '@/routes/admin';
import { destroy, move, store, update } from '@/routes/trails/checkpoints';
import type { PropsBersama } from '@/types';

interface IsianCheckpoint {
    name: string;
    sequence: string;
    checkpoint_type: string;
    elevation_m: string;
    notes: string;
    latitude: string;
    longitude: string;
}

interface BarisCheckpoint {
    id: number;
    name: string;
    title: string;
    type: string;
    elevation: string | null;
    isian: IsianCheckpoint;
}

interface Props extends PropsBersama {
    trail: { id: number; name: string };
    checkpoints: BarisCheckpoint[];
    nextSequence: string;
    types: Pilihan[];
    penolakan: string | null;
}

/** Id stabil tombol Naik (arah -1) dan Turun (arah 1) satu checkpoint, tujuan fokus sesudah pindah (ruling CC-3). */
const idArah = (id: number, arah: -1 | 1): string => `${arah < 0 ? 'naik' : 'turun'}-checkpoint-${id}`;

const kosong = (urutan: string): IsianCheckpoint => ({ name: '', sequence: urutan, checkpoint_type: 'POS', elevation_m: '', notes: '', latitude: '', longitude: '' });

/**
 * FR-16: tambah, ubah, urutkan, dan hapus checkpoint. Terbuka juga bagi pemandu
 * bersertifikat untuk kawasannya sendiri; navigasi admin hanya untuk admin, karena bagi
 * pemandu setiap tautannya akan berujung penolakan.
 */
export default function Checkpoints() {
    const { auth, flash, trail, checkpoints, nextSequence, types, penolakan } = usePage<Props>().props;
    const [sunting, setSunting] = useState<number | null>(null);
    const sedangPindah = useRef(false);
    const form = useForm<IsianCheckpoint>(kosong(nextSequence));
    const admin = auth.user?.isAdmin ?? false;

    const ubah = (baris: BarisCheckpoint) => {
        form.clearErrors();
        form.setData(baris.isian);
        setSunting(baris.id);
    };

    const batal = () => {
        form.clearErrors();
        form.setData(kosong(nextSequence));
        setSunting(null);
    };

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
            form.put(update.url({ trail: trail.id, checkpoint: sunting }), opsi);
        }
    };

    /**
     * Ruling CC-3: sesudah pindah, baris daftar berurut ulang (React memindah simpul DOM-nya), dan
     * tombol yang ditekan dapat menjadi `disabled` karena pos itu kini di ujung daftar. Keduanya
     * menjatuhkan fokus ke body. Daftar baru sudah dirender sebelum onSuccess (swap Inertia memakai
     * flushSync), maka fokus mengikuti pos yang dipindah lewat id stabilnya: tombol arah yang sama
     * bila masih aktif, selain itu tombol arah lainnya pada pos yang sama. Fokus yang sudah dipindah
     * pengguna, atau yang tetap di tombolnya, tidak direbut: pemindahan hanya terjadi selama fokus
     * di body atau #konten (pola useFokusKembali).
     */
    const pindah = (id: number, arah: -1 | 1) => {
        // Klik ganda selama kiriman berjalan diabaikan. Inertia hanya membatalkan kunjungan pertama
        // di klien, sedangkan server tetap memproses kedua POST, jadi pos bergeser dua langkah.
        // Tombolnya sengaja tidak dinonaktifkan, supaya fokus CC-3 tidak jatuh ke body.
        if (sedangPindah.current) {
            return;
        }

        sedangPindah.current = true;
        router.post(
            move.url({ trail: trail.id, checkpoint: id }),
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

    const hapus = (id: number) =>
        konfirmasiLalu('Hapus checkpoint ini?', () => router.delete(destroy.url({ trail: trail.id, checkpoint: id }), { preserveScroll: true }));

    return (
        <div className="py-8">
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={admin ? { href: daftarJalur.url(), label: 'Kelola Jalur' } : { href: contribute.url(), label: 'Kontribusi Data Jalur' }} title="Kelola Checkpoint" description={trail.name} />

                {admin ? <AdminNav /> : null}

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}

                {/* Penolakan gerbang publikasi adalah keputusan domain: varian peringatan
                    dengan role="alert", bukan varian sukses. */}
                {penolakan ? <Alert variant="warning" className="mb-4">{penolakan}</Alert> : null}

                <FormulirAdmin
                    key={String(sunting)}
                    className="mb-6"
                    judul={sunting === null ? 'Tambah checkpoint' : 'Ubah checkpoint'}
                    onSubmit={simpan}
                    processing={form.processing}
                    errors={form.errors}
                    labelSimpan="Simpan checkpoint"
                    onBatal={sunting === null ? undefined : batal}
                    fokusAwal={sunting === null ? undefined : 'name'}
                >
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <FormField name="name" label="Nama checkpoint" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} error={form.errors.name} />
                        <FormField name="sequence" type="number" min={1} label="Urutan" value={form.data.sequence} onChange={(e) => form.setData('sequence', e.target.value)} error={form.errors.sequence} />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <PilihanAdmin name="checkpoint_type" label="Tipe" required placeholder="Pilih tipe" options={types} value={form.data.checkpoint_type} onChange={(v) => form.setData('checkpoint_type', v)} error={form.errors.checkpoint_type} />
                        <FormField name="latitude" type="number" step="any" label="Latitude" value={form.data.latitude} onChange={(e) => form.setData('latitude', e.target.value)} error={form.errors.latitude} />
                        <FormField name="longitude" type="number" step="any" label="Longitude" value={form.data.longitude} onChange={(e) => form.setData('longitude', e.target.value)} error={form.errors.longitude} />
                    </div>

                    <FormField name="elevation_m" type="number" label="Ketinggian (mdpl)" value={form.data.elevation_m} onChange={(e) => form.setData('elevation_m', e.target.value)} error={form.errors.elevation_m} />

                    <TeksPanjangAdmin name="notes" label="Catatan" rows={2} value={form.data.notes} onChange={(v) => form.setData('notes', v)} error={form.errors.notes} />
                </FormulirAdmin>

                <Card className="mb-6" title="Daftar checkpoint">
                    {checkpoints.length === 0 ? (
                        <p className="text-sm text-secondary">Belum ada checkpoint.</p>
                    ) : (
                        <ol className="space-y-2">
                            {checkpoints.map((c, i) => (
                                <li key={c.id} className="flex flex-wrap items-center justify-between gap-3 rounded-control border border-subtle px-3 py-2 text-sm">
                                    <span>
                                        <span className="font-medium text-primary">{c.title}</span>
                                        <span className="text-secondary"> · {c.type}</span>
                                        {c.elevation ? <span className="text-secondary"> · {c.elevation}</span> : null}
                                    </span>
                                    <span className="flex flex-wrap gap-2">
                                        <Button id={idArah(c.id, -1)} variant="secondary" size="sm" disabled={i === 0} onClick={() => pindah(c.id, -1)} aria-label={`Naikkan urutan ${c.name}`}>Naik</Button>
                                        <Button id={idArah(c.id, 1)} variant="secondary" size="sm" disabled={i === checkpoints.length - 1} onClick={() => pindah(c.id, 1)} aria-label={`Turunkan urutan ${c.name}`}>Turun</Button>
                                        <Button variant="secondary" size="sm" onClick={() => ubah(c)} aria-label={`Ubah ${c.name}`}>Ubah</Button>
                                        <Button variant="danger" size="sm" onClick={() => hapus(c.id)} aria-label={`Hapus ${c.name}`}>Hapus</Button>
                                    </span>
                                </li>
                            ))}
                        </ol>
                    )}
                </Card>

                {admin ? (
                    <Button variant="secondary" href={daftarJalur.url()}>Kembali ke daftar jalur</Button>
                ) : (
                    <Button variant="secondary" href={contribute.url()}>Kembali ke halaman kontribusi</Button>
                )}
            </div>
        </div>
    );
}

Checkpoints.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
