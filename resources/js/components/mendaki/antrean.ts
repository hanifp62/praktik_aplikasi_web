/**
 * Antrean aksi mode lapangan (ruling W6-R6): tiba di pos, jeda, dan lanjut.
 *
 * Setiap aksi dicatat dulu di perangkat lalu dikirim; tanpa sinyal ia menunggu di sini dan
 * diputar ulang sekali saat mode lapangan dipasang bila daring (pasangAntrean, Q-12/ruling
 * W6-3z: antrean yang dipulihkan sesudah muat penuh tidak lagi menunggu), saat peramban
 * mengabarkan "online", saat aksi berikutnya ditambahkan, atau tiap 30 detik selama halaman
 * terbuka. Ketiga aksi dapat diputar ulang menurut isinya tanpa kunci idempoten: server memakai
 * UPDATE bersyarat, dan jeda atau lanjut yang waktunya tidak lebih baru dari lanjut terakhir
 * diabaikan (HikingSession::mulaiJeda dan lanjutkanJeda, ruling W6-10f bagian a dan W6-10h).
 * Token XSRF dibaca dari cookie pada saat kirim, bukan saat aksi dibuat, sehingga sesi yang
 * diperbarui tetap diterima.
 *
 * Penyimpanannya memori bawaan sampai mode lapangan memanggil pakaiPenyimpanan() dengan
 * penyimpananSessionStorage() saat halaman dibuka (I-1/ruling W6-3a): antrean lalu bertahan
 * dari muat ulang penuh, berkunci per pengguna, dan terhapus dari penyimpanan begitu aksi
 * terkirim. Tanpa ini, sesi yang berakhir (401/419) menjanjikan "Masuk lagi supaya aksi yang
 * menunggu terkirim", padahal masuk lagi selalu memicu Inertia::location (muat penuh) yang
 * mengosongkan memori modul; sessionStorage bertahan dari muat penuh itu selama tab yang sama.
 *
 * Satu antrean per pengguna di perangkat (ruling W6-9a, menutup M-9 dan W6-3i): shell luring
 * memasang penyimpananGabungan(penyimpananIndexedDb(db), penyimpananSessionStorage(kunci))
 * dengan pemiliknya, yaitu IndexedDB paket pengguna itu yang bertahan walau OS menutup aplikasi,
 * dan yang menyerap antrean sessionStorage tab yang sama. Penyimpanan baru untuk pemilik yang
 * sama digabung dengan antrean memori menurut id dan urutan dibuat, tidak menimpanya; pemilik
 * lain mulai dari isinya sendiri. Aksi yang dijawab server dibuang menurut id, karena aksi
 * terdepan dapat berganti ketika penyimpanan lain bergabung di tengah kiriman. Modul ini tanpa
 * impor: PenyimpananSesiLapanganTest dan AntreanLapanganTest menjalankannya di Node.
 *
 * Mode lapangan daring (W6 Tugas 10) memasang sessionStorage berkunci kunci perangkat dengan
 * pemiliknya, lalu naik ke gabungan IndexedDB paket bila basis datanya ada, jadi tab daring dan
 * shell luring berbagi satu antrean. Dua tab yang hidup bersamaan memutar antrean bergiliran
 * lewat kunci lintas tab (navigator.locks, ruling W6-10f bagian b): di dalam kunci, penyimpanan
 * bersama dibaca ulang sebelum setiap kirim, dan aksi yang pernah terlihat di sana tetapi sudah
 * hilang (dikirim tab lain) dibuang dari memori. Aksi baru juga baru didorong sesudah penyimpanan
 * bersama dibaca ulang (tinjauan Tugas 10 m-B), supaya tab basi tidak menghidupkan lagi aksi yang
 * sudah dikirim tab lain. Tanpa API kunci perilakunya seperti sebelumnya; server mengabaikan jeda
 * dan lanjut yang diputar ulang (HikingSession::mulaiJeda dan lanjutkanJeda).
 */

export interface AksiAntrean {
    id: string;
    url: string;
    isi: Record<string, unknown>;
    dibuat: number;
}

export interface PenyimpananAntrean {
    muat(): Promise<AksiAntrean[]>;
    simpan(aksi: AksiAntrean[]): Promise<void>;
}

/** Penyimpanan yang dapat melaporkan apakah tulisannya benar-benar selesai (W6-9a). */
export interface PenyimpananTerbukti extends PenyimpananAntrean {
    /** Benar hanya sesudah tulisan tersimpan; tidak pernah melempar. */
    tulis(aksi: AksiAntrean[]): Promise<boolean>;
}

export interface KeadaanAntrean {
    jumlah: number;
    /** Kalimat untuk pendaki bila satu aksi ditolak server atau sesi perlu masuk lagi. */
    pesan: string | null;
    /** Putaran kirim sedang berjalan (Q-12/ruling W6-3z). */
    mengirim: boolean;
    /**
     * Kiriman terakhir gagal dan aksi terdepan masih menunggu (W6-3z): 'sementara' untuk galat
     * jaringan, 429, dan 5xx, 'sesi' untuk 401 dan 419. Null sebelum percobaan pertama dan
     * sesudah server menerima atau menolak aksi terdepan.
     */
    gagal: 'sementara' | 'sesi' | null;
}

type Pendengar = (keadaan: KeadaanAntrean) => void;

