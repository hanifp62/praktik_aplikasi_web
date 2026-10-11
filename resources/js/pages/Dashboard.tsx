import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { JalurUntukAnda } from '@/components/beranda/JalurUntukAnda';
import { KabarDiikuti } from '@/components/beranda/KabarDiikuti';
import { KartuFase } from '@/components/beranda/KartuFase';
import { sapaan } from '@/components/beranda/sapaan';
import { TanggaTanjakan } from '@/components/beranda/TanggaTanjakan';
import { TenggatTerdekat } from '@/components/beranda/TenggatTerdekat';
import type { FaseBeranda, JalurLainData, KabarBerandaData, TanggaData, TenggatBeranda } from '@/components/beranda/types';
import { PageHeader } from '@/components/ui/PageHeader';
import { Tautan } from '@/components/ui/Tautan';
import { AppLayout } from '@/layouts/AppLayout';
import { TombolPengingat } from '@/layouts/Pengingat';
import { kelas } from '@/lib/kelas';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    fase: FaseBeranda;
    tenggat: TenggatBeranda | null;
    kabar: KabarBerandaData | null;
    jalurLain: JalurLainData | null;
    tangga: TanggaData | null;
}

/**
 * Beranda berfase (spec v3 bagian 6.1, DK-45, prototype 01). Server memilih fase dan menyusun
 * isinya (App\Services\Dashboard\RingkasanDasbor dan kelas bagian di sebelahnya); halaman ini
 * hanya memilih tampilan dan tidak memuat aturan domain. Sapaan waktu dihitung dari jam
 * perangkat.
 *
 * Urutan di ponsel mengikuti prototype 01: kartu fase, tenggat, kabar, lalu bagian penemuan.
 * Mulai 1024 px bagian penemuan pindah ke kolom samping 22rem; tanpa isi samping halaman tetap
 * satu kolom selebar 3xl, supaya kartu fase tidak melebar tanpa isi.
 */
export default function Dashboard() {
    const { auth, fase, tenggat, kabar, jalurLain, tangga } = usePage<Props>().props;
    const samping = jalurLain !== null || tangga !== null;

    return (
        <div className="py-6 lg:py-8">
            <div className={kelas('mx-auto max-w-3xl px-4 sm:px-6 lg:px-8', samping && 'lg:max-w-6xl')}>
                <PageHeader akar merek aksi={<TombolPengingat />} title={`${sapaan(new Date())}, ${auth.user?.name ?? ''}`} description={fase.ringkasan} />

                <div className={kelas(samping && 'lg:grid lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:gap-8')}>
                    <div className="space-y-8">
                        <div className="space-y-4">
                            <KartuFase kartu={fase.kartu} />
                            {fase.tripLewat ? (
                                <div className="text-kecil text-secondary">
                                    <p>
                                        Rencana <span className="font-bold text-primary">{fase.tripLewat.nama}</span> ({fase.tripLewat.tanggal}) sudah lewat
                                        tanggalnya tanpa dimulai.
                                    </p>
                                    <Tautan
                                        href={fase.tripLewat.url}
                                        className="inline-flex min-h-11 items-center font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                    >
                                        Ubah tanggal atau batalkan
                                    </Tautan>
                                </div>
                            ) : null}
                        </div>
                        {tenggat ? <TenggatTerdekat tenggat={tenggat} /> : null}
                        {kabar ? <KabarDiikuti kabar={kabar} /> : null}
                    </div>

                    {samping ? (
                        <div className="mt-8 space-y-8 lg:mt-0">
                            {jalurLain ? <JalurUntukAnda data={jalurLain} /> : null}
                            {tangga ? <TanggaTanjakan tangga={tangga} /> : null}
                        </div>
                    ) : null}
                </div>

                <p className="mt-8 border-t border-subtle pt-4 text-kecil text-secondary">
                    Sistem ini membantu pengambilan keputusan pendakian. Sistem tidak menyatakan bahwa suatu gunung atau jalur aman, dan tidak menggantikan
                    informasi resmi dari pengelola.
                </p>
            </div>
        </div>
    );
}

Dashboard.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
