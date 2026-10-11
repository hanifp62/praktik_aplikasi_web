/**
 * Keputusan keluar akun dan pembersihan data Memount di perangkat (spec v3 bagian 10.6; rencana
 * induk W6 butir 7; ruling W6-R10 dan W6-11a sampai W6-11e). Tanpa impor nilai (ruling L3):
 * LogoutClearsDeviceDataTest menjalankannya di Node. keluar.ts merangkainya dengan Inertia,
 * simpan.ts, antrean.ts, dan perekam jejak; pembantu penyimpanan peramban di sini menerima API-nya
 * lewat argumen supaya dapat diuji dengan tiruan.
 *
 * Nama penyimpanan di bawah disalin dari pemiliknya, karena modul ini tidak boleh mengimpornya;
 * test yang sama memeriksa bahwa salinannya sama.
 */

/** Awalan basis data paket per pengguna (AWALAN_BASIS_DATA di simpan.ts). */
export const AWALAN_PAKET = 'memount-luring-';

/** Awalan basis data jejak per pengguna (AWALAN_DB di jejak.js). */
export const AWALAN_JEJAK = 'jejak-pendakian-';

/** Basis data jejak lama tanpa pemilik, sebelum W6 (DB_LAMA di jejak.js). */
export const JEJAK_LAMA = 'jejak-pendakian';

/** Awalan antrean aksi mode lapangan di sessionStorage (penyimpananSessionStorage di antrean.ts). */
export const AWALAN_ANTREAN = 'mendaki.antrean.';

/** Penanda shell paket di setiap cache shell-* (resources/views/sw.blade.php, ruling W6-9g). */
export const PENANDA_SHELL = '/__memount-shell-paket';

/** Penghapusan yang terhalang tab lain ditunggu paling lama ini sebelum dilaporkan tertunda (sama dengan simpan.ts). */
export const BATAS_HAPUS_MS = 3000;

/** Yang belum terkirim di perangkat ini milik pengguna yang keluar (ruling W6-11a). */
export interface HitunganKeluar {
    /** Titik di jejak-pendakian-<kunci>. */
    milik: number;
    /** Titik di basis data jejak lama tanpa pemilik. */
    lama: number;
    /** Catatan tiba, jeda, dan lanjut yang menunggu, di basis data paket dan sessionStorage tab ini. */
    aksi: number;
}

/** 'tertunda': tab lain masih memegang basis data; 'gagal': ada penghapusan yang ditolak peramban. */
export type HasilBersih = 'terhapus' | 'tertunda' | 'gagal';

export const KALIMAT_GAGAL_HITUNG =
    'Memount tidak dapat memeriksa jejak pendakian dan catatan yang belum terkirim di perangkat ini. Keluar akan menghapus semua data Memount di perangkat ini, termasuk yang mungkin belum terkirim. Lanjutkan keluar?';

export const KALIMAT_TERTUNDA =
    'Data Memount di perangkat ini sedang dipakai tab Memount lain, jadi sebagian baru terhapus sesudah tab itu ditutup. Tutup tab Memount lain, lalu pilih OK untuk keluar. Pilih Batal untuk tetap masuk.';

function angka(n: number): string {
    return n.toLocaleString('id-ID');
}

/**
 * Pertanyaan sebelum keluar (ruling W6-11a, PT11-1): null bila tidak ada yang belum terkirim.
 * Kalimatnya tidak menjanjikan jalan kirim tertentu (mode lapangan, sinyal), jadi benar sebelum
 * dan sesudah sisipan W6-P2 dan sejalan dengan teks persetujuan rekam jejak. Titik basis data lama
 * disebut terpisah karena pemiliknya tidak diketahui. Hitungan yang gagal tetap bertanya (PT11-8),
 * karena diam berarti menghapus yang mungkin belum terkirim tanpa ditanya.
 *
 * Kalimat Batal menyebut pendakian yang masih berlangsung (ruling W6-FC): catatan tiba atau jeda
 * milik pendakian yang sudah diselesaikan dijawab 409 dan tidak pernah terkirim, jadi "supaya
 * catatan itu dapat terkirim" salah di cabang itu.
 */
