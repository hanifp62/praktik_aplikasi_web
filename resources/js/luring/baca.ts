import { antreanSaatIni } from '@/components/mendaki/antrean';
import { rakitDataLapangan, terapkanAntrean, type DataPaket } from './rakit';
import { adaBasisData, bacaBagian, bacaSesi, buka, semuaPaket, type RekamanBagian, type RekamanPaket } from './simpan';

/**
 * Membaca paket dari perangkat saja, tanpa jaringan: dipakai shell luring, sakelar "Pakai paket
 * saja" di mode lapangan daring, dan pintu unduh (spec v3 bagian 10.2 dan 10.4). Basis data paket
 * tidak pernah dibuat untuk pengguna yang belum mengunduh (PT10-16).
 */

export type { DataPaket } from './rakit';

export function teksBagian(bagian: RekamanBagian[], nama: RekamanBagian['nama']): string {
    const ada = bagian.find((b) => b.nama === nama);

    if (!ada) {
        throw new Error(`Bagian ${nama} tidak ada di paket.`);
    }

    return ada.teks;
}

/** Satu versi per trip: yang terakhir lolos pemeriksaan. */
export function terbaruPerTrip(paket: RekamanPaket[]): RekamanPaket[] {
    const per = new Map<number, RekamanPaket>();

    for (const p of paket.filter((p) => p.siap)) {
        const ada = per.get(p.tripId);

        if (!ada || (p.diverifikasi ?? '') > (ada.diverifikasi ?? '')) {
            per.set(p.tripId, p);
        }
    }

    return [...per.values()];
}

/** Paket "Siap luring" trip ini di perangkat; null tanpa membuka basis data bila belum pernah dibuat (PT10-16). */
export async function paketSiap(kunci: string, tripId: number): Promise<RekamanPaket | null> {
    if (!(await adaBasisData(kunci))) {
        return null;
    }

    const db = await buka(kunci);

    return terbaruPerTrip(await semuaPaket(db)).find((p) => p.tripId === tripId) ?? null;
}

/**
 * Data mode lapangan dari satu paket: bagian lapangan dan laporan dinamis, keadaan sesi terakhir
 * di perangkat, lalu aksi yang masih menunggu di antrean dan dibuat sesudah rekaman sesi itu
 * (ruling W6-9d). Penyimpanan antrean dipasang pemanggil lebih dulu (shell luring) atau oleh
 * mode lapangan daring, supaya antreanSaatIni membaca antrean pengguna ini.
 */
export async function dataLapanganDariPaket(kunci: string, paket: RekamanPaket): Promise<DataPaket> {
    const db = await buka(kunci);
    const bagian = await bacaBagian(db, paket.tripId, paket.versi);
    const lapangan = JSON.parse(teksBagian(bagian, 'lapangan')) as DataPaket;
    const sesi = await bacaSesi(db, paket.tripId);
    const rakitan = rakitDataLapangan(lapangan, JSON.parse(teksBagian(bagian, 'dinamis')), sesi);

    return { ...terapkanAntrean(rakitan, await antreanSaatIni(), sesi ? Date.parse(sesi.diperbarui) : null), dataContoh: lapangan.dataContoh };
}
