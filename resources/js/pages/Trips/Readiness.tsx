import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { Freshness } from '@/components/ui/Freshness';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatusBadge, type StatusResmi } from '@/components/ui/StatusBadge';
import { kelas } from '@/lib/kelas';
import { AppLayout } from '@/layouts/AppLayout';
import { preparation, show } from '@/routes/trips';
import { confirm, recompute } from '@/routes/trips/readiness';
import type { PropsBersama } from '@/types';

interface Cuaca {
    tersedia: boolean;
    pesan?: string;
    area?: string;
    sumber?: string;
    diperbarui?: string | null;
    kesegaran?: string | null;
}

interface Props extends PropsBersama {
    trip: { id: number; keterangan: string };
    check: {
        keadaan: 'READY' | 'NEEDS_PREPARATION' | 'NOT_RECOMMENDED' | null;
        label: string;
        dihitung: string | null;
        kecocokan: string;
        persiapan: string;
        kritisBelum: number;
        statusResmi: { status: StatusResmi; label: string; sumber: string };
        penjelasan: Array<{ judul: string; baris: string[] }>;
        cuaca: Cuaca;
    };
    bolehKonfirmasi: boolean;
    preDeparture: { dikonfirmasi: boolean; dicatat: string | null };
    penolakan: string | null;
}

const gayaKeadaan: Record<string, string> = {
    READY: 'border-brand-300 bg-brand-50 text-brand-900',
    NEEDS_PREPARATION: 'border-warn-300 bg-warn-50 text-warn-900',
    NOT_RECOMMENDED: 'border-danger-300 bg-danger-50 text-danger-900',
};

/**
 * FR-10 kesiapan: kecocokan jalur, persiapan, dan kondisi terkini (PRD §38-39).
 * Dukungan keputusan, bukan izin keselamatan maupun izin medis (R-014): halaman ini
 * tidak pernah menyatakan sebuah pendakian aman, dan tidak menampilkan skor.
 */
export default function Readiness() {
    const { trip, check, bolehKonfirmasi, preDeparture, penolakan, flash } = usePage<Props>().props;
    const [menghitung, setMenghitung] = useState(false);
    const [mengonfirmasi, setMengonfirmasi] = useState(false);

    const hitungUlang = () => {
        setMenghitung(true);
        router.post(recompute.url(trip.id), {}, { preserveScroll: true, onFinish: () => setMenghitung(false) });
    };

    const konfirmasi = () => {
        setMengonfirmasi(true);
        router.post(confirm.url(trip.id), {}, { preserveScroll: true, onFinish: () => setMengonfirmasi(false) });
    };

    return (
        <div className="py-8">
            <div className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader title="Kesiapan Pendakian" description={trip.keterangan} />

                {/* Penolakan tidak memakai alert hijau: kotak berhasil yang berisi kabar
                    gagal adalah pesan yang membantah dirinya sendiri. */}
                {penolakan ? <Alert variant="warning">{penolakan}</Alert> : null}

                {flash.status ? <Alert variant="success">{flash.status}</Alert> : null}

                {preDeparture.dikonfirmasi ? (
                    <Alert variant="success" title="Pre-departure check sudah dikonfirmasi">
                        Dicatat {preDeparture.dicatat}. Kondisi di bawah tetap dihitung ulang setiap halaman dibuka, jadi periksa kembali sebelum berangkat.
                    </Alert>
                ) : null}

                <section className={kelas('rounded-lg border p-5', (check.keadaan && gayaKeadaan[check.keadaan]) || 'border-subtle bg-surface-sunken text-primary')}>
                    <h2 className="text-lg font-semibold">{check.label}</h2>
                    <p className="mt-1 text-sm">
                        Penilaian ini menggabungkan kecocokan jalur, kelengkapan persiapan, dan kondisi terkini. Sistem tidak menyatakan bahwa suatu pendakian aman.
                    </p>
                    {/* §93: jam dibaca dalam zona gunungnya, lengkap penandanya. */}
                    {check.dihitung ? <p className="mt-2 text-xs">Dihitung {check.dihitung}</p> : null}
                </section>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <Card title="Kecocokan jalur">
                        <p className="text-sm text-primary">{check.kecocokan}</p>
                    </Card>

                    <Card title="Persiapan">
                        <p className="text-sm text-primary">{check.persiapan}</p>
                        <p className="mt-1 text-xs text-secondary">{check.kritisBelum} item kritis belum dikonfirmasi</p>
                    </Card>

                    <Card title="Status resmi">
                        <StatusBadge status={check.statusResmi.status} text={check.statusResmi.label} />
                        <p className="mt-2 text-xs text-secondary">Sumber: {check.statusResmi.sumber}</p>
                    </Card>
                </div>

                <Card title="Penjelasan">
                    {check.penjelasan.map((kelompok) => (
                        <div key={kelompok.judul} className="mb-3">
                            <h3 className="text-sm font-semibold text-primary">{kelompok.judul}</h3>
                            <ul className="mt-1 list-disc space-y-1 pl-5 text-sm text-secondary">
                                {kelompok.baris.map((baris) => (
                                    <li key={baris}>{baris}</li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </Card>

                <Card title="Cuaca area sekitar jalur">
                    {check.cuaca.tersedia ? (
                        <>
                            <p className="text-sm text-secondary">Area referensi: {check.cuaca.area}</p>
                            <p className="mt-1 text-xs text-muted">Sumber: {check.cuaca.sumber}</p>
                            <Freshness rendered={check.cuaca.diperbarui ?? null} stateLabel={check.cuaca.kesegaran} />
                        </>
                    ) : (
                        <p className="text-sm text-secondary">{check.cuaca.pesan}</p>
                    )}
                </Card>

                <div className="flex flex-wrap gap-3">
                    <Button variant="secondary" size="sm" onClick={hitungUlang} processing={menghitung}>Hitung ulang</Button>
                    <Button variant="secondary" href={preparation.url(trip.id)}>Kembali ke persiapan</Button>
                    {bolehKonfirmasi ? (
                        <Button onClick={konfirmasi} processing={mengonfirmasi}>
                            {preDeparture.dikonfirmasi ? 'Konfirmasi ulang pre-departure check' : 'Konfirmasi pre-departure check'}
                        </Button>
                    ) : null}
                    <Button variant="secondary" href={show.url(trip.id)}>Detail trip</Button>
                </div>
            </div>
        </div>
    );
}

Readiness.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
