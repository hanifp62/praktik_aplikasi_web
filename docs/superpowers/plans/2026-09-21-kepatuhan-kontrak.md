# Kepatuhan Kontrak CLAUDE.md — Fase 1: Bukti yang Dapat Dipercaya

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menutup empat pelanggaran kontrak `CLAUDE.md` yang sudah terverifikasi ada di repositori ini, sehingga klaim "sudah benar" pada fase berikutnya berdiri di atas bukti mesin, bukan di atas dokumen.

**Architecture:** Tiga dari empat task menegakkan aturannya lewat test penjaga yang memindai kode sumber — pola yang sudah dipakai repositori ini di `tests/Feature/SecretsNotInRepoTest.php`, karena aturan yang hanya ditulis di dokumen akan dilanggar tanpa sengaja. Task ketiga memindahkan satu pembuktian yang tidak mungkin dilakukan SQLite ke suite PostgreSQL yang sudah berjalan di CI. Task keempat merapikan dokumen konteks dan mengamankannya ke riwayat git secara lokal.

**Tech Stack:** PHP 8.5.10, Laravel 13.31.0, PHPUnit (gaya kelas, bukan Pest), Livewire, PostgreSQL + PostGIS (CI), SQLite in-memory (suite utama), Pint.

**Spec:** `CLAUDE.md` di root proyek. Bagian yang menjadi dasar tiap task disebut per task.

## Global Constraints

Berlaku untuk setiap task di bawah ini, tanpa perlu diulang:

- **Jangan push, merge, tag, atau force push.** Commit lokal boleh. Persetujuan pemilik produk wajib sebelum menyentuh remote. (§86)
- **Jangan mengakses Supabase** — termasuk `migrate:status`, `migrate`, dan kuncinya — tanpa izin eksplisit pemilik produk pada saat itu juga. Kunci tidak pernah ditulis ke berkas mana pun. (§14, §41, §87)
- **Komentar dan docblock ditulis dalam Bahasa Indonesia**, menjelaskan *mengapa*, bukan *apa*. Nama method test dalam Bahasa Inggris `snake_case` berawalan `test_`. Ini pola yang sudah berlaku di seluruh `tests/`.
- **Pint harus bersih** sebelum setiap commit: `vendor/bin/pint --test`.
- **Suite utama berjalan di SQLite in-memory** (`phpunit.xml:34`). Hijau di sana tidak pernah menjadi bukti perilaku PostgreSQL. Klaim yang menyangkut kunci baris, transaksi, constraint, atau PostGIS wajib dibuktikan di suite `Spatial`. (§51)
- **Skor numerik internal tidak pernah sampai ke antarmuka.** Label publik hanya `COCOK`, `PERLU PERSIAPAN`, `KURANG COCOK`. (§14)
- **Jangan mengubah versi framework** dan jangan menambah dependensi baru dalam rencana ini. (§56)
- Jalankan `php artisan test --testsuite=Unit --testsuite=Feature` sebelum commit pada task yang menyentuh `app/`. Angka dasar sebelum rencana ini: **874 test, 863 lulus, 11 dilewati, 0 gagal**.

---

### Task 1: Panduan agen berisi panduan proyek, bukan instruksi pemasangan

**Dasar kontrak:** §57 mewajibkan Laravel Boost tersedia dan `AGENTS.md` hasil generate dibaca sebelum perubahan aplikasi.

**Masalah terverifikasi:** `laravel/boost` v2.9.1 ada di `composer.lock`, tetapi `AGENTS.md` masih berisi teks bootstrap yang menyuruh memasang Boost. Artinya `php artisan boost:install` belum pernah dijalankan, dan agen yang diwajibkan membaca konvensi proyek justru membaca instruksi instalasi.

**Files:**
- Create: `tests/Feature/AgentGuidelinesTest.php`
- Modify: `AGENTS.md` (ditulis ulang oleh `boost:install`, bukan diketik tangan)

**Interfaces:**
- Consumes: tidak ada.
- Produces: tidak ada API baru. Task ini hanya menegakkan keadaan berkas.

- [ ] **Step 1: Tulis test yang gagal**

