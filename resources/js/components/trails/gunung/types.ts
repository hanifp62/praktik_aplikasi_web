import type { SumberKeterangan } from '@/components/trails/LabelSumber';
import type { FitRingkas, StatusRingkas } from '@/components/trails/types';

/** Bentuk yang dikirim App\Services\Mountains\HalamanGunung; angka sudah diformat server. */
export interface GunungRingkas {
    id: number;
    nama: string;
    provinsi: string | null;
    puncak: string | null;
    zona: string;
}

export interface JalurGunung {
    id: number;
    nama: string;
    url: string;
    fit: FitRingkas | null;
    angka: string;
    medan: string | null;
    status: StatusRingkas;
}

/** Profil setiap jalur pada skala mutlak yang sama (SkalaProfil::mutlakBersama), 0..1. */
export interface ProfilGunung {
    garis: Array<{ id: number; nama: string; titik: Array<[number, number]> }>;
    keterangan: string;
}

export interface IzinGunung {
    pengelola: string;
    jendela: string | null;
    kuota: string | null;
    pemandu: string | null;
    catatan: string | null;
    bookingUrl: string | null;
    sumber: SumberKeterangan;
    diverifikasi: string | null;
}

export interface LaporanGunung {
    id: number;
    jalur: string;
    tags: string[];
    catatan: string | null;
    dilaporkan: string;
    tanggalPendakian: string;
    kesegaran: string;
}

export interface Provenans {
    sumber: SumberKeterangan | null;
    diverifikasi: string | null;
}
