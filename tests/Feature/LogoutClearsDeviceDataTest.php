<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Keluar, berganti akun, dan menghapus akun membersihkan data Memount di perangkat (spec v3
 * bagian 10.6; rencana induk W6 butir 7; ruling W6-R10 dan W6-11a sampai W6-11e).
 *
 * Saat keluar, halaman menghitung yang belum terkirim (titik jejak pengguna ini, titik basis data
 * jejak lama, dan catatan tiba atau jeda yang menunggu) dan bertanya lebih dulu. Sesudah itu ia
 * menghapus basis data paket dan jejak, antrean aksi, kunci perangkat, cache pack-*, penanda shell
 * paket, dan OPFS, baru mengakhiri sesi. Respons logout dan hapus akun mengirim Clear-Site-Data
 * sebagai lapis kedua.
 *
 * Diubah dari brief rencana (PT11-5, PT11-2). Asersi urutan teks keluar.ts (brief T11 :66-83) dan
 * asersi teks penjaga ganti akun (T11 :111-112) tetap hijau walau Batal tetap menghapus, walau
 * pertanyaan tidak pernah muncul, atau walau semua pengguna kembali berbagi satu basis data jejak
 * (mutan K1 sampai K8 pra-tinjau). Keputusan keluar, kalimatnya, pemilihan data yang dihapus, dan
 * perekam jejak kini diuji perilakunya di Node atas modul tanpa impor nilai (ruling L3):
 * keluarMurni.ts, jejak.js, dan simpan.ts dengan indexedDB tiruan. Sambungan React dijaga asersi
 * sumber seperlunya, dan asersi pintu Keluar di MenuAkun dan PintuAkun tetap. Perilaku dengan
 * IndexedDB sungguhan dibuktikan di peramban (laporan Tugas 11) dan diuji lagi di tugas verifikasi
 * akhir (Q17).
 */
class LogoutClearsDeviceDataTest extends TestCase
{
    use RefreshDatabase;
    use SumberReact;

    /**
     * Diubah ruling W6-FC: "supaya catatan itu dapat terkirim" salah untuk catatan pendakian yang
     * sudah diselesaikan (dijawab 409), jadi Batal kini menyebut pendakian yang masih berlangsung.
     */
    private const PENUTUP_ADA = 'Keluar akan menghapus semuanya dari perangkat ini, dan itu tidak dapat dipulihkan. Pilih Batal untuk tetap masuk, supaya jejak dan catatan pendakian yang masih berlangsung sempat terkirim.';

    public function test_logout_asks_the_browser_to_clear_this_origins_storage(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect('/')
            ->assertHeader('Clear-Site-Data', '"storage"');

        $this->assertGuest();
    }

    /**
     * Kalimat pertanyaan keluar per cabang (ruling W6-11a, PT11-1, PT11-8). Kalimatnya tidak
     * menjanjikan jalan kirim tertentu, jadi benar sebelum dan sesudah sisipan W6-P2. Hitungan yang
     * gagal tetap bertanya, dan angka ditulis dengan pemisah ribuan Indonesia. Kegagalan rute logout
     * (W6-11c) dan penghapusan yang tertunda tab lain punya kalimatnya sendiri. Bila sebagian
     * penghapusan belum selesai, kalimat kegagalan tidak menyebut data sudah dihapus.
     *
     * Putaran perbaikan 1: kalimat HTTP diamandemen ruling W6-11h ("jawaban server tidak dapat
     * dibaca"), karena "server tidak menerima" salah bila keluar diterima lalu halaman tujuan 5xx
     * (tinjauan Tugas 11 m-6). Cabang baru "dihentikan" untuk kunjungan lain yang membatalkan
     * permintaan keluar (m-3).
     */
    public function test_the_sign_out_question_names_what_has_not_been_sent(): void
    {
        $hasil = $this->node(['murni' => 'luring/keluarMurni.ts'], <<<'JS'
            const k = murni.kalimatKeluar;
            process.stdout.write(JSON.stringify({
                kosong: k({ milik: 0, lama: 0, aksi: 0 }),
                milik: k({ milik: 3, lama: 0, aksi: 0 }),
                aksi: k({ milik: 0, lama: 0, aksi: 2 }),
                lama: k({ milik: 0, lama: 1234, aksi: 0 }),
                milikLama: k({ milik: 1, lama: 7, aksi: 0 }),
                campuran: k({ milik: 12, lama: 5, aksi: 1 }),
                gagal: k(null),
                tertunda: murni.KALIMAT_TERTUNDA,
                jaringan: murni.kalimatGagalKeluar('jaringan', 'terhapus'),
                http: murni.kalimatGagalKeluar('http', 'terhapus'),
                jaringanSisa: murni.kalimatGagalKeluar('jaringan', 'tertunda'),
                httpSisa: murni.kalimatGagalKeluar('http', 'gagal'),
                dihentikan: murni.kalimatGagalKeluar('dihentikan', 'terhapus'),
                dihentikanSisa: murni.kalimatGagalKeluar('dihentikan', 'tertunda'),
            }));
            JS);

        $this->assertNull($hasil['kosong'], 'Tanpa data yang belum terkirim, keluar tidak bertanya.');
        $this->assertSame('Ada 3 titik jejak pendakian Anda yang belum terkirim di perangkat ini. '.self::PENUTUP_ADA, $hasil['milik']);
        $this->assertSame('Ada 2 catatan tiba di pos atau jeda yang belum terkirim. '.self::PENUTUP_ADA, $hasil['aksi']);
        $this->assertSame('Ada 1.234 titik jejak dari sebelum pembaruan aplikasi, tersimpan tanpa tanda pemilik. Keluar akan menghapusnya dari perangkat ini.', $hasil['lama']);
        $this->assertSame('Ada 1 titik jejak pendakian Anda yang belum terkirim di perangkat ini. Ada 7 titik jejak dari sebelum pembaruan aplikasi, tersimpan tanpa tanda pemilik. '.self::PENUTUP_ADA, $hasil['milikLama']);
        $this->assertSame(
            'Ada 12 titik jejak pendakian Anda yang belum terkirim di perangkat ini. Ada 1 catatan tiba di pos atau jeda yang belum terkirim. Ada 5 titik jejak dari sebelum pembaruan aplikasi, tersimpan tanpa tanda pemilik. '.self::PENUTUP_ADA,
            $hasil['campuran'],
        );
        $this->assertSame('Memount tidak dapat memeriksa jejak pendakian dan catatan yang belum terkirim di perangkat ini. Keluar akan menghapus semua data Memount di perangkat ini, termasuk yang mungkin belum terkirim. Lanjutkan keluar?', $hasil['gagal']);
        $this->assertSame('Data Memount di perangkat ini sedang dipakai tab Memount lain, jadi sebagian baru terhapus sesudah tab itu ditutup. Tutup tab Memount lain, lalu pilih OK untuk keluar. Pilih Batal untuk tetap masuk.', $hasil['tertunda']);
        $this->assertSame('Belum pasti keluar: sambungan putus. Data Memount di perangkat ini sudah dihapus. Ketuk Keluar lagi saat ada sinyal.', $hasil['jaringan']);
        $this->assertSame('Belum pasti keluar: jawaban server tidak dapat dibaca. Data Memount di perangkat ini sudah dihapus. Muat ulang halaman, lalu ketuk Keluar lagi.', $hasil['http']);
        $this->assertSame('Belum pasti keluar: sambungan putus. Sebagian data Memount di perangkat ini belum terhapus. Ketuk Keluar lagi saat ada sinyal.', $hasil['jaringanSisa']);
        $this->assertSame('Belum pasti keluar: jawaban server tidak dapat dibaca. Sebagian data Memount di perangkat ini belum terhapus. Muat ulang halaman, lalu ketuk Keluar lagi.', $hasil['httpSisa']);
        $this->assertSame('Belum pasti keluar: halaman berpindah sebelum jawaban server tiba. Data Memount di perangkat ini sudah dihapus. Ketuk Keluar lagi.', $hasil['dihentikan']);
        $this->assertSame('Belum pasti keluar: halaman berpindah sebelum jawaban server tiba. Sebagian data Memount di perangkat ini belum terhapus. Ketuk Keluar lagi.', $hasil['dihentikanSisa']);
    }