export function kalimatKeluar(hitungan: HitunganKeluar | null): string | null {
    if (hitungan === null) {
        return KALIMAT_GAGAL_HITUNG;
    }

    const { milik, lama, aksi } = hitungan;
    const bagian: string[] = [];

    if (milik > 0) {
        bagian.push(`Ada ${angka(milik)} titik jejak pendakian Anda yang belum terkirim di perangkat ini.`);
    }

    if (aksi > 0) {
        bagian.push(`Ada ${angka(aksi)} catatan tiba di pos atau jeda yang belum terkirim.`);
    }

    if (lama > 0) {
        bagian.push(`Ada ${angka(lama)} titik jejak dari sebelum pembaruan aplikasi, tersimpan tanpa tanda pemilik.`);
    }

    if (bagian.length === 0) {
        return null;
    }

    bagian.push(
        milik > 0 || aksi > 0
            ? 'Keluar akan menghapus semuanya dari perangkat ini, dan itu tidak dapat dipulihkan. Pilih Batal untuk tetap masuk, supaya jejak dan catatan pendakian yang masih berlangsung sempat terkirim.'
            : 'Keluar akan menghapusnya dari perangkat ini.',
    );

    return bagian.join(' ');
}

/** Sebab rute keluar tidak memberi jawaban yang dapat dibaca halaman ini. */
export type GagalKeluar = 'jaringan' | 'http' | 'dihentikan';

/**
 * Kalimat bila rute keluar gagal sesudah data perangkat dihapus (ruling W6-11c, diamandemen
 * W6-11h). "Belum pasti" di setiap cabang, karena tidak satu pun membuktikan sesi masih hidup:
 * - jaringan: jawabannya bisa hilang sesudah sesi berakhir;
 * - http: 404, 419, atau 5xx, sebelum maupun sesudah keluar diproses (W6-11h);
 * - dihentikan: kunjungan lain atau tombol Kembali membatalkan permintaan di halaman ini, padahal
 *   server mungkin sudah memprosesnya (tinjauan Tugas 11 m-3).
 * Bila sebagian penghapusan tertunda atau ditolak, kalimatnya tidak menyebut data sudah dihapus
 * (butir 2).
 */
export function kalimatGagalKeluar(jenis: GagalKeluar, hasil: HasilBersih): string {
    const data = hasil === 'terhapus' ? 'Data Memount di perangkat ini sudah dihapus.' : 'Sebagian data Memount di perangkat ini belum terhapus.';

    if (jenis === 'jaringan') {
        return `Belum pasti keluar: sambungan putus. ${data} Ketuk Keluar lagi saat ada sinyal.`;
    }

    return jenis === 'http'
        ? `Belum pasti keluar: jawaban server tidak dapat dibaca. ${data} Muat ulang halaman, lalu ketuk Keluar lagi.`
        : `Belum pasti keluar: halaman berpindah sebelum jawaban server tiba. ${data} Ketuk Keluar lagi.`;
}

export interface AlatKeluar {
    /** Yang belum terkirim; null atau lemparan berarti tidak dapat diperiksa. */
    hitung(): Promise<HitunganKeluar | null>;
    /** Pertanyaan ke pendaki; true berarti lanjut keluar. */
    tanya(kalimat: string): boolean;
    bersihkan(): Promise<HasilBersih>;
    /** Mengakhiri sesi di server; selesai sesudah jawabannya, berhasil atau gagal. */
    akhiri(hasil: HasilBersih): Promise<void> | void;
}

export type HasilKeluar = 'batal' | 'batal-tertunda' | 'diakhiri';

/**
 * Hasil janji, atau null bila janji belum selesai dalam `ms` (tinjauan Tugas 11 m-1). Penolakan
 * sebelum batas tetap diteruskan; penolakan sesudah batas diabaikan.
 */
