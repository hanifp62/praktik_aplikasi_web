import { router, useForm, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import { FormulirAdmin, PilihanAdmin, TeksPanjangAdmin, type Pilihan } from '@/components/admin/FormulirAdmin';
import { Tabel } from '@/components/admin/Tabel';
import type { TautanHalaman } from '@/components/trails/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { FormField } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
import { dispute, store, update, verify } from '@/routes/admin/sources';
import type { PropsBersama } from '@/types';

interface IsianSumber {
    source_name: string;
    source_type: string;
    source_url: string;
    source_owner: string;
    freshness_policy: string;
    notes: string;
}

interface BarisSumber {
    id: number;
    name: string;
    url: string | null;
    type: string;
    verification: string;
    verifiedAt: string;
    isian: IsianSumber;
}

interface Props extends PropsBersama {
    sources: { data: BarisSumber[]; links: TautanHalaman[] };
    types: Pilihan[];
}

const kosong: IsianSumber = { source_name: '', source_type: 'OFFICIAL', source_url: '', source_owner: '', freshness_policy: '', notes: '' };

/** FR-18: pendaftaran dan verifikasi sumber data (§58-60). */
export default function DataSources() {
    const { flash, sources, types } = usePage<Props>().props;
    const [sunting, setSunting] = useState<number | null>(null);
    const form = useForm<IsianSumber>(kosong);

    const ubah = (baris: BarisSumber) => {
        form.clearErrors();
        form.setData(baris.isian);
        setSunting(baris.id);
    };

    const batal = () => {
        form.clearErrors();
        form.setData(kosong);
        setSunting(null);
    };

    const simpan = () => {
        const opsi = { preserveScroll: true, onSuccess: batal };

        if (sunting === null) {
            form.post(store.url(), opsi);
        } else {
            form.put(update.url({ source: sunting }), opsi);
        }
    };

    const tandai = (url: string) => router.post(url, {}, { preserveScroll: true });

    return (
        <div className="py-8">
            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: profile.url(), label: 'Profil' }} title="Kelola Sumber Data" description="Setiap data penting harus memiliki sumber, tipe sumber, waktu pengambilan, dan status verifikasi." />

                <AdminNav />

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}

                <FormulirAdmin
                    key={String(sunting)}
                    className="mb-6"
                    judul={sunting === null ? 'Daftarkan sumber data' : 'Ubah sumber data'}
                    onSubmit={simpan}
                    processing={form.processing}
                    errors={form.errors}
                    labelSimpan="Simpan sumber"
                    onBatal={sunting === null ? undefined : batal}
                    fokusAwal={sunting === null ? undefined : 'source_name'}
                >
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <FormField name="source_name" label="Nama sumber" value={form.data.source_name} onChange={(e) => form.setData('source_name', e.target.value)} error={form.errors.source_name} />
                        <PilihanAdmin name="source_type" label="Tipe sumber" required placeholder="Pilih tipe" options={types} value={form.data.source_type} onChange={(v) => form.setData('source_type', v)} error={form.errors.source_type} />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <FormField name="source_url" type="url" label="URL sumber" value={form.data.source_url} onChange={(e) => form.setData('source_url', e.target.value)} error={form.errors.source_url} />
                        <FormField name="source_owner" label="Pemilik sumber" value={form.data.source_owner} onChange={(e) => form.setData('source_owner', e.target.value)} error={form.errors.source_owner} />
                    </div>

                    <FormField
                        name="freshness_policy"
                        label="Kebijakan kesegaran data"
                        hint="Contoh: mengikuti jadwal pembaruan BMKG, atau diverifikasi ulang setiap awal bulan."
                        value={form.data.freshness_policy}
                        onChange={(e) => form.setData('freshness_policy', e.target.value)}
                        error={form.errors.freshness_policy}
                    />

                    <TeksPanjangAdmin name="notes" label="Catatan" rows={2} value={form.data.notes} onChange={(v) => form.setData('notes', v)} error={form.errors.notes} />
                </FormulirAdmin>

                <Tabel
                    keterangan="Daftar sumber data"
                    baris={sources.data}
                    kunci={(s) => s.id}
                    kosong={{
                        judul: 'Belum ada sumber data terdaftar',
                        penjelasan: 'Sumber data menentukan asal setiap angka yang ditampilkan. Tanpa satu pun, keterangan yang terbit tidak dapat ditelusuri asalnya.',
                    }}
                    kolom={[
                        {
                            judul: 'Sumber',
                            kepala: true,
                            isi: (s) => (
                                <>
                                    {s.name}
                                    {s.url ? (
                                        <a href={s.url} target="_blank" rel="noopener noreferrer" className="block break-all text-xs font-normal text-brand-ink underline">
                                            {s.url}
                                            <span className="sr-only"> (membuka tab baru)</span>
                                        </a>
                                    ) : null}
                                </>
                            ),
                        },
                        { judul: 'Tipe', isi: (s) => s.type },
                        { judul: 'Verifikasi', isi: (s) => s.verification },
                        { judul: 'Diverifikasi', isi: (s) => s.verifiedAt },
                        {
                            judul: 'Aksi',
                            isi: (s) => (
                                <div className="flex flex-wrap gap-2">
                                    <Button variant="secondary" size="sm" onClick={() => ubah(s)} aria-label={`Ubah ${s.name}`}>Ubah</Button>
                                    <Button variant="secondary" size="sm" onClick={() => tandai(verify.url({ source: s.id }))} aria-label={`Verifikasi ${s.name}`}>Verifikasi</Button>
                                    <Button variant="secondary" size="sm" onClick={() => tandai(dispute.url({ source: s.id }))} aria-label={`Tandai diperdebatkan ${s.name}`}>Tandai diperdebatkan</Button>
                                </div>
                            ),
                        },
                    ]}
                />

                <div className="mt-6">
                    <Pagination links={sources.links} />
                </div>
            </div>
        </div>
    );
}

DataSources.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