/**
 * Satu elemen larik tersimpan yang dapat dikirim (Q-11/ruling W6-3y): `url` berupa string dan
 * `isi` berupa objek (bukan null dan bukan larik). Elemen lain, misalnya null, {}, 1, atau "x",
 * dibuang saat dimuat, karena putarAntrean berhenti di aksi terdepan yang gagal: satu elemen
 * rusak di depan akan menahan setiap aksi di belakangnya selamanya. Diekspor (ruling W6-9a)
 * supaya setiap penyimpanan memakai saringan yang sama, bukan salinannya.
 */
export function aksiSah(elemen: unknown): elemen is AksiAntrean {
    if (typeof elemen !== 'object' || elemen === null) {
        return false;
    }

    const { url, isi } = elemen as { url?: unknown; isi?: unknown };

    return typeof url === 'string' && typeof isi === 'object' && isi !== null && !Array.isArray(isi);
}

/** Waktu dibuat untuk pengurutan; nilai yang bukan angka dianggap 0 (ruling W6-9a). */
function waktuDibuat(aksi: AksiAntrean): number {
    return typeof aksi.dibuat === 'number' && Number.isFinite(aksi.dibuat) ? aksi.dibuat : 0;
}

/**
 * Satu antrean dari beberapa daftar (ruling W6-9a): masukan yang bukan larik diabaikan, elemen
 * disaring aksiSah, id string yang ganda dibuang (yang pertama menang, jadi satu aksi dikirim
 * sekali walau tercatat di dua penyimpanan), lalu diurutkan menurut waktu dibuat dan id. Urutan
 * itu penting: server menerima lanjut hanya selagi jeda, jadi lanjut yang terkirim sebelum
 * jedanya diabaikan.
 */
export function gabungAntrean(...daftar: unknown[]): AksiAntrean[] {
    const terlihat = new Set<string>();
    const hasil: AksiAntrean[] = [];

    for (const isi of daftar) {
        if (!Array.isArray(isi)) {
            continue;
        }

        for (const elemen of isi) {
            if (!aksiSah(elemen)) {
                continue;
            }

            if (typeof elemen.id === 'string') {
                if (terlihat.has(elemen.id)) {
                    continue;
                }

                terlihat.add(elemen.id);
            }

            hasil.push(elemen);
        }
    }

    return hasil.sort((a, b) => waktuDibuat(a) - waktuDibuat(b) || (String(a.id) < String(b.id) ? -1 : String(a.id) > String(b.id) ? 1 : 0));
}

function penyimpananMemori(): PenyimpananAntrean {
    let isi: AksiAntrean[] = [];

    return {
        muat: async () => isi,
        simpan: async (aksi) => {
            isi = aksi;
        },
    };
}

/**
 * Penyimpanan berkunci per pengguna di sessionStorage (I-1/ruling W6-3a): bertahan dari muat
 * ulang penuh selama tab ini terbuka, tetapi tidak terbagi ke tab atau perangkat lain, dan
 * hilang saat tab ditutup. Antrean dihapus dari penyimpanan begitu kosong (aksi terkirim),
 * bukan disimpan sebagai larik kosong, supaya "dihapus sesudah terkirim" benar secara literal.
 *
 * Dibungkus try/catch: mode pribadi atau kuota penuh membuat sessionStorage melempar, dan
 * antrean tetap bekerja di memori modul selama mode lapangan TIDAK dipasang ulang: sekali
 * kirimAksi dipanggil, aksinya aman di memori sampai terkirim (docblock jujur, N-4/ruling
 * W6-3n). Residu yang belum diperbaiki (N-4): bila mode lapangan dipasang ulang (pindah
 * halaman lalu kembali tanpa muat penuh) SELAGI sessionStorage masih melempar, pastikanTermuat()
 * di bawah membaca ulang lewat muat() yang jatuh ke catch dan mengembalikan larik kosong,
 * menimpa antrean memori yang belum tersalin ke penyimpanan. Kemungkinannya kecil (peramban
 * yang memblokir penyimpanan tetapi mengizinkan cookie, atau kuota penuh persis pada saat itu);
 * perbaikannya satu kelas dengan M-9 (muat() menjaga memori saat gagal, atau penggabungan
 * menurut id, bukan menimpa). Sejak ruling W6-9a residu itu tertutup untuk pemasangan yang
 * menyebut pemiliknya (pakaiPenyimpanan(baru, pemilik)), yang menggabung, bukan menimpa;
 * pemasangan tanpa pemilik tetap seperti di atas.
 *
 * Kuncinya kunci perangkat per pengguna (prop bersama `perangkat`, HMAC id pengguna): mode
 * lapangan daring dan cadangan shell luring sama-sama memakainya sejak W6 Tugas 10 (ruling W6-9a).
 * Tipe angka tetap diterima untuk pemanggil lama dan test.
 */
