/**
 * Model saringan Jelajah di klien (spec v3 bagian 6.2).
 *
 * Server (App\Services\Trails\TrailSearch::gagalPada) adalah satu-satunya yang menentukan
 * hasil. Klien hanya menghitung pratinjau "Tampilkan N jalur" di lembar saringan dari atribut
 * yang dikirim server untuk setiap kandidat, dengan lima uji yang sama urutan dan artinya:
 * status, kecocokan, teknis, jenis, naik. Nilai yang tidak diketahui tidak lolos saringan
 * rendah atau rentang (R-006, R-007). Untuk kecocokan dan jenis, null berarti tanpa saringan
 * dan larik kosong berarti semua pilihan dilepas.
 */

export interface AtributJalur {
    id: number;
    kecocokan: string | null;
    jenis: string[];
    teknisRendah: boolean;
    status: string;
    naik: number | null;
}

/** Saringan yang sedang dikembalikan server (TrailSearch::filter). */
export interface FilterJelajah {
    search: string;
    abaikan: string[];
    technical: string;
    region: string;
    distance: number | null;
    duration: number | null;
    kecocokan: string[] | null;
    jenis: string[] | null;
    teknisRendah: boolean;
    status: string[];
    naikDari: number | null;
    naikSampai: number | null;
    tampilkanSemua: boolean;
}

/** Yang diubah pembaca di lembar saringan sebelum ditampilkan. */
export interface DrafSaringan {
    kecocokan: string[] | null;
    jenis: string[] | null;
    teknisRendah: boolean;
    status: string[];
    naikDari: number | null;
    naikSampai: number | null;
}

export const STATUS_BAWAAN = ['OPEN', 'RESTRICTED', 'UNKNOWN'];

export function drafDari(filter: FilterJelajah, teknisAktif: boolean): DrafSaringan {
    return {
        kecocokan: filter.kecocokan,
        jenis: filter.jenis,
        teknisRendah: teknisAktif,
        status: filter.status,
        naikDari: filter.naikDari,
        naikSampai: filter.naikSampai,
    };
}

export const drafKosong: DrafSaringan = { kecocokan: null, jenis: null, teknisRendah: false, status: STATUS_BAWAAN, naikDari: null, naikSampai: null };

/** Sama dengan TrailSearch::gagalPada: null bila lolos, atau kunci saringan pertama yang menolak. */
export function gagalPada(a: AtributJalur, d: DrafSaringan, dinilai: boolean): string | null {
    if (!d.status.includes(a.status)) return 'status';
    if (dinilai && d.kecocokan !== null && (a.kecocokan === null || !d.kecocokan.includes(a.kecocokan))) return 'kecocokan';
    if (d.teknisRendah && !a.teknisRendah) return 'teknis';
    if (d.jenis !== null && !d.jenis.some((j) => a.jenis.includes(j))) return 'jenis';

    if (d.naikDari !== null || d.naikSampai !== null) {
        if (a.naik === null || (d.naikDari !== null && a.naik < d.naikDari) || (d.naikSampai !== null && a.naik > d.naikSampai)) return 'naik';
    }

    return null;
}

export function hitungLolos(atribut: AtributJalur[], d: DrafSaringan, dinilai: boolean): number {
    return atribut.filter((a) => gagalPada(a, d, dinilai) === null).length;
}

/**
 * Mengubah satu pilihan kecocokan atau jenis. Semua pilihan tercentang kembali menjadi null
 * (tanpa saringan), supaya jalur yang tidak punya nilai itu tidak ikut tersembunyi.
 */
export function alihPilihan(daftar: string[] | null, semua: string[], nilai: string, dicentang: boolean): string[] | null {
    const kini = daftar ?? semua;
    const baru = dicentang ? [...kini.filter((n) => n !== nilai), nilai] : kini.filter((n) => n !== nilai);

    return baru.length === semua.length ? null : baru;
}

/**
 * Kueri URL untuk satu keadaan saringan. Nilai bawaan tidak ditulis, supaya tautan yang
 * dibagikan tetap pendek; tuntutan teknis dari tafsiran kalimat dilepas lewat abaikan,
 * bukan lewat teknisRendah=0.
 */
export function kueriDari(filter: FilterJelajah, d: DrafSaringan, tafsiranTeknis: boolean, tampilkanSemua = false): Record<string, string | number> {
    const abaikan = new Set(filter.abaikan);

    if (tafsiranTeknis && !d.teknisRendah) {
        abaikan.add('teknis');
    }

    const kueri: Record<string, string | number | null> = {
        search: filter.search || null,
        abaikan: abaikan.size > 0 ? Array.from(abaikan).join(',') : null,
        technical: filter.technical || null,
        region: filter.region || null,
        distance: filter.distance,
        duration: filter.duration,
        kecocokan: d.kecocokan === null ? null : d.kecocokan.join(','),
        jenis: d.jenis === null ? null : d.jenis.join(','),
        teknisRendah: d.teknisRendah && !tafsiranTeknis ? '1' : null,
        status: [...d.status].sort().join(',') === [...STATUS_BAWAAN].sort().join(',') ? null : d.status.join(','),
        naikDari: d.naikDari,
        naikSampai: d.naikSampai,
        tampilkanSemua: tampilkanSemua ? '1' : null,
    };

    // Hanya null yang dibuang: nilai kosong ('') berarti pembaca melepas semua pilihannya,
    // dan server membacanya sebagai pilihan kosong, bukan sebagai bawaan.
    return Object.fromEntries(Object.entries(kueri).filter(([, v]) => v !== null)) as Record<string, string | number>;
}
