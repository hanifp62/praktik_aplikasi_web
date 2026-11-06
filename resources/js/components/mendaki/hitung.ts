/**
 * Hitungan mode lapangan di perangkat (spec v3 bagian 6.12): pos berikutnya, sisa jarak, naik
 * lagi, tiba di pos, dan teks angka. Dihitung di sini, bukan di server, karena mode lapangan
 * harus tetap berjalan tanpa sinyal dan posisi pendaki tidak dikirim ke mana pun (PRD §82,
 * §145.1). Satu sumber untuk halaman daring dan paket luring.
 *
 * Modul ini sengaja tanpa impor selain ekspor ulang lib/waktuBergerak.ts lewat jalur relatif
 * berekstensi (ruling W6-FJ), yang juga tanpa impor: FieldModeFiguresTest menjalankannya langsung
 * di Node dari PHPUnit, jadi aturan pembulatan dan tiba di pos diuji sebagai perilaku, bukan sebagai
 * teks sumber. Ketinggian GPS tidak dipakai di mana pun: naik lagi dibaca dari profil elevasi
 * jalur, karena ketinggian GPS ponsel berderau puluhan meter.
 */

/** Satu titik [bujur, lintang], urutan GeoJSON. */
export type Titik = [number, number];

/** Satu titik profil elevasi [km dari awal profil, meter]. */
export type TitikProfil = [number, number];

export type KeputusanTiba = 'tiba' | 'tanya' | 'belum';

const RADIUS_BUMI_M = 6_371_000;

/**
 * Posisi yang menyimpang lebih jauh dari ini dari garis jalur tidak diukur menyusuri jalur:
 * garisnya mungkin kurang teliti atau pendaki di luar garis, dan jarak menyusuri jalur dari
 * titik yang salah lebih menyesatkan daripada jarak garis lurus yang disebut apa adanya.
 */
export const BATAS_MENYIMPANG_M = 150;

const angkaBulat = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });
const angkaSatuDesimal = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
const angkaKoordinat = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 5, maximumFractionDigits: 5 });

const radian = (derajat: number) => (derajat * Math.PI) / 180;

/** Jarak permukaan bumi antara dua titik, haversine, dalam meter. */
export function jarakMeter(a: Titik, b: Titik): number {
    const dLat = radian(b[1] - a[1]);
    const dLng = radian(b[0] - a[0]);
    const h = Math.sin(dLat / 2) ** 2 + Math.cos(radian(a[1])) * Math.cos(radian(b[1])) * Math.sin(dLng / 2) ** 2;

    return RADIUS_BUMI_M * 2 * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
}

/** Panjang garis menyusuri titik-titiknya, dalam meter. */
export function panjangGaris(garis: Titik[]): number {
    let panjang = 0;

    for (let i = 1; i < garis.length; i++) {
        panjang += jarakMeter(garis[i - 1] as Titik, garis[i] as Titik);
    }

    return panjang;
}

/**
 * Garis dibelah pada jarak sepanjangM dari awalnya: bagian yang sudah dilalui dan yang tersisa,
 * untuk peta mode lapangan. Titik belahnya ikut di kedua bagian supaya garis tidak terputus.
 */
export function potongGaris(garis: Titik[], sepanjangM: number): { dilalui: Titik[]; sisa: Titik[] } {
    if (garis.length < 2 || sepanjangM <= 0) {
        return { dilalui: [], sisa: garis };
    }

    let jalan = 0;

    for (let i = 1; i < garis.length; i++) {
        const a = garis[i - 1] as Titik;
        const b = garis[i] as Titik;
        const ruasM = jarakMeter(a, b);

        if (jalan + ruasM >= sepanjangM) {
            const t = ruasM === 0 ? 0 : (sepanjangM - jalan) / ruasM;
            const belah: Titik = [a[0] + t * (b[0] - a[0]), a[1] + t * (b[1] - a[1])];

            return { dilalui: [...garis.slice(0, i), belah], sisa: [belah, ...garis.slice(i)] };
        }

        jalan += ruasM;
    }

    return { dilalui: garis, sisa: [] };
}

/**
 * Letak sebuah posisi pada garis jalur: jarak dari awal garis sampai titik terdekat di garis
 * (sepanjangM) dan jarak posisi dari garis itu (menyimpangM). Tiap ruas garis diproyeksikan di
 * bidang datar lokal; untuk ruas sepanjang jalur pendakian galatnya jauh di bawah akurasi GPS.
 */