export function penyimpananSessionStorage(pemilik: string | number): PenyimpananAntrean {
    const kunci = kunciSessionStorage(pemilik);

    return {
        muat: async () => {
            try {
                const mentah = sessionStorage.getItem(kunci);
                const diurai: unknown = mentah ? JSON.parse(mentah) : [];

                // Isi yang bukan larik diabaikan, bukan dibiarkan mematahkan kirimAksi
                // (N-5/ruling W6-3l): format baru yang tidak kompatibel di kunci yang sama
                // (misalnya dari paket lapangan Tugas 9-11) tidak boleh menghentikan antrean.
                // Elemen larik yang bukan aksi sah juga disaring (Q-11/ruling W6-3y).
                return Array.isArray(diurai) ? diurai.filter(aksiSah) : [];
            } catch {
                return [];
            }
        },
        simpan: async (aksi) => {
            try {
                if (aksi.length === 0) {
                    sessionStorage.removeItem(kunci);
                } else {
                    sessionStorage.setItem(kunci, JSON.stringify(aksi));
                }
            } catch {
                // sessionStorage penuh atau diblokir: antrean tetap bekerja di memori modul.
            }
        },
    };
}

/** Kunci sessionStorage antrean satu pemilik; satu sumber untuk penyimpanan dan pembaca ruang kerja. */
function kunciSessionStorage(pemilik: string | number): string {
    return `mendaki.antrean.${pemilik}`;
}

/** Kunci tetap larik aksi di toko antrean basis data paket (spec v3 bagian 10.4). */
const KUNCI_AKSI = 'aksi';

/** Nama toko antrean di basis data paket (ANTREAN di simpan.ts, yang membuat tokonya). */
const TOKO_ANTREAN = 'antrean';

/**
 * Antrean di toko IndexedDB basis data paket pengguna (ruling W6-9a; pengganti
 * penyimpananAntrean di simpan.ts brief, yang tidak dapat diuji Node karena modul itu tidak
 * boleh berimpor nilai). Tahan galat (PT9-6): koneksi yang ditutup, transaksi yang melempar,
 * atau isi yang bukan larik membuat muat memberi larik kosong dan simpan tidak pernah melempar,
 * jadi kirimAksi tidak pernah gagal sebelum aksinya masuk antrean memori.
 *
 * Setiap tulis membaca, menggabung, dan menulis dalam satu transaksi readwrite: hasilnya isi
 * tersimpan tanpa aksi yang sudah dibuang penyimpanan INI (terkirim atau ditolak), ditambah
 * aksi yang diberikan. Aksi milik penyimpanan lain atas basis data yang sama (shell dan mode
 * lapangan daring di dua jendela) tidak pernah terhapus olehnya. tulis bernilai benar hanya
 * sesudah transaksi selesai (oncomplete).
 */
export function penyimpananIndexedDb(db: IDBDatabase, toko = 'antrean'): PenyimpananTerbukti {
    /** Id yang terakhir diketahui penyimpanan ini: dari muat dan dari tulis terakhir. */
    let dikenal = new Set<string>();
    const dibuang = new Set<string>();
    const idDari = (aksi: AksiAntrean[]) => new Set(aksi.flatMap((a) => (typeof a.id === 'string' ? [a.id] : [])));

    const tulis = (aksi: AksiAntrean[]): Promise<boolean> =>
        new Promise<boolean>((selesai) => {
            try {
                const sekarang = idDari(aksi);

                dikenal.forEach((id) => {
                    if (!sekarang.has(id)) {
                        dibuang.add(id);
                    }
                });
                dikenal = sekarang;

                const transaksi = db.transaction(toko, 'readwrite');
                const simpanan = transaksi.objectStore(toko);
                const baca = simpanan.get(KUNCI_AKSI);

                baca.onsuccess = () => {
                    const tersimpan = gabungAntrean(baca.result).filter((a) => !(typeof a.id === 'string' && dibuang.has(a.id)));
                    const hasil = gabungAntrean(tersimpan, aksi);

                    if (hasil.length === 0) {
                        simpanan.delete(KUNCI_AKSI);
                    } else {
                        simpanan.put(hasil, KUNCI_AKSI);
                    }
                };
                transaksi.oncomplete = () => selesai(true);
                transaksi.onerror = () => selesai(false);
                transaksi.onabort = () => selesai(false);
            } catch {
                selesai(false);
            }
        });

    return {
        muat: async () => {
            try {
                const isi = await new Promise<unknown>((selesai, gagal) => {
                    const baca = db.transaction(toko, 'readonly').objectStore(toko).get(KUNCI_AKSI);
                    baca.onsuccess = () => selesai(baca.result);
                    baca.onerror = () => gagal(baca.error);
                });
                const hasil = gabungAntrean(isi);

                idDari(hasil).forEach((id) => dikenal.add(id));

                return hasil;
            } catch {
                return [];
            }
        },
        simpan: async (aksi) => {
            await tulis(aksi);
        },
        tulis,
    };
}

/**
 * Penyimpanan utama dengan cadangan (ruling W6-9a): shell memakai IndexedDB paket sebagai utama
 * dan sessionStorage tab yang sama sebagai cadangan. muat menggabung keduanya, menulisnya ke
 * utama, dan mengosongkan cadangan HANYA bila tulis utama terbukti selesai; simpan ke utama, dan
 * bila gagal ke cadangan, supaya aksi tidak hilang selama IndexedDB menolak. Cadangan yang
 * pernah terisi dikosongkan lagi begitu tulis utama berhasil, supaya aksi yang sudah terkirim
 * tidak kembali dari cadangan pada muat berikutnya.
 */
