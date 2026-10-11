import type * as Periksa from './periksa';
import type { ManifestPaket, RekamanBagian, RekamanPaket } from './simpan';

/**
 * Alur unduh paket lapangan dan kesiapan shell luring (spec v3 bagian 10.2; rencana induk W6
 * butir 5; ruling W6-R9, W6-8d, W6-9b, W6-9g, W6-9i, W6-9n, W6-10d, W6-10e, W6-10g; PT10-9).
 *
 * Tanpa impor nilai (ruling L3): penyimpanan, jaringan, hash, cache, dan kalimat disuntik, jadi
 * FieldPackDeviceTest menjalankan alur yang sama dengan peramban di Node dan membuktikan
 * urutannya lewat perilaku, bukan teks. paket.ts merangkainya dengan simpan.ts dan periksa.ts.
 * Keputusan pintu paket (keadaan sesudah unduh, perbarui, dan pemeriksaan ulang) juga ada di sini
 * (tinjauan Tugas 10 m-D); PintuPaket.tsx hanya menampilkan dan mengabarkannya.
 */

/** Kegagalan yang kalimatnya sudah ditulis untuk pendaki; status HTTP bila berasal dari jawaban server. */
export class GagalPaket extends Error {
    status: number | null;

    constructor(pesan: string, status: number | null = null) {
        super(pesan);
        this.name = 'GagalPaket';
        this.status = status;
    }
}

/**
 * Kegagalan karena shell luring belum tersimpan (tinjauan Tugas 10 I-2): pemanggil membedakannya
 * dari kegagalan unduhan, karena paket lama juga tidak dapat dibuka tanpa sinyal selama shell
 * belum ada, jadi tidak boleh disebut "tetap siap luring".
 */
export class GagalShell extends GagalPaket {
    hasil: Exclude<HasilShell, 'siap'>;

    constructor(pesan: string, hasil: Exclude<HasilShell, 'siap'>) {
        super(pesan);
        this.name = 'GagalShell';
        this.hasil = hasil;
    }
}

export interface KemajuanPaket {
    /** Bagian yang sudah terverifikasi dari seluruh bagian manifest. */
    selesai: number;
    jumlah: number;
    /** 'shell' sesudah semua bagian utuh, selama halaman luring disiapkan dan diperiksa (m-4). */
    tahap?: 'bagian' | 'shell';
}

/** Hasil pesan simpan-shell-paket ke service worker. */
export type HasilSiapkan = 'tersimpan' | 'gagal' | 'tanpa-sw';

/** Keadaan shell luring sesudah pemeriksaan. */
export type HasilShell = 'siap' | 'gagal' | 'tanpa-sw';

/** Batas menunggu service worker siap dan menjawab pesan (ruling W6-9g, W6-10d). */
export const BATAS_SHELL_MS = 60_000;

export const KALIMAT_DATA_BERUBAH = 'Data paket berubah saat diunduh. Coba lagi.';

export interface JawabanBagian {
    ok: boolean;
    status: number;
    text(): Promise<string>;
}

export interface AlatUnduh {
    ambilManifest(): Promise<ManifestPaket>;
    ambilBagian(url: string): Promise<JawabanBagian>;
    sha256(teks: string): Promise<string>;
    bacaBagian(tripId: number, versi: string): Promise<RekamanBagian[]>;
    tulisBagian(rekaman: RekamanBagian): Promise<void>;
    /** Menulis rekaman siap dan menghapus versi yang lebih tua dalam satu transaksi (W6-9i). */
    gantiVersi(rekaman: RekamanPaket): Promise<void>;
    pastikanShell(): Promise<HasilShell>;
    simpanKunci(): void;
    sekarang(): Date;
    periksa: Pick<typeof Periksa, 'bagianBelumUtuh' | 'kalimatBelumLengkap' | 'kalimatTidakUtuh' | 'kalimatHttp' | 'kalimatShell'>;
}

