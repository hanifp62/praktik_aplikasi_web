import type { FitRingkas, TautanHalaman } from '@/components/trails/types';

export type { FitRingkas, TautanHalaman };

export interface Opsi {
    value: string;
    label: string;
}

/** Baris daftar trip dari TripRowResource: seluruh teks sudah diformat server. */
export interface BarisTrip {
    id: number;
    name: string;
    url: string;
    trail: string | null;
    mountain: string | null;
    tanggal: string;
    tipe: string;
    status: string;
    persiapan: string | null;
    kesiapan: { teks: string; peringatan: boolean } | null;
    fit: FitRingkas | null;
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
