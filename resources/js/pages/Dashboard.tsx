import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { TombolPengingat } from '@/layouts/Pengingat';
import { history, onboarding } from '@/routes';
import { create as goalsCreate } from '@/routes/goals';
import { index as trailsIndex } from '@/routes/trails';
import { index as tripsIndex } from '@/routes/trips';
import type { PropsBersama } from '@/types';

interface Trip {
    id: number;
    name: string;
    trail: string | null;
    mountain: string | null;
    tanggal: string;
    hitungMundur: { angka: number | null; kata: string };
    catatan: { jenis: 'ditarik' | 'basi' | 'terakhir' | 'belum'; label: string | null };
    urls: { hike: string; show: string; readiness: string; preparation: string };
}

interface Props extends PropsBersama {
    keadaan: 'profil' | 'berjalan' | 'terdekat' | 'mulai';
    trip: Trip | null;
}

const tautan = 'inline-flex min-h-11 items-center text-brand-ink underline hover:text-brand-900';

function Lokasi({ trip }: { trip: Trip }) {
    return (
        <p className="text-sm text-secondary">
            {trip.trail}
            {trip.mountain ? `, ${trip.mountain}` : null}
        </p>
    );
}

/**
 * Dasbor yang mengikuti posisi pengguna dalam alur. Urutannya adalah urutan
 * kepentingan, bukan urutan alur: yang paling menentukan hari ini muncul lebih dulu,
 * pendakian yang sedang berjalan, lalu trip terdekat, baru ajakan memulai.
 */
export default function Dashboard() {
    const { auth, keadaan, trip } = usePage<Props>().props;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader
                    akar
                    merek
                    aksi={<TombolPengingat />}
                    title={`Selamat datang, ${auth.user?.name ?? ''}`}
                    description="Alurnya: lengkapi profil, tentukan rencana, lihat kecocokan jalur, siapkan kebutuhan, lalu periksa kondisi sebelum berangkat."
                />

                {keadaan === 'profil' ? (
                    <Card title="Lengkapi profil pendaki" subtitle="Rekomendasi jalur membutuhkan data pengalaman dan preferensi Anda.">
                        <Button href={onboarding.url()}>Isi profil sekarang</Button>
                    </Card>
                ) : keadaan === 'berjalan' && trip ? (
                    <Card title="Pendakian sedang berlangsung" subtitle={trip.name}>
                        <div className="mb-4">
                            <Lokasi trip={trip} />
                        </div>
                        <div className="flex flex-wrap gap-3">
                            <Button href={trip.urls.hike}>Buka mode pendakian</Button>
                            <Button variant="secondary" href={trip.urls.show}>Rincian trip</Button>
                        </div>
                    </Card>
                ) : keadaan === 'terdekat' && trip ? (
                    /*
                     * Pendakian berikutnya adalah hero halaman ini, bukan kartu ketiga:
                     * angka hitung mundurnya dibuat sebesar perannya.
                     */
                    <section className="rounded-lg border border-subtle bg-surface p-6">
                        <p className="text-sm text-secondary">{trip.tanggal}</p>

                        <p className="mt-1 flex flex-wrap items-baseline gap-x-3">
                            {trip.hitungMundur.angka !== null ? (
                                <>
                                    <span data-angka className="text-4xl font-semibold leading-none text-primary">{trip.hitungMundur.angka}</span>
                                    <span className="text-lg text-secondary">{trip.hitungMundur.kata}</span>
                                </>
                            ) : (
                                <span className="text-3xl font-semibold leading-tight text-primary">{trip.hitungMundur.kata}</span>
                            )}
                        </p>

                        <h2 className="mt-4 text-lg font-semibold text-primary">{trip.name}</h2>
                        <Lokasi trip={trip} />

                        {trip.catatan.jenis === 'ditarik' ? (
                            <p className="mt-3 text-sm font-medium text-warn-900">Jalur ditarik dari katalog</p>
                        ) : trip.catatan.jenis === 'basi' ? (
                            <p className="mt-3 text-sm font-medium text-warn-900">Status jalur berubah, perlu dinilai ulang</p>
                        ) : trip.catatan.jenis === 'terakhir' ? (
                            <p className="mt-3 text-sm text-secondary">
                                Pemeriksaan terakhir: <span className="font-medium text-primary">{trip.catatan.label}</span>
                            </p>
                        ) : (
                            <p className="mt-3 text-sm text-secondary">Kesiapan trip ini belum pernah diperiksa.</p>
                        )}

                        <div className="mt-5 flex flex-wrap gap-3">
                            <Button href={trip.urls.readiness}>Cek kesiapan</Button>
                            <Button variant="secondary" href={trip.urls.preparation}>Daftar persiapan</Button>
                        </div>
                    </section>
                ) : (
                    <Card title="Mulai rencana pendakian" subtitle="Tentukan target perjalanan Anda untuk melihat jalur yang sesuai.">
                        <Button href={goalsCreate.url()}>Buat rencana pendakian</Button>
                    </Card>
                )}

                <nav aria-label="Lanjutkan ke" className="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                    <Tautan href={trailsIndex.url()} className={tautan}>Jelajahi jalur</Tautan>
                    <Tautan href={tripsIndex.url()} className={tautan}>Rencana trip saya</Tautan>
                    <Tautan href={history.url()} className={tautan}>Riwayat pendakian</Tautan>
                    {/* Satu-satunya jalan ke ringkasan profil pendaki setelah profilnya lengkap. */}
                    {keadaan !== 'profil' ? <Tautan href={onboarding.url()} className={tautan}>Profil pendaki</Tautan> : null}
                </nav>

                <p className="border-t border-subtle pt-4 text-sm text-muted">
                    Sistem ini membantu pengambilan keputusan pendakian. Sistem tidak menyatakan bahwa suatu gunung atau jalur aman, dan tidak
                    menggantikan informasi resmi dari pengelola.
                </p>
            </div>
        </div>
    );
}

Dashboard.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