/** Hash teks yang benar-benar tersimpan, bukan field sha256 rekamannya (PT10-13). */
async function hashTersimpan(alat: AlatUnduh, tripId: number, versi: string): Promise<Array<{ nama: string; sha256: string }>> {
    const bagian = await alat.bacaBagian(tripId, versi);

    return Promise.all(bagian.map(async (b) => ({ nama: b.nama, sha256: await alat.sha256(b.teks) })));
}

/**
 * Mengunduh bagian satu versi. Null bila semua bagian tersimpan; manifest baru bila hash salah
 * karena data berubah di server (ruling W6-8d), supaya unduhan diulang sekali dengan versi itu.
 */
async function unduhBagian(manifest: ManifestPaket, alat: AlatUnduh, kabar: (k: KemajuanPaket) => void, sudahDiulang: boolean): Promise<ManifestPaket | null> {
    const { id } = manifest.trip;
    const utuh = new Map((await hashTersimpan(alat, id, manifest.versi)).map((b) => [b.nama, b.sha256]));
    let selesai = 0;

    kabar({ selesai, jumlah: manifest.bagian.length });

    for (const bagian of manifest.bagian) {
        if (utuh.get(bagian.nama) !== bagian.sha256) {
            const jawaban = await alat.ambilBagian(bagian.url);

            if (!jawaban.ok) {
                throw new GagalPaket(alat.periksa.kalimatHttp(jawaban.status), jawaban.status);
            }

            const teks = await jawaban.text();
            const sha256 = await alat.sha256(teks);

            if (sha256 !== bagian.sha256) {
                if (sudahDiulang) {
                    throw new GagalPaket(KALIMAT_DATA_BERUBAH);
                }

                const terbaru = await alat.ambilManifest();

                if (terbaru.versi === manifest.versi) {
                    throw new GagalPaket(alat.periksa.kalimatBelumLengkap([bagian.nama]));
                }

                return terbaru;
            }

            await alat.tulisBagian({ tripId: id, versi: manifest.versi, nama: bagian.nama, teks, sha256 });
        }

        selesai += 1;
        kabar({ selesai, jumlah: manifest.bagian.length });
    }

    return null;
}

/**
 * Urutannya:
 * 1. setiap bagian diunduh, di-hash, lalu disimpan; bagian yang teks tersimpannya sudah utuh
 *    dilewati, jadi unduhan yang putus dilanjutkan per bagian. Hash yang salah mengambil manifest
 *    sekali lagi: versi sama berarti kiriman rusak, versi baru berarti data berubah dan unduhan
 *    diulang sekali, dan salah lagi sesudah itu disebut "Data paket berubah saat diunduh" (W6-8d);
 * 2. setiap bagian dibaca ulang dari penyimpanan dan dicocokkan dengan manifest;
 * 3. shell luring disiapkan dan diperiksa di cache service worker (W6-9b, W6-9g);
 * 4. rekaman "Siap luring" ditulis sekali lewat gantiVersi, yang menghapus versi yang lebih tua
 *    dalam transaksi yang sama (W6-9i);
 * 5. kunci perangkat disimpan untuk shell luring.
 *
 * Kegagalan di langkah mana pun melempar GagalPaket tanpa menulis rekaman siap; versi lama tetap.
 */
export async function alurUnduhPaket(awal: ManifestPaket, alat: AlatUnduh, kabar: (k: KemajuanPaket) => void): Promise<RekamanPaket> {
    const terbaru = await unduhBagian(awal, alat, kabar, false);
    const manifest = terbaru ?? awal;

    if (terbaru !== null) {
        await unduhBagian(manifest, alat, kabar, true);
    }

    const { id } = manifest.trip;
    const kurang = alat.periksa.bagianBelumUtuh(manifest, await hashTersimpan(alat, id, manifest.versi));

    if (kurang.length > 0) {
        throw new GagalPaket(alat.periksa.kalimatTidakUtuh(kurang));
    }

    // Menunggu service worker dapat memakan waktu (ready, penyimpanan shell paket): pendaki diberi
    // tahu bahwa yang berjalan sekarang menyiapkan halaman luring (tinjauan Tugas 10 m-4).
    kabar({ selesai: manifest.bagian.length, jumlah: manifest.bagian.length, tahap: 'shell' });

    const shell = await alat.pastikanShell();

    if (shell !== 'siap') {
        throw new GagalShell(alat.periksa.kalimatShell(shell, 'unduh'), shell);
    }

    const rekaman: RekamanPaket = { tripId: id, versi: manifest.versi, manifest, siap: true, diverifikasi: alat.sekarang().toISOString() };

    await alat.gantiVersi(rekaman);
    alat.simpanKunci();

    return rekaman;
}

