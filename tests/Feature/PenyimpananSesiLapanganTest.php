<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Persistensi sessionStorage antrean mode lapangan (N-3/ruling W6-3l): sebelum ini W6-3a tidak
 * punya satu test repo pun, padahal perilakunya dapat diuji di Node dengan stub sessionStorage
 * berbasis Map yang bertahan lintas "muat ulang penuh" (impor modul baru dengan ?v=N mengosongkan
 * memori modul; sessionStorage tiruan tab yang sama tetap). Dasar skenario: tinjauan ulang
 * putaran 1 W6 Tugas 3 (w6t3rr/sesi/uji_sesi.mjs).
 */
class PenyimpananSesiLapanganTest extends TestCase
{
    public function test_the_key_is_per_user_and_holds_no_position(): void
    {
        $s = $this->skenario('s1');

        $this->assertSame(['mendaki.antrean.7'], $s['kunci']);
        $this->assertSame(['urutan' => 3], $s['kunciIsi']);
    }

    /**
     * Sambungan mode lapangan ke penyimpanan ini (Q-2/ruling W6-3q): test lain di berkas ini
     * menjalankan antrean.ts sendiri dan memanggil pakaiPenyimpanan sendiri, jadi hook yang
     * berhenti memasang sessionStorage (mutan SS2_HOOK, task-3-fix2-review.md bagian 2.2) lolos
     * semuanya, dan aksi luring hilang lagi sesudah "Masuk lagi" (I-1). Asersi sumber atas dua
     * hal: hook memasang pakaiPenyimpanan(penyimpananSessionStorage(penggunaId)), dan penggunaId
     * berasal dari prop bersama auth.user (id pengguna sesi dari server), satu-satunya penugasan
     * nama itu di hook, bukan konstanta atau nilai dari tempat lain.
     *
     * W6 Tugas 9 sampai 11 mengganti penyimpanan ini dengan IndexedDB paket lapangan, jadi asersi
     * ini ikut diperbarui di sana, bersama penggabungan isi lama yang dituntut M-9.
     *
     * Diperbarui W6 Tugas 10 seperti dijanjikan di atas (ruling W6-9a, PT10-10, PT11-3 bagian b):
     * kuncinya kini kunci perangkat pengguna sesi dari prop bersama `perangkat` (HMAC id, ruling
     * W6-R10), bukan id pengguna. sessionStorage berkunci itu dipasang segera dengan pemiliknya;
     * bila basis data paket pengguna ini sudah ada di perangkat (diperiksa tanpa membukanya,
     * PT10-16), antrean naik ke gabungan IndexedDB paket dan sessionStorage, yang terbagi dengan
     * shell luring, sambil menyerap isi sessionStorage (M-9). Niat asersinya tetap: kunci berasal
     * dari sesi server, satu penugasan, dan penyimpanan wajib dipasang saat mode lapangan dipasang.
     */
    public function test_field_mode_installs_this_storage_keyed_by_the_signed_in_user(): void
    {
        $hook = File::get(resource_path('js/components/mendaki/useModeLapangan.ts'));

        $this->assertStringContainsString('const kunci = usePage<PropsBersama>().props.perangkat?.kunci ?? null;', $hook);
        $this->assertSame(1, preg_match_all('/\bkunci\s*=[^=]/', $hook), 'kunci hanya ditugaskan sekali, dari prop bersama perangkat.');
        $this->assertMatchesRegularExpression(
            '/if \(kunci !== null\) \{\s*pakaiPenyimpanan\(penyimpananSessionStorage\(kunci\), kunci\);/',
            $hook,
            'Mode lapangan wajib memasang sessionStorage berkunci perangkat pengguna sesi saat dipasang.',
        );
        $this->assertStringContainsString('adaBasisData(kunci)', $hook, 'Basis data paket diperiksa tanpa dibuat (PT10-16).');
        $this->assertStringContainsString('pakaiPenyimpanan(penyimpananGabungan(penyimpananIndexedDb(db), penyimpananSessionStorage(kunci)), kunci);', $hook);
        $this->assertStringNotContainsString('auth.user', $hook);
    }

    /**
     * Antrean shell luring dan mode lapangan daring satu antrean (ruling W6-9a, PT10-10): aksi
     * yang dicatat di shell tanpa sinyal (IndexedDB paket) terkirim tepat sekali dari halaman
     * daring sesudah sinyal kembali, dan tidak dua kali bila keduanya memutar berurutan di tab
     * yang sama (impor modul baru = muat penuh). Halaman daring memakai sambungan useModeLapangan:
     * sessionStorage berkunci perangkat, lalu naik ke gabungan IndexedDB paket.
     */
    #[DataProvider('urutanShellDanDaring')]
    public function test_an_action_recorded_in_the_offline_shell_is_sent_once_from_the_online_page(string $urutan): void
    {
        $s = $this->skenario('g10', $urutan);

        $this->assertSame(['/trips/1/hike/tiba'], $s['tersimpanShell'], 'Aksi shell tersimpan di IndexedDB paket.');
        $this->assertSame('/trips/1/hike/tiba', $s['urutanPanggilan'], "Urutan {$urutan}: aksi shell terkirim tepat sekali.");
        $this->assertSame([], $s['sisaIdb']);
        $this->assertSame([], $s['kunciSesi']);
    }