export async function dalamBatas<T>(janji: Promise<T>, ms: number): Promise<T | null> {
    let pewaktu: ReturnType<typeof setTimeout> | null = null;
    const habis = new Promise<null>((selesai) => {
        pewaktu = setTimeout(() => selesai(null), ms);
    });

    janji.catch(() => undefined);

    try {
        return await Promise.race([janji, habis]);
    } finally {
        if (pewaktu !== null) {
            clearTimeout(pewaktu);
        }
    }
}

/**
 * Urutan keluar (ruling W6-R10, W6-11c): tanya bila ada yang belum terkirim atau hitungannya
 * gagal, hapus data perangkat, tanya lagi bila tab lain menahan penghapusan, baru akhiri sesi.
 * Batal tidak menghapus apa pun. Penghapusan yang melempar tetap mengakhiri sesi: di perangkat
 * bersama, sesi yang tetap hidup lebih berbahaya, dan Clear-Site-Data respons logout menjadi
 * lapis kedua.
 *
 * Hitungan dibatasi BATAS_HAPUS_MS (tinjauan Tugas 11 m-1): membuka basis data jejak yang
 * penghapusannya masih ditahan tab lain mengantre tanpa batas, dan tanpa batas ini keluar
 * menggantung tanpa kalimat. Lewat batas berarti hitungan tidak diketahui, jadi pendaki membaca
 * pertanyaan "tidak dapat memeriksa".
 */
export async function alurKeluar(alat: AlatKeluar): Promise<HasilKeluar> {
    let hitungan: HitunganKeluar | null;

    try {
        hitungan = await dalamBatas(alat.hitung(), BATAS_HAPUS_MS);
    } catch {
        hitungan = null;
    }

    const kalimat = kalimatKeluar(hitungan);

    if (kalimat !== null && !alat.tanya(kalimat)) {
        return 'batal';
    }

    let hasil: HasilBersih;

    try {
        hasil = await alat.bersihkan();
    } catch {
        hasil = 'gagal';
    }

    if (hasil === 'tertunda' && !alat.tanya(KALIMAT_TERTUNDA)) {
        return 'batal-tertunda';
    }

    await alat.akhiri(hasil);

    return 'diakhiri';
}

export interface AlatHitung {
    /** Jumlah titik satu basis data jejak tanpa membuatnya; null bila tidak dapat diketahui. */
    jumlahTitik(nama: string): Promise<number | null>;
    /** Antrean aksi di basis data paket pengguna ini (aksiDiPaketDengan); null bila tidak dapat diketahui. */
    aksiDiPaket(kunci: string): Promise<unknown[] | null>;
    /** Antrean aksi di sessionStorage tab ini. */
    aksiDiTab(kunci: string): Promise<unknown[]>;
    /** gabungAntrean dari antrean.ts: aksi sah, tanpa id ganda. */
    gabungAntrean(...daftar: unknown[]): unknown[];
}

/**
 * Hitungan pertanyaan keluar (ruling W6-11a): titik basis data milik dan lama, dan aksi yang
 * menunggu digabung menurut id, supaya aksi yang tercatat di dua penyimpanan dihitung sekali.
 * Satu jumlah yang tidak diketahui membuat seluruh hitungan tidak diketahui (null).
 */
export async function hitungDengan(alat: AlatHitung, kunci: string | null): Promise<HitunganKeluar | null> {
    const [lama, milik] = await Promise.all([alat.jumlahTitik(JEJAK_LAMA), kunci ? alat.jumlahTitik(`${AWALAN_JEJAK}${kunci}`) : Promise.resolve(0)]);

    if (lama === null || milik === null) {
        return null;
    }

    if (!kunci) {
        return { milik, lama, aksi: 0 };
    }

    const [diPaket, diTab] = await Promise.all([alat.aksiDiPaket(kunci), alat.aksiDiTab(kunci)]);

    if (diPaket === null) {
        return null;
    }

    return { milik, lama, aksi: alat.gabungAntrean(diPaket, diTab).length };
}

export interface AlatAksiPaket {
    /** Nama dari indexedDB.databases(); null bila peramban tidak memilikinya. */
    daftarBasisData(): Promise<string[] | null>;
    kunciTersimpan(): string | null;
    kunciTertutup(kunci: string): boolean;
    /** Membaca antrean dari basis data yang sudah ada, menurut namanya, lalu menutup koneksinya. */
    bacaMenurutNama(nama: string): Promise<unknown[]>;
}

