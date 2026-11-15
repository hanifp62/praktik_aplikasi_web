import { router, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent, type ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import { useKonfirmasiAdmin } from '@/components/admin/FormulirAdmin';
import { SelectField, TextareaField, type Opsi } from '@/components/discovery/Isian';
import { FotoGunung } from '@/components/trails/gunung/FotoGunung';
import type { FotoGunungData } from '@/components/trails/gunung/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { FormField, InputError, Label } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { mountains } from '@/routes/admin';
import { destroy, store } from '@/routes/admin/mountains/photo';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    title: string;
    gunung: { id: number; nama: string };
    foto: FotoGunungData | null;
    lisensi: Opsi[];
    batasKb: number;
}

interface IsianFoto {
    foto: File | null;
    creator: string;
    license: string;
    source_url: string;
    attribution: string;
    alt_text: string;
}

/**
 * Foto gunung berlisensi (DK-37; spec v3 bagian 8.7; ruling W8-R19): pratinjau persis seperti di
 * halaman gunung, lalu unggah atau ganti dengan kolom wajib lisensinya. Foto ditulis ulang tanpa
 * EXIF di server. Foto komunitas tidak pernah diunggah dari sini (R-017).
 */
export default function MountainPhoto() {
    const { flash, title, gunung, foto, lisensi, batasKb } = usePage<Props>().props;
    const [menghapus, setMenghapus] = useState(false);
    const { konfirmasiLalu, dialogKonfirmasi } = useKonfirmasiAdmin();
    const form = useForm<IsianFoto>({ foto: null, creator: '', license: '', source_url: '', attribution: '', alt_text: '' });

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.post(store.url({ mountain: gunung.id }), { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset() });
    };

    const hapus = () =>
        konfirmasiLalu('Hapus foto ini? Halaman gunung kembali memakai visual data dari profil jalurnya.', () => {
            setMenghapus(true);
            router.delete(destroy.url({ mountain: gunung.id }), { preserveScroll: true, onFinish: () => setMenghapus(false) });
        });

    return (
        <div className="py-8">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: mountains.url(), label: 'Kelola Gunung' }} title={title} description="Foto berlisensi untuk kepala halaman gunung, dengan atribusi di pojoknya." />

                <AdminNav />

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}

                <section aria-labelledby="judul-tampil" className="space-y-3">
                    <h2 id="judul-tampil" className="text-bagian text-primary">
                        Yang tampil di halaman gunung
                    </h2>
                    {foto ? (
                        <>
                            <FotoGunung foto={foto} />
                            <dl className="grid grid-cols-1 gap-x-6 gap-y-2 text-kecil sm:grid-cols-[minmax(0,10rem)_1fr]">
                                <dt className="font-bold text-secondary">Pembuat</dt>
                                <dd className="break-words text-primary">{foto.pembuat}</dd>
                                <dt className="font-bold text-secondary">Lisensi</dt>
                                <dd className="text-primary">{foto.lisensi}</dd>
                                <dt className="font-bold text-secondary">Sumber</dt>
                                <dd className="break-all text-primary">{foto.sumber ?? 'Milik Memount, tanpa sumber luar'}</dd>
                                <dt className="font-bold text-secondary">Teks alternatif</dt>
                                <dd className="break-words text-primary">{foto.alt}</dd>
                            </dl>
                            <Button variant="danger" size="sm" processing={menghapus} onClick={hapus}>
                                Hapus foto
                            </Button>
                        </>
                    ) : (
                        <p className="max-w-prose text-kecil text-secondary">Belum ada foto. Halaman {gunung.nama} memakai visual data dari profil jalurnya.</p>
                    )}
                </section>

                <form onSubmit={kirim} className="mt-8 space-y-4 border-t border-subtle pt-6" noValidate>
                    <h2 className="text-bagian text-primary">{foto ? 'Ganti foto' : 'Unggah foto'}</h2>
                    <div>
                        <Label htmlFor="foto">Berkas foto</Label>
                        <input
                            id="foto"
                            name="foto"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={(e) => form.setData('foto', e.target.files?.[0] ?? null)}
                            aria-describedby={form.errors.foto ? 'foto-hint foto-error' : 'foto-hint'}
                            aria-invalid={form.errors.foto ? true : undefined}
                            className="mt-1 block min-h-12 w-full rounded-tombol border border-control bg-surface px-3 py-2 text-kecil text-primary file:mr-3 file:min-h-11 file:rounded-control file:border-0 file:bg-surface-sunken file:px-3 file:font-bold file:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                        />
                        <p id="foto-hint" className="mt-1 text-kecil text-muted">
                            JPG, PNG, atau WebP, paling kecil 800 x 400 piksel, paling besar {Math.round(batasKb / 1024)} MB. Ditulis ulang tanpa metadata lokasi.
                        </p>
                        <InputError id="foto-error" messages={form.errors.foto} className="mt-2" />
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <FormField name="creator" label="Pembuat foto" required value={form.data.creator} onChange={(e) => form.setData('creator', e.target.value)} error={form.errors.creator} />
                        <SelectField name="license" label="Lisensi" required placeholder="Pilih lisensi" options={lisensi} value={form.data.license} onChange={(e) => form.setData('license', e.target.value)} error={form.errors.license} />
                    </div>
                    <FormField
                        name="source_url"
                        label="Alamat sumber foto"
                        type="url"
                        hint="Wajib, kecuali foto milik Memount."
                        value={form.data.source_url}
                        onChange={(e) => form.setData('source_url', e.target.value)}
                        error={form.errors.source_url}
                    />
                    <FormField
                        name="attribution"
                        label="Teks atribusi"
                        required
                        hint='Ringkas, seperti yang diminta lisensinya. Misalnya "Foto: Rani Prasetyo, CC BY 4.0, Wikimedia Commons".'
                        value={form.data.attribution}
                        onChange={(e) => form.setData('attribution', e.target.value)}
                        error={form.errors.attribution}
                    />
                    <TextareaField name="alt_text" label="Teks alternatif" required rows={2} value={form.data.alt_text} onChange={(e) => form.setData('alt_text', e.target.value)} error={form.errors.alt_text} />
                    <Button type="submit" processing={form.processing}>
                        {foto ? 'Ganti foto' : 'Simpan foto'}
                    </Button>
                </form>
                {dialogKonfirmasi}
            </div>
        </div>
    );
}

MountainPhoto.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;

