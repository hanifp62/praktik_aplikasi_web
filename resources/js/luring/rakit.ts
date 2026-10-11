import type { AksiAntrean } from '@/components/mendaki/antrean';
import type { DataLapangan, LaporanLapangan } from '@/components/mendaki/types';
import type { PropsBersama } from '@/types';
import type { StatusPaket } from './kesegaran';
import type { ManifestPaket, RekamanPaket, RekamanSesi } from './simpan';

/**
 * Data mode lapangan dan daftar paket dari paket di perangkat (spec v3 bagian 10.4: satu pohon
 * komponen, dua sumber data). Bagian lapangan membawa data jalur yang stabil tanpa keadaan sesi;
 * keadaan sesi datang dari rekaman terakhir mode lapangan daring di perangkat ini, bila ada.
 * Tanpa rekaman itu pendakian tampil belum dimulai, bukan berjalan sejak waktu tebakan (R-007).
 * Laporan komunitas datang dari potret dinamis dengan tanggal mendakinya.
 *
 * Izin rekam jejak di shell juga berasal dari rekaman sesi terakhir itu (ruling W6-9h, PT9-21).
 * Sejak sisipan W6-P2, Profil yang menyimpan "mati" mengosongkan rekamJejak di semua rekaman sesi
 * basis data paket pengguna itu di perangkat ini (cabutRekamJejakSesi di simpan.ts), jadi shell
 * perangkat ini berhenti merekam. Sisa yang diketahui: perangkat lain, dan tab lain yang menulis
 * rekaman sesi lagi dari mode lapangan basi, tetap memakai izin lamanya sampai dibuka dengan
 * sinyal (residu W6-P2k). Shell tidak pernah mengirim titik; pengirimnya hanya di AppLayout.
 *
 * Tanpa impor nilai (ruling L3): OfflineShellTest menjalankannya di Node dari PHPUnit.
 */

/** Waktu rekaman sesi yang dipakai rakitan, untuk kalimat keadaan mode lapangan (W6-9e, Tugas 10). */
export interface CatatanSesi {
    diperbarui: string;
}

export type DataPaket = DataLapangan & { dataContoh?: boolean; catatanSesi?: CatatanSesi | null };

/** Bagian rencana paket (kontrak keluaran Tugas 8; CetakTrip::rencanaDanAturan dan TripSteps). */
export interface RencanaPaket {
    rencana: { nama: string; tanggal: string; jamMulai: string | null; kembali: string | null; registrasi: string | null };
    aturan: { ringkasan: string; catatan: string | null; dicek: string | null } | null;
    persiapan: PersiapanPaket;
}

export interface PersiapanPaket {
    dikonfirmasi: number;
    berlaku: number;
    jumlah: number;
    butir: Array<{ nama: string; kritis: boolean; keadaan: string; berlaku: boolean }>;
}

/** Bagian dinamis paket (kontrak keluaran Tugas 8): semuanya terakhir diketahui saat paket dibuat. */
export interface DinamisPaket {
    status: StatusPaket;
    cuaca: {
        kalimat: string;
        hari: Array<{ tanggal: string; label: string; kalimat: string | null; baris: Array<{ waktu: string; cuaca: string; suhu: string }> }>;
    };
    laporan: LaporanLapangan[];
}

