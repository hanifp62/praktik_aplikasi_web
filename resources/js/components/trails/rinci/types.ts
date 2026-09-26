import type { LineString, MultiLineString } from 'geojson';
import type { Penanda } from '@/components/trails/types';
import type { FaktorBar } from '@/components/ui/FactorBars';
import type { LabelFit } from '@/components/ui/FitBadge';
import type { StatusResmi } from '@/components/ui/StatusBadge';

/** Bentuk yang dikirim TrailHeaderResource dan TrailDetailPage: sudah diformat server, tanpa skor (BR-09). */
export interface IdentitasJalur {
    name: string;
    mountain: string;
    mountainId: number;
    province: string | null;
    mdpl: string | null;
}

export interface AngkaBerlabel {
    label: string;
    nilai: string;
    ada: boolean;
}

export interface FitRinci {
    label: LabelFit;
    labelText: string;
    deskripsi: string | null;
    denganRencana: boolean;
    faktor: FaktorBar[];
    tangga: string | null;
}

export interface PetaJalurData {
    geometry: LineString | MultiLineString | null;
    penanda: Penanda[];
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
    reason: string | null;
    diverifikasi: string | null;
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
}

export interface BarisCuaca {
    id: number;
    waktu: string;
    cuaca: string;
    suhu: string;
    kelembapan: string;
}

export type Cuaca =
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

export interface SumberData {
    jalur: string;
    jalurUrl: string | null;
    statusResmi: string;
    cuaca: string;
}
