import type { DataLapangan } from '@/components/mendaki/types';

/**
 * Penyimpanan paket lapangan di perangkat (spec v3 bagian 10.4 dan 10.6; rencana induk W6 butir
 * 5 dan 7; ruling W6-R9, W6-R10, W6-9i).
 *
 * Satu basis data IndexedDB per pengguna, `memount-luring-<kunci>`, dengan kunci HMAC id pengguna
 * dari server (App\Support\KunciPerangkat, Tugas 10), sehingga akun lain di peramban yang sama
 * tidak membuka paket ini. Kunci disimpan di localStorage hanya supaya shell luring, yang tidak
 * dapat bertanya ke server, tahu basis data mana yang dibuka; keluar akun menghapus keduanya.
 *
 * Isi: bagian paket persis seperti bita dari server beserta SHA-256-nya, rekaman paket per
 * versi, keadaan sesi mode lapangan terakhir, dan antrean aksi (toko `antrean`, dikelola
 * penyimpananIndexedDb di components/mendaki/antrean.ts, ruling W6-9a). Tanpa token dan tanpa
 * data orang lain (spec v3 bagian 10.6).
 *
 * Koneksi tidak pernah menahan penghapusan (PT9-7): tab lain yang menghapus atau meningkatkan
 * basis data ini (keluar akun) membuat koneksi di sini menutup diri, dan penghapusan juga menutup
 * kuncinya di tab ini sampai muat penuh (tinjauan Tugas 11 m-7). Setiap penulisan baru
 * selesai sesudah transaksinya tersimpan, bukan sesudah permintaannya diterima (PT9-20), karena
 * transaksi masih dapat batal saat disimpan (ruang penuh).
 *
 * Hanya berimpor tipe (ruling L3): OfflineShellTest menjalankannya di Node.
 */

export const AWALAN_BASIS_DATA = 'memount-luring-';
export const KUNCI_PERANGKAT = 'memount-perangkat';

const VERSI_SKEMA = 1;
const BAGIAN = 'bagian';
const PAKET = 'paket';
const SESI = 'sesi';
const ANTREAN = 'antrean';

/** Penghapusan yang terhalang tab lain ditunggu paling lama ini sebelum dilaporkan tertunda. */
const BATAS_HAPUS_MS = 3000;

export type NamaBagian = 'lapangan' | 'rencana' | 'dinamis';

export interface BagianManifest {
    nama: NamaBagian;
    url: string;
    ukuran: number;
    sha256: string;
}

/** Manifest paket dari server (kontrak keluaran Tugas 8; dataContoh dari Tugas 9, di luar hash versi). */
export interface ManifestPaket {
    versi: string;
    trip: { id: number; nama: string; jalur: string; gunung: string | null; tanggal: string };
    bagian: BagianManifest[];
    ukuran: number;
    ambang: { bukaJam: number };
    /** Berurutan tetap: sumber garis jalur, peta dasar, lalu cuaca (PT8-4). */
    atribusi: string[];
    dataContoh?: boolean;
}

export interface RekamanBagian {
    tripId: number;
    versi: string;
    nama: NamaBagian;
    teks: string;
    sha256: string;
}

export interface RekamanPaket {
    tripId: number;
    versi: string;
    manifest: ManifestPaket;
    /** Benar hanya setelah setiap bagian dibaca ulang dari penyimpanan dan cocok dengan manifest. */
    siap: boolean;
    diverifikasi: string | null;
}

/** Keadaan sesi terakhir yang dilihat mode lapangan daring; paket sendiri tidak membawanya. */
export interface RekamanSesi {
    tripId: number;
    trip: Pick<DataLapangan['trip'], 'berlangsung' | 'mulai' | 'jedaSejak' | 'jedaDetik'>;
    tercapai: number;
    sesiId: number | null;
    rekamJejak: boolean;
    jejak: string | null;
    diperbarui: string;
}