/**
 * Antrean aksi di basis data paket satu kunci untuk pertanyaan keluar (ruling W6-11g bagian m-2).
 * Dibaca menurut nama, bukan lewat buka() atau adaBasisData() di simpan.ts. Keduanya menolak
 * kunci yang sudah ditutup percobaan keluar sebelumnya di tab ini (W6-11d), padahal basis datanya
 * dapat masih berisi aksi: penghapusan ditahan tab lain, atau tab lain menulisnya lagi. Tanpa
 * itu, percobaan ulang menghitung aksi terlalu sedikit dan dapat menghapusnya tanpa ditanya.
 *
 * Basis data tidak pernah dibuat untuk dihitung. Tanpa indexedDB.databases(), keberadaannya hanya
 * diketahui dari kunci tersimpan. Kunci tertutup tanpa kunci tersimpan tidak dapat diperiksa
 * tanpa membuka, jadi hasilnya null (tidak diketahui).
 */
export async function aksiDiPaketDengan(alat: AlatAksiPaket, kunci: string): Promise<unknown[] | null> {
    const nama = `${AWALAN_PAKET}${kunci}`;
    const daftar = await alat.daftarBasisData();

    if (daftar !== null) {
        return daftar.includes(nama) ? alat.bacaMenurutNama(nama) : [];
    }

    if (alat.kunciTersimpan() === kunci) {
        return alat.bacaMenurutNama(nama);
    }

    return alat.kunciTertutup(kunci) ? null : [];
}

/**
 * Satu jalan sekaligus (PT11-10): panggilan selama yang pertama berjalan diabaikan (hasil false),
 * jadi ketukan ganda tidak menampilkan dua pertanyaan atau mengirim dua permintaan keluar. Pintunya
 * terbuka lagi sesudah selesai, batal, atau gagal.
 */
export function sekaliJalan<A extends unknown[]>(kerja: (...argumen: A) => Promise<unknown>): { jalankan: (...argumen: A) => Promise<boolean>; berjalan: () => boolean } {
    let sedangKeluar = false;

    return {
        berjalan: () => sedangKeluar,
        jalankan: async (...argumen: A) => {
            if (sedangKeluar) {
                return false;
            }

            sedangKeluar = true;

            try {
                await kerja(...argumen);
            } finally {
                sedangKeluar = false;
            }

            return true;
        },
    };
}

export interface PelacakRiwayat {
    /** Peramban memulihkan halaman dari riwayat (popstate). */
    popstate(): void;
    /** Kunjungan Inertia dimulai: props berikutnya datang dari server. */
    mulaiKunjungan(): void;
    /** Props halaman sekarang jawaban server, bukan pulihan riwayat. */
    boleh(): boolean;
}

/**
 * Asal props halaman (ruling W6-11g bagian I-1). Inertia memulihkan props lama dari
 * history.state, tanpa bertanya ke server, untuk dokumen yang dimuat lewat tombol Kembali atau
 * Maju (back_forward) dan untuk popstate di dokumen yang sama. Props itu dapat milik akun yang sudah
 * keluar, padahal yang sedang masuk akun lain. Bendera bernilai benar sejak back_forward atau
 * popstate, dan salah sejak kunjungan berikutnya dimulai.
 */
export function pelacakRiwayat(awalDariRiwayat: boolean): PelacakRiwayat {
    let propsDariRiwayat = awalDariRiwayat;

    return {
        popstate: () => {
            propsDariRiwayat = true;
        },
        mulaiKunjungan: () => {
            propsDariRiwayat = false;
        },
        boleh: () => !propsDariRiwayat,
    };
}

/**
 * Penjaga ganti akun (ruling W6-R10, W6-11g): membersihkan data perangkat kunci selain `kunci`,
 * hanya bila kunci itu dari jawaban server. Kunci dari props pulihan riwayat dapat milik akun lama,
 * dan memakainya berarti menghapus data akun yang sedang masuk tanpa bertanya (tinjauan Tugas 11
 * I-1). Benar bila pembersihan dijalankan.
 */
