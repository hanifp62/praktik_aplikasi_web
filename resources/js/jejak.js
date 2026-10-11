/*
 * Perekam jejak pendakian.
 *
 * Titik direkam ke IndexedDB selama pendakian, dan modul ini tidak mengirim satu pun (ruling
 * W6-P2). Pengiriman berjalan sesudah pendakian selesai atau dibatalkan, saat ada sinyal, dari
 * halaman aplikasi biasa (resources/js/luring/kirimJejak.ts), dan hanya sesudah server menjawab
 * bahwa sesi itu boleh menerima. Mode lapangan dan shell luring hanya merekam, sesuai persetujuan
 * Profil: "Tidak ada yang dikirim saat Anda masih di jalur."
 *
 * IndexedDB, bukan localStorage: perekaman tiap lima detik selama dua belas jam
 * menghasilkan sekitar 8.600 titik, dan localStorage sinkron serta berbatas sekitar 5 MB.
 * Menulisnya di thread utama tiap lima detik akan terasa pada peranti kelas bawah yang
 * justru paling banyak dipakai.
 *
 * Perekam menyimpan paling banyak satu titik per lima detik per sesi di tab ini (ruling W6-P2d),
 * dengan waktu rekam dibulatkan ke bawah ke detik (ruling W6-P2c). Server memotong waktu ke
 * detik UTC yang sama, jadi kunci titik di perangkat dan di server sama, dan kiriman ulang
 * menimpa baris yang sama.
 *
 * Basis datanya per pengguna sejak W6 (spec v3 bagian 10.6; ruling W6-R10):
 * `jejak-pendakian-<kunci>`, dengan kunci perangkat dari server (App\Support\KunciPerangkat).
 * Sebelumnya satu basis data `jejak-pendakian` dipakai siapa pun yang masuk di peramban itu.
 * Basis data lama itu dihitung dalam pertanyaan sebelum keluar dan dihapus bersama data perangkat
 * lainnya. Pengirim membacanya sesudah basis data milik dan mengirim per sesi hanya bila server
 * menerima sesi itu; sesi akun lain dijawab 403 dan titiknya tetap di perangkat sampai keluar
 * (ruling W6-11e).
 *
 * Fungsi berbasis nama (adaBasisDataJejak, daftarSesi, bacaTitikSesi, jumlahTitikSesi,
 * hapusTitikKunci, hapusTitikSesi) dipakai pengirim dan ringkasan pendakian. Semuanya tidak
 * pernah membuat basis data, dan koneksinya ditutup sesudah dipakai.
 *
 * Koneksi tidak pernah menahan penghapusan (PT11-4, pola simpan.ts PT9-7): penghapusan dari tab
 * mana pun (keluar akun, ganti akun) membuat koneksi di sini menutup diri dan melepas perekam tab
 * ini. Nama basis data yang dihapus juga ditolak aturPengguna sampai muat penuh (tinjauan Tugas 11
 * m-7), jadi perekam tab lain yang masih terbuka tidak membuat ulang basis data akun yang sudah
 * keluar.
 */

export const AWALAN_DB = 'jejak-pendakian-';
export const DB_LAMA = 'jejak-pendakian';
const TOKO = 'titik';

/** Penghapusan yang terhalang tab lain ditunggu paling lama ini sebelum dilaporkan tertunda (sama dengan simpan.ts). */
const BATAS_HAPUS_MS = 3000;

/** Selang paling pendek antara dua titik yang disimpan perekam di tab ini (ruling W6-P2d). */
export const SELANG_TITIK_MS = 5000;

let namaDb = null;
let dbTerbuka = null;

/**
 * Nama basis data jejak yang dihapus selama dokumen ini hidup, dari tab mana pun (tinjauan Tugas
 * 11 m-7). Basis data jejak tidak pernah dinaikkan versinya, dan versionchange penghapusan membawa
 * newVersion null.
 */
const namaTerhapus = new Set();

/** Waktu rekam (ms, sudah dibulatkan ke detik) titik terakhir yang disimpan perekam tab ini, per sesi. */
const terakhirPerSesi = new Map();

/** Koneksi perekam ditutup dan dilupakan; buka() berikutnya membuka basis data namaDb lagi. */
function lepasKoneksi() {
    const janji = dbTerbuka;

    dbTerbuka = null;
    janji?.then((db) => db.close(), () => {});
}