/** Kunci satu bagian di toko `bagian`: [tripId, versi, nama]. */
export type KunciBagian = [number, string, string];

/** Rencana hapus satu penggantian versi (ruling W6-9i). */
export interface RencanaGantiVersi {
    paket: Array<[number, string]>;
    bagian: KunciBagian[];
}

export function namaBasisData(kunci: string): string {
    return `${AWALAN_BASIS_DATA}${kunci}`;
}

/** localStorage dapat menolak (mode privat, situs diblokir); kegagalannya berarti tanpa paket. */
export function kunciTersimpan(): string | null {
    try {
        return window.localStorage.getItem(KUNCI_PERANGKAT);
    } catch {
        return null;
    }
}

export function simpanKunci(kunci: string): void {
    try {
        window.localStorage.setItem(KUNCI_PERANGKAT, kunci);
    } catch {
        // Tanpa localStorage shell luring tidak menemukan paket; halaman daring tetap berjalan.
    }
}

/**
 * Kunci perangkat untuk perekam jejak (ruling W6-11f): kunci props halaman daring, atau kunci
 * tersimpan di shell luring, yang merakit props tanpa perangkat. Halaman daring untuk pengguna masuk
 * selalu membawa kuncinya, jadi kunci tersimpan (bisa milik akun lain) tidak pernah menggantikannya.
 */
export function kunciPerekam(kunciHalaman: string | null, tersimpan: string | null): string | null {
    return kunciHalaman ?? tersimpan;
}

export function lupakanKunci(): void {
    try {
        window.localStorage.removeItem(KUNCI_PERANGKAT);
    } catch {
        // Tidak ada yang perlu dilupakan.
    }
}

const terbuka = new Map<string, Promise<IDBDatabase>>();

/**
 * Kunci yang ditutup di tab ini sesudah keluar akun atau dibersihkan penjaga ganti akun (ruling
 * W6-11d), sampai muat penuh. Halaman yang dipulihkan tombol Kembali sesudah keluar tidak membuka,
 * dan karena itu tidak membuat ulang, basis data paket kunci itu. Perbaikan penuh (encryptHistory,
 * clearHistory) tetap W6-K5.
 */
const tertutup = new Set<string>();

export function tutupKunci(kunci: string): void {
    tertutup.add(kunci);
}

export function kunciTertutup(kunci: string): boolean {
    return tertutup.has(kunci);
}

/**
 * Membuka basis data paket satu pengguna, satu koneksi per kunci. Janji yang gagal atau terhalang
 * dibuang dari peta, supaya percobaan berikutnya membuka ulang (PT9-7), dan versionchange dari
 * tab lain menutup koneksi ini. Kunci yang sudah ditutup di tab ini ditolak tanpa membuka apa pun
 * (ruling W6-11d).
 */
export function buka(kunci: string): Promise<IDBDatabase> {
    if (tertutup.has(kunci)) {
        return Promise.reject(new Error('Kunci perangkat ini sudah ditutup di tab ini.'));
    }

    const ada = terbuka.get(kunci);

    if (ada) {
        return ada;
    }

    const janji: Promise<IDBDatabase> = new Promise<IDBDatabase>((selesai, gagal) => {
        let terhalang = false;
        const permintaan = indexedDB.open(namaBasisData(kunci), VERSI_SKEMA);
        const lepas = () => {
            if (terbuka.get(kunci) === janji) {
                terbuka.delete(kunci);
            }
        };

        permintaan.onupgradeneeded = () => {
            const db = permintaan.result;
            db.createObjectStore(BAGIAN, { keyPath: ['tripId', 'versi', 'nama'] });
            db.createObjectStore(PAKET, { keyPath: ['tripId', 'versi'] });
            db.createObjectStore(SESI, { keyPath: 'tripId' });
            db.createObjectStore(ANTREAN);
        };
        permintaan.onsuccess = () => {
            const db = permintaan.result;

            if (terhalang) {
                db.close();

                return;
            }

            // newVersion null berarti basis data ini sedang dihapus (keluar atau ganti akun di tab
            // mana pun): kuncinya ditutup di tab ini juga, supaya halaman yang masih terbuka tidak
            // membuatnya ulang (tinjauan Tugas 11 m-7). Kenaikan VERSI_SKEMA kelak membawa
            // newVersion angka, jadi hanya melepas koneksi dan membuka ulang seperti sebelumnya.
            db.onversionchange = (peristiwa?: IDBVersionChangeEvent) => {
                db.close();
                lepas();

                if (peristiwa?.newVersion === null) {
                    tutupKunci(kunci);
                }
            };
            selesai(db);
        };
        permintaan.onerror = () => {
            lepas();
            gagal(permintaan.error);
        };
        permintaan.onblocked = () => {
            terhalang = true;
            lepas();
            gagal(new Error('Basis data paket sedang dipakai tab lain.'));
        };
    });

    terbuka.set(kunci, janji);

    return janji;
}