/** Wadah service worker yang dipakai pesan shell paket (navigator.serviceWorker di peramban). */
export type WadahSw = Pick<ServiceWorkerContainer, 'controller' | 'ready' | 'addEventListener' | 'removeEventListener'> & {
    getRegistration?: ServiceWorkerContainer['getRegistration'];
};

function sebelumBatas<T>(janji: Promise<T>, ms: number): Promise<T | null> {
    let pewaktu: ReturnType<typeof setTimeout> | undefined;
    const batas = new Promise<null>((selesai) => {
        pewaktu = setTimeout(() => selesai(null), ms);
    });

    return Promise.race([janji.catch(() => null), batas]).finally(() => clearTimeout(pewaktu));
}

/**
 * Meminta service worker menyimpan shell paket (pesan simpan-shell-paket lewat MessageChannel,
 * kontrak sisipan T9b; ruling W6-10d).
 *
 * Pesan dikirim ke registrasi aktif dari serviceWorker.ready, bukan ke controller: sesudah muat
 * ulang paksa controller bernilai null walau service worker aktif. "tanpa-sw" hanya bila API
 * tidak ada, atau ready tidak selesai dalam batasnya. Selama menunggu jawaban, controllerchange
 * (deploy yang mengganti service worker) mengirim ulang pesan tepat sekali ke controller baru,
 * karena pesan ke service worker lama hilang tanpa galat. { ok: false } dan diam sampai batas
 * berarti gagal; pemanggil tetap memeriksa cache sesudahnya (periksaShellLuring).
 */
export async function siapkanShellPaket(sw: WadahSw | undefined, batasMs: number = BATAS_SHELL_MS): Promise<HasilSiapkan> {
    if (!sw) {
        return 'tanpa-sw';
    }

    // Tanpa pendaftaran sama sekali, ready tidak pernah selesai: jawab seketika, bukan sesudah batas
    // (tinjauan Tugas 10 m-4). Pendaftaran yang masih dipasang tetap ditunggu lewat ready.
    if (typeof sw.getRegistration === 'function' && (await sw.getRegistration().catch(() => undefined)) === undefined) {
        return 'tanpa-sw';
    }

    const registrasi = await sebelumBatas(sw.ready, batasMs);
    const aktif = registrasi?.active ?? null;

    if (aktif === null) {
        return 'tanpa-sw';
    }

    return new Promise<HasilSiapkan>((selesai) => {
        const saluran: MessageChannel[] = [];
        let usai = false;
        let dikirimUlang = false;

        const akhiri = (hasil: HasilSiapkan) => {
            if (usai) {
                return;
            }

            usai = true;
            clearTimeout(pewaktu);
            sw.removeEventListener('controllerchange', gantiPengendali);
            saluran.forEach((s) => s.port1.close());
            selesai(hasil);
        };

        const kirim = (pekerja: ServiceWorker) => {
            const s = new MessageChannel();

            saluran.push(s);
            s.port1.onmessage = (event: MessageEvent) => akhiri((event.data as { ok?: unknown } | null)?.ok === true ? 'tersimpan' : 'gagal');
            pekerja.postMessage({ jenis: 'simpan-shell-paket' }, [s.port2]);
        };

        function gantiPengendali() {
            const baru = sw?.controller ?? null;

            if (dikirimUlang || baru === null) {
                return;
            }

            dikirimUlang = true;

            try {
                kirim(baru);
            } catch {
                akhiri('gagal');
            }
        }

        const pewaktu = setTimeout(() => akhiri('gagal'), batasMs);

        sw.addEventListener('controllerchange', gantiPengendali);

        try {
            kirim(aktif);
        } catch {
            akhiri('gagal');
        }
    });
}