export function proyeksikan(garis: Titik[], posisi: Titik): { sepanjangM: number; menyimpangM: number } | null {
    if (garis.length < 2) {
        return null;
    }

    let terbaik: { sepanjangM: number; menyimpangM: number } | null = null;
    let sebelumnya = 0;

    for (let i = 1; i < garis.length; i++) {
        const a = garis[i - 1] as Titik;
        const b = garis[i] as Titik;
        const skalaX = Math.cos(radian((a[1] + b[1]) / 2)) * radian(1) * RADIUS_BUMI_M;
        const skalaY = radian(1) * RADIUS_BUMI_M;
        const bx = (b[0] - a[0]) * skalaX;
        const by = (b[1] - a[1]) * skalaY;
        const px = (posisi[0] - a[0]) * skalaX;
        const py = (posisi[1] - a[1]) * skalaY;
        const kuadrat = bx * bx + by * by;
        const t = kuadrat === 0 ? 0 : Math.min(1, Math.max(0, (px * bx + py * by) / kuadrat));
        const menyimpangM = Math.hypot(px - t * bx, py - t * by);
        const ruasM = jarakMeter(a, b);

        if (terbaik === null || menyimpangM < terbaik.menyimpangM) {
            terbaik = { sepanjangM: sebelumnya + t * ruasM, menyimpangM };
        }

        sebelumnya += ruasM;
    }

    return terbaik;
}

/**
 * Jarak dibulatkan supaya tidak terbaca lebih pasti dari GPS-nya (R-005): kelipatan 50 m di
 * bawah 1 km, 0,1 km mulai 1 km (spec v3 bagian 6.12). Di bawah 50 m tidak ada angka.
 */
export function teksJarak(meter: number): string {
    if (meter < 50) {
        return '< 50 m';
    }

    const dibulatkan = Math.round(meter / 50) * 50;

    if (dibulatkan < 1000) {
        return `${angkaBulat.format(dibulatkan)} m`;
    }

    return `${angkaSatuDesimal.format(Math.round(meter / 100) / 10)} km`;
}

/**
 * Naik dalam meter dengan tanda panah, angka Indonesia ("↑1.650 m"). Spasi tak terputus
 * ( , M-5/ruling W6-3g) antara angka dan satuan: petak sempit (sekitar 320 px) memotong
 * spasi biasa dan menulis "↑235" di satu baris dan "m" sendirian di baris berikutnya.
 */
export function teksNaik(meter: number): string {
    return `↑${angkaBulat.format(Math.round(meter))} m`;
}

/** Bahan keterangan petak "Naik lagi" yang angkanya tidak ada; semua dari cabang useModeLapangan. */
export interface SebabNaikKosong {
    /** Profil elevasi berisi paling sedikit dua titik (naikDiantara memberi null di bawah itu). */
    profil: boolean;
    /** Letak pos tujuan dan titik awal di garis jalur diketahui (garis ada, koordinat pos ada dan dekat garis). */
    letakDiGaris: boolean;
    /** Urutan pos terakhir yang dicapai; 0 bila belum ada. */
    tercapai: number;
    /** Ketinggian pos tujuan dan pos terakhir yang dicapai sama-sama tercatat. */
    ketinggianPos: boolean;
}

/**
 * Keterangan petak "Naik lagi" bila angkanya tidak ada (ruling AF-9, audit E2E M-7). Angkanya dicari
 * dari dua sumber berurutan (useModeLapangan): profil elevasi sepanjang garis jalur, lalu selisih
 * ketinggian pos terakhir yang dicapai dan pos tujuan. Angka kosong berarti KEDUANYA gagal, jadi
 * keterangannya menyebut sebab nyata keduanya, masing-masing dengan frasanya sendiri:
 * - sumber profil: "profil elevasi belum ada", atau "garis jalur atau koordinat pos belum dapat
 *   dipakai" (garis tidak ada, pos tanpa koordinat, atau pos jauh dari garis);
 * - sumber pos: "belum ada pos yang dicapai", atau "ketinggian pos belum tercatat".
 * Keadaan lain tidak menghasilkan angka kosong; bila tetap terjadi, keterangannya umum dan benar.
 */
export function keteranganNaikKosong(sebab: SebabNaikKosong): string {
    const dariProfil = !sebab.profil ? 'profil elevasi belum ada' : !sebab.letakDiGaris ? 'garis jalur atau koordinat pos belum dapat dipakai' : null;
    const dariPos = sebab.tercapai === 0 ? 'belum ada pos yang dicapai' : !sebab.ketinggianPos ? 'ketinggian pos belum tercatat' : null;
    const bagian = [dariProfil, dariPos].filter((b): b is string => b !== null);

    return bagian.length > 0 ? bagian.join(' dan ') : 'belum dapat dihitung';
}

/** Akurasi posisi ("±15 m"). */
export function teksAkurasi(meter: number): string {
    return `±${angkaBulat.format(Math.round(meter))} m`;
}