Buat `tests/Feature/AgentGuidelinesTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Panduan agen harus berisi konvensi proyek ini, bukan cara memasang alatnya.
 *
 * `composer require laravel/boost` sudah dijalankan tetapi `php artisan boost:install`
 * belum, sehingga AGENTS.md tertinggal sebagai teks bootstrap. Akibatnya setiap agen
 * yang menuruti CLAUDE.md §57 membaca instruksi pemasangan dan menyangka itulah
 * konvensi proyeknya. Kesalahan ini tidak terlihat oleh siapa pun yang tidak membuka
 * berkasnya, jadi mesin yang mengingatkannya.
 */
class AgentGuidelinesTest extends TestCase
{
    public function test_the_agent_guidelines_are_not_the_bootstrap_stub(): void
    {
        $panduan = file_get_contents(base_path('AGENTS.md'));

        $this->assertStringNotContainsString(
            'Install Laravel Boost from the application root',
            $panduan,
            'AGENTS.md masih teks bootstrap Boost. Jalankan: php artisan boost:install'
        );
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan ia gagal**

Jalankan: `php artisan test --filter=AgentGuidelinesTest`
Diharapkan: FAIL, dengan pesan "AGENTS.md masih teks bootstrap Boost."

- [ ] **Step 3: Jalankan boost:install**

```sh
php artisan boost:install
```

Perintah ini interaktif. Pilih Laravel Boost untuk menulis ulang `AGENTS.md`. Jangan mengetik isi `AGENTS.md` secara manual — yang diuji adalah bahwa panduan itu benar-benar dihasilkan alatnya.

- [ ] **Step 4: Jalankan test untuk memastikan ia lulus**

Jalankan: `php artisan test --filter=AgentGuidelinesTest`
Diharapkan: PASS.

- [ ] **Step 5: Baca ulang AGENTS.md hasil generate**

§57 menuntut panduan hasil generate dibaca, bukan sekadar ada. Catat konvensi yang berbeda dari kebiasaan sekarang; bila ada yang bertentangan dengan `CLAUDE.md`, laporkan ke pemilik produk dan jangan diam-diam memilih salah satu (§0 butir 16).

- [ ] **Step 6: Pint dan commit**

```sh
vendor/bin/pint
git add AGENTS.md tests/Feature/AgentGuidelinesTest.php
git commit -m "chore: panduan agen dihasilkan Boost, bukan teks pemasangan"
```

---

### Task 2: Tanggal kalender tidak pernah lahir dari jam aplikasi

**Dasar kontrak:** §54 mencatat dua cacat produksi dari kelas ini sebagai skenario regresi permanen — perhitungan izin yang menambah tujuh jam setiap hari, dan dasbor yang berkata "Berangkat besok" untuk keberangkatan hari itu juga. §20 menuntut waktu selalu punya acuan yang jelas.

**Masalah terverifikasi:** zona aplikasi adalah UTC, dan tidak ada pengguna sistem ini yang hidup di UTC. `app/Support/Timezone.php` sudah menyediakan lantai dan langit-langit tanggal Indonesia, tetapi tiga tempat masih menurunkan tanggal kalender langsung dari jam aplikasi:

- `app/Livewire/Reports/ConditionReportForm.php:51` — `now()->toDateString()` sebagai isian awal `hike_date`. Selama tujuh jam setiap hari, tanggal UTC masih tanggal kemarin bagi WIB, jadi formulir mengisi sendiri tanggal yang salah bagi pelapor yang baru turun sore hari.
- `app/Models/ExpertCredential.php:95` — kredensial dianggap masih berlaku sampai tujuh jam setelah tanggal kedaluwarsanya lewat di Indonesia.
- `app/Services/ConditionAggregatorService.php:54` — batas jendela kesegaran laporan digeser mundur, sehingga laporan lama masih terhitung "terbaru". Ini tetangga langsung cacat regresi nomor 3.

**Files:**
- Create: `tests/Feature/DomainDateGuardTest.php`
- Modify: `app/Support/Timezone.php:41` (tanda tangan `earliestDateInIndonesia`)
- Modify: `app/Livewire/Reports/ConditionReportForm.php:51`
- Modify: `app/Models/ExpertCredential.php:95`
- Modify: `app/Services/ConditionAggregatorService.php:54`

**Interfaces:**
- Consumes: `App\Support\Timezone::latestDateInIndonesia(): string` (sudah ada).
- Produces: `App\Support\Timezone::earliestDateInIndonesia(int $mundurHari = 0): string` — parameter baru dengan nilai bawaan, sehingga seluruh pemanggil lama tetap berjalan tanpa perubahan.

- [ ] **Step 1: Tulis test penjaga yang gagal**

Buat `tests/Feature/DomainDateGuardTest.php`:

```php
<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Tanggal kalender tidak boleh lahir dari jam aplikasi.
 *
 * Zona aplikasi adalah UTC dan tidak ada pengguna sistem ini yang hidup di UTC.
 * Selama tujuh jam setiap hari tanggal UTC masih tanggal kemarin bagi WIB dan sembilan
 * jam bagi WIT. Dua cacat produksi yang tercatat di CLAUDE.md §54 lahir persis dari
 * jendela itu: izin pendakian yang salah hitung, dan dasbor yang berkata "Berangkat
 * besok" untuk keberangkatan hari itu juga.
 *
 * App\Support\Timezone menyediakan lantai (WIB, tanggal terkecil) dan langit-langit
 * (WIT, tanggal terbesar) yang benar. Test ini menolak setiap jalan pintas yang
 * melewatinya. Timezone sendiri lolos karena selalu menyebut zonanya secara eksplisit;
 * yang dilarang adalah jam tanpa zona, bukan Carbon.
 */