export function jalankanPenjaga(kunci: string | null, riwayat: PelacakRiwayat, bersihkan: (kunci: string) => void): boolean {
    if (kunci === null || !riwayat.boleh()) {
        return false;
    }

    bersihkan(kunci);

    return true;
}

/** Tombol Keluar yang memulai keluar: menu avatar, kartu Akun di Profil, atau halaman verifikasi surel. */
export type AsalKeluar = 'menu' | 'kartu' | 'verifikasi';

/** Keadaan keluar di tab ini: satu toko untuk semua tombol, dengan asal tombol yang memulainya. */
export interface KeadaanKeluar {
    sibuk: boolean;
    pesan: string;
    asal: AsalKeluar | null;
}

/**
 * Yang dilihat satu tombol Keluar (tinjauan Tugas 11 m-4): sibuk dan kalimat hanya milik tombol
 * yang memulai keluar, supaya kalimat kegagalan ditulis ke satu wilayah status saja walau menu
 * avatar dan kartu Akun tampil bersamaan di Profil layar lebar.
 */
export function pandanganKeluar(keadaan: KeadaanKeluar, asal: AsalKeluar): { sibuk: boolean; pesan: string } {
    const milik = keadaan.asal === asal;

    return { sibuk: milik && keadaan.sibuk, pesan: milik ? keadaan.pesan : '' };
}

/** Bagian router Inertia yang dipakai keluar; test memakai router tiruan. */
export interface RouterKeluar {
    post(url: string, data: Record<string, never>, opsi: { onNetworkError: () => boolean; onHttpException: () => boolean; onCancel: () => void; onFinish: () => void }): void;
    on(nama: 'navigate', panggil: () => void): () => void;
}

export interface BahanKeluar {
    router: RouterKeluar;
    rute: string;
    /** Alat alur keluar untuk satu kunci, tanpa akhiri (disusun di sini). */
    alat(kunci: string | null): Omit<AlatKeluar, 'akhiri'>;
}

export interface PengendaliKeluar {
    keluar(kunci: string | null, asal: AsalKeluar): Promise<void>;
    berjalan(): boolean;
    keadaan(): KeadaanKeluar;
    dengar(panggil: () => void): () => void;
}

/**
 * Pengendali keluar satu tab (ruling W6-R10, W6-11c, W6-11h; tinjauan Tugas 11 m-3, m-4, m-5):
 * - satu keluar sekaligus (sekaliJalan); `sibuk` benar selama berjalan dan selalu kembali salah;
 * - rute keluar dikirim sesudah data perangkat dihapus. Galat jaringan dan HTTP menulis kalimat
 *   W6-11c dan mengembalikan false, jadi pesan jaringan umum dan dialog galat Inertia tidak tampil.
 *   Kalimatnya hilang pada perpindahan halaman berikutnya;
 * - kunjungan lain atau tombol Kembali yang membatalkan permintaan menulis kalimat "halaman
 *   berpindah". Kalimat itu tidak dihapus perpindahan halaman, karena perpindahan itulah
 *   sebabnya; ia diganti percobaan keluar berikutnya;
 * - onFinish menyelesaikan penantian di setiap cabang (berhasil, galat, dibatalkan).
 */
