import type { LabelFit } from '@/components/trails/LabelKecocokan';

/** Bentuk yang dikirim App\Services\Trails\PenilaianKecocokan: kata, tanpa skor dan bobot (BR-09). */
export interface JalurDinilai {
    id: number;
    nama: string;
    url: string;
    gunung: string;
    gunungUrl: string;
}

export interface Penilaian {
    label: LabelFit;
    labelText: string;
    definisi: string | null;
    /** Lolos syarat dasar (lapis 1); false berarti jalur tidak dinilai, bukan Kurang cocok. */
    layak: boolean;
    denganRencana: boolean;
    alasanTidakDinilai: string[];
}

export interface DinilaiDari {
    profil: string;
    profilUrl: string;
    rencana: string | null;
    rencanaUrl: string | null;
}

/** Tiga keadaan (perbaikan P-1, R-006): status UNKNOWN bukan syarat yang gagal, tetapi juga
 * bukan yang sudah dipastikan terpenuhi. */
export type KeadaanSyarat = 'terpenuhi' | 'gagal' | 'tak-diketahui';

export interface Syarat {
    teks: string;
    keadaan: KeadaanSyarat;
}

export interface Langkah {
    judul: string;
    keterangan: string;
    url: string | null;
}
