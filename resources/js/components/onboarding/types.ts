import type { JalurKatalog } from '@/components/trails/PemilihJalur';
import type { OpsiKartu } from './PilihanKartu';

/** Cara mengisi langkah 2 (App\Http\Requests\StoreProfileSetupRequest). */
export type CaraPengalaman = 'KATALOG' | 'MANUAL' | 'BELUM';

/** Isian onboarding, sama dengan kunci yang dibaca StoreProfileSetupRequest. */
export interface NilaiOnboarding {
    experience_level: string;
    cara_pengalaman: CaraPengalaman;
    jalur_terberat_id: number | '';
    completed_hikes_count: number;
    terrain_experience: string[];
    navigation_experience: string;
    highest_elevation_gain_m: number | string;
    durasi_terlama_jam: string;
    preferred_trip_type: string;
    preferred_duration: string;
    interest_regions: string[];
    max_elevation_gain_preference_m: number | string;
}

/** Hasil pencarian jalur terberat (App\Services\Profil\TurunanPengalaman::baris). */
export interface JalurTerberat extends JalurKatalog {
    turunan: { tanjakan_m: number | null; durasi_menit: number | null; medan: string[]; navigasi: string | null };
    tulisan: { tanjakan: string; durasi: string; medan: string; navigasi: string };
}

export interface PilihanOnboarding {
    tingkat: OpsiKartu[];
    navigasi: OpsiKartu[];
    medan: OpsiKartu[];
    jenisTrip: OpsiKartu[];
    durasi: OpsiKartu[];
    wilayah: OpsiKartu[];
}

