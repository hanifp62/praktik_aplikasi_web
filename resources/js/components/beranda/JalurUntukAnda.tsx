import { AngkaRingkas } from '@/components/trails/AngkaRingkas';
import { GarisProfil } from '@/components/trails/GarisProfil';
import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import { fokusTerlihat } from '@/components/ui/gaya';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';
import { onboarding } from '@/routes';
import { index as trailsIndex } from '@/routes/trails';
import type { JalurLainData } from './types';

const tautanTeks = kelas('inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 hover:text-brand-900', fokusTerlihat);

/**
 * "Jalur lain untuk Anda" (spec v3 bagian 6.1, prototype 01): paling banyak tiga jalur dengan
 * label kecocokan (ikon dan kata), tiga angka, garis profil pada skala yang sama, dan satu
 * alasan; tanpa skor atau persen (R-005). Di ponsel kartunya bergulir menyamping dengan titik
 * henti seperti prototype; mulai 1024 px kartunya berderet di kolom samping. Setiap kartu
 * punya tautan, jadi papan ketik menggulirnya lewat fokus. Daftar gulirnya `relative`, supaya
 * teks sr-only (position absolute) di kartu ikut terpotong di dalamnya dan tidak melebarkan
 * halaman. Status resmi tidak ditulis di sini; ia ada di halaman jalurnya.
 */
export function JalurUntukAnda({ data }: { data: JalurLainData }) {
    return (
        <section aria-labelledby="jalur-lain">
            <h2 id="jalur-lain" className="text-bagian text-primary">
                Jalur lain untuk Anda
            </h2>
            {data.tanpaProfil ? (
                <div className="mt-1">
                    <p className="max-w-prose text-kecil text-secondary">Jalur dinilai menurut profil pendaki Anda, dan profilnya belum terisi.</p>
                    <Tautan href={onboarding.url()} className={tautanTeks}>
                        Isi profil pendaki
                    </Tautan>
                </div>
            ) : data.jalur.length === 0 ? (
                <div className="mt-1">
                    <p className="max-w-prose text-kecil text-secondary">
                        Belum ada jalur lain yang dapat ditawarkan untuk profil Anda. Jelajah memuat semua jalur terbit beserta penilaiannya.
                    </p>
                    <Tautan href={trailsIndex.url()} className={tautanTeks}>
                        Buka Jelajah
                    </Tautan>
                </div>
            ) : (
                <>
                    <p className="mt-1 text-kecil text-secondary">Kecocokan dasar dengan profil Anda, selalu dengan alasannya.</p>
                    <ul className="relative -mx-4 mt-3 flex snap-x snap-mandatory scroll-px-4 gap-3 overflow-x-auto px-4 pb-2 sm:-mx-6 sm:scroll-px-6 sm:px-6 lg:mx-0 lg:flex-col lg:overflow-visible lg:px-0 lg:pb-0">
                        {data.jalur.map((j) => (
                            <li key={j.id} className="w-68 shrink-0 snap-start lg:w-auto">
                                <article aria-labelledby={`jalur-lain-${j.id}`} className="h-full rounded-lg border border-subtle bg-surface p-4">
                                    {j.fit ? <LabelKecocokan nilai={j.fit.label} label={j.fit.labelText} /> : null}
                                    <h3 id={`jalur-lain-${j.id}`} className="mt-2 wrap-break-word text-isi font-bold text-primary">
                                        <Tautan href={j.url} className={kelas('rounded-xs underline-offset-2 hover:underline', fokusTerlihat)}>
                                            {j.name}
                                        </Tautan>
                                    </h3>
                                    {j.mountain ? (
                                        <p className="wrap-break-word text-meta text-secondary">
                                            {j.mountain}
                                            {j.province ? `, ${j.province}` : null}
                                        </p>
                                    ) : null}
                                    <AngkaRingkas jalur={j} className="mt-2 text-secondary" />
                                    {j.garis ? <GarisProfil titik={j.garis} label={j.fit?.label ?? null} className="mt-2" /> : null}
                                    {j.fit ? <p className="mt-2 wrap-break-word text-kecil text-primary">{j.fit.alasan}</p> : null}
                                </article>
                            </li>
                        ))}
                    </ul>
                </>
            )}
        </section>
    );
}
