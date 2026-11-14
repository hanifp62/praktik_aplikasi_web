import { useForm, usePage } from '@inertiajs/react';
import { useMemo, type FormEvent, type ReactNode } from 'react';
import { SelectField, TextareaField } from '@/components/discovery/Isian';
import type { DrafKurator, IsianStatus, KelompokSasaran, PilihanFormulir } from '@/components/kurator/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { FormField, InputError, Label, Select } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { desk } from '@/routes/curator';
import { store, update } from '@/routes/curator/statuses';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    title: string;
    draf: DrafKurator | null;
    sasaran: KelompokSasaran[];
    sasaranAwal: string | null;
    pilihan: PilihanFormulir;
    batasBuktiKb: number;
}

const kosong: Omit<IsianStatus, 'sasaran' | 'otoritas'> = {
    status: 'CLOSED',
    jenis_bukti: 'OFFICIAL_LETTER',
    url_sumber: '',
    waktu_tangkap: '',
    bukti: null,
    nomor_surat: '',
    tanggal_surat: '',
    mulai_berlaku: '',
    cara_berakhir: 'UNTIL_NOTICE',
    berlaku_sampai: '',
    batas: '',
    golongan_alasan: '',
    alasan: '',
    dasar_hukum: '',
    kutipan: '',
    ringkasan: '',
};

/**
 * Langkah 1 (masukan) dan 2 (penstrukturan) meja kurator (spec v3 bagian 10.7). Sasaran dan badan
 * penerbit hanya dari kawasan kurator; server memeriksa keduanya ulang, dan pencatat serta
 * keadaan tidak pernah dikirim dari sini. Menyimpan membuat draf yang hanya terbaca pencatatnya;
 * pemeriksaan konsistensi dan kiriman ada di halaman catatan.
 */