export interface AlatShell {
    siapkan(): Promise<HasilSiapkan>;
    periksa(): Promise<boolean>;
}

/**
 * Shell siap untuk paket yang sedang diunduh (ruling W6-9b, W6-9g): pesan ke service worker lebih
 * dulu, lalu pemeriksaan cache, yang tetap wajib walau jawabannya { ok: true } (W6-10d).
 */
export async function pastikanShell(alat: AlatShell): Promise<HasilShell> {
    const kiriman = await alat.siapkan().catch((): HasilSiapkan => 'gagal');

    if (kiriman === 'tanpa-sw') {
        return 'tanpa-sw';
    }

    return (await alat.periksa().catch(() => false)) ? 'siap' : 'gagal';
}

/**
 * Penyembuhan diri shell paket (ruling W6-9n): muat daring yang melihat paket "Siap luring"
 * memeriksa cache lebih dulu, tanpa pesan bila shell utuh; bila tidak, shell disiapkan sekali lalu
 * diperiksa lagi. Tanpa permintaan ke server, hanya service worker dan cache.
 */
export async function pulihkanShell(alat: AlatShell): Promise<HasilShell> {
    if (await alat.periksa().catch(() => false)) {
        return 'siap';
    }

    return pastikanShell(alat);
}

/**
 * Menunggu service worker pengendali yang masih "activating" selesai diaktifkan (ruling W6-10g,
 * tinjauan Tugas 10 I-A). controllerchange tiba saat pekerja baru MASUK "activating", sekitar
 * satu detik sebelum handler activate selesai menghapus cache shell lama; caches.match mencari di
 * semua cache, jadi pemeriksaan pada saat itu lulus atas cache yang sebentar lagi hilang.
 *
 * Selesai seketika bila tidak ada pengendali atau pengendali tidak "activating". Selain itu
 * menunggu statechange sampai keadaannya bukan "activating" lagi ("activated", atau "redundant"
 * bila aktivasi gagal), paling lama `batasMs`; sesudah batas, pemeriksaan tetap berjalan.
 */
export async function sesudahAktif(sw: WadahSw | undefined, batasMs: number = BATAS_SHELL_MS): Promise<void> {
    const pekerja = sw?.controller ?? null;

    if (pekerja === null || pekerja.state !== 'activating') {
        return;
    }

    let saatUbah: () => void = () => undefined;
    const aktif = new Promise<void>((selesai) => {
        saatUbah = () => {
            if (pekerja.state !== 'activating') {
                selesai();
            }
        };
        pekerja.addEventListener('statechange', saatUbah);
    });

    await sebelumBatas(aktif, batasMs);
    pekerja.removeEventListener('statechange', saatUbah);
}

/**
 * pulihkanShell sesudah pengendali selesai diaktifkan (ruling W6-10g): satu pintu untuk setiap
 * pemeriksaan ulang, yaitu pendengar controllerchange yang menetap, pemeriksaan saat pintu dimuat,
 * pemeriksaan ulang aksi menurut hitungan pergantian (shellTerbaru), dan paket lama sesudah
 * perbarui. Pemulihannya tetap W6-10e: periksa, bila gagal siapkan sekali, lalu periksa lagi.
 */
export async function pulihkanShellSesudahAktif(sw: WadahSw | undefined, alat: AlatShell, batasMs: number = BATAS_SHELL_MS): Promise<HasilShell> {
    await sesudahAktif(sw, batasMs);

    return pulihkanShell(alat);
}

