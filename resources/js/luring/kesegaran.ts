/**
 * Data dinamis di paket lapangan (spec v3 bagian 10.3; rencana induk W6 butir 6; ruling W6-R8,
 * W6-8b, W6-8c, dan W6-8e). Semuanya "terakhir diketahui" dengan waktunya, tidak pernah tampil
 * sebagai keadaan terkini (R-013):
 * - Buka turun menjadi "belum dapat dipastikan" sesudah waktu yang dihitung server,
 *   turunSejak = min(verified_at + Q9 jam, expires_at). Waktu yang tidak tercatat atau tidak
 *   terbaca juga turun (gagal tertutup). Buka tidak pernah berarti aman (R-012).
 * - Tutup dan Dibatasi tidak pernah turun: tampil dengan umur cek mulai satu hari dan masa
 *   berlakunya, ditambah tanda bila masa berlaku itu sudah lewat.
 * - Belum diketahui tetap Belum diketahui (R-006).
 * - Pembatasan segmen dan area terbatas ditulis sebagai kalimat sendiri; keduanya tidak
 *   pernah mengubah status jalur (R-009, R-010).
 *
 * Modul ini sengaja tanpa impor: FieldPackStalenessTest menjalankannya di Node dari PHPUnit.
 */

export interface PembatasanPaket {
    segmenId: number;
    segmen: string;
    nilai: 'CLOSED' | 'RESTRICTED';
    label: string;
    alasan: string | null;
    dicekTeks: string | null;
}

export interface AreaTerbatasPaket {
    id: number;
    nama: string;
    alasan: string | null;
}

export interface StatusPaket {
    nilai: 'OPEN' | 'CLOSED' | 'RESTRICTED' | 'UNKNOWN';
    label: string;
    sumber: string | null;
    jenisSumber: string | null;
    alasan: string | null;
    /** verified_at, ISO 8601 UTC; null bila tidak tercatat. */
    dicek: string | null;
    dicekTeks: string | null;
    /** Masa berlaku catatan dalam zona gunung, siap tampil; null bila tidak ada. */
    berlaku: string | null;
    /** expires_at, ISO 8601 UTC; null bila tidak ada. */
    berlakuIso: string | null;
    /** Waktu status Buka berhenti dapat dipercaya, ISO 8601 UTC; null selain Buka atau tanpa tanggal cek. */
    turunSejak: string | null;
    pembatasan: PembatasanPaket[];
    areaTerbatas: AreaTerbatasPaket[];
}

export interface KalimatStatus {
    judul: string;
    kalimat: string;
    /** Buka yang sudah melewati ambangnya: tampil sebagai belum dapat dipastikan. */
    turun: boolean;
}

const R012 = 'Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.';

const SEHARI_MS = 86_400_000;

export function kalimatStatusLuring(status: StatusPaket, sekarangMs: number): KalimatStatus {
    if (status.nilai === 'UNKNOWN') {
        return { judul: 'Belum diketahui', kalimat: 'Status resmi jalur ini belum diketahui saat paket dibuat. Pastikan di pos registrasi.', turun: false };
    }

    const dicek = status.dicekTeks ? `Dicek ${status.dicekTeks}${umur(status.dicek, sekarangMs)}` : 'Tanggal cek tidak tercatat';
    const sumber = status.sumber ? `, ${status.sumber}` : '';
    const berlaku = status.berlaku ? ` Berlaku sampai ${status.berlaku}.` : '';

    if (status.nilai === 'OPEN') {
        const batas = status.turunSejak === null ? Number.NaN : Date.parse(status.turunSejak);

        if (!Number.isFinite(batas) || sekarangMs >= batas) {
            return {
                judul: 'Status belum dapat dipastikan',
                kalimat: status.dicekTeks
                    ? `Status belum dapat dipastikan sejak ${status.dicekTeks}. Pastikan di pos registrasi.`
                    : 'Status belum dapat dipastikan: tanggal cek tidak tercatat. Pastikan di pos registrasi.',
                turun: true,
            };
        }

        return { judul: `Terakhir diketahui: ${status.label}`, kalimat: `${dicek}${sumber}.${berlaku} ${R012}`, turun: false };
    }

    const lewat = status.berlakuIso !== null && sekarangMs >= Date.parse(status.berlakuIso) ? ' Masa berlaku itu sudah lewat; pastikan di pos registrasi.' : '';

    return { judul: `Terakhir diketahui: ${status.label}`, kalimat: `${dicek}${sumber}.${alasan(status.alasan)}${berlaku}${lewat}`, turun: false };
}

/** Satu segmen Tutup atau Dibatasi, dengan tanggal cek catatannya. */
export function kalimatPembatasan(p: PembatasanPaket): string {
    const cek = p.dicekTeks ? `dicek ${p.dicekTeks}` : 'tanggal cek tidak tercatat';

    return `Segmen ${p.segmen}: ${p.label}, ${cek}.${alasan(p.alasan)}`;
}

/**
 * Satu area terbatas yang bertaut ke jalur, ke gunungnya, atau memotong garis jalur. Kalimatnya
 * benar untuk ketiga kaitan itu, jadi tidak menyebut letaknya terhadap jalur (ruling W6-8m).
 */
export function kalimatAreaTerbatas(a: AreaTerbatasPaket): string {
    return `Area terbatas tercatat untuk jalur atau gunung ini: ${a.nama}.${alasan(a.alasan)}`;
}

/** " ({n} hari lalu)" sesudah teks cek bila umurnya paling sedikit satu hari; selain itu kosong. */
function umur(dicek: string | null, sekarangMs: number): string {
    const hari = dicek === null ? Number.NaN : Math.floor((sekarangMs - Date.parse(dicek)) / SEHARI_MS);

    return Number.isFinite(hari) && hari >= 1 ? ` (${hari} hari lalu)` : '';
}

/** " Alasan: {alasan}." tanpa titik ganda bila alasannya sudah berakhir titik; kosong bila tidak ada. */
function alasan(teks: string | null): string {
    const bersih = teks?.replace(/[\s.]+$/u, '') ?? '';

    return bersih === '' ? '' : ` Alasan: ${bersih}.`;
}