/** Umur posisi terakhir ("30 detik lalu"). */
export function teksUmur(detik: number): string {
    const utuh = Math.max(0, Math.floor(detik));

    if (utuh < 10) {
        return 'baru saja';
    }

    if (utuh < 60) {
        return `${utuh} detik lalu`;
    }

    if (utuh < 3600) {
        return `${Math.floor(utuh / 60)} menit lalu`;
    }

    return `${Math.floor(utuh / 3600)} jam lalu`;
}

/**
 * teksLama dan detikBergerak tinggal di lib/waktuBergerak.ts (ruling W6-FJ), supaya strip pendakian tidak menarik
 * modul ini ke potongan tata letak. Diekspor ulang di sini, jadi pemanggil dan test Node lama tidak berubah.
 * Ekstensi .ts ditulis karena Node menjalankan modul ini langsung (allowImportingTsExtensions di tsconfig.json).
 */
export { detikBergerak, teksLama } from '../../lib/waktuBergerak.ts';

/**
 * Tinggi pada satu jarak tertentu, interpolasi linear antar titik profil terdekat. Prasyarat:
 * `profil` sudah terurut naik menurut km; pemanggil bertanggung jawab mengurutkan (lihat
 * `naikDiantara`), fungsi ini tidak mengurutkan ulang. Km di luar rentang profil memipihkan ke
 * tinggi ujung terdekat.
 */
function tinggiPada(profil: TitikProfil[], km: number): number {
    for (let i = 0; i < profil.length; i++) {
        const [x, y] = profil[i] as TitikProfil;

        if (x >= km) {
            if (i === 0) {
                return y;
            }

            const [x0, y0] = profil[i - 1] as TitikProfil;

            return x === x0 ? y : y0 + ((y - y0) * (km - x0)) / (x - x0);
        }
    }

    return (profil[profil.length - 1] as TitikProfil)[1];
}

/**
 * Naik yang tersisa dari satu jarak ke jarak lain pada profil elevasi: jumlah kenaikan saja,
 * turunan tidak mengurangi. Jarak di luar rentang profil dijepit ke ujungnya. Null bila profil
 * tidak dapat dibaca (R-007): tanpa profil, naik tidak ditebak.
 *
 * `profil` tidak harus terurut naik menurut km dari pemanggil: rekaman GPX atau profil dari
 * basis data tidak menjamin urutannya. Salinan yang diurutkan dibuat di sini sebelum dipakai;
 * larik `profil` milik pemanggil tidak diubah (tanpa mutasi in-place).
 */
export function naikDiantara(profil: TitikProfil[], dariKm: number, sampaiKm: number): number | null {
    if (profil.length < 2) {
        return null;
    }

    const diurutkan = [...profil].sort((a, b) => a[0] - b[0]);
    const awal = (diurutkan[0] as TitikProfil)[0];
    const akhir = (diurutkan[diurutkan.length - 1] as TitikProfil)[0];
    const jepit = (km: number) => Math.min(akhir, Math.max(awal, km));
    const dari = jepit(dariKm);
    const sampai = jepit(sampaiKm);
    const maju = sampai >= dari;
    const tengah = diurutkan.filter(([km]) => (maju ? km > dari && km < sampai : km < dari && km > sampai)).map(([, m]) => m);
    const tinggi = [tinggiPada(diurutkan, dari), ...(maju ? tengah : tengah.reverse()), tinggiPada(diurutkan, sampai)];
    let naik = 0;

    for (let i = 1; i < tinggi.length; i++) {
        naik += Math.max(0, (tinggi[i] as number) - (tinggi[i - 1] as number));
    }

    return Math.round(naik);
}

/**
 * Jarak menyusuri garis jalur dipetakan ke sumbu profil elevasi menurut perbandingan panjangnya:
 * garis rute dan profil dapat berasal dari rekaman yang panjangnya berbeda (T6-M9).
 *
 * `profil` tidak harus terurut naik menurut km dari pemanggil (lihat `naikDiantara`); salinan
 * yang diurutkan dibuat di sini sebelum dipakai, larik `profil` milik pemanggil tidak diubah.
 */
export function kmPadaProfil(sepanjangM: number, panjangGarisM: number, profil: TitikProfil[]): number | null {
    if (panjangGarisM <= 0 || profil.length < 2) {
        return null;
    }

    const diurutkan = [...profil].sort((a, b) => a[0] - b[0]);
    const awal = (diurutkan[0] as TitikProfil)[0];
    const akhir = (diurutkan[diurutkan.length - 1] as TitikProfil)[0];

    return awal + (Math.min(sepanjangM, panjangGarisM) / panjangGarisM) * (akhir - awal);
}