/**
 * Pemeriksaan ulang shell setiap kali service worker pengendali berganti (ruling W6-10e, W6-10g,
 * tinjauan Tugas 10 I-1 dan I-A). Deploy yang berbalapan dengan penyimpanan shell paket menulis
 * penanda di cache service worker LAMA; balasannya tiba sebelum pekerja baru aktif, lalu aktivasi
 * menghapus cache itu. Pendengar ini menetap selama halaman yang memegang paket "Siap luring"
 * terbuka, tidak dilepas sesudah kejadian pertama, dan setiap controllerchange menunggu pengendali
 * baru selesai diaktifkan lalu menjalankan pulihkanShell (periksa, bila gagal siapkan sekali, lalu
 * periksa lagi). `mulai` dipanggil saat kejadian, `hasil` sesudah pemeriksaannya; hasil
 * pemeriksaan yang sudah disusul kejadian lain tidak dilaporkan.
 *
 * `perlu`, bila ada, ditanya sesudah `mulai` pada setiap kejadian: false berarti tidak ada paket
 * yang perlu dijaga saat itu, jadi shell tidak diperiksa dan TIDAK disiapkan. Tanpa itu kunjungan
 * pertama ke halaman trip yang belum punya paket menyuruh service worker menyimpan shell paket
 * (dan penandanya, yang membuat setiap pemasangan berikutnya membawanya) untuk pendaki yang belum
 * mengunduh apa pun. Mengembalikan fungsi pelepas.
 */
export function pantauShellPaket(
    sw: WadahSw | undefined,
    alat: AlatShell,
    kabar: { mulai?: () => void; perlu?: () => boolean; hasil: (hasil: HasilShell) => void },
): () => void {
    if (!sw) {
        return () => undefined;
    }

    let nomor = 0;
    const saatGanti = () => {
        const ini = ++nomor;

        kabar.mulai?.();

        if (kabar.perlu && !kabar.perlu()) {
            return;
        }

        void pulihkanShellSesudahAktif(sw, alat).then((hasil) => {
            if (ini === nomor) {
                kabar.hasil(hasil);
            }
        });
    };

    sw.addEventListener('controllerchange', saatGanti);

    return () => sw.removeEventListener('controllerchange', saatGanti);
}

/** Kalimat versi yang sama saat memperbarui. */
export const KALIMAT_VERSI_TERBARU = 'Paket di perangkat ini sudah versi terbaru.';

export type KeadaanPaketLama =
    | { tahap: 'siap'; pesan: string | null }
    | { tahap: 'belumLuring'; shell: Exclude<HasilShell, 'siap'>; pesan: string | null }
    | { tahap: 'gagal'; pesan: string; sebutLama: true };

/**
 * Keadaan paket lama sesudah memperbarui yang tidak menghasilkan versi baru (tinjauan Tugas 10
 * I-2, W6-9n): `galat` null berarti versi di server sama.
 * - kegagalan dari pemeriksaan shell saat unduh (GagalShell): paket lama belum dapat dibuka tanpa
 *   sinyal, dengan hasil pemeriksaan itu;
 * - selain itu shell diperiksa SEKARANG (pulihkanShell): hanya bila lulus paket lama disebut siap
 *   ("tetap siap luring" bila perbarui gagal, "sudah versi terbaru" bila sama); bila tidak lulus,
 *   paket lama belum dapat dibuka tanpa sinyal, dengan kalimat sebabnya.
 */
export async function keadaanPaketLama(galat: unknown, pesan: string, pulihkan: () => Promise<HasilShell>): Promise<KeadaanPaketLama> {
    if (galat instanceof GagalShell) {
        return { tahap: 'belumLuring', shell: galat.hasil, pesan: null };
    }

    const shell = await pulihkan().catch((): HasilShell => 'gagal');
    const kalimat = galat === null ? KALIMAT_VERSI_TERBARU : pesan;

    if (shell === 'siap') {
        return galat === null ? { tahap: 'siap', pesan: KALIMAT_VERSI_TERBARU } : { tahap: 'gagal', pesan, sebutLama: true };
    }

    return { tahap: 'belumLuring', shell, pesan: kalimat };
}

