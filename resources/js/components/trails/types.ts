export interface FitRingkas {
    label: 'COCOK' | 'PERLU_PERSIAPAN' | 'KURANG_COCOK' | null;
    labelText: string;
    alasan: string;
    denganRencana: boolean;
}

/** Bentuk yang dikirim TrailRowResource: angka sudah diformat di server. */
export interface BarisJalur {
    id: number;
    slug: string;
    name: string;
    url: string;
    mountain: string | null;
    province: string | null;
    technical: string | null;
    durasi: string;
    jarak: string;
    tanjakan: string;
    curam: string | null;
    kemudahan: string[];
    ditimbang: boolean;
    fit: FitRingkas | null;
}

/** Baris halaman Simpanan: baris jalur ditambah keadaan terbit dan catatan pribadi (FR-22). */
export interface JalurTersimpan extends BarisJalur {
    terbit: boolean;
    catatan: string | null;
}

export interface TautanHalaman {
    url: string | null;
    label: string;
    active: boolean;
}

export interface JalurMenunggu {
    id: number;
    name: string;
    url: string;
    mountain: string | null;
    province: string | null;
}

export interface Penanda {
    lng: number;
    lat: number;
    label: string;
}

/**
 * Satu jalur di peta sebaran Jelajah (App\Services\Trails\PetaSebaranJalur): bentuk
 * jalur dari PostGIS dan titik awalnya. Teks ringkasannya diambil dari baris daftar
 * dengan id yang sama, jadi peta dan daftar tidak pernah menyebut angka yang berbeda.
 */
export interface FiturPeta {
    id: number;
    geometri: { type: 'LineString'; coordinates: Array<[number, number]> };
    awal: [number, number];
}