export function rakitDataLapangan(lapangan: DataPaket, dinamis: { laporan: LaporanLapangan[] }, sesi: RekamanSesi | null): DataPaket {
    // Rekaman trip lain (tidak pernah dibaca shell, tetapi rekaman tetap data dari perangkat) diabaikan.
    const milikTrip = sesi !== null && sesi.tripId === lapangan.trip.id ? sesi : null;

    return {
        ...lapangan,
        sumber: 'paket',
        laporan: dinamis.laporan,
        trip: milikTrip
            ? { ...lapangan.trip, berlangsung: milikTrip.trip.berlangsung, mulai: milikTrip.trip.mulai, jedaSejak: milikTrip.trip.jedaSejak, jedaDetik: milikTrip.trip.jedaDetik }
            : lapangan.trip,
        tercapai: milikTrip?.tercapai ?? lapangan.tercapai,
        sesiId: milikTrip?.sesiId ?? lapangan.sesiId,
        rekamJejak: milikTrip?.rekamJejak ?? lapangan.rekamJejak,
        urls: { ...lapangan.urls, jejak: milikTrip?.jejak ?? lapangan.urls.jejak },
        catatanSesi: milikTrip ? { diperbarui: milikTrip.diperbarui } : null,
    };
}

/** Rekaman sesi dari data mode lapangan daring, untuk disimpan ke paket trip yang sama. */
export function sesiDari(data: DataLapangan, sekarang: Date): RekamanSesi {
    return {
        tripId: data.trip.id,
        trip: { berlangsung: data.trip.berlangsung, mulai: data.trip.mulai, jedaSejak: data.trip.jedaSejak, jedaDetik: data.trip.jedaDetik },
        tercapai: data.tercapai,
        sesiId: data.sesiId,
        rekamJejak: data.rekamJejak,
        jejak: data.urls.jejak,
        diperbarui: sekarang.toISOString(),
    };
}

function waktu(teks: unknown): number | null {
    const ms = typeof teks === 'string' ? Date.parse(teks) : Number.NaN;

    return Number.isFinite(ms) ? ms : null;
}

/**
 * Aksi yang dicatat di perangkat dan masih menunggu sinyal, diterapkan ke keadaan shell (ruling
 * W6-9d): aksi itu fakta yang sudah dicatat pendaki, jadi pos yang sudah dicatat tidak
 * ditanyakan ulang sesudah OS menutup aplikasi. Tidak ada tebakan (R-007).
 *
 * Hanya aksi trip ini (URL tiba, jeda, atau lanjut-nya) yang dibuat SESUDAH rekaman sesi
 * (`sejakMs`; null berarti tanpa rekaman, semua berlaku), diurutkan menurut waktu dibuat lalu id:
 * mode lapangan daring sudah menerapkan aksinya ke layar dan menulis rekaman sesi sesudahnya
 * sementara aksi itu masih di antrean, jadi tanpa syarat ini jeda terhitung dua kali. Sama
 * dengan server (UPDATE bersyarat, W6-R6): tiba hanya menaikkan pos tercapai, jeda hanya bila
 * belum jeda, dan lanjut hanya selagi jeda, menambah lama jeda yang tidak negatif.
 */
export function terapkanAntrean(data: DataPaket, aksi: AksiAntrean[], sejakMs: number | null): DataPaket {
    const { tiba, jeda, lanjut } = data.urls;
    const dibuat = (a: AksiAntrean) => (typeof a.dibuat === 'number' && Number.isFinite(a.dibuat) ? a.dibuat : 0);
    const berlaku = aksi
        .filter((a) => (a.url === tiba || a.url === jeda || a.url === lanjut) && (sejakMs === null || dibuat(a) > sejakMs))
        .sort((a, b) => dibuat(a) - dibuat(b) || (String(a.id) < String(b.id) ? -1 : String(a.id) > String(b.id) ? 1 : 0));

    let tercapai = data.tercapai;
    let jedaSejak = data.trip.jedaSejak;
    let jedaDetik = data.trip.jedaDetik;

    for (const a of berlaku) {
        const pada = waktu(a.isi.pada);

        if (a.url === tiba) {
            const urutan = a.isi.urutan;

            if (typeof urutan === 'number' && Number.isFinite(urutan)) {
                tercapai = Math.max(tercapai, urutan);
            }
        } else if (a.url === jeda) {
            if (jedaSejak === null && pada !== null) {
                jedaSejak = a.isi.pada as string;
            }
        } else if (a.url === lanjut && jedaSejak !== null && pada !== null) {
            const mulaiJeda = waktu(jedaSejak);

            jedaDetik += mulaiJeda === null ? 0 : Math.max(0, Math.round((pada - mulaiJeda) / 1000));
            jedaSejak = null;
        }
    }

    return { ...data, tercapai, trip: { ...data.trip, jedaSejak, jedaDetik } };
}

