import { router, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent, type ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import { BarisKosong, BingkaiTabel, type Paginasi } from '@/components/admin/Tabel';
import { CheckboxGroup, SelectField, TextareaField, type Opsi } from '@/components/discovery/Isian';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { FormField } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
import { store, update } from '@/routes/admin/authorities';
import { destroy as membersDestroy, store as membersStore } from '@/routes/admin/authorities/members';
import type { PropsBersama } from '@/types';

interface Isian {
    name: string;
    type: string;
    abbreviation: string;
    website: string;
    contact: string;
    jurisdiction: string;
    notes: string;
    mountain_ids: string[];
}

interface Badan {
    id: number;
    nama: string;
    jenis: string;
    wilayah: string;
    jumlahKawasan: number;
    belumDikaitkan: boolean;
    isian: { [K in keyof Isian]: K extends 'mountain_ids' ? number[] : string | null };
}

interface Anggota {
    id: number;
    badanId: number;
    nama: string;
    email: string;
    badan: string;
    peran: string;
    surat: string;
    berlaku: string;
}

interface IsianAnggota {
    badan: string;
    email: string;
    peran: string;
    surat: string;
    berlaku_mulai: string;
    berlaku_sampai: string;
}

interface Props extends PropsBersama {
    badan: Paginasi<Badan>;
    types: Opsi[];
    mountains: Array<{ value: number; label: string }>;
    anggota: Anggota[];
    badanBerwenang: Array<{ value: number; label: string }>;
    peranAnggota: Opsi[];
}

const kosong: Isian = { name: '', type: 'NATIONAL_PARK', abbreviation: '', website: '', contact: '', jurisdiction: '', notes: '', mountain_ids: [] };

/**
 * Badan resmi dan kawasan di bawahnya (§43). Dari sinilah halaman jalur tahu nama
 * siapa yang disebut ketika datanya belum ada.
 */
export default function Authorities() {
    const { flash, badan, types, mountains, anggota, badanBerwenang, peranAnggota } = usePage<Props>().props;
    const [terbuka, setTerbuka] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const form = useForm<Isian>(kosong);

    const tambah = () => {
        setEditingId(null);
        form.clearErrors();
        form.setData(kosong);
        setTerbuka(true);
    };

    const ubah = (b: Badan) => {
        setEditingId(b.id);
        form.clearErrors();
        form.setData({
            name: b.isian.name ?? '',
            type: b.isian.type ?? 'NATIONAL_PARK',
            abbreviation: b.isian.abbreviation ?? '',
            website: b.isian.website ?? '',
            contact: b.isian.contact ?? '',
            jurisdiction: b.isian.jurisdiction ?? '',
            notes: b.isian.notes ?? '',
            mountain_ids: b.isian.mountain_ids.map(String),
        });
        setTerbuka(true);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        const opsi = { preserveScroll: true, onSuccess: () => setTerbuka(false) };
        if (editingId) {
            form.put(update.url(editingId), opsi);
        } else {
            form.post(store.url(), opsi);
        }
    };

    const isian = (nama: Exclude<keyof Isian, 'mountain_ids' | 'type' | 'notes'>, label: string, extra: { type?: string; hint?: string } = {}) => (
        <FormField name={nama} label={label} {...extra} value={form.data[nama]} onChange={(e) => form.setData(nama, e.target.value)} error={form.errors[nama]} />
    );

    return (
        <div className="py-8">
            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
                    title="Kelola Badan Resmi"
                    description="Menentukan siapa yang berwenang atas sebuah kawasan. Dari sinilah halaman jalur tahu nama siapa yang disebut ketika datanya belum ada."
                />

                <AdminNav />

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}

                <div className="mb-4">
                    <Button onClick={tambah}>Tambah badan resmi</Button>
                </div>

                {terbuka ? (
                    <Card className="mb-6" title={editingId ? 'Ubah badan resmi' : 'Tambah badan resmi'}>
                        <form onSubmit={kirim} className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                {isian('name', 'Nama resmi')}
                                <SelectField
                                    name="type"
                                    label="Jenis"
                                    required
                                    placeholder="Pilih jenis"
                                    options={types}
                                    value={form.data.type}
                                    onChange={(e) => form.setData('type', e.target.value)}
                                    error={form.errors.type}
                                />
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                {isian('abbreviation', 'Singkatan')}
                                {isian('jurisdiction', 'Wilayah kewenangan')}
                                {isian('contact', 'Kontak')}
                            </div>

                            {isian('website', 'Situs resmi', { type: 'url', hint: 'Dipakai pada halaman jalur agar pendaki dapat memeriksa langsung ke sumbernya.' })}

                            {/* Lembaga sertifikasi dan asosiasi profesi biasanya tidak perlu dikaitkan ke gunung:
                                mereka mengesahkan orang, bukan menyatakan keadaan jalur. Pengaitannya tetap
                                diizinkan karena balai taman nasional juga menyelenggarakan sertifikasi. */}
                            <CheckboxGroup
                                name="mountain_ids"
                                label="Kawasan yang berada di bawahnya"
                                options={mountains.map((m) => ({ value: String(m.value), label: m.label }))}
                                value={form.data.mountain_ids}
                                onChange={(v) => form.setData('mountain_ids', v)}
                                error={form.errors.mountain_ids}
                            />

                            <TextareaField name="notes" label="Catatan" rows={2} value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} error={form.errors.notes} />

                            <div className="flex gap-3">
                                <Button type="submit" processing={form.processing}>Simpan</Button>
                                <Button variant="secondary" onClick={() => setTerbuka(false)}>Batal</Button>
                            </div>
                        </form>
                    </Card>
                ) : null}

                <BingkaiTabel keterangan="Daftar badan resmi" kolom={['Nama', 'Jenis', 'Wilayah', 'Kawasan', 'Aksi']}>
                    {badan.data.length === 0 ? (
                        <BarisKosong
                            kolom={5}
                            judul="Belum ada badan otoritas tercatat"
                            penjelasan="Tanpa badan otoritas, tidak ada status resmi yang dapat diterbitkan, dan seluruh keterangan jalur akan bersumber dari komunitas saja."
                        />
                    ) : (
                        badan.data.map((b) => (
                            <tr key={b.id} className="border-b border-subtle">
                                <td className="p-4 font-medium text-primary">{b.nama}</td>
                                <td className="p-4 text-secondary">{b.jenis}</td>
                                <td className="p-4 text-secondary">{b.wilayah}</td>
                                <td className="p-4 text-secondary">
                                    {b.jumlahKawasan}
                                    {b.belumDikaitkan ? <span className="ml-1 text-xs text-warn-900">(belum dikaitkan)</span> : null}
                                </td>
                                <td className="p-4">
                                    <Button variant="secondary" size="sm" onClick={() => ubah(b)} aria-label={`Ubah ${b.nama}`}>Ubah</Button>
                                </td>
                            </tr>
                        ))
                    )}
                </BingkaiTabel>

                <div className="mt-6">
                    <Pagination links={badan.links} />
                </div>

                <AnggotaKurator anggota={anggota} badanBerwenang={badanBerwenang} peranAnggota={peranAnggota} />
            </div>
        </div>
    );
}

