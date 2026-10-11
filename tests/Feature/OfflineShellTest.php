<?php

namespace Tests\Feature;

use App\Enums\TripStatus;
use App\Models\Checkpoint;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Support\AsetLuring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Shell luring (spec v3 bagian 10.4; rencana induk W6 butir 3; ruling W6-R9): halaman /offline
 * yang disimpan service worker, dan mode lapangan yang dirakit di perangkat dari paket.
 *
 * Diperluas W6 Tugas 9 di luar brief (task-9-tambahan.md, P-R10): penanda shell untuk
 * pemeriksaan "Siap luring" Tugas 10 (W6-9b), props bersama shell yang lengkap tanpa pengguna
 * (PT9-1), aksi yang menunggu diterapkan ke keadaan shell (W6-9d), ganti versi atomik (W6-9i),
 * penyimpanan perangkat yang menutup koneksinya sendiri (PT9-7, PT9-20), hash yang sama dengan
 * server (PT9-15), dan kalimat daftar paket di setiap cabang (PT9-12 sampai PT9-14, W6-9c).
 */
class OfflineShellTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Selama paket mati, halaman luring hanya teks pengakuan seperti sebelum W6. Sesudah dibuka,
     * halaman yang sama memuat entri shell dan wadah untuk daftar paket dan mode lapangan.
     *
     * Ditambah W6-9b: halaman yang memuat shell menandai dirinya dengan path entri shell yang
     * ada di manifest build, supaya Tugas 10 hanya menyebut "Siap luring" bila /offline yang
     * tersimpan benar-benar memuat shell. Dua wilayah umpan balik shell selalu terpasang
     * (PT9-2, PT9-3), dan memakai <p>, bukan <div> (DesignSystemTest).
     */
    public function test_the_offline_page_loads_the_shell_only_when_the_pack_is_open(): void
    {
        $mati = $this->get('/offline')->assertOk()->getContent();

        $this->assertStringNotContainsString('shell', $mati);
        $this->assertStringContainsString('id="paket-luring"', $mati);

        config(['hiking.field_pack.enabled' => true]);
        $hidup = $this->get('/offline')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#/build/assets/shell-[^"]+\.js#', $hidup);
        $this->assertStringContainsString('<div id="app"></div>', $hidup);

        $manifest = json_decode(File::get(public_path('build/manifest.json')), true);
        $entri = '/build/'.$manifest[AsetLuring::ENTRI_SHELL]['file'];

        $this->assertStringContainsString('<meta name="memount-shell-luring" content="'.$entri.'">', $hidup);
        $this->assertFileExists(public_path(ltrim($entri, '/')));

        foreach ([$mati, $hidup] as $halaman) {
            $this->assertMatchesRegularExpression('#<p id="luring-kabar" role="status" aria-atomic="true"[^>]*></p>#', $halaman);
            $this->assertMatchesRegularExpression('#<p id="luring-galat" role="alert" tabindex="-1"[^>]*></p>#', $halaman);
            $this->assertLessThan(strpos($halaman, 'id="paket-luring"'), strpos($halaman, 'id="luring-galat"'), 'Wilayah umpan balik berada tepat sebelum daftar paket.');
        }
    }

    /**
     * Manifest build yang belum memuat entri shell (build lama, atau build yang gagal sebagian)
     * tidak membuat /offline 500 dan tidak memasang penanda shell (PT9-16 bagian d): halaman luring
     * jatuh ke teks pengakuan dengan app.css saja, dan "Siap luring" tidak dapat lolos.
     */
    public function test_a_build_without_the_shell_entry_falls_back_to_the_plain_offline_page(): void
    {
        $sementara = storage_path('framework/testing/luring-'.Str::random(12));
        File::ensureDirectoryExists($sementara.'/build');
        File::put($sementara.'/build/manifest.json', json_encode([
            'resources/css/app.css' => ['file' => 'assets/app-uji.css', 'src' => 'resources/css/app.css', 'isEntry' => true],
        ]));

        try {
            $this->app->usePublicPath($sementara);
            config(['hiking.field_pack.enabled' => true]);

            $this->assertNull(AsetLuring::entriShell());

            $halaman = $this->get('/offline')->assertOk()->getContent();

            $this->assertStringNotContainsString('memount-shell-luring', $halaman);
            $this->assertStringNotContainsString('shell', $halaman);
            $this->assertStringContainsString('/build/assets/app-uji.css', $halaman);
        } finally {
            File::deleteDirectory($sementara);
        }
    }

    /**
     * Keadaan sesi berasal dari rekaman mode lapangan daring terakhir di perangkat; tanpa rekaman
     * itu pendakian tampil belum dimulai, bukan berjalan sejak waktu tebakan (R-007).
     *
     * Ditambah PT9-15: rekaman yang sedang jeda membawa jedaSejak-nya, rakitan menyebut waktu
     * rekaman itu (catatanSesi, untuk kalimat Tugas 10), dan sesiDari atas hasil rakitan
     * mengembalikan rekaman yang sama persis (pulang-pergi), jadi tidak ada kunci yang tertukar
     * atau hilang di antara keduanya.
     */
    public function test_the_field_data_merges_the_last_known_session(): void
    {
        $lapangan = [
            'trip' => ['id' => 7, 'nama' => 'Gede akhir pekan', 'jalur' => 'Gede via Cibodas', 'gunung' => 'Gunung Gede', 'berlangsung' => false, 'mulai' => null, 'jedaSejak' => null, 'jedaDetik' => 0],
            'tercapai' => 0, 'sesiId' => null, 'rekamJejak' => false, 'sumber' => 'paket', 'laporan' => [],
            'urls' => ['tiba' => '/trips/7/hike/tiba', 'jejak' => null],
        ];
        $dinamis = ['laporan' => [['segmen' => 3, 'kondisi' => 'Licin', 'catatan' => null, 'waktu' => 'mendaki 5 Okt']]];
        $sesi = ['tripId' => 7, 'trip' => ['berlangsung' => true, 'mulai' => '2026-10-22T21:00:00Z', 'jedaSejak' => null, 'jedaDetik' => 600], 'tercapai' => 2, 'sesiId' => 41, 'rekamJejak' => true, 'jejak' => '/hike/41/track', 'diperbarui' => '2026-10-22T23:10:00Z'];
        $dijeda = ['tripId' => 7, 'trip' => ['berlangsung' => true, 'mulai' => '2026-10-22T21:00:00.000Z', 'jedaSejak' => '2026-10-22T23:00:00.000Z', 'jedaDetik' => 120], 'tercapai' => 3, 'sesiId' => 41, 'rekamJejak' => false, 'jejak' => '/hike/41/track', 'diperbarui' => '2026-10-22T23:05:00.000Z'];

        [$tanpaSesi, $denganSesi, $sedangJeda] = $this->rakit([[$lapangan, $dinamis, null], [$lapangan, $dinamis, $sesi], [$lapangan, $dinamis, $dijeda]]);

        $this->assertSame([false, 0, null, 'mendaki 5 Okt', 'paket'], [$tanpaSesi['trip']['berlangsung'], $tanpaSesi['tercapai'], $tanpaSesi['urls']['jejak'], $tanpaSesi['laporan'][0]['waktu'], $tanpaSesi['sumber']]);
        $this->assertSame([true, '2026-10-22T21:00:00Z', 600, 2, 41, true, '/hike/41/track', 'Gede via Cibodas'], [
            $denganSesi['trip']['berlangsung'], $denganSesi['trip']['mulai'], $denganSesi['trip']['jedaDetik'], $denganSesi['tercapai'],
            $denganSesi['sesiId'], $denganSesi['rekamJejak'], $denganSesi['urls']['jejak'], $denganSesi['trip']['jalur'],
        ]);
        $this->assertSame(['2026-10-22T23:00:00.000Z', 120, 3, false], [$sedangJeda['trip']['jedaSejak'], $sedangJeda['trip']['jedaDetik'], $sedangJeda['tercapai'], $sedangJeda['rekamJejak']]);

        $this->assertNull($tanpaSesi['catatanSesi']);
        $this->assertSame(['diperbarui' => '2026-10-22T23:05:00.000Z'], $sedangJeda['catatanSesi']);

        $pulang = $this->node(['rakit' => 'luring/rakit.ts'], 'const s = '.json_encode($dijeda).';'
            .'process.stdout.write(JSON.stringify(rakit.sesiDari(rakit.rakitDataLapangan('.json_encode($lapangan).', '.json_encode($dinamis).', s), new Date(s.diperbarui))));');

        $this->assertSame($dijeda, $pulang);
    }

    /**
     * Basis data paket per pengguna (spec v3 bagian 10.6) dan tanpa token di dalamnya.
     */
    public function test_the_pack_store_is_per_user_and_holds_no_tokens(): void
    {
        $simpan = File::get(resource_path('js/luring/simpan.ts'));

        $this->assertStringContainsString("export const AWALAN_BASIS_DATA = 'memount-luring-';", $simpan);
        $this->assertStringContainsString('return `${AWALAN_BASIS_DATA}${kunci}`;', $simpan);

        foreach (['XSRF', 'document.cookie'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $simpan);
        }
    }

    /**
     * Props halaman mode lapangan dari paket berasal dari satu fungsi (PT9-1, ruling L1): setiap
     * kunci wajib PropsBersama ada, karena Trips/Hike dan turunannya membaca props bersama
     * (useModeLapangan membaca perangkat sejak W6 Tugas 10, sebelumnya auth.user; props shell
     * tanpa perangkat membuatnya tidak mengganti penyimpanan antrean yang dipasang shell). Tanpa
     * pengguna (shell tidak bertanya ke server dan tidak memegang identitas), tanpa perangkat,
     * tanpa peta dasar dengan atribusi sumber garis
     * dari manifest (indeks 0, kontrak Tugas 8), lencana Data contoh dari paket, dan judul yang
     * menyebut jalur beserta gunungnya.
     *
     * Kunci wajib dibaca dari antarmuka PropsBersama sendiri, jadi kunci bersama baru yang
     * ditambah kelak ikut dituntut di sini tanpa menyunting test ini.
     */
    public function test_the_shell_props_carry_every_shared_key_without_a_user(): void
    {
        $manifest = $this->manifestContoh(['dataContoh' => true]);
        $props = $this->node(['rakit' => 'luring/rakit.ts'], 'process.stdout.write(JSON.stringify(rakit.propsHalamanLuring('
            .'rakit.rakitDataLapangan('.json_encode($this->lapanganContoh()).', { laporan: [] }, null), '.json_encode($manifest).', "Memount")));');

        $this->assertSame(1, preg_match('/export interface PropsBersama \{(.*?)\n\}/s', File::get(resource_path('js/types/index.d.ts')), $blok));
        preg_match_all('/^ {4}(\w+):/m', $blok[1], $wajib);
        $this->assertGreaterThanOrEqual(10, count($wajib[1]), 'Kunci wajib PropsBersama tidak terbaca.');

        foreach ($wajib[1] as $kunci) {
            $this->assertArrayHasKey($kunci, $props, "Prop bersama {$kunci} tidak ada di props shell luring.");
        }

        $this->assertSame(['user' => null], $props['auth']);
        $this->assertArrayHasKey('perangkat', $props);
        $this->assertNull($props['perangkat']);
        $this->assertSame(['styleUrl' => null, 'rasterTiles' => [], 'attribution' => 'Garis jalur, pos, dan angka: Balai Besar TNGGP.', 'maxZoom' => 17], $props['peta']);
        $this->assertTrue($props['demo']);
        $this->assertSame('Mode lapangan dari paket: Gede via Cibodas, Gunung Gede', $props['title']);
        $this->assertSame([], $props['errors']);
        $this->assertSame(['paket', 'Gede via Cibodas'], [$props['sumber'], $props['trip']['jalur']]);
        $this->assertSame(['status' => null, 'kabarAksi' => null, 'timbanganPenuh' => null, 'simpananTerhapus' => null, 'simpananSudahAda' => null, 'tautanBagikan' => null, 'butirDiubah' => null], $props['flash']);

        $tanpaContoh = $this->node(['rakit' => 'luring/rakit.ts'], 'process.stdout.write(JSON.stringify(rakit.propsHalamanLuring('
            .'rakit.rakitDataLapangan('.json_encode([...$this->lapanganContoh(), 'dataContoh' => false]).', { laporan: [] }, null), '.json_encode($this->manifestContoh()).', "Memount")));');

        $this->assertFalse($tanpaContoh['demo']);
        $this->assertStringContainsString('satisfies PropsBersama & DataLapangan', File::get(resource_path('js/luring/rakit.ts')));
    }

    /**
     * Aksi yang dicatat di perangkat dan masih menunggu sinyal diterapkan ke keadaan shell
     * (ruling W6-9d): pendaki yang aplikasinya ditutup OS tidak ditanya ulang pos yang sudah
     * dicatatnya. Hanya aksi trip ini (URL tiba, jeda, lanjut), urut waktu dibuat, dan hanya yang
     * lebih baru dari rekaman sesi: mode lapangan daring sudah menerapkan aksi yang lebih tua ke
     * rekaman itu, jadi jeda tidak terhitung dua kali.
     */
    public function test_waiting_actions_are_applied_to_the_shell_state_once(): void
    {
        $data = $this->lapanganContoh();
        $data['trip'] = [...$data['trip'], 'berlangsung' => true, 'mulai' => '2026-10-22T21:00:00.000Z', 'jedaDetik' => 600];
        $data['tercapai'] = 2;
        $aksi = fn (string $jenis, array $isi, int $dibuat, int $trip = 7) => ['id' => $jenis.$dibuat, 'url' => "/trips/{$trip}/hike/{$jenis}", 'isi' => $isi, 'dibuat' => $dibuat];

        $kasus = [
            // Tiba hanya menaikkan pos tercapai; aksi lanjut dicatat lebih dulu di larik tetapi dibuat sesudah jeda.
            [$data, [$aksi('tiba', ['urutan' => 4], 2000), $aksi('tiba', ['urutan' => 3], 3000), $aksi('lanjut', ['pada' => '2026-10-22T23:10:00.000Z'], 5000), $aksi('jeda', ['pada' => '2026-10-22T23:00:00.000Z'], 4000)], 1000],
            // Aksi trip lain diabaikan, juga lanjut trip lain selagi trip ini dijeda.
            [[...$data, 'trip' => [...$data['trip'], 'jedaSejak' => '2026-10-22T22:30:00.000Z']], [$aksi('tiba', ['urutan' => 5], 2000, 8), $aksi('lanjut', ['pada' => '2026-10-22T23:00:00.000Z'], 2001, 8)], 1000],
            // Jeda dan lanjut yang lebih tua dari rekaman sesi sudah ada di rekaman itu.
            [$data, [$aksi('jeda', ['pada' => '2026-10-22T22:00:00.000Z'], 500), $aksi('lanjut', ['pada' => '2026-10-22T22:10:00.000Z'], 900)], 1000],
            // Jeda yang sudah berjalan tidak dimulai ulang; tanpa rekaman sesi semua aksi berlaku.
            [[...$data, 'trip' => [...$data['trip'], 'jedaSejak' => '2026-10-22T22:30:00.000Z']], [$aksi('jeda', ['pada' => '2026-10-22T23:00:00.000Z'], 10)], null],
        ];

        $hasil = $this->node(['rakit' => 'luring/rakit.ts'], 'const kasus = '.json_encode($kasus).';'
            .'process.stdout.write(JSON.stringify(kasus.map(([d, a, s]) => rakit.terapkanAntrean(d, a, s))));');

        $this->assertSame([4, null, 1200], [$hasil[0]['tercapai'], $hasil[0]['trip']['jedaSejak'], $hasil[0]['trip']['jedaDetik']]);
        $this->assertSame([2, '2026-10-22T22:30:00.000Z', 600], [$hasil[1]['tercapai'], $hasil[1]['trip']['jedaSejak'], $hasil[1]['trip']['jedaDetik']]);
        $this->assertSame([2, null, 600], [$hasil[2]['tercapai'], $hasil[2]['trip']['jedaSejak'], $hasil[2]['trip']['jedaDetik']]);
        $this->assertSame('2026-10-22T22:30:00.000Z', $hasil[3]['trip']['jedaSejak']);
    }

    /**
     * Teks bagian disimpan sebagai bita dari server dan di-hash di perangkat; "Siap luring" hanya
     * benar bila hash perangkat sama dengan hash('sha256') server untuk teks multibita, tanpa
     * normalisasi Unicode apa pun (PT9-15 bagian c). Teksnya memuat huruf berdiakritik yang
     * ditulis terurai, sehingga normalisasi NFC akan mengubah hash.
     */
    public function test_the_device_hash_matches_the_server_hash_for_multibyte_text(): void
    {
        $teks = json_encode(['pos' => "Kandang Badak, 2.395 m dpl, 12 °C, Puncak Gede\u{0301}"], JSON_UNESCAPED_UNICODE);

        $hash = $this->node(['simpan' => 'luring/simpan.ts'], 'process.stdout.write(JSON.stringify(await simpan.sha256Hex('.json_encode($teks, JSON_UNESCAPED_UNICODE).')));');

        $this->assertSame(hash('sha256', $teks), $hash);
    }

    /**
     * Ganti versi paket (ruling W6-9i): rencana hapusnya hanya menyentuh trip yang sama, rekaman
     * yang diperiksa lebih lama dari versi baru beserta bagiannya, dan bagian yatim versi lain.
     * Versi baru dan versi yang lebih baru (diunduh tab lain sesudahnya) tidak pernah dihapus.
     */
    public function test_replacing_a_pack_version_never_deletes_the_new_or_a_newer_one(): void
    {
        $paket = fn (int $trip, string $versi, ?string $diverifikasi) => ['tripId' => $trip, 'versi' => $versi, 'siap' => true, 'diverifikasi' => $diverifikasi, 'manifest' => []];
        $ada = [$paket(7, 'lebihbaru', '2026-10-21T12:00:00.000Z'), $paket(7, 'lama', '2026-10-20T10:00:00.000Z'), $paket(8, 'lain', '2026-10-01T00:00:00.000Z'), $paket(7, 'baru', '2026-10-19T00:00:00.000Z')];
        $kunci = [[7, 'lama', 'lapangan'], [7, 'baru', 'lapangan'], [7, 'yatim', 'dinamis'], [7, 'lebihbaru', 'rencana'], [8, 'lain', 'lapangan'], [7, 'lama', 'rencana']];
        $baru = $paket(7, 'baru', '2026-10-21T10:00:00.000Z');

        $rencana = $this->node(['simpan' => 'luring/simpan.ts'], 'process.stdout.write(JSON.stringify(simpan.rencanaGantiVersi('.json_encode($ada).', '.json_encode($kunci).', '.json_encode($baru).')));');

        $this->assertSame(['paket' => [[7, 'lama']], 'bagian' => [[7, 'lama', 'lapangan'], [7, 'yatim', 'dinamis'], [7, 'lama', 'rencana']]], $rencana);
    }

    /**
     * Koneksi basis data paket tidak menahan penghapusan dan dapat dibuka lagi sesudah gagal
     * (PT9-7), dan penulisan baru selesai sesudah transaksinya benar-benar tersimpan (PT9-20).
     * Dijalankan di Node atas simpan.ts dengan indexedDB tiruan.
     */
    public function test_the_device_store_releases_its_connection_and_waits_for_commits(): void
    {
        $hasil = $this->node(['simpan' => 'luring/simpan.ts'], <<<'JS'
            const kejadian = [];
            const dbTiruan = () => ({ ditutup: false, close() { this.ditutup = true; kejadian.push('tutup'); }, onversionchange: null });
            let bukaKe = 0;
            let modeHapus = 'sukses';
            const pewaktu = [];
            globalThis.setTimeout = (f, ms) => { pewaktu.push({ f, ms }); return pewaktu.length; };
            globalThis.clearTimeout = () => {};
            globalThis.indexedDB = {
                open() {
                    bukaKe++;
                    const r = { result: null, error: null };
                    const ke = bukaKe;
                    queueMicrotask(() => {
                        if (ke === 1) { r.error = new Error('UnknownError'); r.onerror?.(); return; }
                        r.result = dbTiruan();
                        r.onsuccess?.();
                    });
                    return r;
                },
                deleteDatabase() {
                    kejadian.push('hapus');
                    const r = {};
                    queueMicrotask(() => (modeHapus === 'sukses' ? r.onsuccess?.() : r.onblocked?.()));
                    return r;
                },
            };

            let galatPertama = null;
            try { await simpan.buka('k1'); } catch (e) { galatPertama = String(e.message); }
            const db = await simpan.buka('k1');
            const samaLagi = (await simpan.buka('k1')) === db;
            db.onversionchange();
            const sesudahVersi = await simpan.buka('k1');

            const hapusSukses = await simpan.hapusBasisData('k1');
            await simpan.buka('k2');
            modeHapus = 'terhalang';
            const janjiTertunda = simpan.hapusBasisData('k2');
            await new Promise((r) => setImmediate(r));
            await new Promise((r) => setImmediate(r));
            const batas = pewaktu.map((p) => p.ms);
            pewaktu.splice(0).forEach((p) => p.f());
            const hapusTertunda = await janjiTertunda;

            const tx = (akhir) => {
                const t = { error: null, objectStore: () => ({ put: () => { const r = {}; queueMicrotask(() => { r.result = 1; r.onsuccess?.(); queueMicrotask(() => (akhir === 'batal' ? (t.error = new Error('QuotaExceededError'), t.onerror?.(), t.onabort?.()) : t.oncomplete?.())); }); return r; } }) };
                return t;
            };
            let tulisBatal = 'selesai';
            try { await simpan.tulisPaket({ transaction: () => tx('batal') }, { tripId: 7, versi: 'v', siap: true, diverifikasi: null, manifest: {} }); } catch (e) { tulisBatal = 'gagal: ' + e.message; }
            let tulisSukses = 'selesai';
            try { await simpan.tulisSesi({ transaction: () => tx('sukses') }, { tripId: 7 }); } catch (e) { tulisSukses = 'gagal: ' + e.message; }

            process.stdout.write(JSON.stringify({ galatPertama, bukaKe, samaLagi, dbLamaDitutup: db.ditutup, baruSesudahVersi: sesudahVersi !== db, hapusSukses, hapusTertunda, batas, urutan: kejadian, tulisBatal, tulisSukses }));
            JS);

        $this->assertSame('UnknownError', $hasil['galatPertama']);
        $this->assertTrue($hasil['samaLagi'], 'Koneksi yang terbuka dipakai ulang.');
        $this->assertTrue($hasil['dbLamaDitutup'], 'versionchange dari tab lain menutup koneksi ini.');
        $this->assertTrue($hasil['baruSesudahVersi'], 'Sesudah versionchange, buka() membuka koneksi baru.');
        $this->assertSame(['terhapus', 'tertunda'], [$hasil['hapusSukses'], $hasil['hapusTertunda']]);
        $this->assertSame([3000], $hasil['batas']);
        $this->assertSame(['tutup', 'tutup', 'hapus', 'tutup', 'hapus'], $hasil['urutan'], 'hapusBasisData menutup koneksinya sendiri sebelum menghapus.');
        $this->assertSame(['gagal: QuotaExceededError', 'selesai'], [$hasil['tulisBatal'], $hasil['tulisSukses']]);
    }

    /**
     * Ada-tidaknya basis data paket diperiksa tanpa membuatnya (PT10-16): indexedDB.databases()
     * bila peramban memilikinya, selain itu kunci perangkat yang tersimpan.
     */
    public function test_checking_for_a_pack_store_never_creates_one(): void
    {
        $hasil = $this->node(['simpan' => 'luring/simpan.ts'], <<<'JS'
            let dibuka = 0;
            globalThis.window = { localStorage: { getItem: () => 'k2' } };
            globalThis.indexedDB = { open() { dibuka++; return {}; }, databases: async () => [{ name: 'memount-luring-k1', version: 1 }] };
            const denganDaftar = [await simpan.adaBasisData('k1'), await simpan.adaBasisData('k2')];
            delete globalThis.indexedDB.databases;
            const tanpaDaftar = [await simpan.adaBasisData('k1'), await simpan.adaBasisData('k2')];
            process.stdout.write(JSON.stringify({ denganDaftar, tanpaDaftar, dibuka }));
            JS);

        $this->assertSame(['denganDaftar' => [true, false], 'tanpaDaftar' => [false, true], 'dibuka' => 0], $hasil);
    }

    /**
     * Izin rekam jejak di shell dicabut sesudah Profil menyimpan "mati" (ruling W6-9h, W6-P2f):
     * semua rekaman sesi basis data paket pengguna ini menjadi rekamJejak false dalam satu transaksi
     * dan field lain tetap. Basis data yang tidak ada tidak dibuka (membuka berarti membuat), juga
     * tanpa indexedDB.databases() bila kunci tersimpan milik orang lain, dan kunci yang sudah
     * ditutup di tab ini tidak disentuh sama sekali.
     */
    public function test_turning_recording_off_revokes_it_in_every_stored_session(): void
    {
        $hasil = $this->node(['simpan' => 'luring/simpan.ts'], <<<'JS'
            const dibuka = [];
            const ditulis = [];
            let daftar = [];
            const rekaman = [
                { tripId: 9, trip: { berlangsung: true, mulai: 'm9', jedaSejak: null, jedaDetik: 0 }, tercapai: 2, sesiId: 41, rekamJejak: true, jejak: '/hike/41/track', diperbarui: 'd9' },
                { tripId: 3, trip: { berlangsung: false, mulai: null, jedaSejak: null, jedaDetik: 0 }, tercapai: 0, sesiId: null, rekamJejak: false, jejak: null, diperbarui: 'd3' },
                { tripId: 5, trip: { berlangsung: true, mulai: 'm5', jedaSejak: 'j5', jedaDetik: 60 }, tercapai: 4, sesiId: 17, rekamJejak: true, jejak: '/hike/17/track', diperbarui: 'd5' },
            ];
            globalThis.window = { localStorage: { getItem: () => 'orang-lain' } };
            globalThis.indexedDB = {
                databases: async () => daftar,
                open(nama) {
                    dibuka.push(nama);
                    const r = {};
                    queueMicrotask(() => {
                        r.result = { close() {}, onversionchange: null, transaction: () => {
                            const t = { error: null, objectStore: () => ({
                                getAll() { const q = {}; queueMicrotask(() => { q.result = rekaman.map((x) => ({ ...x })); q.onsuccess?.(); queueMicrotask(() => t.oncomplete?.()); }); return q; },
                                put(x) { ditulis.push(x); },
                            }) };
                            return t;
                        } };
                        r.onsuccess?.();
                    });
                    return r;
                },
            };
            await simpan.cabutRekamJejakSesi('k1');
            const tanpaBasisData = dibuka.length;
            delete globalThis.indexedDB.databases;
            await simpan.cabutRekamJejakSesi('k1');
            const tanpaDaftarKunciLain = dibuka.length;
            globalThis.indexedDB.databases = async () => daftar;
            daftar = [{ name: 'memount-luring-k1', version: 1 }];
            await simpan.cabutRekamJejakSesi('k1');
            const sesudah = { dibuka: dibuka.slice(), ditulis: ditulis.slice() };
            simpan.tutupKunci('k1');
            await simpan.cabutRekamJejakSesi('k1');
            process.stdout.write(JSON.stringify({ tanpaBasisData, tanpaDaftarKunciLain, sesudah, dibukaSesudahTutup: dibuka.length, ditulisSesudahTutup: ditulis.length }));
            JS);

        $this->assertSame(0, $hasil['tanpaBasisData'], 'Tanpa basis data paket, indexedDB.open tidak dipanggil.');
        $this->assertSame(0, $hasil['tanpaDaftarKunciLain'], 'Tanpa indexedDB.databases(), kunci tersimpan orang lain tidak membuat basis data dibuka.');
        $this->assertSame(['memount-luring-k1'], $hasil['sesudah']['dibuka']);
        $this->assertSame([
            ['tripId' => 9, 'trip' => ['berlangsung' => true, 'mulai' => 'm9', 'jedaSejak' => null, 'jedaDetik' => 0], 'tercapai' => 2, 'sesiId' => 41, 'rekamJejak' => false, 'jejak' => '/hike/41/track', 'diperbarui' => 'd9'],
            ['tripId' => 5, 'trip' => ['berlangsung' => true, 'mulai' => 'm5', 'jedaSejak' => 'j5', 'jedaDetik' => 60], 'tercapai' => 4, 'sesiId' => 17, 'rekamJejak' => false, 'jejak' => '/hike/17/track', 'diperbarui' => 'd5'],
        ], $hasil['sesudah']['ditulis'], 'Semua rekaman yang masih menyala menjadi false, field lain tetap.');
        $this->assertSame(1, $hasil['dibukaSesudahTutup'], 'Kunci yang sudah ditutup tidak dibuka.');
        $this->assertSame(2, $hasil['ditulisSesudahTutup'], 'Kunci yang sudah ditutup tidak ditulis.');
    }

    /**
     * Kalimat daftar paket yang dihitung (PT9-14 bagian a, W6-9c), satu kasus per cabang:
     * laporan bersegmen tampil per segmen di mode lapangan, laporan tanpa segmen tidak tampil
     * di mana pun luring, dan jumlahnya paling banyak sepuluh terbaru. Persiapan berupa hitungan
     * butir yang berlaku, bukan persen (R-005). Daftar diurutkan menurut tanggal trip lalu id
     * (PT9-13), dengan fixture yang sengaja terbalik.
     */
    public function test_the_pack_list_sentences_are_true_in_every_branch(): void
    {
        $laporan = fn (?int $segmen) => ['segmen' => $segmen, 'kondisi' => 'Licin', 'catatan' => null, 'waktu' => 'mendaki 5 Okt'];
        $kasus = [[], [$laporan(3), $laporan(4)], [$laporan(3), $laporan(null), $laporan(null)], [$laporan(null)]];
        $persiapan = [
            ['dikonfirmasi' => 0, 'berlaku' => 0, 'jumlah' => 0, 'butir' => []],
            ['dikonfirmasi' => 0, 'berlaku' => 0, 'jumlah' => 2, 'butir' => []],
            ['dikonfirmasi' => 3, 'berlaku' => 5, 'jumlah' => 6, 'butir' => []],
        ];
        $rekaman = fn (int $id, string $tanggal) => ['rekaman' => ['tripId' => $id, 'versi' => 'v', 'siap' => true, 'diverifikasi' => null, 'manifest' => ['trip' => ['id' => $id, 'tanggal' => $tanggal]]]];
        $daftar = [$rekaman(9, '2026-11-02'), $rekaman(5, '2026-10-22'), $rekaman(3, '2026-11-02'), $rekaman(4, '2026-10-22')];

        $hasil = $this->node(['rakit' => 'luring/rakit.ts'], 'process.stdout.write(JSON.stringify({'
            .'laporan: '.json_encode($kasus).'.map((l) => rakit.kalimatLaporanPaket(l)),'
            .'persiapan: '.json_encode($persiapan).'.map((p) => rakit.kalimatPersiapanPaket(p)),'
            .'urutan: rakit.urutDaftarPaket('.json_encode($daftar).').map((p) => p.rekaman.tripId),'
            .'}));');

        $this->assertSame([
            'Tidak ada laporan komunitas 30 hari terakhir saat paket dibuat.',
            '2 laporan komunitas 30 hari terakhir saat paket dibuat, paling banyak 10 terbaru. Yang bersegmen tampil per segmen di mode lapangan, satu laporan terbaru untuk setiap segmen.',
            '3 laporan komunitas 30 hari terakhir saat paket dibuat, paling banyak 10 terbaru. Yang bersegmen tampil per segmen di mode lapangan, satu laporan terbaru untuk setiap segmen. 2 laporan tanpa segmen tidak tampil di mode lapangan.',
            '1 laporan komunitas 30 hari terakhir saat paket dibuat, paling banyak 10 terbaru. 1 laporan tanpa segmen tidak tampil di mode lapangan.',
        ], $hasil['laporan']);
        $this->assertSame([
            'Daftar persiapan belum ada saat paket dibuat.',
            'Tidak ada butir persiapan yang berlaku.',
            '3 dari 5 butir dikonfirmasi.',
        ], $hasil['persiapan']);
        $this->assertSame([4, 5, 3, 9], $hasil['urutan']);
    }

    /**
     * Daftar paket di shell (PT9-4, PT9-12, PT9-13, PT9-19 bagian a, W6-9c), asersi sumber
     * karena komponennya React dan dipotret di pratinjau luring:
     * - waktu sekarang diperbarui tiap menit dan saat halaman terlihat lagi, supaya Buka yang
     *   melewati ambangnya turun walau shell dibiarkan terbuka (R-012, R-013);
     * - status, cuaca, dan laporan masing-masing satu baris "terakhir diketahui" dengan ikon jam
     *   dan garis putus-putus, status dengan label jenis sumbernya dan laporan dengan label
     *   Komunitas (R-009, R-010);
     * - nama trip tidak melebarkan halaman 320 px, label jalur menyebut gunungnya, jam berzona,
     *   lencana Data contoh per paket, dan tombol buka bernama aksesibel berkonteks;
     * - rencana dan aturan di <details> tertutup bawaan dengan kalimat aturan tiap cabang.
     */
    public function test_the_pack_list_marks_every_last_known_fact(): void
    {
        $daftar = File::get(resource_path('js/luring/DaftarPaket.tsx'));

        $this->assertStringContainsString('useState(sekarangMs)', $daftar);
        $this->assertMatchesRegularExpression('/setInterval\(perbarui, 60_000\)/', $daftar);
        $this->assertStringContainsString("addEventListener('visibilitychange', saatTerlihat)", $daftar);
        $this->assertStringContainsString('kalimatStatusLuring(p.dinamis.status, sekarang)', $daftar);

        $this->assertSame(3, substr_count($daftar, '<TerakhirDiketahui'));
        $this->assertStringContainsString('<Icon name="clock" size={20} />', $daftar);
        $this->assertStringContainsString('border-dashed', $daftar);
        $this->assertStringContainsString('<LabelSumber jenis={jenisSumber(p.dinamis.status.jenisSumber)} />', $daftar);
        $this->assertStringContainsString('<LabelKomunitas />', $daftar);
        $this->assertStringContainsString('kalimatPembatasan(', $daftar);
        $this->assertStringContainsString('kalimatAreaTerbatas(', $daftar);

        $this->assertStringContainsString('min-w-0 [overflow-wrap:anywhere]', $daftar);
        $this->assertStringContainsString("timeZoneName: 'short'", $daftar);
        $this->assertStringContainsString('aria-label={`Buka mode lapangan: ${labelPaket(p.rekaman.manifest.trip)}`}', $daftar);
        $this->assertStringContainsString('urutDaftarPaket(paket)', $daftar);
        $this->assertStringContainsString('p.rekaman.manifest.dataContoh ?', $daftar);

        // Baris cuaca per hari dari fungsi murni yang teruji Node, dan dirender (tinjauan m-2 bagian b).
        $this->assertStringContainsString('barisCuacaPaket(p.dinamis.cuaca).map((h) =>', $daftar);
        $this->assertStringContainsString('{h.baris.map((b) => (', $daftar);

        // Kegagalan membuka paket tampil di kartu paket itu dan menerima fokus (tinjauan m-1).
        $this->assertMatchesRegularExpression('#<p\s+id=\{`luring-galat-\$\{kunciPaket\(p\)\}`\}\s+role="alert"\s+tabIndex=\{-1\}#', $daftar);
        $this->assertStringContainsString('wilayahGalat.current.get(kunciGalat(galat))?.focus();', $daftar);

        $this->assertStringContainsString('<details', $daftar);
        $this->assertStringContainsString('Rencana dan aturan', $daftar);
        $this->assertStringContainsString("'Belum diverifikasi Memount. Pastikan di pos registrasi.'", $daftar);
        $this->assertStringContainsString('Belum diketahui', $daftar);
    }

    /**
     * Shell (bentuk wajib task-9-tambahan.md): ketukan ganda tidak memasang dua aplikasi (PT9-23),
     * setiap kegagalan disebut di wilayah yang selalu terpasang (PT9-2, PT9-3), pesan luring
     * baru disembunyikan sesudah mode lapangan terpasang lalu fokus pindah ke #konten, antrean
     * memakai IndexedDB paket berkunci perangkat dan menyerap sessionStorage (W6-9a), dan tema
     * diterapkan dari cookie sekarang (PT9-19 bagian b).
     */
    public function test_the_shell_guards_double_taps_and_never_fails_silently(): void
    {
        $shell = File::get(resource_path('js/luring/shell.tsx'));

        $this->assertMatchesRegularExpression('/if \(membuka\) \{\s*return;\s*\}\s*membuka = true;/', $shell);
        $this->assertStringContainsString("document.getElementById('luring-galat')", $shell);
        $this->assertStringContainsString("document.getElementById('luring-kabar')", $shell);
        $this->assertStringContainsString('createRoot(el, { onUncaughtError: () => gagalBuka(paket) })', $shell);
        // Kegagalan membuka ditulis di kartu paket yang diketuk begitu daftar tergambar (tinjauan
        // m-1); wilayah global #luring-galat hanya cadangan bila daftar belum ada.
        $this->assertMatchesRegularExpression('/if \(tampilkanDaftar\) \{\s*tampilkanDaftar\(\{ tripId: paket\.tripId, versi: paket\.versi, pesan: kalimatGagalBuka\(labelTrip\(paket\)\) \}\);\s*return;\s*\}/', $shell);
        $this->assertStringContainsString('tampilkanDaftar = (galat) => akar.render(<DaftarPaket paket={paket} sekarangMs={sekarangMs} galat={galat}', $shell);
        $this->assertStringContainsString('return labelPaket(paket.manifest.trip);', $shell);

        // Pesan luring disembunyikan sekali, di SesudahPasang, tidak pernah dibuang: daftar dan
        // wilayah galat ada di dalamnya (tinjauan m-2 bagian a, cacat brief PT9-2).
        $this->assertDoesNotMatchRegularExpression("/getElementById\('luring-pesan'\)\??\.remove\(\)/", $shell);
        $this->assertSame(1, substr_count($shell, "classList.add('hidden')"));
        $this->assertSame(1, preg_match('/function SesudahPasang\(.*?\n\}/s', $shell, $sesudahPasang));
        $this->assertStringContainsString("classList.add('hidden')", $sesudahPasang[0]);

        // Ruling W6-9k: halaman Inertia rakitan memakai alamat yang sedang dibuka, bukan /offline,
        // supaya muat ulang dengan sinyal kembali ke halaman asal.
        $this->assertStringContainsString('url: window.location.pathname + window.location.search,', $shell);
        $this->assertStringNotContainsString("url: '/offline'", $shell);

        // Ruling W6-9l: halaman Hike dimuat malas, supaya kunci halamannya tetap ada di manifest
        // build dan pramuat mode lapangan daring tidak hilang (PramuatHalamanTest).
        $this->assertDoesNotMatchRegularExpression('/^import Hike from/m', $shell);
        $this->assertStringContainsString("await import('@/pages/Trips/Hike')", $shell);
        $this->assertStringContainsString("document.getElementById('konten')?.focus({ preventScroll: true });", $shell);
        $this->assertStringContainsString('window.scrollTo(0, 0);', $shell);
        $this->assertStringContainsString('Promise.allSettled(', $shell);
        $this->assertStringContainsString('pakaiPenyimpanan(penyimpananGabungan(penyimpananIndexedDb(db), penyimpananSessionStorage(kunci)), kunci);', $shell);
        $this->assertStringContainsString('propsHalamanLuring(data, paket.manifest, namaMerek())', $shell);
        $this->assertStringContainsString('window.terapkanTema?.();', $shell);
        $this->assertStringContainsString('mulai().catch(() => kabar(TIDAK_TERBACA));', $shell);
    }

    /**
     * Label paket unik untuk dua paket di jalur yang sama (tinjauan m-1, butir 3): nama trip, jalur,
     * dan gunung tanpa pengulangan, ditambah tanggal trip. Tanggalnya tanggal kalender di gunung
     * dari manifest (Y-m-d), ditulis tanpa konversi zona perangkat, jadi tidak bergeser sehari di
     * zona lain. Kalimat gagal membuka menyebut paketnya.
     */
    public function test_each_pack_is_named_by_trip_trail_mountain_and_date(): void
    {
        $trip = fn (string $nama, string $tanggal, ?string $gunung = 'Gunung Gede') => ['id' => 1, 'nama' => $nama, 'jalur' => 'Gede via Cibodas', 'gunung' => $gunung, 'tanggal' => $tanggal];
        $kasus = [
            $trip('Pendakian Gede via Cibodas', '2026-10-24'),
            $trip('Pendakian Gede via Cibodas', '2026-11-07'),
            $trip('Gede via Cibodas', '2026-01-01'),
            $trip('Gede bersama kantor', '2026-12-31', null),
        ];

        $hasil = $this->node(['rakit' => 'luring/rakit.ts'], 'process.stdout.write(JSON.stringify({'
            .'label: '.json_encode($kasus).'.map((t) => rakit.labelPaket(t)),'
            .'gagal: rakit.kalimatGagalBuka("Gede via Cibodas, Gunung Gede, 24 Okt 2026"),'
            .'}));');

        $this->assertSame([
            'Pendakian Gede via Cibodas, Gede via Cibodas, Gunung Gede, 24 Okt 2026',
            'Pendakian Gede via Cibodas, Gede via Cibodas, Gunung Gede, 7 Nov 2026',
            'Gede via Cibodas, Gunung Gede, 1 Jan 2026',
            'Gede bersama kantor, Gede via Cibodas, 31 Des 2026',
        ], $hasil['label']);
        $this->assertSame('Mode lapangan dari paket Gede via Cibodas, Gunung Gede, 24 Okt 2026 belum dapat dibuka di perangkat ini. Pakai peta cetak atau perangkat lain, lalu unduh ulang paket selagi ada sinyal.', $hasil['gagal']);
    }

    /**
     * Baris cuaca per hari di daftar paket (W6-9c; tinjauan m-2 bagian b): hari berbaris ditulis
     * "{waktu berzona}: {cuaca}, {suhu}", hari tanpa baris ditulis kalimatnya, dan hari yang
     * kalimatnya sama dengan kalimat ringkas cuaca tidak diulang.
     */
    public function test_weather_rows_per_day_keep_their_zone_and_sentence(): void
    {
        $cuaca = [
            'kalimat' => 'Prakiraan area Cimacan (BMKG), analisis 23 Okt 2026 07.00 WIB.',
            'hari' => [
                ['tanggal' => '2026-10-24', 'label' => 'Sab, 24 Okt', 'kalimat' => null, 'baris' => [['waktu' => '06.00 WIB', 'cuaca' => 'Cerah Berawan', 'suhu' => "18\u{00A0}°C"], ['waktu' => '12.00 WIB', 'cuaca' => 'Hujan Ringan', 'suhu' => "21\u{00A0}°C"]]],
                ['tanggal' => '2026-10-25', 'label' => 'Min, 25 Okt', 'kalimat' => 'Prakiraan BMKG untuk 25 Okt belum tersedia di Memount saat paket dibuat.', 'baris' => []],
            ],
        ];
        $tanpaBaris = ['kalimat' => 'Prakiraan BMKG untuk 2 Nov belum terbit saat paket dibuat.', 'hari' => [['tanggal' => '2026-11-02', 'label' => 'Sen, 2 Nov', 'kalimat' => 'Prakiraan BMKG untuk 2 Nov belum terbit saat paket dibuat.', 'baris' => []]]];

        $hasil = $this->node(['rakit' => 'luring/rakit.ts'], 'process.stdout.write(JSON.stringify(['.json_encode($cuaca, JSON_UNESCAPED_UNICODE).', '.json_encode($tanpaBaris).'].map((c) => rakit.barisCuacaPaket(c))));');

        $this->assertSame([
            ['tanggal' => '2026-10-24', 'label' => 'Sab, 24 Okt', 'baris' => ["06.00 WIB: Cerah Berawan, 18\u{00A0}°C", "12.00 WIB: Hujan Ringan, 21\u{00A0}°C"], 'kalimat' => null],
            ['tanggal' => '2026-10-25', 'label' => 'Min, 25 Okt', 'baris' => [], 'kalimat' => 'Prakiraan BMKG untuk 25 Okt belum tersedia di Memount saat paket dibuat.'],
        ], $hasil[0]);
        $this->assertSame([], $hasil[1]);
    }

    /**
     * Jendela laporan komunitas di kalimat daftar paket berasal dari satu konstanta klien yang
     * wajib sama dengan konfigurasi server (tinjauan m-4), dan kalimatnya memakai jendela yang
     * diberikan, bukan angka tertulis.
     */
    public function test_the_report_window_in_the_pack_list_matches_the_server(): void
    {
        $laporan = [['segmen' => 3, 'kondisi' => 'Licin', 'catatan' => null, 'waktu' => 'mendaki 5 Okt']];

        $hasil = $this->node(['rakit' => 'luring/rakit.ts'], 'process.stdout.write(JSON.stringify({'
            .'jendela: rakit.JENDELA_LAPORAN,'
            .'kosong: rakit.kalimatLaporanPaket([], { hari: 14, batas: 5 }),'
            .'isi: rakit.kalimatLaporanPaket('.json_encode($laporan).', { hari: 14, batas: 5 }),'
            .'}));');

        $this->assertSame(['hari' => (int) config('hiking.conditions.community_recent_days'), 'batas' => (int) config('hiking.conditions.community_report_limit')], $hasil['jendela']);
        $this->assertSame('Tidak ada laporan komunitas 14 hari terakhir saat paket dibuat.', $hasil['kosong']);
        $this->assertStringStartsWith('1 laporan komunitas 14 hari terakhir saat paket dibuat, paling banyak 5 terbaru.', $hasil['isi']);
    }

    /**
     * Lencana Data contoh ikut paket (PT9-19 bagian a): di bagian lapangan untuk mode lapangan
     * dari paket, dan di manifest untuk daftar paket di shell. Kuncinya bukan demo, nama prop
     * bersama server (ReminderSheetTest).
     */
    public function test_the_pack_carries_the_sample_data_flag(): void
    {
        config(['hiking.field_pack.enabled' => true]);
        $pendaki = User::factory()->create();
        $jalur = Trail::factory()->published()->create(['name' => 'Gede via Cibodas']);
        Checkpoint::factory()->for($jalur)->create(['sequence' => 1, 'name' => 'Pintu Cibodas', 'latitude' => -6.74, 'longitude' => 107.0]);
        $trip = TripPlan::factory()->create(['user_id' => $pendaki->id, 'trail_id' => $jalur->id, 'status' => TripStatus::PLANNED->value, 'planned_date' => now()->addDays(10)->toDateString()]);

        foreach ([true, false] as $contoh) {
            config(['app.demo' => $contoh]);
            $manifest = $this->actingAs($pendaki)->getJson(route('trips.pack', $trip))->assertOk()->json();
            $lapangan = json_decode($this->actingAs($pendaki)->get(route('trips.pack.section', [$trip, 'lapangan']))->assertOk()->getContent(), true);

            $this->assertSame([$contoh, $contoh], [$manifest['dataContoh'], $lapangan['dataContoh']]);
            $this->assertArrayNotHasKey('demo', $lapangan);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function lapanganContoh(): array
    {
        return [
            'trip' => ['id' => 7, 'nama' => 'Gede akhir pekan', 'jalur' => 'Gede via Cibodas', 'gunung' => 'Gunung Gede', 'berlangsung' => false, 'mulai' => null, 'jedaSejak' => null, 'jedaDetik' => 0],
            'pos' => [], 'garis' => null, 'profil' => null, 'ruas' => [], 'tercapai' => 0, 'radiusTiba' => 50, 'laporan' => [],
            'rekamJejak' => false, 'sesiId' => null, 'sumber' => 'paket', 'darurat' => null, 'pilihan' => ['hasil' => [], 'alasan' => []],
            'urls' => ['tiba' => '/trips/7/hike/tiba', 'jeda' => '/trips/7/hike/jeda', 'lanjut' => '/trips/7/hike/lanjut', 'selesai' => '/trips/7/selesai', 'ruangKerja' => '/trips/7', 'jejak' => null],
            'dataContoh' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $tambahan
     * @return array<string, mixed>
     */
    private function manifestContoh(array $tambahan = []): array
    {
        return [
            'versi' => '0123456789abcdef',
            'trip' => ['id' => 7, 'nama' => 'Gede akhir pekan', 'jalur' => 'Gede via Cibodas', 'gunung' => 'Gunung Gede', 'tanggal' => '2026-10-22'],
            'bagian' => [],
            'ukuran' => 0,
            'ambang' => ['bukaJam' => 24],
            'atribusi' => ['Garis jalur, pos, dan angka: Balai Besar TNGGP.', 'Peta dasar tidak dibawa paket.', 'Prakiraan cuaca: BMKG.'],
            ...$tambahan,
        ];
    }

    /**
     * @param  array<int, array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>|null}>  $kasus
     * @return array<int, array<string, mixed>>
     */
    private function rakit(array $kasus): array
    {
        return $this->node(['modul' => 'luring/rakit.ts'], 'const kasus = '.json_encode($kasus).';'
            .'process.stdout.write(JSON.stringify(kasus.map(([l, d, s]) => modul.rakitDataLapangan(l, d, s))));');
    }

    /**
     * Menjalankan satu badan modul ES di Node atas modul TypeScript murni yang hanya berimpor
     * tipe (ruling L3), lalu mengurai JSON yang ditulisnya ke stdout.
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

        $this->assertTrue($proses->isSuccessful(), 'Node gagal: '.mb_substr($proses->getErrorOutput(), 0, 600));

        return json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }
}