    /**
     * Urutan keluar (ruling W6-R10, W6-11c), satu kasus per cabang. Pertanyaan lebih dulu bila ada
     * yang belum terkirim atau hitungannya gagal. Batal tidak menghapus apa pun dan tidak
     * mengakhiri sesi. Penghapusan selalu sebelum sesi diakhiri, dan penghapusan yang tertunda tab
     * lain ditanyakan sebelum sesi diakhiri.
     *
     * Putaran perbaikan 1 (tinjauan Tugas 11 m-1): hitungan yang tidak pernah selesai, misalnya
     * membuka basis data jejak yang penghapusannya masih ditahan tab lain, dibatasi tiga detik lalu
     * dianggap tidak diketahui, jadi pendaki membaca pertanyaan "tidak dapat memeriksa".
     */
    public function test_signing_out_asks_before_clearing_and_clears_before_ending_the_session(): void
    {
        $hasil = $this->node(['murni' => 'luring/keluarMurni.ts'], <<<'JS'
            const jenis = (kalimat, hitungan) =>
                kalimat === murni.KALIMAT_TERTUNDA ? 'tertunda' : kalimat === murni.kalimatKeluar(null) ? 'gagal' : kalimat === murni.kalimatKeluar(hitungan) ? 'data' : 'lain';
            const kasus = async (hitungan, jawaban, bersih) => {
                const catatan = [];
                let ke = 0;
                const hasil = await murni.alurKeluar({
                    hitung: async () => {
                        catatan.push('hitung');
                        if (hitungan === 'lempar') throw new Error('hitung gagal');
                        return hitungan;
                    },
                    tanya: (kalimat) => {
                        catatan.push('tanya:' + jenis(kalimat, hitungan));
                        return jawaban[ke++];
                    },
                    bersihkan: async () => {
                        catatan.push('bersihkan');
                        if (bersih === 'lempar') throw new Error('hapus gagal');
                        return bersih;
                    },
                    akhiri: async (h) => {
                        catatan.push('akhiri:' + h);
                    },
                });
                return { hasil, catatan };
            };
            const nol = { milik: 0, lama: 0, aksi: 0 };
            const ada = { milik: 2, lama: 0, aksi: 0 };
            process.stdout.write(JSON.stringify({
                tanpaData: await kasus(nol, [], 'terhapus'),
                batal: await kasus(ada, [false], 'terhapus'),
                setuju: await kasus(ada, [true], 'terhapus'),
                hitungMelempar: await kasus('lempar', [true], 'terhapus'),
                hitungNullBatal: await kasus(null, [false], 'terhapus'),
                tertundaBatal: await kasus(nol, [false], 'tertunda'),
                tertundaSetuju: await kasus(nol, [true], 'tertunda'),
                bersihMelempar: await kasus(nol, [], 'lempar'),
                menggantung: await (async () => {
                    const pewaktu = [];
                    globalThis.setTimeout = (f, ms) => { pewaktu.push({ f, ms }); return pewaktu.length; };
                    globalThis.clearTimeout = () => {};
                    const catatan = [];
                    const janji = murni.alurKeluar({
                        hitung: () => { catatan.push('hitung'); return new Promise(() => {}); },
                        tanya: (kalimat) => { catatan.push('tanya:' + jenis(kalimat, null)); return false; },
                        bersihkan: async () => { catatan.push('bersihkan'); return 'terhapus'; },
                        akhiri: async () => { catatan.push('akhiri'); },
                    });
                    await new Promise((r) => setImmediate(r));
                    const sebelumBatas = catatan.slice();
                    const batas = pewaktu.map((p) => p.ms);
                    pewaktu.splice(0).forEach((p) => p.f());
                    return { hasil: await janji, sebelumBatas, batas, catatan };
                })(),
            }));
            JS);

        $this->assertSame(['hasil' => 'diakhiri', 'catatan' => ['hitung', 'bersihkan', 'akhiri:terhapus']], $hasil['tanpaData']);
        $this->assertSame(['hasil' => 'batal', 'catatan' => ['hitung', 'tanya:data']], $hasil['batal'], 'Batal tidak menghapus apa pun dan tidak mengakhiri sesi.');
        $this->assertSame(['hasil' => 'diakhiri', 'catatan' => ['hitung', 'tanya:data', 'bersihkan', 'akhiri:terhapus']], $hasil['setuju']);
        $this->assertSame(['hasil' => 'diakhiri', 'catatan' => ['hitung', 'tanya:gagal', 'bersihkan', 'akhiri:terhapus']], $hasil['hitungMelempar'], 'Hitungan yang gagal tetap bertanya (PT11-8).');
        $this->assertSame(['hasil' => 'batal', 'catatan' => ['hitung', 'tanya:gagal']], $hasil['hitungNullBatal']);
        $this->assertSame(['hasil' => 'batal-tertunda', 'catatan' => ['hitung', 'bersihkan', 'tanya:tertunda']], $hasil['tertundaBatal']);
        $this->assertSame(['hasil' => 'diakhiri', 'catatan' => ['hitung', 'bersihkan', 'tanya:tertunda', 'akhiri:tertunda']], $hasil['tertundaSetuju']);
        $this->assertSame(['hasil' => 'diakhiri', 'catatan' => ['hitung', 'bersihkan', 'akhiri:gagal']], $hasil['bersihMelempar'], 'Penghapusan yang melempar tetap mengakhiri sesi; Clear-Site-Data menjadi lapis kedua.');
        $this->assertSame(
            ['hasil' => 'batal', 'sebelumBatas' => ['hitung'], 'batas' => [3000], 'catatan' => ['hitung', 'tanya:gagal']],
            $hasil['menggantung'],
            'Hitungan yang menggantung dibatasi tiga detik, lalu pendaki ditanya "tidak dapat memeriksa".',
        );
    }

    /**
     * Yang dihitung pertanyaan keluar (ruling W6-11a): titik jejak milik pengguna ini dan titik
     * basis data lama, ditambah catatan antrean di basis data paket dan di sessionStorage tab ini,
     * digabung menurut id supaya satu aksi tidak terhitung dua kali. Satu hitungan yang tidak
     * diketahui membuat seluruhnya tidak diketahui.
     */
    public function test_the_question_counts_both_track_stores_and_the_waiting_actions_once(): void
    {
        $hasil = $this->node(['murni' => 'luring/keluarMurni.ts', 'antrean' => 'components/mendaki/antrean.ts'], <<<'JS'
            const aksi = (id) => ({ id, url: '/trips/1/hike/tiba', isi: { urutan: 1 }, dibuat: 1 });
            const jalankan = async (kunci, titik, paket, tab) => {
                const diminta = [];
                const hasil = await murni.hitungDengan({
                    jumlahTitik: async (nama) => { diminta.push(nama); return nama in titik ? titik[nama] : 0; },
                    aksiDiPaket: async (k) => { diminta.push('paket:' + k); return paket; },
                    aksiDiTab: async (k) => { diminta.push('tab:' + k); return tab; },
                    gabungAntrean: antrean.gabungAntrean,
                }, kunci);
                return { hasil, diminta: diminta.sort() };
            };
            process.stdout.write(JSON.stringify({
                lengkap: await jalankan('k1', { 'jejak-pendakian-k1': 4, 'jejak-pendakian': 2 }, [aksi('a1'), aksi('a2')], [aksi('a2'), aksi('a3'), null]),
                tidakDiketahui: await jalankan('k1', { 'jejak-pendakian-k1': null }, [], []),
                tanpaKunci: await jalankan(null, { 'jejak-pendakian': 1 }, [aksi('x')], [aksi('y')]),
                paketTidakDiketahui: await jalankan('k1', {}, null, [aksi('a1')]),
            }));
            JS);

        $this->assertSame(['milik' => 4, 'lama' => 2, 'aksi' => 3], $hasil['lengkap']['hasil']);
        $this->assertSame(['jejak-pendakian', 'jejak-pendakian-k1', 'paket:k1', 'tab:k1'], $hasil['lengkap']['diminta']);
        $this->assertNull($hasil['tidakDiketahui']['hasil']);
        $this->assertSame(['milik' => 0, 'lama' => 1, 'aksi' => 0], $hasil['tanpaKunci']['hasil']);
        $this->assertSame(['jejak-pendakian'], $hasil['tanpaKunci']['diminta']);
        $this->assertNull($hasil['paketTidakDiketahui']['hasil'], 'Antrean paket yang tidak dapat diperiksa membuat hitungan tidak diketahui.');
    }

