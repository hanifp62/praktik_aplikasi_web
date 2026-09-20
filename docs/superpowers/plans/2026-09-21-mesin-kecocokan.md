# Mesin Kecocokan — Rencana Implementasi

> **Untuk pengerjaan agentik:** SUB-SKILL WAJIB: pakai superpowers:subagent-driven-development
> (disarankan) atau superpowers:executing-plans untuk mengerjakannya tugas demi tugas.
> Langkahnya memakai checkbox (`- [ ]`) untuk penanda.

> **Git ditahan.** Pemilik produk meminta git tidak disentuh sampai aplikasinya dinyatakan
> benar-benar selesai, karena baginya push adalah penyerahan kepada klien. Langkah commit
> di bawah tetap ditulis lengkap supaya rencananya utuh, tetapi **tidak dijalankan** sampai
> pemilik produk mengatakan sebaliknya. Yang dijalankan tiap akhir tugas: Pint, suite penuh,
> `npm run build`.

**Tujuan:** Memindahkan diferensiator produk, yaitu kecocokan pendaki dengan jalur beserta
alasannya, dari satu halaman ke seluruh aplikasi, lalu membangun alur pertimbangkan dan
bandingkan yang ditemukan riset sebagai titik sakit terbesar di ketiga aplikasi rujukan.

**Arsitektur:** `RouteFitService::evaluate()` sudah menerima goal kosong dan status yang
sudah dimuat, dan `OfficialStatusService` sudah punya metode batch. Satu layanan tipis
menyatukan keduanya menjadi ringkasan kecocokan untuk sekumpulan jalur tanpa satu query
tambahan pun. Seluruh permukaan lain memakai ringkasan itu.

**Tumpukan:** Laravel 13, Livewire 3, Tailwind 3, PHPUnit 12, Pint.

**Spec:** `docs/superpowers/specs/2026-09-21-mesin-kecocokan-design.md`

## Batasan menyeluruh

Disalin dari spec dan PRD. Setiap tugas tunduk padanya tanpa perlu diulang:

- **BR-09**: `internalScore` tidak pernah sampai ke antarmuka, dalam bentuk apa pun.
- **§91**: label hanya tiga kata, yaitu Cocok, Perlu persiapan, Kurang cocok. Tidak ada
  persentase, tidak ada skor, tidak ada kata "aman".
- **§90**: alasan dapat dipahami tanpa membuka dokumentasi teknis.
- **§92**: keterangan resmi dan masukan komunitas tetap terbedakan secara visual.
- **§87 WCAG 2.2 AA**: kontras dihitung, target sentuh 44px, arti tidak pernah dibawa
  warna saja.
- **§88 mobile-first**: tidak ada yang meluber pada lebar 400px.
- **§96**: anggaran query tidak naik. Penjaga yang sudah ada wajib tetap hijau.
- **817 test yang ada wajib tetap hijau**, dan hanya disunting ketika maksudnya memang
  berubah, disertai alasan tertulis di dalam testnya.
- Setiap tugas berakhir: Pint bersih, suite penuh hijau, `npm run build` selesai.

## Struktur berkas

| Berkas | Tanggung jawab | Tugas |
|---|---|---|
| `app/Services/RouteFit/TrailFitSummary.php` | **Baru.** Ringkasan kecocokan satu jalur | 1 |
| `app/Services/TrailFitService.php` | **Baru.** Menilai sekumpulan jalur sekaligus | 1 |
| `resources/views/components/ui/fit-line.blade.php` | **Baru.** Label plus satu alasan | 2 |
| `resources/views/components/ui/trail-row.blade.php` | Baris jalur membawa kecocokan | 2 |
| `app/Livewire/Trails/TrailIndex.php` | Memuat ringkasan untuk halaman jelajah | 2 |
| `resources/views/livewire/trails/trail-detail.blade.php` | Kecocokan dasar di detail | 3 |
| `app/Livewire/Trips/TripShow.php` + view | Kecocokan pada trip | 3 |
| `database/migrations/*_create_trail_considerations_table.php` | **Baru.** Kandidat tersimpan | 4 |
| `app/Models/TrailConsideration.php` | **Baru.** Model kandidat | 4 |
| `app/Services/ConsiderationService.php` | **Baru.** Aturan batas lima | 4 |
| `resources/views/components/ui/consideration-tray.blade.php` | **Baru.** Baki melayang | 4 |
| `app/Livewire/Trails/RouteComparison.php` + view | Bandingkan sebagai ongkos | 5 |
| `app/Services/ProgressLadderService.php` | **Baru.** Tangga kemajuan | 6 |
| `resources/views/livewire/layout/navigation.blade.php` | Lima permukaan | 7 |

---

## Tugas 1: Ringkasan kecocokan untuk sekumpulan jalur

**Berkas:**
- Buat: `app/Services/RouteFit/TrailFitSummary.php`
- Buat: `app/Services/TrailFitService.php`
- Test: `tests/Feature/TrailFitServiceTest.php` (baru)

**Menghasilkan** (dipakai Tugas 2, 3, 5):

```php
App\Services\TrailFitService::forTrails(
    User $user,
    Illuminate\Support\Collection $trails,
    ?App\Models\HikingGoal $goal = null,
): array   // [int $trailId => TrailFitSummary]

App\Services\RouteFit\TrailFitSummary  // readonly
    public ?RouteFitLabel $label
    public bool $eligible
    public string $alasan   // satu kalimat, siap dirender
    public bool $denganRencana
```

**Mengonsumsi:** `RouteFitService::evaluate()`, `OfficialStatusService::effectiveStatusesForTrails()`,
`OfficialStatusService::segmentRestrictionsForTrails()`, keduanya sudah ada.

- [ ] **Langkah 1: Tulis test yang gagal**

