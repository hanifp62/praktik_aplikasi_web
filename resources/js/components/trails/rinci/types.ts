import type { LineString, MultiLineString } from 'geojson';
import type { JenisSumber } from '@/components/trails/LabelSumber';
import type { Penanda } from '@/components/trails/types';
import type { LabelFit } from '@/components/trails/LabelKecocokan';
import type { StatusResmi } from '@/components/trails/StatusAkses';

/** Bentuk yang dikirim TrailHeaderResource dan TrailDetailPage: sudah diformat server, tanpa skor (BR-09). */
export interface IdentitasJalur {
    name: string;
    mountain: string;
    mountainId: number;
    /** Halaman gunung (DK-44), untuk remah di kepala rincian. */
    mountainUrl: string;
    province: string | null;
    mdpl: string | null;
}

export interface AngkaBerlabel {
    label: string;
    nilai: string;
    ada: boolean;
}

/** "Cocokkah untuk saya?" (TrailDetailPage::kecocokan): label, alasan, dan tiga daftar tanpa skor. */
export interface KecocokanRinci {
    label: LabelFit;
    labelText: string;
    deskripsi: string | null;
    layak: boolean;
    /** Ringkasan trip terencana di jalur ini; null berarti kecocokan dasar. */
    rencana: string | null;
    alasan: string;
    mengapaSesuai: string[];
    perluDiperhatikan: string[];
    celahPersiapan: string[];
    tangga: string | null;
    penilaianUrl: string;
}

/** Satu baris "Apa tantangannya?"; ruas null berarti tanpa bilah (kata saja, atau belum diketahui). */
export interface BarisTantangan {
    judul: string;
    kata: string;
    ruas: number | null;
}

export type AksenPos = 'kemah' | 'tujuan' | null;

/** Satu pos di daftar pos rincian (CheckpointJourney::daftar). */
export interface PosJalur {
    id: number;
    urutan: number;
    nama: string;
    jenis: string;
    aksen: AksenPos;
    elevasi: string | null;
    antara: string | null;
    km: string | null;
    waktu: string | null;
    catatan: string | null;
}

/** Profil elevasi rincian (ProfilElevasiJalur): titik [km, m], bagian tercuram, penanda pos. */
export interface ProfilElevasiData {
    titik: Array<[number, number]>;
    tercuram: { dariKm: number; sampaiKm: number; naik: number } | null;
    pos: Array<{ urutan: number; nama: string; km: number; aksen: AksenPos }>;
    keterangan: string;
}

export interface ButirPersiapan {
    judul: string;
    keterangan: string | null;
    wajib: boolean;
}

export interface BarisSumber {
    bagian: string;
    jenis: JenisSumber | null;
    isi: string;
    url: string | null;
}

export interface PetaJalurData {
    geometry: LineString | MultiLineString | null;
    penanda: Array<Penanda & { urutan: number; aksen: AksenPos }>;
}

export interface Segmen {
    id: number;
    judul: string;
    ringkas: string;
    description: string | null;
}

export interface StatusResmiRinci {
    status: StatusResmi;
    statusLabel: string;
    scope: string | null;
    source: string;
    /** Jenis sumber catatan penentu (SourceType); null bila tidak tercatat. */
    jenisSumber: JenisSumber | null;
    reason: string | null;
    /** Waktu catatan penentu dalam zona gunung beserta penandanya; null tanpa stempel. */
    waktu: string | null;
    /** Awalan waktu dari kolom yang dipakai: "Diverifikasi", "Diterbitkan", atau "Berlaku sejak". */
    awalanWaktu: string | null;
    /** Keadaan kesegaran (DataFreshnessService::keadaanDari); null tanpa catatan. */
    kesegaran: string | null;
}

export interface Perizinan {
    authority: string;
    kuotaHarian: string | null;
    batasPemesanan: string | null;
    durasiMaksimum: string | null;
    pemandu: string;
    notes: string | null;
    bookingUrl: string | null;
    diverifikasi: string | null;
    source: string;
    jenisSumber: JenisSumber | null;
}

export interface BarisCuaca {
    id: number;
    waktu: string;
    cuaca: string;
    suhu: string;
    kelembapan: string;
}

/**
 * Prakiraan untuk tanggal trip pembaca (ruling W3-6b, R-013): "menunggu" sebelum jendela
 * terbit BMKG tercapai, "tersedia" dengan baris prakiraan tanggal itu sendiri (disaring
 * dari forecast penuh, bukan delapan baris pertama kartu cuaca), atau "tidakTercakup"
 * ketika jendela terbit sudah tercapai tetapi datanya belum sampai ke tanggal itu.
 */
export type UntukTrip = null | { status: 'menunggu'; pesan: string } | { status: 'tersedia'; judul: string; forecast: BarisCuaca[] } | { status: 'tidakTercakup'; pesan: string };

export type Cuaca = (
    | { available: false; message: string; source: string }
    | {
          available: true;
          referenceArea: string;
          message: string | null;
          timezoneLabel: string;
          forecast: BarisCuaca[];
          source: string;
          freshnessLabel: string | null;
          diperbarui: string | null;
      }
) & {
    untukTrip: UntukTrip;
};

export interface LaporanKomunitas {
    id: number;
    tags: string[];
    note: string | null;
    photoUrl: string | null;
    photoAlt: string;
    milikSaya: boolean;
    sudahTerimaKasih: boolean;
    thanksCount: number;
    pelapor: string;
    dilaporkan: string;
    tanggalPendakian: string;
    kesegaran: string;
    segmen: string | null;
}

export interface Komunitas {
    available: boolean;
    message: string | null;
    reports: LaporanKomunitas[];
}
