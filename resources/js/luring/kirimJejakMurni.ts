/**
 * Keputusan pengirim jejak pendakian (ruling W6-P2, W6-P2a sampai W6-P2h, W6-11e). Tanpa impor
 * nilai (ruling L3): PengirimJejakTest menjalankannya di Node. kirimJejak.ts menyambungkannya ke
 * Inertia, fetch, dan perekam jejak (jejak.js).
 *
 * Titik direkam di perangkat selama pendakian dan dikirim sesudah pendakian selesai atau
 * dibatalkan, saat ada sinyal. Untuk setiap sesi yang punya titik, pengirim bertanya lebih dulu
 * ke server apakah sesi itu boleh menerima (pertanyaan tanpa lokasi), dan tidak mengirim satu
 * titik pun sebelum jawabannya `kirim: true`. Basis data milik pengguna dibaca lebih dulu, lalu
 * basis data lama tanpa pemilik (W6-11e): titik sesi akun lain di sana dijawab 403 dan tidak
 * pernah diunggah.
 *
 * Titik dihapus dari perangkat hanya sesudah server menerima kelompoknya (2xx) dan transaksi
 * hapusnya selesai. Titik yang tidak akan pernah diterima server (W6-P2e: lintang atau bujur di
 * luar batas, akurasi di atas 10.000 m, waktu yang tidak terbaca) tidak dikirim, lalu dihapus
 * bersama kelompoknya sesudah kelompok itu diterima; kelompok yang seluruhnya tak sah dihapus
 * tanpa unggahan. Ketinggian di luar rentang dikirim null, tidak dijepit (R-007).
 */

/** Titik paling banyak per unggahan; server menerima paling banyak 5.000 (HikeTrackController::MAKS_TITIK). */
export const BATAS_TITIK = 500;

/**
 * Batas nilai yang diterima server (validasi HikeTrackController dan konstanta PenerimaanJejak),
 * disalin karena modul ini tidak boleh mengimpor; PengirimJejakTest memeriksa salinannya.
 */
export const BATAS_NILAI = { lintang: 90, bujur: 180, akurasiMaksM: 10000, tinggiMinM: -500, tinggiMaksM: 9000 } as const;

/** Lama keputusan "belum sekarang" diingat di tab ini: kirim false dan 409 (rancangan W6-P2 tabel 2.4). */
export const INGAT_SEMENTARA_MS = 5 * 60 * 1000;

/** Jeda sesudah 429 bila Retry-After tidak terbaca. */
export const JEDA_429_BAWAAN_MS = 60 * 1000;

/** Titik seperti disimpan perekam (jejak.js). */
export interface TitikPerangkat {
    sesi: number;
    recorded_at: string;
    latitude: unknown;
    longitude: unknown;
    elevation_m: unknown;
    accuracy_m: unknown;
}

/** Titik seperti dikirim ke server. */
export interface TitikKirim {
    recorded_at: string;
    latitude: number;
    longitude: number;
    elevation_m: number | null;
    accuracy_m: number | null;
}

export type KunciTitik = [number, string];

function angkaHingga(nilai: unknown): nilai is number {
    return typeof nilai === 'number' && Number.isFinite(nilai);
}

function bulatDalam(nilai: unknown, bawah: number, atas: number): number | null {
    return angkaHingga(nilai) && Number.isInteger(nilai) && nilai >= bawah && nilai <= atas ? nilai : null;
}

/**
 * Satu kelompok dari perangkat menjadi isi unggahan dan kunci yang dihapus sesudah diterima
 * (ruling W6-P2e). `kunci` memuat SEMUA titik kelompok, termasuk yang tak sah.
 */