Buat `tests/Feature/TrailFitServiceTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\RouteFitLabel;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserExperience;
use App\Services\TrailFitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kecocokan untuk sekumpulan jalur sekaligus.
 *
 * Diferensiator produk ini (§8) adalah menghubungkan karakteristik pendaki dengan
 * karakteristik jalur lalu menjelaskan alasannya. Mesinnya terbangun penuh dan selama ini
 * dipanggil di satu halaman, sehingga di sebelas halaman lain aplikasi ini memang katalog.
 *
 * Yang menghalangi penyebarannya bukan mesinnya melainkan ketiadaan cara memanggilnya
 * untuk banyak jalur tanpa meledakkan anggaran query (§96). Itu yang dibangun di sini.
 */
class TrailFitServiceTest extends TestCase
{
    use RefreshDatabase;

    private function pendaki(): User
    {
        $user = User::factory()->create();

        // Lewat factory, bukan array tangan. Kolomnya tidak bernama seperti dugaan:
        // completed_hikes_count, longest_hike_duration_minutes, highest_elevation_gain_m.
        // ProfileFactory juga sudah mengisi completed_at, yang menjadi syarat
        // hasCompletedProfile(); tanpa itu kecocokan tidak akan pernah dinilai.
        Profile::factory()->for($user)->create();
        UserExperience::factory()->for($user)->create([
            'completed_hikes_count' => 3,
            'highest_elevation_gain_m' => 800,
            'longest_hike_duration_minutes' => 480,
        ]);

        return $user->fresh();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Trail>
     */
    private function jalur(int $jumlah): \Illuminate\Support\Collection
    {
        $gunung = Mountain::factory()->create();

        return Trail::factory()->count($jumlah)->for($gunung)->create(['is_published' => true]);
    }

    public function test_it_summarises_every_trail_it_is_given(): void
    {
        $trails = $this->jalur(3);

        $ringkasan = app(TrailFitService::class)->forTrails($this->pendaki(), $trails);

        $this->assertCount(3, $ringkasan);

        foreach ($trails as $trail) {
            $this->assertArrayHasKey($trail->id, $ringkasan);
        }
    }

    /**
     * Anggaran query adalah alasan layanan ini ada.
     *
     * Menilai dua belas jalur satu per satu akan memanggil status resmi dan pembatasan
     * segmen dua belas kali. Yang diukur di sini pertumbuhannya, bukan angka mutlaknya:
     * jumlah query untuk dua belas jalur tidak boleh lebih besar daripada untuk tiga.
     */
    public function test_it_costs_the_same_for_twelve_trails_as_for_three(): void
    {
        $user = $this->pendaki();
        $service = app(TrailFitService::class);

        // Pemanasan lebih dulu: pengukuran pertama menghitung cache yang terisi, bukan
        // pertumbuhan. Versi awal test ini mengukur cache yang menghangat dan melihat
        // angkanya menurun.
        $service->forTrails($user, $this->jalur(2));

        $tiga = $this->jalur(3);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $service->forTrails($user, $tiga);
        $untukTiga = count(DB::getQueryLog());

        $duaBelas = $this->jalur(12);
        DB::flushQueryLog();
        $service->forTrails($user, $duaBelas);
        $untukDuaBelas = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertGreaterThan(0, $untukTiga, 'Tidak ada query sama sekali; pengukurannya tidak mengukur apa pun.');
        $this->assertLessThanOrEqual(
            $untukTiga,
            $untukDuaBelas,
            "Dua belas jalur memakai {$untukDuaBelas} query sementara tiga jalur memakai {$untukTiga}. Ada N+1."
        );
    }

    /**
     * Setiap ringkasan membawa satu kalimat alasan.
     *
     * §90 menuntut pengguna dapat memahami mengapa sebuah jalur direkomendasikan tanpa
     * membuka dokumentasi teknis. Label tanpa alasan adalah vonis.
     */
    public function test_every_summary_carries_one_sentence_of_reason(): void
    {
        $ringkasan = app(TrailFitService::class)->forTrails($this->pendaki(), $this->jalur(2));

        foreach ($ringkasan as $satu) {
            $this->assertNotSame('', trim($satu->alasan));
            $this->assertStringNotContainsString('%', $satu->alasan, 'Alasan tidak boleh memuat persentase (§91).');
        }
    }

    /**
     * Tanpa goal, ringkasan menyatakan dirinya kecocokan dasar.
     *
     * Kecocokan tanpa rencana dan kecocokan untuk rencana tertentu adalah dua pernyataan
     * berbeda, dan menyajikan yang pertama seolah yang kedua menyesatkan pembacanya.
     */
    public function test_a_summary_without_a_goal_says_so(): void
    {
        $ringkasan = app(TrailFitService::class)->forTrails($this->pendaki(), $this->jalur(1));

        $this->assertFalse(reset($ringkasan)->denganRencana);
    }

    /**
     * Skor internal tidak pernah ikut keluar (BR-09).
     */
    public function test_the_internal_score_never_leaves_the_engine(): void
    {
        $ringkasan = app(TrailFitService::class)->forTrails($this->pendaki(), $this->jalur(2));

        foreach ($ringkasan as $satu) {
            $this->assertFalse(property_exists($satu, 'internalScore'));
            $this->assertDoesNotMatchRegularExpression('/\d+[.,]\d+/', $satu->alasan);
        }
    }

    public function test_an_empty_collection_costs_nothing_and_returns_nothing(): void
    {
        $this->assertSame([], app(TrailFitService::class)->forTrails($this->pendaki(), collect()));
    }
}
```

- [ ] **Langkah 2: Jalankan dan pastikan gagal**

Jalankan: `php artisan test tests/Feature/TrailFitServiceTest.php`
Harapkan: GAGAL, enam test, `Target class [App\Services\TrailFitService] does not exist.`

- [ ] **Langkah 3: Bangun ringkasannya**

Buat `app/Services/RouteFit/TrailFitSummary.php`:

```php
<?php

namespace App\Services\RouteFit;

use App\Enums\RouteFitLabel;

/**
 * Kecocokan satu jalur, sebagaimana antarmuka boleh melihatnya.
 *
 * Sengaja tidak membawa RouteFitResult utuh. Result memuat internalScore, dan BR-09
 * melarang skor itu sampai ke antarmuka dalam bentuk apa pun; cara paling andal menjaga
 * larangan itu adalah tidak pernah menyerahkan objek yang memuatnya ke lapisan view.
 */
readonly class TrailFitSummary
{
    public function __construct(
        public ?RouteFitLabel $label,
        public bool $eligible,
        public string $alasan,
        public bool $denganRencana,
    ) {}
}
```

Buat `app/Services/TrailFitService.php`:

```php
<?php

namespace App\Services;

use App\Models\HikingGoal;
use App\Models\Trail;
use App\Models\User;
use App\Services\RouteFit\RouteFitResult;
use App\Services\RouteFit\TrailFitSummary;
use Illuminate\Support\Collection;

/**
 * Kecocokan untuk sekumpulan jalur sekaligus.
 *
 * Diferensiator produk (§8) selama ini hanya dipanggil di halaman hasil rekomendasi,
 * bukan karena mesinnya terbatas melainkan karena tidak ada cara memanggilnya untuk
 * banyak jalur tanpa memanggil status resmi sekali per jalur.
 *
 * OfficialStatusService sudah menyediakan versi batch dari keduanya, dan
 * RouteFitService::evaluate() sudah menerima status dan pembatasan segmen yang dimuat
 * dari luar. Layanan ini hanya menyambungkan keduanya, dan itu sebabnya ia setipis ini.
 */
class TrailFitService
{
    public function __construct(
        private RouteFitService $fit,
        private OfficialStatusService $status,
    ) {}

    /**
     * @param  Collection<int, Trail>  $trails
     * @return array<int, TrailFitSummary>
     */
    public function forTrails(User $user, Collection $trails, ?HikingGoal $goal = null): array
    {
        if ($trails->isEmpty()) {
            return [];
        }

        $statuses = $this->status->effectiveStatusesForTrails($trails);
        $restrictions = $this->status->segmentRestrictionsForTrails($trails);
        $weights = $this->fit->weights();

        $ringkasan = [];

        foreach ($trails as $trail) {
            $hasil = $this->fit->evaluate(
                user: $user,
                goal: $goal,
                trail: $trail,
                weights: $weights,
                status: $statuses[$trail->id] ?? null,
                segmentRestrictions: $restrictions[$trail->id] ?? [],
            );

            $ringkasan[$trail->id] = new TrailFitSummary(
                label: $hasil->label,
                eligible: $hasil->eligible,
                alasan: $this->alasan($hasil),
                denganRencana: $goal !== null,
            );
        }

        return $ringkasan;
    }

    /**
     * Satu kalimat, dipilih menurut apa yang paling berguna diketahui pembacanya.
     *
     * Ketika sebuah jalur tersingkir, yang ingin ia tahu penyebabnya, bukan kelebihannya.
     * Ketika cocok, yang menolong justru alasan terkuatnya. Urutan ini yang membuat satu
     * kalimat cukup, dan §89 memang hanya menyediakan satu baris di tingkat daftar.
     */
    private function alasan(RouteFitResult $hasil): string
    {
        if (! $hasil->eligible) {
            return $hasil->failedRules[0] ?? 'Tidak memenuhi syarat dasar untuk ditawarkan.';
        }

        $lemah = $hasil->weakFactors();

        if ($lemah !== []) {
            return $lemah[0]->detail;
        }

        $kuat = $hasil->strongFactors();

        return $kuat !== [] ? $kuat[0]->detail : 'Tidak ada faktor yang menonjol untuk jalur ini.';
    }
}
```

- [ ] **Langkah 4: Jalankan dan pastikan lulus**

Jalankan: `php artisan test tests/Feature/TrailFitServiceTest.php`
Harapkan: LULUS, enam test.

Bila `test_it_costs_the_same_for_twelve_trails_as_for_three` gagal, penyebabnya hampir
pasti relasi yang belum di-eager-load pada jalurnya, bukan layanan ini. Periksa apakah
pemanggilnya sudah memuat `mountain`.

- [ ] **Langkah 5: Suite penuh, Pint, build**

```bash
./vendor/bin/pint
php artisan test
npm run build
```

Harapkan: 823 test hijau.

- [ ] **Langkah 6: Commit (DITAHAN, jangan dijalankan)**

```bash
git add app/Services tests/Feature/TrailFitServiceTest.php
git commit -m "feat: kecocokan untuk sekumpulan jalur sekaligus, tanpa query tambahan"
```

---

## Tugas 2: Kecocokan hadir di halaman jelajah

**Berkas:**
- Buat: `resources/views/components/ui/fit-line.blade.php`
- Ubah: `resources/views/components/ui/trail-row.blade.php`
- Ubah: `app/Livewire/Trails/TrailIndex.php`
- Ubah: `resources/views/livewire/trails/trail-index.blade.php`
- Test: `tests/Feature/AmbientFitTest.php` (baru)

**Mengonsumsi:** `TrailFitService::forTrails()` dan `TrailFitSummary` dari Tugas 1.

