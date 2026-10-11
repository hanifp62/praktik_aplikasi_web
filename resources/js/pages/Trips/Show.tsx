import { router, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent, type ReactNode } from 'react';
import { FitTrip } from '@/components/trips/FitTrip';
import { KepalaTrip } from '@/components/trips/KepalaTrip';
import { LangkahTrip } from '@/components/trips/LangkahTrip';
import { PengingatTrip, type PengingatHalaman } from '@/components/trips/PengingatTrip';
import { HapusRencana, type KelolaRencana } from '@/components/trips/rencana/HapusRencana';
import type { FitRingkas, KepalaTripData, Langkah, Opsi } from '@/components/trips/types';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { InputError, Label, Select, Textarea } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { cancel, complete, hike, index as tripsIndex, start } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    kepala: KepalaTripData;
    trip: {
        id: number;
        aktif: boolean;
        berlangsung: boolean;
        catatan: string | null;
        /** Hari ini di zona gunung >= tanggal rencana (ruling W4-G1): hanya di sinilah
         *  "Mulai pendakian" memakai variant primary; selain itu secondary. */
        hariBerangkat: boolean;
    };
    langkah: Langkah[];
    fit: FitRingkas | null;
    penilaian: string | null;
    izin: { ditutup: boolean; pesan: string; tautanPemesanan: string | null } | null;
    hasilPendakian: string | null;
    completionStates: Opsi[];
    penolakan: string | null;
    pengingat: PengingatHalaman;
    kelola: KelolaRencana;
}

const tautanTeks =
    'inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';

/**
 * Ruang kerja satu trip (spec v3 bagian 6.9): empat langkah prototype di kolom kiri mulai
 * 1024 px dan di atas isi di ponsel, lalu kecocokan untuk rencana ini, izin, pengingat, dan
 * aksi yang mengubah status trip. Langkah memandu, tidak mengunci.
 */