    /**
     * Antrean basis data paket dibaca menurut nama (ruling W6-11g bagian m-2). Percobaan keluar
     * ulang di tab yang sama tetap menghitungnya, walau kuncinya sudah ditutup percobaan pertama
     * (W6-11d). Basis data tidak pernah dibuat untuk dihitung. Tanpa indexedDB.databases(),
     * keberadaannya hanya diketahui dari kunci tersimpan. Kunci tertutup tanpa kunci tersimpan
     * tidak dapat diperiksa (null).
     */
    public function test_a_retried_sign_out_still_counts_the_actions_in_the_pack_store(): void
    {
        $hasil = $this->node(['murni' => 'luring/keluarMurni.ts'], <<<'JS'
            const jalankan = async ({ daftar, tersimpan, tertutup, kunci }) => {
                const dibaca = [];
                const hasil = await murni.aksiDiPaketDengan({
                    daftarBasisData: async () => daftar,
                    kunciTersimpan: () => tersimpan,
                    kunciTertutup: (k) => tertutup.includes(k),
                    bacaMenurutNama: async (nama) => { dibaca.push(nama); return ['m1', 'm2']; },
                }, kunci);
                return { hasil, dibaca };
            };
            process.stdout.write(JSON.stringify({
                tertutupAda: await jalankan({ daftar: ['memount-luring-k1'], tersimpan: null, tertutup: ['k1'], kunci: 'k1' }),
                tidakAda: await jalankan({ daftar: ['memount-luring-k2'], tersimpan: 'k1', tertutup: [], kunci: 'k1' }),
                tanpaDaftarTersimpan: await jalankan({ daftar: null, tersimpan: 'k1', tertutup: ['k1'], kunci: 'k1' }),
                tanpaDaftarTertutup: await jalankan({ daftar: null, tersimpan: null, tertutup: ['k1'], kunci: 'k1' }),
                tanpaDaftarTanpaPaket: await jalankan({ daftar: null, tersimpan: 'k2', tertutup: [], kunci: 'k1' }),
            }));
            JS);

        $this->assertSame(['hasil' => ['m1', 'm2'], 'dibaca' => ['memount-luring-k1']], $hasil['tertutupAda'], 'Kunci yang sudah ditutup tetap dihitung menurut nama.');
        $this->assertSame(['hasil' => [], 'dibaca' => []], $hasil['tidakAda'], 'Basis data yang tidak ada tidak dibuka.');
        $this->assertSame(['hasil' => ['m1', 'm2'], 'dibaca' => ['memount-luring-k1']], $hasil['tanpaDaftarTersimpan']);
        $this->assertSame(['hasil' => null, 'dibaca' => []], $hasil['tanpaDaftarTertutup']);
        $this->assertSame(['hasil' => [], 'dibaca' => []], $hasil['tanpaDaftarTanpaPaket']);
    }

    /**
     * Ketukan kedua selama keluar berjalan diabaikan (PT11-10): satu pertanyaan, satu pembersihan,
     * dan satu permintaan keluar. Pintunya terbuka lagi sesudah selesai, batal, atau gagal.
     */
    public function test_a_second_tap_while_signing_out_is_ignored(): void
    {
        $hasil = $this->node(['murni' => 'luring/keluarMurni.ts'], <<<'JS'
            let mulai = 0;
            let lepas = () => {};
            const pintu = murni.sekaliJalan(async (x) => {
                mulai++;
                await new Promise((r) => { lepas = r; });
                if (x === 'lempar') throw new Error('gagal');
            });
            const pertama = pintu.jalankan('a');
            const kedua = await pintu.jalankan('b');
            const selama = pintu.berjalan();
            lepas();
            const hasilPertama = await pertama;
            const sesudah = pintu.berjalan();
            const ketiga = pintu.jalankan('lempar');
            lepas();
            let galat = null;
            try { await ketiga; } catch (e) { galat = e.message; }
            process.stdout.write(JSON.stringify({ mulai, kedua, selama, hasilPertama, sesudah, galat, sesudahGagal: pintu.berjalan() }));
            JS);

        $this->assertSame(['mulai' => 2, 'kedua' => false, 'selama' => true, 'hasilPertama' => true, 'sesudah' => false, 'galat' => 'gagal', 'sesudahGagal' => false], $hasil);
    }

    /**
     * Pengendali keluar dengan router Inertia tiruan (tinjauan Tugas 11 m-3, m-4, m-5; ruling W6-11c,
     * W6-11h). Setiap cabang jawaban rute keluar menyelesaikan penantian, dan `sibuk` kembali salah.
     * Galat jaringan dan HTTP menulis kalimatnya dan mengembalikan false (tanpa pesan jaringan umum
     * dan dialog galat Inertia); kalimat itu hilang pada perpindahan halaman berikutnya. Pembatalan
     * oleh kunjungan lain menulis kalimat "halaman berpindah" yang bertahan melewati perpindahan
     * pembatal itu, lalu diganti percobaan berikutnya. Kalimat dan sibuk hanya terlihat oleh tombol
     * yang memulai keluar.
     */
    public function test_every_answer_of_the_sign_out_route_ends_with_the_right_sentence_at_its_own_button(): void
    {
        $hasil = $this->node(['murni' => 'luring/keluarMurni.ts'], <<<'JS'
            const buatRouter = (jawaban) => {
                const navigasi = new Set();
                const router = {
                    dikirim: [],
                    kembali: [],
                    pindah: () => [...navigasi].forEach((f) => f()),
                    post: (url, data, opsi) => {
                        router.dikirim.push(url);
                        queueMicrotask(() => {
                            if (jawaban === 'jaringan') router.kembali.push(opsi.onNetworkError());
                            if (jawaban === 'http') router.kembali.push(opsi.onHttpException());
                            if (jawaban === 'dihentikan') opsi.onCancel();
                            opsi.onFinish();
                        });
                    },
                    on: (nama, f) => { navigasi.add(f); return () => navigasi.delete(f); },
                };
                return router;
            };
            const kasus = async (jawaban, asal) => {
                const router = buatRouter(jawaban);
                const pengendali = murni.buatKeluar({
                    router,
                    rute: '/logout',
                    alat: () => ({ hitung: async () => ({ milik: 0, lama: 0, aksi: 0 }), tanya: () => true, bersihkan: async () => 'terhapus' }),
                });
                const janji = pengendali.keluar('k1', asal);
                const selama = { ...pengendali.keadaan(), berjalan: pengendali.berjalan() };
                const akhir = await Promise.race([janji.then(() => 'selesai'), new Promise((r) => setTimeout(() => r('menggantung'), 300))]);
                const sesudah = { ...pengendali.keadaan(), berjalan: pengendali.berjalan() };
                const pandangan = { menu: murni.pandanganKeluar(pengendali.keadaan(), 'menu'), kartu: murni.pandanganKeluar(pengendali.keadaan(), 'kartu') };
                router.pindah();
                const sesudahPindah = pengendali.keadaan().pesan;
                router.pindah();
                const sesudahPindahKedua = pengendali.keadaan().pesan;
                const ulang = pengendali.keluar('k1', asal);
                const saatUlang = pengendali.keadaan().pesan;
                await ulang;
                return { selama, akhir, sesudah, pandangan, sesudahPindah, sesudahPindahKedua, saatUlang, dikirim: router.dikirim, kembali: router.kembali };
            };
            process.stdout.write(JSON.stringify({
                jaringan: await kasus('jaringan', 'menu'),
                http: await kasus('http', 'kartu'),
                dihentikan: await kasus('dihentikan', 'menu'),
                berhasil: await kasus('berhasil', 'verifikasi'),
            }));
            JS);

        $jaringan = 'Belum pasti keluar: sambungan putus. Data Memount di perangkat ini sudah dihapus. Ketuk Keluar lagi saat ada sinyal.';
        $http = 'Belum pasti keluar: jawaban server tidak dapat dibaca. Data Memount di perangkat ini sudah dihapus. Muat ulang halaman, lalu ketuk Keluar lagi.';
        $dihentikan = 'Belum pasti keluar: halaman berpindah sebelum jawaban server tiba. Data Memount di perangkat ini sudah dihapus. Ketuk Keluar lagi.';

        foreach (['jaringan' => 'menu', 'http' => 'kartu', 'dihentikan' => 'menu', 'berhasil' => 'verifikasi'] as $jawaban => $asal) {
            $satu = $hasil[$jawaban];

            $this->assertSame(['sibuk' => true, 'pesan' => '', 'asal' => $asal, 'berjalan' => true], $satu['selama'], $jawaban);
            $this->assertSame('selesai', $satu['akhir'], "{$jawaban}: onFinish menyelesaikan penantian.");
            $this->assertFalse($satu['sesudah']['sibuk'], "{$jawaban}: sibuk kembali salah.");
            $this->assertFalse($satu['sesudah']['berjalan'], "{$jawaban}: tombol dapat diketuk lagi.");
            $this->assertSame(['/logout', '/logout'], $satu['dikirim'], $jawaban);
            $this->assertSame('', $satu['saatUlang'], "{$jawaban}: percobaan berikutnya mengosongkan kalimat lama.");
        }

        $this->assertSame([false, false], $hasil['jaringan']['kembali'], 'Galat jaringan tidak diteruskan ke pesan jaringan umum.');
        $this->assertSame($jaringan, $hasil['jaringan']['sesudah']['pesan']);
        $this->assertSame(['menu' => ['sibuk' => false, 'pesan' => $jaringan], 'kartu' => ['sibuk' => false, 'pesan' => '']], $hasil['jaringan']['pandangan'], 'Hanya wilayah tombol yang memulai keluar memuat kalimatnya (m-4).');
        $this->assertSame('', $hasil['jaringan']['sesudahPindah'], 'Kalimat galat hilang pada perpindahan halaman berikutnya.');

        $this->assertSame([false, false], $hasil['http']['kembali'], 'Galat HTTP tidak membuka dialog galat Inertia.');
        $this->assertSame($http, $hasil['http']['sesudah']['pesan']);
        $this->assertSame(['menu' => ['sibuk' => false, 'pesan' => ''], 'kartu' => ['sibuk' => false, 'pesan' => $http]], $hasil['http']['pandangan']);
        $this->assertSame('', $hasil['http']['sesudahPindah']);

        $this->assertSame($dihentikan, $hasil['dihentikan']['sesudah']['pesan']);
        $this->assertSame($dihentikan, $hasil['dihentikan']['sesudahPindah'], 'Perpindahan yang membatalkan keluar tidak menghapus kalimatnya (m-3).');
        $this->assertSame($dihentikan, $hasil['dihentikan']['sesudahPindahKedua']);

        $this->assertSame('', $hasil['berhasil']['sesudah']['pesan']);
    }