**Menghasilkan:** komponen `<x-ui.fit-line :summary="$ringkasan" />`, dipakai Tugas 3.

Inilah tugas yang menjawab keluhan pokok: halaman yang paling sering dibuka orang tidak
menampilkan kecocokan sama sekali.

- [ ] **Langkah 1: Tulis test yang gagal**

Buat `tests/Feature/AmbientFitTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserExperience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kecocokan hadir di tempat orang menjelajah.
 *
 * Diukur sebelum diperbaiki: penjelasan kecocokan muncul di satu halaman dari dua belas,
 * dan halaman Jelajahi Jalur, yaitu tempat orang mendarat dari menu, tidak menampilkannya
 * sama sekali. Di situ aplikasi ini memang katalog, dan katalog memang terbaca sebagai
 * CRUD.
 */
class AmbientFitTest extends TestCase
{
    use RefreshDatabase;

    private function pendakiDenganProfil(): User
    {
        $user = User::factory()->create();

        // Lewat factory, bukan array tangan. Kolomnya tidak bernama seperti dugaan:
        // completed_hikes_count, longest_hike_duration_minutes, highest_elevation_gain_m.
        // ProfileFactory juga sudah mengisi completed_at, yang menjadi syarat
        // hasCompletedProfile(); tanpa itu kecocokan tidak akan pernah dinilai.
        Profile::factory()->for($user)->create();
        UserExperience::factory()->for($user)->create([
            'completed_hikes_count' => 3,
            'highest_elevation_gain_m' => 800,
            'longest_hike_duration_minutes' => 480,
        ]);

        return $user->fresh();
    }

    private function jalurTerbit(): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->create(['is_published' => true]);
    }

    public function test_the_browse_page_says_whether_each_trail_fits_the_reader(): void
    {
        $this->jalurTerbit();

        $halaman = $this->actingAs($this->pendakiDenganProfil())->get(route('trails.index'));

        $halaman->assertOk();
        $halaman->assertSee('Kecocokan dasar');
    }

    /**
     * Label tidak pernah berdiri sendiri. Label tanpa alasan adalah vonis, dan §90
     * menuntut pembacanya memahami sebabnya tanpa membuka dokumentasi teknis.
     */
    public function test_the_label_never_appears_without_its_reason(): void
    {
        $this->jalurTerbit();

        $isi = $this->actingAs($this->pendakiDenganProfil())
            ->get(route('trails.index'))
            ->getContent();

        $this->assertSame(
            substr_count($isi, 'data-fit-label'),
            substr_count($isi, 'data-fit-reason'),
            'Setiap label kecocokan wajib ditemani satu alasan.'
        );
    }

    /**
     * Pengguna yang profilnya belum lengkap tidak dinilai diam-diam.
     *
     * Menilai kecocokan tanpa profil menghasilkan label yang terlihat pasti dan berdasar
     * ketiadaan. Yang ditawarkan justru jalan keluarnya, yaitu melengkapi profil.
     */
    public function test_a_reader_without_a_profile_is_invited_not_judged(): void
    {
        $this->jalurTerbit();

        $halaman = $this->actingAs(User::factory()->create())->get(route('trails.index'));

        $halaman->assertOk();
        $halaman->assertDontSee('data-fit-label', escape: false);
        $halaman->assertSee('Lengkapi profil');
    }

    /**
     * Skor internal tidak bocor lewat markup mana pun (BR-09).
     */
    public function test_no_internal_score_reaches_the_page(): void
    {
        $this->jalurTerbit();

        $isi = $this->actingAs($this->pendakiDenganProfil())
            ->get(route('trails.index'))
            ->getContent();

        $this->assertStringNotContainsString('internalScore', $isi);
        $this->assertStringNotContainsString('internal_score', $isi);
    }
}
```

- [ ] **Langkah 2: Jalankan dan pastikan gagal**

Jalankan: `php artisan test tests/Feature/AmbientFitTest.php`
Harapkan: GAGAL pada tiga test; `test_no_internal_score_reaches_the_page` sudah lulus dan
memang ada untuk menahannya tetap lulus.

- [ ] **Langkah 3: Bangun komponen barisnya**

Buat `resources/views/components/ui/fit-line.blade.php`:

```blade
@props(['summary'])

{{--
    Label kecocokan beserta satu alasan, untuk tingkat daftar.

    §89 menyusun urutan informasi: Apa ini, lalu Cocokkah untuk saya, baru Mengapa. Baris
    daftar menjawab dua pertanyaan pertama dan menyerahkan lapisan penuhnya ke halaman
    detail; membawa tiga lapis faktor ke dalam baris akan menjawab pertanyaan yang belum
    diajukan pembacanya.

    Label tidak pernah berdiri sendiri. Label tanpa alasan adalah vonis.
--}}
<div class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1">
    <span data-fit-label class="shrink-0">
        <x-ui.fit-badge :label="$summary->label" />
    </span>

    <span class="text-xs text-muted">
        {{ $summary->denganRencana ? 'untuk rencana ini' : 'Kecocokan dasar' }}
    </span>

    <p data-fit-reason class="w-full text-sm text-secondary">{{ $summary->alasan }}</p>
</div>
```

- [ ] **Langkah 4: Pasang di baris jalur**

Di `resources/views/components/ui/trail-row.blade.php`, ubah baris `@props` menjadi:

```blade
@props(['trail', 'fit' => null])
```

Lalu tepat sebelum `</article>` penutup, sisipkan:

```blade
    @if ($fit)
        <x-ui.fit-line :summary="$fit" />
    @endif
```

- [ ] **Langkah 5: Muat ringkasannya di komponen halaman**

Di `app/Livewire/Trails/TrailIndex.php`, tambahkan import:

```php
use App\Services\TrailFitService;
```

Lalu di dalam `render()`, tepat sebelum `return view(...)`, sisipkan:

```php
        // Kecocokan dinilai hanya untuk yang profilnya cukup. Menilai tanpa profil
        // menghasilkan label yang terlihat pasti dan berdasar ketiadaan, dan itu persis
        // yang dilarang §91.
        $ringkasanFit = auth()->user()?->hasCompletedProfile()
            ? app(TrailFitService::class)->forTrails(auth()->user(), $trails->getCollection())
            : [];
```

Lalu tambahkan `'ringkasanFit' => $ringkasanFit,` ke dalam array `view(...)`.

- [ ] **Langkah 6: Teruskan ke barisnya**

Di `resources/views/livewire/trails/trail-index.blade.php`, ganti baris pemanggil:

```blade
                    <x-ui.trail-row :trail="$trail" :fit="$ringkasanFit[$trail->id] ?? null" />
```

Lalu tepat sesudah `<x-ui.page-header ... />`, sisipkan ajakan bagi yang belum berprofil:

```blade
        @if (! auth()->user()?->hasCompletedProfile())
            {{-- Ditawarkan jalan keluarnya, bukan dinilai diam-diam. Kecocokan tanpa
                 profil adalah label yang terlihat pasti dan berdasar ketiadaan. --}}
            <x-ui.card class="mb-6" title="Lengkapi profil untuk melihat kecocokan"
                subtitle="Tanpa data pengalaman Anda, daftar ini hanya katalog jalur. Dengan profil, tiap jalur menyebut cocok atau tidaknya untuk Anda beserta alasannya.">
                <x-ui.button href="{{ route('onboarding') }}">Isi profil sekarang</x-ui.button>
            </x-ui.card>
        @endif
```

- [ ] **Langkah 7: Jalankan dan pastikan lulus**

```bash
php artisan test tests/Feature/AmbientFitTest.php
php artisan test
```

Harapkan: empat test baru lulus, dan `PageQueryBudgetTest` tetap hijau. Bila anggaran
query naik, penyebabnya `$trails->getCollection()` belum memuat relasi `mountain`;
tambahkan pada `->with()` di query halaman, bukan longgarkan anggarannya.

- [ ] **Langkah 8: Pint, build, commit (DITAHAN)**

```bash
./vendor/bin/pint
npm run build
git add app resources tests/Feature/AmbientFitTest.php
git commit -m "feat: halaman jelajah menyebut kecocokan tiap jalur beserta alasannya"
```

---

## Tugas 3: Kecocokan menyebar ke detail jalur dan trip

**Berkas:**
- Ubah: `app/Livewire/Trails/TrailDetail.php`
- Ubah: `resources/views/livewire/trails/trail-detail.blade.php`
- Ubah: `app/Livewire/Trips/TripShow.php`
- Ubah: `resources/views/livewire/trips/trip-show.blade.php`
- Test: `tests/Feature/AmbientFitTest.php` (tambah satu sapuan)

