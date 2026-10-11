import { router, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent, type ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import { BingkaiTabel, type Paginasi } from '@/components/admin/Tabel';
import { SelectField, TextareaField } from '@/components/discovery/Isian';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { Checkbox, FormField } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
import { destroy, store, update } from '@/routes/admin/permits';
import type { PropsBersama } from '@/types';

type Angka = number | string | null;

interface Isian {
    scope: 'mountain' | 'trail';
    trail_id: Angka;
    mountain_id: Angka;
    authority: string;
    booking_url: string | null;
    daily_quota: Angka;
    booking_opens_days_before: Angka;
    booking_closes_days_before: Angka;
    guide_required: boolean;
    max_duration_days: Angka;
    notes: string | null;
    source: string | null;
    source_url: string | null;
}

interface Aturan {
    id: number;
    sasaran: string;
    sasaranKeterangan: string;
    authority: string;
    ringkasan: string;
    diverifikasi: string;
    sumber: string;
    isian: Isian;
}

interface Props extends PropsBersama {
    rules: Paginasi<Aturan>;
    trails: Array<{ value: number; label: string }>;
    mountains: Array<{ value: number; label: string }>;
}

const kosong: Isian = {
    scope: 'mountain',
    trail_id: '',
    mountain_id: '',
    authority: '',
    booking_url: '',
    daily_quota: '',
    booking_opens_days_before: '',
    booking_closes_days_before: '',
    guide_required: false,
    max_duration_days: '',
    notes: '',
    source: '',
    source_url: '',
};

const teks = (v: Angka | string | null) => (v === null || v === undefined ? '' : String(v));

/**
 * Pengelolaan aturan perizinan pendakian (§36, §60, §61). Yang dikelola adalah catatan
 * tentang aturan pihak lain: sistem tidak memesan izin dan tidak melacak kuota.
 */
export default function Permits() {
    const { flash, rules, trails, mountains } = usePage<Props>().props;
    const [editingId, setEditingId] = useState<number | null>(null);
    const form = useForm<Isian>(kosong);

    const batal = () => {
        setEditingId(null);
        form.setData(kosong);
        form.clearErrors();
    };

    const ubah = (aturan: Aturan) => {
        setEditingId(aturan.id);
        form.clearErrors();
        form.setData({
            ...aturan.isian,
            trail_id: teks(aturan.isian.trail_id),
            mountain_id: teks(aturan.isian.mountain_id),
            daily_quota: teks(aturan.isian.daily_quota),
            booking_opens_days_before: teks(aturan.isian.booking_opens_days_before),
            booking_closes_days_before: teks(aturan.isian.booking_closes_days_before),
            max_duration_days: teks(aturan.isian.max_duration_days),
            booking_url: aturan.isian.booking_url ?? '',
            notes: aturan.isian.notes ?? '',
            source: aturan.isian.source ?? '',
            source_url: aturan.isian.source_url ?? '',
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        const opsi = { preserveScroll: true, onSuccess: batal };
        if (editingId) {
            form.put(update.url(editingId), opsi);
        } else {
            form.post(store.url(), opsi);
        }
    };

    const hapus = (id: number) => {
        if (!window.confirm('Hapus aturan perizinan ini?')) {
            return;
        }
        router.delete(destroy.url(id), { preserveScroll: true, onSuccess: () => (editingId === id ? batal() : undefined) });
    };

    const angka = (nama: keyof Isian, label: string) => (
        <FormField
            name={nama}
            type="number"
            label={label}
            value={teks(form.data[nama] as Angka)}
            onChange={(e) => form.setData(nama, e.target.value)}
            error={form.errors[nama]}
        />
    );

    return (
        <div className="py-8">
            <div className="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
                    title="Kelola Perizinan"
                    description="Catatan aturan pengelola. Sistem tidak memesan izin dan tidak melacak kuota; yang disimpan di sini hanya apa yang perlu diurus pendaki dan ke mana."
                />

                <AdminNav />

                {flash.status ? <Alert variant="success">{flash.status}</Alert> : null}

                <Card title={editingId ? 'Ubah aturan perizinan' : 'Catat aturan perizinan'}>
                    <form onSubmit={kirim} className="space-y-4">
                        <fieldset>
                            <legend className="block text-sm font-medium text-secondary">Aturan berlaku untuk</legend>
                            <div className="mt-2 flex flex-wrap gap-4">
                                <label htmlFor="scope-mountain" className="flex min-h-11 items-center gap-2 text-sm text-secondary">
                                    <input
                                        id="scope-mountain"
                                        type="radio"
                                        name="scope"
                                        value="mountain"
                                        checked={form.data.scope === 'mountain'}
                                        onChange={() => form.setData('scope', 'mountain')}
                                        className="border-control text-brand-ink focus:ring-brand-600"
                                    />
                                    Seluruh gunung
                                </label>
                                <label htmlFor="scope-trail" className="flex min-h-11 items-center gap-2 text-sm text-secondary">
                                    <input
                                        id="scope-trail"
                                        type="radio"
                                        name="scope"
                                        value="trail"
                                        checked={form.data.scope === 'trail'}
                                        onChange={() => form.setData('scope', 'trail')}
                                        className="border-control text-brand-ink focus:ring-brand-600"
                                    />
                                    Satu jalur tertentu
                                </label>
                            </div>
                        </fieldset>

                        {form.data.scope === 'mountain' ? (
                            <SelectField
                                name="mountain_id"
                                label="Gunung"
                                placeholder="Pilih gunung"
                                options={mountains.map((m) => ({ value: String(m.value), label: m.label }))}
                                value={teks(form.data.mountain_id)}
                                onChange={(e) => form.setData('mountain_id', e.target.value)}
                                error={form.errors.mountain_id}
                            />
                        ) : (
                            <SelectField
                                name="trail_id"
                                label="Jalur"
                                placeholder="Pilih jalur"
                                options={trails.map((t) => ({ value: String(t.value), label: t.label }))}
                                value={teks(form.data.trail_id)}
                                onChange={(e) => form.setData('trail_id', e.target.value)}
                                error={form.errors.trail_id}
                            />
                        )}

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <FormField name="authority" label="Penyelenggara" placeholder="TN Bromo Tengger Semeru" value={form.data.authority} onChange={(e) => form.setData('authority', e.target.value)} error={form.errors.authority} />
                            <FormField name="booking_url" label="URL pemesanan resmi" placeholder="https://" value={form.data.booking_url ?? ''} onChange={(e) => form.setData('booking_url', e.target.value)} error={form.errors.booking_url} />
                            {angka('daily_quota', 'Kuota harian (orang)')}
                            {angka('max_duration_days', 'Durasi maksimum (hari)')}
                            {angka('booking_opens_days_before', 'Pemesanan dibuka H-')}
                            {angka('booking_closes_days_before', 'Pemesanan ditutup H-')}
                        </div>

                        <label htmlFor="guide_required" className="flex min-h-11 items-center gap-2 text-sm text-secondary">
                            <Checkbox id="guide_required" checked={form.data.guide_required} onChange={(e) => form.setData('guide_required', e.target.checked)} />
                            Wajib didampingi pemandu terdaftar
                        </label>

                        <TextareaField name="notes" label="Catatan tambahan" value={form.data.notes ?? ''} onChange={(e) => form.setData('notes', e.target.value)} error={form.errors.notes} />

                        {/* PRD §60: data penting membawa sumbernya. */}
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <FormField name="source" label="Sumber informasi" placeholder="Situs resmi pengelola" value={form.data.source ?? ''} onChange={(e) => form.setData('source', e.target.value)} error={form.errors.source} />
                            <FormField name="source_url" label="URL sumber" placeholder="https://" value={form.data.source_url ?? ''} onChange={(e) => form.setData('source_url', e.target.value)} error={form.errors.source_url} />
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <Button type="submit" processing={form.processing}>{editingId ? 'Simpan perubahan' : 'Catat aturan'}</Button>
                            {editingId ? <Button variant="secondary" onClick={batal}>Batal</Button> : null}
                        </div>
                    </form>
                </Card>

                {rules.data.length === 0 ? (
                    <EmptyState
                        jenis="pertama"
                        title="Belum ada aturan perizinan tercatat"
                        description="Jalur tanpa aturan tercatat tidak akan memunculkan peringatan perizinan apa pun. Ketiadaan data tidak dianggap sebagai masalah."
                    />
                ) : (
                    <Card title="Aturan tercatat">
                        <BingkaiTabel keterangan="Daftar aturan perizinan pendakian" kolom={['Berlaku untuk', 'Penyelenggara', 'Ketentuan', 'Diverifikasi', 'Tindakan']}>
                            {rules.data.map((r) => (
                                <tr key={r.id} className="border-t border-subtle align-top">
                                    <td className="p-4">
                                        {r.sasaran}
                                        <span className="block text-xs text-muted">{r.sasaranKeterangan}</span>
                                    </td>
                                    <td className="p-4">{r.authority}</td>
                                    <td className="p-4 text-secondary">{r.ringkasan}</td>
                                    <td className="p-4 text-secondary">
                                        {r.diverifikasi}
                                        <span className="block text-xs text-muted">{r.sumber}</span>
                                    </td>
                                    <td className="p-4">
                                        <div className="flex flex-wrap gap-2">
                                            <Button variant="secondary" size="sm" onClick={() => ubah(r)} aria-label={`Ubah aturan ${r.sasaran}`}>Ubah</Button>
                                            <Button variant="danger" size="sm" onClick={() => hapus(r.id)} aria-label={`Hapus aturan ${r.sasaran}`}>Hapus</Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </BingkaiTabel>
                        <div className="mt-4">
                            <Pagination links={rules.links} />
                        </div>
                    </Card>
                )}
            </div>
        </div>
    );
}

Permits.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
