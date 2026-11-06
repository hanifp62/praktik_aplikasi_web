import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import type { Paginasi } from '@/components/admin/Tabel';
import { SelectField, TextareaField, type Opsi } from '@/components/discovery/Isian';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { FormField, InputError, Select, TextInput } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
import { store } from '@/routes/admin/usability';
import type { PropsBersama } from '@/types';

interface Ringkasan {
    pesertaTugas: number;
    cakupanMasalah: string;
    pesertaKurang: number;
    susRata: string | null;
    susGrade: string | null;
    susDapatDiandalkan: boolean;
    respondenSus: number;
    respondenSusKurang: number;
    sesiPembacaLayar: number;
    tugas: Array<{ kode: string; judul: string; inti: boolean; diamati: number; gagal: number; tingkatBerhasil: string }>;
}

interface Props extends PropsBersama {
    ringkasan: Ringkasan;
    daftarTugas: Array<{ kode: string; judul: string; berhasil: string }>;
    pernyataanSus: Array<{ nomor: number; pernyataan: string }>;
    hasilTugas: Opsi[];
    jenis: Opsi[];
    tingkat: Opsi[];
    kodeBerikutnya: string;
    sesi: Paginasi<{ id: number; kode: string; jenis: string; fasilitator: string | null; sus: string; waktu: string }>;
}

type HasilTugas = { outcome: string; seconds: string; note: string };

const tugasKosong: HasilTugas = { outcome: '', seconds: '', note: '' };

interface Isian {
    participant_code: string;
    kind: string;
    experience_level: string;
    notes: string;
    tasks: Record<string, HasilTugas>;
    sus: Record<number, string>;
}

const isianAwal = (kode: string, tugas: Props['daftarTugas'], sus: Props['pernyataanSus']): Isian => ({
    participant_code: kode,
    kind: 'TASK',
    experience_level: '',
    notes: '',
    tasks: Object.fromEntries(tugas.map((t) => [t.kode, { ...tugasKosong }])),
    sus: Object.fromEntries(sus.map((p) => [p.nomor, ''])),
});

const kontrolKecil = 'text-sm';

/**
 * Pencatat studi kegunaan. Ringkasan di atas formulir supaya yang pertama terbaca
 * adalah posisi studi saat ini: fasilitator yang tahu tinggal berapa peserta lagi
 * mengambil keputusan berbeda. Skor dan ambangnya dihitung server
 * (UsabilityStudyService); halaman ini hanya menampilkannya.
 */