export default function StatusForm() {
    const { title, draf, sasaran, sasaranAwal, pilihan, batasBuktiKb, errors } = usePage<Props>().props;
    const awalSasaran = sasaranAwal && sasaran.some((k) => k.pilihan.some((p) => p.value === sasaranAwal)) ? sasaranAwal : '';
    const form = useForm<IsianStatus>({ sasaran: awalSasaran, otoritas: '', ...kosong, ...(draf?.isian ?? {}), bukti: null });

    const badanPilihan = useMemo(() => sasaran.find((k) => k.pilihan.some((p) => p.value === form.data.sasaran))?.badan ?? [], [sasaran, form.data.sasaran]);
    const otoritasTerpilih = badanPilihan.length === 1 ? String(badanPilihan[0]?.value) : form.data.otoritas;

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        const opsi = { forceFormData: true, preserveScroll: true };

        if (draf) {
            form.transform((d) => ({ ...d, sasaran: undefined, otoritas: undefined, _method: 'put' }));
            form.post(update.url(draf.id), opsi);
        } else {
            form.transform((d) => ({ ...d, otoritas: otoritasTerpilih }));
            form.post(store.url(), opsi);
        }
    };

    const isian = (nama: keyof Omit<IsianStatus, 'bukti'>, label: string, extra: { type?: string; hint?: string; required?: boolean } = {}) => (
        <FormField name={nama} label={label} {...extra} value={form.data[nama]} onChange={(e) => form.setData(nama, e.target.value)} error={form.errors[nama]} />
    );

    return (
        <div className="py-8">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={draf ? { href: draf.url, label: `Catatan #${draf.id}` } : { href: desk.url(), label: 'Meja kurator' }} title={title} />

                {draf ? (
                    <p className="mb-6 text-kecil text-secondary">
                        {draf.sasaran}
                        {draf.badan ? `, diterbitkan ${draf.badan}` : ''}. Sasaran dan badan tidak diubah di draf; hapus draf ini dan catat yang baru bila keduanya keliru.
                    </p>
                ) : null}

                {errors.konsistensi ? <Alert variant="danger" className="mb-4">{errors.konsistensi}</Alert> : null}

                {!draf && sasaran.length === 0 ? (
                    <EmptyState
                        jenis="menunggu"
                        title="Belum ada kawasan di badan Anda"
                        menunggu="Menunggu admin Memount mengaitkan gunung ke badan Anda."
                        description="Status hanya dicatat untuk gunung, jalur, dan segmen di kawasan badan tempat Anda menjadi anggota."
                    />
                ) : (
                    <form onSubmit={kirim} className="space-y-8" noValidate>
                        <fieldset className="space-y-4">
                            <legend className="text-bagian text-primary">1. Masukan</legend>
                            <p className="max-w-prose text-kecil text-secondary">
                                Salin dari surat, laman, atau akun resmi pengelola. Berita media hanya bukti sekunder: catatan berbukti berita menunggu kurator kedua dan tidak pernah dapat membuka jalur.
                            </p>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <SelectField name="jenis_bukti" label="Jenis bukti" required options={pilihan.jenisBukti} value={form.data.jenis_bukti} onChange={(e) => form.setData('jenis_bukti', e.target.value)} error={form.errors.jenis_bukti} />
                                {isian('url_sumber', 'Alamat sumber (opsional)', { type: 'url', hint: 'Laman atau unggahan yang Anda salin.' })}
                            </div>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                {isian('waktu_tangkap', 'Waktu Anda membaca sumbernya', { type: 'datetime-local', hint: 'Jam gunung. Kosong berarti saat disimpan.' })}
                                <div>
                                    <Label htmlFor="bukti">Salinan bukti{draf?.adaArsip ? ' (ganti)' : ''}</Label>
                                    <input
                                        id="bukti"
                                        name="bukti"
                                        type="file"
                                        accept="application/pdf,image/jpeg,image/png,image/webp"
                                        onChange={(e) => form.setData('bukti', e.target.files?.[0] ?? null)}
                                        aria-describedby={form.errors.bukti ? 'bukti-hint bukti-error' : 'bukti-hint'}
                                        aria-invalid={form.errors.bukti ? true : undefined}
                                        className="mt-1 block min-h-12 w-full rounded-tombol border border-control bg-surface px-3 py-2 text-kecil text-primary file:mr-3 file:min-h-11 file:rounded-control file:border-0 file:bg-surface-sunken file:px-3 file:font-bold file:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                    />
                                    <p id="bukti-hint" className="mt-1 text-kecil text-muted">
                                        PDF atau foto JPG, PNG, WebP, paling besar {Math.round(batasBuktiKb / 1024)} MB. Foto ditulis ulang tanpa metadata lokasi; sidik SHA-256 dicatat.
                                    </p>
                                    <InputError id="bukti-error" messages={form.errors.bukti} className="mt-2" />
                                </div>
                            </div>
                        </fieldset>

                        <fieldset className="space-y-4">
                            <legend className="text-bagian text-primary">2. Penstrukturan</legend>
                            {draf ? null : (
                                <div className="space-y-4">
                                    <div>
                                        <Label htmlFor="sasaran">Gunung, jalur, atau segmen</Label>
                                        <Select
                                            id="sasaran"
                                            name="sasaran"
                                            required
                                            value={form.data.sasaran}
                                            onChange={(e) => form.setData({ ...form.data, sasaran: e.target.value, otoritas: '' })}
                                            aria-describedby={form.errors.sasaran ? 'sasaran-error' : undefined}
                                            aria-invalid={form.errors.sasaran ? true : undefined}
                                        >
                                            <option value="">Pilih sasaran</option>
                                            {sasaran.map((k) => (
                                                <optgroup key={k.gunung} label={k.gunung}>
                                                    {k.pilihan.map((p) => (
                                                        <option key={p.value} value={p.value}>
                                                            {p.label}
                                                        </option>
                                                    ))}
                                                </optgroup>
                                            ))}
                                        </Select>
                                        <InputError id="sasaran-error" messages={form.errors.sasaran} className="mt-2" />
                                    </div>
                                    <SelectField
                                        name="otoritas"
                                        label="Badan penerbit"
                                        required
                                        placeholder={form.data.sasaran ? 'Pilih badan' : 'Pilih sasaran lebih dulu'}
                                        options={badanPilihan.map((b) => ({ value: String(b.value), label: b.label }))}
                                        value={otoritasTerpilih}
                                        onChange={(e) => form.setData('otoritas', e.target.value)}
                                        error={form.errors.otoritas}
                                        disabled={badanPilihan.length === 0}
                                    />
                                </div>
                            )}
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                {isian('nomor_surat', 'Nomor surat atau pengumuman')}
                                {isian('tanggal_surat', 'Tanggal surat', { type: 'date' })}
                            </div>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <SelectField name="status" label="Status akses" required options={pilihan.status} value={form.data.status} onChange={(e) => form.setData('status', e.target.value)} error={form.errors.status} />
                                {isian('batas', 'Batas pembukaan atau penutupan (opsional)', { hint: 'Misalnya "sampai Ranu Kumbolo".' })}
                            </div>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                {isian('mulai_berlaku', 'Mulai berlaku', { type: 'date', required: true })}
                                <SelectField
                                    name="cara_berakhir"
                                    label="Cara berakhir"
                                    required
                                    options={pilihan.caraBerakhir}
                                    value={form.data.cara_berakhir}
                                    onChange={(e) => form.setData({ ...form.data, cara_berakhir: e.target.value, berlaku_sampai: e.target.value === 'ON_DATE' ? form.data.berlaku_sampai : '' })}
                                    error={form.errors.cara_berakhir}
                                />
                                {form.data.cara_berakhir === 'ON_DATE' ? isian('berlaku_sampai', 'Berlaku sampai', { type: 'date', required: true }) : null}
                            </div>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <SelectField name="golongan_alasan" label="Golongan alasan" placeholder="Tidak disebut" options={pilihan.golonganAlasan} value={form.data.golongan_alasan} onChange={(e) => form.setData('golongan_alasan', e.target.value)} error={form.errors.golongan_alasan} />
                                {isian('dasar_hukum', 'Dasar hukum (opsional)')}
                            </div>
                            <TextareaField name="alasan" label="Alasan menurut pengelola (opsional)" rows={2} value={form.data.alasan} onChange={(e) => form.setData('alasan', e.target.value)} error={form.errors.alasan} />
                            <TextareaField name="kutipan" label="Kutipan resmi singkat (opsional)" rows={3} value={form.data.kutipan} onChange={(e) => form.setData('kutipan', e.target.value)} error={form.errors.kutipan} />
                            <TextareaField
                                name="ringkasan"
                                label="Ringkasan untuk pendaki"
                                required
                                rows={2}
                                value={form.data.ringkasan}
                                onChange={(e) => form.setData('ringkasan', e.target.value)}
                                error={form.errors.ringkasan}
                            />
                        </fieldset>

                        <div className="flex flex-wrap gap-3">
                            <Button type="submit" processing={form.processing}>
                                Simpan draf
                            </Button>
                            <Button variant="secondary" href={draf ? draf.url : desk.url()}>
                                Batal
                            </Button>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
}

StatusForm.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
