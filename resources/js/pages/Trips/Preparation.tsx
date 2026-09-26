import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
import { kelas } from '@/lib/kelas';
import { AppLayout } from '@/layouts/AppLayout';
import { readiness } from '@/routes/trips';
import { regenerate, update } from '@/routes/trips/preparation';
import type { Opsi } from '@/components/trips/types';
import type { PropsBersama } from '@/types';

interface Butir {
    id: number;
    label: string;
    description: string | null;
    kritis: boolean;
    status: string;
}

interface Props extends PropsBersama {
    trip: { id: number; keterangan: string };
    kategori: Array<{ nama: string; items: Butir[] }>;
    statuses: Opsi[];
    kemajuan: { dikonfirmasi: number; berlaku: number; teks: string };
}

/**
 * FR-09 daftar persiapan tiga keadaan. "Belum dikonfirmasi" adalah pernyataan tentang
 * catatan, bukan tuduhan bahwa barangnya tidak ada (BR-10). Kemajuan ditulis sebagai
 * hitungan ("3 dari 8"), bukan persentase dan bukan warna saja.
 */
export default function Preparation() {
    const { trip, kategori, statuses, kemajuan, flash } = usePage<Props>().props;
    const [sementara, setSementara] = useState<Record<number, string>>({});
    const [hanyaBelum, setHanyaBelum] = useState(false);
    const [memperbarui, setMemperbarui] = useState(false);

    const belum = kemajuan.berlaku - kemajuan.dikonfirmasi;

    // Pilihan langsung terlihat sebelum server menjawab; bila ditolak, kembali ke
    // status dari server. async supaya konfirmasi beruntun tidak saling membatalkan.
    const setel = (item: Butir, status: string) => {
        setSementara((s) => ({ ...s, [item.id]: status }));
        router.post(update.url({ trip: trip.id, item: item.id }), { status }, {
            async: true,
            preserveScroll: true,
            preserveState: true,
            onFinish: () =>
                setSementara((s) => {
                    const salinan = { ...s };
                    delete salinan[item.id];
                    return salinan;
                }),
        });
    };

    const perbarui = () => {
        setMemperbarui(true);
        router.post(regenerate.url(trip.id), {}, { preserveScroll: true, onFinish: () => setMemperbarui(false) });
    };

    return (
        <div className="py-8">
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <PageHeader title="Persiapan Pendakian" description={trip.keterangan} />

                {flash.status ? <Alert variant="success" className="mb-6">{flash.status}</Alert> : null}

                <Card className="mb-6">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p className="text-sm text-secondary">Item dikonfirmasi</p>
                            <p className="text-2xl font-semibold text-primary">
                                {kemajuan.berlaku > 0 ? `${kemajuan.dikonfirmasi} dari ${kemajuan.berlaku}` : kemajuan.teks}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button variant="secondary" size="sm" onClick={perbarui} processing={memperbarui}>Perbarui daftar</Button>
                            <Button href={readiness.url(trip.id)}>Cek kesiapan</Button>
                        </div>
                    </div>
                    {kemajuan.berlaku > 0 ? (
                        <div
                            className="mt-3 h-2 w-full overflow-hidden rounded-full bg-surface-sunken"
                            role="progressbar"
                            aria-valuenow={kemajuan.dikonfirmasi}
                            aria-valuemin={0}
                            aria-valuemax={kemajuan.berlaku}
                            aria-valuetext={kemajuan.teks}
                            aria-label="Kemajuan persiapan"
                        >
                            <div className="h-full bg-brand-500" style={{ width: `${(kemajuan.dikonfirmasi / kemajuan.berlaku) * 100}%` }} />
                        </div>
                    ) : null}
                    <p className="mt-3 text-xs text-muted">
                        Status &ldquo;Belum dikonfirmasi&rdquo; berarti item tersebut belum Anda pastikan, bukan berarti perlengkapannya tidak Anda miliki.
                    </p>
                    {belum > 0 ? (
                        <div className="mt-3 flex flex-wrap items-center gap-3 border-t border-subtle pt-3">
                            <p className="text-sm text-secondary">{belum} item belum dikonfirmasi</p>
                            <Button variant="ghost" size="sm" aria-pressed={hanyaBelum} onClick={() => setHanyaBelum((v) => !v)}>
                                {hanyaBelum ? 'Tampilkan semua item' : 'Tampilkan yang belum dikonfirmasi saja'}
                            </Button>
                        </div>
                    ) : null}
                </Card>

                <div className="space-y-6">
                    {kategori.map((k) => {
                        const items = k.items.filter((item) => !hanyaBelum || (sementara[item.id] ?? item.status) === 'NOT_CONFIRMED');

                        if (items.length === 0) {
                            return null;
                        }

                        return (
                            <Card key={k.nama} title={k.nama}>
                                <ul className="space-y-3">
                                    {items.map((item) => {
                                        const aktif = sementara[item.id] ?? item.status;

                                        return (
                                            <li key={item.id} className="rounded-control border border-subtle p-3">
                                                <div className="flex flex-wrap items-start justify-between gap-3">
                                                    <div className="min-w-0">
                                                        <p className="text-sm font-medium text-primary">
                                                            {item.label}
                                                            {item.kritis ? <span className="ml-1 rounded bg-warn-100 px-1.5 py-0.5 text-xs text-warn-900">Kritis</span> : null}
                                                        </p>
                                                        {item.description ? <p className="mt-1 text-sm text-secondary">{item.description}</p> : null}
                                                    </div>

                                                    <fieldset className="flex flex-wrap gap-1">
                                                        <legend className="sr-only">Status untuk {item.label}</legend>
                                                        {statuses.map((s) => {
                                                            const dipilih = aktif === s.value;

                                                            return (
                                                                <button
                                                                    key={s.value}
                                                                    type="button"
                                                                    onClick={() => setel(item, s.value)}
                                                                    aria-pressed={dipilih}
                                                                    className={kelas(
                                                                        'inline-flex min-h-11 items-center gap-1.5 rounded-control border px-3 py-1 text-xs font-medium',
                                                                        // Satu-satunya momen gerak yang diarahkan di aplikasi ini
                                                                        // (DESIGN.md MOTION 1), di interaksi yang paling sering
                                                                        // diulang. Yang bergerak hanya warna dan batasnya; tidak ada
                                                                        // yang muncul dari ketiadaan.
                                                                        'transition-colors duration-150 ease-out motion-reduce:transition-none',
                                                                        'focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-1',
                                                                        dipilih ? 'border-brand-700 bg-brand-700 text-white' : 'border-control text-secondary hover:bg-surface-sunken',
                                                                    )}
                                                                >
                                                                    {/* Bulatan penuh atau kosong: pilihan terbaca tanpa warna. */}
                                                                    <span aria-hidden="true">{dipilih ? '●' : '○'}</span>
                                                                    {s.label}
                                                                </button>
                                                            );
                                                        })}
                                                    </fieldset>
                                                </div>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </Card>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}

Preparation.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