    /**
     * Pemilihan data yang dihapus (PT11-2, PT11-3, W6-11d, W6-11e), dengan fixture yang sengaja
     * tidak berurutan. Saat keluar: semua basis data paket dan jejak, basis data jejak lama, dan
     * semua antrean sessionStorage. Penjaga ganti akun: hanya milik kunci lain, tanpa basis data
     * jejak lama, tanpa mengosongkan antrean memori pemilik sekarang, dan tidak menyentuh apa pun
     * bila tidak ada data akun lain. Kunci yang dibersihkan ditutup sebelum penghapusan apa pun,
     * antrean memori dikosongkan sebelum sessionStorage dihapus, dan kunci tersimpan dilupakan hanya
     * bila sesudah penghapusan ia masih kunci yang dibersihkan. Tanpa indexedDB.databases(), kunci
     * sekarang dan kunci tersimpan yang berbeda tetap dibersihkan. Penantian modul antrean dibatasi
     * tiga detik (putaran perbaikan 1), karena modul yang belum termuat tidak punya memori untuk
     * dikosongkan.
     */
    public function test_cleanup_picks_every_store_on_sign_out_and_only_other_users_stores_on_switch(): void
    {
        $hasil = $this->node(['murni' => 'luring/keluarMurni.ts'], <<<'JS'
            const semua = ['memount-luring-B', 'jejak-pendakian-A', 'lain', 'jejak-pendakian', 'memount-luring-A', 'jejak-pendakian-B'];
            const sesi = ['mendaki.antrean.B', 'lain', 'mendaki.antrean.A', 'mendaki.antrean.7'];
            const jalankan = async ({ basisData, kunciSesi = sesi, tersimpan, kunci, saatKeluar, hapusPaket = async () => 'terhapus', hapusJejak = async () => 'terhapus', tersimpanSesudah }) => {
                const catatan = [];
                let simpanan = tersimpan;
                const hasil = await murni.bersihkanDengan({
                    daftarBasisData: async () => basisData,
                    kunciSesi: () => kunciSesi,
                    kunciTersimpan: () => simpanan,
                    tutupKunci: (k) => catatan.push('tutup:' + k),
                    kosongkanAntrean: async () => { catatan.push('antrean'); },
                    hapusSesi: (k) => catatan.push('sesi:' + k),
                    hapusPaket: async (k) => {
                        catatan.push('paket:' + k);
                        if (tersimpanSesudah !== undefined) simpanan = tersimpanSesudah;
                        return hapusPaket(k);
                    },
                    hapusJejak: async (n) => { catatan.push('jejak:' + n); return hapusJejak(n); },
                    bersihkanCache: async () => { catatan.push('cache'); },
                    kosongkanOpfs: async () => { catatan.push('opfs'); },
                    lupakanKunci: () => { catatan.push('lupakan'); simpanan = null; },
                }, kunci, saatKeluar);
                return { hasil, catatan };
            };
            process.stdout.write(JSON.stringify({
                keluar: await jalankan({ basisData: semua, tersimpan: 'B', kunci: 'A', saatKeluar: true }),
                keluarTanpaDaftar: await jalankan({ basisData: [], tersimpan: 'B', kunci: 'A', saatKeluar: true }),
                penjaga: await jalankan({ basisData: semua, tersimpan: 'B', kunci: 'A', saatKeluar: false }),
                penjagaTanpaData: await jalankan({ basisData: ['memount-luring-B', 'jejak-pendakian-B', 'jejak-pendakian'], kunciSesi: ['mendaki.antrean.B'], tersimpan: 'B', kunci: 'B', saatKeluar: false }),
                penjagaKunciSendiri: await jalankan({ basisData: semua, tersimpan: 'A', kunci: 'A', saatKeluar: false }),
                penjagaTanpaDaftar: await jalankan({ basisData: [], kunciSesi: [], tersimpan: 'B', kunci: 'A', saatKeluar: false }),
                tersimpanBerganti: await jalankan({ basisData: ['memount-luring-A'], kunciSesi: [], tersimpan: 'A', kunci: 'A', saatKeluar: true, tersimpanSesudah: 'C' }),
                tertunda: await jalankan({ basisData: semua, tersimpan: null, kunci: 'A', saatKeluar: true, hapusPaket: async (k) => (k === 'B' ? 'tertunda' : 'terhapus') }),
                gagal: await jalankan({ basisData: semua, tersimpan: null, kunci: 'A', saatKeluar: true, hapusJejak: async () => { throw new Error('UnknownError'); } }),
                antreanMenggantung: await (async () => {
                    const pewaktu = [];
                    globalThis.setTimeout = (f, ms) => { pewaktu.push({ f, ms }); return pewaktu.length; };
                    globalThis.clearTimeout = () => {};
                    const catatan = [];
                    const janji = murni.bersihkanDengan({
                        daftarBasisData: async () => ['memount-luring-A'],
                        kunciSesi: () => [],
                        kunciTersimpan: () => null,
                        tutupKunci: () => {},
                        kosongkanAntrean: () => new Promise(() => {}),
                        hapusSesi: () => {},
                        hapusPaket: async (k) => { catatan.push('paket:' + k); return 'terhapus'; },
                        hapusJejak: async () => 'terhapus',
                        bersihkanCache: async () => {},
                        kosongkanOpfs: async () => {},
                        lupakanKunci: () => {},
                    }, 'A', true);
                    await new Promise((r) => setImmediate(r));
                    const sebelumBatas = catatan.slice();
                    const batas = pewaktu.map((p) => p.ms);
                    pewaktu.splice(0).forEach((p) => p.f());
                    return { hasil: await janji, sebelumBatas, batas, catatan };
                })(),
            }));
            JS);

        $hapusSemua = ['paket:A', 'paket:B', 'jejak:jejak-pendakian', 'jejak:jejak-pendakian-A', 'jejak:jejak-pendakian-B', 'cache', 'opfs'];

        $this->assertSame('terhapus', $hasil['keluar']['hasil']);
        $this->assertSame(
            ['tutup:A', 'tutup:B', 'antrean', 'sesi:mendaki.antrean.7', 'sesi:mendaki.antrean.A', 'sesi:mendaki.antrean.B', ...$hapusSemua, 'lupakan'],
            $hasil['keluar']['catatan'],
        );
        $this->assertSame($hasil['keluar'], $hasil['keluarTanpaDaftar'], 'Tanpa indexedDB.databases(), kunci sekarang dan kunci tersimpan tetap dibersihkan.');
        $this->assertSame(
            ['tutup:B', 'sesi:mendaki.antrean.7', 'sesi:mendaki.antrean.B', 'paket:B', 'jejak:jejak-pendakian-B', 'cache', 'opfs', 'lupakan'],
            $hasil['penjaga']['catatan'],
            'Penjaga hanya membersihkan kunci lain, tanpa basis data jejak lama (W6-11e) dan tanpa antrean memori.',
        );
        $this->assertSame(['hasil' => 'terhapus', 'catatan' => []], $hasil['penjagaTanpaData'], 'Tanpa data akun lain, penjaga tidak menyentuh apa pun.');
        $this->assertNotContains('lupakan', $hasil['penjagaKunciSendiri']['catatan'], 'Kunci tersimpan milik pengguna sekarang tidak dilupakan.');
        $this->assertSame(['tutup:B', 'paket:B', 'jejak:jejak-pendakian-B', 'cache', 'opfs', 'lupakan'], $hasil['penjagaTanpaDaftar']['catatan']);
        $this->assertNotContains('lupakan', $hasil['tersimpanBerganti']['catatan'], 'Kunci yang disimpan tab lain selama penghapusan tidak dilupakan (PT11-2 bagian 3).');
        $this->assertSame('tertunda', $hasil['tertunda']['hasil']);
        $this->assertSame('gagal', $hasil['gagal']['hasil']);
        $this->assertSame(
            ['hasil' => 'terhapus', 'sebelumBatas' => [], 'batas' => [3000], 'catatan' => ['paket:A']],
            $hasil['antreanMenggantung'],
            'Modul antrean yang tidak termuat dalam tiga detik tidak menahan penghapusan (putaran perbaikan 1).',
        );
    }