/**
 * Props halaman Trips/Hike dari paket, satu sumber untuk shell luring dan sakelar "Pakai paket
 * saja" (PT9-1, PT10-1, ruling L1). Semua kunci PropsBersama ada, karena halaman dan turunannya
 * membacanya: tanpa pengguna, tanpa perangkat (shell tidak bertanya ke server dan tidak memegang
 * identitas, jadi mode lapangan tidak mengganti penyimpanan antrean yang dipasang shell, dan
 * pengirim jejak tidak pernah berjalan di sini), tanpa peta dasar dengan atribusi sumber garis
 * dari manifest (indeks 0, PT8-4), lencana Data contoh dari paket, dan judul yang menyebut jalur
 * beserta gunungnya.
 */
export function propsHalamanLuring(data: DataPaket, manifest: ManifestPaket, merek: string) {
    return {
        auth: { user: null },
        merek: { nama: merek, tagline: '' },
        jumlahSimpanan: null,
        pendakianAktif: null,
        tema: 'system',
        tampilan: { efek: 'otomatis', gerak: 'sistem' },
        flash: { status: null, kabarAksi: null, timbanganPenuh: null, simpananTerhapus: null, simpananSudahAda: null, tautanBagikan: null, butirDiubah: null },
        adminNav: null,
        perangkat: null,
        ...data,
        title: `Mode lapangan dari paket: ${[data.trip.jalur, data.trip.gunung].filter(Boolean).join(', ')}`,
        demo: data.dataContoh ?? manifest.dataContoh ?? false,
        peta: { styleUrl: null, rasterTiles: [], attribution: manifest.atribusi[0] ?? 'Sumber garis jalur belum tercatat.', maxZoom: 17 },
        errors: {},
    } satisfies PropsBersama & DataLapangan;
}

/**
 * Jendela laporan komunitas yang dipakai PaketLapangan saat paket dibuat: hari dari
 * hiking.conditions.community_recent_days dan batas dari community_report_limit. Paket tidak
 * membawa kedua angka itu, jadi klien memegangnya di satu tempat ini, dan OfflineShellTest
 * memeriksa keduanya sama dengan konfigurasi server (tinjauan m-4).
 */
export const JENDELA_LAPORAN = { hari: 30, batas: 10 };

/**
 * Hitungan laporan komunitas di daftar paket (PT9-14 bagian a). Mode lapangan menampilkan satu
 * laporan terbaru untuk segmen pos yang dituju (ModeLapangan.tsx), jadi laporan tanpa segmen tidak
 * tampil di sana. Angka jendelanya dari parameter, bawaannya JENDELA_LAPORAN.
 */
export function kalimatLaporanPaket(laporan: LaporanLapangan[], jendela: { hari: number; batas: number } = JENDELA_LAPORAN): string {
    if (laporan.length === 0) {
        return `Tidak ada laporan komunitas ${jendela.hari} hari terakhir saat paket dibuat.`;
    }

    const tanpaSegmen = laporan.filter((l) => l.segmen === null).length;
    const kalimat = [`${laporan.length} laporan komunitas ${jendela.hari} hari terakhir saat paket dibuat, paling banyak ${jendela.batas} terbaru.`];

    if (tanpaSegmen < laporan.length) {
        kalimat.push('Yang bersegmen tampil per segmen di mode lapangan, satu laporan terbaru untuk setiap segmen.');
    }

    if (tanpaSegmen > 0) {
        kalimat.push(`${tanpaSegmen} laporan tanpa segmen tidak tampil di mode lapangan.`);
    }

    return kalimat.join(' ');
}