export function buatKeluar(bahan: BahanKeluar): PengendaliKeluar {
    let keadaan: KeadaanKeluar = { sibuk: false, pesan: '', asal: null };
    const pendengar = new Set<() => void>();

    const ubah = (baru: Partial<KeadaanKeluar>) => {
        keadaan = { ...keadaan, ...baru };
        pendengar.forEach((panggil) => panggil());
    };

    const tulisPesan = (pesan: string, bertahan: boolean) => {
        ubah({ pesan });

        if (bertahan) {
            return;
        }

        const lepas = bahan.router.on('navigate', () => {
            lepas();

            if (keadaan.pesan === pesan) {
                ubah({ pesan: '' });
            }
        });
    };

    const akhiri = (hasil: HasilBersih) =>
        new Promise<void>((selesai) => {
            bahan.router.post(bahan.rute, {}, {
                onNetworkError: () => {
                    tulisPesan(kalimatGagalKeluar('jaringan', hasil), false);

                    return false;
                },
                onHttpException: () => {
                    tulisPesan(kalimatGagalKeluar('http', hasil), false);

                    return false;
                },
                onCancel: () => tulisPesan(kalimatGagalKeluar('dihentikan', hasil), true),
                onFinish: () => selesai(),
            });
        });

    const pintu = sekaliJalan(async (kunci: string | null, asal: AsalKeluar) => {
        ubah({ sibuk: true, pesan: '', asal });

        try {
            await alurKeluar({ ...bahan.alat(kunci), akhiri });
        } finally {
            ubah({ sibuk: false });
        }
    });

    return {
        keluar: (kunci, asal) => pintu.jalankan(kunci, asal).then(
            () => undefined,
            () => undefined,
        ),
        berjalan: () => pintu.berjalan(),
        keadaan: () => keadaan,
        dengar: (panggil) => {
            pendengar.add(panggil);

            return () => {
                pendengar.delete(panggil);
            };
        },
    };
}

/** Kunci perangkat dari nama basis data paket atau jejak; null untuk nama lain dan basis data jejak lama. */
export function kunciDariNama(nama: string): string | null {
    for (const awalan of [AWALAN_PAKET, AWALAN_JEJAK]) {
        if (nama.startsWith(awalan) && nama.length > awalan.length) {
            return nama.slice(awalan.length);
        }
    }

    return null;
}

/**
 * Nama basis data yang diketahui tanpa indexedDB.databases() (PT11-2 bagian 2): milik kunci
 * sekarang dan kunci tersimpan bila berbeda, ditambah basis data jejak lama. Menghapus basis data
 * yang tidak ada tidak berakibat apa pun.
 */
export function namaDikenal(kunci: string | null, tersimpan: string | null): string[] {
    const kunciDikenal = [...new Set([kunci, tersimpan])].filter((k): k is string => typeof k === 'string' && k !== '');

    return [...kunciDikenal.flatMap((k) => [`${AWALAN_PAKET}${k}`, `${AWALAN_JEJAK}${k}`]), JEJAK_LAMA];
}

/**
 * Basis data yang dihapus (PT11-2, ruling W6-11e), urut dan tanpa ganda. Saat keluar: semua
 * memount-luring-*, semua jejak-pendakian-*, dan jejak-pendakian lama. Penjaga ganti akun: hanya
 * yang akhirannya bukan kunci sekarang, dan TIDAK jejak-pendakian lama, karena pemiliknya tidak
 * diketahui dan bisa pengguna sekarang. Nama milik aplikasi lain di asal yang sama tidak disentuh.
 */
export function namaUntukDihapus(semua: string[], kunciSekarang: string | null, saatKeluar: boolean): string[] {
    const hasil = semua.filter((nama) => {
        if (nama === JEJAK_LAMA) {
            return saatKeluar;
        }

        const kunci = kunciDariNama(nama);

        return kunci !== null && (saatKeluar || kunci !== kunciSekarang);
    });

    return [...new Set(hasil)].sort();
}

/** Kunci sessionStorage antrean yang dihapus (PT11-3): semuanya saat keluar, selain milik kunci sekarang di penjaga. */
export function kunciAntreanUntukDihapus(semua: string[], kunciSekarang: string | null, saatKeluar: boolean): string[] {
    const milikSekarang = `${AWALAN_ANTREAN}${kunciSekarang}`;

    return [...new Set(semua.filter((k) => k.startsWith(AWALAN_ANTREAN) && (saatKeluar || k !== milikSekarang)))].sort();
}

export interface RencanaBersih {
    /** Kunci basis data paket yang dihapus (hapusBasisData di simpan.ts). */
    paket: string[];
    /** Nama basis data jejak yang dihapus menurut nama. */
    jejak: string[];
    /** Kunci yang ditutup di tab ini sebelum penghapusan apa pun (ruling W6-11d). */
    ditutup: string[];
    /** Kunci sessionStorage antrean yang dihapus. */
    antrean: string[];
    /** Ada data yang dibersihkan; penjaga tanpa data akun lain tidak menyentuh apa pun. */
    ada: boolean;
}

