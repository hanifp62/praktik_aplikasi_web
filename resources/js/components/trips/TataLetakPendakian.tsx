import { useState, type ReactNode } from 'react';
import { fokusTerlihat, umpanTekan } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { JudulHalaman } from '@/components/ui/JudulHalaman';
import { LencanaDataContoh } from '@/components/ui/LencanaDataContoh';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { usePenjagaPerangkat } from '@/luring/keluar';

export type TemaLapangan = 'terang' | 'gelap';

interface TataLetakPendakianProps {
    /** Ruang kerja trip; null di paket luring, karena halaman lain tidak dapat dibuka tanpa sinyal. */
    kembali: string | null;
    /** Satu h1 dari halaman (PageHeaderContractTest). */
    judul: ReactNode;
    /** Keadaan dan lama berjalan, dibaca sekilas di bawah judul. */
    keterangan: string;
    children: ReactNode;
}

const tautanKembali = kelas('inline-flex h-12 min-w-12 shrink-0 items-center justify-center rounded-full border border-control bg-surface px-3 text-primary', fokusTerlihat, umpanTekan);

/**
 * Tata letak mode lapangan (spec v3 bagian 6.12, prototype 11). Tanpa bilah tab, strip, dan
 * navigasi lima permukaan: di jalur, dengan sarung tangan dan layar berkeringat, setiap tautan
 * yang tidak dibutuhkan adalah sasaran salah sentuh. Yang tersisa jalan kembali ke ruang kerja
 * trip (pendakian tetap berjalan dan dibuka lagi dari strip), judul, dan sakelar tema.
 *
 * Tema lapangan (DK-49) mengikuti tema pengguna lalu dapat dibalik sekali ketuk. Atributnya di
 * akar halaman ini, bukan di <html>, jadi tema aplikasi tidak berubah sesudah keluar dari mode
 * lapangan; warna teks, kursor, dan aksen kontrol diturunkan dari akar ini (app.css).
 */
export function TataLetakPendakian({ kembali, judul, keterangan, children }: TataLetakPendakianProps) {
    const [tema, setTema] = useState<TemaLapangan>(() => (window.temaGelapAktif?.() ? 'gelap' : 'terang'));

    usePenjagaPerangkat();

    return (
        <div data-tema-lapangan={tema} className="min-h-dvh bg-canvas text-primary">
            <JudulHalaman />
            <header className="sticky top-0 z-20 border-b border-subtle bg-surface">
                <div className="mx-auto flex max-w-3xl items-center gap-3 px-4 py-2 sm:px-6">
                    {kembali ? (
                        <Tautan href={kembali} aria-label="Kembali ke detail trip. Pendakian tetap berjalan." className={tautanKembali}>
                            <Icon name="arrow-left" size={22} />
                        </Tautan>
                    ) : null}
                    <div className="min-w-0 flex-1">
                        {judul}
                        <p className="text-kecil text-secondary">{keterangan}</p>
                        <LencanaDataContoh />
                    </div>
                    <button
                        type="button"
                        aria-label={tema === 'gelap' ? 'Ganti ke tema lapangan terang' : 'Ganti ke tema lapangan gelap'}
                        onClick={() => setTema(tema === 'gelap' ? 'terang' : 'gelap')}
                        className={kelas('inline-flex min-h-12 min-w-12 shrink-0 items-center justify-center rounded-full border border-control bg-surface px-3 text-kecil font-bold text-primary', fokusTerlihat, umpanTekan)}
                    >
                        {tema === 'gelap' ? 'Terang' : 'Gelap'}
                    </button>
                </div>
            </header>
            <main id="konten" tabIndex={-1} className="mx-auto max-w-3xl px-4 pb-8 sm:px-6">
                {children}
            </main>
        </div>
    );
}