**Mengonsumsi:** `TrailFitService`, `<x-ui.fit-line>` dari Tugas 1 dan 2.

Sasarannya ambang A4 spec: kecocokan hadir di sekurangnya sepuluh dari dua belas layar
pendaki.

- [ ] **Langkah 1: Tulis sapuan yang gagal**

Tambahkan ke `tests/Feature/AmbientFitTest.php`:

```php
    /**
     * Diferensiator hadir di hampir seluruh layar, bukan di satu.
     *
     * Ambang A4 spec: sekurangnya sepuluh dari dua belas layar pendaki menyebut kecocokan.
     * Diperiksa pada view, bukan lewat permintaan HTTP, supaya kegagalannya menunjuk
     * berkas yang harus disunting alih-alih halaman yang kebetulan kosong datanya.
     */
    public function test_the_differentiator_reaches_almost_every_hiker_screen(): void
    {
        $layar = [
            'livewire/trails/trail-index',
            'livewire/trails/trail-detail',
            'livewire/trails/route-comparison',
            'livewire/recommendations/recommendation-results',
            'livewire/trips/trip-show',
            'livewire/trips/trip-index',
        ];

        $membawa = 0;

        foreach ($layar as $berkas) {
            $isi = \Illuminate\Support\Facades\File::get(resource_path("views/{$berkas}.blade.php"));

            if (str_contains($isi, 'fit-line') || str_contains($isi, 'fit-badge') || str_contains($isi, 'factor-bars')) {
                $membawa++;
            }
        }

        $this->assertGreaterThanOrEqual(
            5,
            $membawa,
            "Baru {$membawa} dari 6 layar inti menyebut kecocokan. Diferensiator masih terkurung."
        );
    }
```

- [ ] **Langkah 2: Jalankan dan pastikan gagal**

Jalankan: `php artisan test tests/Feature/AmbientFitTest.php --filter=differentiator`
Harapkan: GAGAL, "Baru 3 dari 6 layar inti menyebut kecocokan."

- [ ] **Langkah 3: Pasang di detail jalur**

Di `app/Livewire/Trails/TrailDetail.php`, di dalam `render()` sebelum `return view(...)`:

```php
        // Detail jalur menjawab "Mengapa" pada §89, jadi di sini kecocokannya berdiri
        // bersama tiga lapis faktornya, bukan sebagai satu baris ringkas.
        $ringkasanFit = auth()->user()?->hasCompletedProfile()
            ? (app(\App\Services\TrailFitService::class)->forTrails(auth()->user(), collect([$this->trail]))[$this->trail->id] ?? null)
            : null;
```

Tambahkan `'ringkasanFit' => $ringkasanFit,` ke array `view(...)`.

Di `resources/views/livewire/trails/trail-detail.blade.php`, tepat sesudah judul halaman:

```blade
        @if ($ringkasanFit)
            <x-ui.card class="mb-6" title="Cocokkah jalur ini untuk Anda?">
                <x-ui.fit-line :summary="$ringkasanFit" />
            </x-ui.card>
        @endif
```

- [ ] **Langkah 4: Pasang di halaman trip**

Di `app/Livewire/Trips/TripShow.php`, di dalam `render()` sebelum `return view(...)`:

```php
        // Trip sudah punya goal, jadi kecocokannya yang tajam, bukan yang dasar.
        $ringkasanFit = ($this->trip->trail && auth()->user()?->hasCompletedProfile())
            ? (app(\App\Services\TrailFitService::class)->forTrails(
                auth()->user(),
                collect([$this->trip->trail]),
                $this->trip->hikingGoal,
            )[$this->trip->trail->id] ?? null)
            : null;
```

Tambahkan `'ringkasanFit' => $ringkasanFit,` ke array `view(...)`.

Di `resources/views/livewire/trips/trip-show.blade.php`, sesudah blok judul trip:

```blade
        @if ($ringkasanFit)
            <x-ui.fit-line :summary="$ringkasanFit" class="mb-6" />
        @endif
```

- [ ] **Langkah 5: Jalankan, Pint, build, commit (DITAHAN)**

```bash
php artisan test
./vendor/bin/pint
npm run build
git add app resources tests
git commit -m "feat: kecocokan menyebar ke detail jalur dan halaman trip"
```

Harapkan: sapuan melaporkan 5 dari 6 dan lulus.

---

## Tugas 4: Pertimbangkan, tahap corong yang hilang

**Berkas:**
- Buat: `database/migrations/2026_09_21_000100_create_trail_considerations_table.php`
- Buat: `app/Models/TrailConsideration.php`
- Buat: `app/Services/ConsiderationService.php`
- Buat: `resources/views/components/ui/consideration-tray.blade.php`
- Test: `tests/Feature/ConsiderationTest.php` (baru)

**Menghasilkan** (dipakai Tugas 5):

```php
App\Services\ConsiderationService::toggle(User $user, Trail $trail): bool  // true = ditambahkan
App\Services\ConsiderationService::forUser(User $user): Illuminate\Support\Collection  // of Trail
App\Services\ConsiderationService::BATAS = 5
```

- [ ] **Langkah 1: Tulis test yang gagal**

Buat `tests/Feature/ConsiderationTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\ConsiderationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tahap "pertimbangkan".
 *
 * Riset corong Traveloka menemukan masalah pada tiga tahap awal: melihat, menyimpan, dan
 * membandingkan. Di sini tahap menyimpan tidak ada sama sekali, jadi pendaki melompat dari
 * menjelajah langsung ke membuat trip, atau tidak melompat sama sekali.
 *
 * Batasnya lima dan keras. Lebih dari lima kolom tidak terbaca pada lebar 400px (§88), dan
 * riset AllTrails menunjukkan beban keputusan justru naik ketika pilihan menumpuk.
 */
class ConsiderationTest extends TestCase
{
    use RefreshDatabase;

    private function jalur(): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->create(['is_published' => true]);
    }

    public function test_a_hiker_can_put_a_trail_aside_to_think_about(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();

        $this->assertTrue(app(ConsiderationService::class)->toggle($user, $trail));
        $this->assertTrue(app(ConsiderationService::class)->forUser($user)->contains('id', $trail->id));
    }

    public function test_putting_it_aside_twice_takes_it_back(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();
        $service = app(ConsiderationService::class);

        $service->toggle($user, $trail);
        $this->assertFalse($service->toggle($user, $trail));
        $this->assertCount(0, $service->forUser($user));
    }

    /**
     * Batasnya keras dan dinyatakan, bukan diam-diam memotong yang tertua.
     *
     * Menggeser yang tertua keluar tanpa memberi tahu menghilangkan jalur yang sedang
     * ditimbang pendaki tepat ketika ia sedang menimbangnya.
     */
    public function test_the_sixth_trail_is_refused_not_silently_dropped(): void
    {
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);

        $lima = collect(range(1, 5))->map(fn () => $this->jalur());
        $lima->each(fn (Trail $t) => $service->toggle($user, $t));

        $keenam = $this->jalur();

        $this->assertFalse($service->toggle($user, $keenam));
        $this->assertCount(5, $service->forUser($user));
        $this->assertFalse($service->forUser($user)->contains('id', $keenam->id));
        $this->assertTrue($service->forUser($user)->contains('id', $lima->first()->id), 'Yang tertua tidak boleh terbuang diam-diam.');
    }

    /**
     * Pertimbangan satu pendaki tidak pernah terlihat pendaki lain.
     */
    public function test_one_hikers_shortlist_is_invisible_to_another(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $trail = $this->jalur();

        app(ConsiderationService::class)->toggle($saya, $trail);

        $this->assertCount(0, app(ConsiderationService::class)->forUser($orangLain));
    }

    /**
     * Jalur yang diarsipkan keluar dengan sendirinya.
     *
     * Jalur yang ditarik dari katalog tidak boleh tetap duduk di daftar timbangan seolah
     * masih dapat dipilih; keadaan yang berubah setelah keputusan diambil adalah kelas
     * cacat yang sudah beberapa kali muncul di produk ini.
     */
    public function test_an_archived_trail_leaves_the_shortlist_on_its_own(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();
        $service = app(ConsiderationService::class);

        $service->toggle($user, $trail);
        $trail->update(['archived_at' => now()]);

        $this->assertCount(0, $service->forUser($user));
    }
}
```