class DomainDateGuardTest extends TestCase
{
    public function test_no_calendar_date_is_derived_from_the_unzoned_clock(): void
    {
        $pelanggaran = [];

        foreach ($this->berkasPhpDiApp() as $berkas) {
            foreach (preg_split('/\R/', file_get_contents($berkas)) as $indeks => $baris) {
                if (preg_match("/(now\(\)|today\(\))[^;]*(->toDateString\(\)|->format\('Y-m-d'\))/", $baris)) {
                    $relatif = str_replace(base_path().DIRECTORY_SEPARATOR, '', $berkas);
                    $pelanggaran[] = str_replace('\\', '/', $relatif).':'.($indeks + 1);
                }
            }
        }

        $this->assertSame(
            [],
            $pelanggaran,
            "Tanggal kalender diturunkan dari jam tanpa zona. Pakai App\\Support\\Timezone:\n"
            .implode("\n", $pelanggaran)
        );
    }

    /**
     * @return list<string>
     */
    private function berkasPhpDiApp(): array
    {
        $berkas = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path()));

        foreach ($iterator as $item) {
            if ($item->isFile() && $item->getExtension() === 'php') {
                $berkas[] = $item->getPathname();
            }
        }

        sort($berkas);

        return $berkas;
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan ia gagal dengan tepat tiga temuan**

Jalankan: `php artisan test --filter=DomainDateGuardTest`
Diharapkan: FAIL, mendaftar tepat tiga baris:

```
app/Livewire/Reports/ConditionReportForm.php:51
app/Models/ExpertCredential.php:95
app/Services/ConditionAggregatorService.php:54
```

Bila jumlahnya berbeda, berhenti dan periksa: berarti kode berubah sejak rencana ini ditulis, dan tiap temuan tambahan butuh keputusan zonanya sendiri.

- [ ] **Step 3: Beri Timezone kemampuan mundur beberapa hari**

Di `app/Support/Timezone.php`, ganti `earliestDateInIndonesia()` menjadi:

```php
    public static function earliestDateInIndonesia(int $mundurHari = 0): string
    {
        return Carbon::now('Asia/Jakarta')->subDays($mundurHari)->toDateString();
    }
```

Docblock yang sudah ada di atasnya tetap berlaku dan tidak perlu diubah; tambahkan satu kalimat di akhirnya:

```
     * Parameter $mundurHari melayani jendela mundur seperti "laporan tujuh hari
     * terakhir", supaya batas jendela pun tidak lahir dari jam tanpa zona.
```

- [ ] **Step 4: Perbaiki isian awal tanggal pendakian**

Di `app/Livewire/Reports/ConditionReportForm.php:51`, ganti:

```php
        $this->hike_date = now()->toDateString();
```

menjadi:

```php
        // WIB berganti hari paling akhir, jadi tanggalnya selalu yang terkecil di
        // antara ketiga zona Indonesia dan tidak pernah jatuh di masa depan bagi
        // pelapor mana pun. Memakai jam aplikasi di sini mengisi formulir dengan
        // tanggal kemarin selama tujuh jam setiap hari.
        $this->hike_date = Timezone::earliestDateInIndonesia();
```