export function penyimpananGabungan(utama: PenyimpananTerbukti, cadangan: PenyimpananAntrean): PenyimpananAntrean {
    let cadanganTerisi = false;

    const kosongkanCadangan = async () => {
        try {
            await cadangan.simpan([]);
            cadanganTerisi = false;
        } catch {
            // Cadangan yang menolak dikosongkan pada tulis utama berikutnya.
        }
    };

    return {
        muat: async () => {
            const [isiUtama, isiCadangan] = await Promise.all([muatAman(utama), muatAman(cadangan)]);
            const hasil = gabungAntrean(isiUtama, isiCadangan);

            if (await utama.tulis(hasil)) {
                await kosongkanCadangan();
            } else if (isiCadangan.length > 0) {
                cadanganTerisi = true;
            }

            return hasil;
        },
        simpan: async (aksi) => {
            if (await utama.tulis(aksi)) {
                if (cadanganTerisi) {
                    await kosongkanCadangan();
                }

                return;
            }

            try {
                await cadangan.simpan(aksi);
                cadanganTerisi = true;
            } catch {
                // Kedua penyimpanan menolak: antrean tetap bekerja di memori modul.
            }
        },
    };
}

async function muatAman(sumber: PenyimpananAntrean): Promise<AksiAntrean[]> {
    try {
        return gabungAntrean(await sumber.muat());
    } catch {
        return [];
    }
}

let penyimpanan: PenyimpananAntrean = penyimpananMemori();
/** Pemilik antrean sekarang: kunci perangkat atau id pengguna; null untuk pemasangan tanpa pemilik. */
let pemilikSekarang: string | number | null = null;
let antrean: AksiAntrean[] = [];
let termuat: Promise<void> | null = null;
let mengirim = false;
let pesan: string | null = null;
let gagal: KeadaanAntrean['gagal'] = null;
/**
 * Naik setiap kali antrean berpindah pemilik atau dikosongkan: pemuatan dan putaran kirim yang
 * dimulai sebelumnya berhenti tanpa menyentuh antrean pemilik baru.
 */
let generasi = 0;
/**
 * Id yang sudah dijawab server pada generasi ini (ruling W6-9m): tidak pernah masuk lagi dari
 * penyimpanan mana pun. Penyimpanan baru untuk pemilik yang sama dapat masih memuat aksi yang baru
 * saja terkirim (cadangan sessionStorage yang ditulis sebelum jawabannya datang), dan tanpa
 * saringan ini pemuatannya menghidupkan aksi itu lagi sehingga putaran yang sama mengirimnya dua kali.
 */
const dijawab = new Set<string>();
/**
 * Id yang pernah terlihat di penyimpanan pada generasi ini, dari pemuatan atau pembacaan ulang
 * (ruling W6-10f bagian b). Aksi yang pernah terlihat lalu hilang dari penyimpanan bersama sudah
 * dijawab tab lain; aksi yang belum pernah tersimpan (penyimpanan menolak) tidak pernah dibuang.
 */
const terlihatTersimpan = new Set<string>();
const pendengar = new Set<Pendengar>();

function idDari(aksi: AksiAntrean[]): Set<string> {
    return new Set(aksi.flatMap((a) => (typeof a.id === 'string' ? [a.id] : [])));
}

/** Kunci lintas tab (Web Locks), hanya untuk pemasangan yang menyebut pemiliknya. */
interface KunciTab {
    request(nama: string, panggil: () => Promise<void>): Promise<unknown>;
}

function kunciLintasTab(): KunciTab | null {
    const locks = (globalThis.navigator as { locks?: Partial<KunciTab> } | undefined)?.locks;

    return pemilikSekarang !== null && locks && typeof locks.request === 'function' ? (locks as KunciTab) : null;
}

/**
 * Membaca ulang penyimpanan bersama (ruling W6-10f bagian b): aksi di memori yang pernah terlihat
 * tersimpan tetapi sudah hilang dari sana dijawab tab lain, jadi dibuang dan dicatat dijawab; aksi
 * tersimpan yang belum ada di memori (dicatat tab lain) digabung. False bila antrean berpindah
 * pemilik atau dikosongkan selama membaca.
 */
async function serapPenyimpanan(milik: number): Promise<boolean> {
    const isi = await muatAman(penyimpanan);

    if (milik !== generasi) {
        return false;
    }

    const ada = idDari(isi);

    antrean = antrean.filter((a) => {
        if (typeof a.id === 'string' && terlihatTersimpan.has(a.id) && !ada.has(a.id)) {
            dijawab.add(a.id);

            return false;
        }

        return true;
    });
    antrean = gabungAntrean(antrean, isi.filter((a) => !(typeof a.id === 'string' && dijawab.has(a.id))));
    ada.forEach((id) => terlihatTersimpan.add(id));

    return true;
}

/**
 * Mengganti penyimpanan antrean.
 *
 * Tanpa pemilik (pemanggil lama dan test s1 sampai s11; mode lapangan daring dan shell luring
 * selalu menyebut pemiliknya sejak W6 Tugas 10): malas seperti sebelum W6-9a, isi penyimpanan
 * baru dimuat pada pemakaian berikutnya dan menggantikan antrean memori.
 *
 * Dengan pemilik (ruling W6-9a, M-9):
 * - pemilik sama dengan sebelumnya: pemuatan menunggu pemuatan sebelumnya selesai, lalu antrean
 *   = gabungAntrean(antrean memori, isi penyimpanan baru tanpa id yang sudah dijawab server),
 *   dan hasilnya ditulis ke penyimpanan baru. Aksi memori yang belum tersalin tidak pernah hilang,
 *   dan aksi yang sudah dijawab tidak hidup lagi dari penyimpanan baru (ruling W6-9m);
 * - pemilik berbeda: antrean, pesan, dan keadaan gagal kosong; isi penyimpanan baru saja;
 * - pemuatan dimulai segera, lalu pendengar dikabari dan antrean diputar sekali bila daring,
 *   sehingga penyimpanan yang naik ke IndexedDB di tengah halaman langsung terlihat dan terkirim.
 */
