import type { ProfilGunung } from './types';

const LEBAR = 120;
const TINGGI = 56;

/** Titik 0..1 menjadi koordinat SVG; sumbu y dibalik karena ketinggian naik ke atas. */
function jalurGaris(titik: Array<[number, number]>): string {
    return titik.map(([x, y]) => `${(x * LEBAR).toFixed(1)},${(TINGGI - y * (TINGGI - 4) - 2).toFixed(1)}`).join(' ');
}

/** Bidang di bawah garis: dari dasar kiri, menyusuri garis, lalu turun ke dasar di ujung garis. */
function bidang(titik: Array<[number, number]>): string {
    const ujung = titik.at(-1)?.[0] ?? 0;

    return `0,${TINGGI} ${jalurGaris(titik)} ${(ujung * LEBAR).toFixed(1)},${TINGGI}`;
}

/**
 * Visual data pengganti foto gunung (spec v3 bagian 8.7, DK-37): profil setiap jalur terbit
 * sebagai kelipatan kecil pada skala mutlak yang sama, jadi jalur yang lebih panjang tampak
 * lebih panjang dan puncak yang sama berada di ketinggian yang sama. Kelipatan kecil, bukan
 * garis bertumpuk: garis yang berakhir di puncak yang sama saling menutupi, dan membedakannya
 * dengan warna saja melanggar WCAG 1.4.1. Pembaca layar mendapat kalimat ringkasnya.
 */
export function VisualProfilGunung({ profil }: { profil: ProfilGunung }) {
    return (
        <figure className="rounded-utama border border-subtle bg-surface p-4">
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                {profil.garis.map((g) => (
                    <div key={g.id} className="min-w-0">
                        <p className="wrap-break-word text-meta font-bold text-primary">{g.nama}</p>
                        <svg viewBox={`0 0 ${LEBAR} ${TINGGI}`} preserveAspectRatio="none" aria-hidden="true" className="mt-1 h-14 w-full">
                            <polygon points={bidang(g.titik)} className="fill-brand-100" />
                            <polyline points={jalurGaris(g.titik)} fill="none" strokeWidth="2" strokeLinejoin="round" className="stroke-brand-ink" vectorEffect="non-scaling-stroke" />
                        </svg>
                    </div>
                ))}
            </div>
            <figcaption className="mt-3 text-meta text-secondary">{profil.keterangan}</figcaption>
        </figure>
    );
}