/**
 * Apakah basis data paket pengguna ini ada, tanpa pernah membukanya (membuka berarti membuatnya):
 * indexedDB.databases() bila peramban memilikinya, selain itu kunci perangkat yang tersimpan.
 * Kunci yang sudah ditutup di tab ini selalu dianggap tanpa basis data (ruling W6-11d).
 */
export async function adaBasisData(kunci: string): Promise<boolean> {
    if (tertutup.has(kunci)) {
        return false;
    }

    const nama = namaBasisData(kunci);

    if (typeof indexedDB !== 'undefined' && typeof indexedDB.databases === 'function') {
        try {
            return (await indexedDB.databases()).some((d) => d.name === nama);
        } catch {
            // Peramban yang menolak daftar basis data jatuh ke kunci tersimpan.
        }
    }

    return kunciTersimpan() === kunci;
}

/** Selesai pada oncomplete; gagal pada onerror dan onabort (PT9-20). */
export function selesaiTransaksi(transaksi: IDBTransaction): Promise<void> {
    return new Promise<void>((selesai, gagal) => {
        transaksi.oncomplete = () => selesai();
        transaksi.onerror = () => gagal(transaksi.error ?? new Error('Transaksi paket gagal.'));
        transaksi.onabort = () => gagal(transaksi.error ?? new Error('Transaksi paket dibatalkan.'));
    });
}

function minta<T>(permintaan: IDBRequest<T>): Promise<T> {
    return new Promise((selesai, gagal) => {
        permintaan.onsuccess = () => selesai(permintaan.result);
        permintaan.onerror = () => gagal(permintaan.error);
    });
}

function toko(db: IDBDatabase, nama: string, mode: IDBTransactionMode): IDBObjectStore {
    return db.transaction(nama, mode).objectStore(nama);
}

async function tulisSatu(db: IDBDatabase, nama: string, rekaman: unknown): Promise<void> {
    const transaksi = db.transaction(nama, 'readwrite');
    const selesai = selesaiTransaksi(transaksi);

    transaksi.objectStore(nama).put(rekaman);
    await selesai;
}

/** Rentang kunci semua rekaman satu trip di toko berkunci [tripId, ...]: larik lebih besar dari string. */
function rentangTrip(tripId: number): IDBKeyRange {
    return IDBKeyRange.bound([tripId], [tripId, []]);
}

/** Rentang kunci bagian satu versi: string apa pun di antara '' dan '￿'. */
function rentangVersi(tripId: number, versi: string): IDBKeyRange {
    return IDBKeyRange.bound([tripId, versi, ''], [tripId, versi, '￿']);
}

export async function tulisBagian(db: IDBDatabase, rekaman: RekamanBagian): Promise<void> {
    await tulisSatu(db, BAGIAN, rekaman);
}