/** Keadaan pintu paket (PintuPaket.tsx), di sini supaya keputusannya diuji di Node (tinjauan Tugas 10 m-D). */
export type KeadaanPintu =
    | { tahap: 'memeriksa' }
    | { tahap: 'kosong' }
    | { tahap: 'pratinjau'; manifest: ManifestPaket; ruang: { boleh: boolean; kalimat: string } }
    | { tahap: 'mengunduh'; kemajuan: KemajuanPaket }
    | { tahap: 'siap'; paket: RekamanPaket; menetap: boolean | null; pesan: string | null }
    | { tahap: 'belumLuring'; paket: RekamanPaket; shell: Exclude<HasilShell, 'siap'>; pesan: string | null }
    | { tahap: 'gagal'; pesan: string; lama: RekamanPaket | null; sebutLama: boolean };

/** Satu sumber keadaan yang dibaca seketika oleh pemantau dan dicerminkan ke render lewat langganan. */
export interface Toko<T> {
    ambil(): T;
    ubah(baru: T): void;
    langganan(dengar: (baru: T) => void): () => void;
}

/**
 * Toko keadaan pintu (tinjauan Tugas 10 m-D): pemantau shell membaca keadaan dari sini, bukan dari
 * salinan yang harus disinkronkan efek React. Sebelumnya salinan itu (keadaanKini) dapat dibuang
 * tanpa satu test pun merah, dan pemeriksaan ulang di pintu mati diam-diam.
 */
export function buatToko<T>(awal: T): Toko<T> {
    let kini = awal;
    const pendengar = new Set<(baru: T) => void>();

    return {
        ambil: () => kini,
        ubah: (baru) => {
            kini = baru;
            pendengar.forEach((dengar) => dengar(baru));
        },
        langganan: (dengar) => {
            pendengar.add(dengar);

            return () => {
                pendengar.delete(dengar);
            };
        },
    };
}

/**
 * Paket yang keadaannya dijaga pemeriksaan ulang shell (ruling W6-10e, W6-10g): paket "Siap
 * luring", paket yang belum dapat dibuka tanpa sinyal, dan paket lama yang disebut "tetap siap
 * luring" sesudah perbarui gagal (tinjauan Tugas 10 m-C). Null untuk keadaan lain, termasuk
 * kegagalan yang hanya menyebut paket lama tanpa klaim siap (404, sebutLama false).
 */
export function paketDijaga(keadaan: KeadaanPintu): RekamanPaket | null {
    if (keadaan.tahap === 'siap' || keadaan.tahap === 'belumLuring') {
        return keadaan.paket;
    }

    return keadaan.tahap === 'gagal' && keadaan.lama !== null && keadaan.sebutLama ? keadaan.lama : null;
}

/**
 * Keadaan pintu sesudah pemeriksaan ulang shell (ruling W6-10e, tinjauan Tugas 10 m-C): shell
 * yang tidak siap menurunkan "Siap luring" dan "tetap siap luring" menjadi belum dapat dibuka tanpa
 * sinyal (pesan perbarui yang gagal tetap disebut), dan shell yang siap menaikkan keadaan itu lagi.
 * Null bila tidak berubah atau keadaannya tidak dijaga.
 */
export function keadaanSesudahPeriksaUlang(keadaan: KeadaanPintu, shell: HasilShell): KeadaanPintu | null {
    const paket = paketDijaga(keadaan);

    if (paket === null) {
        return null;
    }

    if (shell === 'siap') {
        return keadaan.tahap === 'belumLuring' ? { tahap: 'siap', paket, menetap: null, pesan: null } : null;
    }

    return { tahap: 'belumLuring', paket, shell, pesan: keadaan.tahap === 'gagal' ? keadaan.pesan : null };
}

/**
 * Kabar pemantau shell untuk pintu paket (ruling W6-10e, W6-10g, tinjauan Tugas 10 m-C dan m-D).
 * Setiap pergantian pengendali dihitung (`efek.ganti`, untuk shellTerbaru di aksi yang sedang
 * berjalan). Shell hanya diperiksa dan disiapkan bila pintu memegang paket yang dijaga dan tidak ada
 * aksi pendaki yang berjalan: tanpa paket, shell paket tidak disimpan untuk pendaki yang belum
 * mengunduh; selama aksi, aksi itu memeriksa ulang sendiri. Keadaan baru ditulis ke toko lalu
 * dikabarkan lewat `efek.ubah` (fokus dan wilayah status).
 */
