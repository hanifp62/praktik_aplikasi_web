import type { StatusResmi } from '@/components/ui/StatusBadge';

/** Pilihan formulir yang labelnya sudah diambil dari enum server. */
export interface Opsi {
    value: string | number;
    label: string;
}

/** Perubahan status resmi pada gunung, jalur, atau segmen yang diikuti. */
export interface ButirResmi {
    id: number;
    judul: string;
    status: StatusResmi;
    statusLabel: string | null;
    cakupan: string | null;
    alasan: string | null;
    sumber: string;
    waktu: string;
    waktuIso: string;
}

/** Prakiraan BMKG untuk satu wilayah acuan; pesan terisi ketika datanya tidak tersedia atau sudah lama. */
export interface WilayahCuaca {
    kode: string;
    area: string;
    jalur: string;
    sumber: string;
    ringkasan: string | null;
    waktuPrakiraan: string | null;
    diperbarui: string | null;
    kesegaran: string;
    kesegaranLama: boolean;
    pesan: string | null;
}

export interface CuacaGunung {
    gunung: string;
    wilayah: WilayahCuaca[];
    pesan: string | null;
}

/** Laporan kondisi yang sudah lolos moderasi. */
export interface ButirKomunitas {
    id: number;
    judul: string;
    jalurSlug: string | null;
    tag: string[];
    catatan: string | null;
    pelapor: string;
    tanggalPendakian: string | null;
    kesegaran: string;
    kesegaranLama: boolean;
    waktu: string;
    waktuIso: string;
    terimaKasih: number;
    foto: boolean;
}

/** Satu batang grafik: tanjakan jalur pada satu pendakian yang tuntas. m null berarti belum dicatat, bukan nol. */
export interface TitikTanjakan {
    tanggal: string;
    jalur: string;
    m: number | null;
    teks: string;
}

export interface GarisGeoJson {
    type: 'LineString';
    coordinates: Array<[number, number]>;
}