    /**
     * Dua tab yang HIDUP bersamaan, shell luring dan mode lapangan daring, berbagi antrean
     * IndexedDB paket (ruling W6-10f bagian b, menutup I-3 tinjauan Tugas 10): pasangan jeda dan
     * lanjut yang dicatat di shell terkirim tepat sekali walau tab shell memutar lagi sesudah tab
     * daring mengirimnya, dan walau keduanya memutar bersamaan. Kunci lintas tab menggilirkan
     * putaran, dan di dalamnya penyimpanan bersama dibaca ulang sebelum setiap kirim. Dasar: probe
     * peninjau dua-tab.mjs, yang tanpa kunci dan bacaan ulang menghasilkan empat kiriman.
     */
    #[DataProvider('urutanDuaTabHidup')]
    public function test_two_live_tabs_send_each_shared_action_once(string $urutan): void
    {
        $s = $this->skenario('g11', $urutan);

        $this->assertSame('/trips/1/hike/jeda,/trips/1/hike/lanjut', $s['urutanPanggilan'], "Urutan {$urutan}: aksi bersama terkirim lebih dari sekali.");
        $this->assertSame(0, $s['sisaIdb']);
        $this->assertSame(['memount-antrean-k1'], $s['namaKunci']);
    }

    /**
     * Aksi baru yang dicatat di tab basi tidak menghidupkan lagi aksi yang sudah dikirim tab lain
     * (tinjauan Tugas 10 m-B; dasar probe peninjau dua-tab-f1.mjs skenario catat-di-tab-basi): tab
     * daring mengirim jeda 01.00 dan lanjut 01.30 dari antrean bersama, lalu menjeda lagi 02.00;
     * tab shell yang masih terbuka, dan belum membaca ulang sejak itu, mencatat tiba pos. Dengan
     * kunci lintas tab, tab shell membaca ulang penyimpanan bersama SEBELUM mendorong aksi barunya,
     * jadi setiap aksi terkirim sekali: empat kiriman, bukan enam.
     */
    public function test_a_new_action_in_a_stale_tab_does_not_revive_actions_sent_by_another_tab(): void
    {
        $s = $this->skenario('g11', 'catat-di-tab-basi');

        $this->assertSame(['/trips/1/hike/jeda 01:00', '/trips/1/hike/lanjut 01:30', '/trips/1/hike/jeda 02:00', '/trips/1/hike/tiba 2'], $s['kiriman']);
        $this->assertSame(0, $s['sisaIdb']);
        $this->assertSame(['memount-antrean-k1'], $s['namaKunci']);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function urutanDuaTabHidup(): array
    {
        return [
            'tab daring mengirim, lalu tab shell memutar lagi' => ['daring-lalu-shell'],
            'kedua tab memutar bersamaan' => ['bersamaan'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function urutanShellDanDaring(): array
    {
        return [
            'shell luring, lalu halaman daring mengirim' => ['daring-mengirim'],
            'shell mengirim saat sinyal kembali, lalu halaman daring dibuka' => ['shell-mengirim'],
        ];
    }

    /**
     * Elemen tersimpan yang bukan aksi sah (Q-11/ruling W6-3y) dibuang saat dimuat, bersama satu
     * aksi sah di belakangnya: aksi sah tetap terkirim, aksi baru juga, dan kuncinya terhapus.
     * Sebelum ini null di depan membuat putaran berhenti setiap kali dicoba (0 kiriman), dan {}, 1,
     * atau "x" dikirim ke URL undefined. Dua data set terakhir punya `url` tetapi payloadnya bukan
     * objek, jadi syarat payload di saringan juga dijaga, bukan hanya syarat `url`.
     */
    #[DataProvider('elemenBukanAksi')]
    public function test_stored_elements_that_are_not_actions_are_dropped_and_the_queue_moves_on(string $elemen): void
    {
        $s = $this->skenario('s10', $elemen);

        $this->assertNull($s['galat'], "Elemen {$elemen} tidak boleh membuat kirimAksi melempar.");
        $this->assertSame('/trips/1/hike/tiba,/b', $s['urutanPanggilan'], "Elemen {$elemen} tidak boleh menahan aksi sah di belakangnya atau dikirim sendiri.");
        $this->assertSame(0, $s['jumlahAkhir']);
        $this->assertFalse($s['kunciTersisa'], 'Elemen rusak tidak boleh tertinggal di kunci sesudah antrean kosong.');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function elemenBukanAksi(): array
    {
        return [
            'null' => ['null'],
            'objek kosong' => ['{}'],
            'angka' => ['1'],
            'string' => ['"x"'],
            'url tanpa payload' => ['{"url":"/x"}'],
            'payload berupa larik' => ['{"url":"/x","isi":[1]}'],
        ];
    }

    /**
     * Antrean diputar sekali saat mode lapangan dipasang, bila daring (Q-12/ruling W6-3z): aksi
     * yang dipulihkan sesudah muat penuh terkirim tanpa menunggu "online", aksi berikutnya, atau
     * putaran 30 detik. Saat luring, pasangAntrean tidak mengirim apa pun dan aksinya tetap
     * menunggu.
     */
    public function test_installing_field_mode_replays_a_restored_queue_once_when_online(): void
    {
        $daring = $this->skenario('s11', 'daring');
        $luring = $this->skenario('s11', 'luring');

        $this->assertSame(1, $daring['jumlahPanggilan'], 'Antrean yang dipulihkan wajib terkirim saat mode lapangan dipasang dan daring.');
        $this->assertSame(['urutan' => 3], $daring['isiPanggilan']);
        $this->assertSame(0, $daring['jumlahAkhir']);
        $this->assertFalse($daring['kunciTersisa']);

        $this->assertSame(0, $luring['jumlahPanggilan'], 'Saat luring tidak ada kiriman.');
        $this->assertSame(1, $luring['jumlahAkhir'], 'Saat luring aksinya tetap menunggu.');
    }

    public function test_a_full_reload_restores_the_queue_sends_it_once_and_removes_the_key(): void
    {
        $s = $this->skenario('s2');

        $this->assertSame(1, $s['jumlahSaatDibuka'], 'Antrean wajib terlihat segera setelah muat penuh, sebelum dikirim.');
        $this->assertSame(1, $s['jumlahPanggilan']);
        $this->assertSame(['urutan' => 3], $s['isiPanggilan']);
        $this->assertFalse($s['kunciTersisa'], 'Kunci wajib terhapus (removeItem) sesudah terkirim.');
    }

    public function test_a_different_user_in_the_same_tab_does_not_see_or_send_another_users_queue(): void
    {
        $s = $this->skenario('s3');

        $this->assertSame(0, $s['jumlahPenggunaLain']);
        $this->assertSame(0, $s['jumlahPanggilan']);
        $this->assertTrue($s['kunci7Utuh'], 'Antrean pengguna 7 tidak boleh tersentuh oleh pengguna 8.');
    }

    /**
     * Tanpa aksi ganda ketika mode lapangan dipasang ulang sementara memori dan sessionStorage
     * sama-sama sudah berisi aksi yang sama (komponen lepas lalu pasang, bukan muat ulang).
     */
    public function test_reinstalling_while_both_memory_and_storage_hold_the_action_does_not_duplicate(): void
    {
        $s = $this->skenario('s4');

        $this->assertSame(1, $s['jumlahSesudahPasangUlang']);
        $this->assertSame('/a', $s['urutanPanggilan']);
        $this->assertFalse($s['kunciTersisa']);
    }

    /**
     * Tanpa aksi ganda ketika mode lapangan dipasang ulang SELAGI kiriman pertama masih
     * berjalan, lalu aksi baru ditambahkan.
     */
    public function test_reinstalling_mid_send_does_not_duplicate_the_in_flight_action(): void
    {
        $s = $this->skenario('s5');

        $this->assertSame('/a,/b', $s['urutanPanggilan']);
        $this->assertFalse($s['kunciTersisa']);
    }

    /**
     * sessionStorage yang melempar (mode pribadi atau diblokir) tetap menjaga aksi di memori
     * modul selama mode lapangan tidak dipasang ulang; terkirim begitu sinyal kembali.
     */
    public function test_a_throwing_session_storage_keeps_the_action_in_memory_until_it_can_send(): void
    {
        $s = $this->skenario('s7');

        $this->assertNull($s['galat'], 'kirimAksi tidak boleh melempar hanya karena sessionStorage diblokir.');
        $this->assertSame(1, $s['jumlahSebelumKirim']);
        $this->assertSame(1, $s['jumlahPanggilan']);
    }

    /**
     * Isi kunci yang bukan larik (N-5/ruling W6-3l) diabaikan, bukan mematahkan kirimAksi:
     * format lain di kunci yang sama (rusak, atau dari format baru yang tidak kompatibel)
     * tidak boleh menghentikan antrean sama sekali.
     */
    #[DataProvider('isiBukanLarik')]
    public function test_non_array_stored_content_is_ignored_not_fatal(string $mentah): void
    {
        $s = $this->skenario('s9', $mentah);

        $this->assertNull($s['galat'], "Isi {$mentah} tidak boleh membuat kirimAksi melempar.");
        $this->assertSame(1, $s['jumlahPanggilan'], "Aksi baru tetap wajib terkirim walau isi lama {$mentah}.");
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function isiBukanLarik(): array
    {
        return [
            'objek kosong' => ['{}'],
            'null' => ['null'],
        ];
    }

    /**
     * Ruling W6-9a (M-9, W6-3i): penyimpanan yang diganti untuk pemilik yang sama menggabungkan
     * antrean memori dengan isi penyimpanan baru, bukan menimpanya, dan hasilnya ditulis ke
     * penyimpanan baru. Penyimpanan baru (IndexedDB tiruan) berisi aksi yang dibuat sesudah aksi
     * memori, jadi urutan dibuat sama dengan urutan memori lalu penyimpanan.
     */
    public function test_a_new_storage_for_the_same_owner_merges_with_the_memory_queue(): void
    {
        $s = $this->skenario('g1');

        $this->assertSame(['/a', '/b'], $s['antrean']);
        $this->assertSame(['/a', '/b'], $s['tersimpan']);
    }

    /**
     * Pemilik berbeda (akun lain di tab yang sama) tidak mewarisi antrean, pesan, atau keadaan
     * gagal pemilik sebelumnya (W6-9a).
     */
    public function test_a_new_owner_starts_from_its_own_storage_only(): void
    {
        $s = $this->skenario('g2');

        $this->assertSame('sesi', $s['gagalSebelum'], 'Skenario harus mulai dari keadaan gagal pemilik lama.');
        $this->assertNotNull($s['pesanSebelum']);
        $this->assertSame(['/b'], $s['antrean']);
        $this->assertSame([1, null, null], [$s['keadaan']['jumlah'], $s['keadaan']['pesan'], $s['keadaan']['gagal']]);
    }

    /**
     * Aksi yang sama (id sama) di memori dan di penyimpanan baru terkirim sekali (W6-9a).
     */
    public function test_an_action_held_twice_is_sent_once(): void
    {
        $s = $this->skenario('g3');

        $this->assertSame('/x', $s['urutanPanggilan']);
        $this->assertSame(0, $s['sisa']);
    }

    /**
     * Kiriman yang sedang berjalan ketika penyimpanan diganti dengan aksi yang lebih tua: aksi
     * yang dijawab server dibuang menurut id, bukan aksi terdepan (W6-9a). Dengan shift(), aksi
     * tua yang kini terdepan terbuang tanpa pernah terkirim dan aksi yang sudah terkirim dikirim
     * ulang.
     */
    public function test_an_older_action_arriving_mid_send_is_kept_and_sent_next(): void
    {
        $s = $this->skenario('g4');

        $this->assertSame('/a,/tua', $s['urutanPanggilan']);
        $this->assertSame(0, $s['sisa']);
    }

    /**
     * Penyimpanan IndexedDB yang gagal (koneksi tertutup, transaksi melempar) tidak mematahkan
     * antrean (PT9-6): muat memberi larik kosong, simpan tidak melempar, isi yang bukan larik
     * diabaikan, dan elemen yang bukan aksi sah dibuang (aksiSah yang sama, diekspor).
     */
    public function test_a_failing_indexeddb_storage_never_breaks_the_queue(): void
    {
        $s = $this->skenario('g5');

        $this->assertNull($s['galat']);
        $this->assertSame([[], [], ['/sah']], [$s['muatLempar'], $s['muatBukanLarik'], $s['muatTersaring']]);
        $this->assertFalse($s['tulisLempar']);
    }

    /**
     * Dua penyimpanan atas basis data yang sama (shell dan mode lapangan daring di dua jendela)
     * tidak saling menghapus aksi: setiap tulis membaca isi tersimpan, membuang hanya aksi yang
     * dibuang penyimpanan itu sendiri, lalu menggabungkan (W6-9a).
     */
    public function test_two_storages_on_one_database_do_not_drop_each_others_actions(): void
    {
        $s = $this->skenario('g6');

        $this->assertSame(['/a', '/b'], $s['sesudahB']);
        $this->assertSame(['/b'], $s['sesudahAKirim']);
        $this->assertTrue($s['tulisBerhasil']);
    }

    /**
     * Penyimpanan gabungan menyerap cadangan (sessionStorage) ke utama (IndexedDB), dan
     * mengosongkan cadangan HANYA bila tulis utama terbukti selesai; bila utama gagal, aksi
     * baru ikut ke cadangan (W6-9a).
     */
    public function test_the_combined_storage_empties_the_fallback_only_after_the_main_write(): void
    {
        $s = $this->skenario('g7');

        $this->assertSame(['/x'], $s['gagal']['muat']);
        $this->assertTrue($s['gagal']['cadanganTersisa'], 'Cadangan tidak boleh dikosongkan bila tulis utama gagal.');
        $this->assertSame(['/x', '/y'], $s['gagal']['cadanganSesudahSimpan']);

        $this->assertSame(['/x'], $s['berhasil']['muat']);
        $this->assertFalse($s['berhasil']['cadanganTersisa']);
        $this->assertSame(['/x'], $s['berhasil']['utama']);
    }

    /**
     * kosongkanAntrean (dipakai Tugas 11 saat keluar): antrean, pesan, dan keadaan gagal kembali
     * bawaan, penyimpanan kembali memori, dan pendengar dikabari; aksi sesudahnya tidak ditulis
     * ke penyimpanan pemilik lama.
     */
    public function test_emptying_the_queue_resets_it_and_releases_the_storage(): void
    {
        $s = $this->skenario('g8');

        $this->assertSame([1, 'sesi'], [$s['sebelum']['jumlah'], $s['sebelum']['gagal']], 'Skenario harus mulai dari antrean berisi dan gagal.');
        $this->assertSame([0, null, null, false], [$s['keadaan']['jumlah'], $s['keadaan']['pesan'], $s['keadaan']['gagal'], $s['keadaan']['mengirim']]);
        $this->assertSame([], $s['antrean']);
        $this->assertSame(['/a'], $s['tersimpanLama'], 'Penyimpanan pemilik lama tidak disentuh sesudah dikosongkan.');
    }

    /**
     * Ruling W6-9m (tinjauan I-2): satu tab tidak mengirim aksi yang sudah dijawab server dua kali.
     * Penyimpanan pemilik yang sama diganti dari sessionStorage ke gabungan IndexedDB selagi aksi
     * dikirim; cadangan sessionStorage masih memuat aksi itu, jadi tanpa saringan id yang sudah
     * dijawab, pemuatan menghidupkannya lagi dan putaran yang sama mengirimnya kedua kali. Enam
     * timing probe peninjau P-3 (jeda IndexedDB per permintaan, lama fetch, penggantian sesudah,
     * dalam ms): empat yang dahulu mengirim dua kali, dan dua yang selesai sebelum penggantian atau
     * sesudah pemuatan.
     */
    #[DataProvider('timingPenggantian')]
    public function test_an_action_answered_while_the_storage_is_replaced_is_sent_once(string $timing): void
    {
        $s = $this->skenario('g9', $timing);

        $this->assertSame('/trips/1/hike/tiba', $s['urutanPanggilan'], "Timing {$timing}: aksi yang sudah dijawab server terkirim lagi.");
        $this->assertSame(0, $s['sisa']);
        $this->assertSame([], $s['tersimpan']);
        $this->assertSame([], $s['kunciSesi'], 'Cadangan sessionStorage dikosongkan sesudah tulis utama berhasil.');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function timingPenggantian(): array
    {
        return [
            'IndexedDB 0, fetch 30' => ['0,30,10'],
            'IndexedDB 1, fetch 30' => ['1,30,10'],
            'IndexedDB 5, fetch 30' => ['5,30,10'],
            'IndexedDB 15, fetch 30' => ['15,30,10'],
            'IndexedDB 15, fetch 5' => ['15,5,10'],
            'IndexedDB 15, fetch 200' => ['15,200,10'],
        ];
    }

    /**
     * Menjalankan satu skenario di Node atas antrean.ts yang sebenarnya, dengan sessionStorage
     * (Map yang bertahan antar "muat ulang"), fetch, cookie, dan navigator tiruan. Skenario g1
     * sampai g8 (ruling W6-9a) menambah IndexedDB tiruan minimal: satu Map per basis data,
     * transaksi yang selesai sesudah semua permintaannya, dan mode yang melempar atau batal.
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

        const isiSesi = new Map();
        let modeSesi = 'normal';
        const sesiTiruan = {
            getItem(k) {
                return isiSesi.has(k) ? isiSesi.get(k) : null;
            },
            setItem(k, v) {
                if (modeSesi === 'setlempar') {
                    throw new Error('QuotaExceededError');
                }
                isiSesi.set(k, String(v));
            },
            removeItem(k) {
                isiSesi.delete(k);
            },
        };
        Object.defineProperty(globalThis, 'sessionStorage', {
            configurable: true,
            get() {
                if (modeSesi === 'lempar') {
                    throw new Error('SecurityError: The operation is insecure.');
                }
                return sesiTiruan;
            },
        });

        let panggilan = [];
        let skrip = [];
        globalThis.fetch = async (url, init) => {
            panggilan.push({ url, token: init.headers['X-XSRF-TOKEN'], isi: JSON.parse(init.body) });
            const langkah = skrip.shift();
            if (langkah?.tunda) {
                await new Promise((r) => setTimeout(r, langkah.tunda));
            }
            if (langkah?.galat) {
                throw new TypeError('Failed to fetch');
            }
            return new Response(langkah?.json ? JSON.stringify(langkah.json) : null, { status: langkah?.status ?? 204, headers: { 'Content-Type': 'application/json' } });
        };

        let versi = 0;
        async function muatPenuh() {
            versi++;
            return await import(pathToFileURL(berkas).href + '?v=' + versi);
        }
        function pasang(m, id) {
            let keadaan = { jumlah: -1, pesan: null };
            m.pakaiPenyimpanan(m.penyimpananSessionStorage(id));
            m.dengarAntrean((k) => {
                keadaan = k;
            });
            return { keadaan: () => keadaan };
        }
        const tunggu = (ms) => new Promise((r) => setTimeout(r, ms));

        let hasil = {};

        if (nama === 's1') {
            const m = await muatPenuh();
            pasang(m, 7);
            navigator.onLine = false;
            await m.kirimAksi('/trips/1/hike/tiba', { urutan: 3 });
            const kunci = [...isiSesi.keys()];
            const tersimpan = JSON.parse(isiSesi.get('mendaki.antrean.7') ?? '[]');
            hasil = { kunci, kunciIsi: tersimpan.length === 1 ? tersimpan[0].isi : null };
        }

        if (nama === 's2') {
            const m0 = await muatPenuh();
            pasang(m0, 7);
            navigator.onLine = false;
            await m0.kirimAksi('/trips/1/hike/tiba', { urutan: 3 });

            const m = await muatPenuh();
            const p = pasang(m, 7);
            await tunggu(5);
            const jumlahSaatDibuka = p.keadaan().jumlah;
            navigator.onLine = true;
            await m.putarAntrean();
            hasil = { jumlahSaatDibuka, jumlahPanggilan: panggilan.length, isiPanggilan: panggilan[0]?.isi, kunciTersisa: isiSesi.has('mendaki.antrean.7') };
        }

        if (nama === 's3') {
            isiSesi.set('mendaki.antrean.7', JSON.stringify([{ id: 'x', url: '/trips/1/hike/tiba', isi: { urutan: 2 }, dibuat: 1 }]));
            const m = await muatPenuh();
            const p = pasang(m, 8);
            await tunggu(5);
            await m.putarAntrean();
            hasil = { jumlahPenggunaLain: p.keadaan().jumlah, jumlahPanggilan: panggilan.length, kunci7Utuh: isiSesi.has('mendaki.antrean.7') };
        }

        if (nama === 's4') {
            const m = await muatPenuh();
            pasang(m, 7);
            navigator.onLine = false;
            await m.kirimAksi('/a', { urutan: 1 });
            const p = pasang(m, 7);
            await tunggu(5);
            const jumlahSesudahPasangUlang = p.keadaan().jumlah;
            navigator.onLine = true;
            await m.putarAntrean();
            hasil = { jumlahSesudahPasangUlang, urutanPanggilan: panggilan.map((x) => x.url).join(','), kunciTersisa: isiSesi.has('mendaki.antrean.7') };
        }

        if (nama === 's5') {
            const m = await muatPenuh();
            pasang(m, 7);
            skrip = [{ status: 204, tunda: 60 }, { status: 204 }];
            const a = m.kirimAksi('/a', { urutan: 1 });
            await tunggu(15);
            pasang(m, 7);
            await tunggu(5);
            const b = m.kirimAksi('/b', { urutan: 2 });
            await Promise.all([a, b]);
            await tunggu(100);
            await m.putarAntrean();
            hasil = { urutanPanggilan: panggilan.map((x) => x.url).join(','), kunciTersisa: isiSesi.has('mendaki.antrean.7') };
        }

        if (nama === 's7') {
            modeSesi = 'lempar';
            const m = await muatPenuh();
            const p = pasang(m, 7);
            navigator.onLine = false;
            let galat = null;
            try {
                await m.kirimAksi('/a', { urutan: 1 });
            } catch (e) {
                galat = String(e);
            }
            const jumlahSebelumKirim = p.keadaan().jumlah;
            navigator.onLine = true;
            await m.putarAntrean();
            hasil = { galat, jumlahSebelumKirim, jumlahPanggilan: panggilan.length };
        }

        if (nama === 's9') {
            isiSesi.set('mendaki.antrean.7', argumen);
            const m = await muatPenuh();
            pasang(m, 7);
            await tunggu(5);
            let galat = null;
            try {
                await m.kirimAksi('/a', { urutan: 1 });
            } catch (e) {
                galat = String(e).slice(0, 120);
            }
            hasil = { galat, jumlahPanggilan: panggilan.length };
        }

        if (nama === 's10') {
            const sah = { id: 'sah', url: '/trips/1/hike/tiba', isi: { urutan: 2 }, dibuat: 1 };
            isiSesi.set('mendaki.antrean.7', '[' + argumen + ',' + JSON.stringify(sah) + ']');
            const m = await muatPenuh();
            const p = pasang(m, 7);
            await tunggu(5);
            let galat = null;
            try {
                await m.kirimAksi('/b', { urutan: 3 });
            } catch (e) {
                galat = String(e).slice(0, 120);
            }
            hasil = { galat, urutanPanggilan: panggilan.map((x) => x.url).join(','), jumlahAkhir: p.keadaan().jumlah, kunciTersisa: isiSesi.has('mendaki.antrean.7') };
        }

        if (nama === 's11') {
            const m0 = await muatPenuh();
            pasang(m0, 7);
            navigator.onLine = false;
            await m0.kirimAksi('/trips/1/hike/tiba', { urutan: 3 });

            navigator.onLine = argumen === 'daring';
            const m = await muatPenuh();
            let keadaan = { jumlah: -1 };
            m.pakaiPenyimpanan(m.penyimpananSessionStorage(7));
            m.pasangAntrean((k) => {
                keadaan = k;
            });
            await tunggu(20);
            hasil = { jumlahPanggilan: panggilan.length, isiPanggilan: panggilan[0]?.isi ?? null, jumlahAkhir: keadaan.jumlah, kunciTersisa: isiSesi.has('mendaki.antrean.7') };
        }

        function dbTiruan(peta = new Map(), mode = 'normal', tunda = 0) {
            return {
                transaction() {
                    if (mode === 'lempar') {
                        throw new Error('InvalidStateError: The database connection is closing.');
                    }
                    const tx = { error: null, oncomplete: null, onerror: null, onabort: null };
                    let tertunda = 0;
                    const selesaiSatu = () => {
                        tertunda--;
                        if (tertunda === 0) {
                            setTimeout(() => {
                                if (mode === 'batal') {
                                    tx.error = new Error('QuotaExceededError');
                                    tx.onerror?.();
                                    tx.onabort?.();
                                } else {
                                    tx.oncomplete?.();
                                }
                            }, tunda);
                        }
                    };
                    const minta = (hasil, efek) => {
                        tertunda++;
                        const r = { result: undefined, onsuccess: null, onerror: null };
                        setTimeout(() => {
                            if (mode !== 'batal') {
                                efek?.();
                            }
                            r.result = hasil();
                            r.onsuccess?.();
                            selesaiSatu();
                        }, tunda);
                        return r;
                    };
                    tx.objectStore = () => ({
                        get: (k) => minta(() => (peta.has(k) ? structuredClone(peta.get(k)) : undefined)),
                        put: (v, k) => minta(() => k, () => peta.set(k, structuredClone(v))),
                        delete: (k) => minta(() => undefined, () => peta.delete(k)),
                    });
                    return tx;
                },
            };
        }
        const aksi = (id, url, dibuat) => ({ id, url, isi: { urutan: 1 }, dibuat });
        const urlDari = (daftar) => (daftar ?? []).map((x) => x.url);

        if (nama === 'g1') {
            const m = await muatPenuh();
            navigator.onLine = false;
            m.pakaiPenyimpanan(m.penyimpananSessionStorage('k1'), 'k1');
            await m.kirimAksi('/a', { urutan: 1 });
            const peta = new Map([['aksi', [aksi('b', '/b', Date.now() + 60_000)]]]);
            m.pakaiPenyimpanan(m.penyimpananIndexedDb(dbTiruan(peta)), 'k1');
            hasil = { antrean: urlDari(await m.antreanSaatIni()), tersimpan: urlDari(peta.get('aksi')) };
        }

        if (nama === 'g2') {
            const m = await muatPenuh();
            m.pakaiPenyimpanan(m.penyimpananSessionStorage('k1'), 'k1');
            let keadaan = null;
            m.dengarAntrean((k) => {
                keadaan = k;
            });
            // Pemuatan pemilik dan putarannya selesai dulu, supaya hanya kirimAksi yang mengirim.
            await tunggu(5);
            skrip = [{ status: 419 }];
            await m.kirimAksi('/a', { urutan: 1 });
            const gagalSebelum = keadaan.gagal;
            const pesanSebelum = keadaan.pesan;
            m.pakaiPenyimpanan(m.penyimpananIndexedDb(dbTiruan(new Map([['aksi', [aksi('b', '/b', 5)]]]))), 'k2');
            navigator.onLine = false;
            const antrean = urlDari(await m.antreanSaatIni());
            await tunggu(20);
            hasil = { gagalSebelum, pesanSebelum, antrean, keadaan };
        }

        if (nama === 'g3') {
            const m = await muatPenuh();
            navigator.onLine = false;
            m.pakaiPenyimpanan(m.penyimpananSessionStorage('k1'), 'k1');
            await tunggu(5);
            const x = aksi('x', '/x', 10);
            isiSesi.set('mendaki.antrean.k1', JSON.stringify([x]));
            m.pakaiPenyimpanan(m.penyimpananSessionStorage('k1'), 'k1');
            m.pakaiPenyimpanan(m.penyimpananIndexedDb(dbTiruan(new Map([['aksi', [x, x]]]))), 'k1');
            await m.antreanSaatIni();
            navigator.onLine = true;
            await m.putarAntrean();
            hasil = { urutanPanggilan: panggilan.map((p) => p.url).join(','), sisa: (await m.antreanSaatIni()).length };
        }

        if (nama === 'g4') {
            const m = await muatPenuh();
            m.pakaiPenyimpanan(m.penyimpananSessionStorage('k1'), 'k1');
            await tunggu(5);
            skrip = [{ status: 204, tunda: 60 }, { status: 204 }];
            const a = m.kirimAksi('/a', { urutan: 1 });
            await tunggu(15);
            m.pakaiPenyimpanan(m.penyimpananIndexedDb(dbTiruan(new Map([['aksi', [aksi('tua', '/tua', 1)]]]))), 'k1');
            await a;
            await tunggu(100);
            await m.putarAntrean();
            hasil = { urutanPanggilan: panggilan.map((p) => p.url).join(','), sisa: (await m.antreanSaatIni()).length };
        }

        if (nama === 'g5') {
            const m = await muatPenuh();
            let galat = null;
            let muatLempar;
            let tulisLempar;
            let muatBukanLarik;
            let muatTersaring;
            try {
                const lempar = m.penyimpananIndexedDb(dbTiruan(new Map(), 'lempar'));
                muatLempar = await lempar.muat();
                await lempar.simpan([aksi('a', '/a', 1)]);
                tulisLempar = await lempar.tulis([aksi('a', '/a', 1)]);
                muatBukanLarik = await m.penyimpananIndexedDb(dbTiruan(new Map([['aksi', { url: '/x' }]]))).muat();
                muatTersaring = urlDari(await m.penyimpananIndexedDb(dbTiruan(new Map([['aksi', [null, { url: '/rusak', isi: [1] }, aksi('sah', '/sah', 1)]]]))).muat());
            } catch (e) {
                galat = String(e).slice(0, 160);
            }
            hasil = { galat, muatLempar, muatBukanLarik, muatTersaring, tulisLempar };
        }

        if (nama === 'g6') {
            const m = await muatPenuh();
            const peta = new Map();
            const db = dbTiruan(peta);
            const A = m.penyimpananIndexedDb(db);
            const B = m.penyimpananIndexedDb(db);
            await A.simpan([aksi('a', '/a', 1)]);
            const tulisBerhasil = await B.tulis([aksi('b', '/b', 2)]);
            const sesudahB = urlDari(peta.get('aksi'));
            await A.simpan([]);
            hasil = { sesudahB, sesudahAKirim: urlDari(peta.get('aksi')), tulisBerhasil };
        }

        if (nama === 'g7') {
            const m = await muatPenuh();
            const jalankan = async (mode) => {
                isiSesi.set('mendaki.antrean.k1', JSON.stringify([aksi('x', '/x', 1)]));
                const peta = new Map();
                const gabungan = m.penyimpananGabungan(m.penyimpananIndexedDb(dbTiruan(peta, mode)), m.penyimpananSessionStorage('k1'));
                const muat = urlDari(await gabungan.muat());
                const cadanganTersisa = isiSesi.has('mendaki.antrean.k1');
                await gabungan.simpan([aksi('x', '/x', 1), aksi('y', '/y', 2)]);
                const cadangan = isiSesi.has('mendaki.antrean.k1') ? urlDari(JSON.parse(isiSesi.get('mendaki.antrean.k1'))) : [];
                isiSesi.delete('mendaki.antrean.k1');
                return { muat, cadanganTersisa, cadanganSesudahSimpan: cadangan, utama: urlDari(peta.get('aksi')).slice(0, 1) };
            };
            hasil = { gagal: await jalankan('batal'), berhasil: await jalankan('normal') };
        }

        if (nama === 'g9') {
            // Probe peninjau P-3 (task-9-review.md I-2): pemilik sama, sessionStorage diganti gabungan
            // IndexedDB SELAGI aksi dikirim; cadangan masih memuat aksi itu sesudah server menjawab.
            const [tundaIdb, tundaFetch, gantiSesudah] = argumen.split(',').map(Number);
            const m = await muatPenuh();
            m.pakaiPenyimpanan(m.penyimpananSessionStorage('k1'), 'k1');
            await tunggu(5);
            skrip = [{ status: 204, tunda: tundaFetch }];
            const kirim = m.kirimAksi('/trips/1/hike/tiba', { urutan: 2 });
            await tunggu(gantiSesudah);
            const peta = new Map();
            m.pakaiPenyimpanan(m.penyimpananGabungan(m.penyimpananIndexedDb(dbTiruan(peta, 'normal', tundaIdb)), m.penyimpananSessionStorage('k1')), 'k1');
            await kirim;
            await tunggu(400);
            await m.putarAntrean();
            await tunggu(200);
            hasil = { urutanPanggilan: panggilan.map((p) => p.url).join(','), sisa: (await m.antreanSaatIni()).length, tersimpan: urlDari(peta.get('aksi')), kunciSesi: [...isiSesi.keys()] };
        }

        if (nama === 'g10') {
            // PT10-10 (ruling W6-9a): satu IndexedDB paket (peta bersama) untuk shell dan halaman daring.
            const peta = new Map();
            const pasangGabungan = (m) => m.pakaiPenyimpanan(m.penyimpananGabungan(m.penyimpananIndexedDb(dbTiruan(peta)), m.penyimpananSessionStorage('k1')), 'k1');

            const shell = await muatPenuh();
            navigator.onLine = false;
            pasangGabungan(shell);
            await shell.kirimAksi('/trips/1/hike/tiba', { urutan: 2 });
            await tunggu(20);
            const tersimpanShell = urlDari(peta.get('aksi'));

            navigator.onLine = true;

            if (argumen === 'shell-mengirim') {
                await shell.putarAntrean();
                await tunggu(20);
            }

            // Halaman daring (muat penuh): sambungan useModeLapangan, sessionStorage berkunci perangkat
            // lalu naik ke gabungan IndexedDB paket karena basis datanya ada.
            const daring = await muatPenuh();
            daring.pakaiPenyimpanan(daring.penyimpananSessionStorage('k1'), 'k1');
            pasangGabungan(daring);
            await tunggu(50);
            await daring.putarAntrean();
            await tunggu(20);
            await daring.putarAntrean();

            // Shell dibuka lagi di tab yang sama sesudahnya (muat penuh): tidak ada yang tersisa untuk dikirim.
            const shellLagi = await muatPenuh();
            pasangGabungan(shellLagi);
            await tunggu(50);
            await shellLagi.putarAntrean();
            await tunggu(20);

            hasil = { tersimpanShell, urutanPanggilan: panggilan.map((p) => p.url).join(','), sisaIdb: urlDari(peta.get('aksi')), kunciSesi: [...isiSesi.keys()] };
        }

        if (nama === 'g11') {
            // Ruling W6-10f bagian b (probe peninjau dua-tab.mjs): dua tab HIDUP bersamaan, masing-masing
            // dengan instance modulnya sendiri atas IndexedDB paket yang sama; sessionStorage tidak
            // terbagi antartab, jadi dibuat melempar. navigator.locks tiruan: satu antrean janji per nama.
            modeSesi = 'lempar';
            const antreKunci = new Map();
            const namaKunci = [];
            if (!argumen.endsWith('tanpa-kunci')) {
                navigator.locks = {
                    request(namaK, panggil) {
                        namaKunci.push(namaK);
                        const kini = (antreKunci.get(namaK) ?? Promise.resolve()).then(() => panggil());
                        antreKunci.set(namaK, kini.catch(() => {}));
                        return kini;
                    },
                };
            }
            const peta = new Map();
            const pasangGabungan = (m) => m.pakaiPenyimpanan(m.penyimpananGabungan(m.penyimpananIndexedDb(dbTiruan(peta)), m.penyimpananSessionStorage('k1')), 'k1');

            const shell = await muatPenuh();
            navigator.onLine = false;
            pasangGabungan(shell);
            await shell.kirimAksi('/trips/1/hike/jeda', { pada: '2026-10-10T01:00:00.000Z' });
            await shell.kirimAksi('/trips/1/hike/lanjut', { pada: '2026-10-10T01:30:00.000Z' });
            await tunggu(20);
            navigator.onLine = true;

            const daring = await muatPenuh();
            daring.pakaiPenyimpanan(daring.penyimpananSessionStorage('k1'), 'k1');
            pasangGabungan(daring);
            await tunggu(80);

            if (argumen.startsWith('daring-lalu-shell')) {
                await daring.putarAntrean();
                await tunggu(20);
                // Tab shell masih terbuka: putaran 30 detik atau event online-nya berjalan.
                await shell.putarAntrean();
                await tunggu(20);
            }

            if (argumen.startsWith('bersamaan')) {
                skrip = [{ status: 204, tunda: 15 }, { status: 204, tunda: 15 }, { status: 204, tunda: 15 }, { status: 204, tunda: 15 }];
                await Promise.all([shell.putarAntrean(), daring.putarAntrean()]);
                await tunggu(80);
            }

            if (argumen === 'catat-di-tab-basi') {
                // Tinjauan Tugas 10 m-B: tab daring mengirim antrean bersama lalu menjeda lagi; tab shell
                // yang masih terbuka mencatat tiba tanpa pernah membaca ulang sejak itu. Penantian
                // menunggu kirimannya benar-benar terjadi, bukan jeda tetap: jeda 20 ms terlalu pendek
                // di sebagian mesin, sehingga urutannya bergeser dan cacatnya tidak muncul.
                const sampai = async (n) => {
                    for (let i = 0; i < 400 && panggilan.length < n; i++) {
                        await tunggu(5);
                    }
                };
                await daring.putarAntrean();
                await sampai(2);
                await tunggu(20);
                await daring.kirimAksi('/trips/1/hike/jeda', { pada: '2026-10-10T02:00:00.000Z' });
                await sampai(3);
                await tunggu(30);
                await shell.kirimAksi('/trips/1/hike/tiba', { urutan: 2 });
                await tunggu(300);
            }

            hasil = {
                urutanPanggilan: panggilan.map((p) => p.url).join(','),
                kiriman: panggilan.map((p) => `${p.url} ${p.isi.pada?.slice(11, 16) ?? p.isi.urutan}`),
                sisaIdb: (peta.get('aksi') ?? []).length,
                namaKunci: [...new Set(namaKunci)],
            };
        }

        if (nama === 'g8') {
            const m = await muatPenuh();
            m.pakaiPenyimpanan(m.penyimpananSessionStorage('k1'), 'k1');
            let keadaan = null;
            m.dengarAntrean((k) => {
                keadaan = k;
            });
            await tunggu(5);
            skrip = [{ status: 419 }];
            await m.kirimAksi('/a', { urutan: 1 });
            const sebelum = { ...keadaan };
            m.kosongkanAntrean();
            const sesudah = { ...keadaan };
            const antrean = urlDari(await m.antreanSaatIni());
            navigator.onLine = false;
            await m.kirimAksi('/b', { urutan: 2 });
            const tersimpanLama = urlDari(JSON.parse(isiSesi.get('mendaki.antrean.k1') ?? '[]'));
            hasil = { sebelum, keadaan: sesudah, antrean, tersimpanLama };
        }

        console.log(JSON.stringify(hasil));
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
}
