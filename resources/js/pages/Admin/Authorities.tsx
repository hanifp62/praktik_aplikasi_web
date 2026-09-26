import { useForm, usePage } from '@inertiajs/react';
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
import { store, update } from '@/routes/admin/authorities';
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

interface Props extends PropsBersama {
    badan: Paginasi<Badan>;
    types: Opsi[];
    mountains: Array<{ value: number; label: string }>;
}

const kosong: Isian = { name: '', type: 'NATIONAL_PARK', abbreviation: '', website: '', contact: '', jurisdiction: '', notes: '', mountain_ids: [] };

/**
 * Badan resmi dan kawasan di bawahnya (§43). Dari sinilah halaman jalur tahu nama
 * siapa yang disebut ketika datanya belum ada.
 */
export default function Authorities() {
    const { flash, badan, types, mountains } = usePage<Props>().props;
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
            </div>
        </div>
    );
}

Authorities.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