export function pakaiPenyimpanan(baru: PenyimpananAntrean, pemilik: string | number | null = null): void {
    const sebelumnya = termuat;
    const pemilikSama = pemilik !== null && pemilik === pemilikSekarang;

    if (pemilik !== null && !pemilikSama) {
        generasi++;
        dijawab.clear();
        terlihatTersimpan.clear();
        antrean = [];
        pesan = null;
        gagal = null;
        mengirim = false;
    }

    penyimpanan = baru;
    pemilikSekarang = pemilik;

    if (pemilik === null) {
        termuat = null;

        return;
    }

    const milik = generasi;
    const pemuatan = (async () => {
        if (pemilikSama && sebelumnya !== null) {
            await sebelumnya.catch(() => undefined);
        }

        const isi = await muatAman(baru);

        if (milik !== generasi) {
            return;
        }

        idDari(isi).forEach((id) => terlihatTersimpan.add(id));
        antrean = gabungAntrean(antrean, isi.filter((a) => !(typeof a.id === 'string' && dijawab.has(a.id))));

        try {
            await baru.simpan(antrean);
        } catch {
            // Penyimpanan yang menolak: antrean tetap bekerja di memori modul.
        }
    })();

    termuat = pemuatan;

    void pemuatan.then(() => {
        if (milik === generasi) {
            kabari();
            void putarAntrean();
        }
    });
}

/** Salinan antrean saat ini, sesudah termuat (dipakai shell untuk menerapkan aksi yang menunggu, W6-9d). */
export async function antreanSaatIni(): Promise<AksiAntrean[]> {
    await pastikanTermuat();

    return antrean.slice();
}

/** Keadaan gagal kiriman terakhir (lembar Selesai mode lapangan memilih kalimat sesi, W6-FC). */
export function gagalTerakhir(): KeadaanAntrean['gagal'] {
    return gagal;
}

/** Url aksi lapangan satu trip: data.urls mode lapangan, atau rute trips.hike.* di ruang kerja. */
export interface UrlAksiTrip {
    tiba: string;
    jeda: string;
    lanjut: string;
}

/**
 * Jumlah aksi trip ini yang menahan Selesai (ruling W6-FC, I-2 tinjauan akhir W6): tiba, jeda,
 * dan lanjut trip itu yang masih menunggu di perangkat. Selesai yang mendahuluinya menutup sesi,
 * sehingga aksi itu dijawab 409 dan angka ringkasan salah tanpa kabar. Aksi trip lain tidak
 * menahan. Antrean yang tidak dapat dibaca (null: tanpa kunci perangkat, atau peramban menolak)
 * memberi 0, yaitu perilaku sebelum ruling ini: Selesai dikirim.
 */
export function aksiMenahanSelesai(aksi: unknown[] | null, url: UrlAksiTrip): number {
    if (aksi === null) {
        return 0;
    }

    const milikTrip = new Set([url.tiba, url.jeda, url.lanjut]);

    return gabungAntrean(aksi).filter((a) => milikTrip.has(a.url)).length;
}

/**
 * Kalimat di dekat tombol Simpan selama Selesai tertahan (ruling W6-FC). Di mode lapangan,
 * antrean sudah diputar sebelum kalimat ini dipilih; bila kiriman terakhirnya dijawab 401 atau
 * 419, menunggu sinyal tidak menolong, jadi kalimatnya mengajak masuk lagi seperti pesan sesi
 * antrean. Ruang kerja tidak memutar antrean dan menunjuk mode lapangan, yang memutarnya.
 */
export function kalimatSelesaiTertahan(jumlah: number, tempat: 'lapangan' | 'ruang-kerja', sesiBerakhir = false): string {
    if (tempat === 'ruang-kerja') {
        return `${jumlah} catatan tiba atau jeda dari perangkat ini belum terkirim. Buka mode lapangan supaya catatan itu terkirim, lalu selesaikan pendakian.`;
    }

    return sesiBerakhir
        ? `${jumlah} catatan tiba atau jeda belum terkirim karena sesi Anda berakhir. Masuk lagi supaya catatan itu terkirim, lalu simpan.`
        : `${jumlah} catatan tiba atau jeda belum terkirim. Tunggu sampai terkirim saat ada sinyal, lalu simpan.`;
}

/**
 * Kalimat dan tautan opsional Selesai yang tertahan; bentuknya sama dengan SelesaiTertahan di
 * FormulirSelesai.tsx (yang membaca hasil fungsi ini lewat prop `tahan`), diulang di sini karena
 * modul ini tanpa impor.
 */
export interface SelesaiTertahan {
    kalimat: string;
    tautan?: { href: string; label: string };
}

