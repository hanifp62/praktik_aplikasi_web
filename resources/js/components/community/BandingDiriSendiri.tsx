import { Lencana } from '@/components/ui/Lencana';
import { Tautan } from '@/components/ui/Tautan';
import { fokusTerlihat } from '@/components/ui/gaya';
import { kelas } from '@/lib/kelas';
import type { TanggaRingkasan } from './ringkasan';

interface BandingDiriSendiriProps {
    tangga: TanggaRingkasan | null;
    /** Kalimat pengganti tangga: pendakian yang berbalik, atau tanjakan jalur yang belum tercatat. */
    banding: string | null;
    pencapaian: string[];
    tingkat: { kalimat: string; url: string };
}

/**
 * Dibanding pendakian Anda sendiri (spec v3 bagian 6.13, prototype 12; R-015): tangga tanjakan
 * dengan ukuran yang sama dengan Beranda, satu kalimat selisih, pencapaian pribadi, dan BR-11.
 * Tanpa pendaki lain, peringkat, atau kecepatan.
 *
 * Satu seri dengan skala sama, jadi tanpa legenda: setiap anak tangga menulis nama dan
 * nilainya, sehingga daftar ini sekaligus tabelnya dan batangnya disembunyikan dari pembaca
 * layar. Pendakian ini dibedakan dengan isian penuh, huruf tebal, dan kata "ini" untuk pembaca
 * layar, bukan warna saja.
 *
 * Kunci baris memakai id jalur, bukan nama (ruling W6-6a, konsisten dengan ruling W5-6a
 * TanggaTanjakan Beranda): trails.name tidak unik, dan nama gunung tampil di baris kedua label
 * untuk disambiguasi. id ditambah penanda ini/lalu karena jalur yang sama dapat muncul dua kali
 * (pendakian sebelumnya dan pendakian ini), sama seperti TanggaTanjakan membedakan rencana/tuntas.
 */
export function BandingDiriSendiri({ tangga, banding, pencapaian, tingkat }: BandingDiriSendiriProps) {
    const puncak = Math.max(1, ...(tangga?.baris.map((b) => b.m) ?? []));

    return (
        <section aria-labelledby="judul-banding">
            <h2 id="judul-banding" className="text-bagian text-primary">
                Dibanding pendakian Anda sendiri
            </h2>
            <p className="mt-1 text-kecil text-secondary">Tangga diukur dari tanjakan jalur, bukan ketinggian puncak.</p>

            <div className="mt-3 rounded-kartu border border-subtle bg-surface p-4 sm:p-5">
                {tangga ? (
                    <>
                        <ol className="grid auto-cols-fr grid-flow-col items-start gap-2 sm:gap-3">
                            {tangga.baris.map((b) => (
                                <li key={`${b.id}-${b.ini ? 'ini' : 'lalu'}`} className="flex min-w-0 flex-col text-center">
                                    {/* Nilai dan batang dalam tinggi tetap, sehingga semua batang berdiri di garis dasar yang sama walau nama jalurnya membungkus. */}
                                    <span className="flex h-36 flex-col justify-end">
                                        <span data-angka className={kelas('text-meta tabular-nums', b.ini ? 'font-bold text-primary' : 'text-secondary')}>
                                            {b.teks}
                                        </span>
                                        <span
                                            aria-hidden="true"
                                            className={kelas('mt-1 block w-full rounded-t-kecil', b.ini ? 'bg-brand-700' : 'bg-brand-200')}
                                            style={{ height: `${Math.max(6, Math.round((b.m / puncak) * 112))}px` }}
                                        />
                                    </span>
                                    <span className={kelas('mt-1.5 break-words text-meta', b.ini ? 'font-bold text-primary' : 'text-secondary')}>
                                        {b.nama}
                                        {b.ini ? <span className="sr-only">, pendakian ini</span> : null}
                                    </span>
                                    {/* Nama gunung mendisambiguasi jalur bernama sama (ruling W6-6a), sama seperti
                                        TanggaTanjakan Beranda; pemisah tekstual sr-only, bukan hanya baris baru visual. */}
                                    <span className="block break-words text-meta text-secondary">
                                        <span className="sr-only">, </span>
                                        {b.gunung}
                                    </span>
                                </li>
                            ))}
                        </ol>
                        <p className="mt-4 text-isi text-primary">{tangga.kalimat}</p>
                    </>
                ) : (
                    <p className="text-isi text-secondary">{banding}</p>
                )}

                {pencapaian.length > 0 ? (
                    <ul aria-label="Pencapaian pribadi" className="mt-3 flex flex-wrap gap-2">
                        {pencapaian.map((p) => (
                            <li key={p}>
                                <Lencana nada="merek" bentuk="pil" ikon="tanjakan">
                                    {p}
                                </Lencana>
                            </li>
                        ))}
                    </ul>
                ) : null}

                <p className="mt-4 border-t border-subtle pt-3 text-kecil text-secondary">
                    {tingkat.kalimat}{' '}
                    {/* Target sentuh 44 px (ruling W6-6l, M-7), sama dengan tautan setara di
                        Beranda (beranda/TanggaTanjakan.tsx): inline-flex min-h-11, bukan tautan
                        sebaris polos yang hanya setinggi hurufnya. */}
                    <Tautan href={tingkat.url} className={kelas('inline-flex min-h-11 items-center font-bold text-brand-ink underline underline-offset-2', fokusTerlihat)}>
                        Buka Profil
                    </Tautan>
                </p>
            </div>
        </section>
    );
}
