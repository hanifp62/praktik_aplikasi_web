import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { update } from '@/routes/trips/reminders';

export interface PengingatHalaman {
    aktif: boolean;
    dapatDiatur: boolean;
    jadwal: string;
    daftar: Array<{
        jenis: string;
        label: string;
        waktu: string;
        keadaan: 'menunggu' | 'terkirim' | 'dilewati';
        keadaanTeks: string;
    }>;
    catatan: string | null;
}

/**
 * Pengingat perjalanan satu trip (FR-24): sakelar surel, jadwal yang berlaku di zona
 * gunungnya, dan keadaan tiap pengingat (menunggu, terkirim, atau dilewati). Semua teks
 * waktu dan keadaan disusun server (TripReminderScheduler::untukHalaman()).
 *
 * Sakelar hanya ditawarkan selama trip belum dimulai; sesudahnya server menolak, jadi
 * tombolnya tidak tampil dan kalimatnya menjelaskan. Isi surel tidak pernah menyatakan
 * jalur atau pendaki aman (R-012).
 */
export function PengingatTrip({ tripId, pengingat }: { tripId: number; pengingat: PengingatHalaman }) {
    const [memproses, setMemproses] = useState(false);

    const ubah = () => {
        setMemproses(true);
        router.put(update.url(tripId), { aktif: !pengingat.aktif }, { preserveScroll: true, onFinish: () => setMemproses(false) });
    };

    return (
        <Card title="Pengingat perjalanan" subtitle={pengingat.jadwal}>
            {pengingat.dapatDiatur ? (
                <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <p className="text-sm text-secondary">{pengingat.aktif ? 'Pengingat surel menyala.' : 'Pengingat surel mati.'}</p>
                    <Button size="sm" variant="secondary" onClick={ubah} processing={memproses}>
                        {pengingat.aktif ? 'Matikan pengingat' : 'Nyalakan pengingat'}
                    </Button>
                </div>
            ) : (
                <p className="text-sm text-secondary">Pengingat tidak dapat diubah lagi: trip ini sudah dimulai, selesai, atau dibatalkan.</p>
            )}

            {pengingat.catatan ? <p className="mt-3 text-sm text-secondary">{pengingat.catatan}</p> : null}

            {pengingat.daftar.length === 0 ? (
                <p className="mt-3 text-sm text-muted">
                    {pengingat.aktif ? 'Belum ada pengingat yang dijadwalkan.' : 'Tidak ada pengingat yang dijadwalkan karena pengingat mati.'}
                </p>
            ) : (
                <ul className="mt-3 divide-y divide-subtle border-y border-subtle">
                    {pengingat.daftar.map((p) => (
                        <li key={p.jenis} className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-3 text-sm">
                            <span className="text-primary">
                                {p.label}
                                <span data-angka className="block text-xs text-muted">
                                    {p.waktu}
                                </span>
                            </span>
                            <span className="text-secondary">{p.keadaanTeks}</span>
                        </li>
                    ))}
                </ul>
            )}
        </Card>
    );
}