Impornya sudah ada di `app/Livewire/Reports/ConditionReportForm.php:13` — berkas ini memakai `Timezone::latestDateInIndonesia()` untuk validasi `hike_date` di baris 62. Tidak ada impor baru yang perlu ditambahkan di sini.

- [ ] **Step 5: Perbaiki masa berlaku kredensial**

Di `app/Models/ExpertCredential.php:95`, ganti `now()->toDateString()` menjadi `Timezone::latestDateInIndonesia()`, dan tambahkan komentar tepat di atas baris itu:

```php
            // WIT berganti hari paling dahulu, jadi tanggalnya yang terbesar. Kredensial
            // yang kedaluwarsa berhenti berlaku secepat mungkin, bukan selambat mungkin:
            // tanggal UTC membuatnya tetap sah sampai tujuh jam setelah lewat di Indonesia.
```

Tambahkan `use App\Support\Timezone;` — berkas ini belum mengimpornya (terverifikasi 21 September 2026).

- [ ] **Step 6: Perbaiki batas jendela kesegaran laporan**

Di `app/Services/ConditionAggregatorService.php:54`, ganti:

```php
            ->where('hike_date', '>=', now()->subDays($days)->toDateString())
```

menjadi:

```php
            // Batas jendela memakai tanggal WIB. Tanggal UTC menggesernya mundur sampai
            // tujuh jam, sehingga laporan yang sudah di luar jendela masih terhitung
            // terbaru -- persis arah kesalahan cacat regresi nomor 3 (§54).
            ->where('hike_date', '>=', Timezone::earliestDateInIndonesia($days))
```

Tambahkan `use App\Support\Timezone;` — berkas ini belum mengimpornya (terverifikasi 21 September 2026).

- [ ] **Step 7: Jalankan test penjaga untuk memastikan ia lulus**

Jalankan: `php artisan test --filter=DomainDateGuardTest`
Diharapkan: PASS.

- [ ] **Step 8: Jalankan seluruh suite untuk memastikan tidak ada yang rusak**

Jalankan: `php artisan test --testsuite=Unit --testsuite=Feature`
Diharapkan: 0 gagal. Jumlah test bertambah dua dari angka dasar (satu dari Task 1, satu dari task ini).
Perhatikan khususnya `ReadinessConditionsTest`, `DashboardRelevanceTest`, dan `TripPermitWindowTest` — ketiganya menyentuh tanggal. Bila salah satu gagal, bacalah sebagai temuan, bukan gangguan: kemungkinan besar test itu ikut memakai jam tanpa zona.

- [ ] **Step 9: Pint dan commit**

```sh
vendor/bin/pint
git add tests/Feature/DomainDateGuardTest.php app/Support/Timezone.php app/Livewire/Reports/ConditionReportForm.php app/Models/ExpertCredential.php app/Services/ConditionAggregatorService.php
git commit -m "fix: tanggal kalender domain tidak lagi lahir dari jam tanpa zona"
```

---

### Task 3: Kunci baris timbangan dibuktikan di PostgreSQL

**Dasar kontrak:** §51 menyatakan test integrasi PostgreSQL WAJIB untuk konkurensi dan penguncian, dan §36 menuntut pola query yang benar di basis data sungguhan.

**Masalah terverifikasi:** `app/Services/ConsiderationService.php:38` memakai `lockForUpdate()` untuk menjaga batas lima jalur agar tidak menjadi enam ketika dua perangkat mengirim bersamaan. Docblock di berkas itu dan di `tests/Feature/ConsiderationTest.php:103` sama-sama menyatakan dengan jujur bahwa kunci itu tanpa efek di SQLite, sehingga suite tidak membuktikan apa pun tentangnya. CI sudah menjalankan PostGIS (`.github/workflows/ci.yml`), jadi tempat pembuktiannya sudah tersedia dan belum dipakai.

**Hambatan teknis yang harus ditangani:** `tests/Spatial/SpatialTestCase.php` memakai `RefreshDatabase`, yang membungkus setiap test dalam transaksi. Koneksi kedua tidak akan pernah melihat baris yang dibuat di dalam transaksi itu, sehingga test kunci mustahil ditulis di atas basis kelas tersebut. Karena itu logika DSN-nya diangkat menjadi trait, lalu dipakai oleh dua basis kelas: yang lama dengan `RefreshDatabase`, dan yang baru dengan `DatabaseTruncation`.

