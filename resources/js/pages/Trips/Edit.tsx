import { router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent, type ReactNode } from 'react';
import { fokusGalat } from '@/components/akun/fokusGalat';
import { BarisStatus } from '@/components/trails/BarisStatus';
import { LabelKecocokan, type LabelFit } from '@/components/trails/LabelKecocokan';
import type { StatusRingkas } from '@/components/trails/types';
import { KotakIzin, type IzinRencana } from '@/components/trips/rencana/KotakIzin';
import type { BatasRencana, IsianRencanaData } from '@/components/trips/rencana/IsianRencana';
import { BarAksi } from '@/components/ui/BarAksi';
import { Button } from '@/components/ui/Button';
import { FormField, InputError, Label, Textarea } from '@/components/ui/Field';
import { KontrolSegmen } from '@/components/ui/KontrolSegmen';
import { PageHeader } from '@/components/ui/PageHeader';
import { Stepper } from '@/components/ui/Stepper';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { show, update } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface JalurRencana {
    id: number;
    nama: string;
    gunung: string;
    url: string;
    ringkas: string;
    status: StatusRingkas;
    peringatan: string | null;
}

interface Kecocokan {
    label: LabelFit;
    labelText: string;
    alasan: string;
    denganRencana: boolean;
    layak: boolean;
}

interface JenisTrip {
    nilai: string;
    label: string;
    keterangan: string;
    lamaHari: number;
}

interface PosRingkas {
    id: number;
    urutan: number;
    nama: string;
    tipe: string;
    elevasi: string | null;
}

interface Props extends PropsBersama {
    trip: { id: number; jalur: string };
    awal: IsianRencanaData;
    jalur: JalurRencana;
    jenisTrip: JenisTrip[];
    tanggalTerawal: string;
    batas: BatasRencana;
    zona: string | null;
    kecocokan: Kecocokan | null;
    jadwal: { rentang: string; ringkas: string; lama: string; prakiraan: string } | null;
    izin: IzinRencana | null;
    pos: PosRingkas[];
}

function tambahHari(tanggal: string, hari: number): string {
    const d = new Date(`${tanggal}T00:00:00Z`);
    d.setUTCDate(d.getUTCDate() + hari);

    return d.toISOString().slice(0, 10);
}

/**
 * Mengubah rencana yang belum dimulai (features/04, S2.2 paritas penuh dengan Rencanakan Trip).
 * Jalurnya tetap: jalur lain berarti rencana lain dengan daftar persiapannya sendiri. Kecocokan,
 * jadwal & prakiraan BMKG, serta jendela izin SIMAKSI dihitung ulang server secara langsung.
 */