export function rencanaBersih(masukan: { basisData: string[]; kunciSesi: string[]; kunci: string | null; tersimpan: string | null; saatKeluar: boolean }): RencanaBersih {
    const { kunci, tersimpan, saatKeluar } = masukan;
    const nama = namaUntukDihapus([...masukan.basisData, ...namaDikenal(kunci, tersimpan)], kunci, saatKeluar);
    const antrean = kunciAntreanUntukDihapus(masukan.kunciSesi, kunci, saatKeluar);

    return {
        paket: nama.filter((n) => n.startsWith(AWALAN_PAKET)).map((n) => n.slice(AWALAN_PAKET.length)),
        jejak: nama.filter((n) => !n.startsWith(AWALAN_PAKET)),
        ditutup: [...new Set(nama.map(kunciDariNama).filter((k): k is string => k !== null))].sort(),
        antrean,
        ada: saatKeluar || nama.length > 0 || antrean.length > 0,
    };
}

/** Satu hasil dari semua penghapusan: tertunda didahulukan, lalu gagal. */
export function ringkasHasil(hasil: Array<PromiseSettledResult<unknown>>): HasilBersih {
    if (hasil.some((h) => h.status === 'fulfilled' && h.value === 'tertunda')) {
        return 'tertunda';
    }

    return hasil.some((h) => h.status === 'rejected') ? 'gagal' : 'terhapus';
}

export interface AlatBersih {
    /** Nama dari indexedDB.databases(); kosong bila peramban tidak memilikinya. */
    daftarBasisData(): Promise<string[]>;
    kunciSesi(): string[];
    kunciTersimpan(): string | null;
    tutupKunci(kunci: string): void;
    /** Mengosongkan antrean di memori modul antrean bila termuat di dokumen ini. */
    kosongkanAntrean(): Promise<void>;
    hapusSesi(kunci: string): void;
    hapusPaket(kunci: string): Promise<'terhapus' | 'tertunda'>;
    hapusJejak(nama: string): Promise<'terhapus' | 'tertunda'>;
    bersihkanCache(): Promise<void>;
    kosongkanOpfs(): Promise<void>;
    lupakanKunci(): void;
}

/**
 * Menghapus data perangkat menurut rencanaBersih. Urutannya:
 * - kunci yang dibersihkan ditutup lebih dulu (W6-11d), supaya tidak ada yang membukanya lagi di
 *   tab ini selama penghapusan;
 * - saat keluar, antrean memori dikosongkan sebelum sessionStorage dihapus, supaya kiriman yang
 *   sedang berjalan tidak menulisnya lagi. Penantiannya dibatasi BATAS_HAPUS_MS: modul antrean
 *   yang belum termuat di dokumen ini tidak punya memori untuk dikosongkan, dan memuatnya di
 *   sinyal lemah tidak boleh menahan keluar tanpa batas;
 * - basis data, cache, dan OPFS dihapus bersamaan;
 * - kunci tersimpan dilupakan hanya bila SESUDAH penghapusan ia masih salah satu kunci yang
 *   dibersihkan, jadi kunci yang baru disimpan tab lain tidak hilang (PT11-2 bagian 3).
 */
export async function bersihkanDengan(alat: AlatBersih, kunci: string | null, saatKeluar: boolean): Promise<HasilBersih> {
    const basisData = await alat.daftarBasisData().catch((): string[] => []);
    const rencana = rencanaBersih({ basisData, kunciSesi: alat.kunciSesi(), kunci, tersimpan: alat.kunciTersimpan(), saatKeluar });

    if (!rencana.ada) {
        return 'terhapus';
    }

    rencana.ditutup.forEach((k) => alat.tutupKunci(k));

    if (saatKeluar) {
        await dalamBatas(alat.kosongkanAntrean(), BATAS_HAPUS_MS).catch(() => undefined);
    }

    rencana.antrean.forEach((k) => alat.hapusSesi(k));

    const hasil = await Promise.allSettled([
        ...rencana.paket.map((k) => alat.hapusPaket(k)),
        ...rencana.jejak.map((n) => alat.hapusJejak(n)),
        alat.bersihkanCache(),
        alat.kosongkanOpfs(),
    ]);
    const tersimpan = alat.kunciTersimpan();

    if (tersimpan !== null && rencana.ditutup.includes(tersimpan)) {
        alat.lupakanKunci();
    }

    return ringkasHasil(hasil);
}