**Files:**
- Create: `tests/Spatial/UsesSpatialDatabase.php`
- Modify: `tests/Spatial/SpatialTestCase.php` (memakai trait, isinya berkurang)
- Create: `tests/Spatial/TruncatingSpatialTestCase.php`
- Create: `tests/Spatial/ConsiderationLockTest.php`

**Interfaces:**
- Consumes: `App\Services\ConsiderationService::toggle(User $user, Trail $trail): ConsiderationOutcome`, konstanta `ConsiderationService::BATAS` (bernilai 5), enum `App\Enums\ConsiderationOutcome` dengan kasus `DITAMBAHKAN`, `DIHAPUS`, `DITOLAK`.
- Produces: trait `Tests\Spatial\UsesSpatialDatabase` dengan method `protected function siapkanBasisDataSpasial(): void` dan `protected function pastikanPostgisTersedia(): void`, konstanta `HOST_TERLARANG`, serta nama koneksi kedua `pgsql_kedua` yang didaftarkannya.

- [ ] **Step 1: Angkat logika DSN menjadi trait**

Buat `tests/Spatial/UsesSpatialDatabase.php`:

```php
<?php

namespace Tests\Spatial;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Penyiapan koneksi untuk test yang membutuhkan PostgreSQL sungguhan.
 *
 * Diangkat dari SpatialTestCase karena ada dua kebutuhan yang tidak dapat memakai satu
 * basis kelas: test geografi cukup dengan RefreshDatabase, sedangkan test penguncian
 * justru mustahil di dalamnya -- koneksi kedua tidak pernah melihat baris yang dibuat
 * di dalam transaksi RefreshDatabase.
 */
trait UsesSpatialDatabase
{
    /**
     * Basis data yang jelas bukan basis data uji. Kedua basis kelas menghapus isi tabel,
     * jadi salah isi DSN sekali saja berarti kehilangan data sungguhan.
     */
    private const HOST_TERLARANG = ['supabase.co', 'supabase.com', 'rds.amazonaws.com', 'neon.tech'];

    protected function siapkanBasisDataSpasial(): void
    {
        $dsn = env('SPATIAL_TEST_DSN');

        if (blank($dsn)) {
            $this->markTestSkipped('SPATIAL_TEST_DSN belum diisi; suite spasial dilewati.');
        }

        $this->refuseProductionLookingHost($dsn);
        $this->configureConnection($dsn);
    }

    protected function pastikanPostgisTersedia(): void
    {
        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        } catch (Throwable $exception) {
            $this->markTestSkipped('PostGIS tidak tersedia pada basis data uji: '.$exception->getMessage());
        }
    }

    private function refuseProductionLookingHost(string $dsn): void
    {
        $host = parse_url($dsn, PHP_URL_HOST) ?: '';

        foreach (self::HOST_TERLARANG as $terlarang) {
            if (str_contains($host, $terlarang)) {
                $this->fail(
                    "SPATIAL_TEST_DSN menunjuk ke {$host}, yang tampak seperti basis data sungguhan. "
                    .'Suite ini menghapus seluruh isi tabel. Pakai Postgres lokal, bukan basis data yang Anda pakai.'
                );
            }
        }

        if (blank(parse_url($dsn, PHP_URL_PATH))) {
            $this->fail('SPATIAL_TEST_DSN tidak menyebutkan nama basis data.');
        }
    }

    /**
     * Koneksi kedua menunjuk basis data yang sama tetapi merupakan sesi PostgreSQL yang
     * terpisah. Tanpa sesi kedua, penguncian baris tidak dapat diamati sama sekali:
     * satu sesi selalu boleh mengunci ulang baris yang sudah dikuncinya sendiri.
     */
    private function configureConnection(string $dsn): void
    {
        $parts = parse_url($dsn);

        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => $parts['host'] ?? '127.0.0.1',
            'database.connections.pgsql.port' => $parts['port'] ?? 5432,
            'database.connections.pgsql.database' => ltrim($parts['path'] ?? '', '/'),
            'database.connections.pgsql.username' => $parts['user'] ?? 'postgres',
            'database.connections.pgsql.password' => $parts['pass'] ?? '',
        ]);

        config(['database.connections.pgsql_kedua' => config('database.connections.pgsql')]);
    }
}
```