/**
 * Ketinggian pada satu jarak di profil elevasi, untuk blok lokasi kartu darurat: dari profil,
 * bukan dari ketinggian GPS yang berderau puluhan meter. Null tanpa profil atau tanpa jarak.
 *
 * `profil` tidak harus terurut naik menurut km dari pemanggil (kontrak W6-2a, pra-tinjau Tugas
 * 7 PT7-11): salinan yang diurutkan dibuat di sini, larik milik pemanggil tidak diubah.
 */
export function elevasiPada(profil: TitikProfil[] | null, km: number | null): number | null {
    if (profil === null || profil.length < 2 || km === null) {
        return null;
    }

    return Math.round(tinggiPada([...profil].sort((a, b) => a[0] - b[0]), km));
}

/**
 * Ketinggian di letak posisi pada garis jalur, untuk blok lokasi kartu darurat (spec v3 bagian
 * 10.2; pra-tinjau Tugas 7 PT7-1): dari profil elevasi, bukan ketinggian GPS. Posisinya
 * diproyeksikan sendiri ke garis, bukan dibaca dari dilaluiM milik peta, yang jatuh ke letak
 * pos terakhir yang dicapai saat pendaki menyimpang atau sudah melewati pos tujuan. Null bila
 * garis atau profil tidak ada, bila akurasi lebih kasar dari AKURASI_LEMAH_M, atau bila posisi
 * lebih jauh dari BATAS_MENYIMPANG_M dari garis: ketinggian titik jalur terdekat bukan
 * ketinggian pendaki yang di luar jalur (R-007).
 */
export function ketinggianDiPosisi(garis: Titik[] | null, profil: TitikProfil[] | null, posisi: { lng: number; lat: number; akurasi: number } | null): number | null {
    if (garis === null || profil === null || posisi === null || posisi.akurasi > AKURASI_LEMAH_M) {
        return null;
    }

    const letak = proyeksikan(garis, [posisi.lng, posisi.lat]);

    if (letak === null || letak.menyimpangM > BATAS_MENYIMPANG_M) {
        return null;
    }

    return elevasiPada(profil, kmPadaProfil(letak.sepanjangM, panjangGaris(garis), profil));
}

/**
 * Koordinat untuk dibacakan kepada petugas (spec v3 bagian 10.2): derajat desimal lima angka,
 * sekitar satu meter, lintang lebih dulu, dengan penanda arah Indonesia. Derajat dan penanda
 * arah dipisah spasi tak terputus (PT7-13), supaya lembar sempit tidak memotong "6,78412°" dari
 * "LS".
 */
export function teksKoordinat(lat: number, lng: number): string {
    return `${angkaKoordinat.format(Math.abs(lat))}° ${lat < 0 ? 'LS' : 'LU'}, ${angkaKoordinat.format(Math.abs(lng))}° ${lng < 0 ? 'BB' : 'BT'}`;
}

/** Ketinggian kartu darurat ("2.395 m"), angka dan satuan dengan spasi tak terputus (PT7-13). */
export function teksKetinggian(meter: number): string {
    return `${angkaBulat.format(Math.round(meter))} m`;
}

/**
 * Jarak kartu darurat yang dibacakan ("sekitar 300 m"): bentuk teksJarak dengan kata "sekitar",
 * dan di bawah 50 m "kurang dari 50 m", bukan "sekitar < 50 m".
 */
export function teksSekitar(meter: number): string {
    return meter < 50 ? 'kurang dari 50 m' : `sekitar ${teksJarak(meter)}`;
}

/**
 * Kalimat kartu darurat saat perangkat belum pernah memberi posisi, per keadaan lokasi
 * (usePosisiLapangan; pra-tinjau Tugas 7 PT7-3a): "belum" hanya untuk keadaan yang memang masih
 * menunggu. Izin yang ditolak tidak ditulis "belum didapat", karena posisi itu tidak akan datang.
 * Hanya untuk cabang tanpa posisi; bila posisi terakhir masih tampil, kartu memakai
 * kalimatPosisiLama (ruling W6-7h). Lokasi mati juga untuk pendakian yang sudah berakhir di
 * perangkat ini (shell dari rekaman berakhir, ruling AF-6), jadi kalimatnya menyebut keadaan itu.
 */
export function kalimatPosisiDarurat(keadaan: string): string {
    switch (keadaan) {
        case 'ditolak':
            return 'Izin lokasi ditolak, jadi koordinat tidak dapat dibaca. Izinkan lokasi untuk peramban ini di pengaturan perangkat.';
        case 'tidak-didukung':
            return 'Peramban ini tidak dapat membaca lokasi.';
        case 'mati':
            return 'Lokasi tidak dibaca selama pendakian dijeda, belum dimulai, atau sudah berakhir.';
        case 'galat':
            return 'Posisi belum terbaca; perangkat mencoba lagi.';
        default:
            return 'Posisi belum didapat dari perangkat.';
    }
}

