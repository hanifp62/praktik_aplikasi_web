import type { FitRingkas, TautanHalaman } from '@/components/trails/types';

export type { FitRingkas, TautanHalaman };

export interface Opsi {
    value: string;
    label: string;
}

/** Satu kartu indeks trip dari TripRowResource: seluruh teks sudah diformat server. */
export interface BarisTrip {
    id: number;
    nama: string;
    url: string;
    jalur: string | null;
    gunung: string | null;
    jenis: string;
    tanggal: string;
    hari: string | null;
    /** "Tanggal rencana sudah lewat" untuk rencana yang belum dimulai (ruling W4-1c); null bila tidak. */
    lewat: string | null;
    keadaan: string | null;
    fase: { kunci: string; judul: string; keterangan: string; peringatan: boolean } | null;
    aksi: { label: string; href: string } | null;
    kesiapan: { teks: string; peringatan: boolean } | null;
    fit: FitRingkas | null;
}

/** Satu kelompok indeks trip (App\Services\Trips\IndeksTrip). */
export interface KelompokTrip {
    kunci: 'berlangsung' | 'mendatang' | 'selesai';
    judul: string;
    trip: BarisTrip[];
}

/** Kepala satu trip dari App\Services\Trips\KepalaTrip: seluruh teks sudah diformat server. */
export interface KepalaTripData {
    id: number;
    nama: string;
    jalur: string;
    gunung: string;
    jenis: string;
    hari: string | null;
    /** "Tanggal rencana sudah lewat" untuk rencana yang belum dimulai (ruling W4-1c); null bila tidak. */
    lewat: string | null;
    tanggal: string;
    jam: string | null;
    peserta: string | null;
    keadaan: string | null;
    ubah: string | null;
}

export type KeadaanLangkah = 'selesai' | 'berikutnya' | 'belum';

/** Satu langkah perjalanan dari App\Services\Trips\TripSteps. */
export interface Langkah {
    keadaan: KeadaanLangkah;
    kunci: string;
    judul: string;
    keterangan: string;
    catatan: string | null;
    peringatan: boolean;
    tautan: { href: string; teks: string } | null;
}

export interface Penanda {
    lng: number;
    lat: number;
    label: string;
}

/** Geometri GeoJSON jalur (LineString) dari kolom geografi, atau null. */
export interface GeometriJalur {
    type: string;
    coordinates: [number, number][];
}
