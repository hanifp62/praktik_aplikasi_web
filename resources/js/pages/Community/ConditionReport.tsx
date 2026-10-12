import { router, useForm, usePage } from '@inertiajs/react';
import type { ChangeEvent, FormEvent, ReactNode } from 'react';
import type { Opsi } from '@/components/community/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Checkbox, FormField, InputError, Label, Select, Textarea } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { progress } from '@/routes';
import { store } from '@/routes/reports';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    trails: Opsi[];
    segments: Opsi[];
    tags: Opsi[];
    awal: { trail_id: number | null; trip_plan_id: number | null; hike_date: string };
    tanggalMaksimum: string;
}

/*
 * Batas yang sama dengan aturan server (hiking.uploads.report_photo_max_kb, dijaga ConditionReportPageTest). Di
 * Vercel, permintaan di atas 4,5 MB ditolak platform sebelum sampai ke server, jadi foto yang lebih besar ditolak di
 * sini, sebelum dikirim, dengan pesan di dekat kontrolnya.
 */
const BATAS_FOTO_BYTE = 4096 * 1024;
const PESAN_FOTO_BESAR = 'Ukuran foto lebih dari 4 MB. Pilih foto yang lebih kecil, atau kirim laporan tanpa foto.';

interface Isian {
    trail_id: string;
    trail_segment_id: string;
    hike_date: string;
    condition_tags: string[];
    note: string;
    photo: File | null;
    trip_plan_id: string;
}

/**
 * FR-13 laporan kondisi. Ringkas di ponsel: satu kolom, kondisi sebagai pilihan dua
 * kolom yang besar untuk jempol, dan satu tombol kirim di ujungnya. Validasi, batas
 * laju, dan pembersihan EXIF foto tetap di server; laporan komunitas tidak mengubah
 * status resmi (R-010) dan selalu melewati moderasi.
 */