/**
 * Keterangan di bawah posisi terakhir yang masih tampil di kartu darurat saat perangkat tidak
 * memberi posisi baru (ruling W6-7h, I-2 tinjauan Tugas 7). usePosisiLapangan tidak pernah
 * mengosongkan posisi terakhir, jadi saat jeda, izin dicabut, galat, atau menunggu sesudah
 * Lanjut, koordinat di atasnya tetap posisi terakhir yang terukur dan masih berguna bagi
 * petugas. Kalimatnya tentang posisi BARU, bukan "koordinat tidak dapat dibaca" seperti
 * kalimatPosisiDarurat untuk cabang tanpa posisi. Null saat aktif.
 */
export function kalimatPosisiLama(keadaan: string): string | null {
    switch (keadaan) {
        case 'aktif':
            return null;
        case 'mati':
            return 'Lokasi tidak diperbarui selama pendakian dijeda. Koordinat di atas adalah posisi terakhir yang terukur.';
        case 'ditolak':
            // Ruling W6-7h2: usePosisiLapangan memasang pengawas baru hanya sesudah halaman sempat
            // tersembunyi lalu terlihat, jadi izin yang diberikan lagi tidak selalu memulihkan posisi
            // dengan sendirinya. Kalimatnya tidak menjanjikan pemulihan; langkahnya sama dengan
            // kalimatPosisiDarurat, dan membuka pengaturan perangkat memang menyembunyikan halaman.
            return 'Izin lokasi dicabut, jadi posisi tidak diperbarui. Koordinat di atas adalah posisi terakhir yang terukur. Izinkan lokasi untuk peramban ini di pengaturan perangkat.';
        case 'galat':
            return 'Posisi baru belum terbaca; perangkat mencoba lagi. Koordinat di atas adalah posisi terakhir yang terukur.';
        default:
            return 'Menunggu posisi baru dari perangkat. Koordinat di atas adalah posisi terakhir yang terukur.';
    }
}

/**
 * Pos terdekat garis lurus untuk kartu darurat, hanya bila akurasi cukup tajam (AKURASI_LEMAH_M,
 * ruling W6-3e; pra-tinjau Tugas 7 PT7-3c dan d): dengan akurasi ratusan meter, "pos terdekat,
 * sekitar 300 m" adalah ketelitian palsu (R-005). Pos tanpa koordinat dilewati, jarak yang sama
 * dipecah urutan, dan urutan ikut dikembalikan karena nama pos tidak unik.
 */
export function posTerdekatDarurat(
    pos: Array<{ urutan: number; nama: string; lng: number | null; lat: number | null }>,
    posisi: { lng: number; lat: number; akurasi: number },
): { urutan: number; nama: string; meter: number } | null {
    if (posisi.akurasi > AKURASI_LEMAH_M) {
        return null;
    }

    const calon = pos
        .filter((p) => p.lng !== null && p.lat !== null)
        .map((p) => ({ urutan: p.urutan, nama: p.nama, meter: jarakMeter([posisi.lng, posisi.lat], [p.lng as number, p.lat as number]) }))
        .sort((a, b) => a.meter - b.meter || a.urutan - b.urutan);

    return calon[0] ?? null;
}

/**
 * Tiba di pos (spec v3 bagian 6.12): otomatis bila jarak ditambah akurasi masih di dalam radius;
 * bila hanya jarak dikurangi akurasi yang masuk radius, posisinya mungkin sudah di pos dan
 * pendaki ditanya; selain itu belum.
 */
export function keputusanTiba(jarakM: number, akurasiM: number, radiusM: number): KeputusanTiba {
    if (jarakM + akurasiM <= radiusM) {
        return 'tiba';
    }

    return jarakM - akurasiM <= radiusM ? 'tanya' : 'belum';
}

/**
 * Akurasi di atas ini terlalu kasar untuk menanyakan "Sudah tiba?" otomatis (I-5/ruling
 * W6-3e): jarak ke pos tujuan bisa salah ratusan meter walau keputusanTiba sudah menjawab
 * 'tanya', dan kalimat "posisi Anda dekat" menjadi ketelitian palsu (R-005) walau pendaki
 * ratusan meter jauhnya. Nilai yang sama juga menjadi ambang peringatan "Akurasi rendah".
 */
export const AKURASI_LEMAH_M = 50;

/**
 * Kartu "Sudah tiba?" (I-5/ruling W6-3e) hanya pantas ditampilkan ketika akurasi cukup tajam
 * untuk dipercaya. Di atas AKURASI_LEMAH_M, pendaki memakai tombol manual, tanpa klaim jarak;
 * jawaban "Ya" tetap tindakan eksplisit, tanpa urungkan otomatis.
 */