export function rapikanKelompok(titik: TitikPerangkat[]): { kirim: TitikKirim[]; kunci: KunciTitik[] } {
    const kirim: TitikKirim[] = [];

    for (const t of titik) {
        const sahWaktu = typeof t.recorded_at === 'string' && Number.isFinite(Date.parse(t.recorded_at));
        const sahLetak = angkaHingga(t.latitude) && Math.abs(t.latitude) <= BATAS_NILAI.lintang && angkaHingga(t.longitude) && Math.abs(t.longitude) <= BATAS_NILAI.bujur;
        const akurasi = t.accuracy_m === null ? null : bulatDalam(t.accuracy_m, 0, BATAS_NILAI.akurasiMaksM);
        const sahAkurasi = t.accuracy_m === null || akurasi !== null;

        if (!sahWaktu || !sahLetak || !sahAkurasi) {
            continue;
        }

        kirim.push({
            recorded_at: t.recorded_at,
            latitude: t.latitude as number,
            longitude: t.longitude as number,
            elevation_m: bulatDalam(t.elevation_m, BATAS_NILAI.tinggiMinM, BATAS_NILAI.tinggiMaksM),
            accuracy_m: akurasi,
        });
    }

    return { kirim, kunci: titik.map((t): KunciTitik => [t.sesi, t.recorded_at]) };
}

/** Yang menentukan apakah putaran pengirim boleh dimulai di tab ini (ruling W6-P2a, W6-11d, W6-11g). */
export interface GerbangPengirim {
    /** Halaman AppLayout terpasang. Mode lapangan dan shell luring tidak memasangnya (I6). */
    terpasang: boolean;
    /** Props `perangkat` ada: pengguna masuk di halaman daring. Shell luring merakitnya null. */
    adaPerangkat: boolean;
    /** Props `pendakianAktif` berisi: pengguna punya pendakian berlangsung. */
    pendakianAktif: boolean;
    /** navigator.onLine tidak false. */
    daring: boolean;
    /** Kunci perangkat sudah ditutup di tab ini sesudah keluar atau ganti akun. */
    kunciTertutup: boolean;
    /** Props halaman jawaban server, bukan pulihan riwayat (pelacak riwayat keluar.ts). */
    propsDariServer: boolean;
}

export function bolehMulai(g: GerbangPengirim): boolean {
    return g.terpasang && g.adaPerangkat && !g.pendakianAktif && g.daring && !g.kunciTertutup && g.propsDariServer;
}

/** Yang diperiksa sebelum setiap permintaan di tengah putaran (ruling W6-P2a, W6-P2q). */
export interface KeadaanLanjut {
    /** Halaman AppLayout masih terpasang: pindah ke mode lapangan menghentikan putaran (I6). */
    terpasang: boolean;
    /** Kunci perangkat yang dipakai putaran ini. */
    kunciPutaran: string;
    /** Kunci props halaman sekarang; berbeda berarti halaman berganti pengguna. */
    kunciHalaman: string | null;
    /** Props `pendakianAktif` halaman sekarang berisi (I1). */
    pendakianAktif: boolean;
    daring: boolean;
    kunciTertutup: boolean;
}

/**
 * Putaran yang sedang berjalan boleh mengirim permintaan berikutnya. Tanpa syarat props riwayat,
 * karena tombol Kembali tidak mengganti sesi masuk yang dipakai unggahan.
 */
export function bolehLanjut(k: KeadaanLanjut): boolean {
    return k.terpasang && k.kunciHalaman === k.kunciPutaran && !k.pendakianAktif && k.daring && !k.kunciTertutup;
}

export type Tahap = 'periksa' | 'unggah';

/**
 * - lanjut: 2xx;
 * - tahan-sementara: sesi ditahan sampai pendakian berlangsung berakhir, Profil menyimpan rekam
 *   jejak, atau INGAT_SEMENTARA_MS;
 * - tahan-tab: sesi ditahan sampai muat penuh;
 * - berhenti: putaran berhenti tanpa mengingat apa pun;
 * - jeda: putaran berhenti dan tab menunggu Retry-After.
 */
export type Tindakan = 'lanjut' | 'tahan-sementara' | 'tahan-tab' | 'berhenti' | 'jeda';

