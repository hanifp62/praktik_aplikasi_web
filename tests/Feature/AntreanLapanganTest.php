<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Perilaku antrean.ts (ruling W6-R6; I-2/ruling W6-3b): tiba, jeda, dan lanjut dikirim lewat
 * antrean perangkat, bukan langsung ke server. Modul ini sengaja tanpa impor (lihat docblock
 * antrean.ts), jadi diuji dengan menjalankannya di Node lewat proses terpisah, dengan fetch,
 * cookie, dan navigator tiruan, pola yang sama dengan FieldModeFiguresTest.
 *
 * Skenarionya diturunkan dari tinjauan putaran 1 W6 Tugas 3 (w6t3rev/antrean/uji_antrean.mjs),
 * yang menunjukkan lima mutan (A1 sampai A5: 429 dibuang, 401/419 dibuang, penjaga `mengirim`
 * dibuang, token dibaca saat aksi dibuat, galat 4xx lain disimpan) lolos tanpa terdeteksi karena
 * repo sebelumnya tidak punya satu test pun untuk berkas ini.
 */
class AntreanLapanganTest extends TestCase
{
    /**
     * payloadTiba dan payloadJeda adalah fungsi murni, satu tempat yang menentukan bentuk
     * payload (N-2/ruling W6-3k): hanya kunci yang disebut, tanpa posisi, berapa pun jumlah
     * argumen tambahan yang dicoba diselipkan sebagai sumbernya.
     */
    public function test_payload_functions_only_carry_their_named_key(): void
    {
        $skrip = 'import { pathToFileURL } from "node:url";'
            .'const modul = await import(pathToFileURL('.json_encode(resource_path('js/components/mendaki/antrean.ts')).').href);'
            .'process.stdout.write(JSON.stringify([modul.payloadTiba(3), modul.payloadJeda("2026-10-10T01:00:00Z")]));';

        $proses = new Process(['node', '--experimental-strip-types', '--input-type=module', '-'], base_path(), null, $skrip, 60);
        $proses->run();

        $this->assertTrue($proses->isSuccessful(), 'Node gagal menjalankan antrean.ts: '.mb_substr($proses->getErrorOutput(), 0, 600));
        $this->assertSame(
            [['urutan' => 3], ['pada' => '2026-10-10T01:00:00Z']],
            json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR)
        );
    }

    public function test_an_action_is_sent_once_with_only_its_payload_and_a_decoded_token(): void
    {
        $s = $this->skenario('s1');

        $this->assertSame(1, $s['jumlahPanggilan']);
        $this->assertSame(['urutan' => 3], $s['isiPertama']);
        $this->assertSame('token=lama', $s['tokenPertama']);
        $this->assertSame('POST', $s['metodePertama']);
        $this->assertSame('same-origin', $s['kredensialPertama']);
        $this->assertSame(0, $s['sisaAntreanAkhir']);
    }

    public function test_a_network_error_keeps_the_action_for_the_next_replay(): void
    {
        $s = $this->skenario('s2');

        $this->assertSame(1, $s['sisaSesudahGalat']);
        $this->assertSame(0, $s['sisaSesudahPutaranBerikut']);
        $this->assertSame(2, $s['jumlahPanggilanAkhir']);
    }

    /**
     * A2: 401 dan 419 menyimpan aksi dan meminta masuk lagi, bukan membuangnya.
     */
    public function test_expired_session_responses_keep_the_action_and_ask_to_sign_in_again(): void
    {
        foreach ([401, 419] as $status) {
            $s = $this->skenario('s3', (string) $status);

            $this->assertSame(1, $s['sisaAntrean'], "status {$status} seharusnya menyimpan aksi");
            $this->assertStringContainsString('Masuk lagi', $s['pesan'] ?? '', "status {$status} seharusnya meminta masuk lagi");
        }
    }

    /**
     * A1: 429 dibuang pada mutan, padahal batas laju harus disimpan untuk putaran berikutnya.
     * Disertakan juga 500 dan 503 pada aksi server yang sedang sibuk atau tidak tersedia.
     */
    public function test_rate_limit_and_server_error_responses_keep_the_action(): void
    {
        foreach ([429, 500, 503] as $status) {
            $s = $this->skenario('s5', (string) $status);

            $this->assertSame(1, $s['sisaAntrean'], "status {$status} seharusnya menyimpan aksi");
            $this->assertSame(1, $s['jumlahPanggilan'], "status {$status} seharusnya hanya mencoba sekali per putaran");
        }
    }

    /**
     * A5: galat 4xx lain (selain 401/419/429) tetap tersimpan pada mutan, padahal 403, 404, 409,
     * dan 422 adalah penolakan permanen yang harus dibuang dengan pesan dari server.
     */
    public function test_permanent_rejections_discard_the_action_with_the_servers_reason(): void
    {
        foreach ([403, 404, 409, 422] as $status) {
            $s = $this->skenario('s6', (string) $status);

            $this->assertSame(0, $s['sisaAntrean'], "status {$status} seharusnya membuang aksi");
            $this->assertStringContainsString('tidak diterima', $s['pesan'] ?? '', "status {$status} seharusnya menyebut alasannya");
        }
    }

    /**
     * A4: token dibaca saat KIRIM, bukan saat aksi dibuat. Cookie yang berganti sesudah aksi
     * disimpan (misalnya sesudah muat ulang) harus tetap terpakai pada putaran berikutnya.
     */
    public function test_the_xsrf_token_is_read_at_send_time_not_when_the_action_was_queued(): void
    {
        $s = $this->skenario('s7');

        $this->assertSame(0, $s['panggilanSaatLuring']);
        $this->assertSame('token=baru', $s['tokenSaatKirim']);
    }

    /**
     * A3: penjaga `mengirim` mencegah dua putaran yang tumpang tindih mengirim aksi yang sama
     * dua kali. Tanpa penjaga itu, urutannya menjadi /a,/a,/a,/b alih-alih /a,/b.
     */
    public function test_overlapping_replays_do_not_send_the_same_action_twice(): void
    {
        $s = $this->skenario('s8');

        $this->assertSame('/a,/b', $s['urutanPanggilan']);
        $this->assertSame(0, $s['sisaAntrean']);
    }

    public function test_a_failing_action_in_front_holds_back_the_one_behind_it_in_order(): void
    {
        $s = $this->skenario('s9');

        $this->assertSame(1, $s['jumlahPanggilanSetelahGagal']);
        $this->assertSame(2, $s['sisaSetelahGagal']);
        $this->assertSame('/jeda,/jeda,/lanjut', $s['urutanSetelahPutaranBerikutnya']);
        $this->assertSame(0, $s['sisaSetelahPutaranBerikutnya']);
    }

    public function test_the_rejection_message_clears_after_a_later_action_succeeds(): void
    {
        $s = $this->skenario('s10');

        $this->assertStringContainsString('Pos itu tidak ada', $s['pesanTolak'] ?? '');
        $this->assertNull($s['pesanAkhir']);
    }

    /**
     * Kalimat wilayah status antrean benar di setiap keadaan (Q-12/ruling W6-3z). Sebelum ini
     * "menunggu sinyal" tertulis juga saat daring: selama kiriman berjalan, saat server menjawab
     * 503, dan sesudah muat penuh sampai putaran berikutnya. 401 dan 419 memakai pesan sesinya
     * sendiri sesudah jumlahnya. Tanpa aksi menunggu, kalimatnya "Luring." saat luring, diikuti
     * pesan bila ada, dan kosong saat daring tanpa pesan.
     *
     * Diubah putaran perbaikan 4b (ruling W6-3z2): kasus 'kosong, luring' semula menuntut kalimat
     * kosong, bunyi ruling W6-3z yang menghapus satu-satunya penanda luring mode lapangan. Kini
     * menuntut "Luring." seperti sebelum putaran 4, dan kasus luring kosong dengan pesan ditambah.
     */
    public function test_the_queue_sentence_is_true_in_every_state(): void
    {
        $keadaan = fn (int $jumlah, ?string $pesan = null, bool $mengirim = false, ?string $gagal = null): array => compact('jumlah', 'pesan', 'mengirim', 'gagal');
        $sesi = 'Sesi Anda berakhir. Masuk lagi supaya aksi yang menunggu terkirim.';
        $tolak = 'Satu aksi tidak diterima: Pos itu tidak ada di jalur trip ini.';

        $kasus = [
            'luring, 2 menunggu' => [false, $keadaan(2), 'Luring. 2 catatan menunggu sinyal untuk dikirim.'],
            'luring, kiriman terakhir gagal' => [false, $keadaan(1, gagal: 'sementara'), 'Luring. 1 catatan menunggu sinyal untuk dikirim.'],
            'daring, sedang mengirim' => [true, $keadaan(1, mengirim: true), '1 catatan sedang dikirim.'],
            'daring, kiriman terakhir gagal' => [true, $keadaan(2, gagal: 'sementara'), '2 catatan belum terkirim. Memount mencoba lagi otomatis.'],
            'daring, dicoba lagi sesudah gagal' => [true, $keadaan(2, mengirim: true, gagal: 'sementara'), '2 catatan belum terkirim. Memount mencoba lagi otomatis.'],
            'daring, sesi berakhir (401 atau 419)' => [true, $keadaan(1, $sesi, gagal: 'sesi'), '1 catatan belum terkirim. '.$sesi],
            'daring, belum dicoba' => [true, $keadaan(1), '1 catatan menunggu untuk dikirim.'],
            'kosong, daring' => [true, $keadaan(0), ''],
            'kosong, luring' => [false, $keadaan(0), 'Luring.'],
            'kosong, luring, dengan pesan penolakan' => [false, $keadaan(0, $tolak), 'Luring. '.$tolak],
            'kosong dengan pesan penolakan' => [true, $keadaan(0, $tolak), $tolak],
            'luring dengan pesan sesi' => [false, $keadaan(1, $sesi, gagal: 'sesi'), 'Luring. 1 catatan menunggu sinyal untuk dikirim. '.$sesi],
        ];

        $hasil = $this->panggil(array_map(fn (array $satu): array => ['teksAntrean', [$satu[0], $satu[1]]], array_values($kasus)));

        $this->assertSame(array_map(fn (array $satu): string => $satu[2], $kasus), array_combine(array_keys($kasus), $hasil));
    }

    /**
     * Keadaan "sedang mengirim" dan "gagal terakhir" ikut dikabarkan dengarAntrean (W6-3z), dan
     * kalimat yang sama tidak diumumkan ulang di setiap putaran. Wilayah status hanya
     * mengumumkan saat isinya berubah, jadi percobaan ulang sesudah kegagalan (dua 503 lalu galat
     * jaringan) wajib menghasilkan kalimat yang sama dengan akhir putaran pertama, bukan bolak-
     * balik antara "sedang dikirim" dan "belum terkirim". Sesudah terkirim, gagal kembali null.
     */
    public function test_retries_after_a_failure_keep_one_sentence_and_report_their_state(): void
    {
        $belumTerkirim = '1 catatan belum terkirim. Memount mencoba lagi otomatis.';
        $s = $this->skenario('s11');

        $this->assertContains('1 catatan sedang dikirim.', $s['teksPutaran1'], 'Keadaan sedang mengirim wajib dikabarkan selama kiriman berjalan.');
        $this->assertSame($belumTerkirim, end($s['teksPutaran1']));
        $this->assertSame(['jumlah' => 1, 'pesan' => null, 'mengirim' => false, 'gagal' => 'sementara'], $s['keadaanSesudahGagal']);
        $this->assertNotEmpty($s['teksPutaranUlang']);
        $this->assertSame([$belumTerkirim], array_values(array_unique($s['teksPutaranUlang'])), 'Percobaan ulang tidak boleh mengganti kalimat bolak-balik.');
        $this->assertSame(['jumlah' => 0, 'pesan' => null, 'mengirim' => false, 'gagal' => null], $s['keadaanSesudahTerkirim']);

        foreach ([401, 419] as $status) {
            $sesi = $this->skenario('s12', (string) $status);

            $this->assertSame(['jumlah' => 1, 'pesan' => 'Sesi Anda berakhir. Masuk lagi supaya aksi yang menunggu terkirim.', 'mengirim' => false, 'gagal' => 'sesi'], $sesi['keadaan'], "status {$status}");
        }
    }

    /**
     * Mode lapangan memakai keadaan antrean apa adanya (W6-3z): komponen menulis kalimat wilayah
     * status hanya lewat teksAntrean, hook memasang antrean lewat pasangAntrean (dengar lalu putar
     * sekali saat dipasang, perilakunya diuji Node di PenyimpananSesiLapanganTest), dan putaran 30
     * detik yang membuat "Memount mencoba lagi otomatis." benar tetap ada.
     *
     * Diperketat putaran 4b (ruling W6-3z2): regex wilayah status kini menuntut bingkai (border dan
     * latar) pada kalimat yang tidak kosong, jadi bingkai ikut tampil saat luring dengan antrean
     * kosong ("Luring."), seperti sebelum putaran 4.
     */
    public function test_field_mode_renders_the_queue_sentence_and_replays_on_mount(): void
    {
        $hook = File::get(resource_path('js/components/mendaki/useModeLapangan.ts'));
        $komponen = File::get(resource_path('js/components/mendaki/ModeLapangan.tsx'));

        $this->assertStringContainsString('const lepas = pasangAntrean(setAntrean);', $hook);
        $this->assertStringNotContainsString('dengarAntrean(', $hook, 'Hook memasang antrean lewat pasangAntrean, bukan dengarAntrean tanpa putaran saat dipasang.');
        $this->assertMatchesRegularExpression('/window\.setInterval\(\(\) => void putarAntrean\(\), 30_000\)/', $hook, 'Putaran 30 detik wajib tetap ada.');

        $this->assertStringContainsString('const teksStatusAntrean = teksAntrean(mode.daring, mode.antrean);', $komponen);
        $this->assertMatchesRegularExpression(
            "/<p role=\"status\" aria-atomic=\"true\" className=\\{kelas\\('text-kecil text-primary', teksStatusAntrean !== '' && 'rounded-kecil border border-subtle bg-surface-sunken px-3 py-2'\\)\\}>\\s*\\{teksStatusAntrean\\}\\s*<\\/p>/",
            $komponen,
            'Wilayah status antrean selalu terpasang, hanya berisi teksAntrean, dan berbingkai selama kalimatnya tidak kosong.',
        );
        $this->assertStringNotContainsString('menunggu sinyal', $komponen, 'Kalimat antrean hanya dari teksAntrean.');
    }

    /**
     * Ruling W6-FF (M-3 tinjauan akhir W6, mutasi D1b): penyimpanan sessionStorage berpemilik
     * dipasang SEBELUM pasangAntrean, supaya putaran saat dipasang memuat antrean milik pengguna
     * ini, bukan memori tanpa pemilik. Asersi urutan sumber, karena hook React tidak dijalankan di
     * suite ini; perilaku pasangAntrean sendiri diuji Node di PenyimpananSesiLapanganTest.
     */
    public function test_the_hook_installs_its_storage_before_it_starts_listening(): void
    {
        $hook = File::get(resource_path('js/components/mendaki/useModeLapangan.ts'));
        $pasangPenyimpanan = strpos($hook, 'pakaiPenyimpanan(penyimpananSessionStorage(kunci), kunci);');
        $dengar = strpos($hook, 'const lepas = pasangAntrean(setAntrean);');

        $this->assertNotFalse($pasangPenyimpanan);
        $this->assertNotFalse($dengar);
        $this->assertSame(1, substr_count($hook, 'pasangAntrean('), 'pasangAntrean dipanggil tepat sekali.');
        $this->assertLessThan($dengar, $pasangPenyimpanan, 'Penyimpanan berpemilik wajib dipasang sebelum pasangAntrean.');
    }

    /**
     * Ruling W6-FC (I-2 tinjauan akhir W6): Selesai tertahan hanya oleh tiba, jeda, dan lanjut trip
     * itu yang masih menunggu. Antrean kosong atau milik trip lain tidak menahan; antrean yang tidak
     * terbaca (null) juga tidak, yaitu perilaku sebelum ruling. Elemen yang bukan aksi sah dan id
     * ganda tidak dihitung, sama dengan antrean yang benar-benar dikirim.
     */
    public function test_finishing_is_held_only_by_this_trips_waiting_actions(): void
    {
        $url = ['tiba' => '/trips/7/hike/tiba', 'jeda' => '/trips/7/hike/jeda', 'lanjut' => '/trips/7/hike/lanjut'];
        $aksi = fn (string $id, string $alamat): array => ['id' => $id, 'url' => $alamat, 'isi' => (object) [], 'dibuat' => 1];

        $hasil = $this->panggil([
            ['aksiMenahanSelesai', [[], $url]],
            ['aksiMenahanSelesai', [[$aksi('a1', '/trips/7/hike/tiba'), $aksi('a2', '/trips/7/hike/lanjut'), $aksi('a3', '/trips/8/hike/jeda')], $url]],
            ['aksiMenahanSelesai', [null, $url]],
            ['aksiMenahanSelesai', [[$aksi('a1', '/trips/7/hike/jeda'), $aksi('a1', '/trips/7/hike/jeda'), null, ['url' => '/trips/7/hike/tiba']], $url]],
        ]);

        $this->assertSame([0, 2, 0, 1], $hasil);
    }

    /**
     * selesaiTertahan, fungsi murni satu tempat untuk keputusan Selesai tertahan (N-1/temuan
     * pemeriksa final-fix-check; menutup mutan FC1b dan FC4b): kosong (jumlah aksi yang menahan
     * nol) dan tidak terbaca (aksi null) memberi null, sama dengan perilaku sebelum ruling
     * W6-FC; menunggu memberi kalimat sesuai tempat dan sesi (kalimatSelesaiTertahan), dengan
     * tautan hanya bila diberikan. SelesaiLapangan.tsx dan tahanSelesai di Trips/Show.tsx hanya
     * memanggil fungsi ini (asersi sumber di test_finishing_waits_for_the_queue_in_field_mode_and_checks_it_in_the_workspace).
     */
    public function test_the_held_finish_decision_is_null_when_empty_or_unreadable_and_the_right_sentence_when_waiting(): void
    {
        $url = ['tiba' => '/trips/7/hike/tiba', 'jeda' => '/trips/7/hike/jeda', 'lanjut' => '/trips/7/hike/lanjut'];
        $aksi = fn (string $id, string $alamat): array => ['id' => $id, 'url' => $alamat, 'isi' => (object) [], 'dibuat' => 1];
        $menahan = [$aksi('a1', '/trips/7/hike/tiba'), $aksi('a2', '/trips/7/hike/lanjut')];
        $tautan = ['href' => '/trips/7/hike', 'label' => 'Buka mode lapangan'];

        $hasil = $this->panggil([
            ['selesaiTertahan', [[], $url, 'lapangan']],
            ['selesaiTertahan', [null, $url, 'lapangan']],
            ['selesaiTertahan', [$menahan, $url, 'lapangan']],
            ['selesaiTertahan', [$menahan, $url, 'lapangan', true]],
            ['selesaiTertahan', [$menahan, $url, 'ruang-kerja', false, $tautan]],
        ]);

        $this->assertSame([
            null,
            null,
            ['kalimat' => '2 catatan tiba atau jeda belum terkirim. Tunggu sampai terkirim saat ada sinyal, lalu simpan.'],
            ['kalimat' => '2 catatan tiba atau jeda belum terkirim karena sesi Anda berakhir. Masuk lagi supaya catatan itu terkirim, lalu simpan.'],
            ['kalimat' => '2 catatan tiba atau jeda dari perangkat ini belum terkirim. Buka mode lapangan supaya catatan itu terkirim, lalu selesaikan pendakian.', 'tautan' => $tautan],
        ], $hasil);
    }

    /**
     * Kalimat Selesai yang tertahan (ruling W6-FC), satu per tempat. Di mode lapangan sesudah
     * kiriman terakhir dijawab 401 atau 419, menunggu sinyal tidak menolong, jadi kalimatnya
     * mengajak masuk lagi (daftar periksa butir 2).
     */
    public function test_the_held_finish_sentence_is_exact_for_each_place(): void
    {
        $hasil = $this->panggil([
            ['kalimatSelesaiTertahan', [2, 'lapangan']],
            ['kalimatSelesaiTertahan', [1, 'lapangan', true]],
            ['kalimatSelesaiTertahan', [3, 'ruang-kerja']],
        ]);

        $this->assertSame([
            '2 catatan tiba atau jeda belum terkirim. Tunggu sampai terkirim saat ada sinyal, lalu simpan.',
            '1 catatan tiba atau jeda belum terkirim karena sesi Anda berakhir. Masuk lagi supaya catatan itu terkirim, lalu simpan.',
            '3 catatan tiba atau jeda dari perangkat ini belum terkirim. Buka mode lapangan supaya catatan itu terkirim, lalu selesaikan pendakian.',
        ], $hasil);
    }

    /**
     * Ruang kerja membaca antrean perangkat tanpa pernah membuat basis data (ruling W6-FC, pola
     * W6-P2g), dengan sessionStorage dan IndexedDB tiruan:
     * - sessionStorage yang menolak: null, jadi Selesai dikirim seperti sebelum ruling;
     * - basis data paket tidak ada menurut indexedDB.databases(): hanya sessionStorage, dan open()
     *   tidak pernah dipanggil;
     * - basis data paket ada: kedua sumber digabung menurut id;
     * - tanpa databases(), pembukaan yang memicu upgradeneeded dibatalkan, jadi basis data baru
     *   tidak tersimpan, dan hasilnya kosong;
     * - pembukaan yang galat tanpa upgrade: null.
     */
    public function test_the_workspace_reads_waiting_actions_without_creating_a_database(): void
    {
        $skrip = 'import { pathToFileURL } from "node:url";'
            .'const modul = await import(pathToFileURL('.json_encode(resource_path('js/components/mendaki/antrean.ts')).').href);'
            .<<<'JS'
            const aksi = (id, url, dibuat) => ({ id, url, isi: {}, dibuat });
            const ids = (daftar) => (daftar === null ? null : daftar.map((a) => a.id));
            const nama = 'memount-luring-k1';
            const hasil = {};

            globalThis.sessionStorage = { getItem() { throw new Error('diblokir'); } };
            hasil.sesiDitolak = await modul.aksiTersimpanDiPerangkat('k1', nama);

            const sesi = { 'mendaki.antrean.k1': JSON.stringify([aksi('a1', '/trips/7/hike/tiba', 1)]) };
            globalThis.sessionStorage = { getItem: (k) => sesi[k] ?? null };
            let dibuka = 0;
            globalThis.indexedDB = { databases: async () => [{ name: 'memount-luring-lain' }], open() { dibuka++; throw new Error('tidak boleh dibuka'); } };
            hasil.tanpaBasisData = { aksi: ids(await modul.aksiTersimpanDiPerangkat('k1', nama)), dibuka };

            const basisData = (isi) => {
                const db = { ditutup: false, objectStoreNames: { contains: (n) => n === 'antrean' }, close() { db.ditutup = true; } };
                db.transaction = (toko, mode) => ({ objectStore: () => ({ get: (kunci) => { const r = {}; queueMicrotask(() => { r.result = toko === 'antrean' && mode === 'readonly' && kunci === 'aksi' ? isi : undefined; r.onsuccess?.(); }); return r; } }) });
                return db;
            };
            const db = basisData([aksi('a2', '/trips/7/hike/jeda', 2), aksi('a1', '/trips/7/hike/tiba', 1)]);
            globalThis.indexedDB = { databases: async () => [{ name: nama }], open: (n) => { const r = {}; queueMicrotask(() => { r.result = n === nama ? db : null; r.onsuccess?.(); }); return r; } };
            hasil.denganBasisData = { aksi: ids(await modul.aksiTersimpanDiPerangkat('k1', nama)), ditutup: db.ditutup };

            let dibatalkan = 0;
            globalThis.indexedDB = { open() { const r = { transaction: { abort() { dibatalkan++; } } }; queueMicrotask(() => { r.onupgradeneeded?.(); r.onerror?.({ preventDefault() {} }); }); return r; } };
            hasil.belumAda = { aksi: ids(await modul.aksiTersimpanDiPerangkat('k1', nama)), dibatalkan };

            globalThis.indexedDB = { open() { const r = {}; queueMicrotask(() => r.onerror?.({ preventDefault() {} })); return r; } };
            hasil.galat = await modul.aksiTersimpanDiPerangkat('k1', nama);

            process.stdout.write(JSON.stringify(hasil));
            JS;

        $proses = new Process(['node', '--experimental-strip-types', '--input-type=module', '-'], base_path(), null, $skrip, 60);
        $proses->run();

        $this->assertTrue($proses->isSuccessful(), 'Node gagal menjalankan antrean.ts: '.mb_substr($proses->getErrorOutput(), 0, 600));
        $this->assertSame([
            'sesiDitolak' => null,
            'tanpaBasisData' => ['aksi' => ['a1'], 'dibuka' => 0],
            'denganBasisData' => ['aksi' => ['a1', 'a2'], 'ditutup' => true],
            'belumAda' => ['aksi' => ['a1'], 'dibatalkan' => 1],
            'galat' => null,
        ], json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR));
    }

    /**
     * Sambungan W6-FC (asersi sumber, karena React tidak dijalankan di suite ini):
     * - lembar Selesai mode lapangan memutar antrean lebih dulu, menunggu hasilnya, lalu menghitung
     *   aksi trip ini dari antrean yang sama;
     * - ruang kerja membaca antrean perangkat lewat aksiTersimpanDiPerangkat dengan url tiba, jeda,
     *   dan lanjut trip itu, menautkan mode lapangan, dan tidak memutar antrean;
     * - FormulirSelesai menjalankan `tahan` sebelum kiriman dan berhenti tanpa mengirim selama ada
     *   kalimat, dengan fokus kembali ke tombol Simpan; kalimatnya di wilayah status yang sama.
     */
    public function test_finishing_waits_for_the_queue_in_field_mode_and_checks_it_in_the_workspace(): void
    {
        $lembar = File::get(resource_path('js/components/mendaki/SelesaiLapangan.tsx'));

        $this->assertMatchesRegularExpression(
            '/const tahan = async \(\): Promise<SelesaiTertahan \| null> => \{\s*await putarAntrean\(\);\s*return selesaiTertahan\(await antreanSaatIni\(\), data\.urls, \'lapangan\', gagalTerakhir\(\) === \'sesi\'\);/',
            $lembar,
            'Antrean wajib diputar dan ditunggu, lalu keputusan tertahan dihitung lewat fungsi murni selesaiTertahan (N-1).',
        );
        $this->assertStringContainsString('id="selesai-lapangan" tahan={tahan} />', $lembar);

        $show = File::get(resource_path('js/pages/Trips/Show.tsx'));

        // N-1/FC4b: penjaga kunci perangkat mengunci literal sumbernya, supaya mutan "selalu boleh"
        // (if (!kunci || kunci)) terdeteksi merah: badan if itu tidak lagi berupa penjaga sama sekali.
        $this->assertStringContainsString("if (!kunci) {\n            return null;\n        }", $show, 'Tanpa kunci perangkat, tahanSelesai wajib tertahan null tanpa pengecualian lain.');
        $this->assertStringContainsString('return antrean.selesaiTertahan(', $show);
        $this->assertStringContainsString('await antrean.aksiTersimpanDiPerangkat(kunci, simpan.namaBasisData(kunci)),', $show);
        $this->assertMatchesRegularExpression('/\{ tiba: arrive\.url\(trip\.id\), jeda: pause\.url\(trip\.id\), lanjut: resume\.url\(trip\.id\) \},/', $show);
        $this->assertStringContainsString("{ href: hike.url(trip.id), label: 'Buka mode lapangan' },", $show);
        $this->assertStringContainsString('id="selesai-ruang-kerja" tahan={tahanSelesai} />', $show);
        $this->assertStringNotContainsString('putarAntrean', $show, 'Ruang kerja tidak mengirim aksi lapangan; mode lapangan yang memutarnya.');

        $formulir = File::get(resource_path('js/components/trips/FormulirSelesai.tsx'));

        $this->assertMatchesRegularExpression(
            '/if \(tahan\) \{.*?hasilPeriksa = await tahan\(\);.*?if \(hasilPeriksa !== null\) \{\s*setTertahan\(hasilPeriksa\);\s*fokusKe\(idSimpan\);\s*return;\s*\}\s*\}\s*form\.transform/s',
            $formulir,
            'Selesai yang tertahan wajib berhenti sebelum form.post, dengan fokus kembali ke Simpan.',
        );
        $this->assertStringContainsString(": (tertahan?.kalimat ?? '')}", $formulir, 'Kalimat tertahan dibacakan di wilayah status di atas Simpan.');
        $this->assertStringContainsString('processing={form.processing || memeriksa}', $formulir);
    }

    /**
     * Ruling AF-8 (audit E2E M-6): kalimat Selesai tertahan dinilai ulang setiap kali antrean berubah
     * atau terkirim, tanpa menunggu tekan Simpan berikutnya (asersi sumber; keputusannya sendiri,
     * selesaiTertahan, diuji di Node di atas):
     * - FormulirSelesai memasang pemantau dari pemanggil selama kalimat tertahan tampil, dan setiap
     *   keputusan barunya menggantikan kalimat itu, kecuali selama pemeriksaan Simpan sendiri berjalan;
     *   pemasangannya hanya mengikuti ada-tidaknya kalimat, bukan identitas fungsi pemantau;
     * - mode lapangan memantau lewat dengarAntrean dan menghitung dari antrean saat itu tanpa
     *   memutarnya, jadi kegagalan kirim tidak memicu putaran ulang tanpa henti;
     * - ruang kerja membaca ulang lewat tahanSelesai saat tab kembali terlihat atau difokus, tetap
     *   tanpa memutar antrean dan tanpa efek saat dipasang.
     */
    public function test_a_held_finish_sentence_is_re_evaluated_whenever_the_queue_changes(): void
    {
        $formulir = File::get(resource_path('js/components/trips/FormulirSelesai.tsx'));

        $this->assertMatchesRegularExpression(
            '/useEffect\(\(\) => \{\s*const pantau = pantauTerakhir\.current;\s*if \(!adaTertahan \|\| !pantau\) \{\s*return;\s*\}\s*let terpasang = true;\s*const lepas = pantau\(\(hasilBaru\) => \{\s*if \(terpasang && !memeriksaRef\.current\) \{\s*setTertahan\(hasilBaru\);\s*\}\s*\}\);\s*return \(\) => \{\s*terpasang = false;\s*lepas\(\);\s*\};\s*\}, \[adaTertahan\]\);/',
            $formulir,
        );
        $this->assertStringContainsString('const adaTertahan = tertahan !== null;', $formulir);

        $lembar = File::get(resource_path('js/components/mendaki/SelesaiLapangan.tsx'));
        $this->assertMatchesRegularExpression(
            '/const pantauTahan = \(perbarui: \(hasil: SelesaiTertahan \| null\) => void\) =>\s*dengarAntrean\(\(\) => \{\s*void antreanSaatIni\(\)\.then\(\(aksi\) => perbarui\(selesaiTertahan\(aksi, data\.urls, \'lapangan\', gagalTerakhir\(\) === \'sesi\'\)\), \(\) => undefined\);\s*\}\);/',
            $lembar,
        );
        $this->assertSame(1, substr_count($lembar, 'putarAntrean()'), 'Hanya tahan (tekan Simpan) yang memutar antrean; pemantau tidak.');
        $this->assertStringContainsString('pantauTahan={pantauTahan} id="selesai-lapangan" tahan={tahan} />', $lembar);

        $show = File::get(resource_path('js/pages/Trips/Show.tsx'));
        $this->assertMatchesRegularExpression("/const periksa = \(\) => \{\s*if \(document\.visibilityState === 'visible'\) \{\s*void tahanSelesai\(\)\.then\(perbarui, \(\) => undefined\);/", $show);
        $this->assertStringContainsString("window.addEventListener('focus', periksa);", $show);
        $this->assertStringContainsString("document.addEventListener('visibilitychange', periksa);", $show);
        $this->assertStringContainsString('pantauTahan={pantauTahanSelesai} id="selesai-ruang-kerja" tahan={tahanSelesai} />', $show);
    }

    /**
     * Menjalankan satu skenario di Node atas berkas antrean.ts yang sebenarnya, dengan fetch,
     * cookie, dan navigator tiruan. $argumen menjadi process.argv[3] (status HTTP untuk
     * skenario yang mengulang beberapa status).
     *
     * @return array<string, mixed>
     */
    private function skenario(string $nama, string $argumen = ''): array
    {
        $skrip = <<<'JS'
        import { pathToFileURL } from 'node:url';

        // argv[0] node, argv[1] '-' (skrip dari stdin), argv[2..] argumen sungguhan.
        const berkas = process.argv[2];
        const nama = process.argv[3];
        const argumen = process.argv[4] ?? '';

        Object.defineProperty(globalThis, 'navigator', { value: { onLine: true }, configurable: true, writable: true });
        globalThis.document = { cookie: 'XSRF-TOKEN=token%3Dlama' };

        let panggilan = [];
        let skrip = [];

        globalThis.fetch = async (url, init) => {
            panggilan.push({ url, init, token: init.headers['X-XSRF-TOKEN'], isi: JSON.parse(init.body) });
            const langkah = skrip.shift();
            if (!langkah) {
                return new Response(null, { status: 204 });
            }
            if (langkah.tunda) {
                await new Promise((r) => setTimeout(r, langkah.tunda));
            }
            if (langkah.galat) {
                throw new TypeError('Failed to fetch');
            }
            return new Response(langkah.json ? JSON.stringify(langkah.json) : null, { status: langkah.status, headers: { 'Content-Type': 'application/json' } });
        };

        let versi = 0;

        async function modulBaru() {
            versi++;
            panggilan = [];
            skrip = [];
            navigator.onLine = true;
            document.cookie = 'XSRF-TOKEN=token%3Dlama';
            const m = await import(pathToFileURL(berkas).href + '?v=' + versi);
            let keadaan = { jumlah: 0, pesan: null };
            m.dengarAntrean((k) => {
                keadaan = k;
            });
            return { m, keadaan: () => keadaan };
        }

        let hasil = {};

        if (nama === 's1') {
            const { m, keadaan } = await modulBaru();
            await m.kirimAksi('/trips/1/hike/tiba', { urutan: 3 });
            const p = panggilan[0];
            hasil = {
                jumlahPanggilan: panggilan.length,
                isiPertama: p?.isi,
                tokenPertama: p?.token,
                metodePertama: p?.init.method,
                kredensialPertama: p?.init.credentials,
                sisaAntreanAkhir: keadaan().jumlah,
            };
        }

        if (nama === 's2') {
            const { m, keadaan } = await modulBaru();
            skrip = [{ galat: true }];
            await m.kirimAksi('/a', { urutan: 1 });
            const sisaSesudahGalat = keadaan().jumlah;
            await m.putarAntrean();
            hasil = { sisaSesudahGalat, sisaSesudahPutaranBerikut: keadaan().jumlah, jumlahPanggilanAkhir: panggilan.length };
        }

        if (nama === 's3') {
            const { m, keadaan } = await modulBaru();
            skrip = [{ status: Number(argumen) }];
            await m.kirimAksi('/a', { urutan: 1 });
            hasil = { sisaAntrean: keadaan().jumlah, pesan: keadaan().pesan };
        }

        if (nama === 's5') {
            const { m, keadaan } = await modulBaru();
            skrip = [{ status: Number(argumen) }];
            await m.kirimAksi('/a', { urutan: 1 });
            hasil = { sisaAntrean: keadaan().jumlah, jumlahPanggilan: panggilan.length };
        }

        if (nama === 's6') {
            const { m, keadaan } = await modulBaru();
            skrip = [{ status: Number(argumen), json: { message: 'Pendakian ini tidak sedang berlangsung.' } }];
            await m.kirimAksi('/a', { urutan: 1 });
            hasil = { sisaAntrean: keadaan().jumlah, pesan: keadaan().pesan };
        }

        if (nama === 's7') {
            const { m } = await modulBaru();
            navigator.onLine = false;
            await m.kirimAksi('/a', { urutan: 1 });
            const panggilanSaatLuring = panggilan.length;
            document.cookie = 'XSRF-TOKEN=token%3Dbaru';
            navigator.onLine = true;
            await m.putarAntrean();
            hasil = { panggilanSaatLuring, tokenSaatKirim: panggilan[0]?.token };
        }

        if (nama === 's8') {
            const { m, keadaan } = await modulBaru();
            skrip = [{ status: 204, tunda: 50 }, { status: 204, tunda: 10 }];
            const a = m.kirimAksi('/a', { urutan: 1 });
            const b = m.kirimAksi('/b', { urutan: 2 });
            const c = m.putarAntrean();
            await Promise.all([a, b, c]);
            await new Promise((r) => setTimeout(r, 120));
            hasil = { urutanPanggilan: panggilan.map((x) => x.url).join(','), sisaAntrean: keadaan().jumlah };
        }

        if (nama === 's9') {
            const { m, keadaan } = await modulBaru();
            navigator.onLine = false;
            await m.kirimAksi('/jeda', { pada: 'x' });
            await m.kirimAksi('/lanjut', { pada: 'y' });
            navigator.onLine = true;
            skrip = [{ status: 503 }];
            await m.putarAntrean();
            const jumlahPanggilanSetelahGagal = panggilan.length;
            const sisaSetelahGagal = keadaan().jumlah;
            await m.putarAntrean();
            hasil = {
                jumlahPanggilanSetelahGagal,
                sisaSetelahGagal,
                urutanSetelahPutaranBerikutnya: panggilan.map((x) => x.url).join(','),
                sisaSetelahPutaranBerikutnya: keadaan().jumlah,
            };
        }

        if (nama === 's10') {
            const { m, keadaan } = await modulBaru();
            skrip = [{ status: 422, json: { message: 'Pos itu tidak ada di jalur trip ini.' } }, { status: 204 }];
            await m.kirimAksi('/a', { urutan: 9 });
            const pesanTolak = keadaan().pesan;
            await m.kirimAksi('/b', { urutan: 2 });
            hasil = { pesanTolak, pesanAkhir: keadaan().pesan };
        }

        if (nama === 's11') {
            const { m, keadaan } = await modulBaru();
            const teks = [];
            m.dengarAntrean((k) => {
                teks.push(m.teksAntrean(true, k));
            });
            skrip = [{ status: 503 }, { status: 503 }, { galat: true }];
            await m.kirimAksi('/a', { urutan: 1 });
            const teksPutaran1 = teks.splice(0);
            const keadaanSesudahGagal = keadaan();
            await m.putarAntrean();
            await m.putarAntrean();
            const teksPutaranUlang = teks.splice(0);
            await m.putarAntrean();
            hasil = { teksPutaran1, keadaanSesudahGagal, teksPutaranUlang, keadaanSesudahTerkirim: keadaan() };
        }

        if (nama === 's12') {
            const { m, keadaan } = await modulBaru();
            skrip = [{ status: Number(argumen) }];
            await m.kirimAksi('/a', { urutan: 1 });
            hasil = { keadaan: keadaan() };
        }

        process.stdout.write(JSON.stringify(hasil));
        JS;

        $proses = new Process(
            ['node', '--experimental-strip-types', '--input-type=module', '-', resource_path('js/components/mendaki/antrean.ts'), $nama, $argumen],
            base_path(),
            null,
            $skrip,
            60
        );
        $proses->run();

        $this->assertTrue($proses->isSuccessful(), "Node gagal menjalankan skenario {$nama}: ".mb_substr($proses->getErrorOutput(), 0, 600));

        return json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Memanggil fungsi murni antrean.ts di Node dan mengembalikan hasilnya berurutan, pola
     * FieldModeFiguresTest::hitung().
     *
     * @param  array<int, array{0: string, 1: array<int, mixed>}>  $panggilan
     * @return array<int, mixed>
     */
    private function panggil(array $panggilan): array
    {
        $skrip = 'import { pathToFileURL } from "node:url";'
            .'const modul = await import(pathToFileURL('.json_encode(resource_path('js/components/mendaki/antrean.ts')).').href);'
            .'const panggilan = '.json_encode($panggilan).';'
            .'process.stdout.write(JSON.stringify(panggilan.map(([nama, argumen]) => modul[nama](...argumen))));';

        $proses = new Process(['node', '--experimental-strip-types', '--input-type=module', '-'], base_path(), null, $skrip, 60);
        $proses->run();

        $this->assertTrue($proses->isSuccessful(), 'Node gagal menjalankan antrean.ts: '.mb_substr($proses->getErrorOutput(), 0, 600));

        return json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }
}