export function bolehTanyaTiba(keputusan: KeputusanTiba, akurasiM: number): boolean {
    return keputusan === 'tanya' && akurasiM <= AKURASI_LEMAH_M;
}

/** Pos berikutnya: pos pertama menurut urutan yang belum tercapai; null bila semua tercapai. */
export function posBerikutnya<T extends { urutan: number }>(pos: T[], tercapai: number): T | null {
    return [...pos].sort((a, b) => a.urutan - b.urutan).find((p) => p.urutan > tercapai) ?? null;
}

/**
 * Pos tertinggi yang sudah terlampaui di garis tetapi belum tercatat (I-6/ruling W6-3f):
 * letak pendaki di garis melewati letak pos itu lebih dari BATAS_MENYIMPANG_M, dan akurasi
 * cukup tajam (akurasiM <= AKURASI_LEMAH_M) untuk mempercayai bacaan itu. Memulihkan niat lama
 * "pos sebelum yang terdekat dianggap terlewati" sebagai PERTANYAAN, bukan otomatis: pendaki
 * yang membuka mode lapangan di tengah jalur (naik ojek ke Pos 3, misalnya) atau melewatkan
 * tiba otomatis di satu pos ditanya, bukan dipaksa mengetuk "Saya sudah di …" berkali-kali
 * atau tersandera jarak garis lurus ke pos yang sudah jauh di belakangnya.
 *
 * Checkpoint berurutan diasumsikan monoton sepanjang garis: bila pos N terlampaui lebih dari
 * BATAS_MENYIMPANG_M, setiap pos sebelumnya (urutan lebih kecil) ikut terlampaui, termasuk pos
 * yang letaknya di garis tidak diketahui. Karena itu `sampai` adalah pos terlampaui tertinggi
 * yang letaknya diketahui, dan `dari` adalah pos pertama sesudah `tercapai` (Q-13/ruling
 * W6-3v), bukan pos terlampaui pertama yang letaknya diketahui: "Ya" mengirim `sampai`, jadi
 * setiap pos sesudah `tercapai` sampai `sampai` ikut tercatat dan wajib disebut kartunya. Pos
 * pertama sesudah `tercapai` sama dengan tercapai + 1 bila urutan pos tanpa celah;
 * CheckpointRequest hanya menjamin urutan unik 1 sampai 200, jadi tercapai + 1 harfiah dapat
 * menyebut pos yang tidak ada.
 *
 * @param  pos  Pos berurutan dengan letaknya di garis (null bila tidak diketahui atau jauh dari garis).
 * @return Rentang urutan [dari, sampai] yang terlampaui tetapi belum tercatat; null bila tidak
 *         ada, posisi tidak dekat garis, atau akurasi belum cukup tajam untuk dipercaya.
 */
export function posTerlampaui(
    pos: Array<{ urutan: number; sepanjangM: number | null }>,
    tercapai: number,
    letak: { sepanjangM: number; menyimpangM: number } | null,
    akurasiM: number,
): { dari: number; sampai: number } | null {
    if (letak === null || letak.menyimpangM > BATAS_MENYIMPANG_M || akurasiM > AKURASI_LEMAH_M) {
        return null;
    }

    const belumTercatat = pos.filter((p) => p.urutan > tercapai).sort((a, b) => a.urutan - b.urutan);
    const terlampaui = belumTercatat.filter((p) => p.sepanjangM !== null && letak.sepanjangM - p.sepanjangM > BATAS_MENYIMPANG_M);
    const pertama = belumTercatat[0];
    const terakhir = terlampaui[terlampaui.length - 1];

    if (pertama === undefined || terakhir === undefined) {
        return null;
    }

    return { dari: pertama.urutan, sampai: terakhir.urutan };
}

/**
 * Kalimat kartu lewat untuk rentang [dari, sampai] (Q-7 dan Q-13/ruling W6-3v): judul Alert
 * dan kalimat wilayah status, keduanya dari satu syarat. Bentuk tunggal bila dari === sampai
 * ("Pos 2", bukan "Pos 2 sampai 2"), bentuk rentang selain itu ("Pos 1 sampai 2"). Rentangnya
 * persis yang dicatat "Ya": ModeLapangan.tsx mengirim `sampai`, dan `dari` adalah pos pertama
 * sesudah tercapai (posTerlampaui), jadi tidak ada pos yang ikut tercatat tanpa disebut.
 */
export function kalimatKartuLewat(dari: number, sampai: number): { judul: string; status: string } {
    const rentang = dari === sampai ? `Pos ${dari}` : `Pos ${dari} sampai ${sampai}`;

    return {
        judul: `Tandai ${rentang} sudah dilewati?`,
        status: `${rentang} tampak sudah dilewati. Tandai di kartu di bawah bila benar.`,
    };
}