/**
 * Keputusan Selesai tertahan, satu fungsi murni untuk kedua tempat (temuan N-1 pemeriksa putaran
 * final-fix-check): SelesaiLapangan.tsx dan tahanSelesai di Trips/Show.tsx hanya memanggil fungsi
 * ini, supaya pembalikan atau penetralan syarat tahan terdeteksi oleh test Node di satu tempat,
 * bukan tersebar di dua komponen yang React tidak menjalankannya di suite ini.
 *
 * Tiga keadaan:
 * - kosong (jumlah aksi trip ini yang menahan, dari aksiMenahanSelesai, nol): null;
 * - menunggu (jumlah > 0): kalimat sesuai tempat dan sesi (kalimatSelesaiTertahan), dengan
 *   tautan hanya bila diberikan (ruang kerja menautkan mode lapangan; mode lapangan sendiri
 *   tanpa tautan);
 * - tidak terbaca (aksi null: sessionStorage atau IndexedDB ditolak peramban, atau ruang kerja
 *   tanpa kunci perangkat): null, yaitu perilaku sebelum ruling W6-FC, karena aksiMenahanSelesai
 *   memberi 0 untuk aksi null.
 */
export function selesaiTertahan(aksi: unknown[] | null, url: UrlAksiTrip, tempat: 'lapangan' | 'ruang-kerja', sesiBerakhir = false, tautan?: { href: string; label: string }): SelesaiTertahan | null {
    const jumlah = aksiMenahanSelesai(aksi, url);

    if (jumlah === 0) {
        return null;
    }

    const kalimat = kalimatSelesaiTertahan(jumlah, tempat, sesiBerakhir);

    return tautan ? { kalimat, tautan } : { kalimat };
}

/**
 * Larik aksi di toko antrean basis data paket yang SUDAH ada, tanpa pernah membuatnya (pola W6-P2g,
 * bukaBilaAda di jejak.js): daftar indexedDB.databases() bila peramban memilikinya, lalu pembukaan
 * tanpa versi yang membatalkan upgrade, karena upgradeneeded berarti basis data itu belum ada dan
 * peramban tidak menyimpan basis data baru yang upgrade-nya dibatalkan. Kosong bila tidak ada;
 * null bila peramban menolak. Koneksinya ditutup sesudah membaca, dan juga saat tab lain meminta
 * penghapusan (keluar akun), supaya penghapusan itu tidak tertahan.
 */
async function aksiDiBasisDataBilaAda(nama: string): Promise<unknown[] | null> {
    const idb = (globalThis as { indexedDB?: IDBFactory }).indexedDB;

    if (!idb) {
        return [];
    }

    if (typeof idb.databases === 'function') {
        try {
            if (!(await idb.databases()).some((d) => d.name === nama)) {
                return [];
            }
        } catch {
            // Daftar yang ditolak jatuh ke pembukaan tanpa membuat di bawah.
        }
    }

    return new Promise<unknown[] | null>((selesai) => {
        let belumAda = false;
        let permintaan: IDBOpenDBRequest;

        try {
            permintaan = idb.open(nama);
        } catch {
            selesai(null);

            return;
        }

        permintaan.onupgradeneeded = () => {
            belumAda = true;
            permintaan.transaction?.abort();
        };
        permintaan.onsuccess = () => {
            const db = permintaan.result;

            db.onversionchange = () => db.close();

            try {
                if (!db.objectStoreNames.contains(TOKO_ANTREAN)) {
                    db.close();
                    selesai([]);

                    return;
                }

                const baca = db.transaction(TOKO_ANTREAN, 'readonly').objectStore(TOKO_ANTREAN).get(KUNCI_AKSI);

                baca.onsuccess = () => {
                    db.close();
                    selesai(Array.isArray(baca.result) ? baca.result : []);
                };
                baca.onerror = () => {
                    db.close();
                    selesai(null);
                };
            } catch {
                db.close();
                selesai(null);
            }
        };
        permintaan.onerror = (peristiwa) => {
            if (belumAda) {
                peristiwa.preventDefault();
                selesai([]);

                return;
            }

            selesai(null);
        };
    });
}

/**
 * Aksi yang menunggu di perangkat ini untuk satu pemilik, untuk Selesai di ruang kerja trip (ruling
 * W6-FC): antrean memori modul ini bila pemiliknya sama, cadangan sessionStorage tab ini, dan toko
 * antrean basis data paket bila basis data itu sudah ada, digabung menurut id. Tidak membuat dan
 * tidak mengirim apa pun. Null bila sessionStorage atau IndexedDB ditolak peramban, supaya
 * pemanggil memakai perilaku sebelum ruling.
 *
 * @param namaBasisData nama basis data paket pemilik itu (namaBasisData di simpan.ts)
 */
export async function aksiTersimpanDiPerangkat(pemilik: string, namaBasisData: string): Promise<AksiAntrean[] | null> {
    let mentah: string | null;

    try {
        mentah = sessionStorage.getItem(kunciSessionStorage(pemilik));
    } catch {
        return null;
    }

    let diTab: unknown = [];

    if (mentah) {
        try {
            diTab = JSON.parse(mentah);
        } catch {
            // Isi yang rusak juga diabaikan penyimpananSessionStorage, jadi tidak pernah terkirim.
        }
    }

    const diPaket = await aksiDiBasisDataBilaAda(namaBasisData);

    if (diPaket === null) {
        return null;
    }

    return gabungAntrean(pemilikSekarang === pemilik ? antrean : [], diTab, diPaket);
}