export default function Usability() {
    const { flash, ringkasan, daftarTugas, pernyataanSus, hasilTugas, jenis, tingkat, kodeBerikutnya, sesi } = usePage<Props>().props;
    const form = useForm<Isian>(isianAwal(kodeBerikutnya, daftarTugas, pernyataanSus));
    const galat = form.errors as Record<string, string | undefined>;

    const ubahTugas = (kode: string, kunci: keyof HasilTugas, nilai: string) =>
        form.setData('tasks', { ...form.data.tasks, [kode]: { ...(form.data.tasks[kode] ?? tugasKosong), [kunci]: nilai } });

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: (halaman) => {
                const baru = (halaman.props as unknown as Props).kodeBerikutnya;
                form.setData(isianAwal(baru, daftarTugas, pernyataanSus));
            },
        });
    };

    return (
        <div className="py-8">
            <div className="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
                    title="Studi Kegunaan"
                    description="Bukti dari manusia adalah satu-satunya yang tidak dapat dihasilkan sistem sendiri. Halaman ini mengumpulkannya dan menghitungnya."
                />

                <AdminNav />

                {flash.status ? <Alert variant="success">{flash.status}</Alert> : null}

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <Card title="Peserta uji tugas">
                        <p className="text-2xl font-semibold text-primary">{ringkasan.pesertaTugas}</p>
                        <p className="mt-1 text-sm text-secondary">
                            Perkiraan masalah kegunaan yang tertemukan: <span className="font-medium text-primary">{ringkasan.cakupanMasalah}</span>
                        </p>
                        {ringkasan.pesertaKurang > 0 ? (
                            <p className="mt-2 text-sm text-warn-900">{ringkasan.pesertaKurang} peserta lagi untuk mencapai sekitar 84%.</p>
                        ) : (
                            <p className="mt-2 text-sm text-secondary">Ambang lima peserta tercapai. Peserta berikutnya tetap menambah, hanya makin landai.</p>
                        )}
                    </Card>

                    <Card title="Skor SUS">
                        {ringkasan.susRata === null ? (
                            <p className="text-sm text-secondary">Belum ada responden yang melengkapi kuesioner.</p>
                        ) : (
                            <>
                                <p className="text-2xl font-semibold text-primary">
                                    {ringkasan.susRata} <span className="text-base font-medium text-secondary">({ringkasan.susGrade})</span>
                                </p>
                                {/* Ditulis tanpa tanda persen karena SUS memang bukan persentase. */}
                                <p className="mt-1 text-xs text-muted">Skala 0-100, bukan persentase. Rata-rata seluruh produk sekitar 68.</p>
                            </>
                        )}
                        {!ringkasan.susDapatDiandalkan ? (
                            <p className="mt-2 text-sm text-warn-900">
                                Dari {ringkasan.respondenSus} responden. Butuh {ringkasan.respondenSusKurang} lagi sebelum angka ini layak dikutip.
                            </p>
                        ) : null}
                    </Card>

                    <Card title="Pembaca layar">
                        <p className="text-2xl font-semibold text-primary">{ringkasan.sesiPembacaLayar}</p>
                        <p className="mt-1 text-sm text-secondary">Penelusuran yang sudah tercatat. Pemeriksaan otomatis tidak dapat menggantikannya.</p>
                    </Card>
                </div>

                <Card title="Hasil per tugas" subtitle="Berhasil dengan kesulitan dihitung setengah, karena tugas yang selesai setelah tersesat bukan tugas yang berhasil.">
                    <div className="relative overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <caption className="sr-only">Tingkat keberhasilan tiap tugas protokol</caption>
                            <thead>
                                <tr className="border-b border-subtle text-left text-secondary">
                                    <th scope="col" className="py-2 pr-3 font-medium">Tugas</th>
                                    <th scope="col" className="py-2 pr-3 font-medium">Diamati</th>
                                    <th scope="col" className="py-2 pr-3 font-medium">Gagal</th>
                                    <th scope="col" className="py-2 font-medium">Tingkat berhasil</th>
                                </tr>
                            </thead>
                            <tbody>
                                {ringkasan.tugas.map((t) => (
                                    <tr key={t.kode} className="border-b border-subtle">
                                        <td className="py-2 pr-3">
                                            <span className="font-medium text-primary">{t.kode}</span> <span className="text-secondary">{t.judul}</span>
                                            {t.inti ? <span className="ml-1 rounded-sm bg-brand-100 px-1.5 py-0.5 text-xs font-medium text-brand-900">inti</span> : null}
                                        </td>
                                        <td className="py-2 pr-3 text-secondary">{t.diamati}</td>
                                        <td className="py-2 pr-3 text-secondary">{t.gagal}</td>
                                        <td className="py-2 text-primary">{t.tingkatBerhasil}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>

                <Card title="Catat sesi baru">
                    <form onSubmit={kirim} className="space-y-6">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <FormField
                                name="participant_code"
                                label="Kode peserta"
                                required
                                hint="Kode, bukan nama."
                                value={form.data.participant_code}
                                onChange={(e) => form.setData('participant_code', e.target.value)}
                                error={form.errors.participant_code}
                            />
                            <SelectField name="kind" label="Jenis sesi" required options={jenis} value={form.data.kind} onChange={(e) => form.setData('kind', e.target.value)} error={form.errors.kind} />
                            <SelectField
                                name="experience_level"
                                label="Tingkat pengalaman (opsional)"
                                placeholder="Tidak dicatat"
                                options={tingkat}
                                value={form.data.experience_level}
                                onChange={(e) => form.setData('experience_level', e.target.value)}
                                error={form.errors.experience_level}
                            />
                        </div>

                        <fieldset className="space-y-4">
                            <legend className="text-sm font-medium text-primary">Hasil tugas</legend>

                            {daftarTugas.map((t) => (
                                <div key={t.kode} className="rounded-control border border-subtle p-3">
                                    <p className="text-sm font-medium text-primary">{t.kode}. {t.judul}</p>
                                    {/* Kriteria berhasil ikut tertulis supaya fasilitator tidak menafsirkan ulang dari ingatan. */}
                                    <p className="mt-1 text-xs text-secondary">Berhasil bila: {t.berhasil}</p>

                                    <div className="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        <div>
                                            <label htmlFor={`outcome-${t.kode}`} className="block text-xs font-medium text-secondary">Hasil</label>
                                            <Select
                                                id={`outcome-${t.kode}`}
                                                className={kontrolKecil}
                                                value={form.data.tasks[t.kode]?.outcome ?? ''}
                                                onChange={(e) => ubahTugas(t.kode, 'outcome', e.target.value)}
                                                aria-invalid={galat[`tasks.${t.kode}.outcome`] ? true : undefined}
                                                aria-describedby={galat[`tasks.${t.kode}.outcome`] ? `outcome-${t.kode}-error` : undefined}
                                            >
                                                <option value="">Tidak dijalankan</option>
                                                {hasilTugas.map((h) => (
                                                    <option key={h.value} value={h.value}>{h.label}</option>
                                                ))}
                                            </Select>
                                            <InputError id={`outcome-${t.kode}-error`} messages={galat[`tasks.${t.kode}.outcome`]} className="mt-1" />
                                        </div>
                                        <div>
                                            <label htmlFor={`seconds-${t.kode}`} className="block text-xs font-medium text-secondary">Waktu (detik)</label>
                                            <TextInput
                                                id={`seconds-${t.kode}`}
                                                type="number"
                                                min={0}
                                                className={kontrolKecil}
                                                value={form.data.tasks[t.kode]?.seconds ?? ''}
                                                onChange={(e) => ubahTugas(t.kode, 'seconds', e.target.value)}
                                                aria-invalid={galat[`tasks.${t.kode}.seconds`] ? true : undefined}
                                                aria-describedby={galat[`tasks.${t.kode}.seconds`] ? `seconds-${t.kode}-error` : undefined}
                                            />
                                            <InputError id={`seconds-${t.kode}-error`} messages={galat[`tasks.${t.kode}.seconds`]} className="mt-1" />
                                        </div>
                                        <div>
                                            <label htmlFor={`note-${t.kode}`} className="block text-xs font-medium text-secondary">Kutipan atau masalah</label>
                                            <TextInput
                                                id={`note-${t.kode}`}
                                                className={kontrolKecil}
                                                value={form.data.tasks[t.kode]?.note ?? ''}
                                                onChange={(e) => ubahTugas(t.kode, 'note', e.target.value)}
                                                aria-invalid={galat[`tasks.${t.kode}.note`] ? true : undefined}
                                                aria-describedby={galat[`tasks.${t.kode}.note`] ? `note-${t.kode}-error` : undefined}
                                            />
                                            <InputError id={`note-${t.kode}-error`} messages={galat[`tasks.${t.kode}.note`]} className="mt-1" />
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </fieldset>

                        <fieldset className="space-y-3">
                            <legend className="text-sm font-medium text-primary">SUS</legend>
                            <p className="text-xs text-secondary">
                                Diisi setelah semua tugas selesai. 1 sangat tidak setuju, 5 sangat setuju. Lembar yang tidak lengkap tetap tersimpan, hanya tidak menyumbang skor.
                            </p>

                            {pernyataanSus.map((p) => (
                                <div key={p.nomor} className="border-b border-subtle pb-2">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <label htmlFor={`sus-${p.nomor}`} className="text-sm text-primary">{p.nomor}. {p.pernyataan}</label>
                                        <Select
                                            id={`sus-${p.nomor}`}
                                            className="mt-0 w-auto text-sm"
                                            value={form.data.sus[p.nomor] ?? ''}
                                            onChange={(e) => form.setData('sus', { ...form.data.sus, [p.nomor]: e.target.value })}
                                            aria-invalid={galat[`sus.${p.nomor}`] ? true : undefined}
                                            aria-describedby={galat[`sus.${p.nomor}`] ? `sus-${p.nomor}-error` : undefined}
                                        >
                                            <option value="">-</option>
                                            {[1, 2, 3, 4, 5].map((n) => (
                                                <option key={n} value={n}>{n}</option>
                                            ))}
                                        </Select>
                                    </div>
                                    <InputError id={`sus-${p.nomor}-error`} messages={galat[`sus.${p.nomor}`]} className="mt-1" />
                                </div>
                            ))}
                        </fieldset>

                        <TextareaField name="notes" label="Catatan sesi (opsional)" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} error={form.errors.notes} />

                        <Button type="submit" processing={form.processing}>Simpan sesi</Button>
                    </form>
                </Card>

                <Card title="Sesi tercatat">
                    {sesi.data.length === 0 ? (
                        <p className="text-sm text-secondary">Belum ada sesi tercatat.</p>
                    ) : (
                        sesi.data.map((s) => (
                            <div key={s.id} className="flex flex-wrap items-center justify-between gap-2 border-b border-subtle py-2 text-sm">
                                <div>
                                    <span className="font-medium text-primary">{s.kode}</span>
                                    <span className="text-secondary"> &middot; {s.jenis}</span>
                                    {s.fasilitator ? <span className="text-secondary"> &middot; {s.fasilitator}</span> : null}
                                </div>
                                <div className="text-secondary">
                                    {s.sus} &middot; {s.waktu}
                                </div>
                            </div>
                        ))
                    )}
                    <div className="mt-4">
                        <Pagination links={sesi.links} />
                    </div>
                </Card>
            </div>
        </div>
    );
}

Usability.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