/**
 * Tabel jawaban rancangan W6-P2 bagian 2.4. `status` null berarti galat jaringan.
 * - 403 untuk pertanyaan berarti sesi bukan milik pengguna ini (basis data lama, W6-11e); untuk
 *   unggahan sesudah pertanyaan menjawab ya, perekaman baru saja dimatikan.
 * - 404 (sesi tidak ada) dan 422 (kiriman yang tetap ditolak sesudah saringan W6-P2e) tidak
 *   berubah selama tab hidup, jadi tidak diulang terus.
 * - 4xx lain juga ditahan di tab, karena mengulangnya tidak mengubah jawaban.
 */
export function tindakanUntuk(status: number | null, tahap: Tahap): Tindakan {
    if (status === null || status >= 500) {
        return 'berhenti';
    }

    if (status >= 200 && status < 300) {
        return 'lanjut';
    }

    if (status === 401 || status === 419) {
        return 'berhenti';
    }

    if (status === 429) {
        return 'jeda';
    }

    if (status === 403) {
        return tahap === 'periksa' ? 'tahan-tab' : 'tahan-sementara';
    }

    if (status === 409) {
        return 'tahan-sementara';
    }

    return status >= 400 ? 'tahan-tab' : 'berhenti';
}

/** Keputusan per sesi yang diingat di tab ini (tabel 2.4) dan sesi yang dihapus pendaki. */
export interface IngatanPengirim {
    ditahan(sesi: number, sekarang: number): boolean;
    tahanTab(sesi: number): void;
    tahanSementara(sesi: number, sekarang: number): void;
    lupakanSementara(): void;
    tandaiDihapus(sesi: number): void;
    dihapus(sesi: number): boolean;
}

export function buatIngatan(): IngatanPengirim {
    const tab = new Set<number>();
    const sementara = new Map<number, number>();
    const terhapus = new Set<number>();

    return {
        ditahan: (sesi, sekarang) => tab.has(sesi) || (sementara.get(sesi) ?? Number.NEGATIVE_INFINITY) > sekarang,
        tahanTab: (sesi) => {
            tab.add(sesi);
        },
        tahanSementara: (sesi, sekarang) => {
            sementara.set(sesi, sekarang + INGAT_SEMENTARA_MS);
        },
        lupakanSementara: () => sementara.clear(),
        tandaiDihapus: (sesi) => {
            terhapus.add(sesi);
        },
        dihapus: (sesi) => terhapus.has(sesi),
    };
}

export interface JawabanPengirim {
    /** null: galat jaringan atau jawaban yang tidak terbaca. */
    status: number | null;
    /** Pertanyaan saja: true hanya bila server menjawab `kirim: true`. */
    kirim?: boolean;
    retryAfterMs?: number | null;
}

/** Yang dibutuhkan satu putaran pengirim; kirimJejak.ts mengisinya, test memakai tiruan. */
export interface BahanAlur {
    /** Nama basis data jejak yang ADA di perangkat, milik lebih dulu, lalu lama. Tidak pernah membuat. */
    basisData(): Promise<string[]>;
    daftarSesi(nama: string): Promise<number[]>;
    /** Paling banyak `batas` titik terlama satu sesi, urut waktu. */
    bacaKelompok(nama: string, sesi: number, batas: number): Promise<TitikPerangkat[]>;
    /** Selesai sesudah transaksi hapus tersimpan. */
    hapusKunci(nama: string, kunci: KunciTitik[]): Promise<void>;
    /** Titik sesi ini yang tersisa di kedua basis data; null bila tidak terbaca. */
    sisa(sesi: number): Promise<number | null>;
    periksa(sesi: number): Promise<JawabanPengirim>;
    unggah(sesi: number, titik: TitikKirim[]): Promise<JawabanPengirim>;
    ingatan: IngatanPengirim;
    sekarang(): number;
    /** Diperiksa sebelum setiap permintaan: halaman AppLayout masih terpasang dan gerbangnya masih benar. */
    masihBoleh(): boolean;
    /** Sesudah satu kelompok selesai: titik yang diterima server dari kelompok itu dan sisanya. */
    kabar(sesi: number, sisa: number | null, diterima: number): void;
}

