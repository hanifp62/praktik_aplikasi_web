import type { ButirResmi } from '@/components/community/types';
import type { LaporanGunung } from '@/components/trails/gunung/types';
import type { FitRingkas } from '@/components/trails/types';
import type { KeadaanLangkah } from '@/components/trips/types';

/** Fase Beranda (spec v3 bagian 6.1, App\Services\Dashboard\RingkasanDasbor). */
export type KunciFase = 'B0' | 'B1' | 'B2' | 'B3' | 'B4';

/**
 * Satu aksi utama fase, dipilih server: tautan di dalam aplikasi, tautan ke sistem resmi di
 * situs lain, atau sakelar pengingat trip (PUT trips.reminders.update dengan aktif true).
 */
export interface AksiFase {
    jenis: 'tautan' | 'luar' | 'pengingat';
    label: string;
    href: string;
    keterangan: string | null;
}

/** Kartu perjalanan berikutnya (fase B2): seluruh teks sudah diformat server. */
export interface KartuPerjalananData {
    jenis: 'perjalanan';
    id: number;
    nama: string;
    url: string;
    jalur: string;
    gunung: string;
    jenisTrip: string;
    hari: string | null;
    tanggal: string;
    jam: string | null;
    peserta: string | null;
    /** Format TrailRowResource: "-" bila angkanya belum ada. */
    angka: { jarak: string; tanjakan: string; durasi: string };
    /** Titik 0..1 garis profil jalur; null tanpa profil. */
    garis: Array<[number, number]> | null;
    langkah: Array<{ kunci: string; judul: string; keadaan: KeadaanLangkah }>;
    peringatan: string | null;
    /** `jumlah` adalah jumlah mentah butir kritis, terlepas statusnya (ruling W5-2c / M3). */
    persiapan: { jumlah: number; dikonfirmasi: number; berlaku: number; belum: string[]; sisa: number; menungguBmkg: string | null };
    aksi: AksiFase;
}

export type KartuFaseData =
    | { jenis: 'profil'; aksi: AksiFase }
    | { jenis: 'mulai'; aksi: AksiFase }
    | KartuPerjalananData
    | { jenis: 'mendaki'; posBerikutnya: string | null; aksi: AksiFase }
    | { jenis: 'lapor'; nama: string; jalur: string; gunung: string | null; hasil: string; selesai: string; ringkasanUrl: string; aksi: AksiFase };

/** Tenggat terdekat trip mendatang (fase B2, App\Services\Dashboard\TenggatTrip), dari yang terdekat. */
export interface TenggatBeranda {
    /** Penanda zona gunung untuk seluruh daftar: WIB, WITA, atau WIT. */
    zona: string;
    baris: Array<{
        /** Penanda kalender dan kalimat lengkapnya; null untuk jendela izin yang belum tercatat. */
        kapan: { tanggal: string; bulan: string; tahun: string | null; lengkap: string } | null;
        judul: string;
        keterangan: string;
        /** Tautan ke sistem resmi pengelola (situs lain). */
        tautan: { label: string; href: string } | null;
        peringatan: boolean;
    }>;
}

/** Kabar gunung yang diikuti (App\Services\Dashboard\KabarBeranda); null pada fase B0 dan B3. */
export interface KabarBerandaData {
    judul: string;
    /** Nama gunung yang diikuti bila lebih dari satu; satu gunung sudah disebut judulnya. */
    mengikuti: string | null;
    diikuti: boolean;
    resmi: ButirResmi[];
    komunitas: LaporanGunung[];
    /** Gunung tujuan trip mendatang yang belum diikuti (fase B2). */
    ikuti: { gunung: string; href: string } | null;
}

/**
 * Satu kartu "Jalur lain untuk Anda": hanya isian yang dipakai JalurUntukAnda.tsx, bukan
 * seluruh bentuk BarisJalur (M6, ruling W5-5b).
 */
export interface JalurLain {
    id: number;
    name: string;
    url: string;
    mountain: string | null;
    province: string | null;
    durasi: string;
    jarak: string;
    tanjakan: string;
    fit: Pick<FitRingkas, 'label' | 'labelText' | 'alasan'> | null;
    garis: Array<[number, number]> | null;
}

/** "Jalur lain untuk Anda" (App\Services\Dashboard\JalurUntukAnda); null pada fase B0 dan B3. */
export interface JalurLainData {
    /** Profil pendaki belum terisi, jadi belum ada yang dapat dinilai. */
    tanpaProfil: boolean;
    jalur: JalurLain[];
}

/** Tangga tanjakan (App\Services\Dashboard\TanggaTanjakan); null tanpa pendakian tuntas, dan pada fase B0 dan B3. */
export interface TanggaData {
    anakTangga: Array<{ id: number; jalur: string; gunung: string; m: number; teks: string; rencana: boolean }>;
    kalimat: string;
}

export interface FaseBeranda {
    kunci: KunciFase;
    ringkasan: string;
    kartu: KartuFaseData;
    /** Rencana terbaru yang tanggalnya lewat tanpa dimulai (W4-1c); null bila tidak ada. */
    tripLewat: { nama: string; tanggal: string; url: string } | null;
}