export async function bacaBagian(db: IDBDatabase, tripId: number, versi: string): Promise<RekamanBagian[]> {
    return minta(toko(db, BAGIAN, 'readonly').getAll(rentangVersi(tripId, versi)) as IDBRequest<RekamanBagian[]>);
}

export async function tulisPaket(db: IDBDatabase, rekaman: RekamanPaket): Promise<void> {
    await tulisSatu(db, PAKET, rekaman);
}

export async function semuaPaket(db: IDBDatabase): Promise<RekamanPaket[]> {
    return minta(toko(db, PAKET, 'readonly').getAll() as IDBRequest<RekamanPaket[]>);
}

/** Menghapus satu versi paket beserta bagiannya. */
export async function hapusPaket(db: IDBDatabase, tripId: number, versi: string): Promise<void> {
    const transaksi = db.transaction([PAKET, BAGIAN], 'readwrite');
    const selesai = selesaiTransaksi(transaksi);

    transaksi.objectStore(PAKET).delete([tripId, versi]);
    transaksi.objectStore(BAGIAN).delete(rentangVersi(tripId, versi));
    await selesai;
}

/**
 * Rencana hapus saat versi baru satu trip lolos pemeriksaan (ruling W6-9i), fungsi murni:
 * rekaman trip yang sama yang diperiksa LEBIH LAMA dari versi baru beserta bagiannya, dan bagian
 * yatim versi lain trip itu (bagian tanpa rekaman yang dipertahankan, PT10-13). Versi baru dan
 * versi yang diperiksa lebih baru (diunduh tab lain sesudahnya) tidak pernah masuk rencana, jadi
 * dua tab yang mengganti versi bersamaan tidak meninggalkan perangkat tanpa paket.
 *
 * Bagian yatim termasuk bagian unduhan yang sedang berjalan di tab lain dan belum menulis
 * rekamannya; unduhan itu lalu gagal diperiksa dan pendaki mengunduh ulang. Arahnya hati-hati:
 * paket yang tidak utuh tidak pernah disebut siap.
 */
export function rencanaGantiVersi(paketAda: RekamanPaket[], kunciBagian: KunciBagian[], baru: RekamanPaket): RencanaGantiVersi {
    const waktuBaru = baru.diverifikasi ?? '';
    const tripIni = paketAda.filter((p) => p.tripId === baru.tripId && p.versi !== baru.versi);
    const dihapus = tripIni.filter((p) => (p.diverifikasi ?? '') < waktuBaru);
    const dipertahankan = new Set([baru.versi, ...tripIni.filter((p) => !dihapus.includes(p)).map((p) => p.versi)]);

    return {
        paket: dihapus.map((p): [number, string] => [p.tripId, p.versi]),
        bagian: kunciBagian.filter(([tripId, versi]) => tripId === baru.tripId && !dipertahankan.has(versi)),
    };
}

/**
 * Menulis rekaman versi baru dan menjalankan rencanaGantiVersi dalam SATU transaksi readwrite
 * atas `paket` dan `bagian` (ruling W6-9i): tab lain tidak dapat menyisip di antara baca dan
 * hapus, dan transaksi yang batal tidak menghapus apa pun.
 */
export async function gantiVersi(db: IDBDatabase, rekaman: RekamanPaket): Promise<void> {
    const transaksi = db.transaction([PAKET, BAGIAN], 'readwrite');
    const selesai = selesaiTransaksi(transaksi);
    const paket = transaksi.objectStore(PAKET);
    const bagian = transaksi.objectStore(BAGIAN);
    const bacaPaket = paket.getAll(rentangTrip(rekaman.tripId)) as IDBRequest<RekamanPaket[]>;
    const bacaKunci = bagian.getAllKeys(rentangTrip(rekaman.tripId));
    let sisa = 2;

    const lanjut = () => {
        sisa -= 1;

        if (sisa > 0) {
            return;
        }

        const rencana = rencanaGantiVersi(bacaPaket.result, bacaKunci.result as unknown as KunciBagian[], rekaman);

        paket.put(rekaman);
        rencana.paket.forEach((kunci) => paket.delete(kunci));
        rencana.bagian.forEach((kunci) => bagian.delete(kunci));
    };

    bacaPaket.onsuccess = lanjut;
    bacaKunci.onsuccess = lanjut;
    await selesai;
}