/**
 * Mengikat perekam ke pengguna perangkat; dipanggil sebelum merekam. Melempar untuk basis data
 * yang sudah dihapus di perangkat ini, supaya perekam tidak dimulai dan basis data akun yang
 * sudah keluar tidak dibuat ulang; muat penuh mengikat lagi.
 */
export function aturPengguna(kunci) {
    const nama = `${AWALAN_DB}${kunci}`;

    if (namaTerhapus.has(nama)) {
        throw new Error('Basis data jejak ini sudah dihapus di perangkat ini.');
    }

    if (nama !== namaDb) {
        lepasKoneksi();
        namaDb = nama;
    }
}

function bukaNama(nama) {
    return new Promise((selesai, gagal) => {
        const permintaan = indexedDB.open(nama, 1);

        permintaan.onupgradeneeded = () => {
            const db = permintaan.result;

            if (!db.objectStoreNames.contains(TOKO)) {
                // Kunci gabungan sesi dan waktu rekam. Waktu rekam dibulatkan ke detik
                // (ruling W6-P2c), sama dengan kunci server, jadi dua titik pada detik yang
                // sama dalam satu sesi adalah satu titik.
                db.createObjectStore(TOKO, { keyPath: ['sesi', 'recorded_at'] });
            }
        };

        permintaan.onsuccess = () => {
            const db = permintaan.result;

            // Penghapusan dari tab mana pun menutup koneksi ini lebih dulu. Perekam yang memegang
            // basis data ini dilepas dari penggunanya, jadi titik berikutnya ditolak, bukan
            // menulis ke basis data yang dibuat ulang, dan namanya tidak diikat lagi (m-7).
            db.onversionchange = (peristiwa) => {
                db.close();

                if (peristiwa?.newVersion === null) {
                    namaTerhapus.add(nama);
                }

                if (nama === namaDb) {
                    namaDb = null;
                    dbTerbuka = null;
                }
            };
            selesai(db);
        };
        permintaan.onerror = () => gagal(permintaan.error);
    });
}

function buka() {
    if (namaDb === null) {
        return Promise.reject(new Error('Pengguna perangkat belum diatur untuk perekam jejak.'));
    }

    if (dbTerbuka === null) {
        const janji = bukaNama(namaDb);

        // Pembukaan yang gagal tidak disimpan, supaya titik berikutnya mencoba lagi (pola simpan.ts).
        janji.catch(() => {
            if (dbTerbuka === janji) {
                dbTerbuka = null;
            }
        });
        dbTerbuka = janji;
    }

    return dbTerbuka;
}

function transaksi(mode) {
    return buka().then((db) => db.transaction(TOKO, mode).objectStore(TOKO));
}

/** Waktu dalam milidetik dibulatkan ke bawah ke detik (ruling W6-P2c). */
function keDetik(ms) {
    return Math.floor(ms / 1000) * 1000;
}

export function simpanTitik(sesi, posisi) {
    return transaksi('readwrite').then((toko) => {
        toko.put({
            sesi,
            recorded_at: new Date(keDetik(posisi.timestamp)).toISOString(),
            latitude: posisi.coords.latitude,
            longitude: posisi.coords.longitude,
            // Ketinggian GPS sering tidak tersedia di peranti tanpa barometer, dan
            // ketiadaannya disimpan sebagai null, bukan sebagai nol.
            elevation_m: posisi.coords.altitude === null ? null : Math.round(posisi.coords.altitude),
            accuracy_m: posisi.coords.accuracy === null ? null : Math.round(posisi.coords.accuracy),
        });
    });
}

/**
 * Mulai merekam. Mengembalikan fungsi penghenti.
 *
 * watchPosition, bukan getCurrentPosition berulang: peramban menjaga sendiri jarak antar
 * pembacaan dan dapat memakai pembacaan yang sudah ada, yang lebih hemat baterai
 * daripada memanggilnya sendiri dengan timer.
 *
 * Posisi yang datang kurang dari lima detik sesudah titik terakhir sesi ini di tab ini tidak
 * disimpan (ruling W6-P2d). Waktu yang mundur (jam perangkat dikoreksi) tidak menahan perekam:
 * titiknya disimpan dan menjadi acuan selang berikutnya.
 */
