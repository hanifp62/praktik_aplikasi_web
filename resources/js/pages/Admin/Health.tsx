import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import { Alert } from '@/components/ui/Alert';
import { Card } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { kelas } from '@/lib/kelas';
import { statuses, trails } from '@/routes/admin';
import type { PropsBersama } from '@/types';

type Keadaan = 'SEHAT' | 'TERLAMBAT' | 'GAGAL' | 'BELUM_PERNAH';

interface Tugas {
    kunci: string;
    judul: string;
    jadwal: string;
    toleransiJam: number;
    terakhirJalan: string;
    terakhirBerhasil: string;
    ringkasan: string | null;
    keadaan: Keadaan;
    keadaanLabel: string;
}

interface JalurBelumLengkap {
    id: number;
    nama: string;
    gunung: string | null;
    terbit: boolean;
    kekurangan: string[];
    geometriUrl: string;
    posUrl: string;
}

interface KesehatanData {
    jalurBelumLengkap: number;
    jalurTerbitBelumLengkap: number;
    tanpaGeometri: number | null;
    statusPerluDitinjau: number;
    daftar: JalurBelumLengkap[];
    sisa: number;
}

interface Props extends PropsBersama {
    perluDiurus: boolean;
    kesehatanData: KesehatanData;
    migrasi: { tersedia: boolean; tertunda: string[] };
    tugas: Tugas[];
    riwayat: Array<{ id: number; task: string; berhasil: boolean; durasi: string | null; waktu: string }>;
}

const warnaKeadaan: Record<Keadaan, string> = {
    SEHAT: 'bg-brand-100 text-brand-900',
    TERLAMBAT: 'bg-warn-100 text-warn-900',
    GAGAL: 'bg-danger-100 text-danger-900',
    BELUM_PERNAH: 'bg-danger-100 text-danger-900',
};

const tautanKecil = 'inline-flex min-h-11 items-center rounded-sm text-sm text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600';

/**
 * Kesehatan tugas terjadwal (§98) dan kesehatan data jalur. Keduanya kegagalan yang
 * tidak menghasilkan galat, jadi yang perlu diurus diletakkan paling atas. Angka data
 * dihitung server dari gerbang publikasi yang sama dengan penerbitan (R-022); geometri
 * yang tidak dapat diperiksa ditulis demikian, bukan nol (R-006).
 */