/**
 * Mengembalikan seluruh keadaan modul ke bawaan (keluar akun dan ganti akun, Tugas 11): antrean,
 * pesan, dan keadaan gagal kosong, penyimpanan kembali memori tanpa pemilik, dan pendengar
 * dikabari. Isi penyimpanan pemilik lama tidak disentuh di sini; pemanggilnya yang menghapusnya.
 */
export function kosongkanAntrean(): void {
    generasi++;
    dijawab.clear();
    terlihatTersimpan.clear();
    penyimpanan = penyimpananMemori();
    pemilikSekarang = null;
    antrean = [];
    termuat = null;
    mengirim = false;
    pesan = null;
    gagal = null;
    kabari();
}

function tokenXsrf(): string {
    const cocok = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return cocok?.[1] ? decodeURIComponent(cocok[1]) : '';
}

async function pastikanTermuat(): Promise<void> {
    if (termuat === null) {
        const milik = generasi;

        termuat = penyimpanan.muat().then((isi) => {
            if (milik === generasi) {
                antrean = isi;
                idDari(isi).forEach((id) => terlihatTersimpan.add(id));
            }
        });
    }

    await termuat;
}

function kabari(): void {
    const keadaan: KeadaanAntrean = { jumlah: antrean.length, pesan, mengirim, gagal };
    pendengar.forEach((dengar) => dengar(keadaan));
}

export function dengarAntrean(dengar: Pendengar): () => void {
    pendengar.add(dengar);
    void pastikanTermuat().then(kabari);

    return () => {
        pendengar.delete(dengar);
    };
}

/**
 * Dipanggil sekali saat mode lapangan dipasang (Q-12/ruling W6-3z): mendengar keadaan antrean,
 * lalu memutarnya sekali. putarAntrean berhenti sendiri bila luring, jadi putaran ini hanya
 * mengirim bila daring. Tanpa ini, antrean yang dipulihkan sesudah muat penuh menunggu
 * "online", aksi berikutnya, atau putaran 30 detik, padahal sinyal sudah ada sejak halaman
 * dibuka.
 */
export function pasangAntrean(dengar: Pendengar): () => void {
    const lepas = dengarAntrean(dengar);
    void putarAntrean();

    return lepas;
}

/**
 * Kalimat wilayah status antrean di mode lapangan, satu fungsi murni untuk setiap keadaan
 * (Q-12/ruling W6-3z), supaya tidak ada cabang yang menulis "menunggu sinyal" padahal daring:
 * - luring dengan aksi menunggu: "Luring. {n} catatan menunggu sinyal untuk dikirim.";
 * - daring, kiriman terakhir gagal karena galat jaringan, 429, atau 5xx: "{n} catatan belum
 *   terkirim. Memount mencoba lagi otomatis." Kalimat ini benar karena putaran 30 detik di
 *   useModeLapangan.ts tetap berjalan;
 * - daring, kiriman terakhir dijawab 401 atau 419: "{n} catatan belum terkirim." diikuti pesan
 *   sesinya sendiri ("Sesi Anda berakhir. Masuk lagi ...");
 * - daring dan sedang mengirim: "{n} catatan sedang dikirim.";
 * - daring dan belum dicoba (sesaat sesudah aksi ditambahkan atau antrean dipulihkan):
 *   "{n} catatan menunggu untuk dikirim.";
 * - luring tanpa aksi menunggu: "Luring." (ruling W6-3z2), penanda luring yang ada sebelum
 *   putaran 4; mode lapangan tidak punya penanda luring lain;
 * - daring tanpa aksi menunggu: pesan saja bila ada, selain itu kosong. Wilayahnya tetap
 *   terpasang di ModeLapangan.tsx.
 * Pesan penolakan atau pesan sesi, bila ada, menyusul kalimat di atas, termasuk "Luring.".
 *
 * Gagal didahulukan dari sedang mengirim: percobaan ulang tiap 30 detik sesudah kegagalan tidak
 * mengubah kalimatnya, jadi wilayah status tidak mengumumkan ulang kalimat yang sama di setiap
 * putaran.
 */
export function teksAntrean(daring: boolean, keadaan: KeadaanAntrean): string {
    const n = keadaan.jumlah;
    let kalimat = '';

    if (n > 0) {
        if (!daring) {
            kalimat = `Luring. ${n} catatan menunggu sinyal untuk dikirim.`;
        } else if (keadaan.gagal === 'sementara') {
            kalimat = `${n} catatan belum terkirim. Memount mencoba lagi otomatis.`;
        } else if (keadaan.gagal === 'sesi') {
            kalimat = `${n} catatan belum terkirim.`;
        } else if (keadaan.mengirim) {
            kalimat = `${n} catatan sedang dikirim.`;
        } else {
            kalimat = `${n} catatan menunggu untuk dikirim.`;
        }
    } else if (!daring) {
        kalimat = 'Luring.';
    }

    if (keadaan.pesan === null || keadaan.pesan === '') {
        return kalimat;
    }

    return kalimat === '' ? keadaan.pesan : `${kalimat} ${keadaan.pesan}`;
}

/**
 * Payload tiba dan jeda/lanjut, fungsi murni (N-2/ruling W6-3k): satu tempat yang menentukan
 * bentuk payload yang dikirim lewat antrean, hanya kunci yang disebutkan di sini. useModeLapangan.ts
 * hanya memanggil fungsi ini, tidak pernah merakit objek payload sendiri (lihat asersi sumber
 * LocationPrivacyTest), sehingga kunci tambahan yang diselipkan lewat variabel (MT1_VAR) tidak
 * dapat lolos tanpa mengubah fungsi ini dan mematahkan test Node-nya.
 */
