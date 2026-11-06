import type { Titik, TitikProfil } from './hitung';

/** Satu pos dari App\Services\Trips\HalamanMendaki. Koordinat null bila belum tercatat. */
export interface PosLapangan {
    id: number;
    urutan: number;
    nama: string;
    jenis: string;
    aksen: 'kemah' | 'tujuan' | null;
    lng: number | null;
    lat: number | null;
    /** Ketinggian pos tercatat, meter; null bila belum ada sumber. */
    elevasi: number | null;
    segmen: number | null;
}

/** Perkiraan satu ruas, berakhir di pos berurutan `ke`; null bila rekaman belum cukup. */
export interface RuasLapangan {
    ke: number;
    perkiraan: string | null;
    rekaman: number | null;
}

/** Laporan komunitas yang lolos moderasi, per segmen, tanpa nama pelapor (R-010). */
export interface LaporanLapangan {
    segmen: number | null;
    kondisi: string;
    catatan: string | null;
    waktu: string;
}

/**
 * Data mode lapangan. Bentuknya sama untuk props halaman (daring) dan isi paket lapangan
 * (luring), sehingga mode lapangan adalah satu pohon komponen dengan dua sumber data.
 */
export interface DataLapangan {
    trip: {
        id: number;
        nama: string;
        jalur: string;
        gunung: string;
        berlangsung: boolean;
        /** Instan mulai sesi, ISO 8601 UTC; null bila trip belum dimulai. */
        mulai: string | null;
        /** Instan jeda yang sedang berjalan dimulai; null bila tidak sedang jeda. */
        jedaSejak: string | null;
        /** Jumlah detik jeda yang sudah selesai. */
        jedaDetik: number;
    };
    pos: PosLapangan[];
    garis: Titik[] | null;
    profil: TitikProfil[] | null;
    ruas: RuasLapangan[];
    tercapai: number;
    radiusTiba: number;
    laporan: LaporanLapangan[];
    rekamJejak: boolean;
    sesiId: number | null;
    sumber: 'daring' | 'paket';
    /** Kartu darurat (App\Services\Trips\KartuDarurat); null selama alat keselamatan menunggu Q14. */
    darurat: DaruratLapangan | null;
    /** Pilihan lembar Selesai: hasil pendakian dan alasan berbalik di jalur (DK-50). */
    pilihan: { hasil: Array<{ value: string; label: string }>; alasan: Array<{ value: string; label: string }> };
    urls: { tiba: string; jeda: string; lanjut: string; selesai: string; ruangKerja: string; jejak: string | null };
}

/** Isi kartu darurat dari server; blok lokasinya dihitung di perangkat. */
export interface DaruratLapangan {
    nomor: Array<{ label: string; nomor: string }>;
    pengelola: { nama: string; kontak: string; telepon: string | null } | null;
    tetap: string;
    /** Zona gunung (IANA) dan penandanya, untuk jam pengukuran posisi (pra-tinjau Tugas 7 PT7-14). */
    zona: string;
    labelZona: string;
}

/** Posisi perangkat terakhir: bujur, lintang, akurasi dalam meter, dan waktunya (ms). */
export interface PosisiPerangkat {
    lng: number;
    lat: number;
    akurasi: number;
    waktu: number;
}
