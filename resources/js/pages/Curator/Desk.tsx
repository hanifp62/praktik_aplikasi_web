import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { DaftarAntrean } from '@/components/kurator/DaftarAntrean';
import { DaftarGunungApi } from '@/components/kurator/DaftarGunungApi';
import { DaftarLaporan } from '@/components/kurator/DaftarLaporan';
import type { AntreanKurator, BadanKurator, BarisGunungApi } from '@/components/kurator/types';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { profile } from '@/routes';
import { create } from '@/routes/curator/statuses';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    badan: BadanKurator[];
    gunungApi: BarisGunungApi[];
    antrean: AntreanKurator;
}

/**
 * Meja kurator (spec v3 bagian 10.7; rencana induk W8 butir 9). Kurator menyalin status dari
 * sumber resmi badannya. Halaman ini menjawab "apa yang menunggu saya": verifikasi orang kedua,
 * tinjau berkala (termasuk status lama yang kedaluwarsa dan kini terbaca Belum diketahui),
 * laporan kesalahan data dari pendaki, catatan yang dipersoalkan, dan draf sendiri. Hanya kawasan badan kurator yang tampil; server
 * memeriksa ulang setiap aksi.
 */
export default function Desk() {
    const { flash, badan, gunungApi, antrean } = usePage<Props>().props;

    return (
        <div className="py-8">
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    induk={{ href: profile.url(), label: 'Profil' }}
                    title="Meja kurator"
                    description="Salin status dari sumber resmi badan Anda. Pembatasan dengan bukti dari pengelola terbit seketika dan menunggu kurator kedua; pembukaan baru terbaca pendaki sesudah kurator kedua memverifikasinya."
                />

                {flash.status ? <Alert variant="success" className="mb-4">{flash.status}</Alert> : null}

                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-kecil font-bold text-primary">Keanggotaan Anda</p>
                        <ul className="mt-1 space-y-0.5 text-kecil text-secondary">
                            {badan.map((b) => (
                                <li key={b.nama}>
                                    {b.nama}: {b.peran}
                                    {b.berlakuSampai ? `, berlaku sampai ${b.berlakuSampai}` : ''}
                                </li>
                            ))}
                        </ul>
                    </div>
                    <Button href={create.url()}>Catat status resmi</Button>
                </div>

                <div className="mt-8 space-y-8">
                    <DaftarAntrean
                        id="antrean-verifikasi"
                        judul="Menunggu verifikasi Anda"
                        keterangan="Catatan kurator lain di kawasan Anda. Periksa bukti dan isinya terhadap sumber sebelum memverifikasi; pencatat tidak dapat memverifikasi catatannya sendiri."
                        baris={antrean.verifikasi}
                        kosong="Tidak ada catatan kurator lain yang menunggu Anda."
                    />
                    <DaftarAntrean
                        id="antrean-tinjau"
                        judul="Tinjau berkala"
                        keterangan="Status yang kedaluwarsa, segera kedaluwarsa, atau lewat jadwal tinjau jenisnya. Status yang kedaluwarsa kini terbaca Belum diketahui, tidak pernah Buka. Cek ulang ke sumbernya, atau catat penggantinya bila keadaannya berubah."
                        baris={antrean.tinjau}
                        kosong="Semua status di kawasan Anda masih dalam jadwal tinjau."
                        aksi={(b) =>
                            b.sasaranKunci ? (
                                <Button variant="secondary" size="sm" href={create.url({ query: { sasaran: b.sasaranKunci } })} aria-label={`Catat pengganti untuk ${b.sasaran}`}>
                                    Catat pengganti
                                </Button>
                            ) : null
                        }
                    />
                    <DaftarLaporan baris={antrean.laporan} />
                    <DaftarAntrean
                        id="antrean-dipersoalkan"
                        judul="Dipersoalkan"
                        keterangan="Catatan yang dinilai tidak cocok dengan sumbernya. Pembatasan tetap terbaca pendaki sampai penggantinya terbit; pembukaan berhenti terbaca."
                        baris={antrean.dipersoalkan}
                        kosong="Tidak ada catatan yang dipersoalkan."
                    />
                    <DaftarAntrean id="antrean-draf" judul="Draf Anda" keterangan="Belum terbaca siapa pun selain Anda." baris={antrean.draf} kosong="Belum ada draf." />
                    <DaftarGunungApi baris={gunungApi} />
                </div>
            </div>
        </div>
    );
}

Desk.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