    /**
     * Penjaga ganti akun memakai kunci dari jawaban server saja (ruling W6-11g bagian I-1). Inertia
     * memulihkan props lama dari history.state untuk dokumen yang dimuat lewat tombol Kembali
     * (back_forward) dan untuk popstate. Tanpa gerbang ini, B yang menekan Kembali ke /profile milik A
     * yang sudah keluar kehilangan titik jejak, antrean, dan paketnya tanpa ditanya (bukti peninjau
     * dengan pencatat deleteDatabase). Ganti akun biasa tetap membersihkan data kunci lain.
     */
    public function test_props_restored_from_history_never_clear_the_signed_in_users_device_data(): void
    {
        $hasil = $this->node(['murni' => 'luring/keluarMurni.ts'], <<<'JS'
            const perangkat = (isi) => {
                const s = { db: new Set(isi.db), sesi: new Set(isi.sesi), tersimpan: isi.tersimpan };
                const alat = {
                    daftarBasisData: async () => [...s.db],
                    kunciSesi: () => [...s.sesi],
                    kunciTersimpan: () => s.tersimpan,
                    tutupKunci: () => {},
                    kosongkanAntrean: async () => {},
                    hapusSesi: (k) => { s.sesi.delete(k); },
                    hapusPaket: async (k) => { s.db.delete('memount-luring-' + k); return 'terhapus'; },
                    hapusJejak: async (n) => { s.db.delete(n); return 'terhapus'; },
                    bersihkanCache: async () => {},
                    kosongkanOpfs: async () => {},
                    lupakanKunci: () => { s.tersimpan = null; },
                };
                return { alat, isi: () => ({ db: [...s.db].sort(), sesi: [...s.sesi].sort(), tersimpan: s.tersimpan }) };
            };
            const penjaga = async (riwayat, kunci, alat) => {
                let janji = null;
                const jalan = murni.jalankanPenjaga(kunci, riwayat, (k) => { janji = murni.bersihkanDengan(alat, k, false); });
                await janji;
                return jalan;
            };
            const dataB = () => perangkat({ db: ['memount-luring-B', 'jejak-pendakian-B', 'jejak-pendakian'], sesi: ['mendaki.antrean.B'], tersimpan: 'B' });

            const pelacak = murni.pelacakRiwayat(false);
            const urutan = [pelacak.boleh()];
            pelacak.popstate(); urutan.push(pelacak.boleh());
            pelacak.mulaiKunjungan(); urutan.push(pelacak.boleh());
            const dariKembali = murni.pelacakRiwayat(true);
            urutan.push(dariKembali.boleh());
            dariKembali.mulaiKunjungan(); urutan.push(dariKembali.boleh());

            // A keluar, B masuk (muat penuh), lalu Kembali ke /profile A: dokumen back_forward dengan kunci A dari riwayat.
            const kembali = dataB();
            const riwayatKembali = murni.pelacakRiwayat(true);
            const jalanKembali = await penjaga(riwayatKembali, 'A', kembali.alat);
            const sesudahKembali = kembali.isi();
            riwayatKembali.mulaiKunjungan();
            const jalanKunjungan = await penjaga(riwayatKembali, 'B', kembali.alat);
            const sesudahKunjungan = kembali.isi();

            // Satu dokumen: halaman B dari server, lalu popstate ke halaman A yang dipulihkan.
            const popstate = dataB();
            const riwayatPop = murni.pelacakRiwayat(false);
            await penjaga(riwayatPop, 'B', popstate.alat);
            riwayatPop.popstate();
            const jalanPop = await penjaga(riwayatPop, 'A', popstate.alat);
            const sesudahPop = popstate.isi();

            // Ganti akun biasa: halaman A dari server, data B tertinggal.
            const biasa = dataB();
            const jalanBiasa = await penjaga(murni.pelacakRiwayat(false), 'A', biasa.alat);

            process.stdout.write(JSON.stringify({ urutan, jalanKembali, sesudahKembali, jalanKunjungan, sesudahKunjungan, jalanPop, sesudahPop, jalanBiasa, sesudahBiasa: biasa.isi(), tanpaKunci: murni.jalankanPenjaga(null, murni.pelacakRiwayat(false), () => {}) }));
            JS);

        $utuhB = ['db' => ['jejak-pendakian', 'jejak-pendakian-B', 'memount-luring-B'], 'sesi' => ['mendaki.antrean.B'], 'tersimpan' => 'B'];

        $this->assertSame([true, false, true, false, true], $hasil['urutan'], 'popstate dan back_forward menandai props riwayat; kunjungan berikutnya membawa props server.');
        $this->assertFalse($hasil['jalanKembali']);
        $this->assertSame($utuhB, $hasil['sesudahKembali'], 'Kembali ke halaman akun lama tidak menghapus data akun yang sedang masuk.');
        $this->assertTrue($hasil['jalanKunjungan']);
        $this->assertSame($utuhB, $hasil['sesudahKunjungan'], 'Sesudah kunjungan biasa, props B dari server menjaga data B.');
        $this->assertFalse($hasil['jalanPop']);
        $this->assertSame($utuhB, $hasil['sesudahPop'], 'popstate di dokumen yang sama juga tidak menghapus data akun yang sedang masuk.');
        $this->assertTrue($hasil['jalanBiasa']);
        $this->assertSame(['db' => ['jejak-pendakian'], 'sesi' => [], 'tersimpan' => null], $hasil['sesudahBiasa'], 'Ganti akun biasa tetap membersihkan data kunci lain, kecuali basis data jejak lama (W6-11e).');
        $this->assertFalse($hasil['tanpaKunci']);
    }

    /**
     * Pembantu penyimpanan peramban di keluarMurni.ts. Basis data dihapus menurut nama, tanpa
     * bergantung pada modul jejak (PT11-8 bagian 2), dan penghapusan yang terhalang tab lain
     * dilaporkan tertunda sesudah tiga detik (PT11-4). Cache: semua pack-* dibuang, dan dari setiap
     * cache shell-* hanya penanda shell paket yang dihapus (W6-9g). OPFS dikosongkan rekursif, dan
     * peramban tanpa API itu tidak gagal.
     */
    public function test_device_stores_are_cleared_by_name_and_a_blocked_deletion_is_reported_as_pending(): void
    {
        $hasil = $this->node(['murni' => 'luring/keluarMurni.ts'], <<<'JS'
            const pewaktu = [];
            globalThis.setTimeout = (f, ms) => { pewaktu.push({ f, ms }); return pewaktu.length; };
            globalThis.clearTimeout = () => {};
            let mode = 'sukses';
            const dihapus = [];
            globalThis.indexedDB = {
                deleteDatabase(nama) {
                    dihapus.push(nama);
                    const r = {};
                    queueMicrotask(() => {
                        if (mode === 'sukses') r.onsuccess?.();
                        else if (mode === 'galat') { r.error = new Error('UnknownError'); r.onerror?.(); }
                        else r.onblocked?.();
                    });
                    return r;
                },
            };
            const sukses = await murni.hapusMenurutNama('jejak-pendakian-A');
            mode = 'terhalang';
            const janji = murni.hapusMenurutNama('jejak-pendakian-B');
            await new Promise((r) => setImmediate(r));
            const batas = pewaktu.map((p) => p.ms);
            pewaktu.splice(0).forEach((p) => p.f());
            const tertunda = await janji;
            mode = 'galat';
            let galat = null;
            try { await murni.hapusMenurutNama('x'); } catch (e) { galat = e.message; }
            delete globalThis.indexedDB;
            const tanpaIdb = await murni.hapusMenurutNama('y');

            const cacheLog = [];
            await murni.bersihkanCache({
                keys: async () => ['memount-lain', 'shell-b', 'pack-1', 'shell-a'],
                delete: async (n) => { cacheLog.push('buang ' + n); return true; },
                open: async (n) => ({ delete: async (url) => { cacheLog.push(n + ' hapus ' + url); return true; } }),
            });
            await murni.bersihkanCache(undefined);

            const opfsLog = [];
            const akar = { async *keys() { yield 'a'; yield 'b'; }, removeEntry: async (n, o) => { opfsLog.push(n + ':' + JSON.stringify(o)); } };
            await murni.kosongkanOpfs({ getDirectory: async () => akar });
            await murni.kosongkanOpfs(undefined);
            await murni.kosongkanOpfs({ getDirectory: async () => { throw new Error('SecurityError'); } });

            process.stdout.write(JSON.stringify({ sukses, tertunda, batas, galat, tanpaIdb, dihapus, cacheLog: cacheLog.sort(), opfsLog }));
            JS);

        $this->assertSame('terhapus', $hasil['sukses']);
        $this->assertSame('tertunda', $hasil['tertunda']);
        $this->assertSame([3000], $hasil['batas']);
        $this->assertSame('UnknownError', $hasil['galat']);
        $this->assertSame('terhapus', $hasil['tanpaIdb']);
        $this->assertSame(['jejak-pendakian-A', 'jejak-pendakian-B', 'x'], $hasil['dihapus']);
        $this->assertSame(['buang pack-1', 'shell-a hapus /__memount-shell-paket', 'shell-b hapus /__memount-shell-paket'], $hasil['cacheLog']);
        $this->assertSame(['a:{"recursive":true}', 'b:{"recursive":true}'], $hasil['opfsLog']);
    }