- [ ] **Step 2: Ubah SpatialTestCase agar memakai trait**

Di `tests/Spatial/SpatialTestCase.php`, **pertahankan docblock panjang di atas kelasnya apa adanya** — isinya menjelaskan mengapa suite ini ada, dan itu tidak berubah. Yang berubah hanya badan kelasnya:

```php
abstract class SpatialTestCase extends TestCase
{
    use RefreshDatabase;
    use UsesSpatialDatabase;

    protected function setUp(): void
    {
        $this->siapkanBasisDataSpasial();

        parent::setUp();

        $this->pastikanPostgisTersedia();
    }
}
```

Hapus dari kelas itu: `private const HOST_TERLARANG`, `refuseProductionLookingHost()`, `configureConnection()`, serta impor `use Illuminate\Support\Facades\DB;` dan `use Throwable;` yang tidak lagi terpakai. Docblock privat yang menempel pada method-method itu ikut pindah bersama isinya ke trait, bukan dihapus.

- [ ] **Step 3: Jalankan suite spasial yang sudah ada untuk memastikan refactor tidak merusaknya**

Dengan Postgres lokal:

```sh
SPATIAL_TEST_DSN="pgsql://postgres:postgres@127.0.0.1:5432/hiking_test" php artisan test --testsuite=Spatial
```

Diharapkan: `TrailGeometryTest` dan `TrailGeometryImportTest` lulus seperti sebelumnya.
Tanpa Postgres lokal: `php artisan test --testsuite=Spatial` harus dilewati dengan pesan "SPATIAL_TEST_DSN belum diisi", bukan galat.

- [ ] **Step 4: Buat basis kelas yang tidak membungkus test dalam transaksi**

Buat `tests/Spatial/TruncatingSpatialTestCase.php`:

```php
<?php

namespace Tests\Spatial;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

/**
 * Basis untuk test yang membutuhkan dua sesi PostgreSQL sekaligus.
 *
 * RefreshDatabase membungkus setiap test dalam satu transaksi, dan koneksi kedua tidak
 * pernah melihat apa pun yang belum di-commit. Membersihkan tabel dengan truncate lebih
 * lambat, tetapi ia satu-satunya cara menguji perilaku yang hanya muncul di antara dua
 * sesi -- penguncian baris, tepatnya.
 */
abstract class TruncatingSpatialTestCase extends TestCase
{
    use DatabaseTruncation;
    use UsesSpatialDatabase;

    protected function setUp(): void
    {
        $this->siapkanBasisDataSpasial();

        parent::setUp();

        $this->pastikanPostgisTersedia();
    }
}
```

- [ ] **Step 5: Tulis test kunci yang gagal bila kunci tidak nyata**

Buat `tests/Spatial/ConsiderationLockTest.php`:

```php
<?php

namespace Tests\Spatial;

use App\Enums\ConsiderationOutcome;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\ConsiderationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Batas lima jalur bertahan karena kunci baris, dan kunci itu hanya ada di PostgreSQL.
 *
 * ConsiderationService membungkus periksa-lalu-tulisnya dalam transaksi dengan
 * lockForUpdate() supaya dua perangkat yang sama-sama membaca hitungan di bawah batas
 * tidak sama-sama lolos. Di SQLite, tempat seluruh suite utama berjalan, lockForUpdate()
 * tidak melakukan apa pun, sehingga suite itu tidak dapat membuktikan penjagaan ini ada
 * -- dan CLAUDE.md §51 mewajibkan pembuktiannya dilakukan di sini.
 */
class ConsiderationLockTest extends TruncatingSpatialTestCase
{
    private function jalur(): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->create(['is_published' => true]);
    }

    /**
     * Sesi kedua yang mencoba mengunci baris yang sama harus kehabisan waktu.
     *
     * Ini pembuktian langsung bahwa lockForUpdate() mengambil kunci sungguhan: dengan
     * lock_timeout yang pendek, PostgreSQL menolak dengan SQLSTATE 55P03. Bila kuncinya
     * tidak nyata, query kedua justru berhasil dan test ini gagal dengan pesannya sendiri.
     */
    public function test_the_row_lock_actually_blocks_a_second_session(): void
    {
        $user = User::factory()->create();

        DB::connection('pgsql')->beginTransaction();
        User::whereKey($user->id)->lockForUpdate()->first();

        $kedua = DB::connection('pgsql_kedua');
        $kedua->statement("SET lock_timeout = '500ms'");

        try {
            $kedua->table('users')->where('id', $user->id)->lockForUpdate()->first();

            $this->fail('Sesi kedua berhasil mengunci baris yang sudah terkunci; lockForUpdate() tidak mengunci apa pun.');
        } catch (QueryException $galat) {
            $this->assertSame(
                '55P03',
                $galat->getCode(),
                'Galat yang diharapkan adalah lock_not_available (55P03), bukan: '.$galat->getMessage()
            );
        } finally {
            DB::connection('pgsql')->rollBack();
            $kedua->disconnect();
        }
    }

    /**
     * Batas lima tetap lima di basis data produksi, bukan hanya di SQLite.
     */
    public function test_the_sixth_trail_is_refused_on_postgresql(): void
    {
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);

        foreach (range(1, ConsiderationService::BATAS) as $ignored) {
            $this->assertSame(ConsiderationOutcome::DITAMBAHKAN, $service->toggle($user, $this->jalur()));
        }

        $this->assertSame(ConsiderationOutcome::DITOLAK, $service->toggle($user, $this->jalur()));
    }
}
```