/** Hitungan persiapan, bukan persen (R-005): butir Tidak berlaku tidak ikut dihitung (PT8-12). */
export function kalimatPersiapanPaket(persiapan: PersiapanPaket): string {
    if (persiapan.jumlah === 0) {
        return 'Daftar persiapan belum ada saat paket dibuat.';
    }

    if (persiapan.berlaku === 0) {
        return 'Tidak ada butir persiapan yang berlaku.';
    }

    return `${persiapan.dikonfirmasi} dari ${persiapan.berlaku} butir dikonfirmasi.`;
}

/** Satu hari prakiraan di daftar paket, siap tulis. */
export interface HariCuacaTampil {
    tanggal: string;
    label: string;
    /** "{waktu berzona}: {cuaca}, {suhu}" per baris BMKG; kosong bila hari itu tanpa baris. */
    baris: string[];
    /** Kalimat hari tanpa baris; null bila ada baris. */
    kalimat: string | null;
}

/**
 * Baris cuaca per hari untuk daftar paket (W6-9c; tinjauan m-2 bagian b). Hari yang kalimatnya
 * sama dengan kalimat ringkas cuaca tidak diulang: PaketLapangan memakai kalimat hari pertama
 * sebagai kalimat ringkas bila tidak ada baris sama sekali.
 */
export function barisCuacaPaket(cuaca: DinamisPaket['cuaca']): HariCuacaTampil[] {
    return cuaca.hari
        .filter((h) => h.baris.length > 0 || h.kalimat !== cuaca.kalimat)
        .map((h) => ({
            tanggal: h.tanggal,
            label: h.label,
            baris: h.baris.map((b) => `${b.waktu}: ${b.cuaca}, ${b.suhu}`),
            kalimat: h.baris.length > 0 ? null : h.kalimat,
        }));
}

const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

/**
 * Tanggal trip dari manifest (Y-m-d, tanggal kalender di gunung) sebagai "24 Okt 2026". Ditulis
 * dari angkanya, bukan lewat Date, supaya zona perangkat tidak menggesernya sehari.
 */
export function tanggalTrip(ymd: string): string {
    const [tahun, bulan, hari] = ymd.split('-').map(Number);

    return tahun && bulan && hari && BULAN[bulan - 1] ? `${hari} ${BULAN[bulan - 1]} ${tahun}` : ymd;
}

/**
 * Label satu paket untuk daftar, nama aksesibel tombolnya, dan kalimat shell (tinjauan m-1, butir
 * 3): nama trip, jalur, dan gunung tanpa pengulangan (nama trip bawaan sering sama dengan jalurnya),
 * ditambah tanggal trip, sehingga dua paket di jalur yang sama tetap terbedakan.
 */
export function labelPaket(trip: ManifestPaket['trip']): string {
    return [...new Set([trip.nama, trip.jalur, trip.gunung].filter((b): b is string => Boolean(b))), tanggalTrip(trip.tanggal)].join(', ');
}

/** Kalimat gagal membuka mode lapangan dari satu paket, tampil di kartu paket itu (tinjauan m-1). */
export function kalimatGagalBuka(label: string): string {
    return `Mode lapangan dari paket ${label} belum dapat dibuka di perangkat ini. Pakai peta cetak atau perangkat lain, lalu unduh ulang paket selagi ada sinyal.`;
}

/** Urutan daftar paket: tanggal trip, lalu id trip sebagai pemecah seri (PT9-13). */
export function urutDaftarPaket<T extends { rekaman: RekamanPaket }>(paket: T[]): T[] {
    return [...paket].sort((a, b) => {
        const [ta, tb] = [a.rekaman.manifest.trip.tanggal, b.rekaman.manifest.trip.tanggal];

        return ta < tb ? -1 : ta > tb ? 1 : a.rekaman.tripId - b.rekaman.tripId;
    });
}