    /**
     * Celah lama jejak.js (spec v3 bagian 10.6): satu basis data untuk siapa pun yang masuk di
     * peramban itu. Kini namanya memakai kunci perangkat pengguna, dan perekam menolak berjalan
     * tanpa kunci tanpa membuka basis data apa pun. Penghapusan menutup koneksi tab ini lebih dulu
     * (PT11-4, K8), dan penghapusan dari tab lain (versionchange) menutup koneksinya sehingga
     * perekam tidak membuat ulang basis data yang baru dihapus. Pembukaan yang gagal tidak disimpan,
     * jadi titik berikutnya mencoba lagi.
     *
     * Putaran perbaikan 1 (tinjauan Tugas 11 m-7): nama yang dihapus tab lain (versionchange dengan
     * newVersion null) ditolak aturPengguna sampai muat penuh, jadi efek perekam yang berjalan ulang
     * di tab mode lapangan lain tidak membuat ulang basis data akun yang sudah keluar.
     *
     * Diubah sisipan W6-P2 dengan alasan: bacaTitik dibuang bersama pengirim lama (pengirim baru
     * memakai fungsi berbasis nama yang tidak pernah membuat basis data), jadi pembukaan basis data
     * perekam diuji lewat simpanTitik, jalan yang dipakai perekam sendiri; asersinya tidak berubah.
     * Perekam tidak lagi menerima URL kirim (ruling W6-P2a), jadi panggilan Hike.tsx tanpa
     * data.urls.jejak.
     */
    public function test_the_track_database_is_bound_to_the_user(): void
    {
        $hasil = $this->node(['jejak' => 'jejak.js'], <<<'JS'
            const kejadian = [];
            const koneksi = [];
            const pewaktu = [];
            globalThis.setTimeout = (f, ms) => { pewaktu.push({ f, ms }); return pewaktu.length; };
            globalThis.clearTimeout = () => {};
            let hapusTerhalang = false;
            let gagalBuka = false;
            globalThis.indexedDB = {
                open(nama) {
                    kejadian.push('buka:' + nama);
                    const r = { result: null };
                    queueMicrotask(() => {
                        if (gagalBuka) {
                            gagalBuka = false;
                            r.error = new Error('UnknownError');
                            r.onerror?.();
                            return;
                        }
                        const db = {
                            objectStoreNames: { contains: () => true },
                            close() { kejadian.push('tutup:' + nama); },
                            onversionchange: null,
                            transaction: () => ({ objectStore: () => ({ put() {} }) }),
                        };
                        koneksi.push(db);
                        r.result = db;
                        r.onsuccess?.();
                    });
                    return r;
                },
                deleteDatabase(nama) {
                    kejadian.push('hapus:' + nama);
                    const r = {};
                    queueMicrotask(() => (hapusTerhalang ? r.onblocked?.() : r.onsuccess?.()));
                    return r;
                },
            };
            const POSISI = { timestamp: 0, coords: { latitude: -7.45, longitude: 110.44, altitude: null, accuracy: 8 } };
            const tolak = async () => { try { await jejak.simpanTitik(1, POSISI); return null; } catch (e) { return e.message; } };

            const tolakAwal = await tolak();
            const kejadianAwal = kejadian.slice();
            gagalBuka = true;
            jejak.aturPengguna('abc');
            const tolakBukaGagal = await tolak();
            await jejak.simpanTitik(1, POSISI);
            jejak.aturPengguna('def');
            await jejak.simpanTitik(1, POSISI);
            const hapus = await jejak.hapusBasisData('jejak-pendakian-def');
            const tolakSesudahHapus = await tolak();
            jejak.aturPengguna('ghi');
            await jejak.simpanTitik(1, POSISI);
            koneksi.at(-1).onversionchange();
            const tolakSesudahVersi = await tolak();
            let aturUlangSesudahVersi = 'boleh';
            try { jejak.aturPengguna('ghi'); } catch (e) { aturUlangSesudahVersi = e.message; }
            jejak.aturPengguna('jkl');
            await jejak.simpanTitik(1, POSISI);
            koneksi.at(-1).onversionchange({ newVersion: null });
            const bukaSebelumAturUlang = kejadian.filter((k) => k.startsWith('buka:')).length;
            let aturUlangSesudahHapus = 'boleh';
            try { jejak.aturPengguna('jkl'); } catch (e) { aturUlangSesudahHapus = e.message; }
            const tolakSesudahHapusTabLain = await tolak();
            const bukaSesudahAturUlang = kejadian.filter((k) => k.startsWith('buka:')).length;
            hapusTerhalang = true;
            const janji = jejak.hapusBasisData('jejak-pendakian-lain');
            await new Promise((r) => setImmediate(r));
            const batas = pewaktu.map((p) => p.ms);
            pewaktu.splice(0).forEach((p) => p.f());
            const tertunda = await janji;
            process.stdout.write(JSON.stringify({ tolakAwal, kejadianAwal, tolakBukaGagal, kejadian, hapus, tolakSesudahHapus, tolakSesudahVersi, aturUlangSesudahVersi, aturUlangSesudahHapus, tolakSesudahHapusTabLain, bukaSebelumAturUlang, bukaSesudahAturUlang, batas, tertunda }));
            JS);

        $tolak = 'Pengguna perangkat belum diatur untuk perekam jejak.';
        $kejadian = $hasil['kejadian'];

        $this->assertSame($tolak, $hasil['tolakAwal']);
        $this->assertSame([], $hasil['kejadianAwal'], 'Tanpa kunci perangkat, perekam tidak membuka basis data apa pun.');
        $this->assertSame('UnknownError', $hasil['tolakBukaGagal']);
        $this->assertSame(
            ['buka:jejak-pendakian-abc', 'buka:jejak-pendakian-abc', 'buka:jejak-pendakian-def', 'buka:jejak-pendakian-ghi', 'buka:jejak-pendakian-jkl'],
            array_values(array_filter($kejadian, fn (string $k) => str_starts_with($k, 'buka:'))),
            'Setiap pengguna membuka basis datanya sendiri, tidak ada yang membuka basis data bersama, dan pembukaan yang gagal dicoba lagi pada titik berikutnya.',
        );
        $this->assertContains('tutup:jejak-pendakian-abc', $kejadian, 'Berganti pengguna menutup koneksi pengguna sebelumnya.');

        $tutup = array_search('tutup:jejak-pendakian-def', $kejadian, true);
        $hapus = array_search('hapus:jejak-pendakian-def', $kejadian, true);

        $this->assertIsInt($tutup, 'hapusBasisData menutup koneksi tab ini.');
        $this->assertIsInt($hapus);
        $this->assertLessThan($hapus, $tutup, 'hapusBasisData menutup koneksi tab ini sebelum menghapus.');
        $this->assertSame('terhapus', $hasil['hapus']);
        $this->assertSame($tolak, $hasil['tolakSesudahHapus'], 'Sesudah basis datanya dihapus, perekam tidak membuatnya ulang.');
        $this->assertContains('tutup:jejak-pendakian-ghi', $kejadian, 'versionchange dari tab lain menutup koneksi ini.');
        $this->assertSame($tolak, $hasil['tolakSesudahVersi']);
        $this->assertSame('boleh', $hasil['aturUlangSesudahVersi'], 'versionchange yang bukan penghapusan tidak menutup nama itu.');
        $this->assertSame('Basis data jejak ini sudah dihapus di perangkat ini.', $hasil['aturUlangSesudahHapus'], 'Basis data yang dihapus tab lain tidak diikat lagi sampai muat penuh (m-7).');
        $this->assertSame($tolak, $hasil['tolakSesudahHapusTabLain']);
        $this->assertSame($hasil['bukaSebelumAturUlang'], $hasil['bukaSesudahAturUlang'], 'Tanpa indexedDB.open sesudah penghapusan dari tab lain.');
        $this->assertSame([3000], $hasil['batas']);
        $this->assertSame('tertunda', $hasil['tertunda']);

        $sumber = File::get(resource_path('js/jejak.js'));
        $kait = File::get(resource_path('js/components/trips/usePendakian.ts'));

        $this->assertStringContainsString("export const AWALAN_DB = 'jejak-pendakian-';", $sumber);
        $this->assertStringContainsString("export const DB_LAMA = 'jejak-pendakian';", $sumber);
        $this->assertStringNotContainsString('indexedDB.open(NAMA_DB', $sumber);

        $atur = strpos($kait, 'jejak.aturPengguna(kunci);');
        $rekam = strpos($kait, 'berhenti = jejak.mulaiMerekam(sesiId);');

        $this->assertIsInt($atur, 'Perekam diikat ke pengguna perangkat sebelum merekam.');
        $this->assertIsInt($rekam);
        $this->assertLessThan($rekam, $atur);
        $this->assertStringContainsString(
            'useJejakPendakian(data.rekamJejak && data.trip.berlangsung && !mode.dijeda, data.sesiId, kunci);',
            File::get(resource_path('js/pages/Trips/Hike.tsx')),
            'Mode lapangan menyerahkan kunci perangkat ke perekam (V11-1), dan syarat jeda M-3 tetap.',
        );
    }