/** Kartu tiba yang pantas ditampilkan sekarang; null bila tidak ada. */
export type KartuTiba = { jenis: 'tanya'; urutan: number; nama: string } | { jenis: 'akurasi-kurang'; akurasiM: number } | null;

export interface KartuLapangan {
    tiba: KartuTiba;
    /** Rentang pos terlampaui yang pantas ditanyakan sekarang; null bila tidak ada atau ditunda. */
    lewat: { dari: number; sampai: number } | null;
}

/**
 * @param  tolakTiba  Pos yang baru dijawab "Belum" untuk kartu tiba, dan sampai kapan (ms epoch) ditunda.
 * @param  tolakLewatSampai  Rentang "lewat" tertinggi (`sampai`) yang baru dijawab "Belum"; null bila belum pernah.
 */
export interface KeadaanKartuLapangan {
    keputusan: KeputusanTiba;
    akurasiM: number | null;
    tujuan: { urutan: number; nama: string } | null;
    lewat: { dari: number; sampai: number } | null;
    tolakTiba: { urutan: number; sampai: number } | null;
    tolakLewatSampai: number | null;
    sekarang: number;
}

/**
 * Keputusan kartu lapangan dari masukan yang sudah dirakit (W6-3m): gerbang akurasi kartu tiba
 * (bolehTanyaTiba) dan penundaan "Belum" kedua kartu. Satu-satunya pemanggilnya keadaanKartu di
 * bawah (W6-3p), yang merakit masukan ini dari nilai mentah hook; komponen hanya merender
 * hasilnya. Fungsi ini dijaga dua test Node:
 * FieldModeFiguresTest::test_the_field_card_decision_gates_accuracy_and_remembers_declines
 * (langsung) dan FieldModeFiguresTest::test_the_card_state_has_one_case_per_gate (lewat
 * keadaanKartu).
 *
 * Kartu tiba: hanya ketika keputusanTiba sudah 'tanya' dan ada tujuan, lalu dibagi dua menurut
 * bolehTanyaTiba (akurasi cukup tajam untuk kartu tanya, atau cukup kasar untuk kalimat
 * akurasi-kurang), dan ditunda bila baru dijawab "Belum" untuk pos yang sama (JEDA_TANYA_MS di
 * useModeLapangan.ts, berbasis waktu seperti sejak awal).
 *
 * Kartu lewat: ditunda sampai rentang `sampai`-nya BERTAMBAH, bukan menurut waktu (N-6/ruling
 * W6-3n). Pada kondisi yang menetap (garis kurang tepat, atau pendaki memang menolak), kartu
 * tiba kembali tiap 2 menit dapat diterima karena posisi tetap berubah; kartu lewat untuk
 * rentang yang SAMA tidak semestinya terus kembali hanya karena jam berjalan.
 */
export function kartuLapangan(keadaan: KeadaanKartuLapangan): KartuLapangan {
    let tiba: KartuTiba = null;

    if (keadaan.tujuan !== null && keadaan.akurasiM !== null && keadaan.keputusan === 'tanya') {
        const tp = keadaan.tolakTiba;
        const ditunda = tp !== null && tp.urutan === keadaan.tujuan.urutan && tp.sampai > keadaan.sekarang;

        if (!ditunda) {
            tiba = bolehTanyaTiba(keadaan.keputusan, keadaan.akurasiM)
                ? { jenis: 'tanya', urutan: keadaan.tujuan.urutan, nama: keadaan.tujuan.nama }
                : { jenis: 'akurasi-kurang', akurasiM: keadaan.akurasiM };
        }
    }

    const lewatDitunda = keadaan.lewat !== null && keadaan.tolakLewatSampai !== null && keadaan.lewat.sampai <= keadaan.tolakLewatSampai;

    return { tiba, lewat: lewatDitunda ? null : keadaan.lewat };
}

/**
 * Masukan keadaanKartu (Q-1/ruling W6-3p): nilai mentah dari hook, tanpa jarak, keputusan, atau
 * rentang yang sudah dihitung.
 */
export interface MasukanKartu {
    /** Posisi perangkat terakhir; null bila belum ada. */
    posisi: { lng: number; lat: number; akurasi: number } | null;
    /** Pos pertama yang belum tercapai; koordinat null bila belum tercatat. */
    tujuan: { urutan: number; nama: string; lng: number | null; lat: number | null } | null;
    dijeda: boolean;
    berlangsung: boolean;
    radiusTiba: number;
    /** Setiap pos jalur dengan letaknya di garis; null bila tidak diketahui atau jauh dari garis. */
    pos: Array<{ urutan: number; sepanjangM: number | null }>;
    tercapai: number;
    /** Letak posisi di garis jalur; null tanpa garis atau tanpa posisi. */
    letak: { sepanjangM: number; menyimpangM: number } | null;
    tolakTiba: { urutan: number; sampai: number } | null;
    tolakLewatSampai: number | null;
    sekarang: number;
}