export type HasilPutaran = { hasil: 'selesai' } | { hasil: 'berhenti' } | { hasil: 'jeda'; ms: number };

function jeda(jawaban: JawabanPengirim): HasilPutaran {
    return { hasil: 'jeda', ms: jawaban.retryAfterMs ?? JEDA_429_BAWAAN_MS };
}

/** Mengunggah titik satu sesi dari satu basis data per kelompok; null bila putaran boleh lanjut ke sesi berikutnya. */
async function unggahSesi(bahan: BahanAlur, nama: string, sesi: number): Promise<HasilPutaran | null> {
    const dikirim = new Set<string>();

    for (;;) {
        if (!bahan.masihBoleh()) {
            return { hasil: 'berhenti' };
        }

        if (bahan.ingatan.dihapus(sesi)) {
            return null;
        }

        const kelompok = await bahan.bacaKelompok(nama, sesi, BATAS_TITIK);

        if (kelompok.length === 0) {
            return null;
        }

        // Kelompok yang sudah diproses tetapi terbaca lagi berarti penghapusannya tidak berefek;
        // berhenti, bukan mengulang kiriman yang sama tanpa akhir.
        if (kelompok.some((t) => dikirim.has(t.recorded_at))) {
            return { hasil: 'berhenti' };
        }

        const { kirim, kunci } = rapikanKelompok(kelompok);

        if (kirim.length > 0) {
            const jawaban = await bahan.unggah(sesi, kirim);
            const tindakan = tindakanUntuk(jawaban.status, 'unggah');

            if (tindakan === 'berhenti') {
                return { hasil: 'berhenti' };
            }

            if (tindakan === 'jeda') {
                return jeda(jawaban);
            }

            if (tindakan === 'tahan-tab') {
                bahan.ingatan.tahanTab(sesi);

                return null;
            }

            if (tindakan === 'tahan-sementara') {
                bahan.ingatan.tahanSementara(sesi, bahan.sekarang());

                return null;
            }
        }

        kelompok.forEach((t) => dikirim.add(t.recorded_at));
        await bahan.hapusKunci(nama, kunci);
        bahan.kabar(sesi, await bahan.sisa(sesi), kirim.length);
    }
}

/**
 * Satu putaran pengirim (rancangan W6-P2 bagian 2.3): per basis data yang ada, per sesi yang punya
 * titik, satu pertanyaan, lalu unggahan per kelompok. Sesi yang ada di kedua basis data ditanyakan
 * sekali per putaran. Tanpa basis data jejak di perangkat, tidak ada pembacaan dan tidak ada
 * permintaan jaringan.
 */
export async function alurKirimJejak(bahan: BahanAlur): Promise<HasilPutaran> {
    const disetujui = new Set<number>();

    for (const nama of await bahan.basisData()) {
        for (const sesi of await bahan.daftarSesi(nama)) {
            if (!bahan.masihBoleh()) {
                return { hasil: 'berhenti' };
            }

            if (bahan.ingatan.dihapus(sesi) || bahan.ingatan.ditahan(sesi, bahan.sekarang())) {
                continue;
            }

            if (!disetujui.has(sesi)) {
                const jawaban = await bahan.periksa(sesi);
                const tindakan = tindakanUntuk(jawaban.status, 'periksa');

                if (tindakan === 'berhenti') {
                    return { hasil: 'berhenti' };
                }

                if (tindakan === 'jeda') {
                    return jeda(jawaban);
                }

                if (tindakan === 'tahan-tab') {
                    bahan.ingatan.tahanTab(sesi);
                    continue;
                }

                if (tindakan === 'tahan-sementara' || jawaban.kirim !== true) {
                    bahan.ingatan.tahanSementara(sesi, bahan.sekarang());
                    continue;
                }

                disetujui.add(sesi);
            }

            const hasil = await unggahSesi(bahan, nama, sesi);

            if (hasil !== null) {
                return hasil;
            }
        }
    }

    return { hasil: 'selesai' };
}