    /**
     * Pertanyaan keluar menghitung titik tanpa membuat basis data yang dihitungnya (PT11-8 bagian
     * 3): tanpa indexedDB.databases() hasilnya tidak diketahui (null), bukan nol, dan basis data
     * yang tidak ada dihitung nol tanpa dibuka.
     */
    public function test_counting_unsent_points_never_creates_a_database(): void
    {
        $hasil = $this->node(['jejak' => 'jejak.js'], <<<'JS'
            let dibuka = 0;
            let ditutup = 0;
            const isi = { 'jejak-pendakian-k1': 3 };
            globalThis.indexedDB = {
                open(nama) {
                    dibuka++;
                    const r = {};
                    queueMicrotask(() => {
                        r.result = {
                            objectStoreNames: { contains: () => true },
                            close() { ditutup++; },
                            transaction: () => ({ objectStore: () => ({ count() { const q = {}; queueMicrotask(() => { q.result = isi[nama]; q.onsuccess?.(); }); return q; } }) }),
                        };
                        r.onsuccess?.();
                    });
                    return r;
                },
            };
            const tanpaDaftar = await jejak.jumlahTitik('jejak-pendakian-k1');
            const bukaTanpaDaftar = dibuka;
            globalThis.indexedDB.databases = async () => [{ name: 'jejak-pendakian-k1', version: 1 }];
            const tidakAda = await jejak.jumlahTitik('jejak-pendakian');
            const bukaTidakAda = dibuka;
            const ada = await jejak.jumlahTitik('jejak-pendakian-k1');
            process.stdout.write(JSON.stringify({ tanpaDaftar, bukaTanpaDaftar, tidakAda, bukaTidakAda, ada, dibuka, ditutup }));
            JS);

        $this->assertSame(['tanpaDaftar' => null, 'bukaTanpaDaftar' => 0, 'tidakAda' => 0, 'bukaTidakAda' => 0, 'ada' => 3, 'dibuka' => 1, 'ditutup' => 1], $hasil);
    }

    /**
     * Kunci tertutup di tab ini (ruling W6-11d): sesudah keluar atau dibersihkan penjaga, basis data
     * paket kunci itu tidak dibuka (dan karena itu tidak dibuat ulang) sampai muat penuh, juga oleh
     * halaman yang dipulihkan tombol Kembali. Perekam jejak juga tidak diikat ke kunci itu.
     *
     * Putaran perbaikan 1: penghapusan basis data paket dari tab lain (versionchange dengan
     * newVersion null) juga menutup kuncinya di tab ini (tinjauan Tugas 11 m-7); kenaikan versi skema
     * tidak. kunciPerekam (W6-11f) dijaga ketiga cabangnya: daring, shell, dan tanpa kunci (m-5).
     */
    public function test_a_closed_device_key_is_never_opened_again_in_this_tab(): void
    {
        $hasil = $this->node(['simpan' => 'luring/simpan.ts'], <<<'JS'
            let dibuka = 0;
            globalThis.window = { localStorage: { getItem: () => 'k1' } };
            globalThis.indexedDB = {
                open() { dibuka++; const r = {}; queueMicrotask(() => { r.result = { close() {}, onversionchange: null }; r.onsuccess?.(); }); return r; },
                databases: async () => [{ name: 'memount-luring-k1', version: 1 }, { name: 'memount-luring-k2', version: 1 }],
            };
            const sebelum = [simpan.kunciTertutup('k1'), await simpan.adaBasisData('k1')];
            simpan.tutupKunci('k1');
            let tolak = null;
            try { await simpan.buka('k1'); } catch (e) { tolak = e.message; }
            const sesudah = [simpan.kunciTertutup('k1'), await simpan.adaBasisData('k1'), dibuka];
            await simpan.buka('k2');
            const kunciLain = [simpan.kunciTertutup('k2'), dibuka];
            delete globalThis.indexedDB.databases;
            const tanpaDaftar = await simpan.adaBasisData('k1');
            const db3 = await simpan.buka('k3');
            db3.onversionchange({ newVersion: null });
            let tolakSesudahHapusTabLain = null;
            const bukaSebelum = dibuka;
            try { await simpan.buka('k3'); } catch (e) { tolakSesudahHapusTabLain = e.message; }
            const hapusTabLain = [simpan.kunciTertutup('k3'), dibuka - bukaSebelum];
            const db4 = await simpan.buka('k4');
            db4.onversionchange({ newVersion: 2 });
            const naikVersi = [simpan.kunciTertutup('k4'), (await simpan.buka('k4')) !== db4];
            const perekam = [simpan.kunciPerekam('A', 'B'), simpan.kunciPerekam(null, 'B'), simpan.kunciPerekam(null, null)];
            process.stdout.write(JSON.stringify({ sebelum, tolak, sesudah, kunciLain, tanpaDaftar, tolakSesudahHapusTabLain, hapusTabLain, naikVersi, perekam }));
            JS);

        $this->assertSame([false, true], $hasil['sebelum']);
        $this->assertSame('Kunci perangkat ini sudah ditutup di tab ini.', $hasil['tolak']);
        $this->assertSame([true, false, 0], $hasil['sesudah'], 'Kunci tertutup tidak dibuka dan dianggap tanpa basis data.');
        $this->assertSame([false, 1], $hasil['kunciLain']);
        $this->assertFalse($hasil['tanpaDaftar'], 'Tanpa indexedDB.databases(), kunci tersimpan yang sudah tertutup tetap dianggap tanpa basis data.');
        $this->assertSame('Kunci perangkat ini sudah ditutup di tab ini.', $hasil['tolakSesudahHapusTabLain'], 'Basis data paket yang dihapus tab lain menutup kuncinya di tab ini (m-7).');
        $this->assertSame([true, 0], $hasil['hapusTabLain']);
        $this->assertSame([false, true], $hasil['naikVersi'], 'Kenaikan versi skema kelak hanya melepas koneksi dan membuka ulang.');
        $this->assertSame(['A', 'B', null], $hasil['perekam'], 'Perekam memakai kunci halaman daring, kunci tersimpan di shell, dan tidak merekam tanpa kunci (W6-11f).');

        $kait = $this->tanpaKomentarJs(File::get(resource_path('js/components/trips/usePendakian.ts')));

        $this->assertStringContainsString('const kunci = kunciPerekam(kunciHalaman, kunciTersimpan());', $kait, 'Cadangan kunci shell lewat kunciPerekam (W6-11f).');
        $this->assertStringContainsString('|| kunci === null || kunciTertutup(kunci) ||', $kait, 'Perekam tidak dimulai untuk kunci yang sudah ditutup.');

        $gerbang = strpos($kait, 'if (batal || kunciTertutup(kunci)) {');
        $ikat = strpos($kait, 'jejak.aturPengguna(kunci);');

        $this->assertIsInt($gerbang, 'Kunci yang ditutup selama modul dimuat juga tidak diikat.');
        $this->assertIsInt($ikat);
        $this->assertLessThan($ikat, $gerbang);
    }