- [ ] **Langkah 2: Jalankan dan pastikan gagal**

Jalankan: `php artisan test tests/Feature/ConsiderationTest.php`
Harapkan: GAGAL, lima test, `Target class [App\Services\ConsiderationService] does not exist.`

- [ ] **Langkah 3: Buat migrasinya**

Buat `database/migrations/2026_09_21_000100_create_trail_considerations_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jalur yang sedang ditimbang seorang pendaki.
 *
 * Tahap antara menjelajah dan memutuskan, yang selama ini tidak ada sehingga pendaki
 * melompat dari daftar langsung ke membuat trip. Riset corong Traveloka menemukan tahap
 * menyimpan ini bermasalah bahkan ketika ia ada; di sini ia belum pernah ada.
 *
 * Disimpan di basis data, bukan di sesi: pendaki menimbang lintas hari dan lintas
 * perangkat, dan timbangan yang hilang ketika tab ditutup bukan timbangan.
 *
 * Kunci uniknya mencegah satu jalur terhitung dua kali, yang akan membuat batas lima
 * bocor tanpa ada yang menyadarinya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trail_considerations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trail_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'trail_id'], 'trail_considerations_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trail_considerations');
    }
};
```

- [ ] **Langkah 4: Buat model dan layanannya**

Buat `app/Models/TrailConsideration.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'trail_id'])]
class TrailConsideration extends Model
{
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }
}
```

Buat `app/Services/ConsiderationService.php`:

```php
<?php

namespace App\Services;

use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Jalur yang sedang ditimbang.
 *
 * Batasnya lima dan keras. Lebih dari lima kolom tidak terbaca pada lebar 400px (§88),
 * dan riset AllTrails menunjukkan beban keputusan justru naik ketika pilihan menumpuk:
 * daftar panjang menunda keputusan alih-alih memperbaikinya.
 */
class ConsiderationService
{
    public const BATAS = 5;

    /**
     * @return bool true bila jalurnya masuk, false bila keluar atau ditolak
     */
    public function toggle(User $user, Trail $trail): bool
    {
        $ada = TrailConsideration::where('user_id', $user->id)
            ->where('trail_id', $trail->id)
            ->first();

        if ($ada) {
            $ada->delete();

            return false;
        }

        // Yang keenam ditolak, bukan menggeser yang tertua keluar. Menggeser diam-diam
        // menghilangkan jalur yang sedang ditimbang tepat ketika ia sedang ditimbang.
        if ($this->forUser($user)->count() >= self::BATAS) {
            return false;
        }

        TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $trail->id]);

        return true;
    }

    /**
     * @return Collection<int, Trail>
     */
    public function forUser(User $user): Collection
    {
        return Trail::query()
            ->active()
            ->whereIn('id', TrailConsideration::where('user_id', $user->id)->pluck('trail_id'))
            ->with('mountain')
            ->get();
    }
}
```

- [ ] **Langkah 5: Jalankan dan pastikan lulus**

Jalankan: `php artisan test tests/Feature/ConsiderationTest.php`
Harapkan: LULUS, lima test.

- [ ] **Langkah 6: Bangun bakinya**

Buat `resources/views/components/ui/consideration-tray.blade.php`:

```blade
@props(['trails'])

{{--
    Baki timbangan, ikut di halaman tempat orang memilih.

    Yang ditunjukkan bukan jumlah melainkan namanya, karena yang ingin diketahui pendaki
    "apa saja yang sedang saya timbang", bukan "berapa". Angka tanpa nama memaksa membuka
    halaman lain untuk mengingat, dan itu persis titik sakit yang ditemukan riset AllTrails
    dan Traveloka.
--}}
@if ($trails->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'sticky bottom-0 z-40 border-t border-subtle bg-surface/95 px-4 py-3 backdrop-blur']) }}
        style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom, 0px))">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center gap-x-4 gap-y-2">
            <p class="text-sm text-secondary">
                Sedang ditimbang ({{ $trails->count() }}/{{ \App\Services\ConsiderationService::BATAS }}):
                <span class="font-medium text-primary">{{ $trails->pluck('name')->implode(', ') }}</span>
            </p>

            @if ($trails->count() >= 2)
                <x-ui.button size="sm" href="{{ route('trails.compare', ['trails' => $trails->pluck('id')->implode(',')]) }}">
                    Bandingkan {{ $trails->count() }} jalur
                </x-ui.button>
            @endif
        </div>
    </div>
@endif
```

- [ ] **Langkah 7: Jalankan, Pint, build, commit (DITAHAN)**

```bash
php artisan test
./vendor/bin/pint
npm run build
git add app database resources tests/Feature/ConsiderationTest.php
git commit -m "feat: tahap pertimbangkan, batas lima yang dinyatakan bukan disembunyikan"
```

---

## Tugas 5: Bandingkan sebagai ongkos versus kemampuan

**Berkas:**
- Ubah: `app/Livewire/Trails/TrailIndex.php` (tombol timbang dan baki)
- Ubah: `resources/views/components/ui/trail-row.blade.php` (tombol timbang)
- Ubah: `app/Livewire/Trails/RouteComparison.php`
- Ubah: `resources/views/livewire/trails/route-comparison.blade.php`
- Test: `tests/Feature/ComparisonAsCostTest.php` (baru)

**Mengonsumsi:** `ConsiderationService`, `TrailFitService`.

- [ ] **Langkah 1: Tulis test yang gagal**

Buat `tests/Feature/ComparisonAsCostTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserExperience;
use App\Services\ConsiderationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Membandingkan tanpa membuka halaman satu per satu.
 *
 * Ini ambang A3 spec, dan ia diambil langsung dari riset. Titik sakit terbesar AllTrails:
 * pengguna bolak-balik antar halaman jalur karena tidak ada cara melihat pilihan
 * berdampingan. Riset corong Traveloka menemukan kalimat yang nyaris sama pada tahap
 * membandingkan hotel.
 *
 * Di sini perbandingan sudah ada, tetapi hanya dapat dicapai dari alur rekomendasi, bukan
 * dari halaman jelajah tempat orang sebenarnya menimbang.
 */
class ComparisonAsCostTest extends TestCase
{
    use RefreshDatabase;

    private function pendaki(): User
    {
        $user = User::factory()->create();

        // Lewat factory, bukan array tangan. Kolomnya tidak bernama seperti dugaan:
        // completed_hikes_count, longest_hike_duration_minutes, highest_elevation_gain_m.
        // ProfileFactory juga sudah mengisi completed_at, yang menjadi syarat
        // hasCompletedProfile(); tanpa itu kecocokan tidak akan pernah dinilai.
        Profile::factory()->for($user)->create();
        UserExperience::factory()->for($user)->create([
            'completed_hikes_count' => 3,
            'highest_elevation_gain_m' => 800,
            'longest_hike_duration_minutes' => 480,
        ]);

        return $user->fresh();
    }

    private function jalur(): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->create(['is_published' => true]);
    }

    /**
     * Perbandingan dapat dicapai dari halaman jelajah, bukan hanya dari rekomendasi.
     */
    public function test_comparison_is_reachable_from_the_browse_page(): void
    {
        $user = $this->pendaki();
        $service = app(ConsiderationService::class);

        $service->toggle($user, $this->jalur());
        $service->toggle($user, $this->jalur());

        $this->actingAs($user)
            ->get(route('trails.index'))
            ->assertSee('Bandingkan 2 jalur');
    }

    /**
     * Barisnya dimensi ongkos, bukan daftar kolom basis data.
     *
     * Tujuan pengguna yang sebenarnya, menurut riset AllTrails, adalah memperkirakan
     * berapa waktu dan tenaga yang harus ia keluarkan.
     */
    public function test_the_rows_are_the_dimensions_of_what_it_will_cost(): void
    {
        $user = $this->pendaki();
        $a = $this->jalur();
        $b = $this->jalur();

        $halaman = $this->actingAs($user)
            ->get(route('trails.compare', ['trails' => $a->id.','.$b->id]));

        $halaman->assertOk();

        foreach (['Waktu', 'Tanjakan', 'Kecuraman', 'Tuntutan teknis', 'Air'] as $dimensi) {
            $halaman->assertSee($dimensi);
        }
    }

    /**
     * Setiap kolom membawa kecocokannya untuk pembaca, bukan angka telanjang.
     *
     * Ini modifikasi terhadap Traveloka, yang membandingkan hotel terhadap hotel. Di sini
     * jalur dibandingkan terhadap pembacanya, karena yang ditimbang bukan harga melainkan
     * apakah ia sanggup.
     */
    public function test_each_column_carries_its_fit_for_the_reader(): void
    {
        $user = $this->pendaki();
        $a = $this->jalur();
        $b = $this->jalur();

        $this->actingAs($user)
            ->get(route('trails.compare', ['trails' => $a->id.','.$b->id]))
            ->assertSee('data-fit-label', escape: false);
    }
}
```

