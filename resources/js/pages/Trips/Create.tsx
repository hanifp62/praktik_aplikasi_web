import { router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent, type ReactNode } from 'react';
import { BarisStatus } from '@/components/trails/BarisStatus';
import { LabelKecocokan, type LabelFit } from '@/components/trails/LabelKecocokan';
import type { StatusRingkas } from '@/components/trails/types';
import { LangkahRingkas } from '@/components/trips/LangkahRingkas';
import { fokusGalat } from '@/components/akun/fokusGalat';
import { KotakIzin, type IzinRencana } from '@/components/trips/rencana/KotakIzin';
import { BarAksi } from '@/components/ui/BarAksi';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { FormField, InputError, Label, Textarea } from '@/components/ui/Field';
import { Icon } from '@/components/ui/Icon';
import { KontrolSegmen } from '@/components/ui/KontrolSegmen';
import { PageHeader } from '@/components/ui/PageHeader';
import { Sakelar } from '@/components/ui/Sakelar';
import { Stepper } from '@/components/ui/Stepper';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { index as trailsIndex } from '@/routes/trails';
import { index as tripsIndex, store } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface Isian {
    trail_id: string;
    hiking_goal_id: number | null;
    name: string;
    planned_date: string;
    end_date: string;
    start_time: string;
    participants_count: string;
    trip_type: string;
    notes: string;
    reminders_enabled: boolean;
}

interface JalurRencana {
    id: number;
    nama: string;
    gunung: string;
    url: string;
    ringkas: string;
    status: StatusRingkas;
    peringatan: string | null;
    ganti: string;
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

interface Props extends PropsBersama {
    jalur: JalurRencana | null;
    pilihanJalur: Array<{ nama: string; gunung: string | null; href: string }>;
    awal: Isian;
    jenisTrip: JenisTrip[];
    tanggalTerawal: string;
    batas: { peserta: number; rentangHari: number };
    zona: string | null;
    jadwalPengingat: string;
    kecocokan: Kecocokan | null;
    jadwal: { rentang: string; ringkas: string; lama: string; prakiraan: string } | null;
    izin: IzinRencana | null;
}

const tautanTeks =
    'inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';

/** Tanggal Y-m-d ditambah sekian hari, dihitung di UTC supaya zona peramban tidak menggeser hari. */
function tambahHari(tanggal: string, hari: number): string {
    const d = new Date(`${tanggal}T00:00:00Z`);
    d.setUTCDate(d.getUTCDate() + hari);

    return d.toISOString().slice(0, 10);
}

/**
 * Rencanakan trip (spec v3 bagian 6.8, prototype 08). Kecocokan untuk rencana, jadwal, dan
 * jendela izin dihitung ulang server setiap jenis atau tanggal berubah, lewat muat ulang parsial
 * yang hanya meminta ketiganya; klien tidak pernah menilai sendiri. Jalur berstatus Tutup tetap
 * dapat direncanakan untuk tanggal mendatang, dengan peringatan statusnya sekarang. Halaman
 * tugas: bilah tab disembunyikan, dan ringkasan rencana menempel di bar aksi bersama "Simpan
 * rencana". Pengingat surel mati sampai pendaki menyalakannya sendiri.
 */
export default function Create() {
    const { jalur, pilihanJalur, awal, jenisTrip, tanggalTerawal, batas, zona, jadwalPengingat, kecocokan, jadwal, izin } = usePage<Props>().props;
    const form = useForm<Isian>(awal);
    const [memuat, setMemuat] = useState(false);
    const batalMuatUlang = useRef<{ cancel: () => void } | null>(null);
    const idMuatUlang = useRef(0);
    const tundaTanggal = useRef<ReturnType<typeof setTimeout> | null>(null);
    // Isian terbaru dibaca ulang saat timer tunda menembak, bukan ditutup sebagai parameter
    // saat timer dijadwalkan: tanpa ini timer tanggal yang masih menunggu bisa menembak
    // dengan jenis yang sudah diganti lewat ubahJenis sebelum 400 ms berlalu, menjadi
    // kiriman terakhir yang salah (ruling W4-3f). Ditulis di efek tanpa dependensi, bukan di
    // badan render: efek berjalan sesudah setiap render, jauh sebelum timer 400 ms menembak.
    const isianTerbaru = useRef(form.data);
    // Tanggal turun yang dipilih pendaki sendiri, supaya berganti jenis lalu kembali ke
    // Multi-hari tidak menimpanya dengan lama bawaan (minor putaran 2 Tugas 3).
    const selesaiPilihan = useRef<string | null>(null);

    useEffect(() => {
        isianTerbaru.current = form.data;
    });

    useEffect(
        () => () => {
            if (tundaTanggal.current !== null) clearTimeout(tundaTanggal.current);
        },
        [],
    );

    /**
     * Inertia 3 memaksa muat ulang parsial berjalan async (doReload) dan tidak mengurutkan
     * responsnya sendiri, jadi respons yang tiba terakhir yang menang meski dikirim dari
     * pilihan yang lebih lama. Muat ulang sebelumnya dibatalkan lewat token sebelum muat
     * ulang baru dikirim, dan penanda memuat hanya padam untuk kunjungan yang masih aktif
     * (putaran perbaikan 1 W4 Tugas 3, ruling W4-3b, I1).
     */
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

        // Isian tanggal turun hanya tampil untuk jenis lebih dari dua hari; di sana pilihan
        // pendaki sendiri dipertahankan selama masih sesudah tanggal naik.
        if (lama > 2 && selesaiPilihan.current && tanggal && selesaiPilihan.current >= tanggal) {
            return selesaiPilihan.current;
        }

        return tanggal && lama > 1 ? tambahHari(tanggal, lama - 1) : '';
    };

