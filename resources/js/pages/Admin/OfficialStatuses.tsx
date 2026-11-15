import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { AdminNav } from '@/components/admin/AdminNav';
import { BarisKosong, BingkaiTabel, type Paginasi } from '@/components/admin/Tabel';
import { Alert } from '@/components/ui/Alert';
import { Card } from '@/components/ui/Card';
import { Kesegaran } from '@/components/trails/Kesegaran';
import { PageHeader } from '@/components/ui/PageHeader';
import { Pagination } from '@/components/ui/Pagination';
import { Tautan } from '@/components/ui/Tautan';
import { StatusAkses, type StatusResmi } from '@/components/trails/StatusAkses';
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
import type { PropsBersama } from '@/types';

interface ItemTinjau {
    id: number;
    nama: string;
    status: string;
    statusText: string;
    keterangan: string;
}

interface BarisStatus {
    id: number;
    objek: string;
    cakupan: string;
    status: string;
    statusText: string;
    sumber: string;
    sumberUrl: string | null;
    diverifikasi: string | null;
    berlaku: string;
    dicatatOleh: string;
    keadaan: string;
}

interface Props extends PropsBersama {
    tinjau: { kedaluwarsa: ItemTinjau[]; segera: ItemTinjau[]; basi: ItemTinjau[] };
    records: Paginasi<BarisStatus>;
    badanUrl: string;
}

const kategoriTinjau = [
    ['kedaluwarsa', 'Sudah kedaluwarsa', 'Jalur ini sekarang berjalan tanpa status resmi dan ditampilkan sebagai belum diketahui.'],
    ['segera', 'Akan segera kedaluwarsa', 'Masih sempat diperpanjang sebelum statusnya hilang.'],
    ['basi', 'Lama tidak diverifikasi', 'Catatannya masih berlaku, tetapi sudah lewat jadwal tinjau jenis statusnya.'],
] as const;

/** UNKNOWN tidak punya warna operasional: StatusAkses menggambarnya lingkaran putus-putus. */
const badge = (status: string): StatusResmi => (status === 'OPEN' || status === 'RESTRICTED' || status === 'CLOSED' ? status : null);

/**
 * FR-11 status resmi, halaman admin. Status dicatat kurator di meja kurator sejak W8, tempat aturan
 * dua orang dan aturan terbit asimetris ditegakkan; halaman ini menyajikan antrean tinjau dan
 * riwayat seluruh catatan, termasuk keadaan verifikasinya. Yang perlu ditinjau diletakkan sebelum
 * riwayat: status yang kedaluwarsa membuat jalurnya berjalan tanpa status resmi, dan §95 membuat
 * kemundurannya senyap.
 */
export default function OfficialStatuses() {
    const { flash, tinjau, records, badanUrl } = usePage<Props>().props;
    const adaTinjauan = tinjau.kedaluwarsa.length + tinjau.segera.length + tinjau.basi.length > 0;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
                    title="Kelola Status Resmi"
                    description="Status dapat melekat pada gunung, jalur, atau segmen. Status gunung yang terbuka tidak otomatis berarti semua jalurnya terbuka."
                />

                <AdminNav />

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}

                <p className="mb-6 max-w-prose text-kecil text-secondary">
                    Status resmi dicatat kurator di meja kurator: pembatasan boleh terbit oleh satu kurator dengan bukti dari pengelola, pembukaan wajib diperiksa kurator kedua. Peran kurator diberikan lewat{' '}
                    <Tautan href={badanUrl} className="font-semibold text-brand-ink underline underline-offset-2">
                        keanggotaan badan resmi
                    </Tautan>
                    .
                </p>

                {adaTinjauan ? (
                    <Card className="mb-6" title="Perlu ditinjau">
                        <div className="space-y-5">
                            {kategoriTinjau.map(([kunci, judul, penjelasan]) =>
                                tinjau[kunci].length > 0 ? (
                                    <div key={kunci}>
                                        <h3 className="text-sm font-semibold text-primary">{judul} ({tinjau[kunci].length})</h3>
                                        <p className="mt-0.5 text-xs text-secondary">{penjelasan}</p>
                                        <ul className="mt-2 space-y-1 text-sm">
                                            {tinjau[kunci].map((item) => (
                                                <li key={item.id} className="flex flex-wrap items-center gap-x-2 gap-y-1 text-secondary">
                                                    <span className="font-medium text-primary">{item.nama}</span>
                                                    <StatusAkses nilai={badge(item.status)} label={item.statusText} />
                                                    <span className="text-xs text-secondary">{item.keterangan}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                ) : null,
                            )}
                        </div>
                    </Card>
                ) : null}

                <BingkaiTabel keterangan="Riwayat status resmi" kolom={['Objek', 'Cakupan', 'Status', 'Sumber', 'Berlaku', 'Dicatat oleh']}>
                    {records.data.length === 0 ? (
                        <BarisKosong
                            kolom={6}
                            judul="Belum ada status resmi tercatat"
                            penjelasan="Jalur tanpa status resmi bukan berarti jalur terbuka. Sistem menampilkannya sebagai belum diketahui, dan itu memang keadaan yang sebenarnya."
                        />
                    ) : (
                        records.data.map((r) => (
                            <tr key={r.id} className="border-b border-subtle align-top">
                                <td className="p-4 font-medium text-primary">{r.objek}</td>
                                <td className="p-4 text-secondary">{r.cakupan}</td>
                                <td className="p-4">
                                    <StatusAkses nilai={badge(r.status)} label={r.statusText} />
                                    <span className="mt-1 block text-meta text-muted">{r.keadaan}</span>
                                </td>
                                <td className="p-4 text-secondary">
                                    {r.sumberUrl ? (
                                        <a href={r.sumberUrl} rel="noopener noreferrer" target="_blank" className="text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                                            {r.sumber}
                                            <span className="sr-only"> (membuka tab baru)</span>
                                        </a>
                                    ) : (
                                        r.sumber
                                    )}
                                    <Kesegaran waktu={r.diverifikasi} awalan="Diverifikasi" />
                                </td>
                                <td className="p-4 text-secondary">{r.berlaku}</td>
                                <td className="p-4 text-secondary">{r.dicatatOleh}</td>
                            </tr>
                        ))
                    )}
                </BingkaiTabel>

                <div className="mt-6">
                    <Pagination links={records.links} />
                </div>
            </div>
        </div>
    );
}

OfficialStatuses.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