    /**
     * Setiap pintu keluar lewat keluar() (ruling W6-R10, PT11-12): tombol Keluar di menu avatar,
     * kartu Akun di Profil, dan halaman verifikasi surel. Tidak ada kode lain di resources/js yang
     * memanggil rute logout langsung, selain keluar.ts dan berkas rute Wayfinder. Tombol membawa
     * aria-busy selama berjalan, dan kegagalan ditulis di wilayah status yang selalu terpasang di
     * dekatnya (W6-11c). Menu avatar tetap terbuka selama keluar berjalan, supaya kalimat kegagalan
     * dan fokus tetap di dekat tombol.
     */
    public function test_every_sign_out_door_goes_through_the_device_cleanup(): void
    {
        foreach (['layouts/MenuAkun.tsx' => 'menu', 'components/akun/PintuAkun.tsx' => 'kartu', 'pages/Auth/VerifyEmail.tsx' => 'verifikasi'] as $berkas => $asal) {
            $isi = $this->tanpaKomentarJs(File::get(resource_path('js/'.$berkas)));

            // Putaran perbaikan 1 (m-4): setiap pintu menyebut asalnya, dan wilayah statusnya hanya
            // membaca kalimat dari keluar yang dimulai di pintu itu.
            $this->assertStringContainsString("onClick={() => void keluar(perangkat?.kunci ?? null, '{$asal}')}", $isi, $berkas);
            $this->assertStringContainsString("const keadaanKeluar = useKeadaanKeluar('{$asal}');", $isi, $berkas);
            $this->assertSame(1, substr_count($isi, 'useKeadaanKeluar('), $berkas);
            $this->assertStringContainsString('aria-busy={keadaanKeluar.sibuk}', $isi, $berkas);
            $this->assertMatchesRegularExpression('/<p role="status"[^>]*>\{keadaanKeluar\.pesan\}<\/p>/', $isi, $berkas);
            $this->assertStringNotContainsString("router.post('/logout')", $isi, $berkas);
        }

        $pelanggar = [];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            $nama = str_replace('\\', '/', $berkas->getRelativePathname());

            if ($nama === 'luring/keluar.ts' || str_starts_with($nama, 'routes/') || str_starts_with($nama, 'actions/')) {
                continue;
            }

            if (preg_match('/\blogout\b/', $this->tanpaKomentarJs($berkas->getContents()))) {
                $pelanggar[] = $nama;
            }
        }

        $this->assertSame([], $pelanggar, 'Rute logout dipanggil di luar keluar.ts: '.implode(', ', $pelanggar));

        $keluar = $this->tanpaKomentarJs(File::get(resource_path('js/luring/keluar.ts')));

        $this->assertStringContainsString('hitung: async () => hitungDengan(await alatHitung(), kunci),', $keluar);
        $this->assertStringContainsString('tanya: (kalimat) => window.confirm(kalimat),', $keluar);
        $this->assertStringContainsString('bersihkan: () => bersihkanPerangkat(kunci, { saatKeluar: true }),', $keluar);
        // Rute ditulis langsung, bukan logout.url() dari @/routes: impor itu menambah satu potongan
        // ke pramuat mode lapangan dan ke daftar shell paket lewat penjaga di TataLetakPendakian (23
        // menjadi 24, tepat di batas). Nilainya dipatok ke rute bernama di sini.
        $this->assertStringContainsString("const RUTE_KELUAR = '".route('logout', absolute: false)."';", $keluar);
        $this->assertStringContainsString('rute: RUTE_KELUAR,', $keluar);
        $this->assertStringNotContainsString("from '@/routes'", $keluar);
        $this->assertStringNotContainsString("import('@/routes')", $keluar);
        // Putaran perbaikan 1 (m-5): kalimat, sibuk, dan penyelesaian setiap cabang rute keluar
        // diuji perilakunya di buatKeluar dengan router tiruan; di sini sambungannya ke router Inertia.
        $this->assertStringContainsString('const pengendali = buatKeluar({', $keluar);
        $this->assertStringContainsString('post: (url, data, opsi) => router.post(url, data, opsi),', $keluar);
        $this->assertStringContainsString('on: (nama, panggil) => router.on(nama, panggil),', $keluar);
        $this->assertStringContainsString('return pengendali.keluar(kunci, asal);', $keluar);
        $this->assertStringContainsString('return pandanganKeluar(useSyncExternalStore(pengendali.dengar, pengendali.keadaan, pengendali.keadaan), asal);', $keluar);
        $this->assertStringContainsString("if (menu.current?.matches(':popover-open') && !keluarBerjalan()) {", File::get(resource_path('js/layouts/MenuAkun.tsx')));
    }

    /**
     * Berganti akun tanpa keluar (sesi kedaluwarsa, akun lain masuk): data perangkat akun lain
     * dihapus saat halaman pertama akun ini dibuka, termasuk di mode lapangan (ruling W6-R10).
     * Pemilihannya diuji perilaku di atas; di sini sambungannya ke penghapusan sungguhan.
     *
     * Putaran perbaikan 1: penjaga lewat jalankanPenjaga dengan pelacak asal props (W6-11g bagian
     * I-1): back_forward dan popstate menandai props riwayat, kunjungan Inertia berikutnya membawa
     * props server. Antrean paket dihitung menurut nama (m-2).
     */
    public function test_switching_accounts_clears_the_previous_users_device_data(): void
    {
        $keluar = $this->tanpaKomentarJs(File::get(resource_path('js/luring/keluar.ts')));

        $this->assertStringContainsString('jalankanPenjaga(kunci, riwayat, (k) => void bersihkanPerangkat(k, { saatKeluar: false }));', $keluar);
        $this->assertStringContainsString("(performance.getEntriesByType?.('navigation')[0] as PerformanceNavigationTiming | undefined)?.type === 'back_forward',", $keluar);
        $this->assertStringContainsString("window.addEventListener('popstate', () => riwayat.popstate());", $keluar);
        $this->assertStringContainsString("router.on('start', () => riwayat.mulaiKunjungan());", $keluar);
        $this->assertStringContainsString('return bersihkanDengan(alatBersih, kunci, saatKeluar);', $keluar);

        foreach ([
            'tutupKunci,',
            'lupakanKunci,',
            'kosongkanAntrean: () => muatAntrean().then((m) => m.kosongkanAntrean(), () => undefined),',
            'hapusPaket: (k) => hapusBasisData(k),',
            'hapusJejak: (nama) => hapusMenurutNama(nama),',
            'bersihkanCache: () => bersihkanCache(),',
            'kosongkanOpfs: () => kosongkanOpfs(),',
            'aksiDiPaketDengan(',
            'daftarBasisData: daftarAtauNull,',
            'return await antrean.penyimpananIndexedDb(db).muat();',
            'aksiDiTab: (k) => antrean.penyimpananSessionStorage(k).muat(),',
        ] as $alat) {
            $this->assertStringContainsString($alat, $keluar);
        }

        foreach (['layouts/AppLayout.tsx', 'components/trips/TataLetakPendakian.tsx'] as $berkas) {
            $this->assertStringContainsString('usePenjagaPerangkat();', File::get(resource_path('js/'.$berkas)), $berkas);
        }
    }

    /**
     * keluarMurni.ts tanpa impor nilai menyalin nama penyimpanan yang dihapusnya; salinan itu wajib
     * sama dengan pemiliknya: simpan.ts (paket), jejak.js (jejak), antrean.ts (sessionStorage), dan
     * service worker beserta pemeriksaan Tugas 10 (penanda shell paket).
     */
    public function test_the_names_it_clears_match_the_stores_that_own_them(): void
    {
        $hasil = $this->node([
            'murni' => 'luring/keluarMurni.ts',
            'simpan' => 'luring/simpan.ts',
            'jejak' => 'jejak.js',
            'periksa' => 'luring/periksa.ts',
            'antrean' => 'components/mendaki/antrean.ts',
        ], <<<'JS'
            const ditulis = [];
            globalThis.sessionStorage = { setItem: (k) => ditulis.push(k), removeItem: () => {}, getItem: () => null };
            await antrean.penyimpananSessionStorage('X').simpan([{ id: 'a', url: '/u', isi: {}, dibuat: 1 }]);
            process.stdout.write(JSON.stringify({
                paket: murni.AWALAN_PAKET === simpan.AWALAN_BASIS_DATA,
                jejak: murni.AWALAN_JEJAK === jejak.AWALAN_DB,
                lama: murni.JEJAK_LAMA === jejak.DB_LAMA,
                penanda: murni.PENANDA_SHELL === periksa.PENANDA_SHELL_PAKET,
                antrean: ditulis[0] === murni.AWALAN_ANTREAN + 'X',
                nilaiPenanda: murni.PENANDA_SHELL,
            }));
            JS);

        $this->assertSame(['paket' => true, 'jejak' => true, 'lama' => true, 'penanda' => true, 'antrean' => true, 'nilaiPenanda' => '/__memount-shell-paket'], $hasil);
        $this->assertStringContainsString("const PENANDA_PAKET = '/__memount-shell-paket';", File::get(resource_path('views/sw.blade.php')));
    }

    /**
     * Menjalankan badan skrip di Node atas modul tanpa impor nilai (ruling L3), pola
     * OfflineShellTest::node.
     *
     * @param  array<string, string>  $modul  nama variabel => jalur di bawah resources/js
     */
    private function node(array $modul, string $badan): mixed
    {
        $impor = '';

        foreach ($modul as $nama => $berkas) {
            $impor .= 'const '.$nama.' = await import(pathToFileURL('.json_encode(resource_path('js/'.$berkas)).').href);';
        }

        $proses = new Process(['node', '--experimental-strip-types', '--input-type=module', '-'], base_path(), null, 'import { pathToFileURL } from "node:url";'.$impor.$badan, 60);
        $proses->run();

        $this->assertTrue($proses->isSuccessful(), 'Node gagal: '.mb_substr($proses->getErrorOutput(), 0, 900));

        return json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }
}