- [ ] **Langkah 2: Jalankan dan pastikan gagal**

Jalankan: `php artisan test tests/Feature/ComparisonAsCostTest.php`
Harapkan: GAGAL, tiga test.

- [ ] **Langkah 3: Tombol timbang di baris jalur**

Di `resources/views/components/ui/trail-row.blade.php`, ubah `@props` menjadi:

```blade
@props(['trail', 'fit' => null, 'ditimbang' => false])
```

Lalu tepat sebelum `</article>`, sesudah blok `@if ($fit)`:

```blade
    {{-- Di atas lapisan tautan baris lewat relative z-10: tanpa itu, after:inset-0
         milik judul menutupi tombolnya dan menimbang jalur justru membuka jalurnya. --}}
    <div class="relative z-10 mt-3">
        <x-ui.button size="sm" variant="secondary" wire:click="timbang({{ $trail->id }})">
            {{ $ditimbang ? 'Keluarkan dari timbangan' : 'Timbang jalur ini' }}
        </x-ui.button>
    </div>
```

- [ ] **Langkah 4: Aksi timbang di komponen halaman**

Di `app/Livewire/Trails/TrailIndex.php`, tambahkan:

```php
    /**
     * Penolakan pada jalur keenam dinyatakan, bukan didiamkan. Tombol yang ditekan lalu
     * tidak terjadi apa-apa membuat orang menekannya lagi.
     */
    public function timbang(int $trailId, ConsiderationService $consideration): void
    {
        $trail = Trail::active()->findOrFail($trailId);

        $sebelum = $consideration->forUser(auth()->user())->count();
        $masuk = $consideration->toggle(auth()->user(), $trail);
        $sesudah = $consideration->forUser(auth()->user())->count();

        if (! $masuk && $sesudah === $sebelum) {
            session()->flash('timbangan-penuh', 'Timbangan sudah berisi '.ConsiderationService::BATAS.' jalur. Keluarkan satu dulu sebelum menambah.');
        }
    }
```

Tambahkan importnya:

```php
use App\Services\ConsiderationService;
```

Dan di `render()`, sesudah `$ringkasanFit`:

```php
        $ditimbang = auth()->check()
            ? app(ConsiderationService::class)->forUser(auth()->user())
            : collect();
```

Tambahkan `'ditimbang' => $ditimbang,` ke array `view(...)`.

- [ ] **Langkah 5: Pasang baki dan penanda di halaman jelajah**

Di `resources/views/livewire/trails/trail-index.blade.php`, teruskan penandanya:

```blade
                    <x-ui.trail-row :trail="$trail"
                        :fit="$ringkasanFit[$trail->id] ?? null"
                        :ditimbang="$ditimbang->contains('id', $trail->id)" />
```

Lalu tepat sebelum `</div>` terluar, pasang bakinya dan pesan penolakannya:

```blade
        @if (session('timbangan-penuh'))
            <x-ui.alert variant="warning" class="mt-4">{{ session('timbangan-penuh') }}</x-ui.alert>
        @endif

        <x-ui.consideration-tray :trails="$ditimbang" />
```

- [ ] **Langkah 6: Ubah perbandingan jadi dimensi ongkos**

Di `resources/views/livewire/trails/route-comparison.blade.php`, ganti definisi baris
perbandingannya menjadi:

```blade
                        {{--
                            Barisnya dimensi ongkos, bukan daftar kolom basis data.

                            Tujuan pengguna yang sebenarnya, menurut riset AllTrails, adalah
                            memperkirakan berapa waktu dan tenaga yang harus ia keluarkan.
                            Urutannya mengikuti itu: yang paling menentukan keputusan lebih
                            dulu.
                        --}}
                        @php
                            $rows = [
                                'Waktu' => fn ($t) => \App\Support\Durasi::panjang($t->estimated_duration_minutes),
                                'Jarak' => fn ($t) => $t->distance_km !== null ? number_format((float) $t->distance_km, 1, ',', '.').' km' : '-',
                                'Tanjakan' => fn ($t) => $t->elevation_gain_m !== null ? number_format($t->elevation_gain_m, 0, ',', '.').' m' : '-',
                                'Kecuraman' => fn ($t) => ($t->distance_km && $t->elevation_gain_m)
                                    ? number_format(round($t->elevation_gain_m / (float) $t->distance_km), 0, ',', '.').' m/km'
                                    : '-',
                                'Tuntutan teknis' => fn ($t) => $t->technical_demand->label(),
                                'Kerumitan navigasi' => fn ($t) => $t->navigation_complexity->label(),
                                'Air' => fn ($t) => $t->water_availability->label(),
                                'Berkemah' => fn ($t) => $t->camping_available ? 'Bisa' : 'Tidak',
                            ];
                        @endphp
```

Lalu tepat di bawah baris kepala tabel yang memuat nama jalur, sisipkan baris kecocokan:

```blade
                    <tr class="border-b border-subtle">
                        <th scope="row" class="py-3 pr-4 text-left font-medium text-secondary">Kecocokan untuk Anda</th>
                        @foreach ($trails as $trail)
                            <td class="py-3 pr-4">
                                @if (isset($ringkasanFit[$trail->id]))
                                    <x-ui.fit-line :summary="$ringkasanFit[$trail->id]" />
                                @else
                                    <span class="text-sm text-muted">Belum dinilai</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
```

Di `app/Livewire/Trails/RouteComparison.php`, di `render()` sebelum `return view(...)`:

```php
        $ringkasanFit = auth()->user()?->hasCompletedProfile()
            ? app(\App\Services\TrailFitService::class)->forTrails(auth()->user(), $trails)
            : [];
```

Tambahkan `'ringkasanFit' => $ringkasanFit,` ke array `view(...)`.

- [ ] **Langkah 7: Jalankan, Pint, build, commit (DITAHAN)**

```bash
php artisan test
./vendor/bin/pint
npm run build
git add app resources tests/Feature/ComparisonAsCostTest.php
git commit -m "feat: bandingkan dari halaman jelajah, dibingkai ongkos versus kemampuan"
```

---

## Tugas 6: Tangga kemajuan

**Berkas:**
- Buat: `app/Services/ProgressLadderService.php`
- Ubah: `resources/views/components/ui/fit-line.blade.php`
- Test: `tests/Feature/ProgressLadderTest.php` (baru)

**Mengonsumsi:** `App\Models\HikingHistory`.

**Menghasilkan:**

```php
App\Services\ProgressLadderService::bandingkanDenganRiwayat(User $user, Trail $trail): ?string
// mis. "Satu tingkat di atas pendakian tertinggi Anda", atau null bila riwayatnya kosong
```

- [ ] **Langkah 1: Tulis test yang gagal**

