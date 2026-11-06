import { router, useForm, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import { FormulirAdmin, PilihanAdmin, TeksPanjangAdmin, konfirmasiLalu, type Pilihan } from '@/components/admin/FormulirAdmin';
import { Tabel } from '@/components/admin/Tabel';
import type { TautanHalaman } from '@/components/trails/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { FormField } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
import { archive, store, update } from '@/routes/admin/mountains';
import type { PropsBersama } from '@/types';

interface IsianGunung {
    name: string;
    province: string;
    region: string;
    elevation_mdpl: string;
    description: string;
    data_source_id: string;
    latitude: string;
    longitude: string;
}

interface BarisGunung {
    id: number;
    name: string;
    province: string;
    elevation: string;
    trails: string;
    status: string;
    archived: boolean;
    isian: IsianGunung;
}

interface Props extends PropsBersama {
    mountains: { data: BarisGunung[]; links: TautanHalaman[] };
    sources: Pilihan[];
}

const kosong: IsianGunung = { name: '', province: '', region: '', elevation_mdpl: '', description: '', data_source_id: '', latitude: '', longitude: '' };

/** FR-16: tambah, ubah, atau arsipkan gunung. Setiap perubahan tercatat di audit log (§61). */
export default function Mountains() {
    const { flash, mountains, sources } = usePage<Props>().props;
    const [sunting, setSunting] = useState<number | 'baru' | null>(null);
    const form = useForm<IsianGunung>(kosong);

    const buka = (baris?: BarisGunung) => {
        form.clearErrors();
        form.setData(baris ? baris.isian : kosong);
        setSunting(baris ? baris.id : 'baru');
    };

    const simpan = () => {
        const opsi = { preserveScroll: true, onSuccess: () => setSunting(null) };

        if (sunting === 'baru') {
            form.post(store.url(), opsi);
        } else if (sunting !== null) {
            form.put(update.url({ mountain: sunting }), opsi);
        }
    };

    const ubahArsip = (baris: BarisGunung) =>
        konfirmasiLalu(baris.archived ? null : 'Arsipkan gunung ini? Seluruh jalurnya ikut hilang dari pencarian pendaki.', () =>
            router.post(archive.url({ mountain: baris.id }), {}, { preserveScroll: true }),
        );

    return (
        <div className="py-8">
            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: profile.url(), label: 'Profil' }} title="Kelola Gunung" description="Tambah, ubah, atau arsipkan data gunung. Setiap perubahan tercatat di audit log." />

                <AdminNav />

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}

                <div className="mb-4">
                    <Button onClick={() => buka()}>Tambah gunung</Button>
                </div>

                {sunting !== null ? (
                    <FormulirAdmin
                        key={String(sunting)}
                        className="mb-6"
                        judul={sunting === 'baru' ? 'Tambah gunung' : 'Ubah gunung'}
                        onSubmit={simpan}
                        processing={form.processing}
                        errors={form.errors}
                        onBatal={() => setSunting(null)}
                        fokusAwal="name"
                    >
                        <FormField name="name" label="Nama gunung" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} error={form.errors.name} />

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <FormField name="province" label="Provinsi" value={form.data.province} onChange={(e) => form.setData('province', e.target.value)} error={form.errors.province} />
                            <FormField name="region" label="Wilayah" value={form.data.region} onChange={(e) => form.setData('region', e.target.value)} error={form.errors.region} />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <FormField name="elevation_mdpl" type="number" label="Ketinggian (mdpl)" value={form.data.elevation_mdpl} onChange={(e) => form.setData('elevation_mdpl', e.target.value)} error={form.errors.elevation_mdpl} />
                            <FormField name="latitude" type="number" step="any" label="Latitude" value={form.data.latitude} onChange={(e) => form.setData('latitude', e.target.value)} error={form.errors.latitude} />
                            <FormField name="longitude" type="number" step="any" label="Longitude" value={form.data.longitude} onChange={(e) => form.setData('longitude', e.target.value)} error={form.errors.longitude} />
                        </div>

                        <PilihanAdmin
                            name="data_source_id"
                            label="Sumber data"
                            placeholder="Belum ditentukan"
                            options={sources}
                            value={form.data.data_source_id}
                            onChange={(v) => form.setData('data_source_id', v)}
                            error={form.errors.data_source_id}
                        />

                        <TeksPanjangAdmin name="description" label="Deskripsi" value={form.data.description} onChange={(v) => form.setData('description', v)} error={form.errors.description} />
                    </FormulirAdmin>
                ) : null}

                <Tabel
                    keterangan="Daftar gunung"
                    baris={mountains.data}
                    kunci={(g) => g.id}
                    kosong={{
                        judul: 'Belum ada gunung terdaftar',
                        penjelasan: 'Gunung adalah induk dari jalur, trip, dan status resmi. Selama daftar ini kosong, tidak ada yang dapat dibuat di atasnya.',
                    }}
                    kolom={[
                        { judul: 'Nama', kepala: true, isi: (g) => g.name },
                        { judul: 'Provinsi', isi: (g) => g.province },
                        { judul: 'Ketinggian', isi: (g) => g.elevation },
                        { judul: 'Jalur', isi: (g) => g.trails },
                        { judul: 'Status', isi: (g) => g.status },
                        {
                            judul: 'Aksi',
                            isi: (g) => (
                                <div className="flex flex-wrap gap-2">
                                    <Button variant="secondary" size="sm" onClick={() => buka(g)} aria-label={`Ubah ${g.name}`}>Ubah</Button>
                                    <Button variant="secondary" size="sm" onClick={() => ubahArsip(g)} aria-label={`${g.archived ? 'Aktifkan' : 'Arsipkan'} ${g.name}`}>
                                        {g.archived ? 'Aktifkan' : 'Arsipkan'}
                                    </Button>
                                </div>
                            ),
                        },
                    ]}
                />

                <div className="mt-6">
                    <Pagination links={mountains.links} />
                </div>
            </div>
        </div>
    );
}

Mountains.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
