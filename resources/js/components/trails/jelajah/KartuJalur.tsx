import { useId, useState } from 'react';
import { BarisStatus } from '@/components/trails/BarisStatus';
import { GarisProfil } from '@/components/trails/GarisProfil';
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import { TombolSimpan } from '@/components/trails/TombolSimpan';
import type { KartuJalur as DataKartu } from '@/components/trails/types';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Icon } from '@/components/ui/Icon';
import { Tautan } from '@/components/ui/Tautan';
import type { LayananKerangka } from '@/layouts/Kerangka';
import { kelas } from '@/lib/kelas';
import { fit as ruteKecocokan } from '@/routes/trails';

interface KartuJalurProps {
    jalur: DataKartu;
    /** Kata UNKNOWN dari server, untuk pembaca yang profilnya belum lengkap. */
    kataBelumDinilai: string;
    /** Disorot dari peta atau saat penunjuk berada di atasnya. */
    disorot?: boolean;
    /** Diteruskan ke TombolSimpan: toast Urungkan milik halaman, bukan milik kartu. */
    tampilkanToast: LayananKerangka['tampilkanToast'];
}

/**
 * Kartu jalur Jelajah (spec v3 bagian 6.2, prototype 02 dan 15), urut pengungkapan PRD §89:
 * label kecocokan (ikon dan kata) dan simpan, nama, gunung dan wilayahnya, tiga angka, garis
 * profil pada skala yang sama dengan kartu lain, satu alasan, status resmi beserta asalnya,
 * lalu "Kenapa?" yang membuka alasan lengkapnya di tempat.
 *
 * Seluruh kartu menjadi sasaran tautan lewat after:absolute pada nama jalur; kontrol lain
 * (simpan, gunung, Kenapa?) berdiri di atas lapisan itu (relative z-10). Setiap kartu
 * membawa label: satu dari tiga label publik, atau "Belum dinilai" dari server bila
 * kecocokannya tidak dihitung (profil belum diisi, data kritis belum lengkap, atau jalur
 * tidak lolos syarat dasar). Pada jalur yang tidak lolos, alasannya tertulis di bawah angka,
 * jadi "Belum dinilai" tidak pernah terbaca sebagai nilai rendah (R-004, R-006, R-008).
 */
export function KartuJalur({ jalur, kataBelumDinilai, disorot = false, tampilkanToast }: KartuJalurProps) {
    const [terbuka, setTerbuka] = useState(false);
    const idAlasan = useId();
    const fit = jalur.fit;
    const layak = fit === null || fit.layak;

    return (
        <article
            id={`jalur-${jalur.id}`}
            className={kelas(
                'group relative rounded-lg border bg-surface p-4 transition-colors duration-sentuh sm:p-5',
                disorot ? 'border-brand-ink' : 'border-subtle',
                jalur.tersembunyiOleh !== null && 'opacity-75',
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 pt-2.5">
                    <LabelKecocokan nilai={fit?.label ?? null} label={fit?.labelText ?? kataBelumDinilai} />
                </div>
                <TombolSimpan jalur={jalur} tampilkanToast={tampilkanToast} />
            </div>

            <h2 className="mt-1 text-subjudul text-primary">
                <Tautan href={jalur.url} className="rounded-sm after:absolute after:inset-0 after:rounded-lg group-hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2">
                    {jalur.name}
                </Tautan>
            </h2>
            <p className="mt-0.5 text-meta text-secondary">
                {jalur.gunungUrl ? (
                    <Tautan href={jalur.gunungUrl} className="relative z-10 underline decoration-subtle underline-offset-2 hover:decoration-current focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                        {jalur.mountain}
                    </Tautan>
                ) : (
                    jalur.mountain
                )}
                {jalur.province ? `, ${jalur.province}` : null}
            </p>

            <dl className="mt-3 grid grid-cols-2 gap-2 min-[360px]:grid-cols-3">
                {[
                    ['Jarak', jalur.jarak],
                    ['Tanjakan', jalur.tanjakan === '-' ? '-' : `↑${jalur.tanjakan}`],
                    ['Naik', jalur.durasi],
                ].map(([judul, nilai]) => (
                    <div key={judul}>
                        <dt className="text-meta text-secondary">{judul}</dt>
                        <dd data-angka="" className="text-isi font-bold text-primary">
                            {nilai}
                        </dd>
                    </div>
                ))}
            </dl>

            {jalur.garis ? <GarisProfil titik={jalur.garis} label={fit?.label ?? null} className="mt-3" /> : null}

            {fit ? (
                <p className={kelas('mt-3 text-kecil', layak ? 'text-primary' : 'text-warn-900')} data-fit-reason="">
                    {fit.alasan}
                </p>
            ) : null}

            <div className="mt-3 flex flex-wrap items-center justify-between gap-2">
                <BarisStatus status={jalur.status} />
                {fit && layak ? (
                    // Nama jalur ikut dibacakan supaya daftar tombol tidak terdengar "Kenapa?" berulang
                    // tanpa konteks, dan aria-controls hanya menunjuk panel yang ada (T4-M8).
                    <button
                        type="button"
                        aria-expanded={terbuka}
                        aria-controls={terbuka ? idAlasan : undefined}
                        onClick={() => setTerbuka((v) => !v)}
                        className={kelas('relative z-10 inline-flex min-h-11 items-center gap-1 rounded-tombol border border-control px-3 text-kecil font-bold text-primary hover:bg-surface-sunken', fokusTerlihat)}
                    >
                        Kenapa?<span className="sr-only"> {jalur.name}</span>
                        <Icon name="caret-down" size={16} className={kelas('transition-transform duration-cepat ease-gerak', terbuka && 'rotate-180')} />
                    </button>
                ) : null}
            </div>

            {jalur.tersembunyiOleh !== null ? <p className="mt-2 text-meta text-muted">Tidak lolos saringan {jalur.tersembunyiOleh}; tampil karena Anda memilih Tampilkan juga.</p> : null}

            {fit && terbuka ? (
                <div id={idAlasan} className="relative z-10 mt-3 space-y-3 border-t border-subtle pt-3">
                    {fit.mengapaSesuai.length > 0 ? (
                        <div>
                            <h3 className="text-meta font-bold text-fit-cocok-teks">Mengapa sesuai</h3>
                            <ul className="mt-1 space-y-1">
                                {fit.mengapaSesuai.map((baris) => (
                                    <li key={baris} className="flex gap-2 text-kecil text-primary">
                                        <Icon name="check" size={16} className="mt-0.5 text-fit-cocok-teks" />
                                        <span className="min-w-0">{baris}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ) : null}
                    {fit.perluDiperhatikan.length > 0 ? (
                        <div>
                            <h3 className="text-meta font-bold text-fit-perlu-teks">Yang perlu diperhatikan</h3>
                            <ul className="mt-1 space-y-1">
                                {fit.perluDiperhatikan.map((baris) => (
                                    <li key={baris} className="flex gap-2 text-kecil text-primary">
                                        <Icon name="warning" size={16} className="mt-0.5 text-fit-perlu-teks" />
                                        <span className="min-w-0">{baris}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ) : null}
                    <Tautan href={ruteKecocokan.url({ trail: jalur.slug })} className="inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                        Lihat penilaian lengkap
                    </Tautan>
                </div>
            ) : null}
        </article>
    );
}