export async function tulisSesi(db: IDBDatabase, rekaman: RekamanSesi): Promise<void> {
    await tulisSatu(db, SESI, rekaman);
}

export async function bacaSesi(db: IDBDatabase, tripId: number): Promise<RekamanSesi | null> {
    return (await minta(toko(db, SESI, 'readonly').get(tripId) as IDBRequest<RekamanSesi | undefined>)) ?? null;
}

/**
 * Izin rekam jejak di shell luring dicabut sesudah Profil menyimpan rekam jejak "mati" (ruling
 * W6-9h, W6-P2f): `rekamJejak` di semua rekaman sesi basis data paket pengguna ini menjadi false
 * dalam satu transaksi, field lain tetap. Shell membaca izin itu dari rekaman sesi, jadi sesudah
 * ini shell perangkat ini tidak merekam lagi. Basis data paket yang tidak ada tidak dibuat
 * (adaBasisData), dan kunci yang sudah ditutup di tab ini tidak disentuh (W6-11d). Selesai sesudah
 * transaksinya tersimpan.
 */
export async function cabutRekamJejakSesi(kunci: string): Promise<void> {
    if (!(await adaBasisData(kunci))) {
        return;
    }

    const db = await buka(kunci);
    const transaksi = db.transaction(SESI, 'readwrite');
    const selesai = selesaiTransaksi(transaksi);
    const tokoSesi = transaksi.objectStore(SESI);
    const semua = tokoSesi.getAll() as IDBRequest<RekamanSesi[]>;

    semua.onsuccess = () => {
        for (const rekaman of semua.result) {
            if (rekaman.rekamJejak) {
                tokoSesi.put({ ...rekaman, rekamJejak: false });
            }
        }
    };

    await selesai;
}

/**
 * Menghapus seluruh basis data paket satu pengguna (keluar akun, ganti akun). Koneksi milik
 * halaman ini ditutup lebih dulu. Tab lain yang masih memegang koneksi mendapat versionchange
 * dan menutupnya sendiri; bila penghapusan tetap terhalang lebih dari tiga detik, hasilnya
 * 'tertunda' (peramban menghapusnya begitu koneksi terakhir tertutup), supaya pemanggil dapat
 * menulis kalimat yang jujur (Tugas 11).
 */
export async function hapusBasisData(kunci: string): Promise<'terhapus' | 'tertunda'> {
    const janji = terbuka.get(kunci);

    terbuka.delete(kunci);

    if (janji) {
        try {
            (await janji).close();
        } catch {
            // Koneksi yang tidak pernah terbuka tidak perlu ditutup.
        }
    }

    return new Promise((selesai, gagal) => {
        let pewaktu: ReturnType<typeof setTimeout> | null = null;
        const permintaan = indexedDB.deleteDatabase(namaBasisData(kunci));

        permintaan.onsuccess = () => {
            if (pewaktu !== null) {
                clearTimeout(pewaktu);
            }

            selesai('terhapus');
        };
        permintaan.onerror = () => {
            if (pewaktu !== null) {
                clearTimeout(pewaktu);
            }

            gagal(permintaan.error);
        };
        permintaan.onblocked = () => {
            pewaktu ??= setTimeout(() => selesai('tertunda'), BATAS_HAPUS_MS);
        };
    });
}

/** SHA-256 heksadesimal dari teks UTF-8, sama dengan hash('sha256', ...) di server, tanpa normalisasi. */
export async function sha256Hex(teks: string): Promise<string> {
    const hasil = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(teks));

    return Array.from(new Uint8Array(hasil), (b) => b.toString(16).padStart(2, '0')).join('');
}