/**
 * Menghapus satu basis data menurut namanya, tanpa membuka atau memuat modul pemiliknya (PT11-8
 * bagian 2). Koneksi tab ini menutup diri lewat versionchange (simpan.ts dan jejak.js). Bila tab
 * lain menahannya lebih dari tiga detik, hasilnya 'tertunda': peramban menghapusnya begitu koneksi
 * terakhir tertutup (PT11-4). Peramban tanpa IndexedDB tidak menyimpan apa pun di sana.
 */
export function hapusMenurutNama(nama: string, batasMs = BATAS_HAPUS_MS): Promise<'terhapus' | 'tertunda'> {
    if (typeof indexedDB === 'undefined') {
        return Promise.resolve('terhapus');
    }

    return new Promise((selesai, gagal) => {
        let pewaktu: ReturnType<typeof setTimeout> | null = null;
        const henti = () => {
            if (pewaktu !== null) {
                clearTimeout(pewaktu);
            }
        };
        const permintaan = indexedDB.deleteDatabase(nama);

        permintaan.onsuccess = () => {
            henti();
            selesai('terhapus');
        };
        permintaan.onerror = () => {
            henti();
            gagal(permintaan.error);
        };
        permintaan.onblocked = () => {
            pewaktu ??= setTimeout(() => selesai('tertunda'), batasMs);
        };
    });
}

type CacheTiruan = Pick<CacheStorage, 'keys' | 'delete' | 'open'>;

/**
 * Cache Storage: semua cache pack-* dibuang, dan penanda shell paket dihapus dari setiap cache
 * shell-* (ruling W6-9g). Shell kecil dan aset berversi tetap tersimpan; tanpa penanda, versi
 * service worker berikutnya tidak lagi membawa shell paket. Peramban tanpa Cache Storage tidak
 * menyimpan apa pun di sana.
 */
export async function bersihkanCache(api: CacheTiruan | undefined = globalThis.caches): Promise<void> {
    if (!api) {
        return;
    }

    const nama = await api.keys();

    await Promise.all([
        ...nama.filter((n) => n.startsWith('pack-')).map((n) => api.delete(n)),
        ...nama.filter((n) => n.startsWith('shell-')).map((n) => api.open(n).then((cache) => cache.delete(PENANDA_SHELL))),
    ]);
}

interface DirektoriOpfs {
    keys(): AsyncIterable<string>;
    removeEntry(nama: string, opsi: { recursive: boolean }): Promise<void>;
}

/**
 * OPFS asal ini dikosongkan. Memount belum menulis ke sana; pembersihan ini menjaga janji spec v3
 * bagian 10.6 bila paket kelak memakainya. Peramban tanpa OPFS atau yang menolaknya (mode privat)
 * tidak menyimpan apa pun di sana.
 */
export async function kosongkanOpfs(penyimpanan: { getDirectory?: () => Promise<unknown> } | undefined = globalThis.navigator?.storage): Promise<void> {
    let didapat: DirektoriOpfs | undefined;

    try {
        didapat = (await penyimpanan?.getDirectory?.()) as DirektoriOpfs | undefined;
    } catch {
        return;
    }

    if (!didapat) {
        return;
    }

    const akar = didapat;
    const nama: string[] = [];

    // Nama dikumpulkan dulu: menghapus selama iterasi dapat melewatkan entri.
    for await (const n of akar.keys()) {
        nama.push(n);
    }

    await Promise.all(nama.map((n) => akar.removeEntry(n, { recursive: true })));
}