export default function Edit() {
    const { trip, awal, jalur, jenisTrip, tanggalTerawal, batas, zona, kecocokan, jadwal, izin, pos } = usePage<Props>().props;
    const form = useForm<IsianRencanaData>(awal);
    const [memuat, setMemuat] = useState(false);
    const batalMuatUlang = useRef<{ cancel: () => void } | null>(null);
    const idMuatUlang = useRef(0);
    const tundaTanggal = useRef<ReturnType<typeof setTimeout> | null>(null);
    const isianTerbaru = useRef(form.data);
    const selesaiPilihan = useRef<string | null>(awal.end_date || null);

    useEffect(() => {
        isianTerbaru.current = form.data;
    });

    useEffect(
        () => () => {
            if (tundaTanggal.current !== null) clearTimeout(tundaTanggal.current);
        },
        [],
    );

    const muatUlang = (jenis: string, tanggal: string, selesai: string) => {
        batalMuatUlang.current?.cancel();

        const id = ++idMuatUlang.current;

        router.reload({
            only: ['kecocokan', 'jadwal', 'izin'],
            data: { jenis, tanggal, selesai },
            replace: true,
            onCancelToken: (token) => {
                batalMuatUlang.current = token;
            },
            onStart: () => setMemuat(true),
            onFinish: () => {
                if (id === idMuatUlang.current) {
                    setMemuat(false);
                }
            },
        });
    };

    const selesaiUntuk = (jenis: string, tanggal: string) => {
        const lama = jenisTrip.find((j) => j.nilai === jenis)?.lamaHari ?? 1;

        if (lama > 2 && selesaiPilihan.current && tanggal && selesaiPilihan.current >= tanggal) {
            return selesaiPilihan.current;
        }

        return tanggal && lama > 1 ? tambahHari(tanggal, lama - 1) : '';
    };

    const ubahJenis = (jenis: string) => {
        const selesai = selesaiUntuk(jenis, form.data.planned_date);
        form.setData((d) => ({ ...d, trip_type: jenis, end_date: selesai }));

        if (tundaTanggal.current !== null) {
            clearTimeout(tundaTanggal.current);
            tundaTanggal.current = null;
        }

        muatUlang(jenis, form.data.planned_date, selesai);
    };

    const muatUlangTunda = () => {
        if (tundaTanggal.current !== null) {
            clearTimeout(tundaTanggal.current);
        }

        tundaTanggal.current = setTimeout(() => {
            const i = isianTerbaru.current;
            muatUlang(i.trip_type, i.planned_date, i.end_date);
        }, 400);
    };

    const ubahTanggal = (tanggal: string) => {
        selesaiPilihan.current = null;
        const selesai = selesaiUntuk(form.data.trip_type, tanggal);
        form.setData((d) => ({ ...d, planned_date: tanggal, end_date: selesai }));
        muatUlangTunda();
    };

    const ubahSelesai = (selesai: string) => {
        selesaiPilihan.current = selesai || null;
        form.setData('end_date', selesai);
        muatUlangTunda();
    };

    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.put(update.url(trip.id), {
            onError: (galat) =>
                fokusGalat(galat, [
                    ['trip_type', 'bagian-jenis'],
                    'planned_date',
                    'end_date',
                    'start_time',
                    ['participants_count', 'bagian-rombongan'],
                    'name',
                    'notes',
                ]),
        });
    };

    const jenisDipilih = jenisTrip.find((j) => j.nilai === form.data.trip_type);
    const minTanggalNaik = awal.planned_date && awal.planned_date < tanggalTerawal ? awal.planned_date : tanggalTerawal;

    return (
        <div className="py-6">
            <div className="mx-auto max-w-2xl px-4 sm:px-6">
                {/* Satu nama untuk halaman induk di semua tombol kembali (ruling AF-10, audit E2E M-8). */}
                <PageHeader induk={{ href: show.url(trip.id), label: 'Ruang kerja trip' }} title="Ubah Rencana Trip" description={trip.jalur} />

                <form onSubmit={kirim} className="space-y-8">
                    <section aria-labelledby="jalur-rencana" className="rounded-utama border border-subtle bg-surface p-5">
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <h2 id="jalur-rencana" className="wrap-break-word text-subjudul text-primary">
                                    {jalur.nama}
                                </h2>
                                <p className="text-kecil text-secondary">{jalur.gunung}</p>
                                <p data-angka className="mt-1 text-kecil text-secondary">
                                    {jalur.ringkas}
                                </p>
                            </div>
                            <Tautan
                                href={jalur.url}
                                className="inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600"
                            >
                                Lihat jalur
                            </Tautan>
                        </div>
                        <p className="mt-2 text-kecil text-secondary">Jalur tidak dapat diganti di sini. Untuk jalur lain, buat rencana trip baru.</p>

                        {kecocokan ? (
                            <div aria-live="polite" aria-busy={memuat || undefined} className="mt-4 rounded-tombol bg-surface-sunken p-4">
                                <p className="flex flex-wrap items-center gap-2">
                                    <LabelKecocokan nilai={kecocokan.label} label={kecocokan.labelText} />
                                    {kecocokan.label !== null ? (
                                        <span className="text-meta text-secondary">{kecocokan.denganRencana ? 'untuk rencana ini' : 'Kecocokan dasar'}</span>
                                    ) : null}
                                </p>
                                {!kecocokan.layak ? <p className="mt-2 text-kecil font-bold text-primary">Tidak memenuhi syarat dasar untuk rencana ini.</p> : null}
                                <p className="mt-2 text-kecil text-primary">{kecocokan.alasan}</p>
                                {memuat ? <p className="mt-2 text-meta text-muted">Menghitung ulang untuk pilihan baru…</p> : null}
                            </div>
                        ) : null}

                        <div className="mt-4">
                            <BarisStatus status={jalur.status} />
                            <p className="mt-1 text-meta text-secondary">Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.</p>
                            {jalur.peringatan ? <p className="mt-2 text-kecil font-bold text-warn-900">{jalur.peringatan}</p> : null}
                        </div>

                        {pos.length > 0 ? (
                            <div className="mt-4 border-t border-subtle pt-4">
                                <h3 className="text-kecil font-bold text-primary">Urutan pos jalur ({pos.length} pos)</h3>
                                <ol className="mt-2 space-y-1 text-kecil text-secondary">
                                    {pos.map((p) => (
                                        <li key={p.id} className="flex flex-wrap justify-between gap-2">
                                            <span>
                                                <span data-angka className="font-medium text-primary">
                                                    {p.urutan}. {p.nama}
                                                </span>{' '}
                                                <span>({p.tipe})</span>
                                            </span>
                                            {p.elevasi ? <span data-angka>{p.elevasi}</span> : null}
                                        </li>
                                    ))}
                                </ol>
                            </div>
                        ) : null}
                    </section>

                    <section id="bagian-jenis" tabIndex={-1} aria-labelledby="judul-jenis">
                        <h2 id="judul-jenis" className="text-bagian text-primary">
                            Jenis trip
                        </h2>
                        <KontrolSegmen
                            nama="trip_type"
                            label="Jenis trip"
                            pilihan={jenisTrip.map((j) => ({ nilai: j.nilai, label: j.label, keterangan: j.keterangan }))}
                            nilai={form.data.trip_type}
                            onUbah={ubahJenis}
                            penjelas={form.errors.trip_type ? 'trip_type-error' : undefined}
                            className="mt-3"
                        />
                        <InputError id="trip_type-error" messages={form.errors.trip_type} className="mt-2" />
                    </section>

                    <section aria-labelledby="judul-tanggal" className="space-y-4">
                        <h2 id="judul-tanggal" className="text-bagian text-primary">
                            Tanggal
                        </h2>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <FormField
                                name="planned_date"
                                type="date"
                                label="Tanggal naik"
                                min={minTanggalNaik}
                                required
                                value={form.data.planned_date}
                                onChange={(e) => ubahTanggal(e.target.value)}
                                error={form.errors.planned_date}
                            />
                            {(jenisDipilih && jenisDipilih.lamaHari > 2) || form.data.end_date !== '' ? (
                                <FormField
                                    name="end_date"
                                    type="date"
                                    label="Tanggal turun"
                                    min={form.data.planned_date || minTanggalNaik}
                                    hint={`Paling lambat ${batas.rentangHari} hari setelah tanggal naik.`}
                                    value={form.data.end_date}
                                    onChange={(e) => ubahSelesai(e.target.value)}
                                    error={form.errors.end_date}
                                />
                            ) : null}
                        </div>
                        {jadwal ? (
                            <p data-angka className="text-isi font-bold text-primary">
                                {jadwal.rentang} ({jadwal.lama})
                            </p>
                        ) : null}
                        <div aria-live="polite" aria-busy={memuat || undefined} className="space-y-4 empty:mt-0!">
                            {izin ? <KotakIzin izin={izin} /> : null}
                            {jadwal ? <p className="text-kecil text-secondary">{jadwal.prakiraan}</p> : null}
                        </div>
                    </section>

                    <section id="bagian-rombongan" tabIndex={-1} aria-labelledby="judul-rombongan" className="space-y-5">
                        <h2 id="judul-rombongan" className="text-bagian text-primary">
                            Jam dan rombongan
                        </h2>
                        <FormField
                            name="start_time"
                            type="time"
                            label="Jam mulai (opsional)"
                            hint={zona ? `Waktu setempat gunung (${zona}).` : undefined}
                            value={form.data.start_time}
                            onChange={(e) => form.setData('start_time', e.target.value)}
                            error={form.errors.start_time}
                        />
                        <div>
                            <Stepper
                                label="Peserta"
                                keterangan="Termasuk Anda"
                                nilai={Number(form.data.participants_count) || 1}
                                min={1}
                                max={batas.peserta}
                                onUbah={(n) => form.setData('participants_count', String(n))}
                                penjelas={form.errors.participants_count ? 'participants_count-error' : undefined}
                                className="rounded-lg border border-subtle bg-surface p-4"
                            />
                            <InputError id="participants_count-error" messages={form.errors.participants_count} className="mt-2" />
                            {izin ? <p className="mt-2 text-kecil text-secondary">{izin.kuota}</p> : null}
                        </div>
                    </section>

                    <section aria-labelledby="judul-catatan" className="space-y-5">
                        <h2 id="judul-catatan" className="text-bagian text-primary">
                            Nama dan catatan
                        </h2>
                        <FormField
                            name="name"
                            label="Nama trip"
                            required
                            maxLength={120}
                            autoComplete="off"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            error={form.errors.name}
                        />
                        <div>
                            <Label htmlFor="notes">Catatan (opsional)</Label>
                            <Textarea
                                id="notes"
                                name="notes"
                                rows={3}
                                maxLength={1000}
                                value={form.data.notes}
                                onChange={(e) => form.setData('notes', e.target.value)}
                                aria-invalid={form.errors.notes ? true : undefined}
                                aria-describedby={form.errors.notes ? 'notes-error' : undefined}
                            />
                            <InputError id="notes-error" messages={form.errors.notes} className="mt-2" />
                        </div>
                    </section>

                    <BarAksi
                        label="Aksi ubah rencana trip"
                        ringkasan={
                            <p data-angka className="wrap-break-word">
                                {jenisDipilih ? <span className="font-bold text-primary">{jenisDipilih.label}</span> : 'Pilih jenis trip'}
                                {jadwal ? `, ${jadwal.ringkas} (${jadwal.lama})` : ''}
                            </p>
                        }
                    >
                        <div className="flex flex-wrap gap-2">
                            <Button variant="secondary" href={show.url(trip.id)}>
                                Batal
                            </Button>
                            <Button type="submit" processing={form.processing}>
                                Simpan perubahan
                            </Button>
                        </div>
                    </BarAksi>
                </form>
            </div>
        </div>
    );
}

Edit.layout = (page: ReactNode) => <AppLayout bilahTab={false}>{page}</AppLayout>;
