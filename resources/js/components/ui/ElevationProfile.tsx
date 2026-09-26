export interface TitikElevasi {
    km: number;
    m: number;
}

const idFormat = (n: number, desimal = 0) => n.toLocaleString('id-ID', { minimumFractionDigits: desimal, maximumFractionDigits: desimal });

/**
 * Profil elevasi sebagai SVG sebaris, tanpa pustaka grafik. Pembaca layar mendapat
 * judul dan rangkuman, bukan seratus titik.
 */
export function ElevationProfile({ profile, className }: { profile: TitikElevasi[]; className?: string }) {
    const titik = profile.filter((t) => typeof t.km === 'number' && typeof t.m === 'number');

    if (titik.length < 2) {
        return null;
    }

    const kmMax = Math.max(...titik.map((t) => t.km), 0.001);
    const mMin = Math.min(...titik.map((t) => t.m));
    const mMax = Math.max(...titik.map((t) => t.m));
    const rentang = Math.max(mMax - mMin, 1);
    const lebar = 1000;
    const tinggi = 220;

    const koordinat = titik
        .map((t) => `${((t.km / kmMax) * lebar).toFixed(1)},${(tinggi - ((t.m - mMin) / rentang) * (tinggi - 20) - 10).toFixed(1)}`)
        .join(' ');

    return (
        <figure className={className}>
            <svg viewBox={`0 0 ${lebar} ${tinggi}`} preserveAspectRatio="none" role="img" className="h-40 w-full" aria-labelledby="profil-elevasi-judul">
                <title id="profil-elevasi-judul">
                    Profil elevasi: dari {mMin} sampai {mMax} mdpl sepanjang {idFormat(kmMax, 1)} kilometer.
                </title>
                <polygon points={`0,${tinggi} ${koordinat} ${lebar},${tinggi}`} fill="rgb(var(--brand-100))" />
                <polyline points={koordinat} fill="none" stroke="rgb(var(--brand-ink))" strokeWidth="3" vectorEffect="non-scaling-stroke" />
            </svg>
            <figcaption className="mt-2 flex flex-wrap justify-between gap-2 text-xs text-secondary">
                <span>{idFormat(mMin)} mdpl terendah</span>
                <span>{idFormat(mMax)} mdpl tertinggi</span>
                <span>{idFormat(kmMax, 1)} km</span>
            </figcaption>
        </figure>
    );
}
