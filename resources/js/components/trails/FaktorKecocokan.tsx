import { Icon, type NamaIkon } from '@/components/ui/Icon';
import { kelas } from '@/lib/kelas';

export type KategoriFaktor = 'kuat' | 'cukup' | 'lemah' | 'tak-diketahui';

/**
 * Satu faktor kecocokan sebagaimana dikirim server: nama, rincian mesin, dan kategori dari
 * ambang yang sama dengan penjelasan mesin (App\Support\FaktorKategori). Halaman penilaian
 * kecocokan menambahkan kata pengaruh dan perbandingan "Jalur ini" lawan "Anda".
 */
export interface FaktorBaris {
    /** Kunci mesin (CompatibilityFactor->value), dipakai sebagai key React (perbaikan P-6). */
    kunci?: string;
    label: string;
    detail: string | null;
    kategori: KategoriFaktor;
    pengaruh?: 'besar' | 'sedang' | 'kecil';
    jalurIni?: string;
    anda?: string;
}

/*
 * Tiga keadaan. "Sesuai" hanya untuk kategori kuat: mesin memilih kalimat rinciannya dengan
 * ambang kuat yang sama (CompatibilityScorer), jadi faktor cukup datang dengan kalimat
 * "di atas pengalaman Anda" dan menyebutnya Sesuai berarti membantah kalimatnya sendiri.
 * Cukup dan lemah sama-sama "Perlu perhatian"; data yang belum ada bukan nilai rendah (R-006).
 * Bentuk ikon ikut membawa keadaan, bukan warna saja.
 */
const keadaan: Record<KategoriFaktor, { kata: string; ikon: NamaIkon; nada: string }> = {
    kuat: { kata: 'Sesuai', ikon: 'check', nada: 'bg-fit-cocok text-fit-cocok-teks' },
    cukup: { kata: 'Perlu perhatian', ikon: 'warning', nada: 'bg-fit-perlu text-fit-perlu-teks' },
    lemah: { kata: 'Perlu perhatian', ikon: 'warning', nada: 'bg-fit-perlu text-fit-perlu-teks' },
    'tak-diketahui': { kata: 'Belum diketahui', ikon: 'question', nada: 'bg-fit-belum text-fit-belum-teks' },
};

function Kepala({ f, tampilkanKata }: { f: FaktorBaris; tampilkanKata: boolean }) {
    const k = keadaan[f.kategori];

    return (
        <span className="flex min-w-0 flex-1 items-start gap-3">
            <span aria-hidden="true" className={kelas('mt-0.5 inline-flex size-7 shrink-0 items-center justify-center rounded-full', k.nada)}>
                <Icon name={k.ikon} size={16} />
            </span>
            <span className="min-w-0 flex-1">
                <span className="flex flex-wrap items-baseline justify-between gap-x-3">
                    <span className="text-subjudul text-primary">{f.label}</span>
                    {tampilkanKata ? <span className="text-meta font-bold text-secondary">{k.kata}</span> : null}
                </span>
                {f.detail ? <span className="mt-0.5 block text-kecil text-secondary">{f.detail}</span> : null}
                {f.pengaruh ? <span className="mt-0.5 block text-meta text-muted">Pengaruh {f.pengaruh}</span> : null}
            </span>
        </span>
    );
}

/**
 * Faktor kecocokan sebagai baris keadaan kata (spec v3 bagian 6.5), bukan batang: batang
 * selebar sekian dapat dibaca balik menjadi angka skor (BR-09). Klien hanya menggambar
 * kategori yang sudah dihitung server; skor dan bobot tidak pernah sampai ke sini. Baris yang
 * membawa perbandingan "Jalur ini" dan "Anda" dapat dibuka dengan details asli (papan ketik
 * dan pembaca layar tanpa kode tambahan). Dipindah dari components/ui/FactorBars.tsx dan
 * ditulis ulang W3.
 *
 * `tampilkanKata` (bawaan true, perbaikan P-8): dimatikan ketika pemanggil sudah
 * mengelompokkan baris di bawah heading yang menyebut keadaan yang sama untuk semua
 * anggotanya (mis. panel alasan rekomendasi mengelompokkan per kategori), supaya kata itu
 * tidak terbaca dua kali untuk baris yang sama.
 */
export function FaktorKecocokan({ faktor, className, tampilkanKata = true }: { faktor: FaktorBaris[]; className?: string; tampilkanKata?: boolean }) {
    if (faktor.length === 0) {
        return null;
    }

    return (
        <ul className={kelas('divide-y divide-subtle', className)}>
            {faktor.map((f) =>
                f.jalurIni !== undefined && f.anda !== undefined ? (
                    <li key={f.kunci ?? f.label}>
                        <details className="group">
                            <summary className="flex min-h-11 cursor-pointer list-none items-start gap-2 py-3 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 [&::-webkit-details-marker]:hidden">
                                <Kepala f={f} tampilkanKata={tampilkanKata} />
                                <Icon name="caret-down" size={20} className="mt-1 shrink-0 text-muted transition-transform duration-cepat ease-gerak group-open:rotate-180" />
                            </summary>
                            <dl className="mb-3 grid grid-cols-2 gap-2 pl-10">
                                <div className="rounded-control bg-surface-sunken p-3">
                                    <dt className="text-meta text-secondary">Jalur ini</dt>
                                    <dd className="mt-0.5 break-words text-kecil font-bold text-primary">{f.jalurIni}</dd>
                                </div>
                                <div className="rounded-control bg-surface-sunken p-3">
                                    <dt className="text-meta text-secondary">Anda</dt>
                                    <dd className="mt-0.5 break-words text-kecil font-bold text-primary">{f.anda}</dd>
                                </div>
                            </dl>
                        </details>
                    </li>
                ) : (
                    <li key={f.kunci ?? f.label} className="py-3">
                        <Kepala f={f} tampilkanKata={tampilkanKata} />
                    </li>
                ),
            )}
        </ul>
    );
}
