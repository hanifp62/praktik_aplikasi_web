import { usePage } from '@inertiajs/react';
import { useId, useState, type ReactNode } from 'react';
import { PemilihBanding, type OpsiBanding } from '@/components/trails/banding/PemilihBanding';
import { TabelBanding, type BarisBanding, type KolomJalur } from '@/components/trails/banding/TabelBanding';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { Checkbox } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { index as trailsIndex, saved } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    pilihan: { maks: number; opsi: OpsiBanding[] };
    jumlahTersimpan: number;
    pesan: string[];
    jalur: KolomJalur[];
    baris: BarisBanding[];
    beda: { fit: boolean | null; status: boolean | null };
    pengumuman: string;
}

/**
 * Banding (FR-06, DK-05): dua sampai tiga jalur berdampingan, tanpa pemenang (R-016).
 *
 * Halaman ini hanya membaca. Tanpa ?trails= yang dibandingkan simpanan terbaru yang masih
 * terbit; dengan ?trails= yang dibandingkan pilihan pembaca. Yang tidak ikut dibandingkan
 * (tidak lagi terbit, melewati batas) disebut lewat `pesan`, tidak dibuang diam-diam.
 * Tombol bagikan baru tampil setelah tautan bagikan FR-23 dibangun (DK-40).
 */
export default function Compare() {
    const { pilihan, jumlahTersimpan, pesan, jalur, baris, beda, pengumuman } = usePage<Props>().props;
    const [sembunyikanSama, setSembunyikanSama] = useState(false);
    const idSaring = useId();

    return (
        <div className="py-8">
            <div className="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Bandingkan Jalur"
                    description="Perbandingan ini membantu Anda melihat perbedaan antar jalur. Sistem tidak menentukan jalur mana yang terbaik."
                >
                    {jumlahTersimpan > 0 ? (
                        <div className="mt-3">
                            <Button variant="ghost" size="sm" className="-ml-4" href={saved.url()}>
                                Lihat daftar rute tersimpan ({jumlahTersimpan})
                            </Button>
                        </div>
                    ) : null}
                </PageHeader>

                {pesan.length > 0 ? (
                    <Alert variant="info">
                        <ul className="space-y-1">
                            {pesan.map((p) => (
                                <li key={p}>{p}</li>
                            ))}
                        </ul>
                    </Alert>
                ) : null}

                {pilihan.opsi.length >= 2 ? <PemilihBanding pilihan={pilihan} urutan={jalur.map((j) => j.id)} /> : null}

                {jalur.length === 0 ? (
                    <Card>
                        <p className="text-sm text-secondary">
                            {pilihan.opsi.length >= 2 ? (
                                <>Belum ada jalur yang dipilih. Centang sampai {pilihan.maks} jalur, lalu tekan Bandingkan.</>
                            ) : (
                                'Perbandingan memakai jalur terbit yang Anda simpan. Simpan minimal dua jalur dari Jelajah lebih dulu.'
                            )}
                        </p>
                        {pilihan.opsi.length >= 2 ? null : (
                            <Button variant="secondary" className="mt-3" href={trailsIndex.url()}>
                                Jelajahi jalur
                            </Button>
                        )}
                    </Card>
                ) : (
                    <>
                        {jalur.length >= 2 ? (
                            <label htmlFor={idSaring} className="inline-flex min-h-11 cursor-pointer items-center gap-2 text-sm text-secondary">
                                <Checkbox id={idSaring} checked={sembunyikanSama} onChange={(e) => setSembunyikanSama(e.target.checked)} />
                                Sembunyikan aspek yang sama
                            </label>
                        ) : null}

                        <TabelBanding jalur={jalur} baris={baris} beda={beda} sembunyikanSama={sembunyikanSama} />
                    </>
                )}

                <p role="status" className="sr-only">{pengumuman}</p>
            </div>
        </div>
    );
}

Compare.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