export interface BahanPutaran {
    jalankan(): Promise<HasilPutaran>;
    /** Gerbang terbaru (bolehMulai, termasuk halaman yang terpasang), dibaca setiap kali mencoba. */
    gerbang(): boolean;
    sekarang(): number;
    jadwalkan(panggil: () => void, ms: number): void;
}

export interface PutaranPengirim {
    /** Memulai putaran bila gerbang benar; benar bila putaran dimulai. */
    coba(): boolean;
    /** Selesai sesudah putaran yang sedang berjalan selesai, berhasil atau gagal. */
    tunggu(): Promise<void>;
}

/**
 * Satu putaran sekaligus per tab (rancangan W6-P2 bagian 2.3). Pemicu selama putaran berjalan
 * dicatat dan menjalankan satu putaran lagi sesudahnya, dengan gerbang terbaru. Sesudah 429, tab
 * menunggu Retry-After lalu mencoba sekali (tabel 2.4); pemicu lain selama jeda diabaikan.
 */
export function buatPutaran(bahan: BahanPutaran): PutaranPengirim {
    let berjalan: Promise<void> | null = null;
    let lagi = false;
    let jedaSampai = Number.NEGATIVE_INFINITY;

    const coba = (): boolean => {
        if (!bahan.gerbang()) {
            return false;
        }

        if (berjalan !== null) {
            lagi = true;

            return false;
        }

        if (bahan.sekarang() < jedaSampai) {
            return false;
        }

        lagi = false;
        berjalan = bahan
            .jalankan()
            .then(
                (hasil) => {
                    if (hasil.hasil === 'jeda') {
                        jedaSampai = bahan.sekarang() + hasil.ms;
                        lagi = false;
                        bahan.jadwalkan(() => {
                            jedaSampai = Number.NEGATIVE_INFINITY;
                            coba();
                        }, hasil.ms);
                    }
                },
                () => undefined,
            )
            .finally(() => {
                berjalan = null;

                if (lagi) {
                    lagi = false;
                    coba();
                }
            });

        return true;
    };

    return { coba, tunggu: () => berjalan ?? Promise.resolve() };
}

/**
 * Hasil "Hapus jejak ini" di Memount (ruling W6-P2o). Hapus di Memount selalu dicoba sesudah hapus di
 * perangkat berhasil, karena jawaban unggahan yang hilang tidak membuktikan server gagal menyimpan
 * (butir 2). Kegagalan dibedakan menurut apakah titik perangkat baru saja dihapus.
 */
export type HasilHapusJejak = 'terhapus' | 'gagal-sesudah-perangkat' | 'gagal-tanpa-perangkat';

export function hasilHapusJejak(berhasil: boolean, titikPerangkatDihapus: boolean): HasilHapusJejak {
    if (berhasil) {
        return 'terhapus';
    }

    return titikPerangkatDihapus ? 'gagal-sesudah-perangkat' : 'gagal-tanpa-perangkat';
}

/** Keadaan baris perangkat di ringkasan pendakian (ruling W6-P2h). */
export interface KeadaanJejakPerangkat {
    /** Titik sesi ini di perangkat; null bila tidak dapat dibaca. */
    jumlah: number | null;
    /** Perekaman jejak pengguna menyala (props ringkasan). */
    rekamJejak: boolean;
    /** Pengguna punya pendakian lain yang berlangsung (props pendakianAktif). */
    pendakianLain: boolean;
    /** Ada titik sesi ini yang diterima server sejak halaman ini dibuka. */
    diterimaDiHalaman: boolean;
    /** Ringkasan sudah dimuat ulang lewat "Tampilkan jejak". */
    dimuatUlang: boolean;
    /** Props `jejak` berisi: peta jejak tampil (ruling W6-P2p). */
    jejakTampil: boolean;
    /** Hasil "Hapus jejak ini" di Memount di halaman ini; null bila belum dihapus. */
    hapus: HasilHapusJejak | null;
    /**
     * Kiriman DELETE terakhir dijawab 401 atau 419 (ruling W6-FR3/N-3, temuan pemeriksa
     * final-fix-check). Hanya berarti bila `hapus` salah satu dari dua nilai gagal: menunggu
     * sinyal tidak menolong sesi yang sudah berakhir, jadi kalimatnya mengajak masuk lagi,
     * bukan mengajak mengetuk lagi "saat ada sinyal" padahal sinyal sudah ada.
     */
    sesiBerakhir: boolean;
    /** Penghapusan di perangkat ditolak peramban (sebagian atau seluruhnya); hapus di server tidak dijalankan. */
    gagalHapus: boolean;
}

