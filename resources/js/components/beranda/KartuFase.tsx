import { useId, type ReactNode } from 'react';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { index as trailsIndex } from '@/routes/trails';
import { AksiUtama } from './AksiUtama';
import { KartuPerjalanan } from './KartuPerjalanan';
import type { KartuFaseData } from './types';

const tautanTeks =
    'inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600';

/**
 * Kartu fase Beranda (spec v3 bagian 6.1): satu kartu per fase, masing-masing dengan tepat satu
 * aksi utama yang dipilih server. Fase B2 punya kartunya sendiri (KartuPerjalanan); fase lain
 * berupa kartu pendek yang menyebut posisi pendaki dan alasannya.
 */
export function KartuFase({ kartu }: { kartu: KartuFaseData }) {
    switch (kartu.jenis) {
        case 'perjalanan':
            return <KartuPerjalanan kartu={kartu} />;
        case 'profil':
            return (
                <KartuPendek judul="Kenali pengalaman Anda">
                    <p className="mt-2 max-w-prose text-isi text-secondary">
                        Label kecocokan jalur dihitung dari profil pendaki yang Anda isi sendiri: tingkat pengalaman, rekam jejak pendakian, dan preferensi perjalanan.
                        Sebelum profil terisi, kecocokan setiap jalur belum dapat dinilai.
                    </p>
                    <AksiUtama aksi={kartu.aksi} />
                    <p className="mt-2 text-kecil text-secondary">
                        Jelajah tetap dapat dibuka sekarang.{' '}
                        <Tautan href={trailsIndex.url()} className={tautanTeks}>
                            Lihat jalur tanpa penilaian
                        </Tautan>
                    </p>
                </KartuPendek>
            );
        case 'mulai':
            return (
                <KartuPendek judul="Rencanakan pendakian berikutnya">
                    <p className="mt-2 max-w-prose text-isi text-secondary">
                        Pilih jalur dari simpanan atau dari Jelajah, lalu atur tanggal, jenis trip, dan peserta. Daftar persiapannya disusun dari jalur yang Anda pilih.
                    </p>
                    <AksiUtama aksi={kartu.aksi} />
                </KartuPendek>
            );
        case 'mendaki':
            return (
                <KartuPendek judul={kartu.posBerikutnya ? `Pos berikutnya: ${kartu.posBerikutnya}` : 'Pos berikutnya belum diketahui'} menonjol>
                    <p className="mt-2 text-isi text-primary">
                        {kartu.posBerikutnya
                            ? 'Perbarui posisi dan lihat peta jalur di mode lapangan.'
                            : 'Pos berikutnya tampil sesudah Anda memperbarui posisi di mode lapangan.'}
                    </p>
                    <AksiUtama aksi={kartu.aksi} />
                </KartuPendek>
            );
        case 'lapor':
            return (
                <KartuPendek judul={kartu.nama}>
                    <p className="mt-1 wrap-break-word text-kecil text-secondary">
                        {kartu.jalur}
                        {kartu.gunung ? `, ${kartu.gunung}` : null}
                    </p>
                    <dl className="mt-3 grid grid-cols-2 gap-3">
                        <div>
                            <dt className="text-meta text-secondary">Hasil</dt>
                            <dd className="text-isi font-bold text-primary">{kartu.hasil}</dd>
                        </div>
                        <div>
                            <dt className="text-meta text-secondary">Selesai</dt>
                            <dd className="text-isi font-bold text-primary">{kartu.selesai}</dd>
                        </div>
                    </dl>
                    <p className="mt-3 max-w-prose text-isi text-secondary">
                        Laporan kondisi Anda membantu pendaki yang berangkat sesudah Anda. Laporan tampil untuk pendaki lain sesudah lolos moderasi dan tidak mengubah status resmi.
                    </p>
                    <AksiUtama aksi={kartu.aksi} />
                    <Tautan href={kartu.ringkasanUrl} className={kelas(tautanTeks, 'mt-2')}>
                        Lihat ringkasan pendakian
                    </Tautan>
                </KartuPendek>
            );
    }
}

function KartuPendek({ judul, menonjol = false, children }: { judul: string; menonjol?: boolean; children: ReactNode }) {
    const idJudul = useId();

    return (
        <section aria-labelledby={idJudul} className={kelas('rounded-utama border p-5 sm:p-6', menonjol ? 'border-brand-300 bg-brand-50' : 'border-subtle bg-surface')}>
            <h2 id={idJudul} className="wrap-break-word text-judul-kartu text-primary">
                {judul}
            </h2>
            {children}
        </section>
    );
}