export function kabarPantauPintu(
    toko: Toko<KeadaanPintu>,
    berjalan: () => boolean,
    efek: { ganti: () => void; memeriksa: (sedang: boolean) => void; ubah: (baru: KeadaanPintu) => void },
): { mulai: () => void; perlu: () => boolean; hasil: (hasil: HasilShell) => void } {
    const dijaga = () => !berjalan() && paketDijaga(toko.ambil()) !== null;

    return {
        mulai: () => {
            efek.ganti();

            if (dijaga()) {
                efek.memeriksa(true);
            }
        },
        perlu: dijaga,
        hasil: (shell) => {
            if (!dijaga()) {
                return;
            }

            efek.memeriksa(false);

            const baru = keadaanSesudahPeriksaUlang(toko.ambil(), shell);

            if (baru !== null) {
                toko.ubah(baru);
                efek.ubah(baru);
            }
        },
    };
}

/**
 * Shell terbaru sebelum keadaan siap ditulis (ruling W6-10e, W6-10g): selama pengendali berganti
 * sejak pemeriksaan terakhir (`gantiKe` berbeda dari `awal`), shell yang siap diperiksa ulang lewat
 * `pulihkan`, yang menunggu pengendali baru selesai diaktifkan. Diulang bila pengendali berganti
 * lagi selama pemeriksaan itu.
 */
export async function shellTerbaru(shell: HasilShell, awal: number, gantiKe: () => number, pulihkan: () => Promise<HasilShell>): Promise<HasilShell> {
    let hasil = shell;
    let dari = awal;

    while (hasil === 'siap' && gantiKe() !== dari) {
        dari = gantiKe();
        hasil = await pulihkan().catch((): HasilShell => 'gagal');
    }

    return hasil;
}

/**
 * Keadaan pintu untuk paket yang shell-nya baru diperiksa: sesudah unduhan yang lolos, saat pintu
 * dimuat, dan sesudah "Siapkan ulang". Siap hanya bila shell terbaru siap (shellTerbaru).
 */
export async function keadaanPaketDiperiksa(
    paket: RekamanPaket,
    menetap: boolean | null,
    shell: HasilShell,
    awal: number,
    gantiKe: () => number,
    pulihkan: () => Promise<HasilShell>,
): Promise<KeadaanPintu> {
    const akhir = await shellTerbaru(shell, awal, gantiKe, pulihkan);

    return akhir === 'siap' ? { tahap: 'siap', paket, menetap, pesan: null } : { tahap: 'belumLuring', paket, shell: akhir, pesan: null };
}

/**
 * Keadaan pintu untuk paket lama sesudah perbarui yang tidak menghasilkan versi baru (tinjauan
 * Tugas 10 I-2 dan m-D): keadaanPaketLama, lalu shell yang siap diperiksa ulang bila pengendali
 * berganti selama itu. Belum dapat dibuka tanpa sinyal bila shell tidak siap; "tetap siap luring"
 * (gagal, sebutLama) atau siap dengan "sudah versi terbaru" hanya bila shell terbaru siap.
 */
export async function keadaanPintuLama(
    galat: unknown,
    pesan: string,
    lama: RekamanPaket,
    menetap: boolean | null,
    gantiKe: () => number,
    pulihkan: () => Promise<HasilShell>,
): Promise<KeadaanPintu> {
    const awal = gantiKe();
    const hasil = await keadaanPaketLama(galat, pesan, pulihkan);
    const shell = hasil.tahap === 'belumLuring' ? hasil.shell : await shellTerbaru('siap', awal, gantiKe, pulihkan);

    if (shell !== 'siap') {
        return { tahap: 'belumLuring', paket: lama, shell, pesan: hasil.pesan };
    }

    return hasil.tahap === 'gagal' ? { tahap: 'gagal', pesan: hasil.pesan, lama, sebutLama: true } : { tahap: 'siap', paket: lama, menetap, pesan: hasil.pesan };
}