/**
 * Kalimat baris perangkat ringkasan (rancangan W6-P2 bagian 4). Kosong bila perangkat tidak
 * memegang titik sesi ini atau titiknya tidak terbaca: baris tidak mengklaim apa pun. "Belum
 * dipastikan" karena unggahan yang jawabannya hilang dapat sudah tersimpan (butir 2).
 */
export function kalimatJejakPerangkat(k: KeadaanJejakPerangkat): string {
    if (k.gagalHapus) {
        return 'Jejak pendakian ini belum dapat dihapus seluruhnya dari perangkat ini. Coba lagi.';
    }

    if (k.hapus === 'gagal-sesudah-perangkat') {
        return k.sesiBerakhir
            ? 'Jejak pendakian ini dihapus dari perangkat ini, tetapi belum pasti terhapus dari Memount karena sesi Anda berakhir. Masuk lagi, lalu ketuk Hapus jejak ini.'
            : 'Jejak pendakian ini dihapus dari perangkat ini, tetapi belum pasti terhapus dari Memount. Ketuk Hapus jejak ini lagi saat ada sinyal.';
    }

    if (k.hapus === 'gagal-tanpa-perangkat') {
        return k.sesiBerakhir
            ? 'Jejak pendakian ini belum pasti terhapus dari Memount karena sesi Anda berakhir. Masuk lagi, lalu ketuk Hapus jejak ini.'
            : 'Jejak pendakian ini belum pasti terhapus dari Memount. Ketuk Hapus jejak ini lagi saat ada sinyal.';
    }

    // Hapus yang diterima server memakai kabar server ("Jejak pendakian dihapus."); baris ini tidak
    // mengklaim apa pun.
    if (k.hapus === 'terhapus') {
        return '';
    }

    if (k.jumlah === null) {
        return '';
    }

    if (k.jumlah > 0) {
        const titik = `${k.jumlah.toLocaleString('id-ID')} titik`;

        if (!k.rekamJejak) {
            return `${titik} jejak pendakian ini masih di perangkat ini dan tidak diterima Memount selama perekaman jejak mati. Nyalakan lagi di Profil untuk mengirimnya, atau hapus dari perangkat ini.`;
        }

        if (k.pendakianLain) {
            return `${titik} jejak pendakian ini masih di perangkat ini. Titik itu baru dikirim sesudah pendakian yang sedang berlangsung selesai atau dibatalkan.`;
        }

        return `${titik} jejak pendakian ini masih di perangkat ini dan belum dipastikan terkirim ke Memount. Pengiriman dicoba lagi saat ada sinyal.`;
    }

    // Sesudah "Tampilkan jejak" kalimatnya mengikuti props jejak yang nyata (ruling W6-P2p): varian
    // selesai tanpa peta berarti Memount memegang kurang dari dua titik.
    if (k.dimuatUlang) {
        return k.jejakTampil
            ? 'Ringkasan sudah dimuat ulang dengan rekaman jejak yang terkirim.'
            : 'Rekaman jejak pendakian ini sudah diterima Memount, tetapi kurang dari dua titik, jadi belum dapat digambar di peta.';
    }

    return k.diterimaDiHalaman ? 'Rekaman jejak pendakian ini sudah diterima Memount.' : '';
}
