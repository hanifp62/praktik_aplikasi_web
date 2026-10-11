import { kelas } from '@/lib/kelas';
import type { LabelFit } from './LabelKecocokan';

const LEBAR = 200;
const TINGGI = 40;

/** Warna garis mengikuti label; labelnya sendiri selalu tertulis di kartu, jadi warna bukan satu-satunya pembawa arti. */
const warna: Record<'COCOK' | 'PERLU_PERSIAPAN' | 'KURANG_COCOK' | 'BELUM', { garis: string; isi: string }> = {
    COCOK: { garis: 'stroke-fit-cocok-teks', isi: 'fill-fit-cocok' },
    PERLU_PERSIAPAN: { garis: 'stroke-fit-perlu-teks', isi: 'fill-fit-perlu' },
    KURANG_COCOK: { garis: 'stroke-fit-kurang-teks', isi: 'fill-fit-kurang' },
    BELUM: { garis: 'stroke-brand-ink', isi: 'fill-brand-100' },
};

function koordinat(titik: Array<[number, number]>): string {
    return titik.map(([x, y]) => `${(x * LEBAR).toFixed(1)},${(TINGGI - y * (TINGGI - 4) - 2).toFixed(1)}`).join(' ');
}

/**
 * Garis profil kartu (spec v3 bagian 6.2): bentuk naik-turun jalur pada skala tinggi yang sama
 * untuk semua kartu di halaman (SkalaProfil::naikBersama), sehingga jalur yang naik 280 m
 * terbaca landai di samping jalur yang naik 1.650 m. Hiasan data, bukan satu-satunya sumber:
 * angka tanjakannya tertulis di atasnya, jadi grafiknya disembunyikan dari pembaca layar.
 * Tanpa profil tidak ada garis; garis datar karangan akan terbaca sebagai jalur landai.
 */
export function GarisProfil({ titik, label, className }: { titik: Array<[number, number]>; label: LabelFit; className?: string }) {
    const w = warna[label ?? 'BELUM'];
    const ujung = titik.at(-1)?.[0] ?? 1;

    return (
        <svg viewBox={`0 0 ${LEBAR} ${TINGGI}`} preserveAspectRatio="none" aria-hidden="true" className={kelas('h-10 w-full', className)}>
            <polygon points={`0,${TINGGI} ${koordinat(titik)} ${(ujung * LEBAR).toFixed(1)},${TINGGI}`} className={w.isi} />
            <polyline points={koordinat(titik)} fill="none" strokeWidth="2" strokeLinejoin="round" vectorEffect="non-scaling-stroke" className={w.garis} />
        </svg>
    );
}