    const ubahJenis = (jenis: string) => {
        const selesai = selesaiUntuk(jenis, form.data.planned_date);
        form.setData((d) => ({ ...d, trip_type: jenis, end_date: selesai }));

        // Kontrol segmen jenis tidak ditunda, jadi timer tunda tanggal yang masih menunggu
        // dibersihkan dulu: tanpa ini timer lama bisa menembak sesudah ini dengan jenis
        // yang sudah diganti, menjadi kiriman terakhir yang salah (ruling W4-3f).
        if (tundaTanggal.current !== null) {
            clearTimeout(tundaTanggal.current);
            tundaTanggal.current = null;
        }

        muatUlang(jenis, form.data.planned_date, selesai);
    };

    // Isian tanggal native memancarkan onChange per segmen yang diketik (tahun empat digit
    // berarti sampai empat perubahan beruntun), jadi ditunda seperti saringan Jelajah
    // (Trails/Index.tsx) supaya tidak mengirim satu muat ulang per ketukan. Timer membaca
    // isianTerbaru saat menembak, bukan parameter yang ditutup saat dijadwalkan (W4-3f).
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
        // Tanggal naik yang berganti memulai rentang baru dari lama bawaan jenisnya.
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

    // Fokus ke isian pertama yang ditolak, dalam urutan tampilnya (M9 tinjauan Tugas 3);
    // isian tanpa kontrol sendiri memindah fokus ke bagian yang menyebut galatnya.
    const kirim = (e: FormEvent) => {
        e.preventDefault();
        form.post(store.url(), {
            onError: (galat) =>
                fokusGalat(galat, [
                    ['trail_id', 'bagian-jalur'],
                    ['hiking_goal_id', 'bagian-jalur'],
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
    // trail_id dan hiking_goal_id tidak punya kontrol sendiri di halaman ini (jalur datang
    // dari ?trail=, rencana dari ?goal=), jadi galatnya diikatkan ke kartu jalur yang
    // menampilkannya, bukan ke satu isian (putaran perbaikan 1 W4 Tugas 3, ruling W4-3d, M2).
    const jalurPenjelas = [form.errors.trail_id ? 'trail_id-error' : null, form.errors.hiking_goal_id ? 'hiking_goal_id-error' : null].filter(Boolean).join(' ') || undefined;

    return (
        <div className="py-6">
            <div className="mx-auto max-w-2xl px-4 sm:px-6">
                <PageHeader
                    induk={{ href: tripsIndex.url(), label: 'Trip' }}
                    title="Rencanakan trip"
                    description={jalur === null ? 'Pilih jalur yang akan Anda rencanakan.' : undefined}
                />

                {jalur === null ? (
                    pilihanJalur.length === 0 ? (
                        <EmptyState jenis="pertama" title="Belum ada jalur tersimpan" description="Simpan jalur dari Jelajah, lalu rencanakan tripnya dari sini atau dari rincian jalur.">
                            <Button href={trailsIndex.url()}>Buka Jelajah</Button>
                        </EmptyState>
                    ) : (
                        <section aria-labelledby="dari-simpanan">
                            <h2 id="dari-simpanan" className="text-bagian text-primary">
                                Dari simpanan Anda
                            </h2>
                            <ul className="mt-3 divide-y divide-subtle rounded-lg border border-subtle bg-surface">
                                {pilihanJalur.map((j) => (
                                    <li key={j.href}>
                                        <Tautan
                                            href={j.href}
                                            className="flex min-h-11 items-center justify-between gap-3 px-4 py-3 hover:bg-surface-sunken focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-600"
                                        >
                                            <span className="min-w-0">
                                                <span className="block break-words text-isi font-bold text-primary">{j.nama}</span>
                                                {j.gunung ? <span className="block text-kecil text-secondary">{j.gunung}</span> : null}
                                            </span>
                                            <Icon name="caret-right" size={20} className="text-muted" />
                                        </Tautan>
                                    </li>
                                ))}
                            </ul>
                            <p className="mt-4 text-kecil text-secondary">
                                Jalur lain dapat dipilih dari{' '}
                                <Tautan href={trailsIndex.url()} className="font-bold text-brand-ink underline underline-offset-2">
                                    Jelajah
                                </Tautan>
                                .
                            </p>
                        </section>
                    )
                ) : (
                    <form onSubmit={kirim} className="space-y-8">
                        <LangkahRingkas kini="rencana" tautan={{ jalur: jalur.url }} />

                        <section id="bagian-jalur" tabIndex={-1} aria-labelledby="jalur-rencana" aria-describedby={jalurPenjelas} className="rounded-utama border border-subtle bg-surface p-5">
                            <div className="flex items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <h2 id="jalur-rencana" className="break-words text-subjudul text-primary">
                                        {jalur.nama}
                                    </h2>
                                    <p className="text-kecil text-secondary">{jalur.gunung}</p>
                                    <p data-angka className="mt-1 text-kecil text-secondary">
                                        {jalur.ringkas}
                                    </p>
                                </div>
                                <Tautan href={jalur.ganti} className={tautanTeks}>
                                    Ganti
                                </Tautan>
                            </div>
                            {/* trail_id dan hiking_goal_id tidak punya kontrol sendiri di halaman ini: jalur
                                datang dari ?trail= dan rencana dari ?goal=. Galatnya hanya mungkin bila jalur
                                diarsipkan atau rencana dihapus di antara memuat dan menyimpan halaman, dan tanpa
                                galat ini dirender, "Simpan rencana" gagal tanpa penjelasan (putaran perbaikan 1
                                W4 Tugas 3, ruling W4-3d, M2). */}
                            <InputError id="trail_id-error" messages={form.errors.trail_id} className="mt-2" />
                            <InputError id="hiking_goal_id-error" messages={form.errors.hiking_goal_id} className="mt-2" />

                            {kecocokan ? (
                                <div aria-live="polite" aria-busy={memuat || undefined} className="mt-4 rounded-tombol bg-surface-sunken p-4">
                                    <p className="flex flex-wrap items-center gap-2">
                                        <LabelKecocokan nilai={kecocokan.label} label={kecocokan.labelText} />
                                        {/* Label null (profil belum lengkap) tidak punya dasar untuk disebut "dasar". */}
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
                                    min={tanggalTerawal}
                                    required
                                    value={form.data.planned_date}
                                    onChange={(e) => ubahTanggal(e.target.value)}
                                    error={form.errors.planned_date}
                                />
                                {jenisDipilih && jenisDipilih.lamaHari > 2 ? (
                                    <FormField
                                        name="end_date"
                                        type="date"
                                        label="Tanggal turun"
                                        min={form.data.planned_date || tanggalTerawal}
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
                            ) : (
                                <p className="text-kecil text-secondary">Pilih tanggal naik untuk melihat jendela izin dan jadwal prakiraan BMKG.</p>
                            )}
                            {/* M9 tinjauan Tugas 3: jendela izin dan kalimat BMKG berubah sesudah muat ulang
                                parsial, jadi keduanya di wilayah live yang selalu terpasang. */}
                            <div aria-live="polite" aria-busy={memuat || undefined} className="space-y-4 empty:!mt-0">
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
                            <Sakelar
                                judul="Ingatkan saya"
                                keterangan={jadwalPengingat}
                                nyala={form.data.reminders_enabled}
                                onUbah={(nyala) => form.setData('reminders_enabled', nyala)}
                                className="rounded-lg border border-subtle bg-surface px-4 py-2"
                            />
                        </section>

                        <BarAksi
                            label="Aksi rencana trip"
                            ringkasan={
                                // Nama jalur sudah di kartu atas; bar hanya membawa pilihan yang berubah,
                                // supaya tetap satu baris ringkasan di 360 px.
                                <p data-angka className="break-words">
                                    {jenisDipilih ? <span className="font-bold text-primary">{jenisDipilih.label}</span> : 'Pilih jenis trip'}
                                    {jadwal ? `, ${jadwal.ringkas} (${jadwal.lama})` : ', tanggal belum dipilih'}
                                </p>
                            }
                        >
                            <Button type="submit" processing={form.processing}>
                                Simpan rencana
                            </Button>
                        </BarAksi>
                    </form>
                )}
            </div>
        </div>
    );
}

Create.layout = (page: ReactNode) => <AppLayout bilahTab={false}>{page}</AppLayout>;