/**
 * Keputusan tiba dan kartu lapangan dari nilai mentah, satu fungsi murni (Q-1/ruling W6-3p).
 * Hook memanggilnya sekali; jarak ke tujuan, keputusanTiba, posTerlampaui, dan argumen
 * kartuLapangan dirakit di sini, bukan di hook. Gerbangnya:
 * - dijeda dan tidak berlangsung: keputusan 'belum' dan tanpa kartu lewat;
 * - akurasi tiba: kartu tanya hanya pada akurasi <= AKURASI_LEMAH_M, di atasnya kalimat
 *   akurasi-kurang (bolehTanyaTiba lewat kartuLapangan);
 * - ada atau tiada pos lewat, dan akurasi lewat: posTerlampaui dengan akurasi posisi; tanpa
 *   posisi akurasinya Infinity, jadi tanpa kartu lewat;
 * - tolak tiba dan tolak lewat: penundaan "Belum" di kartuLapangan.
 *
 * `keputusan` juga dipakai efek tiba otomatis di useModeLapangan.ts. Yang menjaga fungsi ini:
 * FieldModeFiguresTest::test_the_card_state_has_one_case_per_gate (satu kasus Node per gerbang;
 * gerbang mana pun yang dibuang di sini membuat kasusnya merah) dan
 * HikeModeCheckpointTest::test_the_hook_hands_raw_values_to_one_card_function (satu regex atas
 * blok panggilan di hook yang menuntut nama argumen mentahnya persis). Nilai mentah itu sendiri
 * dihitung di luar fungsi ini (posisi dari usePosisiLapangan, tujuan dari posBerikutnya, letak
 * dari proyeksikan, sepanjangM dari sepanjangPos) dan tidak dijaga kedua test itu.
 */
export function keadaanKartu(masukan: MasukanKartu): { keputusan: KeputusanTiba; kartu: KartuLapangan } {
    const { posisi, tujuan } = masukan;
    const bergerak = masukan.berlangsung && !masukan.dijeda;
    const keputusan: KeputusanTiba =
        bergerak && posisi !== null && tujuan !== null && tujuan.lng !== null && tujuan.lat !== null
            ? keputusanTiba(jarakMeter([posisi.lng, posisi.lat], [tujuan.lng, tujuan.lat]), posisi.akurasi, masukan.radiusTiba)
            : 'belum';
    const lewat = bergerak ? posTerlampaui(masukan.pos, masukan.tercapai, masukan.letak, posisi?.akurasi ?? Infinity) : null;

    return {
        keputusan,
        kartu: kartuLapangan({
            keputusan,
            akurasiM: posisi?.akurasi ?? null,
            tujuan: tujuan === null ? null : { urutan: tujuan.urutan, nama: tujuan.nama },
            lewat,
            tolakTiba: masukan.tolakTiba,
            tolakLewatSampai: masukan.tolakLewatSampai,
            sekarang: masukan.sekarang,
        }),
    };
}

/**
 * Jarak menyusuri jalur setiap pos dari awal garis, dalam meter, bila posnya berkoordinat dan
 * cukup dekat dengan garis; null bila tidak.
 */
export function sepanjangPos(garis: Titik[] | null, lng: number | null, lat: number | null): number | null {
    if (garis === null || lng === null || lat === null) {
        return null;
    }

    const letak = proyeksikan(garis, [lng, lat]);

    return letak !== null && letak.menyimpangM <= BATAS_MENYIMPANG_M ? letak.sepanjangM : null;
}

/**
 * Sisa jarak ke pos tujuan: menyusuri jalur bila garis dan letak pos di garis diketahui dan
 * posisi berada dekat garis, selain itu garis lurus, dan caranya selalu disebut.
 */
export function sisaJarak(
    garis: Titik[] | null,
    posisi: Titik,
    tujuan: { lng: number | null; lat: number | null; sepanjangM: number | null },
): { meter: number; cara: 'jalur' | 'garis lurus' } | null {
    if (garis !== null && tujuan.sepanjangM !== null) {
        const letak = proyeksikan(garis, posisi);

        if (letak !== null && letak.menyimpangM <= BATAS_MENYIMPANG_M && tujuan.sepanjangM >= letak.sepanjangM) {
            return { meter: tujuan.sepanjangM - letak.sepanjangM, cara: 'jalur' };
        }
    }

    if (tujuan.lng !== null && tujuan.lat !== null) {
        return { meter: jarakMeter(posisi, [tujuan.lng, tujuan.lat]), cara: 'garis lurus' };
    }

    return null;
}