export default function Show() {
    const { kepala, trip, langkah, fit, penilaian, izin, hasilPendakian, completionStates, penolakan, pengingat, kelola, flash } = usePage<Props>().props;
    const [memulai, setMemulai] = useState(false);
    const [membatalkan, setMembatalkan] = useState(false);
    const selesai = useForm({ completion_state: 'COMPLETED', personal_notes: '' });

    const mulai = () => {
        setMemulai(true);
        router.post(start.url(trip.id), {}, { onFinish: () => setMemulai(false) });
    };

    // Dibatalkan bersifat final (PRD §35), jadi ditanyakan dulu.
    const batalkan = () => {
        if (!window.confirm('Batalkan trip ini? Trip yang dibatalkan tidak dapat dimulai lagi.')) {
            return;
        }
        setMembatalkan(true);
        router.post(cancel.url(trip.id), {}, { preserveScroll: true, onFinish: () => setMembatalkan(false) });
    };

    const tandaiSelesai = (e: FormEvent) => {
        e.preventDefault();
        selesai.post(complete.url(trip.id), { preserveScroll: true });
    };

    return (
        <div className="py-6">
            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: tripsIndex.url(), label: 'Trip' }} title={kepala.nama}>
                    <KepalaTrip kepala={kepala} />
                </PageHeader>

                {/* M7 tinjauan akhir W4 (konvensi DESIGN.md "Umpan balik aksi di wilayah yang selalu
                    terpasang"): kabar dan penolakan aksi ruang kerja dibacakan lewat dua wilayah yang
                    terpasang sejak halaman dimuat; hanya isi dan bingkainya yang muncul. */}
                <p role="status" className={flash.status ? 'mb-6 rounded-kartu border border-brand-300 bg-brand-100 p-4 text-sm text-brand-900' : undefined}>
                    {flash.status ?? ''}
                </p>
                <p role="alert" className={penolakan ? 'mb-6 rounded-kartu border border-warn-300 bg-warn-100 p-4 text-sm font-bold text-warn-900' : undefined}>
                    {penolakan ?? ''}
                </p>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-[18rem_minmax(0,1fr)] lg:items-start">
                    <section aria-labelledby="langkah-trip" className="rounded-lg border border-subtle bg-surface p-5">
                        <h2 id="langkah-trip" className="text-bagian text-primary">
                            Langkah
                        </h2>
                        <div className="mt-4">
                            <LangkahTrip langkah={langkah} />
                        </div>
                    </section>

                    <div className="min-w-0 space-y-6">
                        {fit ? (
                            <Card title="Kecocokan jalur">
                                <FitTrip fit={fit} className="mb-6" />
                                {penilaian ? (
                                    <Tautan href={penilaian} className={tautanTeks}>
                                        Lihat penilaian lengkap
                                    </Tautan>
                                ) : null}
                            </Card>
                        ) : null}

                        {/*
                            Jendela pemesanan bergerak relatif terhadap tanggal yang sudah dipilih.
                            Nadanya mengikuti §43: menyebut aturan dan penyelenggaranya, tidak pernah
                            menyatakan izinnya sudah beres atau belum.
                        */}
                        {izin ? (
                            <Card title="Izin pendakian">
                                <p className={izin.ditutup ? 'text-isi font-bold text-warn-900' : 'text-isi text-secondary'}>{izin.pesan}</p>
                                {izin.tautanPemesanan ? (
                                    <a href={izin.tautanPemesanan} rel="noopener noreferrer" target="_blank" className={tautanTeks}>
                                        Pesan di sistem resmi
                                    </a>
                                ) : null}
                            </Card>
                        ) : null}

                        {/* FR-24. Trip yang sudah lewat tanpa pengingat apa pun tidak mendapat kartu:
                            sakelarnya pasti ditolak server dan daftarnya kosong. */}
                        {pengingat.dapatDiatur || pengingat.daftar.length > 0 ? <PengingatTrip tripId={trip.id} pengingat={pengingat} /> : null}

                        {trip.catatan ? (
                            <Card title="Catatan">
                                <p className="whitespace-pre-line break-words text-isi text-secondary">{trip.catatan}</p>
                            </Card>
                        ) : null}

                        {hasilPendakian ? (
                            <Card title="Pendakian ini sudah selesai">
                                <Button href={hasilPendakian}>Lihat hasil pendakian</Button>
                            </Card>
                        ) : null}

                        {trip.aktif ? (
                            <Card title="Tindakan">
                                <div className="flex flex-wrap gap-3">
                                    {trip.berlangsung ? (
                                        <Button href={hike.url(trip.id)}>Buka mode lapangan</Button>
                                    ) : (
                                        <Button onClick={mulai} processing={memulai} variant={trip.hariBerangkat ? 'primary' : 'secondary'}>
                                            Mulai pendakian
                                        </Button>
                                    )}
                                    <Button variant="secondary" size="sm" onClick={batalkan} processing={membatalkan}>
                                        Batalkan trip
                                    </Button>
                                </div>
                            </Card>
                        ) : null}

                        {/* Server hanya mengizinkan transisi ke selesai dari trip yang
                            berlangsung (TripStatus::allowedTransitions); menampilkan formulir
                            ini untuk trip yang belum dimulai menawarkan aksi yang pasti ditolak,
                            dan pada trip Siap berangkat penolakannya menyebut status yang
                            sengaja disembunyikan kepala trip (R-014). */}
                        {trip.berlangsung ? (
                            <Card title="Selesaikan trip" subtitle="Tandai trip selesai untuk menyimpannya ke riwayat dan melaporkan kondisi jalur.">
                                <form onSubmit={tandaiSelesai} className="space-y-4">
                                    <div>
                                        <Label htmlFor="completion_state">Hasil pendakian</Label>
                                        <Select
                                            id="completion_state"
                                            name="completion_state"
                                            required
                                            value={selesai.data.completion_state}
                                            onChange={(e) => selesai.setData('completion_state', e.target.value)}
                                            aria-invalid={selesai.errors.completion_state ? true : undefined}
                                            aria-describedby={selesai.errors.completion_state ? 'completion_state-error' : undefined}
                                        >
                                            <option value="">Pilih hasil</option>
                                            {completionStates.map((s) => (
                                                <option key={s.value} value={s.value}>
                                                    {s.label}
                                                </option>
                                            ))}
                                        </Select>
                                        <InputError id="completion_state-error" messages={selesai.errors.completion_state} className="mt-2" />
                                    </div>

                                    <div>
                                        <Label htmlFor="personal_notes">Catatan pribadi (opsional)</Label>
                                        <Textarea
                                            id="personal_notes"
                                            name="personal_notes"
                                            rows={3}
                                            value={selesai.data.personal_notes}
                                            onChange={(e) => selesai.setData('personal_notes', e.target.value)}
                                            aria-invalid={selesai.errors.personal_notes ? true : undefined}
                                            aria-describedby={selesai.errors.personal_notes ? 'personal_notes-error' : undefined}
                                        />
                                        <InputError id="personal_notes-error" messages={selesai.errors.personal_notes} className="mt-2" />
                                    </div>

                                    <Button type="submit" processing={selesai.processing}>
                                        Tandai selesai
                                    </Button>
                                </form>
                            </Card>
                        ) : null}

                        <HapusRencana tripId={trip.id} kelola={kelola} />
                    </div>
                </div>
            </div>
        </div>
    );
}

Show.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
