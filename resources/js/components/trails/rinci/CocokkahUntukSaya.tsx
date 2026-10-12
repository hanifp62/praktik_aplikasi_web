import { LabelKecocokan } from '@/components/trails/LabelKecocokan';
import { Button } from '@/components/ui/Button';
import { Icon, type NamaIkon } from '@/components/ui/Icon';
import { Tautan } from '@/components/ui/Tautan';
import { onboarding } from '@/routes';
import type { KecocokanRinci } from './types';

function Daftar({ judul, butir, ikon, warna }: { judul: string; butir: string[]; ikon: NamaIkon; warna: string }) {
    if (butir.length === 0) {
        return null;
    }

    return (
        <div>
            <h3 className={`text-meta font-bold ${warna}`}>{judul}</h3>
            <ul className="mt-1 space-y-1">
                {butir.map((b) => (
                    <li key={b} className="flex gap-2 text-kecil text-primary">
                        <Icon name={ikon} size={16} className={`mt-0.5 shrink-0 ${warna}`} />
                        <span className="min-w-0">{b}</span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

/**
 * "Cocokkah untuk saya?" (spec v3 bagian 6.4, prototype 04): label dan definisinya, untuk
 * rencana mana penilaian ini dibuat, satu alasan, lalu mengapa sesuai, yang perlu
 * diperhatikan, dan celah persiapan, semuanya kalimat mesin yang sama dengan halaman
 * rekomendasi. Ketujuh faktor ada di halaman penilaian kecocokan. Tanpa profil, yang
 * ditawarkan jalan mengisinya, bukan penilaian diam-diam.
 *
 * R-005 dan R-014: tanpa skor, dan kalimat penutupnya menyebut ini dukungan keputusan.
 */
export function CocokkahUntukSaya({ kecocokan, idJudul }: { kecocokan: KecocokanRinci | null; idJudul: string }) {
    return (
        <section aria-labelledby={idJudul} className="space-y-3 rounded-lg border border-subtle bg-surface p-4">
            <h2 id={idJudul} className="text-bagian text-primary">
                Cocokkah untuk saya?
            </h2>
            {kecocokan ? (
                <>
                    <div className="flex flex-wrap items-center gap-2">
                        <LabelKecocokan nilai={kecocokan.label} label={kecocokan.labelText} />
                        <span className="text-meta text-secondary">{kecocokan.rencana ? `Untuk rencana ini: ${kecocokan.rencana}` : 'Kecocokan dasar dengan profil Anda'}</span>
                    </div>
                    {kecocokan.deskripsi ? <p className="text-kecil text-secondary">{kecocokan.deskripsi}</p> : null}
                    <p className="text-isi text-primary">{kecocokan.alasan}</p>
                    <Daftar judul="Mengapa sesuai" butir={kecocokan.mengapaSesuai} ikon="check" warna="text-fit-cocok-teks" />
                    <Daftar judul="Yang perlu diperhatikan" butir={kecocokan.perluDiperhatikan} ikon="warning" warna="text-fit-perlu-teks" />
                    <Daftar judul="Celah persiapan" butir={kecocokan.celahPersiapan} ikon="backpack" warna="text-secondary" />
                    {kecocokan.tangga ? <p className="text-meta text-muted">{kecocokan.tangga}</p> : null}
                    <Tautan href={kecocokan.penilaianUrl} className="inline-flex min-h-11 items-center text-kecil font-bold text-brand-ink underline underline-offset-2 focus:outline-hidden focus-visible:ring-2 focus-visible:ring-brand-600">
                        Lihat ketujuh faktor penilaian
                    </Tautan>
                    <p className="text-meta text-muted">Dinilai dengan aturan tertulis, bukan AI, dan tanpa skor angka. Ini dukungan keputusan, bukan jaminan keselamatan.</p>
                </>
            ) : (
                <>
                    <p className="text-kecil text-secondary">Kecocokan jalur ini dengan Anda, beserta alasannya, muncul setelah profil pendakian Anda lengkap.</p>
                    <Button href={onboarding.url()} size="sm">
                        Isi profil sekarang
                    </Button>
                </>
            )}
        </section>
    );
}