export default function Health() {
    const { perluDiurus, kesehatanData: data, migrasi, tugas, riwayat } = usePage<Props>().props;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Kesehatan Sistem"
                    description="Tugas terjadwal yang berhenti berjalan tidak menghasilkan galat. Data lamanya tetap di tempatnya dan setiap halaman tetap tampak normal, jadi satu-satunya tanda adalah sesuatu yang tidak terjadi."
                />

                <AdminNav />

                {perluDiurus ? (
                    <Alert variant="warning">
                        Ada tugas terjadwal yang tidak berjalan sebagaimana mestinya. Selama itu berlangsung, prakiraan cuaca berhenti diperbarui, status resmi yang kedaluwarsa berhenti diperiksa, dan pengingat perjalanan berhenti terkirim.
                    </Alert>
                ) : null}

                {/* Migrasi yang belum jalan di basis data ini membuat halaman bertabel baru galat,
                    sementara test (SQLite, selalu lengkap) tetap hijau. R-024, HANDOVER §6. */}
                {!migrasi.tersedia ? (
                    <Alert variant="warning">Tabel riwayat migrasi tidak ditemukan di basis data ini, sehingga status skemanya tidak dapat diperiksa.</Alert>
                ) : migrasi.tertunda.length > 0 ? (
                    <Alert variant="danger">
                        <p className="font-medium">{migrasi.tertunda.length} migrasi belum dijalankan di basis data ini.</p>
                        <p className="mt-1">Halaman yang memakai tabel atau kolom baru akan galat sampai migrasinya dijalankan. Jalankan hanya dengan izin pemilik produk: <code className="font-mono">php artisan migrate --force</code>, lalu buka halaman ini lagi.</p>
                        <ul className="mt-2 list-disc ps-5 font-mono text-xs">
                            {migrasi.tertunda.map((nama) => (
                                <li key={nama} className="break-all">{nama}</li>
                            ))}
                        </ul>
                    </Alert>
                ) : null}

                <Card title="Kesehatan data jalur" subtitle="Jalur yang datanya belum lengkap tidak dapat diterbitkan dan tidak masuk rekomendasi. Angkanya dihitung ulang setiap kali halaman ini dibuka.">
                    <dl className="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                        <div className="rounded-control border border-subtle p-3">
                            <dt className="text-muted">Jalur belum lengkap</dt>
                            <dd className="mt-1 text-2xl font-semibold text-primary">{data.jalurBelumLengkap}</dd>
                            {data.jalurTerbitBelumLengkap > 0 ? (
                                <dd className="mt-1 text-xs font-medium text-warn-900">{data.jalurTerbitBelumLengkap} di antaranya sudah tayang</dd>
                            ) : null}
                        </div>
                        <div className="rounded-control border border-subtle p-3">
                            <dt className="text-muted">Tanpa geometri</dt>
                            {data.tanpaGeometri === null ? (
                                <dd className="mt-1 text-sm text-secondary">Tidak dapat diperiksa: basis data ini tidak mendukung PostGIS.</dd>
                            ) : (
                                <dd className="mt-1 text-2xl font-semibold text-primary">{data.tanpaGeometri}</dd>
                            )}
                        </div>
                        <div className="rounded-control border border-subtle p-3">
                            <dt className="text-muted">Status resmi perlu ditinjau</dt>
                            <dd className="mt-1 text-2xl font-semibold text-primary">{data.statusPerluDitinjau}</dd>
                            {data.statusPerluDitinjau > 0 ? (
                                <dd>
                                    <Tautan href={statuses.url()} className={tautanKecil}>Tinjau status resmi</Tautan>
                                </dd>
                            ) : null}
                        </div>
                    </dl>

                    {data.daftar.length > 0 ? (
                        <>
                            <h3 className="mt-5 text-sm font-semibold text-primary">Jalur yang perlu dilengkapi</h3>
                            <ul className="mt-2 divide-y divide-subtle border-y border-subtle">
                                {data.daftar.map((j) => (
                                    <li key={j.id} className="py-3">
                                        <p className="text-sm">
                                            <span className="font-medium text-primary">{j.nama}</span>
                                            {j.gunung ? <span className="text-secondary"> &middot; {j.gunung}</span> : null}
                                            {j.terbit ? <span className="ml-2 rounded bg-warn-100 px-1.5 py-0.5 text-xs font-medium text-warn-900">Sudah tayang</span> : null}
                                        </p>
                                        <ul className="mt-1 list-disc ps-5 text-sm text-secondary">
                                            {j.kekurangan.map((k) => (
                                                <li key={k}>{k}</li>
                                            ))}
                                        </ul>
                                        <p className="flex flex-wrap gap-x-4">
                                            <Tautan href={j.geometriUrl} className={tautanKecil} aria-label={`Geometri jalur ${j.nama}`}>Geometri jalur</Tautan>
                                            <Tautan href={j.posUrl} className={tautanKecil} aria-label={`Pos jalur ${j.nama}`}>Pos jalur</Tautan>
                                        </p>
                                    </li>
                                ))}
                            </ul>
                            <p className="mt-3 text-sm text-secondary">
                                {data.sisa > 0 ? `Dan ${data.sisa} jalur lainnya. ` : null}
                                Sumber data dan karakteristik jalur dilengkapi di{' '}
                                <Tautan href={trails.url()} className="text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">Kelola Jalur</Tautan>.
                            </p>
                        </>
                    ) : (
                        <p className="mt-4 text-sm text-secondary">Semua jalur aktif memenuhi syarat penerbitan yang dapat diperiksa di sini.</p>
                    )}
                </Card>

                <div className="space-y-4">
                    {tugas.length === 0 ? (
                        <EmptyState
                            jenis="menunggu"
                            title="Tidak ada tugas terjadwal yang terdaftar"
                            menunggu="Menunggu pengembang mendaftarkan tugas terjadwal di kode aplikasi"
                            description="Daftar yang kosong di halaman ini bukan kabar baik. Selama tidak ada tugas terdaftar, prakiraan cuaca tidak pernah diperbarui, status resmi yang kedaluwarsa tidak pernah diperiksa, pengingat perjalanan tidak pernah terkirim, dan tidak ada yang akan memberi tahu."
                        />
                    ) : (
                        tugas.map((b) => (
                            <Card key={b.kunci}>
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h2 className="text-base font-semibold text-primary">{b.judul}</h2>
                                        <p className="mt-1 text-sm text-secondary">{b.jadwal}</p>
                                        <p className="mt-1 font-mono text-xs text-muted">{b.kunci}</p>
                                    </div>
                                    <span className={kelas('rounded-control px-2.5 py-1 text-xs font-medium', warnaKeadaan[b.keadaan])}>{b.keadaanLabel}</span>
                                </div>

                                <dl className="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                                    <div>
                                        <dt className="text-muted">Terakhir berjalan</dt>
                                        <dd className="font-medium text-primary">{b.terakhirJalan}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted">Terakhir berhasil</dt>
                                        <dd className="font-medium text-primary">{b.terakhirBerhasil}</dd>
                                    </div>
                                </dl>

                                {b.ringkasan ? <p className="mt-3 rounded-control bg-danger-50 px-3 py-2 text-sm text-danger-900">{b.ringkasan}</p> : null}

                                {b.keadaan === 'BELUM_PERNAH' ? (
                                    // Penyebab paling sering, dan perbaikannya satu baris.
                                    <p className="mt-3 text-sm text-secondary">
                                        Kalau aplikasi baru dipasang, pastikan scheduler dijalankan:{' '}
                                        <code className="rounded bg-surface-sunken px-1.5 py-0.5 text-xs">php artisan schedule:work</code> saat pengembangan, atau satu entri cron per menit yang memanggil{' '}
                                        <code className="rounded bg-surface-sunken px-1.5 py-0.5 text-xs">schedule:run</code> di server.
                                    </p>
                                ) : null}

                                <p className="mt-3 text-xs text-muted">
                                    Ditandai terlambat bila tidak ada keberhasilan selama {b.toleransiJam} jam. Ambangnya sengaja lebih longgar dari jadwalnya, agar penandanya tidak menyala tanpa ada yang rusak.
                                </p>
                            </Card>
                        ))
                    )}
                </div>

                <Card title="Dua puluh jalan terakhir">
                    {riwayat.length === 0 ? (
                        <p className="text-sm text-secondary">Belum ada satu pun tugas terjadwal yang tercatat berjalan.</p>
                    ) : (
                        riwayat.map((run) => (
                            <div key={run.id} className="flex flex-wrap items-center justify-between gap-2 border-b border-subtle py-2 text-sm">
                                <span className="font-mono text-xs text-secondary">{run.task}</span>
                                <span className="flex items-center gap-3">
                                    <span className={kelas('font-medium', run.berhasil ? 'text-brand-900' : 'text-danger-900')}>{run.berhasil ? 'berhasil' : 'gagal'}</span>
                                    {run.durasi ? <span className="text-secondary">{run.durasi}</span> : null}
                                    <span className="text-secondary">{run.waktu}</span>
                                </span>
                            </div>
                        ))
                    )}
                </Card>
            </div>
        </div>
    );
}

Health.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
