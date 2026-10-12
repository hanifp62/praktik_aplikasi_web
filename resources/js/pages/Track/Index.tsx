import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { MulaiPendakian } from '@/components/mendaki/MulaiPendakian';
import { PintuPaket } from '@/components/mendaki/PintuPaket';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { Lencana } from '@/components/ui/Lencana';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { kelas } from '@/lib/kelas';
import { create as goalsCreate } from '@/routes/goals';
import { index as tripsIndex } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface TripHariH {
    id: number;
    nama: string;
    jalur: string;
    gunung: string;
    jenis: string;
    hari: string | null;
    lewat: string | null;
    tanggal: string;
    jam: string | null;
    peserta: string | null;
    cek: string;
    /** Hari ini di zona gunung sudah tanggal rencana atau sesudahnya (KepalaTrip::hariBerangkat()). */
    hariBerangkat: boolean;
    /** Tanggal mulai rencana, misalnya "14 Nov 2026", untuk peringatan konfirmasi mulai (ruling CF-3). */
    tanggalRencana: string;
    urls: { mulai: string; ruangKerja: string; cek: string; lapangan: string };
}

interface TripLain {
    id: number;
    nama: string;
    jalur: string;
    gunung: string;
    tanggal: string;
    hari: string | null;
    lewat: string | null;
    hariBerangkat: boolean;
    tanggalRencana: string;
    urls: { mulai: string; ruangKerja: string };
}

interface Props extends PropsBersama {
    hariH: TripHariH | null;
    lain: TripLain[];
    lokasi: string;
    jejak: { menyala: boolean; kalimat: string; url: string };
    paket: { aktif: boolean; kalimat: string };
    potong: { terpotong: boolean; kalimat: string; url: string };
}

const tautanTeks = 'inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600';

/**
 * Pintu tab Mendaki (spec v3 bagian 6.12; rencana induk W6 butir 1): pratinjau hari-H trip
 * terdekat yang dapat dimulai, dengan keadaan paket luring, cara lokasi dipakai, dan rekam
 * jejak, lalu satu tombol "Mulai pendakian" 66 px. Trip lain yang dapat dimulai di bawahnya.
 */
export default function TrackIndex() {
    const { hariH, lain, lokasi, jejak, paket, potong } = usePage<Props>().props;

    return (
        <div className="py-6">
            <div className="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader akar title="Mendaki" description="Mode lapangan untuk hari pendakian: peta jalur, pos berikutnya, dan angka yang dihitung di perangkat Anda." />

                {hariH === null ? (
                    <EmptyState
                        jenis="pertama"
                        title="Belum ada trip yang dapat dimulai."
                        description="Mode lapangan dibuka dari trip yang sudah direncanakan. Rencanakan trip dulu, lalu kembali ke sini pada hari keberangkatan."
                    >
                        <Button href={tripsIndex.url()}>Buka trip saya</Button>
                        <Button variant="secondary" href={goalsCreate.url()}>Cari jalur yang cocok</Button>
                    </EmptyState>
                ) : (
                    <section aria-labelledby="judul-hari-h" className="rounded-utama border border-subtle bg-surface p-5">
                        <h2 id="judul-hari-h" className="text-judul-kartu text-primary">
                            <Tautan href={hariH.urls.ruangKerja} className="underline underline-offset-2 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600 wrap-anywhere">
                                {hariH.nama}
                            </Tautan>
                        </h2>
                        <p className="text-isi text-secondary">
                            {hariH.jalur}, {hariH.gunung}
                        </p>
                        <p className="flex flex-wrap items-center gap-2 text-kecil text-secondary">
                            <span>{[hariH.hari, hariH.jenis, hariH.tanggal, hariH.jam ? `mulai ${hariH.jam}` : null, hariH.peserta].filter(Boolean).join(', ')}</span>
                            {hariH.lewat ? (
                                <Lencana nada="peringatan" ikon="warning">
                                    {hariH.lewat}
                                </Lencana>
                            ) : null}
                        </p>

                        <dl className="mt-4 space-y-3 border-t border-subtle pt-4">
                            <Butir judul="Paket luring">
                                {paket.kalimat}
                                {paket.aktif ? <PintuPaket tripId={hariH.id} /> : null}
                            </Butir>
                            <Butir judul="Lokasi">{lokasi}</Butir>
                            <Butir judul="Rekam jejak">
                                {jejak.kalimat}{' '}
                                <Tautan href={jejak.url} className={tautanTeks}>
                                    Atur di Profil
                                </Tautan>
                            </Butir>
                            <Butir judul="Cek kesiapan">
                                {hariH.cek}{' '}
                                <Tautan href={hariH.urls.cek} className={tautanTeks}>
                                    Buka cek kesiapan
                                </Tautan>
                            </Butir>
                        </dl>

                        <MulaiPendakian id={hariH.id} nama={hariH.nama} gunung={hariH.gunung} url={hariH.urls.mulai} tanggalRencana={hariH.hariBerangkat ? null : hariH.tanggalRencana} besar />
                        <Tautan href={hariH.urls.lapangan} className={kelas(tautanTeks, 'mt-2')}>
                            Tinjau peta dan pos sebelum mulai
                        </Tautan>
                    </section>
                )}

                {lain.length > 0 ? (
                    <section aria-labelledby="judul-trip-lain" className="space-y-3">
                        <h2 id="judul-trip-lain" className="text-bagian text-primary">
                            Trip lain yang dapat dimulai
                        </h2>
                        <ul className="space-y-3">
                            {lain.map((trip) => (
                                <li key={trip.id} className="rounded-kartu border border-subtle bg-surface p-4">
                                    <Tautan href={trip.urls.ruangKerja} className="inline-flex min-h-11 items-center text-subjudul text-primary underline underline-offset-2 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600">
                                        <span className="min-w-0 wrap-anywhere">{trip.nama}</span>
                                    </Tautan>
                                    <p className="text-kecil text-secondary">{trip.jalur}, {trip.gunung}</p>
                                    <p className="flex flex-wrap items-center gap-2 text-kecil text-secondary">
                                        <span>{[trip.tanggal, trip.hari].filter(Boolean).join(', ')}</span>
                                        {trip.lewat ? (
                                            <Lencana nada="peringatan" ikon="warning">
                                                {trip.lewat}
                                            </Lencana>
                                        ) : null}
                                    </p>
                                    <MulaiPendakian id={trip.id} nama={trip.nama} gunung={trip.gunung} url={trip.urls.mulai} tanggalRencana={trip.hariBerangkat ? null : trip.tanggalRencana} />
                                </li>
                            ))}
                        </ul>
                    </section>
                ) : null}

                {potong.terpotong ? (
                    <p className="text-kecil text-secondary">
                        {potong.kalimat}{' '}
                        <Tautan href={potong.url} className={tautanTeks}>
                            Buka tab Trip
                        </Tautan>
                    </p>
                ) : null}
            </div>
        </div>
    );
}

function Butir({ judul, children }: { judul: string; children: ReactNode }) {
    return (
        <div>
            <dt className="text-kecil font-bold text-primary">{judul}</dt>
            <dd className="text-kecil text-secondary">{children}</dd>
        </div>
    );
}

TrackIndex.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
