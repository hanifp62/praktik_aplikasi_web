import type { SumberKeterangan } from './LabelSumber';
import type { StatusResmi } from './StatusAkses';

export interface FitRingkas {
    label: 'COCOK' | 'PERLU_PERSIAPAN' | 'KURANG_COCOK' | null;
    labelText: string;
    alasan: string;
    denganRencana: boolean;
}

/**
 * Status akses satu jalur beserta asalnya (App\Services\Trails\StatusJalurRingkas): nilai,
 * kata, cakupan catatan penentunya, jenis dan nama sumbernya, tanggal pendek dalam zona
 * gunung (bertahun bila bukan tahun berjalan) beserta awalannya, dan kesegarannya. Tanpa
 * catatan yang berlaku, status null dan sumber null.
 */
export interface StatusRingkas {
    status: StatusResmi;
    label: string;
    cakupan: string | null;
    sumber: SumberKeterangan | null;
    waktu: string | null;
    /** Awalan dari kolom waktu yang dipakai: "Diverifikasi", "Diterbitkan", atau "Berlaku sejak" (W3-2c). */
    awalanWaktu: string | null;
    kesegaran: string | null;
}

/** Bentuk yang dikirim TrailRowResource: angka sudah diformat di server, "-" bila tidak ada. */
export interface BarisJalur {
    id: number;
    slug: string;
    name: string;
    url: string;
    mountain: string | null;
    province: string | null;
    durasi: string;
    jarak: string;
    tanjakan: string;
    ditimbang: boolean;
    fit: FitRingkas | null;
}

/** Kecocokan di kartu Jelajah: FitRingkas ditambah isi "Kenapa?" (TrailRowResource). */
export interface FitKartu extends FitRingkas {
    /** Lolos syarat dasar; false berarti tidak dinilai, dan kartunya menyebut alasannya. */
    layak: boolean;
    mengapaSesuai: string[];
    perluDiperhatikan: string[];
}

/** Kartu jalur Jelajah (App\Http\Resources\KartuJalurResource). */
export interface KartuJalur extends Omit<BarisJalur, 'fit'> {
    fit: FitKartu | null;
    gunungUrl: string | null;
    status: StatusRingkas;
    /** Titik 0..1 pada skala naik yang sama dengan kartu lain di halaman ini; null bila tanpa profil. */
    garis: Array<[number, number]> | null;
    adaCatatan: boolean;
    /** Nama saringan yang menyembunyikannya, bila kartu ini tampil lewat "Tampilkan juga". */
    tersembunyiOleh: string | null;
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
