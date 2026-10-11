import { router, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent, type ReactNode } from 'react';
import { FitTrip } from '@/components/trips/FitTrip';
import { LangkahTrip } from '@/components/trips/LangkahTrip';
import { PengingatTrip, type PengingatHalaman } from '@/components/trips/PengingatTrip';
import { HapusRencana, type KelolaRencana } from '@/components/trips/rencana/HapusRencana';
import type { FitRingkas, Langkah, Opsi } from '@/components/trips/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { InputError, Label, Select, Textarea } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { cancel, complete, hike, index as tripsIndex, start } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    trip: {
        id: number;
        name: string;
        trail: string;
        mountain: string;
        tanggal: string;
        sampai: string | null;
        peserta: string | null;
        mulai: string | null;
        tipe: string;
        status: string;
        aktif: boolean;
        berlangsung: boolean;
        catatan: string | null;
    };
    langkah: Langkah[];
    fit: FitRingkas | null;
    izin: { ditutup: boolean; pesan: string; tautanPemesanan: string | null } | null;
    hasilPendakian: string | null;
    completionStates: Opsi[];
    penolakan: string | null;
    pengingat: PengingatHalaman;
    kelola: KelolaRencana;
}

/**
 * Satu trip sebagai langkah berurutan: apa yang sudah selesai dan apa berikutnya
 * (features/04), lalu aksi yang mengubah statusnya. Langkah memandu, tidak mengunci.
 */
export default function Show() {
    const { trip, langkah, fit, izin, hasilPendakian, completionStates, penolakan, pengingat, kelola, flash } = usePage<Props>().props;
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
        <div className="py-8">
            <div className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: tripsIndex.url(), label: 'Trip' }} title={trip.name} description={`${trip.trail} - ${trip.mountain}`}>
                    <p className="mt-1 text-sm text-secondary">
                        {trip.tanggal}
                        {trip.sampai ? <> - {trip.sampai}</> : null}
                        {trip.mulai ? <> &middot; mulai {trip.mulai}</> : null} &middot; {trip.tipe}
                        {trip.peserta ? <> &middot; {trip.peserta}</> : null}
                    </p>
                    <span className="mt-2 inline-block rounded-control bg-surface-sunken px-2.5 py-1 text-xs font-medium text-secondary">{trip.status}</span>
                </PageHeader>

                {fit ? <FitTrip fit={fit} className="mb-6" /> : null}

                {flash.status ? <Alert variant="success">{flash.status}</Alert> : null}
                {penolakan ? <Alert variant="warning">{penolakan}</Alert> : null}

                <Card title="Langkah perjalanan">
                    <LangkahTrip langkah={langkah} />
                </Card>

                {/*
                    Jendela pemesanan bergerak relatif terhadap tanggal yang sudah dipilih.
                    Nadanya mengikuti §43: menyebut aturan dan penyelenggaranya, tidak pernah
                    menyatakan izinnya sudah aman atau belum.
                */}
                {izin ? (
                    <Card title="Izin pendakian">
                        <p className={izin.ditutup ? 'text-sm font-medium text-warn-900' : 'text-sm text-secondary'}>{izin.pesan}</p>
                        {izin.tautanPemesanan ? (
                            <a
                                href={izin.tautanPemesanan}
                                rel="noopener noreferrer"
                                target="_blank"
                                className="mt-2 inline-flex min-h-11 items-center text-sm text-brand-ink underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                            >
                                Buka kanal pemesanan resmi
                            </a>
                        ) : null}
                    </Card>
                ) : null}

                {/* FR-24. Trip yang sudah lewat tanpa pengingat apa pun tidak mendapat kartu:
                    sakelarnya pasti ditolak server dan daftarnya kosong. */}
                {pengingat.dapatDiatur || pengingat.daftar.length > 0 ? <PengingatTrip tripId={trip.id} pengingat={pengingat} /> : null}

                {trip.catatan ? (
                    <Card title="Catatan">
                        <p className="text-sm text-secondary">{trip.catatan}</p>
                    </Card>
                ) : null}

                {hasilPendakian ? (
                    <Card title="Pendakian ini sudah selesai">
                        <Button href={hasilPendakian}>Lihat hasil pendakian</Button>
                    </Card>
                ) : null}

                {trip.aktif ? (
                    <>
                        <Card title="Tindakan">
                            <div className="flex flex-wrap gap-3">
                                {trip.berlangsung ? (
                                    <Button href={hike.url(trip.id)}>Buka hike mode</Button>
                                ) : (
                                    <Button onClick={mulai} processing={memulai}>Mulai hike mode</Button>
                                )}
                                <Button variant="secondary" size="sm" onClick={batalkan} processing={membatalkan}>Batalkan trip</Button>
                            </div>
                        </Card>

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
                                            <option key={s.value} value={s.value}>{s.label}</option>
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

                                <Button type="submit" processing={selesai.processing}>Tandai selesai</Button>
                            </form>
                        </Card>
                    </>
                ) : null}

                <HapusRencana tripId={trip.id} kelola={kelola} />
            </div>
        </div>
    );
}

Show.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