export function mulaiMerekam(sesi) {
    if (!navigator.geolocation) {
        return () => {};
    }

    const pengawas = navigator.geolocation.watchPosition(
        (posisi) => {
            const detik = keDetik(posisi.timestamp);
            const terakhir = terakhirPerSesi.get(sesi);

            if (terakhir !== undefined && detik >= terakhir && detik - terakhir < SELANG_TITIK_MS) {
                return;
            }

            terakhirPerSesi.set(sesi, detik);
            simpanTitik(sesi, posisi).catch(() => {
                // Kegagalan menulis satu titik tidak boleh menghentikan perekaman: jejak
                // yang kehilangan satu titik masih berguna, jejak yang berhenti tidak.
            });
        },
        () => {},
        { enableHighAccuracy: true, maximumAge: 5000, timeout: 30000 }
    );

    return () => navigator.geolocation.clearWatch(pengawas);
}

/**
 * Jumlah titik yang belum terkirim di perangkat ini untuk satu basis data, tanpa membuatnya bila
 * belum ada: pertanyaan sebelum keluar akun membaca ini (spec v3 bagian 10.6; ruling W6-11a).
 * Tanpa indexedDB.databases() keberadaannya tidak dapat diperiksa tanpa membuka, dan membuka
 * berarti membuat, jadi hasilnya null: tidak diketahui, bukan nol (PT11-8).
 */
export async function jumlahTitik(nama) {
    if (typeof indexedDB.databases !== 'function') {
        return null;
    }

    const ada = (await indexedDB.databases()).some((d) => d.name === nama);

    if (!ada) {
        return 0;
    }

    const db = await bukaNama(nama);

    try {
        if (!db.objectStoreNames.contains(TOKO)) {
            return 0;
        }

        return await new Promise((selesai, gagal) => {
            const permintaan = db.transaction(TOKO, 'readonly').objectStore(TOKO).count();
            permintaan.onsuccess = () => selesai(permintaan.result);
            permintaan.onerror = () => gagal(permintaan.error);
        });
    } finally {
        db.close();
    }
}

function minta(permintaan) {
    return new Promise((selesai, gagal) => {
        permintaan.onsuccess = () => selesai(permintaan.result);
        permintaan.onerror = () => gagal(permintaan.error);
    });
}

/**
 * Membuka basis data jejak yang SUDAH ada menurut namanya, tanpa versi, tanpa pernah membuatnya
 * (ruling W6-P2g). upgradeneeded pada pembukaan tanpa versi berarti basis data itu belum ada:
 * transaksi upgrade dibatalkan, dan peramban tidak menyimpan basis data baru yang upgrade-nya
 * dibatalkan. Hasilnya null. Penghapusan dari tab lain menutup koneksi ini.
 */
function bukaBilaAda(nama) {
    return new Promise((selesai, gagal) => {
        let belumAda = false;
        const permintaan = indexedDB.open(nama);

        permintaan.onupgradeneeded = () => {
            belumAda = true;
            permintaan.transaction?.abort();
        };
        permintaan.onsuccess = () => {
            const db = permintaan.result;

            db.onversionchange = () => db.close();
            selesai(db);
        };
        permintaan.onerror = (peristiwa) => {
            if (belumAda) {
                peristiwa?.preventDefault?.();
                selesai(null);

                return;
            }

            gagal(permintaan.error);
        };
    });
}

/** Menjalankan `kerja` atas basis data yang sudah ada, lalu menutupnya; `kosong` bila tidak ada. */
async function denganBasisData(nama, kerja, kosong) {
    const db = await bukaBilaAda(nama);

    if (db === null) {
        return kosong;
    }

    try {
        return db.objectStoreNames.contains(TOKO) ? await kerja(db) : kosong;
    } finally {
        db.close();
    }
}

/** Rentang kunci semua titik satu sesi di toko berkunci [sesi, recorded_at]: larik lebih besar dari string. */
function rentangSesi(sesi) {
    return IDBKeyRange.bound([sesi], [sesi, []]);
}

/**
 * Apakah basis data jejak bernama ini ada, tanpa membuatnya: indexedDB.databases() bila peramban
 * memilikinya, selain itu pembukaan yang membatalkan upgrade (bukaBilaAda).
 */