/**
 * Anggota meja kurator (W8, ruling W8-R7). Kurator terpisah dari moderator dan admin, dan hanya
 * mencatat status di kawasan badannya. Peran diberikan di sini oleh admin, tidak pernah dari isian
 * penggunanya sendiri, dan setiap pemberian serta pencabutan masuk jejak audit.
 */
function AnggotaKurator({ anggota, badanBerwenang, peranAnggota }: Pick<Props, 'anggota' | 'badanBerwenang' | 'peranAnggota'>) {
    const form = useForm<IsianAnggota>({ badan: '', email: '', peran: 'KURATOR', surat: '', berlaku_mulai: '', berlaku_sampai: '' });

    const tambah = (e: FormEvent) => {
        e.preventDefault();
        if (form.data.badan === '') {
            form.setError('badan', 'Pilih badan resmi.');
            return;
        }
        form.post(membersStore.url(Number(form.data.badan)), { preserveScroll: true, onSuccess: () => form.reset('email', 'surat', 'berlaku_mulai', 'berlaku_sampai') });
    };

    return (
        <section aria-labelledby="judul-anggota" className="mt-10">
            <h2 id="judul-anggota" className="text-bagian text-primary">Anggota meja kurator</h2>
            <p className="mt-1 max-w-prose text-kecil text-secondary">
                Kurator menyalin status dari sumber resmi badan ini ke meja kurator, hanya untuk kawasan badannya. Pembatasan boleh terbit oleh satu kurator dengan bukti primer; pembukaan wajib diperiksa kurator kedua. Peran kurator terpisah dari moderator dan admin.
            </p>

            <Card className="mt-4" title="Tambah anggota">
                <form onSubmit={tambah} className="space-y-4">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <SelectField
                            name="badan"
                            label="Badan resmi"
                            required
                            placeholder="Pilih badan"
                            options={badanBerwenang.map((b) => ({ value: String(b.value), label: b.label }))}
                            value={form.data.badan}
                            onChange={(e) => form.setData('badan', e.target.value)}
                            error={form.errors.badan}
                        />
                        <FormField name="email" type="email" label="Surel akun pendaki" required value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} error={form.errors.email} />
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <SelectField name="peran" label="Peran" required options={peranAnggota} value={form.data.peran} onChange={(e) => form.setData('peran', e.target.value)} error={form.errors.peran} />
                        <FormField name="surat" label="Surat penunjukan" required hint="Nomor atau keterangan surat tugasnya." value={form.data.surat} onChange={(e) => form.setData('surat', e.target.value)} error={form.errors.surat} />
                    </div>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <FormField name="berlaku_mulai" type="date" label="Berlaku mulai" required value={form.data.berlaku_mulai} onChange={(e) => form.setData('berlaku_mulai', e.target.value)} error={form.errors.berlaku_mulai} />
                        <FormField name="berlaku_sampai" type="date" label="Berlaku sampai (opsional)" value={form.data.berlaku_sampai} onChange={(e) => form.setData('berlaku_sampai', e.target.value)} error={form.errors.berlaku_sampai} />
                    </div>
                    <Button type="submit" processing={form.processing}>Tambah anggota</Button>
                </form>
            </Card>

            <div className="mt-6">
                <BingkaiTabel keterangan="Anggota meja kurator yang aktif" kolom={['Nama', 'Badan', 'Peran', 'Berlaku', 'Aksi']}>
                    {anggota.length === 0 ? (
                        <BarisKosong
                            kolom={5}
                            judul="Belum ada kurator"
                            penjelasan="Tanpa kurator, tidak ada yang dapat mencatat status resmi baru. Status lama yang kedaluwarsa tampil sebagai belum diketahui."
                        />
                    ) : (
                        anggota.map((a) => (
                            <tr key={a.id} className="border-b border-subtle align-top">
                                <td className="p-4">
                                    <span className="block font-medium text-primary">{a.nama}</span>
                                    <span className="block break-all text-meta text-muted">{a.email}</span>
                                </td>
                                <td className="p-4 text-secondary">{a.badan}</td>
                                <td className="p-4 text-secondary">
                                    {a.peran}
                                    <span className="block text-meta text-muted">{a.surat}</span>
                                </td>
                                <td className="p-4 text-secondary">{a.berlaku}</td>
                                <td className="p-4">
                                    <Button
                                        variant="secondary"
                                        size="sm"
                                        onClick={() => router.delete(membersDestroy.url({ authority: a.badanId, member: a.id }), { preserveScroll: true })}
                                        aria-label={`Cabut keanggotaan ${a.nama} di ${a.badan}`}
                                    >
                                        Cabut
                                    </Button>
                                </td>
                            </tr>
                        ))
                    )}
                </BingkaiTabel>
            </div>
        </section>
    );
}

Authorities.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