- [ ] **Step 6: Jalankan test kunci terhadap PostgreSQL**

```sh
SPATIAL_TEST_DSN="pgsql://postgres:postgres@127.0.0.1:5432/hiking_test" php artisan test --testsuite=Spatial
```

Diharapkan: kedua test baru lulus. Bila `test_the_row_lock_actually_blocks_a_second_session` gagal dengan pesan "tidak mengunci apa pun", itu temuan sungguhan dan bukan test yang salah — laporkan sebelum mengubah apa pun.

Tanpa Postgres lokal, task ini tidak dapat diverifikasi di mesin Anda. CI menjalankannya, tetapi CI baru berjalan setelah cabang di-push, dan push butuh izin pemilik produk (§86). Catat keterbatasan ini apa adanya; jangan menyatakan kuncinya terbukti sebelum suite ini benar-benar hijau di suatu tempat.

- [ ] **Step 7: Perbarui docblock yang menyatakan kunci tidak terbukti**

Di `app/Services/ConsiderationService.php`, docblock `toggle()` menyatakan suite "tidak -- dan tidak bisa -- membuktikan kuncinya". Kalimat itu kini tidak lagi benar. Ganti bagian akhirnya menjadi:

```
     * (basis data produksi) tetapi tanpa efek di SQLite (basis data suite utama), sehingga
     * pembuktiannya tidak berada di ConsiderationTest melainkan di
     * tests/Spatial/ConsiderationLockTest.php, yang berjalan terhadap PostgreSQL.
```

Lakukan penyesuaian serupa pada docblock di `tests/Feature/ConsiderationTest.php:103` sehingga ia menunjuk ke tempat pembuktiannya, bukan menyatakan pembuktian itu mustahil.

- [ ] **Step 8: Jalankan seluruh suite dan commit**

```sh
php artisan test --testsuite=Unit --testsuite=Feature
vendor/bin/pint
git add tests/Spatial app/Services/ConsiderationService.php tests/Feature/ConsiderationTest.php
git commit -m "test: kunci baris timbangan dibuktikan di PostgreSQL, bukan diasumsikan"
```

---

### Task 4: Dokumen konteks berhenti membantah dirinya sendiri

**Dasar kontrak:** §0 butir 16 mewajibkan melaporkan pertentangan antara dokumentasi dan keadaan sebenarnya sebelum bertindak. §86 mengizinkan commit lokal tanpa izin, dan melarang push tanpa izin.

**Masalah terverifikasi:**
- `HANDOVER.md` bagian 6 menyatakan sembilan migrasi **sudah dijalankan** dengan nol Pending, sedangkan bagian 9 di berkas yang sama masih mendaftarkannya sebagai penghalang utama. Keduanya tidak mungkin benar.
- Bagian 7 menyebut "sekitar 149" commit di depan `main`; `git rev-list --count main..hardening` menghasilkan **134**.
- `HANDOVER.md` sama sekali belum dilacak git, dan `CLAUDE.md` punya 2.506 baris perubahan yang belum di-commit. Konteks proyek hanya hidup di disk.

