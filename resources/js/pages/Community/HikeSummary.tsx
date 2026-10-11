import { router, usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { PetaGaris } from '@/components/community/PetaGaris';
import type { GarisGeoJson } from '@/components/community/types';
import { ProfilElevasi } from '@/components/trails/ProfilElevasi';
import { DaftarPos } from '@/components/trails/rinci/DaftarPos';
import type { PosJalur, ProfilElevasiData } from '@/components/trails/rinci/types';
import type { Penanda } from '@/components/trails/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { history, progress } from '@/routes';
import { destroy } from '@/routes/hike/track';
import { show as trailShow } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    jalur: { nama: string; slug: string; wilayah: string };
    tanggal: string;
    hasil: string;
    angka: { durasi: string; posTerjauh: string; elevasi: string; persiapan: string };
    jejak: { geometri: GarisGeoJson; sesi: number } | null;
    garisJalur: GarisGeoJson | null;
    penandaPos: Penanda[];
    profil: ProfilElevasiData | null;
    pos: PosJalur[];
    catatan: string | null;
}

/**
 * Hasil satu pendakian. Nadanya mencatat, bukan menilai: pendakian yang dibatalkan karena
 * cuaca adalah keputusan yang benar dan tidak boleh terbaca seperti kegagalan.
 *
 * Jejak pendaki dan garis jalur resmi tidak pernah tertukar. Jejak yang mirip jalur
 * resmi membuat pendaki mengira aplikasi ini memverifikasi bahwa ia berjalan di jalur
 * yang benar, dan aplikasi ini tidak melakukan itu.
 */
export default function HikeSummary() {
    const { flash, jalur, tanggal, hasil, angka, jejak, garisJalur, penandaPos, profil, pos, catatan } = usePage<Props>().props;
    const [menghapus, setMenghapus] = useState(false);

    // Hak menghapus berada di tempat jejaknya terlihat. Rute dan policy-nya milik
    // HikeTrackController, sama dengan versi Livewire.
    const hapusJejak = () => {
        if (!jejak || !window.confirm('Hapus jejak pendakian ini? Pendakiannya sendiri tetap tersimpan.')) {
            return;
        }

        router.delete(destroy.url(jejak.sesi), {
            preserveScroll: true,
            onStart: () => setMenghapus(true),
            onFinish: () => setMenghapus(false),
        });
    };

    return (
        <div className="py-8">
            <div className="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader induk={{ href: progress.url(), label: 'Progres' }} title={jalur.nama} description={jalur.wilayah || undefined}>
                    <p className="mt-1 text-sm text-secondary">{tanggal}</p>
                    <span className="mt-2 inline-block rounded-control bg-surface-sunken px-2.5 py-1 text-sm font-medium text-secondary">{hasil}</span>
                </PageHeader>

                {flash.status ? <Alert variant="success">{flash.status}</Alert> : null}

                {/* Satu kelompok angka, bukan empat kartu setara yang tidak memilih apa pun. */}
                <dl className="grid grid-cols-2 gap-x-8 gap-y-6 border-y border-subtle py-6 sm:grid-cols-4">
                    <div>
                        <dt className="text-sm text-muted">Lama pendakian</dt>
                        <dd data-angka className="mt-1 text-xl font-semibold text-primary">{angka.durasi}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted">Pos terjauh</dt>
                        <dd className="mt-1 text-xl font-semibold text-primary">{angka.posTerjauh}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted">Elevation gain jalur</dt>
                        <dd data-angka className="mt-1 text-xl font-semibold text-primary">{angka.elevasi}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted">Persiapan terkonfirmasi</dt>
                        <dd data-angka className="mt-1 text-xl font-semibold text-primary">{angka.persiapan}</dd>
                    </div>
                </dl>

                {jejak ? (
                    <Card title="Jejak yang Anda tempuh" subtitle="Rekaman dari perangkat Anda, bukan penilaian apakah Anda berjalan di jalur yang benar.">
                        <PetaGaris id="peta-jejak" geometry={jejak.geometri} markers={penandaPos} label={`Jejak pendakian Anda di ${jalur.nama}`} height="h-96" />
                        <Button variant="secondary" size="sm" className="mt-4" onClick={hapusJejak} processing={menghapus}>
                            Hapus jejak ini
                        </Button>
                    </Card>
                ) : null}

                {garisJalur ? (
                    <Card title="Jalur yang ditempuh" subtitle="Garis ini adalah jalur resmi, bukan rekaman GPS perjalanan Anda.">
                        <PetaGaris id="peta-hasil" geometry={garisJalur} markers={penandaPos} label={`Peta jalur ${jalur.nama}`} />
                    </Card>
                ) : penandaPos.length > 0 ? (
                    <Card title="Pos di jalur ini">
                        <PetaGaris id="peta-hasil" markers={penandaPos} label={`Peta pos jalur ${jalur.nama}`} />
                    </Card>
                ) : null}

                {profil ? (
                    <Card title="Profil elevasi">
                        <ProfilElevasi profil={profil} />
                    </Card>
                ) : null}

                {pos.length > 0 ? (
                    <Card title="Pos yang dilalui">
                        <DaftarPos pos={pos} />
                    </Card>
                ) : null}

                {catatan ? (
                    <Card title="Catatan Anda">
                        <p className="text-sm text-secondary">{catatan}</p>
                    </Card>
                ) : null}

                <div className="flex flex-wrap gap-3">
                    <Button variant="secondary" href={history.url()}>Kembali ke riwayat</Button>
                    <Button variant="secondary" href={trailShow.url({ trail: jalur.slug })}>Buka halaman jalur</Button>
                </div>
            </div>
        </div>
    );
}

HikeSummary.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
