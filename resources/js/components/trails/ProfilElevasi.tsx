import { useId } from 'react';
import type { ProfilElevasiData } from '@/components/trails/rinci/types';
import { kelas } from '@/lib/kelas';

const LEBAR = 1000;
const TINGGI = 240;

const angka = (n: number, desimal = 0) => n.toLocaleString('id-ID', { minimumFractionDigits: desimal, maximumFractionDigits: desimal });

/** Tiga sampai empat angka bulat di antara dua batas, untuk garis bantu sumbu. */
function garisBantu(min: number, maks: number, langkahDasar: number[]): number[] {
    const rentang = Math.max(maks - min, 1);
    const langkah = langkahDasar.find((l) => rentang / l <= 4) ?? langkahDasar[langkahDasar.length - 1] ?? 1;
    const hasil: number[] = [];

    for (let n = Math.ceil(min / langkah) * langkah; n <= maks; n += langkah) {
        hasil.push(n);
    }

    return hasil;
}

/**
 * Profil elevasi (spec v3 bagian 6.4, prototype 04), dipakai rincian jalur dan ringkasan
 * pendakian. Menggantikan ElevationProfile lama di components/ui: komponen ini tahu domain
 * (pos, bagian tercuram), jadi tempatnya di components/trails (keputusan koordinator W3).
 *
 * Bagian tercuram diarsir dengan pola garis miring, bukan warna saja. Penanda pos hanya
 * untuk pos yang jaraknya dari awal diukur sepanjang garis rute; pos berkemah dan tujuan
 * membawa aksen kuning, selalu bersama nomornya dan keterangan di bawah grafik (DESIGN.md,
 * satu aksen). Padanan teksnya kalimat dari server di figcaption, jadi SVG-nya disembunyikan
 * dari pembaca layar (WCAG 1.1.1).
 */
export function ProfilElevasi({ profil, className }: { profil: ProfilElevasiData; className?: string }) {
    const idPola = useId();
    const titik = profil.titik;
    const kmAwal = titik[0]?.[0] ?? 0;
    const kmAkhir = titik.at(-1)?.[0] ?? 1;
    const tinggi = titik.map(([, m]) => m);
    const mMin = Math.min(...tinggi);
    const mMaks = Math.max(...tinggi);
    const bawah = mMin - (mMaks - mMin) * 0.1;
    const atas = mMaks + (mMaks - mMin) * 0.1;
    const x = (km: number) => ((km - kmAwal) / Math.max(kmAkhir - kmAwal, 0.001)) * LEBAR;
    const y = (m: number) => TINGGI - ((m - bawah) / Math.max(atas - bawah, 1)) * TINGGI;
    const garis = titik.map(([km, m]) => `${x(km).toFixed(1)},${y(m).toFixed(1)}`).join(' ');
    const tinggiPada = (km: number) => {
        const i = titik.findIndex(([k]) => k >= km);
        if (i <= 0) return titik[0]?.[1] ?? mMin;
        const [k0, m0] = titik[i - 1] ?? [km, mMin];
        const [k1, m1] = titik[i] ?? [km, mMin];

        return k1 === k0 ? m1 : m0 + ((m1 - m0) * (km - k0)) / (k1 - k0);
    };
    const adaAksen = profil.pos.some((p) => p.aksen !== null);

    return (
        <figure className={className}>
            <div className="grid grid-cols-[auto_minmax(0,1fr)] gap-x-2">
                <div aria-hidden="true" className="relative h-44 w-11 text-meta text-muted">
                    {garisBantu(bawah, atas, [100, 200, 250, 500, 1000]).map((m) => (
                        <span key={m} data-angka="" className="absolute right-0 -translate-y-1/2" style={{ top: `${(y(m) / TINGGI) * 100}%` }}>
                            {angka(m)}
                        </span>
                    ))}
                </div>
                <div className="relative h-44">
                    <svg viewBox={`0 0 ${LEBAR} ${TINGGI}`} preserveAspectRatio="none" aria-hidden="true" className="absolute inset-0 h-full w-full">
                        <defs>
                            <pattern id={idPola} width="12" height="12" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
                                <line x1="0" y1="0" x2="0" y2="12" strokeWidth="5" className="stroke-fit-perlu-teks/40" />
                            </pattern>
                        </defs>
                        {garisBantu(bawah, atas, [100, 200, 250, 500, 1000]).map((m) => (
                            <line key={m} x1="0" x2={LEBAR} y1={y(m)} y2={y(m)} strokeWidth="1" vectorEffect="non-scaling-stroke" className="stroke-subtle" />
                        ))}
                        {profil.tercuram ? (
                            <g>
                                <rect x={x(profil.tercuram.dariKm)} y="0" width={x(profil.tercuram.sampaiKm) - x(profil.tercuram.dariKm)} height={TINGGI} className="fill-fit-perlu/60" />
                                <rect x={x(profil.tercuram.dariKm)} y="0" width={x(profil.tercuram.sampaiKm) - x(profil.tercuram.dariKm)} height={TINGGI} fill={`url(#${idPola})`} />
                            </g>
                        ) : null}
                        <polygon points={`${x(kmAwal)},${TINGGI} ${garis} ${x(kmAkhir)},${TINGGI}`} className="fill-brand-100/70" />
                        <polyline points={garis} fill="none" strokeWidth="3" strokeLinejoin="round" vectorEffect="non-scaling-stroke" className="stroke-brand-ink" />
                    </svg>
                    {profil.pos.map((p) => (
                        <span
                            key={p.urutan}
                            aria-hidden="true"
                            data-angka=""
                            className={kelas(
                                'absolute flex size-6 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border-2 text-meta font-bold',
                                p.aksen ? 'border-primary bg-aksen-pos text-aksen-pos-teks' : 'border-brand-ink bg-surface text-brand-ink',
                            )}
                            style={{ left: `${(x(p.km) / LEBAR) * 100}%`, top: `${(y(tinggiPada(p.km)) / TINGGI) * 100}%` }}
                        >
                            {p.urutan}
                        </span>
                    ))}
                </div>
                <div />
                <div aria-hidden="true" className="relative mt-1 h-4 text-meta text-muted">
                    {garisBantu(kmAwal, kmAkhir, [1, 2, 5, 10]).map((km) => (
                        <span key={km} data-angka="" className="absolute -translate-x-1/2" style={{ left: `${(x(km) / LEBAR) * 100}%` }}>
                            {angka(km)}
                        </span>
                    ))}
                    <span data-angka="" className="absolute right-0">
                        {angka(kmAkhir, 1)} km
                    </span>
                </div>
            </div>
            <figcaption className="mt-3 space-y-1 text-kecil text-secondary">
                <p>{profil.keterangan}</p>
                {adaAksen ? (
                    <p className="flex items-center gap-2 text-meta">
                        <span aria-hidden="true" className="inline-block size-3 rounded-full border-2 border-primary bg-aksen-pos" />
                        Pos berkemah dan tujuan
                    </p>
                ) : null}
            </figcaption>
        </figure>
    );
}
