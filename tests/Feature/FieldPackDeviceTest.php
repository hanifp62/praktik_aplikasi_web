<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Trips\PenerimaanJejak;
use App\Support\AsetLuring;
use App\Support\KunciPerangkat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Unduh dan verifikasi paket lapangan di perangkat (spec v3 bagian 10.2 dan 10.6; rencana induk
 * W6 butir 5; ruling W6-R9 dan W6-R10). Pemeriksaan keutuhan, ruang, dan kalimat dijalankan di
 * Node dari modul yang sama dengan peramban (resources/js/luring/periksa.ts).
 *
 * Diperluas di luar brief (task-10-tambahan.md, P-R10): alur unduh diuji perilakunya di Node
 * lewat alur.ts dengan dependensi disuntik, bukan urutan teks (PT10-9, W6-8d, W6-9i), begitu pula
 * pesan ke service worker (W6-10d), pemeriksaan dan penyembuhan shell luring (W6-9b, W6-9g,
 * W6-9n), kalimat sumber mode lapangan (W6-9e), gabungan sumber "Pakai paket saja" (PT10-3),
 * rahasia kunci perangkat (W6-10c), dan halaman tujuan "Buka paket luring" (W6-9k bagian b).
 * IndexedDB dan cache peramban sungguhan diuji manual di tugas verifikasi akhir (Q17).
 */
class FieldPackDeviceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Kunci perangkat per pengguna: stabil untuk orang yang sama, berbeda antarakun, dan tidak
     * menyebut id pengguna. Tamu tidak mendapat kunci.
     */
    public function test_each_user_gets_a_stable_device_key_that_hides_the_id(): void
    {
        $budi = User::factory()->create();
        $sari = User::factory()->create();
        $kunci = KunciPerangkat::untuk($budi);

        $this->get(route('login'))->assertInertia(fn (AssertableInertia $page) => $page->where('perangkat', null));
        $this->actingAs($budi)->get(route('track'))->assertInertia(fn (AssertableInertia $page) => $page->where('perangkat', ['kunci' => $kunci, 'paket' => false]));

        config(['hiking.field_pack.enabled' => true]);
        $this->actingAs($budi)->get(route('track'))->assertInertia(fn (AssertableInertia $page) => $page->where('perangkat.paket', true));

        $this->assertSame($kunci, KunciPerangkat::untuk($budi->fresh()));
        $this->assertNotSame($kunci, KunciPerangkat::untuk($sari));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{20}$/', $kunci);
        $this->assertNotSame((string) $budi->id, $kunci);
    }

    /**
     * Rahasia kunci perangkat (ruling W6-10c, PT10-9 mutan M7): tanpa rahasia khusus kuncinya
     * mengikuti app.key, jadi bukan hash id yang dapat ditebak siapa pun; dengan rahasia khusus,
     * memutar APP_KEY tidak mengganti kunci, sehingga paket dan jejak yang belum terkirim di
     * perangkat tidak dihapus penjaga ganti akun. Nilai kosong dari .env diperlakukan tanpa rahasia.
     */
    public function test_the_device_key_follows_its_own_secret_not_the_app_key(): void
    {
        $budi = User::factory()->create();
        $kunciA = 'base64:'.base64_encode(str_repeat('a', 32));
        $kunciB = 'base64:'.base64_encode(str_repeat('b', 32));

        config(['hiking.device_key_secret' => null, 'app.key' => $kunciA]);
        $denganA = KunciPerangkat::untuk($budi);
        config(['app.key' => $kunciB]);
        $denganB = KunciPerangkat::untuk($budi);

        $this->assertNotSame($denganA, $denganB, 'Tanpa rahasia khusus, kunci mengikuti app.key.');
        $this->assertNotSame(substr(hash('sha256', 'perangkat:'.$budi->id), 0, 20), $denganA, 'Kunci bukan hash id tanpa rahasia.');

        config(['hiking.device_key_secret' => '']);
        $this->assertSame($denganB, KunciPerangkat::untuk($budi), 'Rahasia kosong dari .env berarti tanpa rahasia khusus.');

        config(['hiking.device_key_secret' => 'rahasia-perangkat-uji']);
        $rahasiaB = KunciPerangkat::untuk($budi);
        config(['app.key' => $kunciA]);

        $this->assertSame($rahasiaB, KunciPerangkat::untuk($budi), 'Dengan rahasia khusus, memutar APP_KEY tidak mengganti kunci.');
        $this->assertNotSame($denganA, $rahasiaB);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{20}$/', $rahasiaB);
    }

    /**
     * Paket "Siap luring" hanya bila setiap bagian manifest ada dan hash bacaan ulangnya cocok;
     * yang kurang disebut dengan namanya. Ruang dianggap cukup bila muat dua versi, karena versi
     * lama bertahan sampai versi baru lolos.
     *
     * Diubah dari brief (task-10-tambahan.md):
     * - ruangCukup tanpa laporan kuota bernilai null, bukan true (PT10-6, R-006, R-007): tidak
     *   diketahui bukan cukup;
     * - angka dan satuan dipisah spasi tak terputus, dan ukuran tepat di bawah 1 MB ditulis MB, bukan
     *   "1024 kB" (PT10-17);
     * - label bagian dinamis menyebut laporan komunitas (PT10-15);
     * - bagian yang tersimpan tidak utuh punya kalimatnya sendiri (PT10-13).
     */
    public function test_a_pack_is_ready_only_when_every_section_matches(): void
    {
        $manifest = ['bagian' => [['nama' => 'lapangan', 'sha256' => 'aa'], ['nama' => 'rencana', 'sha256' => 'bb'], ['nama' => 'dinamis', 'sha256' => 'cc']]];

        [$utuh, $kurang, $kalimat, $tidakUtuh, $cukup, $sempit, $tanpaLaporan, $ukuranKecil, $ukuranBesar, $ukuranBatas] = $this->periksa([
            ['bagianBelumUtuh', [$manifest, [['nama' => 'dinamis', 'sha256' => 'cc'], ['nama' => 'lapangan', 'sha256' => 'aa'], ['nama' => 'rencana', 'sha256' => 'bb']]]],
            ['bagianBelumUtuh', [$manifest, [['nama' => 'lapangan', 'sha256' => 'aa'], ['nama' => 'rencana', 'sha256' => 'zz']]]],
            ['kalimatBelumLengkap', [['rencana', 'dinamis']]],
            ['kalimatTidakUtuh', [['lapangan']]],
            ['ruangCukup', [50_000, 1_000_000, 900_000]],
            ['ruangCukup', [50_000, 1_000_000, 950_000]],
            ['ruangCukup', [50_000, null, null]],
            ['teksUkuran', [48_300]],
            ['teksUkuran', [3_250_000]],
            ['teksUkuran', [1_048_200]],
        ]);

        $this->assertSame([], $utuh);
        $this->assertSame(['rencana', 'dinamis'], $kurang);
        $this->assertSame('Belum lengkap: rencana, potret status, cuaca, dan laporan. Coba lagi selagi ada sinyal.', $kalimat);
        $this->assertSame('Belum lengkap: jalur dan pos belum tersimpan utuh di perangkat ini. Coba lagi untuk mengunduh ulang.', $tidakUtuh);
        $this->assertSame([true, false, null], [$cukup, $sempit, $tanpaLaporan]);
        $this->assertSame(["47\u{00A0}kB", "3,1\u{00A0}MB", "1\u{00A0}MB"], [$ukuranKecil, $ukuranBesar, $ukuranBatas]);
    }

    /**
     * Kalimat ruang di setiap cabang (PT10-6, butir 2): cukup, kurang (unduhan tidak ditawarkan,
     * "periksa lagi" sesudah ruang dikosongkan), dan tidak dilaporkan peramban. Yang terakhir tidak
     * pernah disebut cukup; unduhan tetap boleh dan ruang yang habis disebut saat terjadi.
     */
    public function test_an_unreported_quota_is_never_called_enough(): void
    {
        [$cukup, $kurang, $tanpaPemakaian, $tanpaPerkiraan] = $this->periksa([
            ['penilaianRuang', [48_300, ['quota' => 1_000_000, 'usage' => 100_000]]],
            ['penilaianRuang', [48_300, ['quota' => 1_000_000, 'usage' => 950_000]]],
            ['penilaianRuang', [48_300, ['quota' => 1_000_000]]],
            ['penilaianRuang', [48_300, null]],
        ]);

        $this->assertSame(['boleh' => true, 'kalimat' => "Ukuran paket 47\u{00A0}kB. Ruang perangkat cukup."], $cukup);
        $this->assertSame(['boleh' => false, 'kalimat' => "Ukuran paket 47\u{00A0}kB, dan ruang perangkat tidak cukup. Kosongkan ruang dulu, lalu periksa lagi."], $kurang);

        $tidakDilaporkan = ['boleh' => true, 'kalimat' => "Ukuran paket 47\u{00A0}kB. Peramban ini tidak melaporkan sisa ruang, jadi cukup tidaknya belum dapat dipastikan; bila ruang habis saat mengunduh, Memount menyebutnya."];
        $this->assertSame($tidakDilaporkan, $tanpaPemakaian);
        $this->assertSame($tidakDilaporkan, $tanpaPerkiraan);
    }

    /**
     * Kalimat kegagalan menyebut sebabnya (PT10-7, butir 2): sesi habis, trip yang tidak dapat
     * dibuka sesi ini, paket tidak tersedia, batas laju paket-lapangan, galat server, ruang
     * penyimpanan habis, penyimpanan yang diblokir, dan putus jaringan, satu kasus per cabang.
     * Shell luring yang tidak tersimpan dibedakan dari peramban tanpa service worker.
     */
    public function test_each_failure_names_its_cause(): void
    {
        $hasil = $this->node(['periksa' => 'luring/periksa.ts'], <<<'JS'
            const http = [401, 403, 404, 429, 500, 503, 418].map((s) => periksa.kalimatHttp(s));
            const jaringan = 'JARINGAN';
            const galat = [
                new DOMException('penuh', 'QuotaExceededError'),
                new TypeError('Failed to fetch'),
                new DOMException('tertutup', 'InvalidStateError'),
                new Error('Basis data paket sedang dipakai tab lain.'),
            ].map((g) => periksa.kalimatGalatUnduh(g, jaringan));
            const shell = [periksa.kalimatShell('tanpa-sw', 'unduh'), periksa.kalimatShell('gagal', 'unduh'), periksa.kalimatShell('tanpa-sw', 'paket-ada'), periksa.kalimatShell('gagal', 'paket-ada')];
            process.stdout.write(JSON.stringify({ http, galat, shell }));
            JS);

        $this->assertSame([
            'Sesi Anda berakhir. Masuk lagi, lalu siapkan paket.',
            'Trip ini tidak dapat dibuka dengan sesi sekarang. Muat ulang halaman ini.',
            'Paket untuk trip ini tidak tersedia.',
            'Terlalu banyak permintaan paket. Tunggu satu menit, lalu coba lagi.',
            'Server belum dapat menyiapkan paket. Coba lagi sebentar lagi.',
            'Server belum dapat menyiapkan paket. Coba lagi sebentar lagi.',
            'Paket belum dapat disiapkan. Muat ulang halaman ini, lalu coba lagi.',
        ], $hasil['http']);

        $penyimpanan = 'Peramban ini belum dapat menyimpan paket, misalnya karena penyimpanan diblokir atau jendela privat.';
        $this->assertSame(['Ruang penyimpanan peramban habis saat menyimpan paket. Kosongkan ruang, lalu coba lagi.', 'JARINGAN', $penyimpanan, $penyimpanan], $hasil['galat']);

        $tanpaSw = 'Peramban ini tidak menyimpan halaman luring (misalnya jendela privat), jadi paket tidak dapat dibuka tanpa sinyal di sini.';
        $this->assertSame([
            $tanpaSw,
            'Halaman luring belum tersimpan. Muat ulang halaman ini selagi ada sinyal, lalu coba lagi.',
            $tanpaSw,
            'Halaman luring belum tersimpan, jadi paket di perangkat ini belum dapat dibuka tanpa sinyal. Siapkan ulang selagi ada sinyal.',
        ], $hasil['shell']);
    }

    /**
     * iPhone dan iPad yang membuka dari tab peramban diminta memasang ke Layar Utama lebih dulu,
     * karena penyimpanannya terpisah dari aplikasi yang dibuka di jalur (PT10-18 bagian a): termasuk
     * iPad yang melapor UA Macintosh, bukan Mac tanpa layar sentuh, dan bukan aplikasi terpasang.
     */
    public function test_iphone_and_ipad_tabs_are_asked_to_install_first(): void
    {
        $iphone = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';
        $ipadMac = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Safari/605.1.15';
        $android = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Mobile Safari/537.36';

        $this->assertSame([true, true, false, false, false], $this->periksa([
            ['perluLayarUtama', [$iphone, 5, false]],
            ['perluLayarUtama', [$ipadMac, 5, false]],
            ['perluLayarUtama', [$ipadMac, 0, false]],
            ['perluLayarUtama', [$iphone, 5, true]],
            ['perluLayarUtama', [$android, 5, false]],
        ]));
    }

    /**
     * Urutan unduhan (spec v3 bagian 10.2; PT10-9; ruling W6-8d, W6-9b, W6-9g, W6-9i), diuji
     * perilakunya lewat alurUnduhPaket dengan penyimpanan, jaringan, dan shell tiruan:
     * - setiap bagian di-hash sebelum disimpan, dan bagian yang hash-nya salah tidak pernah ditulis;
     * - bagian yang sudah tersimpan utuh tidak diambil lagi, dan bagian yang tersimpan tidak utuh
     *   diambil ulang walau field hash-nya benar (PT10-13);
     * - hash salah: manifest diambil sekali lagi; versi sama berarti kiriman rusak, versi baru
     *   berarti unduhan diulang sekali, dan salah lagi berarti data berubah saat diunduh (W6-8d);
     * - paket dibaca ulang dari penyimpanan sebelum ditulis siap; bacaan ulang yang tidak cocok,
     *   shell luring yang tidak tersimpan, dan jawaban HTTP gagal tidak pernah menulis rekaman siap;
     * - rekaman siap ditulis sekali lewat gantiVersi (W6-9i), sesudah verifikasi dan shell, dan
     *   kunci perangkat disimpan hanya sesudah itu;
     * - sebelum shell disiapkan, kemajuan mengabarkan tahap shell (tinjauan Tugas 10 m-4), dan
     *   kegagalan shell dilempar sebagai GagalShell (I-2).
     *
     * Menggantikan asersi urutan teks brief T10 :110-114 (ruling W6-9i): urutan teks tidak
     * membuktikan perilaku, dan sembilan mutan pra-tinjau selamat darinya.
     */
    public function test_the_download_verifies_before_it_replaces_the_old_version(): void
    {
        $h = $this->node(['periksa' => 'luring/periksa.ts', 'alur' => 'luring/alur.ts'], <<<'JS'
            const manifestDari = (versi, isi) => ({
                versi,
                trip: { id: 7, nama: 'Gede akhir pekan', jalur: 'Gede via Cibodas', gunung: 'Gunung Gede', tanggal: '2026-10-24' },
                bagian: Object.entries(isi).map(([nama, teks]) => ({ nama, url: '/trips/7/paket/' + nama, ukuran: teks.length, sha256: 'h:' + teks })),
                ukuran: 6,
                ambang: { bukaJam: 24 },
                atribusi: [],
            });
            const v1 = manifestDari('v1', { lapangan: 'L1', rencana: 'R1', dinamis: 'D1' });
            const v2 = manifestDari('v2', { lapangan: 'L2', rencana: 'R1', dinamis: 'D2' });

            async function jalankan(manifest, { server, manifestBerikut = [], rusakSaatTulis = null, shell = 'siap', tersimpan = [] }) {
                const log = [];
                const kemajuan = [];
                const toko = new Map(tersimpan.map((b) => [b.versi + '/' + b.nama, b]));
                const alat = {
                    ambilManifest: async () => {
                        log.push('manifest');
                        return manifestBerikut.shift();
                    },
                    ambilBagian: async (url) => {
                        const nama = url.split('/').pop();
                        log.push('ambil:' + nama);
                        const isi = server[nama];
                        return typeof isi === 'number' ? { ok: false, status: isi, text: async () => '' } : { ok: true, status: 200, text: async () => isi };
                    },
                    sha256: async (teks) => 'h:' + teks,
                    bacaBagian: async (tripId, versi) => [...toko.values()].filter((b) => b.tripId === tripId && b.versi === versi),
                    tulisBagian: async (r) => {
                        log.push('tulis:' + r.nama);
                        toko.set(r.versi + '/' + r.nama, { ...r, teks: rusakSaatTulis === r.nama ? r.teks + '!' : r.teks });
                    },
                    gantiVersi: async (r) => log.push('ganti:' + r.versi + ':' + r.siap),
                    pastikanShell: async () => {
                        log.push('shell');
                        return shell;
                    },
                    simpanKunci: () => log.push('kunci'),
                    sekarang: () => new Date('2026-10-20T01:02:03.000Z'),
                    periksa,
                };

                try {
                    const r = await alur.alurUnduhPaket(manifest, alat, (k) => {
                        if (k.tahap === 'shell') {
                            log.push('kabar-shell');
                        }
                        kemajuan.push(k.tahap === 'shell' ? 'shell' : k.selesai + '/' + k.jumlah);
                    });
                    return { versi: r.versi, siap: r.siap, diverifikasi: r.diverifikasi, log, kemajuan };
                } catch (e) {
                    if (e instanceof alur.GagalShell) {
                        log.push('GagalShell:' + e.hasil);
                    }
                    return { galat: e instanceof alur.GagalPaket ? e.message : 'bukan GagalPaket: ' + String(e), status: e.status ?? null, log };
                }
            }

            const utuh = { lapangan: 'L1', rencana: 'R1', dinamis: 'D1' };
            const simpanan = (nama, teks, sha) => ({ tripId: 7, versi: 'v1', nama, teks, sha256: sha });

            process.stdout.write(JSON.stringify({
                sukses: await jalankan(v1, { server: utuh }),
                lanjut: await jalankan(v1, { server: utuh, tersimpan: [simpanan('lapangan', 'L1', 'h:L1'), simpanan('rencana', 'Rx', 'h:R1')] }),
                hashSalah: await jalankan(v1, { server: { ...utuh, lapangan: 'L-rusak' }, manifestBerikut: [v1] }),
                versiBaru: await jalankan(v1, { server: { lapangan: 'L2', rencana: 'R1', dinamis: 'D2' }, manifestBerikut: [v2] }),
                berubahLagi: await jalankan(v1, { server: { ...utuh, lapangan: 'L3' }, manifestBerikut: [v2] }),
                bacaUlang: await jalankan(v1, { server: utuh, rusakSaatTulis: 'dinamis' }),
                shellGagal: await jalankan(v1, { server: utuh, shell: 'gagal' }),
                tanpaSw: await jalankan(v1, { server: utuh, shell: 'tanpa-sw' }),
                batasLaju: await jalankan(v1, { server: { ...utuh, rencana: 429 } }),
                sesiHabis: await jalankan(v1, { server: { ...utuh, lapangan: 401 } }),
            }));
            JS);

        $this->assertSame([
            'versi' => 'v1', 'siap' => true, 'diverifikasi' => '2026-10-20T01:02:03.000Z',
            'log' => ['ambil:lapangan', 'tulis:lapangan', 'ambil:rencana', 'tulis:rencana', 'ambil:dinamis', 'tulis:dinamis', 'kabar-shell', 'shell', 'ganti:v1:true', 'kunci'],
            'kemajuan' => ['0/3', '1/3', '2/3', '3/3', 'shell'],
        ], $h['sukses']);

        $this->assertSame(['ambil:rencana', 'tulis:rencana', 'ambil:dinamis', 'tulis:dinamis', 'kabar-shell', 'shell', 'ganti:v1:true', 'kunci'], $h['lanjut']['log'], 'Bagian utuh dilewati; bagian yang tersimpan tidak utuh diambil ulang.');

        $this->assertSame(['galat' => 'Belum lengkap: jalur dan pos. Coba lagi selagi ada sinyal.', 'status' => null, 'log' => ['ambil:lapangan', 'manifest']], $h['hashSalah']);

        $this->assertSame('v2', $h['versiBaru']['versi']);
        $this->assertSame(['ambil:lapangan', 'manifest', 'ambil:lapangan', 'tulis:lapangan', 'ambil:rencana', 'tulis:rencana', 'ambil:dinamis', 'tulis:dinamis', 'kabar-shell', 'shell', 'ganti:v2:true', 'kunci'], $h['versiBaru']['log']);

        $this->assertSame(['galat' => 'Data paket berubah saat diunduh. Coba lagi.', 'status' => null, 'log' => ['ambil:lapangan', 'manifest', 'ambil:lapangan']], $h['berubahLagi']);

        $this->assertSame('Belum lengkap: potret status, cuaca, dan laporan belum tersimpan utuh di perangkat ini. Coba lagi untuk mengunduh ulang.', $h['bacaUlang']['galat']);
        $this->assertSame('Halaman luring belum tersimpan. Muat ulang halaman ini selagi ada sinyal, lalu coba lagi.', $h['shellGagal']['galat']);
        $this->assertSame('Peramban ini tidak menyimpan halaman luring (misalnya jendela privat), jadi paket tidak dapat dibuka tanpa sinyal di sini.', $h['tanpaSw']['galat']);
        // Kegagalan shell dilempar sebagai GagalShell beserta hasilnya (tinjauan Tugas 10 I-2), supaya
        // pintu paket tidak menyebut paket lama "tetap siap luring" karenanya.
        $this->assertSame(['GagalShell:gagal', 'GagalShell:tanpa-sw'], [end($h['shellGagal']['log']), end($h['tanpaSw']['log'])]);
        $this->assertNotContains('GagalShell:gagal', $h['batasLaju']['log']);
        $this->assertSame(['galat' => 'Terlalu banyak permintaan paket. Tunggu satu menit, lalu coba lagi.', 'status' => 429, 'log' => ['ambil:lapangan', 'tulis:lapangan', 'ambil:rencana']], $h['batasLaju']);
        $this->assertSame(['Sesi Anda berakhir. Masuk lagi, lalu siapkan paket.', 401], [$h['sesiHabis']['galat'], $h['sesiHabis']['status']]);

        foreach (['hashSalah', 'berubahLagi', 'bacaUlang', 'shellGagal', 'tanpaSw', 'batasLaju', 'sesiHabis'] as $gagal) {
            foreach ($h[$gagal]['log'] as $langkah) {
                $this->assertDoesNotMatchRegularExpression('/^(ganti|kunci)/', $langkah, "Cabang {$gagal} tidak boleh menulis rekaman siap atau menyimpan kunci.");
            }
        }

        $this->assertNotContains('shell', $h['bacaUlang']['log'], 'Shell baru disiapkan sesudah bacaan ulang lolos.');
    }

    /**
     * Pemeriksaan shell luring (ruling W6-9b, W6-9g): /offline yang tersimpan memuat penanda
     * memount-shell-luring, skrip yang dirujuknya tersimpan, dan penanda shell paket ada. Penanda
     * yang dibaca halaman sama dengan yang ditulis service worker, dan path skrip dibaca dari
     * halaman luring yang sungguh dirender.
     */
    public function test_the_offline_shell_check_needs_the_page_its_script_and_the_marker(): void
    {
        $h = $this->node(['periksa' => 'luring/periksa.ts'], <<<'JS'
            const halaman = (meta) => '<!DOCTYPE html><html><head>' + (meta ? '<meta name="memount-shell-luring" content="/build/assets/shell-abc.js">' : '') + '</head><body></body></html>';
            const tembolok = (isi) => ({ match: async (u) => (Object.hasOwn(isi, u) ? new Response(isi[u]) : undefined) });
            const lengkap = { '/offline': halaman(true), '/build/assets/shell-abc.js': 'x', [periksa.PENANDA_SHELL_PAKET]: '' };
            const tanpa = (kunci) => Object.fromEntries(Object.entries(lengkap).filter(([k]) => k !== kunci));
            const hasil = {
                lengkap: await periksa.periksaShellLuring(tembolok(lengkap)),
                tanpaMeta: await periksa.periksaShellLuring(tembolok({ ...lengkap, '/offline': halaman(false) })),
                tanpaSkrip: await periksa.periksaShellLuring(tembolok(tanpa('/build/assets/shell-abc.js'))),
                tanpaPenanda: await periksa.periksaShellLuring(tembolok(tanpa(periksa.PENANDA_SHELL_PAKET))),
                tanpaHalaman: await periksa.periksaShellLuring(tembolok(tanpa('/offline'))),
                tanpaCache: await periksa.periksaShellLuring(undefined),
                melempar: await periksa.periksaShellLuring({ match: async () => { throw new Error('SecurityError'); } }),
                penanda: periksa.PENANDA_SHELL_PAKET,
                bukanHalaman: periksa.skripShellDari('<p>Luring</p>'),
            };
            process.stdout.write(JSON.stringify(hasil));
            JS);

        $this->assertSame([true, false, false, false, false, false, false], [$h['lengkap'], $h['tanpaMeta'], $h['tanpaSkrip'], $h['tanpaPenanda'], $h['tanpaHalaman'], $h['tanpaCache'], $h['melempar']]);
        $this->assertNull($h['bukanHalaman']);

        $this->assertSame(1, preg_match("/const PENANDA_PAKET = '([^']+)';/", File::get(resource_path('views/sw.blade.php')), $sw));
        $this->assertSame($sw[1], $h['penanda'], 'Penanda yang diperiksa halaman sama dengan yang ditulis service worker.');

        config(['hiking.field_pack.enabled' => true]);
        $html = (string) $this->get('/offline')->assertOk()->getContent();
        $skrip = $this->node(['periksa' => 'luring/periksa.ts'], 'process.stdout.write(JSON.stringify(periksa.skripShellDari('.json_encode($html).')));');

        $this->assertSame(AsetLuring::entriShell(), $skrip, 'Path skrip dibaca dari halaman luring yang sungguh dirender.');
    }

    /**
     * Pesan simpan-shell-paket ke service worker (ruling W6-10d): dikirim ke registrasi aktif dari
     * serviceWorker.ready, bukan ke controller yang bernilai null sesudah muat ulang paksa; "tanpa
     * service worker" hanya bila API tidak ada atau ready tidak selesai dalam batasnya; jawaban
     * { ok: false } dan diam sampai batas adalah gagal; dan controllerchange selama menunggu (deploy)
     * mengirim ulang tepat sekali ke controller baru.
     */
    public function test_the_service_worker_message_goes_through_ready_and_is_resent_once(): void
    {
        $h = $this->node(['alur' => 'luring/alur.ts'], <<<'JS'
            const pekerja = (jawaban) => {
                const kiriman = [];
                return {
                    kiriman,
                    postMessage(data, transfer) {
                        kiriman.push(data);
                        if (jawaban !== undefined) {
                            setTimeout(() => transfer[0].postMessage(jawaban), 1);
                        }
                    },
                };
            };
            const wadah = (ready, controller = null) => {
                const pendengar = new Set();
                return {
                    controller,
                    ready,
                    pendengar,
                    addEventListener: (jenis, f) => jenis === 'controllerchange' && pendengar.add(f),
                    removeEventListener: (jenis, f) => pendengar.delete(f),
                    ganti(baru) {
                        this.controller = baru;
                        [...pendengar].forEach((f) => f());
                    },
                };
            };

            const aktifA = pekerja({ ok: true });
            const wA = wadah(Promise.resolve({ active: aktifA }), null);
            const readyTanpaController = await alur.siapkanShellPaket(wA, 500);

            const tanpaApi = await alur.siapkanShellPaket(undefined, 500);
            const readyDiam = await alur.siapkanShellPaket(wadah(new Promise(() => {})), 30);
            const tanpaAktif = await alur.siapkanShellPaket(wadah(Promise.resolve({ active: null })), 30);
            const tolak = await alur.siapkanShellPaket(wadah(Promise.resolve({ active: pekerja({ ok: false }) })), 500);
            const diam = await alur.siapkanShellPaket(wadah(Promise.resolve({ active: pekerja(undefined) })), 40);

            const lama = pekerja(undefined);
            const baru = pekerja({ ok: true });
            const ketiga = pekerja({ ok: true });
            const wG = wadah(Promise.resolve({ active: lama }), lama);
            const janji = alur.siapkanShellPaket(wG, 300);
            await new Promise((r) => setTimeout(r, 10));
            wG.ganti(baru);
            wG.ganti(ketiga);
            const sesudahGanti = await janji;

            process.stdout.write(JSON.stringify({
                readyTanpaController, kirimanA: aktifA.kiriman, pendengarA: wA.pendengar.size,
                tanpaApi, readyDiam, tanpaAktif, tolak, diam,
                sesudahGanti, kirimanLama: lama.kiriman.length, kirimanBaru: baru.kiriman.length, kirimanKetiga: ketiga.kiriman.length, pendengarG: wG.pendengar.size,
            }));
            JS);

        $this->assertSame('tersimpan', $h['readyTanpaController'], 'controller null sesudah muat ulang paksa bukan berarti tanpa service worker.');
        $this->assertSame([['jenis' => 'simpan-shell-paket']], $h['kirimanA']);
        $this->assertSame(['tanpa-sw', 'tanpa-sw', 'tanpa-sw'], [$h['tanpaApi'], $h['readyDiam'], $h['tanpaAktif']]);
        $this->assertSame(['gagal', 'gagal'], [$h['tolak'], $h['diam']]);
        $this->assertSame('tersimpan', $h['sesudahGanti'], 'Pesan yang hilang saat deploy dikirim ulang ke controller baru.');
        $this->assertSame([1, 1, 0], [$h['kirimanLama'], $h['kirimanBaru'], $h['kirimanKetiga']], 'Dikirim ulang tepat sekali.');
        $this->assertSame([0, 0], [$h['pendengarA'], $h['pendengarG']], 'Pendengar controllerchange dilepas sesudah selesai.');
    }

    /**
     * Penyembuhan diri shell paket (ruling W6-9n) dan kesiapan shell saat mengunduh (W6-9b, W6-9g):
     * muat daring yang melihat paket memeriksa shell lebih dulu; shell yang utuh tidak memicu pesan
     * apa pun, shell yang hilang disiapkan sekali lalu diperiksa lagi, dan hasilnya hanya "siap"
     * bila pemeriksaan sesudahnya lulus. Saat mengunduh, pesan dikirim lebih dulu lalu diperiksa.
     */
    public function test_a_missing_offline_shell_is_prepared_once_and_a_present_one_is_left_alone(): void
    {
        $h = $this->node(['alur' => 'luring/alur.ts'], <<<'JS'
            const alat = (urutanPeriksa, hasilSiapkan, log) => ({
                periksa: async () => {
                    log.push('periksa');
                    return urutanPeriksa.shift();
                },
                siapkan: async () => {
                    log.push('siapkan');
                    return hasilSiapkan;
                },
            });
            const pulih = async (urutan, siapkan) => {
                const log = [];
                return { hasil: await alur.pulihkanShell(alat(urutan, siapkan, log)), log };
            };
            const pastikan = async (urutan, siapkan) => {
                const log = [];
                return { hasil: await alur.pastikanShell(alat(urutan, siapkan, log)), log };
            };
            process.stdout.write(JSON.stringify({
                ada: await pulih([true], 'tersimpan'),
                pulih: await pulih([false, true], 'tersimpan'),
                tetapHilang: await pulih([false, false], 'tersimpan'),
                tanpaSw: await pulih([false], 'tanpa-sw'),
                unduh: await pastikan([true], 'tersimpan'),
                unduhDitolak: await pastikan([false], 'gagal'),
            }));
            JS);

        $this->assertSame(['hasil' => 'siap', 'log' => ['periksa']], $h['ada']);
        $this->assertSame(['hasil' => 'siap', 'log' => ['periksa', 'siapkan', 'periksa']], $h['pulih']);
        $this->assertSame(['hasil' => 'gagal', 'log' => ['periksa', 'siapkan', 'periksa']], $h['tetapHilang']);
        $this->assertSame(['hasil' => 'tanpa-sw', 'log' => ['periksa', 'siapkan']], $h['tanpaSw']);
        $this->assertSame(['hasil' => 'siap', 'log' => ['siapkan', 'periksa']], $h['unduh']);
        $this->assertSame(['hasil' => 'gagal', 'log' => ['siapkan', 'periksa']], $h['unduhDitolak']);
    }

    /**
     * Pemeriksaan ulang shell setiap service worker pengendali berganti (ruling W6-10e, tinjauan
     * Tugas 10 I-1), berurutan seperti deploy yang berbalapan: unduhan menerima balasan ok dan shell
     * lulus diperiksa; lalu controllerchange, cache lama terhapus, pemeriksaan ulang gagal, shell
     * disiapkan ke pengendali baru, dan siap. Deploy berikutnya dengan penyimpanan yang gagal
     * menurunkan keadaannya. Pendengarnya menetap sesudah kejadian pertama dan dilepas pemiliknya.
     */
    public function test_every_controller_change_rechecks_the_offline_shell(): void
    {
        $h = $this->node(['alur' => 'luring/alur.ts'], <<<'JS'
            const log = [];
            const pekerja = (nama, jawaban) => ({
                postMessage(data, transfer) {
                    log.push('pesan:' + nama);
                    setTimeout(() => transfer[0].postMessage(jawaban), 1);
                },
            });
            const pendengar = new Set();
            const w = {
                controller: null,
                ready: Promise.resolve({ active: pekerja('A', { ok: true }) }),
                addEventListener: (jenis, f) => jenis === 'controllerchange' && pendengar.add(f),
                removeEventListener: (jenis, f) => pendengar.delete(f),
                ganti(baru) {
                    this.controller = baru;
                    this.ready = Promise.resolve({ active: baru });
                    [...pendengar].forEach((f) => f());
                },
            };
            let cacheUtuh = true;
            const alat = {
                siapkan: async () => {
                    const hasil = await alur.siapkanShellPaket(w, 300);
                    if (hasil === 'tersimpan' && w.pulihkanCache) {
                        cacheUtuh = true;
                    }
                    return hasil;
                },
                periksa: async () => {
                    log.push('periksa:' + (cacheUtuh ? 'ada' : 'hilang'));
                    return cacheUtuh;
                },
            };
            const tunggu = (ms) => new Promise((r) => setTimeout(r, ms));
            const hasil = [];

            const unduh = await alur.pastikanShell(alat);
            const lepas = alur.pantauShellPaket(w, alat, { mulai: () => log.push('mulai'), hasil: (h) => hasil.push(h) });

            // Deploy: pekerja baru aktif, cache lama (berpenanda) terhapus; pekerja baru menyimpan shell paket.
            cacheUtuh = false;
            w.pulihkanCache = true;
            w.ganti(pekerja('B', { ok: true }));
            await tunggu(30);

            // Deploy berikutnya: cache terhapus lagi dan penyimpanan di pekerja baru gagal.
            cacheUtuh = false;
            w.pulihkanCache = false;
            w.ganti(pekerja('C', { ok: false }));
            await tunggu(30);

            const pendengarSebelumLepas = pendengar.size;
            lepas();
            process.stdout.write(JSON.stringify({ unduh, hasil, log, pendengarSebelumLepas, pendengarSesudahLepas: pendengar.size }));
            JS);

        $this->assertSame('siap', $h['unduh']);
        $this->assertSame(['siap', 'gagal'], $h['hasil'], 'Pemeriksaan ulang memulihkan sesudah deploy pertama dan menurunkan sesudah deploy kedua.');
        $this->assertSame([
            'pesan:A', 'periksa:ada',
            'mulai', 'periksa:hilang', 'pesan:B', 'periksa:ada',
            'mulai', 'periksa:hilang', 'pesan:C', 'periksa:hilang',
        ], $h['log']);
        $this->assertSame([1, 0], [$h['pendengarSebelumLepas'], $h['pendengarSesudahLepas']], 'Pendengar menetap sesudah kejadian pertama dan dilepas pemiliknya.');
    }

    /**
     * Pemeriksaan ulang hanya untuk paket yang dipegang (ruling W6-10e): pergantian pengendali saat
     * `perlu` false (pintu belum memegang paket, atau aksi pendaki sedang berjalan) tetap memanggil
     * `mulai`, tetapi tidak memeriksa dan TIDAK menyuruh service worker menyimpan shell paket. Bukti
     * peramban putaran perbaikan 1: tanpa syarat ini kunjungan pertama ke trip tanpa paket menyimpan
     * shell paket beserta penandanya. Begitu `perlu` true, kejadian berikutnya memulihkan seperti biasa.
     */
    public function test_a_controller_change_without_a_held_pack_does_not_store_the_shell(): void
    {
        $h = $this->node(['alur' => 'luring/alur.ts'], <<<'JS'
            const log = [];
            const pekerja = (nama) => ({
                postMessage(data, transfer) {
                    log.push('pesan:' + nama);
                    setTimeout(() => transfer[0].postMessage({ ok: true }), 1);
                },
            });
            const pendengar = new Set();
            const w = {
                controller: null,
                ready: Promise.resolve({ active: pekerja('A') }),
                addEventListener: (jenis, f) => jenis === 'controllerchange' && pendengar.add(f),
                removeEventListener: (jenis, f) => pendengar.delete(f),
                ganti(nama) {
                    this.controller = pekerja(nama);
                    this.ready = Promise.resolve({ active: this.controller });
                    [...pendengar].forEach((f) => f());
                },
            };
            let tersimpan = false;
            const alat = {
                siapkan: async () => {
                    const hasil = await alur.siapkanShellPaket(w, 300);
                    tersimpan = hasil === 'tersimpan';
                    return hasil;
                },
                periksa: async () => {
                    log.push('periksa:' + (tersimpan ? 'ada' : 'hilang'));
                    return tersimpan;
                },
            };
            const tunggu = (ms) => new Promise((r) => setTimeout(r, ms));
            const hasil = [];
            let perlu = false;

            const lepas = alur.pantauShellPaket(w, alat, { mulai: () => log.push('mulai'), perlu: () => perlu, hasil: (h) => hasil.push(h) });
            w.ganti('B');
            await tunggu(30);
            const tanpaPaket = { log: [...log], hasil: [...hasil], tersimpan };

            perlu = true;
            w.ganti('C');
            await tunggu(30);
            lepas();
            process.stdout.write(JSON.stringify({ tanpaPaket, log, hasil, tersimpan }));
            JS);

        $this->assertSame(['log' => ['mulai'], 'hasil' => [], 'tersimpan' => false], $h['tanpaPaket'], 'Tanpa paket yang dipegang, shell tidak diperiksa dan tidak disimpan.');
        $this->assertSame(['mulai', 'mulai', 'periksa:hilang', 'pesan:C', 'periksa:ada'], $h['log']);
        $this->assertSame(['siap'], $h['hasil']);
    }

    /**
     * Urutan deploy ASLI di Chrome (ruling W6-10g, tinjauan Tugas 10 I-A; bukti peninjau rv10f1c dan
     * rv10f1d): balasan ok dari pekerja lama, lalu controllerchange saat pekerja baru masih
     * "activating" dengan cache lama MASIH utuh; handler activate baru menghapus cache lama sesudahnya,
     * lalu keadaannya "activated". Pemeriksaan ditahan sampai pengendali selesai diaktifkan, jadi gagal
     * atas cache yang memang sudah hilang, lalu memulihkan ke pekerja baru. Deploy kedua dengan
     * penyimpanan yang gagal menurunkan keadaannya. Pemeriksaan ulang aksi menurut hitungan pergantian
     * (shellTerbaru lewat pulihkanShellSesudahAktif) menunggu dengan cara yang sama. Test urutan terbalik
     * (cache sudah hilang saat controllerchange) di atas tetap ada.
     */
    public function test_the_recheck_waits_until_the_new_controller_has_activated(): void
    {
        $h = $this->node(['alur' => 'luring/alur.ts'], <<<'JS'
            const log = [];
            // Cache Storage tiruan: nama cache -> berisi penanda shell paket. caches.match mencari di semua cache.
            const cache = new Map([['shell-a', false]]);
            const adaPenanda = () => [...cache.values()].some(Boolean);
            const pekerja = (nama, namaCache, ok) => {
                const p = {
                    state: 'activated',
                    dengar: new Set(),
                    addEventListener: (jenis, f) => jenis === 'statechange' && p.dengar.add(f),
                    removeEventListener: (jenis, f) => p.dengar.delete(f),
                    ubahKe(baru) {
                        p.state = baru;
                        [...p.dengar].forEach((f) => f());
                    },
                    postMessage(data, transfer) {
                        log.push('pesan:' + nama + ':' + p.state);
                        setTimeout(() => {
                            if (ok) {
                                cache.set(namaCache, true);
                            }
                            transfer[0].postMessage({ ok });
                        }, 1);
                    },
                };
                return p;
            };
            const A = pekerja('A', 'shell-a', true);
            const pendengar = new Set();
            const w = {
                controller: A,
                ready: Promise.resolve({ active: A }),
                addEventListener: (jenis, f) => jenis === 'controllerchange' && pendengar.add(f),
                removeEventListener: (jenis, f) => pendengar.delete(f),
            };
            const deploy = (baru, namaCache) => {
                cache.set(namaCache, false);
                baru.state = 'activating';
                w.controller = baru;
                w.ready = Promise.resolve({ active: baru });
                log.push('controllerchange');
                [...pendengar].forEach((f) => f());
                setTimeout(() => {
                    [...cache.keys()].filter((k) => k !== namaCache).forEach((k) => cache.delete(k));
                    log.push('cache lama dihapus');
                    baru.ubahKe('activated');
                }, 20);
            };
            const alat = {
                siapkan: () => alur.siapkanShellPaket(w, 300),
                periksa: async () => {
                    const ada = adaPenanda();
                    log.push('periksa:' + (ada ? 'ada' : 'hilang'));
                    return ada;
                },
            };
            const tunggu = (ms) => new Promise((r) => setTimeout(r, ms));

            const unduh = await alur.pastikanShell(alat);
            const lepas = alur.pantauShellPaket(w, alat, { mulai: () => log.push('mulai'), hasil: (h) => log.push('hasil:' + h) });

            deploy(pekerja('B', 'shell-b', true), 'shell-b');
            await tunggu(80);
            deploy(pekerja('C', 'shell-c', false), 'shell-c');
            await tunggu(80);
            lepas();

            const sebelumAksi = log.length;
            deploy(pekerja('D', 'shell-d', true), 'shell-d');
            const aksi = await alur.shellTerbaru('siap', 0, () => 1, () => alur.pulihkanShellSesudahAktif(w, alat));
            await tunggu(20);

            process.stdout.write(JSON.stringify({ unduh, log: log.slice(0, sebelumAksi), logAksi: log.slice(sebelumAksi), aksi, statechange: [A, w.controller].map((p) => p.dengar.size) }));
            JS);

        $this->assertSame('siap', $h['unduh']);
        $this->assertSame([
            'pesan:A:activated', 'periksa:ada',
            'controllerchange', 'mulai', 'cache lama dihapus', 'periksa:hilang', 'pesan:B:activated', 'periksa:ada', 'hasil:siap',
            'controllerchange', 'mulai', 'cache lama dihapus', 'periksa:hilang', 'pesan:C:activated', 'periksa:hilang', 'hasil:gagal',
        ], $h['log'], 'Pemeriksaan ulang menunggu pengendali baru selesai diaktifkan.');
        $this->assertSame(['controllerchange', 'cache lama dihapus', 'periksa:hilang', 'pesan:D:activated', 'periksa:ada'], $h['logAksi']);
        $this->assertSame('siap', $h['aksi']);
        $this->assertSame([0, 0], $h['statechange'], 'Pendengar statechange dilepas sesudah penantian.');
    }

    /**
     * Keputusan pintu paket sesudah pengendali berganti (ruling W6-10e, tinjauan Tugas 10 m-C dan
     * m-D), dijalankan lewat pantauShellPaket dan toko yang sama dengan PintuPaket: "Siap luring"
     * diturunkan bila shell gagal dan dinaikkan lagi bila pulih; "tetap siap luring" sesudah perbarui
     * gagal juga diturunkan, dengan pesan perbarui tetap disebut (m-C); kegagalan tanpa klaim siap
     * (404) dan aksi yang berjalan tidak memeriksa shell sama sekali. Toko mengabarkan setiap tulisan
     * ke langganannya sampai dilepas.
     */
    public function test_the_pack_door_rechecks_every_state_that_claims_offline_readiness(): void
    {
        $h = $this->node(['alur' => 'luring/alur.ts'], <<<'JS'
            const lama = { tripId: 1, versi: 'v1', siap: true, diverifikasi: '2026-10-10T00:00:00.000Z', manifest: {} };
            const pendengar = new Set();
            const w = {
                controller: null,
                ready: Promise.resolve({ active: null }),
                addEventListener: (jenis, f) => jenis === 'controllerchange' && pendengar.add(f),
                removeEventListener: (jenis, f) => pendengar.delete(f),
            };
            let shellOk = false;
            const log = [];
            const alat = {
                siapkan: async () => {
                    log.push('siapkan');
                    return shellOk ? 'tersimpan' : 'gagal';
                },
                periksa: async () => {
                    log.push('periksa');
                    return shellOk;
                },
            };
            const tunggu = (ms) => new Promise((r) => setTimeout(r, ms));
            const toko = alur.buatToko({ tahap: 'memeriksa' });
            const dilihat = [];
            const henti = toko.langganan((k) => dilihat.push(k.tahap));
            let berjalan = false;
            let ganti = 0;
            const efek = [];
            const lepas = alur.pantauShellPaket(w, alat, alur.kabarPantauPintu(toko, () => berjalan, {
                ganti: () => {
                    ganti += 1;
                },
                memeriksa: (sedang) => efek.push('memeriksa:' + sedang),
                ubah: (baru) => efek.push('ubah:' + baru.tahap),
            }));
            const langkah = async (nama) => {
                log.length = 0;
                efek.length = 0;
                [...pendengar].forEach((f) => f());
                await tunggu(10);
                return { nama, log: [...log], efek: [...efek], keadaan: toko.ambil() };
            };

            const hasil = [];
            toko.ubah({ tahap: 'siap', paket: lama, menetap: null, pesan: null });
            hasil.push(await langkah('siap, shell hilang'));
            shellOk = true;
            hasil.push(await langkah('belum luring, shell pulih'));
            shellOk = false;
            toko.ubah({ tahap: 'gagal', pesan: 'Terlalu banyak permintaan paket.', lama, sebutLama: true });
            hasil.push(await langkah('tetap siap luring, shell hilang'));
            toko.ubah({ tahap: 'gagal', pesan: 'Paket tidak tersedia lagi.', lama, sebutLama: false });
            hasil.push(await langkah('404 tanpa klaim siap'));
            toko.ubah({ tahap: 'kosong' });
            hasil.push(await langkah('tanpa paket'));
            toko.ubah({ tahap: 'siap', paket: lama, menetap: null, pesan: null });
            berjalan = true;
            hasil.push(await langkah('aksi berjalan'));
            lepas();
            henti();
            toko.ubah({ tahap: 'kosong' });

            const dijaga = [
                { tahap: 'siap', paket: lama, menetap: null, pesan: null },
                { tahap: 'belumLuring', paket: lama, shell: 'gagal', pesan: null },
                { tahap: 'gagal', pesan: 'x', lama, sebutLama: true },
                { tahap: 'gagal', pesan: 'x', lama, sebutLama: false },
                { tahap: 'gagal', pesan: 'x', lama: null, sebutLama: false },
                { tahap: 'mengunduh', kemajuan: { selesai: 0, jumlah: 3 } },
            ].map((k) => alur.paketDijaga(k) === lama);

            process.stdout.write(JSON.stringify({ hasil, ganti, dilihat, dijaga }));
            JS);

        $paket = ['tripId' => 1, 'versi' => 'v1', 'siap' => true, 'diverifikasi' => '2026-10-10T00:00:00.000Z', 'manifest' => []];
        [$turun, $naik, $tetap, $tanpaKlaim, $kosong, $berjalan] = $h['hasil'];

        $this->assertSame(['periksa', 'siapkan', 'periksa'], $turun['log']);
        $this->assertSame(['memeriksa:true', 'memeriksa:false', 'ubah:belumLuring'], $turun['efek']);
        $this->assertEquals(['tahap' => 'belumLuring', 'paket' => $paket, 'shell' => 'gagal', 'pesan' => null], $turun['keadaan'], '"Siap luring" diturunkan bila shell hilang.');
        $this->assertEquals(['tahap' => 'siap', 'paket' => $paket, 'menetap' => null, 'pesan' => null], $naik['keadaan']);
        $this->assertSame(['memeriksa:true', 'memeriksa:false', 'ubah:siap'], $naik['efek']);
        $this->assertEquals(['tahap' => 'belumLuring', 'paket' => $paket, 'shell' => 'gagal', 'pesan' => 'Terlalu banyak permintaan paket.'], $tetap['keadaan'], 'Tinjauan m-C: "tetap siap luring" juga diperiksa ulang.');
        $this->assertSame([[], []], [$tanpaKlaim['log'], $tanpaKlaim['efek']]);
        $this->assertSame('gagal', $tanpaKlaim['keadaan']['tahap']);
        $this->assertSame([[], [], 'kosong'], [$kosong['log'], $kosong['efek'], $kosong['keadaan']['tahap']]);
        $this->assertSame([[], [], 'siap'], [$berjalan['log'], $berjalan['efek'], $berjalan['keadaan']['tahap']]);
        $this->assertSame(6, $h['ganti'], 'Setiap pergantian dihitung, juga saat shell tidak diperiksa.');
        $this->assertSame(['siap', 'belumLuring', 'siap', 'gagal', 'belumLuring', 'gagal', 'kosong', 'siap'], $h['dilihat'], 'Langganan menerima setiap tulisan toko sampai dilepas.');
        $this->assertSame([true, true, true, false, false, false], $h['dijaga']);
    }

    /**
     * Lem keputusan pintu untuk paket lama dan paket yang baru diperiksa (tinjauan Tugas 10 I-2 dan
     * m-D): keadaanPintuLama tidak pernah menyebut paket lama siap atau "tetap siap luring" bila
     * shell tidak lulus, termasuk bila pengendali berganti selama pemeriksaan dan pemeriksaan
     * ulangnya gagal; keadaanPaketDiperiksa menulis siap hanya bila shell terbaru siap, dan
     * shellTerbaru memeriksa ulang selama pengendali terus berganti.
     */
    public function test_the_pack_door_writes_ready_only_from_a_shell_that_passes_last(): void
    {
        $h = $this->node(['alur' => 'luring/alur.ts'], <<<'JS'
            const lama = { tripId: 1, versi: 'v1', siap: true, diverifikasi: '2026-10-10T00:00:00.000Z', manifest: {} };
            const batas = new alur.GagalPaket('Terlalu banyak permintaan paket.', 429);
            const urutan = (...hasil) => {
                const panggil = [];
                return { panggil, pulihkan: async () => (panggil.push('pulihkan'), hasil.shift() ?? 'siap') };
            };
            const lamaKasus = async (galat, pesan, gantiSelama, ...hasil) => {
                let ganti = 0;
                const u = urutan(...hasil);
                const keadaan = await alur.keadaanPintuLama(galat, pesan, lama, true, () => ganti, async () => {
                    const h = await u.pulihkan();
                    if (gantiSelama > 0) {
                        gantiSelama -= 1;
                        ganti += 1;
                    }
                    return h;
                });
                return { keadaan, panggil: u.panggil.length };
            };
            const diperiksa = async (shell, gantiSelama, ...hasil) => {
                let ganti = 0;
                const u = urutan(...hasil);
                const awal = ganti;
                ganti += gantiSelama > 0 ? 1 : 0;
                const keadaan = await alur.keadaanPaketDiperiksa(lama, false, shell, awal, () => ganti, async () => {
                    const h = await u.pulihkan();
                    if (gantiSelama > 1) {
                        gantiSelama -= 1;
                        ganti += 1;
                    }
                    return h;
                });
                return { keadaan: keadaan.tahap === 'belumLuring' ? keadaan.tahap + ':' + keadaan.shell : keadaan.tahap, panggil: u.panggil.length };
            };

            process.stdout.write(JSON.stringify({
                shellGagal: await lamaKasus(new alur.GagalShell('x', 'gagal'), 'x', 0),
                lainLulus: await lamaKasus(batas, batas.message, 0, 'siap'),
                lainGagal: await lamaKasus(batas, batas.message, 0, 'gagal'),
                lainGantiLaluGagal: await lamaKasus(batas, batas.message, 1, 'siap', 'gagal'),
                samaLulus: await lamaKasus(null, '', 0, 'siap'),
                samaGantiLaluGagal: await lamaKasus(null, '', 1, 'siap', 'gagal'),
                samaGantiLaluLulus: await lamaKasus(null, '', 1, 'siap', 'siap'),
                unduhTanpaGanti: await diperiksa('siap', 0),
                unduhGantiLaluGagal: await diperiksa('siap', 1, 'gagal'),
                unduhGantiDuaKali: await diperiksa('siap', 2, 'siap', 'gagal'),
                dimuatTanpaSw: await diperiksa('tanpa-sw', 1),
            }));
            JS);

        $paket = ['tripId' => 1, 'versi' => 'v1', 'siap' => true, 'diverifikasi' => '2026-10-10T00:00:00.000Z', 'manifest' => []];
        $terbaru = 'Paket di perangkat ini sudah versi terbaru.';
        $batas = 'Terlalu banyak permintaan paket.';

        $this->assertEquals(['keadaan' => ['tahap' => 'belumLuring', 'paket' => $paket, 'shell' => 'gagal', 'pesan' => null], 'panggil' => 0], $h['shellGagal'], 'Kegagalan shell saat unduh tidak pernah menjadi "tetap siap luring".');
        $this->assertEquals(['keadaan' => ['tahap' => 'gagal', 'pesan' => $batas, 'lama' => $paket, 'sebutLama' => true], 'panggil' => 1], $h['lainLulus']);
        $this->assertEquals(['tahap' => 'belumLuring', 'paket' => $paket, 'shell' => 'gagal', 'pesan' => $batas], $h['lainGagal']['keadaan']);
        $this->assertEquals(['keadaan' => ['tahap' => 'belumLuring', 'paket' => $paket, 'shell' => 'gagal', 'pesan' => $batas], 'panggil' => 2], $h['lainGantiLaluGagal'], 'Pengendali berganti selama pemeriksaan: shell diperiksa ulang sebelum "tetap siap luring".');
        $this->assertEquals(['keadaan' => ['tahap' => 'siap', 'paket' => $paket, 'menetap' => true, 'pesan' => $terbaru], 'panggil' => 1], $h['samaLulus']);
        $this->assertEquals(['tahap' => 'belumLuring', 'paket' => $paket, 'shell' => 'gagal', 'pesan' => $terbaru], $h['samaGantiLaluGagal']['keadaan']);
        $this->assertSame(['siap', 2], [$h['samaGantiLaluLulus']['keadaan']['tahap'], $h['samaGantiLaluLulus']['panggil']]);
        $this->assertSame(['keadaan' => 'siap', 'panggil' => 0], $h['unduhTanpaGanti']);
        $this->assertSame(['keadaan' => 'belumLuring:gagal', 'panggil' => 1], $h['unduhGantiLaluGagal']);
        $this->assertSame(['keadaan' => 'belumLuring:gagal', 'panggil' => 2], $h['unduhGantiDuaKali'], 'Pengendali yang berganti lagi selama pemeriksaan memicu pemeriksaan berikutnya.');
        $this->assertSame(['keadaan' => 'belumLuring:tanpa-sw', 'panggil' => 0], $h['dimuatTanpaSw']);
    }

    /**
     * Paket lama sesudah memperbarui tanpa versi baru (tinjauan Tugas 10 I-2, W6-9n), satu kasus per
     * cabang: kegagalan shell saat unduh menurunkan paket lama tanpa memeriksa lagi; kegagalan lain
     * menyebut "tetap siap luring" hanya bila shell lulus diperiksa sekarang; versi sama menulis siap
     * dan "sudah versi terbaru" hanya bila shell lulus, dan memulihkan penanda yang hilang lebih dulu.
     */
    public function test_an_old_pack_is_called_ready_only_when_its_shell_passes_now(): void
    {
        $h = $this->node(['alur' => 'luring/alur.ts'], <<<'JS'
            const kasus = async (galat, pesan, hasilPulih) => {
                const log = [];
                const keadaan = await alur.keadaanPaketLama(galat, pesan, async () => {
                    log.push('pulihkan');
                    return hasilPulih;
                });
                return { keadaan, log };
            };
            const lain = new alur.GagalPaket('Terlalu banyak permintaan paket. Tunggu satu menit, lalu coba lagi.', 429);
            process.stdout.write(JSON.stringify({
                shellGagal: await kasus(new alur.GagalShell('Halaman luring belum tersimpan.', 'gagal'), 'x', 'siap'),
                shellTanpaSw: await kasus(new alur.GagalShell('tanpa sw', 'tanpa-sw'), 'x', 'siap'),
                lainShellLulus: await kasus(lain, lain.message, 'siap'),
                lainShellGagal: await kasus(lain, lain.message, 'gagal'),
                samaPenandaHilang: await kasus(null, '', 'gagal'),
                samaPenandaAda: await kasus(null, '', 'siap'),
            }));
            JS);

        $terbaru = 'Paket di perangkat ini sudah versi terbaru.';
        $batas = 'Terlalu banyak permintaan paket. Tunggu satu menit, lalu coba lagi.';

        $this->assertSame(['keadaan' => ['tahap' => 'belumLuring', 'shell' => 'gagal', 'pesan' => null], 'log' => []], $h['shellGagal']);
        $this->assertSame(['tahap' => 'belumLuring', 'shell' => 'tanpa-sw', 'pesan' => null], $h['shellTanpaSw']['keadaan']);
        $this->assertSame(['keadaan' => ['tahap' => 'gagal', 'pesan' => $batas, 'sebutLama' => true], 'log' => ['pulihkan']], $h['lainShellLulus']);
        $this->assertSame(['keadaan' => ['tahap' => 'belumLuring', 'shell' => 'gagal', 'pesan' => $batas], 'log' => ['pulihkan']], $h['lainShellGagal']);
        $this->assertSame(['keadaan' => ['tahap' => 'belumLuring', 'shell' => 'gagal', 'pesan' => $terbaru], 'log' => ['pulihkan']], $h['samaPenandaHilang']);
        $this->assertSame(['keadaan' => ['tahap' => 'siap', 'pesan' => $terbaru], 'log' => ['pulihkan']], $h['samaPenandaAda']);
    }

    /**
     * Tanpa pendaftaran service worker sama sekali, kalimat tanpa service worker ditulis seketika,
     * bukan sesudah batas 60 detik (tinjauan Tugas 10 m-4); pendaftaran yang ada tetapi masih
     * dipasang tetap ditunggu lewat ready.
     */
    public function test_a_page_without_a_service_worker_registration_is_told_at_once(): void
    {
        $h = $this->node(['alur' => 'luring/alur.ts'], <<<'JS'
            const pekerja = { postMessage(data, transfer) { setTimeout(() => transfer[0].postMessage({ ok: true }), 1); } };
            const wadah = (registrasi, ready) => ({
                controller: null,
                ready,
                getRegistration: async () => registrasi,
                addEventListener() {},
                removeEventListener() {},
            });
            const mulai = Date.now();
            const tanpaPendaftaran = await alur.siapkanShellPaket(wadah(undefined, new Promise(() => {})), 5000);
            const lama = Date.now() - mulai;
            let selesaiReady;
            const ready = new Promise((r) => { selesaiReady = r; });
            setTimeout(() => selesaiReady({ active: pekerja }), 40);
            const sedangDipasang = await alur.siapkanShellPaket(wadah({ installing: {} }, ready), 5000);
            process.stdout.write(JSON.stringify({ tanpaPendaftaran, cepat: lama < 1000, sedangDipasang }));
            JS);

        $this->assertSame(['tanpaPendaftaran' => 'tanpa-sw', 'cepat' => true, 'sedangDipasang' => 'tersimpan'], $h);
    }

    /**
     * Kalimat sumber mode lapangan (ruling W6-9e, PT9-14 bagian b, PT10-14, W6-10b), satu kasus
     * per cabang dengan kalimat persis. Halaman daring dibedakan dari shell luring lewat
     * halamanLuring, bukan sumber data: "Pakai paket saja" di halaman daring tidak menyuruh menunggu
     * sinyal yang sudah ada, dan shell menyebut asal dan waktu keadaan sesinya.
     *
     * Diubah ruling W6-FH: kalimat shell berlangsung kini juga menyebut bahwa jejak yang terekam
     * sesudah pendakian diselesaikan atau dibatalkan tidak disimpan (ruling W6-FT,
     * PenerimaanJejak::dalamPendakian).
     */
    public function test_the_field_source_sentence_is_true_in_every_branch(): void
    {
        $h = $this->node(['sumber' => 'luring/kalimatSumber.ts'], <<<'JS'
            const k = (halamanLuring, asal, berlangsung, catatan, dijeda = false) => sumber.teksKeadaanLapangan({ halamanLuring, sumber: asal, berlangsung, dijeda, lama: '2 jam 15 menit', catatan });
            process.stdout.write(JSON.stringify([
                k(false, 'daring', true, null),
                k(false, 'daring', true, null, true),
                k(false, 'daring', false, null),
                k(false, 'paket', true, '9 Okt 14.05 WIB'),
                k(false, 'paket', false, null),
                k(true, 'paket', false, null),
                k(true, 'paket', false, '9 Okt 14.05 WIB'),
                k(true, 'paket', true, '9 Okt 14.05 WIB'),
            ]));
            JS);

        $b = 'Paket luring membantu mengikuti jalur resmi. Bawa cadangan: peta cetak atau perangkat lain.';
        $paketSaja = "Dari paket di perangkat ini (Pakai paket saja). {$b} Matikan Pakai paket saja untuk menyelesaikan pendakian atau kembali ke ruang kerja.";

        $this->assertSame([
            ['keterangan' => 'Berjalan, 2 jam 15 menit bergerak', 'kalimat' => null],
            ['keterangan' => 'Dijeda, 2 jam 15 menit bergerak', 'kalimat' => null],
            ['keterangan' => 'Belum dimulai', 'kalimat' => 'Pendakian ini belum dimulai. Mulai dari tab Mendaki atau ruang kerja trip; sebelum itu peta dan pos dapat ditinjau di sini.'],
            ['keterangan' => 'Berjalan, 2 jam 15 menit bergerak', 'kalimat' => $paketSaja],
            ['keterangan' => 'Belum dimulai', 'kalimat' => $paketSaja],
            ['keterangan' => 'Keadaan belum tercatat', 'kalimat' => "Dari paket luring. {$b} Keadaan pendakian ini belum tercatat di perangkat ini, jadi tiba di pos dan jeda belum dapat dicatat dari sini; pos dan peta dapat ditinjau. Bila pendakian sudah dimulai, buka mode lapangan sekali saat ada sinyal supaya keadaannya tercatat di perangkat ini."],
            ['keterangan' => 'Belum dimulai', 'kalimat' => "Dari paket luring. {$b} Menurut catatan terakhir di perangkat ini (9 Okt 14.05 WIB), pendakian ini belum dimulai. Mulai dari tab Mendaki saat ada sinyal; sebelum itu pos dan peta dapat ditinjau di sini."],
            ['keterangan' => 'Berjalan, 2 jam 15 menit bergerak', 'kalimat' => "Dari paket luring. {$b} Keadaan pendakian dari catatan terakhir di perangkat ini (9 Okt 14.05 WIB); bila pendakian sudah diselesaikan atau dibatalkan sesudah itu, tiba di pos dan jeda dari sini tidak diterima server, dan jejak yang terekam lebih dari 10 menit sesudah pendakian diselesaikan atau dibatalkan tidak disimpan. Tiba di pos, jeda, dan lanjut disimpan di perangkat sampai terkirim; selesaikan pendakian saat sinyal kembali."],
        ], $h);
    }

    /**
     * Ruling AF-6 (audit E2E I-6), cabang 7 teksKeadaanLapangan: shell yang rekaman sesinya sudah
     * berakhir di perangkat ini menulis diselesaikan atau dibatalkan beserta waktunya, dan bahwa
     * tiba di pos dan jeda tidak dicatat lagi. Cabang ini didahulukan dari cabang shell berlangsung
     * dan keterangannya tanpa lama bergerak, jadi penghitung waktu tidak berjalan, juga bila
     * rekamannya sedang jeda. Halaman daring tidak memakai cabang ini (keadaan server lebih baru).
     * kalimatBerakhir yang sama dipakai daftar paket, dan tanpa waktu yang terbaca "pada" tidak ditulis.
     */
    public function test_an_ended_hike_in_the_shell_is_named_ended_and_never_running(): void
    {
        $h = $this->node(['sumber' => 'luring/kalimatSumber.ts'], <<<'JS'
            const k = (halamanLuring, berakhir, dijeda = false, berlangsung = true) => sumber.teksKeadaanLapangan({ halamanLuring, sumber: 'paket', berlangsung, dijeda, lama: '2 jam 15 menit', catatan: '9 Okt 14.05 WIB', berakhir });
            process.stdout.write(JSON.stringify({
                selesai: k(true, { jenis: 'selesai', waktu: '11 Okt 20.45 WIB' }),
                batalJeda: k(true, { jenis: 'batal', waktu: '11 Okt 20.50 WIB' }, true, false),
                daring: k(false, { jenis: 'selesai', waktu: '11 Okt 20.45 WIB' }),
                tanpaBerakhir: k(true, null).keterangan,
                daftar: [sumber.kalimatBerakhir('selesai', '11 Okt 2026 20.45 WIB'), sumber.kalimatBerakhir('batal', null)],
            }));
            JS);

        $b = 'Paket luring membantu mengikuti jalur resmi. Bawa cadangan: peta cetak atau perangkat lain.';

        $this->assertSame(['keterangan' => 'Sudah diselesaikan', 'kalimat' => "Dari paket luring. {$b} Pendakian ini sudah diselesaikan di perangkat ini pada 11 Okt 20.45 WIB. Tiba di pos dan jeda tidak dicatat lagi."], $h['selesai']);
        $this->assertSame(['keterangan' => 'Sudah dibatalkan', 'kalimat' => "Dari paket luring. {$b} Pendakian ini sudah dibatalkan di perangkat ini pada 11 Okt 20.50 WIB. Tiba di pos dan jeda tidak dicatat lagi."], $h['batalJeda']);
        $this->assertSame('Berjalan, 2 jam 15 menit bergerak', $h['daring']['keterangan'], 'Halaman daring tidak memakai rekaman berakhir di perangkat.');
        $this->assertSame('Berjalan, 2 jam 15 menit bergerak', $h['tanpaBerakhir']);
        $this->assertSame(['Pendakian ini sudah diselesaikan di perangkat ini pada 11 Okt 2026 20.45 WIB.', 'Pendakian ini sudah dibatalkan di perangkat ini.'], $h['daftar']);
    }

    /**
     * Sambungan ruling AF-6 (asersi sumber, karena React tidak dijalankan di suite ini): jalur sukses
     * Selesai (FormulirSelesai, dari mode lapangan maupun ruang kerja) dan Batal (BatalkanTrip, ruang
     * kerja) menandai rekaman sesi paket trip itu berakhir dengan jenisnya; cabang penolakan tidak.
     * Kedua pemanggil meneruskan id tripnya. Shell membaca rekaman itu untuk daftar paket dan mode
     * lapangan, dan rakitannya memakai `trip.berlangsung` yang mati untuk menyembunyikan Jeda, Lanjut,
     * tiba di pos, dan Selesai.
     */
    public function test_the_success_paths_mark_the_device_session_ended_and_the_shell_reads_it(): void
    {
        $formulir = File::get(resource_path('js/components/trips/FormulirSelesai.tsx'));

        $this->assertMatchesRegularExpression(
            "/onSuccess: \\(page\\) => \\{\\s*ditolak = Boolean\\(page\\.props\\.penolakan\\);\\s*if \\(!ditolak\\) \\{\\s*void tandaiSesiBerakhir\\(kunci, tripId, \\{ jenis: 'selesai', waktu: new Date\\(\\)\\.toISOString\\(\\) \\}\\)\\.catch\\(\\(\\) => undefined\\);\\s*\\}\\s*\\},/",
            $formulir,
        );
        $this->assertSame(1, substr_count($formulir, 'tandaiSesiBerakhir('));
        $this->assertStringContainsString('const kunci = usePage<PropsBersama>().props.perangkat?.kunci ?? null;', $formulir);

        $batal = File::get(resource_path('js/components/trips/BatalkanTrip.tsx'));
        $this->assertSame(1, substr_count($batal, "tandaiSesiBerakhir(kunci, tripId, { jenis: 'batal'"));

        $show = File::get(resource_path('js/pages/Trips/Show.tsx'));
        $this->assertStringContainsString('<BatalkanTrip tripId={trip.id} ', $show);
        $this->assertStringContainsString('<FormulirSelesai tripId={trip.id} ', $show);
        $this->assertStringContainsString('<FormulirSelesai tripId={data.trip.id} ', File::get(resource_path('js/components/mendaki/SelesaiLapangan.tsx')));

        $shell = File::get(resource_path('js/luring/shell.tsx'));
        $this->assertStringContainsString('const sesi = await bacaSesi(db, r.tripId).catch(() => null);', $shell);
        $this->assertStringContainsString('berakhir: sesi?.berakhir ?? null', $shell);
        $this->assertStringContainsString('{p.berakhir ? <p className="mt-2 text-isi font-bold text-primary">{kalimatBerakhir(p.berakhir.jenis, waktuBerakhir(p.berakhir.waktu))}</p> : null}', File::get(resource_path('js/luring/DaftarPaket.tsx')));
        $this->assertStringContainsString('berakhir: data.berakhir ? { jenis: data.berakhir.jenis, waktu: teksWaktu(data.berakhir.waktu) } : null,', File::get(resource_path('js/pages/Trips/Hike.tsx')));

        $mode = File::get(resource_path('js/components/mendaki/ModeLapangan.tsx'));
        $this->assertStringContainsString('{tujuan && data.trip.berlangsung ? (', $mode, 'Tiba di pos hanya selama pendakian berlangsung.');
        $this->assertStringContainsString("{data.trip.berlangsung ? (\n                <div className=\"flex gap-3\">", $mode, 'Jeda, Lanjut, dan Selesai hanya selama pendakian berlangsung.');
    }

    /**
     * Penjaga N-2 (ruling W6-FH, temuan pemeriksa final-fix-check), kalimatnya disesuaikan ruling
     * W6-FT: angka menit di kalimat shell ("jejak yang terekam lebih dari N menit sesudah
     * pendakian diselesaikan atau dibatalkan tidak disimpan") wajib sama dengan
     * PenerimaanJejak::KELONGGARAN_JAM_PERANGKAT_MENIT, yang benar-benar menyaring titik
     * di server (HikeTrackRecordingTest). Kedua angka dibaca dari sumbernya masing-masing, bukan
     * ditulis tetap di sini, supaya mutasi yang mengubah salah satu angka sendirian terdeteksi merah.
     */
    public function test_the_field_sentences_leeway_in_minutes_matches_the_servers_constant(): void
    {
        $sumber = File::get(resource_path('js/luring/kalimatSumber.ts'));

        preg_match_all('/jejak yang terekam lebih dari (\d+) menit sesudah pendakian diselesaikan atau dibatalkan tidak disimpan/', $sumber, $cocok);

        $this->assertNotEmpty($cocok[1], 'Kalimat shell wajib menyebut angka menit kelonggaran secara persis.');
        $this->assertSame(array_fill(0, count($cocok[1]), (string) PenerimaanJejak::KELONGGARAN_JAM_PERANGKAT_MENIT), $cocok[1], 'Angka menit di setiap kalimat shell wajib sama dengan PenerimaanJejak::KELONGGARAN_JAM_PERANGKAT_MENIT.');
    }

    /**
     * "Pakai paket saja" mengganti data jalur saja (PT10-3, PT10-5): garis, pos, laporan, dan isi
     * paket lain dibaca dari paket, sedangkan keadaan pendakian di halaman ini (berlangsung, mulai,
     * jeda, pos tercapai, sesi, dan rekam jejak) tetap. Bagian lapangan paket tidak membawa keadaan
     * sesi (PaketLapangan::lapangan), jadi tanpa gabungan ini sakelar akan menulis "Belum dimulai",
     * menghentikan perekam jejak, dan menimpa rekaman sesi di perangkat dengan keadaan kosong.
     */
    public function test_using_the_pack_only_swaps_the_route_data_not_the_hike_state(): void
    {
        $h = $this->node(['sumber' => 'luring/kalimatSumber.ts'], <<<'JS'
            const urls = { tiba: '/trips/7/hike/tiba', jeda: '/trips/7/hike/jeda', lanjut: '/trips/7/hike/lanjut', selesai: '/trips/7/selesai', ruangKerja: '/trips/7' };
            const trip = { id: 7, nama: 'Gede akhir pekan', jalur: 'Gede via Cibodas', gunung: 'Gunung Gede' };
            const daring = {
                trip: { ...trip, berlangsung: true, mulai: '2026-10-22T21:00:00Z', jedaSejak: '2026-10-22T23:00:00Z', jedaDetik: 600 },
                pos: [{ id: 1, urutan: 1 }], garis: [[106.9, -6.7]], profil: null, ruas: [], tercapai: 2, radiusTiba: 75, laporan: [],
                rekamJejak: true, sesiId: 41, sumber: 'daring', darurat: null, pilihan: { hasil: [], alasan: [] }, urls: { ...urls, jejak: '/pendakian/41/jejak' },
            };
            const paket = {
                trip: { ...trip, jalur: 'Gede via Cibodas (paket)', berlangsung: false, mulai: null, jedaSejak: null, jedaDetik: 0 },
                pos: [{ id: 1, urutan: 1 }, { id: 2, urutan: 2 }], garis: [[106.8, -6.8]], profil: [[0, 1400]], ruas: [], tercapai: 0, radiusTiba: 60,
                laporan: [{ segmen: 3, kondisi: 'Licin', catatan: null, waktu: 'mendaki 5 Okt' }],
                rekamJejak: false, sesiId: null, sumber: 'paket', darurat: null, pilihan: { hasil: [], alasan: [] }, urls: { ...urls, jejak: null },
                dataContoh: true, catatanSesi: null,
            };
            process.stdout.write(JSON.stringify(sumber.dataPakaiPaketSaja(daring, paket)));
            JS);

        $this->assertSame(['paket', 'Gede via Cibodas (paket)', 2, [[106.8, -6.8]], 'Licin', 60, true], [$h['sumber'], $h['trip']['jalur'], count($h['pos']), $h['garis'], $h['laporan'][0]['kondisi'], $h['radiusTiba'], $h['dataContoh']]);
        $this->assertSame(
            [true, '2026-10-22T21:00:00Z', '2026-10-22T23:00:00Z', 600, 2, 41, true, '/pendakian/41/jejak'],
            [$h['trip']['berlangsung'], $h['trip']['mulai'], $h['trip']['jedaSejak'], $h['trip']['jedaDetik'], $h['tercapai'], $h['sesiId'], $h['rekamJejak'], $h['urls']['jejak']],
            'Keadaan pendakian tetap dari halaman ini.',
        );
    }

    /**
     * Sambungan halaman (asersi sumber karena React tidak dijalankan di suite ini):
     * - mengganti sumber tidak memasang ulang mode lapangan (PT10-3, PT10-5): tanpa kunci sumber,
     *   dan rekaman sesi ditulis di kedua mode;
     * - persist() diminta pada ketukan, sesudah ketukan kedua ditolak dan sebelum menunggu izin
     *   (PT10-12 bagian a, PT10-18 bagian b);
     * - kegagalan unduh menulis keadaan gagal, bukan siap (PT10-9 mutan M9);
     * - pintu unduh hanya di pratinjau hari-H dan ruang kerja trip aktif selama sakelar menyala.
     *
     * Diubah di putaran perbaikan 2 (tinjauan Tugas 10 m-D): keputusan siap, belum luring, dan "tetap
     * siap luring" pindah ke alur.ts dan diuji perilakunya di Node, jadi asersi teks lama atas
     * keputusan itu di pintu (hitungan `tahap: 'siap'` di unduhInti, `keadaanPaketLama` dan
     * `, lama, true)` di pintu, `perlu` dengan keadaanKini) diganti asersi lemnya: pintu tidak
     * memutuskan siap atau belum luring sendiri, semua tulisan keadaan lewat toko, dan pemantau serta
     * pemeriksaan shell memakai fungsi alur.ts yang diuji.
     */
    public function test_the_pack_door_and_field_mode_are_wired_on_the_pages(): void
    {
        $hike = File::get(resource_path('js/pages/Trips/Hike.tsx'));
        $pintu = File::get(resource_path('js/components/mendaki/PintuPaket.tsx'));

        $this->assertStringNotContainsString('key={data.sumber}', $hike);
        $this->assertStringContainsString('simpanSesi={paketAktif}', $hike);
        $this->assertDoesNotMatchRegularExpression('/simpanSesi=\{[^}]*hanyaPaket/', $hike);
        $this->assertStringContainsString('dataPakaiPaketSaja(props, dataPaket)', $hike);

        $this->assertStringContainsString('const menetap = await mintaMenetap();', $pintu);
        $this->assertSame(1, preg_match('/const unduh = async \(manifest: ManifestPaket\) => \{(.*?)\n    \};/s', $pintu, $unduh));
        $this->assertMatchesRegularExpression('/if \(berjalan\.current\) \{\s*return;\s*\}\s*berjalan\.current = true;/', $unduh[1]);
        $this->assertLessThan(strpos($unduh[1], 'await mintaMenetap()'), strpos($unduh[1], "tahap: 'mengunduh'"), 'Keadaan mengunduh ditulis sebelum menunggu izin penyimpanan.');
        $this->assertSame(1, preg_match('/const unduhInti = async \(.*?\n    \};/s', $pintu, $inti));
        $this->assertMatchesRegularExpression('/\} catch \(galat\) \{\s*if \(lama\) \{\s*await terapkanLama\(galat, pesanGalat\(galat, UNDUH_PUTUS\), lama, menetap\);\s*\} else \{\s*gagal\(pesanGalat\(galat, UNDUH_PUTUS\), null, false\);\s*\}\s*\}/', $inti[0], 'Kegagalan unduh menulis keadaan gagal; paket lama hanya lewat terapkanLama.');
        $this->assertMatchesRegularExpression("/const gagal = \(pesan: string, lama: RekamanPaket \| null, sebutLama: boolean\) => \{\s*tampilkan\(\{ tahap: 'gagal',/", $pintu);
        $this->assertStringContainsString('tampilkan(await keadaanPaketDiperiksa(paket, menetap, \'siap\', awal, hitunganGanti, pulihkanShellPaket));', $inti[0], 'Keadaan siap hanya dari unduhan yang lolos dan shell terbaru yang siap.');

        // Tinjauan Tugas 10 m-D: pintu tidak memutuskan keadaan siap atau belum luring sendiri; semua
        // datang dari alur.ts (diuji Node), dan semua tulisan keadaan lewat toko yang dibaca pemantau.
        $this->assertStringNotContainsString("tahap: 'siap'", $pintu);
        $this->assertStringNotContainsString("tahap: 'belumLuring'", $pintu);
        $this->assertStringNotContainsString('keadaanKini', $pintu);
        $this->assertSame(1, preg_match_all('/setKeadaan\(/', $pintu), 'Render hanya mencerminkan toko.');
        $this->assertMatchesRegularExpression('/setKeadaan\(toko\.ambil\(\)\);\s*return toko\.langganan\(setKeadaan\);/', $pintu);
        $this->assertMatchesRegularExpression('/const ubah = \(baru: Keadaan, pengumuman: string, fokus: \'utama\' \| \'judul\'\) => \{\s*fokusKe\.current = fokus;\s*setSibuk\(null\);\s*toko\.ubah\(baru\);/', $pintu);
        $this->assertStringContainsString('return keadaanPaketDiperiksa(paket, null, await pulihkanShellPaket(), awal, () => gantiKe.current, pulihkanShellPaket);', $pintu);
        $this->assertStringContainsString('tampilkan(await keadaanPaketDiperiksa(paket, null, shell, awal, hitunganGanti, pulihkanShellPaket));', $pintu);
        $this->assertSame(1, preg_match('/const perbarui = async \(lama: RekamanPaket\) => \{(.*?)\n    \};/s', $pintu, $perbarui));
        $this->assertLessThan(strpos($perbarui[1], 'ambilManifest('), strpos($perbarui[1], 'const menetap = await mintaMenetap();'), 'Perbarui meminta persist() pada ketukan, sebelum menunggu jaringan.');

        // Tinjauan Tugas 10 I-2: perbarui yang gagal dan versi yang sama menyebut paket lama siap
        // hanya lewat terapkanLama, yang memeriksa shell SAAT itu (keadaanPaketLama, diuji Node);
        // satu-satunya "tetap siap luring" (sebutLama true) ada di sana.
        $this->assertStringContainsString("await terapkanLama(null, '', lama, menetap);", $perbarui[1]);
        $this->assertStringContainsString('await terapkanLama(galat, pesanGalat(galat, PERBARUI_PUTUS), lama, menetap);', $perbarui[1]);
        $this->assertStringContainsString('tampilkan(await keadaanPintuLama(galat, pesan, lama, menetap, hitunganGanti, pulihkanShellPaket));', $pintu);
        $this->assertSame(0, substr_count($pintu, ', lama, true)'), '"Tetap siap luring" hanya dari keadaanPintuLama.');

        // Ruling W6-10e: pemeriksaan ulang setiap controllerchange, di pintu paket dan di mode
        // lapangan daring; tinjauan Tugas 10 m-4: tahap shell dikabarkan selama menunggu.
        $kabarShell = File::get(resource_path('js/components/mendaki/KabarShellPaket.tsx'));
        $this->assertMatchesRegularExpression('/return pantauShellPaketDiSini\(\s*kabarPantauPintu\(toko, \(\) => berjalan\.current, \{\s*ganti: \(\) => \{\s*gantiKe\.current \+= 1;/', $pintu, 'Pintu paket memakai keputusan pemantau dari alur.ts, dengan toko dan aksi yang berjalan.');

        // Ruling W6-10g: setiap pemeriksaan ulang di halaman lewat pulihkanShellSesudahAktif.
        $shellPaket = File::get(resource_path('js/luring/shellPaket.ts'));
        $this->assertMatchesRegularExpression('/export function pulihkanShellPaket\(\): Promise<HasilShell> \{\s*return pulihkanShellSesudahAktif\(wadahSw\(\), alatShell\(\)\);/', $shellPaket);
        $this->assertStringContainsString('return pantauShellPaket(wadahSw(), alatShell(), kabar);', $shellPaket);
        $this->assertStringContainsString('return pantauShellPaketDiSini({ hasil: setKeadaan });', $kabarShell);
        $this->assertMatchesRegularExpression("/if \(kemajuan\.tahap === 'shell'\) \{\s*setKabar\(SIAPKAN_SHELL\);/", $inti[0]);
        $this->assertStringContainsString("keadaan.kemajuan.tahap === 'shell' ? SIAPKAN_SHELL", $pintu);

        // Tinjauan Tugas 10 m-1: mode lapangan memuat kabar shell malas, tanpa pintu paket dan rute paket.
        $this->assertStringContainsString("import('@/components/mendaki/KabarShellPaket')", $hike);
        $this->assertStringNotContainsString("from '@/components/mendaki/PintuPaket'", $hike);
        $this->assertStringNotContainsString("from './paket'", File::get(resource_path('js/luring/useLapanganPaket.ts')));

        $this->assertStringContainsString('{paket.aktif ? <PintuPaket tripId={hariH.id} /> : null}', File::get(resource_path('js/pages/Track/Index.tsx')));
        $this->assertStringContainsString('{trip.aktif && perangkat?.paket ? (', File::get(resource_path('js/pages/Trips/Show.tsx')));

        // Basis data paket tidak dibuat untuk pengguna yang belum mengunduh (PT10-16), dan hanya
        // tulisan sesi terakhir yang dimulai yang menulis, jadi rekaman tidak mundur.
        $this->assertMatchesRegularExpression('/if \(!\(await adaBasisData\(kunci\)\)\) \{\s*return null;\s*\}\s*const db = await buka\(kunci\);/', File::get(resource_path('js/luring/baca.ts')));
        $this->assertStringContainsString('if (paket && ini === putaran.current) {', File::get(resource_path('js/luring/useLapanganPaket.ts')));

        // Shell luring dan "Pakai paket saja" memakai satu pembaca paket (V10-1), yang menerapkan
        // aksi yang masih menunggu (W6-9d); di shell, penyimpanan antrean dipasang lebih dulu.
        $baca = File::get(resource_path('js/luring/baca.ts'));
        $shell = File::get(resource_path('js/luring/shell.tsx'));
        $this->assertStringContainsString('terapkanAntrean(rakitan, await antreanSaatIni(), sesi ? Date.parse(sesi.diperbarui) : null)', $baca);
        $this->assertLessThan(strpos($shell, 'const data = await dataLapanganDariPaket(kunci, paket);'), strpos($shell, 'pakaiPenyimpanan(penyimpananGabungan('));
        $this->assertStringContainsString('terbaruPerTrip((await semuaPaket(db)).filter((p) => p.siap))', $shell);
    }

    /**
     * Tujuan "Buka paket luring" (ruling W6-9k bagian b): /offline?dibuka=paket menyebut apa yang
     * ditampilkannya, bukan "belum dapat dimuat", karena halaman itu dibuka pendaki sendiri.
     * Tanpa parameter, kalimat W6-9j tetap, termasuk salinan /offline yang disimpan service worker
     * untuk navigasi yang gagal. Selama paket mati halaman itu tidak memuat shell, jadi tidak
     * menampilkan paket apa pun, dan parameternya tidak mengubah kalimat (butir 2). Label "Luring"
     * tetap di kedua cabang.
     */
    public function test_the_open_pack_link_lands_on_a_page_that_says_what_it_shows(): void
    {
        $mati = preg_replace('/\s+/', ' ', (string) $this->get('/offline?dibuka=paket')->assertOk()->getContent());

        config(['hiking.field_pack.enabled' => true]);
        $dibuka = preg_replace('/\s+/', ' ', (string) $this->get('/offline?dibuka=paket')->assertOk()->getContent());
        $gagal = preg_replace('/\s+/', ' ', (string) $this->get('/offline')->assertOk()->getContent());
        $lain = preg_replace('/\s+/', ' ', (string) $this->get('/offline?dibuka=lain')->assertOk()->getContent());

        $this->assertStringContainsString('<title>Paket luring di perangkat ini</title>', $dibuka);
        $this->assertMatchesRegularExpression('#<h1[^>]*> Paket luring di perangkat ini </h1>#', $dibuka);
        $this->assertStringContainsString('Halaman ini menampilkan paket yang tersimpan di perangkat ini, dibaca dari perangkat tanpa meminta data baru ke Memount.', $dibuka);
        $this->assertStringNotContainsString('Halaman ini belum dapat dimuat', $dibuka);
        $this->assertStringNotContainsString('tidak mendapat jawaban', $dibuka);

        foreach ([$gagal, $lain, $mati] as $halaman) {
            $this->assertStringContainsString('<title>Halaman ini belum dapat dimuat</title>', $halaman);
            $this->assertStringContainsString('Perangkat ini tidak mendapat jawaban dari Memount.', $halaman);
            $this->assertStringNotContainsString('Paket luring di perangkat ini', $halaman);
        }

        foreach ([$dibuka, $gagal] as $halaman) {
            $this->assertStringContainsString('>Luring</p>', $halaman);
            $this->assertStringContainsString('id="paket-luring"', $halaman);
        }
    }

    /**
     * @param  array<int, array{0: string, 1: array<int, mixed>}>  $panggilan
     * @return array<int, mixed>
     */
    private function periksa(array $panggilan): array
    {
        $skrip = 'import { pathToFileURL } from "node:url";'
            .'const modul = await import(pathToFileURL('.json_encode(resource_path('js/luring/periksa.ts')).').href);'
            .'const panggilan = '.json_encode($panggilan).';'
            .'process.stdout.write(JSON.stringify(panggilan.map(([nama, argumen]) => modul[nama](...argumen))));';

        $proses = new Process(['node', '--experimental-strip-types', '--input-type=module', '-'], base_path(), null, $skrip, 60);
        $proses->run();

        $this->assertTrue($proses->isSuccessful(), 'Node gagal menjalankan periksa.ts: '.mb_substr($proses->getErrorOutput(), 0, 600));

        return json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Menjalankan badan skrip di Node dengan modul luring yang disebut sudah diimpor (pola
     * OfflineShellTest::node). Modulnya tanpa impor nilai (ruling L3).
     *
     * @param  array<string, string>  $modul
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