Buat `tests/Feature/ProgressLadderTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\CompletionState;
use App\Models\HikingHistory;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\ProgressLadderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tangga kemajuan.
 *
 * Mekanik Strava adalah perbandingan yang dipersempit ke kelompok acuan yang cukup kecil
 * sehingga menang terasa mungkin. Modifikasinya di sini, dan ini modifikasi terpenting di
 * seluruh rancangan: acuannya diri sendiri, bukan pendaki lain.
 *
 * Papan peringkat tetap ditolak. Memberi hadiah pada jumlah menyerang model kepercayaan
 * data (§60) yang menjadi nilai produk ini; meniru Strava sampai ke papan peringkatnya
 * berarti ikut meniru kerusakannya.
 */
class ProgressLadderTest extends TestCase
{
    use RefreshDatabase;

    private function jalur(int $elevasi): Trail
    {
        return Trail::factory()
            ->for(Mountain::factory()->create(['elevation_mdpl' => $elevasi]))
            ->create(['is_published' => true]);
    }

    /**
     * Riwayat pendakian tidak menyimpan ketinggian sebagai kolom. Ia menyimpan jalurnya,
     * dan ketinggian tinggal di gunung milik jalur itu. Pembantu ini membuat ketiga
     * hubungan itu terlihat di test, bukan tersembunyi di balik factory.
     */
    private function pernahMendaki(User $user, int $elevasi, CompletionState $keadaan = CompletionState::COMPLETED): void
    {
        HikingHistory::factory()->for($user)->create([
            'trail_id' => $this->jalur($elevasi)->id,
            'completion_state' => $keadaan->value,
            'completed_at' => now()->subMonth(),
        ]);
    }

    /**
     * Riwayat kosong tidak menghasilkan kalimat karangan.
     *
     * Pendaki yang belum punya riwayat tidak punya acuan, dan mengarang acuan untuknya
     * menghasilkan kalimat yang terlihat pasti dan berdasar ketiadaan (§91).
     */
    public function test_an_empty_history_produces_no_sentence_at_all(): void
    {
        $this->assertNull(
            app(ProgressLadderService::class)->bandingkanDenganRiwayat(
                User::factory()->create(),
                $this->jalur(3000),
            )
        );
    }

    public function test_a_higher_mountain_reads_as_a_step_up(): void
    {
        $user = User::factory()->create();
        $this->pernahMendaki($user, 2000);

        $kalimat = app(ProgressLadderService::class)->bandingkanDenganRiwayat($user, $this->jalur(3000));

        $this->assertNotNull($kalimat);
        $this->assertStringContainsString('di atas', $kalimat);
    }

    public function test_a_comparable_mountain_reads_as_familiar_ground(): void
    {
        $user = User::factory()->create();
        $this->pernahMendaki($user, 3000);

        $kalimat = app(ProgressLadderService::class)->bandingkanDenganRiwayat($user, $this->jalur(3050));

        $this->assertStringContainsString('setara', $kalimat);
    }

    /**
     * Pita, bukan angka (§91).
     */
    public function test_it_never_states_a_number(): void
    {
        $user = User::factory()->create();
        $this->pernahMendaki($user, 2000);

        $kalimat = app(ProgressLadderService::class)->bandingkanDenganRiwayat($user, $this->jalur(3000));

        $this->assertDoesNotMatchRegularExpression('/\d/', $kalimat);
    }

    /**
     * Pendakian yang berbalik di tengah jalan bukan acuan.
     *
     * Berbalik tidak membuktikan puncaknya tercapai, dan memakainya sebagai acuan akan
     * memberi tahu pendaki bahwa ia sudah pernah sampai ke tempat yang justru membuatnya
     * berbalik. Itu kesalahan yang paling tidak boleh dilakukan produk keselamatan.
     */
    public function test_an_abandoned_hike_is_not_a_rung_on_the_ladder(): void
    {
        $user = User::factory()->create();
        $this->pernahMendaki($user, 3000, CompletionState::ABANDONED);

        $this->assertNull(
            app(ProgressLadderService::class)->bandingkanDenganRiwayat($user, $this->jalur(3100))
        );
    }

    /**
     * Tangga menerangkan, tidak pernah menghalangi.
     *
     * Kalimat yang berbunyi seperti izin mengubah §90 dari penjelasan menjadi penjaga
     * gerbang, dan produk ini membantu keputusan, bukan memberi restu.
     */
    public function test_it_explains_and_never_forbids(): void
    {
        $user = User::factory()->create();
        $this->pernahMendaki($user, 1000);

        $kalimat = app(ProgressLadderService::class)->bandingkanDenganRiwayat($user, $this->jalur(3500));

        foreach (['tidak boleh', 'dilarang', 'jangan', 'terlalu berbahaya'] as $larangan) {
            $this->assertStringNotContainsString($larangan, mb_strtolower($kalimat));
        }
    }
}
```

- [ ] **Langkah 2: Jalankan dan pastikan gagal**

Jalankan: `php artisan test tests/Feature/ProgressLadderTest.php`
Harapkan: GAGAL, lima test. Kegagalan pertamanya bukan layanannya melainkan
`Class "Database\Factories\HikingHistoryFactory" not found`, karena riwayat pendakian
adalah satu-satunya model inti yang belum punya factory. Langkah berikutnya membuatnya.

- [ ] **Langkah 3: Buat factory riwayat pendakian**

Buat `database/factories/HikingHistoryFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\CompletionState;
use App\Enums\TripType;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\HikingHistory>
 */
class HikingHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'trail_id' => Trail::factory(),
            'trip_type' => TripType::TEKTOK->value,
            'completion_state' => CompletionState::COMPLETED->value,
            'preparation_completion_percent' => 100,
            'completed_at' => now()->subMonth(),
        ];
    }

    /**
     * Pendakian yang berbalik di tengah jalan. Dipakai untuk membuktikan bahwa tangga
     * kemajuan tidak memakainya sebagai acuan: berbalik tidak membuktikan puncaknya
     * tercapai.
     */
    public function abandoned(): static
    {
        return $this->state(fn () => ['completion_state' => CompletionState::ABANDONED->value]);
    }
}
```

Jalankan lagi: `php artisan test tests/Feature/ProgressLadderTest.php`
Harapkan: GAGAL, lima test, kini karena `Target class [App\Services\ProgressLadderService]
does not exist.`

- [ ] **Langkah 4: Bangun layanannya**

Buat `app/Services/ProgressLadderService.php`:

```php
<?php

namespace App\Services;

use App\Models\HikingHistory;
use App\Models\Trail;
use App\Models\User;

/**
 * Menempatkan sebuah jalur terhadap yang sudah didaki pembacanya.
 *
 * Mekanik Strava adalah perbandingan yang dipersempit sampai menang terasa mungkin, dan
 * modifikasi terpentingnya di sini: acuannya diri sendiri, bukan pendaki lain.
 *
 * Kalimatnya menerangkan, tidak pernah menghalangi. Yang berbunyi seperti izin mengubah
 * §90 dari penjelasan menjadi penjaga gerbang, dan produk ini membantu keputusan, bukan
 * memberi restu.
 */
class ProgressLadderService
{
    /**
     * Selisih yang masih terbaca sebagai "setara", dalam meter.
     *
     * Dipakai sebagai ambang dua arah supaya gunung yang 50 meter lebih tinggi tidak
     * diumumkan sebagai tingkat baru. Ketepatan semacam itu palsu: cuaca dan jalur
     * mengubah tuntutan jauh lebih besar daripada lima puluh meter.
     */
    private const SETARA_METER = 300;

    public function bandingkanDenganRiwayat(User $user, Trail $trail): ?string
    {
        // Riwayat tidak menyimpan ketinggian. Ia menyimpan jalurnya, dan ketinggian
        // tinggal di gunung milik jalur itu, jadi acuannya diambil lewat dua relasi.
        //
        // Hanya pendakian yang SELESAI yang menjadi acuan. Pendakian yang dibatalkan di
        // tengah jalan tidak membuktikan puncaknya tercapai, dan memakainya sebagai acuan
        // akan memberi tahu pendaki bahwa ia sudah pernah sampai ke tempat yang justru
        // membuatnya berbalik.
        $tertinggi = HikingHistory::query()
            ->where('user_id', $user->id)
            ->where('completion_state', CompletionState::COMPLETED->value)
            ->join('trails', 'trails.id', '=', 'hiking_history.trail_id')
            ->join('mountains', 'mountains.id', '=', 'trails.mountain_id')
            ->max('mountains.elevation_mdpl');

        $puncak = $trail->mountain?->elevation_mdpl;

        if ($tertinggi === null || $puncak === null) {
            return null;
        }

        $selisih = $puncak - $tertinggi;

        return match (true) {
            $selisih > self::SETARA_METER * 3 => 'Jauh di atas pendakian tertinggi Anda sejauh ini.',
            $selisih > self::SETARA_METER => 'Satu tingkat di atas pendakian tertinggi Anda.',
            $selisih < -self::SETARA_METER => 'Di bawah pendakian tertinggi Anda sejauh ini.',
            default => 'Setara dengan pendakian tertinggi yang sudah Anda selesaikan.',
        };
    }
}
```

- [ ] **Langkah 5: Tampilkan di baris kecocokan**

Di `resources/views/components/ui/fit-line.blade.php`, ubah `@props` menjadi:

```blade
@props(['summary', 'tangga' => null])
```

Lalu tepat sebelum `</div>` penutup:

```blade
    @if ($tangga)
        {{-- Acuannya diri sendiri, bukan pendaki lain. Perbandingan yang dipersempit
             sampai menang terasa mungkin adalah mekanik Strava; papan peringkatnya tidak
             ikut, karena memberi hadiah pada jumlah merusak kepercayaan data (§60). --}}
        <p class="w-full text-xs text-muted">{{ $tangga }}</p>
    @endif
```

