/*
 * Perekam jejak pendakian.
 *
 * Titik direkam ke IndexedDB selama pendakian dan tidak dikirim satu pun sebelum pendaki
 * turun. Di hampir seluruh gunung Indonesia tidak ada sinyal, dan mencoba mengirim tiap
 * titik saat berjalan hanya menghabiskan baterai untuk permintaan yang gagal.
 *
 * IndexedDB, bukan localStorage: perekaman tiap lima detik selama dua belas jam
 * menghasilkan sekitar 8.600 titik, dan localStorage sinkron serta berbatas sekitar 5 MB.
 * Menulisnya di thread utama tiap lima detik akan terasa pada peranti kelas bawah yang
 * justru paling banyak dipakai.
 */

const NAMA_DB = 'jejak-pendakian';
const TOKO = 'titik';

let dbTerbuka = null;

function buka() {
    dbTerbuka ??= new Promise((selesai, gagal) => {
        const permintaan = indexedDB.open(NAMA_DB, 1);

        permintaan.onupgradeneeded = () => {
            const db = permintaan.result;

            if (!db.objectStoreNames.contains(TOKO)) {
                // Kunci gabungan sesi dan waktu rekam. Dua titik pada detik yang sama
                // dalam satu sesi adalah titik yang sama, dan menyimpan keduanya hanya
                // menggandakan jejak yang nanti ditolak server.
                db.createObjectStore(TOKO, { keyPath: ['sesi', 'recorded_at'] });
            }
        };

        permintaan.onsuccess = () => selesai(permintaan.result);
        permintaan.onerror = () => gagal(permintaan.error);
    });

    return dbTerbuka;
}

function transaksi(mode) {
    return buka().then((db) => db.transaction(TOKO, mode).objectStore(TOKO));
}

export function simpanTitik(sesi, posisi) {
    return transaksi('readwrite').then((toko) => {
        toko.put({
            sesi,
            recorded_at: new Date(posisi.timestamp).toISOString(),
            latitude: posisi.coords.latitude,
            longitude: posisi.coords.longitude,
            // Ketinggian GPS sering tidak tersedia di peranti tanpa barometer, dan
            // ketiadaannya disimpan sebagai null, bukan sebagai nol.
            elevation_m: posisi.coords.altitude === null ? null : Math.round(posisi.coords.altitude),
            accuracy_m: posisi.coords.accuracy === null ? null : Math.round(posisi.coords.accuracy),
        });
    });
}

export function bacaTitik(sesi) {
    return transaksi('readonly').then((toko) => new Promise((selesai, gagal) => {
        const permintaan = toko.getAll();

        permintaan.onsuccess = () => selesai(permintaan.result.filter((t) => t.sesi === sesi));
        permintaan.onerror = () => gagal(permintaan.error);
    }));
}

export function hapusTitik(sesi) {
    return bacaTitik(sesi).then((titik) => transaksi('readwrite').then((toko) => {
        titik.forEach((t) => toko.delete([t.sesi, t.recorded_at]));
    }));
}

/**
 * Mulai merekam. Mengembalikan fungsi penghenti.
 *
 * watchPosition, bukan getCurrentPosition berulang: peramban menjaga sendiri jarak antar
 * pembacaan dan dapat memakai pembacaan yang sudah ada, yang lebih hemat baterai
 * daripada memanggilnya sendiri dengan timer.
 */
export function mulaiMerekam(sesi) {
    if (!navigator.geolocation) {
        return () => {};
    }

    const pengawas = navigator.geolocation.watchPosition(
        (posisi) => simpanTitik(sesi, posisi).catch(() => {
            // Kegagalan menulis satu titik tidak boleh menghentikan perekaman: jejak
            // yang kehilangan satu titik masih berguna, jejak yang berhenti tidak.
        }),
        () => {},
        { enableHighAccuracy: true, maximumAge: 5000, timeout: 30000 }
    );

    return () => navigator.geolocation.clearWatch(pengawas);
}

/**
 * Kirim jejak yang tersimpan ke server, lalu bersihkan yang sudah terkirim.
 *
 * Dipotong per kelompok karena unggahan besar di sinyal tipis lebih sering gagal
 * seluruhnya daripada sebagian. Titik baru dihapus dari perangkat setelah server
 * menyatakan menerimanya, jadi kegagalan di tengah jalan tidak menghilangkan jejak.
 */
export async function kirimJejak(sesi, url, token, ukuranKelompok = 500) {
    const titik = await bacaTitik(sesi);

    if (titik.length === 0) {
        return 0;
    }

    let terkirim = 0;

    for (let i = 0; i < titik.length; i += ukuranKelompok) {
        const kelompok = titik.slice(i, i + ukuranKelompok);

        const respons = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                Accept: 'application/json',
            },
            body: JSON.stringify({
                points: kelompok.map(({ sesi: _, ...sisanya }) => sisanya),
            }),
        });

        if (!respons.ok) {
            // Berhenti di sini, bukan melanjutkan. Sisa titiknya tetap di perangkat dan
            // dicoba lagi nanti.
            break;
        }

        terkirim += kelompok.length;

        const toko = await transaksi('readwrite');
        kelompok.forEach((t) => toko.delete([t.sesi, t.recorded_at]));
    }

    return terkirim;
}