**Files:**
- Modify: `HANDOVER.md` bagian 6, 7, dan 9
- Commit: `CLAUDE.md`, `HANDOVER.md`

**Interfaces:** tidak ada kode yang berubah.

- [ ] **Step 1: Minta izin memeriksa status migrasi**

Tanyakan kepada pemilik produk, dengan kalimat eksplisit, izin menjalankan **hanya baca**:

```sh
php artisan migrate:status
```

Perintah ini menyentuh Supabase, jadi §14, §41, dan §87 berlaku. Jangan menjalankannya sebelum izinnya diberikan pada saat itu juga. Jangan mencetak kredensial apa pun bersama hasilnya.

- [ ] **Step 2: Catat hasilnya, apa pun hasilnya**

Bila izin diberikan: salin ringkasan hasilnya (jumlah `Ran` dan `Pending`) ke `HANDOVER.md` bagian 6, beserta tanggal pemeriksaan. Bila jumlah `Pending` bukan nol, jangan menjalankan `migrate` — laporkan dan minta keputusan terpisah, karena menjalankan migrasi adalah operasi tulis.

Bila izin tidak diberikan: tulis di bagian 6 bahwa statusnya **belum terverifikasi sejak 21 September 2026**, dan bahwa klaim "sudah dijalankan" berasal dari sesi sebelumnya. Jangan menghapus klaim lama dan jangan pula menegaskannya.

- [ ] **Step 3: Selaraskan bagian 9 dengan bagian 6**

Bagian 9 dibuka dengan "Menghalangi pemilik produk melihat hasil: Sembilan migrasi belum jalan di Supabase". Ganti butir itu dengan pernyataan yang sesuai hasil Step 2 — terverifikasi selesai, atau belum terverifikasi. Satu berkas tidak boleh menjawab pertanyaan yang sama dengan dua jawaban.

- [ ] **Step 4: Perbaiki angka commit**

Di bagian 7, ganti "Commit di depan `main`: sekitar 149" menjadi:

```
- Commit di depan `main`: **134** (diukur 21 September 2026 dengan `git rev-list --count main..hardening`).
```

- [ ] **Step 5: Verifikasi dengan membaca ulang**

Jalankan: `grep -n "migrasi\|149\|134" HANDOVER.md`
Diharapkan: tidak ada lagi pernyataan yang saling bertentangan tentang migrasi, dan angka commit hanya muncul satu kali beserta metode pengukurannya.

- [ ] **Step 6: Commit konteks secara lokal**

```sh
git add CLAUDE.md HANDOVER.md
git commit -m "docs: kontrak operasi dan serah-terima konteks masuk riwayat"
```

**Jangan push.** §86 menempatkan push sebagai penyerahan kepada klien dan menuntut izin eksplisit.

---

## Rencana lanjutan, dan mengapa belum ditulis sekarang

Rencana ini sengaja berhenti di empat task. Sisa kepatuhan `CLAUDE.md` — checklist A sampai N di §65–§78 — mencakup 33 modul fungsional, 33 kelompok tabel, relasi, indeks, tipe data, keamanan, observability, UX, tata kelola data, dan pertumbuhan. Menulis task terperinci untuknya sekarang berarti menebak keadaan yang belum diukur, dan §79 melarang PASS tanpa bukti.

Urutan yang benar menurut kontraknya sendiri:

1. **Rencana ini** — menutup empat pelanggaran yang sudah terbukti, sekaligus membuat alat buktinya dapat dipercaya.
2. **Audit §62–§83** — menghasilkan `AUDIT-REPORT.md` lengkap dengan skor per kategori, bukti per item, dan analisis celah. Ini pekerjaan inspeksi, bukan implementasi; §84 melarang mengubah kode sebelum pemilik produk menyetujui rencana hasil auditnya.
3. **Rencana remediasi** — ditulis dari hasil audit, satu rencana per subsistem sebagaimana dituntut Scope Check, dikerjakan per batch prioritas dengan persetujuan pemilik produk di antara batch CRITICAL dan HIGH (§84 butir 11).

Dua hal yang tidak dapat masuk rencana mana pun sampai manusia mengerjakannya, dicatat di sini supaya tidak terlupakan: uji pakai 5–8 pemula untuk enam task validasi UX, dan studi SUS yang menuntut minimal 12 responden (§49, §50, §9).