export default function ConditionReport() {
    const { trails, segments, tags, awal, tanggalMaksimum } = usePage<Props>().props;

    const form = useForm<Isian>({
        trail_id: awal.trail_id ? String(awal.trail_id) : '',
        trail_segment_id: '',
        hike_date: awal.hike_date,
        condition_tags: [],
        note: '',
        photo: null,
        trip_plan_id: awal.trip_plan_id ? String(awal.trip_plan_id) : '',
    });

    // Galat per butir (condition_tags.0) dan galat formulir (batas laju) tidak punya
    // kunci di data formulir, jadi dibaca sebagai peta biasa.
    const galat = form.errors as Record<string, string | undefined>;
    const galatKondisi = galat.condition_tags ?? Object.entries(galat).find(([k]) => k.startsWith('condition_tags.'))?.[1];

    // Segmen bergantung pada jalur: hanya prop itu yang dimuat ulang dari server.
    const pilihJalur = (nilai: string) => {
        form.setData((d) => ({ ...d, trail_id: nilai, trail_segment_id: '' }));
        router.reload({ only: ['segments'], data: { trail: nilai } });
    };

    const ubahKondisi = (nilai: string, dipilih: boolean) =>
        form.setData('condition_tags', dipilih ? [...form.data.condition_tags, nilai] : form.data.condition_tags.filter((t) => t !== nilai));

    const pilihFoto = (e: ChangeEvent<HTMLInputElement>) => {
        const foto = e.target.files?.[0] ?? null;

        if (foto !== null && foto.size > BATAS_FOTO_BYTE) {
            e.target.value = '';
            form.setData('photo', null);
            form.setError('photo', PESAN_FOTO_BESAR);

            return;
        }

        form.clearErrors('photo');
        form.setData('photo', foto);
    };

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.post(store.url(), {
            forceFormData: true,
            preserveScroll: true,
            // Cadangan bila platform tetap menolak ukuran permintaan: galatnya tampil di dekat kontrol foto,
            // bukan halaman galat platform di dalam dialog.
            onHttpException: (respons) => {
                if (respons.status !== 413) {
                    return;
                }

                form.setError('photo', PESAN_FOTO_BESAR);

                return false;
            },
        });
    };

    return (
        <div className="py-6 sm:py-8">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    induk={{ href: progress.url(), label: 'Progres' }}
                    title="Laporan Kondisi Jalur"
                    description="Laporan Anda menjadi informasi tambahan bagi pendaki berikutnya. Laporan komunitas tidak mengubah status resmi jalur."
                />

                <form onSubmit={kirim} className="space-y-5 rounded-lg bg-surface p-4 sm:p-6">
                    <div>
                        <Label htmlFor="trail_id">Jalur</Label>
                        <Select
                            id="trail_id"
                            required
                            value={form.data.trail_id}
                            onChange={(e) => pilihJalur(e.target.value)}
                            aria-invalid={form.errors.trail_id ? true : undefined}
                            aria-describedby={form.errors.trail_id ? 'trail_id-error' : undefined}
                        >
                            <option value="">Pilih jalur</option>
                            {trails.map((t) => (
                                <option key={t.value} value={t.value}>{t.label}</option>
                            ))}
                        </Select>
                        <InputError id="trail_id-error" messages={form.errors.trail_id} className="mt-2" />
                    </div>

                    {segments.length > 0 ? (
                        <div>
                            <Label htmlFor="trail_segment_id">Segmen (opsional)</Label>
                            <Select
                                id="trail_segment_id"
                                value={form.data.trail_segment_id}
                                onChange={(e) => form.setData('trail_segment_id', e.target.value)}
                                aria-describedby={form.errors.trail_segment_id ? 'trail_segment_id-error' : undefined}
                            >
                                <option value="">Seluruh jalur</option>
                                {segments.map((s) => (
                                    <option key={s.value} value={s.value}>{s.label}</option>
                                ))}
                            </Select>
                            <InputError id="trail_segment_id-error" messages={form.errors.trail_segment_id} className="mt-2" />
                        </div>
                    ) : null}

                    <FormField
                        name="hike_date"
                        type="date"
                        label="Tanggal pendakian"
                        hint="Tanggal pendakian menentukan seberapa baru informasi ini bagi pengguna lain."
                        required
                        max={tanggalMaksimum}
                        value={form.data.hike_date}
                        onChange={(e) => form.setData('hike_date', e.target.value)}
                        error={form.errors.hike_date}
                    />

                    <fieldset aria-describedby={galatKondisi ? 'condition_tags-error' : undefined}>
                        <legend className="block text-sm font-medium text-secondary">Kondisi yang Anda temui</legend>
                        <div className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                            {tags.map((t) => (
                                <label key={t.value} className="flex min-h-11 items-center gap-2 rounded-control border border-subtle px-3 py-2 text-sm text-primary">
                                    <Checkbox
                                        value={t.value}
                                        checked={form.data.condition_tags.includes(String(t.value))}
                                        onChange={(e) => ubahKondisi(String(t.value), e.target.checked)}
                                    />
                                    {t.label}
                                </label>
                            ))}
                        </div>
                        <InputError id="condition_tags-error" messages={galatKondisi} className="mt-2" />
                    </fieldset>

                    <div>
                        <Label htmlFor="note">Catatan (opsional)</Label>
                        <Textarea
                            id="note"
                            rows={3}
                            value={form.data.note}
                            onChange={(e) => form.setData('note', e.target.value)}
                            aria-describedby={form.errors.note ? 'note-error' : undefined}
                        />
                        <InputError id="note-error" messages={form.errors.note} className="mt-2" />
                    </div>

                    <div>
                        <Label htmlFor="photo">Foto (opsional, maksimal 4 MB)</Label>
                        <input
                            id="photo"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={pilihFoto}
                            aria-invalid={form.errors.photo ? true : undefined}
                            aria-describedby={form.errors.photo ? 'photo-error' : undefined}
                            className="mt-1 block w-full text-sm text-secondary file:mr-3 file:min-h-11 file:rounded-control file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-ink"
                        />
                        {form.progress ? (
                            <p className="mt-1 text-xs text-muted" aria-live="polite">
                                Mengunggah foto... {form.progress.percentage}%
                            </p>
                        ) : null}
                        {/* Wilayah yang selalu terpasang: galat ukuran muncul tanpa kiriman, sesudah dialog
                            berkas ditutup dan inputnya dikosongkan, jadi harus diumumkan ke pembaca layar. */}
                        <div aria-live="polite">
                            <InputError id="photo-error" messages={form.errors.photo} className="mt-2" />
                        </div>
                    </div>

                    <p className="rounded-control bg-surface-sunken px-4 py-3 text-sm text-secondary">Laporan akan melalui moderasi sebelum ditampilkan kepada pengguna lain.</p>

                    {galat.form ? <Alert variant="danger">{galat.form}</Alert> : null}

                    <Button type="submit" processing={form.processing} className="w-full sm:w-auto">
                        Kirim laporan
                    </Button>
                </form>
            </div>
        </div>
    );
}

ConditionReport.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