export function payloadTiba(urutan: number): { urutan: number } {
    return { urutan };
}

export function payloadJeda(pada: string): { pada: string } {
    return { pada };
}

export async function kirimAksi(url: string, isi: Record<string, unknown>): Promise<void> {
    await pastikanTermuat();

    // Tab basi membaca ulang penyimpanan bersama SEBELUM aksi baru didorong (tinjauan Tugas 10
    // m-B): simpan() menggabung antrean memori ke penyimpanan, jadi aksi yang sudah dikirim tab lain
    // tetapi masih di memori tab ini akan tertulis lagi dan terkirim dua kali.
    if (kunciLintasTab()) {
        await serapPenyimpanan(generasi);
    }

    antrean.push({ id: globalThis.crypto?.randomUUID?.() ?? `${Date.now()}-${antrean.length}`, url, isi, dibuat: Date.now() });
    await penyimpanan.simpan(antrean);

    // Aksi yang benar-benar tersimpan di penyimpanan bersama tercatat terlihat, supaya tab ini
    // tahu aksi itu dijawab bila tab lain mengirimnya lebih dulu (ruling W6-10f bagian b).
    if (kunciLintasTab()) {
        await serapPenyimpanan(generasi);
    }

    kabari();
    await putarAntrean();
}

async function pesanDari(respons: Response): Promise<string> {
    try {
        const isi = (await respons.json()) as { message?: string };

        return isi.message ? `Satu aksi tidak diterima: ${isi.message}` : 'Satu aksi tidak diterima server.';
    } catch {
        return 'Satu aksi tidak diterima server.';
    }
}

/**
 * Mengirim antrean dari depan. Galat jaringan, 429, dan 5xx menghentikan putaran dan aksinya
 * tetap menunggu; 401 dan 419 juga menunggu, dengan ajakan masuk lagi; penolakan lain (403,
 * 404, 409, 422) membuang aksi itu dan menyebutnya. Keadaan "sedang mengirim" dan "gagal
 * terakhir" ikut dikabarkan ke pendengar (Q-12/ruling W6-3z), untuk teksAntrean.
 *
 * Aksi yang dijawab dibuang menurut identitas atau id-nya, bukan aksi terdepan (ruling W6-9a):
 * penyimpanan yang bergabung di tengah kiriman dapat menaruh aksi yang lebih tua di depan.
 * Putaran yang dimulai sebelum antrean berpindah pemilik atau dikosongkan berhenti tanpa
 * menyentuh keadaan baru.
 *
 * Dua tab yang berbagi antrean (shell luring dan mode lapangan daring, atau dua tab daring)
 * memutar bergiliran lewat navigator.locks bernama `memount-antrean-<pemilik>` bila peramban
 * memilikinya (ruling W6-10f bagian b). Di dalam kunci, penyimpanan bersama dibaca ulang sebelum
 * setiap kirim (serapPenyimpanan), jadi aksi yang sudah dikirim tab lain tidak dikirim lagi.
 */
export async function putarAntrean(): Promise<void> {
    await pastikanTermuat();

    if (mengirim || antrean.length === 0 || navigator.onLine === false) {
        return;
    }

    const milik = generasi;
    const kunciTab = kunciLintasTab();

    mengirim = true;
    kabari();

    try {
        if (kunciTab) {
            await kunciTab.request(`memount-antrean-${pemilikSekarang}`, () => kirimDariDepan(milik, true));
        } else {
            await kirimDariDepan(milik, false);
        }
    } finally {
        if (milik === generasi) {
            mengirim = false;
            kabari();
        }
    }
}

async function kirimDariDepan(milik: number, bacaUlang: boolean): Promise<void> {
    while (milik === generasi) {
        if (bacaUlang) {
            if (!(await serapPenyimpanan(milik))) {
                return;
            }

            kabari();
        }

        if (antrean.length === 0) {
            return;
        }

        const aksi = antrean[0] as AksiAntrean;
        let respons: Response | null;

        try {
            respons = await fetch(aksi.url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': tokenXsrf() },
                body: JSON.stringify(aksi.isi),
            });
        } catch {
            respons = null;
        }

        if (milik !== generasi) {
            return;
        }

        if (respons === null) {
            gagal = 'sementara';

            return;
        }

        if (respons.status === 401 || respons.status === 419) {
            pesan = 'Sesi Anda berakhir. Masuk lagi supaya aksi yang menunggu terkirim.';
            gagal = 'sesi';

            return;
        }

        if (respons.status === 429 || respons.status >= 500) {
            gagal = 'sementara';

            return;
        }

        const ditolak = respons.ok ? null : await pesanDari(respons);

        if (milik !== generasi) {
            return;
        }

        pesan = ditolak;
        gagal = null;

        if (typeof aksi.id === 'string') {
            dijawab.add(aksi.id);
        }

        buang(aksi);
        await penyimpanan.simpan(antrean);
        kabari();
    }
}

function buang(aksi: AksiAntrean): void {
    const letak = antrean.findIndex((a) => a === aksi || (typeof aksi.id === 'string' && a.id === aksi.id));

    if (letak !== -1) {
        antrean.splice(letak, 1);
    }
}