- [ ] **Langkah 6: Jalankan, Pint, build, commit (DITAHAN)**

```bash
php artisan test
./vendor/bin/pint
npm run build
git add app resources tests/Feature/ProgressLadderTest.php
git commit -m "feat: tangga kemajuan, acuannya diri sendiri bukan pendaki lain"
```

---

## Tugas 7: Navigasi, sembilan kata benda jadi lima permukaan

**Berkas:**
- Ubah: `resources/views/livewire/layout/navigation.blade.php`
- Test: `tests/Feature/NavigationConsistencyTest.php` (tambah)

Dikerjakan terakhir. Memindahkan menu sebelum isinya berubah memindahkan orang ke halaman
yang belum berubah.

- [ ] **Langkah 1: Tulis test yang gagal**

Tambahkan ke `tests/Feature/NavigationConsistencyTest.php`:

```php
    /**
     * Menu adalah perjalanan, bukan daftar tabel.
     *
     * Diukur sebelum diubah: sembilan kata benda di menu utama, sementara §7 menetapkan
     * satu loop delapan tahap. AllTrails memakai lima tab, Strava lima, Traveloka empat,
     * dan semuanya campuran satu permukaan temuan, satu milik-saya, satu tindakan, satu
     * identitas. Sembilan kata benda adalah struktur basis data yang bocor ke menu.
     */
    public function test_the_main_menu_is_five_surfaces_not_nine_nouns(): void
    {
        $isi = \Illuminate\Support\Facades\File::get(
            resource_path('views/livewire/layout/navigation.blade.php')
        );

        // Peran, bukan tahap perjalanan: keduanya pindah ke menu profil.
        $this->assertStringNotContainsString("routeIs('admin.*')", $isi);
        $this->assertStringNotContainsString("routeIs('moderation.*')", $isi);

        foreach (['Jelajah', 'Pertimbangkan', 'Perjalanan', 'Progres', 'Kabar'] as $permukaan) {
            $this->assertStringContainsString($permukaan, $isi, "Permukaan {$permukaan} hilang dari menu.");
        }
    }
```

- [ ] **Langkah 2: Jalankan dan pastikan gagal**

Jalankan: `php artisan test tests/Feature/NavigationConsistencyTest.php --filter=five_surfaces`
Harapkan: GAGAL.

- [ ] **Langkah 3: Susun ulang menunya**

Di `resources/views/livewire/layout/navigation.blade.php`, ganti blok tautan desktop
menjadi lima permukaan berikut, dengan label persis seperti ini:

```blade
                    <x-nav-link :href="route('trails.index')" :active="request()->routeIs('trails.*') || request()->routeIs('goals.*')" wire:navigate>
                        Jelajah
                    </x-nav-link>
                    <x-nav-link :href="route('trails.compare')" :active="request()->routeIs('trails.compare')" wire:navigate>
                        Pertimbangkan
                    </x-nav-link>
                    <x-nav-link :href="route('trips.index')" :active="request()->routeIs('trips.*')" wire:navigate>
                        Perjalanan
                    </x-nav-link>
                    <x-nav-link :href="route('progress')" :active="request()->routeIs('progress') || request()->routeIs('history')" wire:navigate>
                        Progres
                    </x-nav-link>
                    <x-nav-link :href="route('news')" :active="request()->routeIs('news')" wire:navigate>
                        Kabar
                    </x-nav-link>
```

Pindahkan tautan Moderasi dan Admin ke dalam dropdown profil, di bawah tautan Profil,
dengan penjaga peran yang sama seperti sebelumnya.

- [ ] **Langkah 4: Lakukan hal yang sama pada menu responsif**

Ulangi kelima tautan di blok `x-responsive-nav-link`, dengan label dan kondisi `:active`
yang sama persis, dan pindahkan Moderasi serta Admin ke bagian profil di bawahnya.

- [ ] **Langkah 5: Jalankan, Pint, build, commit (DITAHAN)**

```bash
php artisan test
./vendor/bin/pint
npm run build
git add resources tests/Feature/NavigationConsistencyTest.php
git commit -m "feat: menu jadi lima permukaan perjalanan, bukan sembilan kata benda"
```

Harapkan: seluruh suite hijau. Bila `NavigationConsistencyTest` yang lama gagal karena
urutan atau nama tautan, periksa maksud test itu lebih dulu: dua kali sebelumnya penjaga
urutan menu memang menangkap penempatan yang keliru, jadi kegagalannya belum tentu usang.

---

## Tinjauan mandiri

**1. Cakupan spec.** §4.1 kecocokan ambient → Tugas 1, 2, 3. §4.2 pertimbangkan → Tugas 4.
§4.3 bandingkan → Tugas 5. §4.4 tangga kemajuan → Tugas 6. §4.5 navigasi → Tugas 7.
Ambang §5: A4 dijaga sapuan di Tugas 3; A3 dijaga `ComparisonAsCostTest` di Tugas 5; A9
dijaga test di Tugas 1 dan 2; A6, A7, A8 dijaga penjaga yang sudah ada dan disebut di
batasan menyeluruh. A1, A2, A5, A10 membutuhkan manusia dan tidak dapat diselesaikan
rencana ini; keduanya tercatat di spec sebagai menunggu peserta uji.

**2. Pemindaian placeholder.** Tidak ada TBD, tidak ada "tangani kasus tepi", tidak ada
"serupa Tugas N". Setiap langkah kode memuat kode yang sebenarnya.

**3. Konsistensi tipe.** `TrailFitService::forTrails(User, Collection, ?HikingGoal): array`
dideklarasikan di Tugas 1 dan dipakai dengan tanda tangan yang sama di Tugas 2, 3, dan 5.
`TrailFitSummary` memakai nama properti `label`, `eligible`, `alasan`, `denganRencana` di
Tugas 1, dan `fit-line.blade.php` membaca `$summary->label`, `$summary->alasan`, dan
`$summary->denganRencana` di Tugas 2. `ConsiderationService::toggle` dan `::forUser` serta
tetapan `::BATAS` dideklarasikan di Tugas 4 dan dipakai identik di Tugas 5.
`ProgressLadderService::bandingkanDenganRiwayat(User, Trail): ?string` dideklarasikan dan
dipakai di Tugas 6.

**4. Urutan ketergantungan.** Tugas 2 dan 3 membutuhkan Tugas 1. Tugas 5 membutuhkan Tugas
4 (baki) dan Tugas 1 (kecocokan per kolom). Tugas 6 berdiri sendiri dan dapat berjalan
paralel. Tugas 7 terakhir, dengan alasan yang ditulis di tugasnya.

**5. Skema diperiksa terhadap migrasi, bukan diingat, dan lima asumsi ternyata keliru.**

Versi pertama rencana ini ditulis di atas nama kolom yang masuk akal dan tidak ada. Semua
sudah dibetulkan di atas, dan dicatat di sini karena pelaksana yang membaca cepat akan
mengulangi tebakan yang sama:

| Ditebak | Sebenarnya |
|---|---|
| `hiking_history.highest_elevation_m` | **tidak ada kolomnya.** Ketinggian diambil lewat riwayat → jalur → gunung |
| tabel `hiking_histories` | `hiking_history`, tunggal |
| `HikingHistory::factory()` | **belum ada**, dibuat di Tugas 6 Langkah 3 |
| `user_experiences.total_hikes`, `highest_elevation_m`, `longest_duration_minutes` | `completed_hikes_count`, `highest_elevation_gain_m`, `longest_hike_duration_minutes` |
| `profiles.home_city` | tidak ada; yang menentukan `completed_at`, dan `ProfileFactory` sudah mengisinya |

Yang sudah diperiksa dan benar: `Mountain::elevation_mdpl` ada, `x-ui.alert` menerima
varian `warning`, rute `trails.compare` tidak menuntut parameter sehingga dapat dipasang
sebagai tautan menu di Tugas 7, dan `CompletionState` bernilai `COMPLETED`, `PARTIAL`,
`ABANDONED`.

**6. Satu keputusan yang lahir dari pemeriksaan itu.** Karena ketinggian diambil lewat
relasi dan bukan kolom, `completion_state` ikut terlihat, dan dengan sendirinya muncul
pertanyaan apakah pendakian yang dibatalkan boleh menjadi acuan. Jawabannya tidak, dan
alasannya ditulis di dalam layanannya beserta testnya sendiri.
