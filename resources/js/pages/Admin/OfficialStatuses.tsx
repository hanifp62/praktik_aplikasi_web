import { useForm, usePage } from '@inertiajs/react';
import { useRef, type FormEvent, type ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import { BarisKosong, BingkaiTabel, type Paginasi } from '@/components/admin/Tabel';
import { SelectField, TextareaField, type Opsi } from '@/components/discovery/Isian';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { FormField } from '@/components/ui/Field';
import { Kesegaran } from '@/components/trails/Kesegaran';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { StatusAkses, type StatusResmi } from '@/components/trails/StatusAkses';
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
import { store } from '@/routes/admin/statuses';
import type { PropsBersama } from '@/types';

interface ItemTinjau {
    id: number;
    nama: string;
    status: string;
    statusText: string;
    keterangan: string;
    scope: string;
    statusableId: string;
}

interface BarisStatus {
    id: number;
    objek: string;
    cakupan: string;
    status: string;
    statusText: string;
    sumber: string;
    sumberUrl: string | null;
    diverifikasi: string | null;
    berlaku: string;
    dicatatOleh: string;
}

interface Props extends PropsBersama {
    scopes: Opsi[];
    statuses: Opsi[];
    sources: Array<{ value: number; label: string }>;
    targets: Record<string, Array<{ value: number; label: string }>>;
    tinjau: { kedaluwarsa: ItemTinjau[]; segera: ItemTinjau[]; basi: ItemTinjau[] };
    records: Paginasi<BarisStatus>;
}

const kategoriTinjau = [
    ['kedaluwarsa', 'Sudah kedaluwarsa', 'Jalur ini sekarang berjalan tanpa status resmi dan ditampilkan sebagai belum diketahui.'],
    ['segera', 'Akan segera kedaluwarsa', 'Masih sempat diperpanjang sebelum statusnya hilang.'],
    ['basi', 'Lama tidak diverifikasi', 'Catatannya masih berlaku, tetapi belum dipastikan ulang ke pengelola.'],
] as const;

/** UNKNOWN tidak punya warna operasional: StatusAkses menggambarnya lingkaran putus-putus. */
const badge = (status: string): StatusResmi => (status === 'OPEN' || status === 'RESTRICTED' || status === 'CLOSED' ? status : null);

const kosongkan = ['statusable_id', 'source', 'source_url', 'published_at', 'effective_at', 'expires_at', 'reason', 'notes'] as const;

/**
 * FR-11 status resmi. Status melekat pada gunung, jalur, atau segmen (§42) dan setiap
 * catatan membawa sumber dan waktunya (§41). Yang perlu ditinjau diletakkan sebelum
 * formulir: status yang kedaluwarsa membuat jalurnya berjalan tanpa status resmi, dan
 * §95 membuat kemundurannya senyap.
 */
export default function OfficialStatuses() {
    const { flash, scopes, statuses, sources, targets, tinjau, records } = usePage<Props>().props;
    const formulir = useRef<HTMLDivElement>(null);
    const form = useForm({
        scope: 'TRAIL',
        statusable_id: '',
        status: 'UNKNOWN',
        data_source_id: '',
        source: '',
        source_url: '',
        published_at: '',
        effective_at: '',
        expires_at: '',
        reason: '',
        notes: '',
    });

    const adaTinjauan = tinjau.kedaluwarsa.length + tinjau.segera.length + tinjau.basi.length > 0;
    const opsiSasaran = (targets[form.data.scope] ?? []).map((t) => ({ value: String(t.value), label: t.label }));

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.post(store.url(), { preserveScroll: true, onSuccess: () => form.reset(...kosongkan) });
    };

    // Temuan dapat langsung ditindak: form terisi sasaran yang sama, tinggal status dan sumbernya.
    const catatUlang = (item: ItemTinjau) => {
        form.setData({ ...form.data, scope: item.scope, statusable_id: item.statusableId });
        formulir.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        formulir.current?.querySelector<HTMLSelectElement>('#status')?.focus({ preventScroll: true });
    };

    return (
        <div className="py-8">
            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
                    title="Kelola Status Resmi"
                    description="Status dapat melekat pada gunung, jalur, atau segmen. Status gunung yang terbuka tidak otomatis berarti semua jalurnya terbuka."
                />

                <AdminNav />

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}

                {adaTinjauan ? (
                    <Card className="mb-6" title="Perlu ditinjau">
                        <div className="space-y-5">
                            {kategoriTinjau.map(([kunci, judul, penjelasan]) =>
                                tinjau[kunci].length > 0 ? (
                                    <div key={kunci}>
                                        <h3 className="text-sm font-semibold text-primary">{judul} ({tinjau[kunci].length})</h3>
                                        <p className="mt-0.5 text-xs text-secondary">{penjelasan}</p>
                                        <ul className="mt-2 space-y-1 text-sm">
                                            {tinjau[kunci].map((item) => (
                                                <li key={item.id} className="flex flex-wrap items-center gap-x-2 gap-y-1 text-secondary">
                                                    <span className="font-medium text-primary">{item.nama}</span>
                                                    <StatusAkses nilai={badge(item.status)} label={item.statusText} />
                                                    <span className="text-xs text-secondary">{item.keterangan}</span>
                                                    <Button variant="ghost" size="sm" onClick={() => catatUlang(item)} aria-label={`Catat ulang status ${item.nama}`}>
                                                        Catat ulang
                                                    </Button>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                ) : null,
                            )}
                        </div>
                    </Card>
                ) : null}

                <div ref={formulir} className="scroll-mt-4">
                    <Card className="mb-6" title="Catat status resmi">
                        <form onSubmit={kirim} className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <SelectField
                                    name="scope"
                                    label="Cakupan"
                                    required
                                    placeholder="Pilih cakupan"
                                    options={scopes}
                                    value={form.data.scope}
                                    onChange={(e) => form.setData({ ...form.data, scope: e.target.value, statusable_id: '' })}
                                    error={form.errors.scope}
                                />
                                <SelectField
                                    name="statusable_id"
                                    label="Objek"
                                    required
                                    placeholder="Pilih objek"
                                    options={opsiSasaran}
                                    value={form.data.statusable_id}
                                    onChange={(e) => form.setData('statusable_id', e.target.value)}
                                    error={form.errors.statusable_id}
                                />
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <SelectField
                                    name="status"
                                    label="Status"
                                    required
                                    placeholder="Pilih status"
                                    options={statuses}
                                    value={form.data.status}
                                    onChange={(e) => form.setData('status', e.target.value)}
                                    error={form.errors.status}
                                />
                                <SelectField
                                    name="data_source_id"
                                    label="Sumber data terdaftar"
                                    placeholder="Belum ditentukan"
                                    options={sources.map((s) => ({ value: String(s.value), label: s.label }))}
                                    value={form.data.data_source_id}
                                    onChange={(e) => form.setData('data_source_id', e.target.value)}
                                    error={form.errors.data_source_id}
                                />
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <FormField name="source" label="Nama sumber (teks bebas)" value={form.data.source} onChange={(e) => form.setData('source', e.target.value)} error={form.errors.source} />
                                <FormField name="source_url" type="url" label="URL sumber" value={form.data.source_url} onChange={(e) => form.setData('source_url', e.target.value)} error={form.errors.source_url} />
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <FormField name="published_at" type="date" label="Tanggal publikasi sumber" value={form.data.published_at} onChange={(e) => form.setData('published_at', e.target.value)} error={form.errors.published_at} />
                                <FormField name="effective_at" type="date" label="Berlaku mulai" value={form.data.effective_at} onChange={(e) => form.setData('effective_at', e.target.value)} error={form.errors.effective_at} />
                                <FormField name="expires_at" type="date" label="Berlaku sampai (opsional)" value={form.data.expires_at} onChange={(e) => form.setData('expires_at', e.target.value)} error={form.errors.expires_at} />
                            </div>

                            <FormField name="reason" label="Alasan" value={form.data.reason} onChange={(e) => form.setData('reason', e.target.value)} error={form.errors.reason} />

                            <TextareaField name="notes" label="Catatan" rows={2} value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} error={form.errors.notes} />

                            <Button type="submit" processing={form.processing}>Simpan status</Button>
                        </form>
                    </Card>
                </div>

                <BingkaiTabel keterangan="Riwayat status resmi" kolom={['Objek', 'Cakupan', 'Status', 'Sumber', 'Berlaku', 'Dicatat oleh']}>
                    {records.data.length === 0 ? (
                        <BarisKosong
                            kolom={6}
                            judul="Belum ada status resmi tercatat"
                            penjelasan="Jalur tanpa status resmi bukan berarti jalur terbuka. Sistem menampilkannya sebagai belum diketahui, dan itu memang keadaan yang sebenarnya."
                        />
                    ) : (
                        records.data.map((r) => (
                            <tr key={r.id} className="border-b border-subtle align-top">
                                <td className="p-4 font-medium text-primary">{r.objek}</td>
                                <td className="p-4 text-secondary">{r.cakupan}</td>
                                <td className="p-4"><StatusAkses nilai={badge(r.status)} label={r.statusText} /></td>
                                <td className="p-4 text-secondary">
                                    {r.sumberUrl ? (
                                        <a href={r.sumberUrl} rel="noopener noreferrer" target="_blank" className="text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600">
                                            {r.sumber}
                                            <span className="sr-only"> (membuka tab baru)</span>
                                        </a>
                                    ) : (
                                        r.sumber
                                    )}
                                    <Kesegaran waktu={r.diverifikasi} awalan="Diverifikasi" />
                                </td>
                                <td className="p-4 text-secondary">{r.berlaku}</td>
                                <td className="p-4 text-secondary">{r.dicatatOleh}</td>
                            </tr>
                        ))
                    )}
                </BingkaiTabel>

                <div className="mt-6">
                    <Pagination links={records.links} />
                </div>
            </div>
        </div>
    );
}

OfficialStatuses.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