export async function adaBasisDataJejak(nama) {
    if (typeof indexedDB.databases === 'function') {
        try {
            return (await indexedDB.databases()).some((d) => d.name === nama);
        } catch {
            // Peramban yang menolak daftar basis data jatuh ke pembukaan tanpa membuat.
        }
    }

    const db = await bukaBilaAda(nama);

    db?.close();

    return db !== null;
}

/** Id sesi yang punya titik di basis data ini, naik; kosong bila basis datanya tidak ada. */
export function daftarSesi(nama) {
    return denganBasisData(
        nama,
        (db) => minta(db.transaction(TOKO, 'readonly').objectStore(TOKO).getAllKeys()).then((kunci) => [...new Set(kunci.map((k) => k[0]))].sort((a, b) => a - b)),
        [],
    );
}

/**
 * Titik satu sesi, urut waktu rekam, paling banyak `batas` titik terlama bila diberikan. Kunci
 * [sesi, recorded_at] sudah urut waktu karena recorded_at ditulis toISOString(); urutan ditegaskan
 * lagi di sini, dengan kunci sebagai pemecah seri.
 */
export function bacaTitikSesi(nama, sesi, batas) {
    return denganBasisData(
        nama,
        (db) =>
            minta(db.transaction(TOKO, 'readonly').objectStore(TOKO).getAll(rentangSesi(sesi), batas)).then((titik) =>
                [...titik].sort((a, b) => Date.parse(a.recorded_at) - Date.parse(b.recorded_at) || (a.recorded_at < b.recorded_at ? -1 : a.recorded_at > b.recorded_at ? 1 : 0)),
            ),
        [],
    );
}

/** Jumlah titik satu sesi di basis data ini; 0 bila basis datanya tidak ada. */
export function jumlahTitikSesi(nama, sesi) {
    return denganBasisData(nama, (db) => minta(db.transaction(TOKO, 'readonly').objectStore(TOKO).count(rentangSesi(sesi))), 0);
}

/** Satu transaksi hapus yang selesai pada oncomplete, bukan saat permintaannya diterima (pola simpan.ts PT9-20). */
function hapusDalamTransaksi(nama, sasaran) {
    return denganBasisData(
        nama,
        (db) =>
            new Promise((selesai, gagal) => {
                const transaksiHapus = db.transaction(TOKO, 'readwrite');
                const toko = transaksiHapus.objectStore(TOKO);

                transaksiHapus.oncomplete = () => selesai();
                transaksiHapus.onerror = () => gagal(transaksiHapus.error ?? new Error('Hapus titik jejak gagal.'));
                transaksiHapus.onabort = () => gagal(transaksiHapus.error ?? new Error('Hapus titik jejak dibatalkan.'));
                sasaran.forEach((s) => toko.delete(s));
            }),
        undefined,
    );
}

/** Menghapus titik menurut kuncinya [sesi, recorded_at]; selesai sesudah transaksinya tersimpan. */
export function hapusTitikKunci(nama, kunci) {
    return kunci.length === 0 ? Promise.resolve() : hapusDalamTransaksi(nama, kunci);
}

/** Menghapus semua titik satu sesi (hapus jejak di ringkasan); selesai sesudah transaksinya tersimpan. */
export function hapusTitikSesi(nama, sesi) {
    return hapusDalamTransaksi(nama, [rentangSesi(sesi)]);
}

/**
 * Menghapus basis data jejak satu nama. Bila itu basis data perekam tab ini, koneksinya ditutup
 * lebih dulu dan perekam dilepas dari penggunanya. Tab lain yang masih memegangnya mendapat
 * versionchange dan menutupnya sendiri; bila penghapusan tetap terhalang lebih dari tiga detik,
 * hasilnya 'tertunda' (peramban menghapusnya begitu koneksi terakhir tertutup), bukan sukses palsu
 * (PT11-4).
 */
export async function hapusBasisData(nama) {
    if (nama === namaDb) {
        const janji = dbTerbuka;

        namaDb = null;
        dbTerbuka = null;

        try {
            (await janji)?.close();
        } catch {
            // Koneksi yang tidak pernah terbuka tidak perlu ditutup.
        }
    }

    return new Promise((selesai, gagal) => {
        let pewaktu = null;
        const permintaan = indexedDB.deleteDatabase(nama);

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
