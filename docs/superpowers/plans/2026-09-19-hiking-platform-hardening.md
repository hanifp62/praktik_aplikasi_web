# Hiking Platform Hardening — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menutup 34 temuan hasil audit terhadap PRD, membalik bias data-hilang agar default-safe, dan merapikan struktur supaya tim bisa mengubah fitur dan UI dari satu tempat.

**Architecture:** Nilai domain yang selama ini tersebar sebagai magic number dipusatkan ke `config/hiking.php`; tampilan dipusatkan ke design token CSS plus komponen Blade; logika domain tetap pada service yang sudah ada dan hanya dipecah bila satu kelas memikul dua tanggung jawab (`ReadinessService` dipecah menjadi `compute()` murni dan `record()` yang menyimpan). Tidak ada perpindahan namespace besar-besaran — konvensi Laravel dipertahankan supaya anggota tim baru langsung mengenali tempatnya.

**Tech Stack:** Laravel 13.31, Livewire 3.6, PHP 8.5, PostgreSQL + PostGIS, Tailwind CSS, Vite, PHPUnit, Pint.

**Spec:** `docs/superpowers/specs/2026-09-19-hiking-platform-hardening-design.md`

## Global Constraints

- PHP 8.5, Laravel 13.31, Livewire 3.6 — jangan menambah dependensi PHP baru kecuali disebut eksplisit pada task.
- Seluruh string yang tampil ke pengguna berbahasa Indonesia.
- Test berjalan di SQLite in-memory (`phpunit.xml`); kode PostGIS wajib no-op yang aman pada koneksi non-pgsql.
- `vendor/bin/pint` harus bersih sebelum tiap commit.
- `php artisan test` harus hijau di akhir tiap task.
- Dilarang menjalankan migrasi terhadap `DB_HOST` pada `.env` — itu instance Supabase yang hidup.
- Skor numerik route fit tidak boleh pernah diserialisasi ke klien (PRD §28, BR-09).
- Sistem tidak pernah menyatakan sesuatu "aman" (PRD §40).

## Struktur file

**Dibuat:**
- `config/hiking.php` — seluruh nilai domain yang bisa dikalibrasi tim
- `resources/css/app.css` (ditulis ulang) — design token sebagai CSS custom property
- `resources/views/components/ui/button.blade.php` — satu tempat untuk gaya tombol
- `resources/views/components/ui/alert.blade.php` — pesan status/peringatan
- `resources/views/components/ui/empty-state.blade.php` — keadaan kosong
- `docs/ARCHITECTURE.md` — peta kode untuk anggota tim baru
- `docs/CONTRIBUTING.md` — cara menambah fitur dan mengubah UI
- `app/Services/RouteFit/UnknownData.php` — penanda faktor tanpa data
- `app/Support/Timezone.php` — konversi zona waktu gunung
- `app/Services/PermitService.php` — aturan jendela booking
- `app/Models/PermitRequirement.php`
- `app/Http/Controllers/ReportPhotoController.php` — penyajian foto ber-otorisasi
- `app/Support/ImageSanitizer.php` — pembersih EXIF

**Diubah:** service inti, komponen Livewire, view, migrasi, `.github/workflows/ci.yml`, `.env.example`.

---

## FASE 0 — Fondasi untuk tim

### Task 1: Pusatkan nilai domain ke config

**Files:**
- Create: `config/hiking.php`
- Modify: `app/Services/RouteFitService.php`, `app/Services/RouteFit/FactorScore.php`, `app/Services/RouteFit/CompatibilityScorer.php`, `app/Services/RecommendationExplanationService.php`
- Test: `tests/Unit/HikingConfigTest.php`

**Interfaces:**
- Produces: `config('hiking.route_fit.label_threshold_fit')`, `config('hiking.route_fit.label_threshold_prepare')`, `config('hiking.route_fit.critical_factor_floor')`, `config('hiking.route_fit.strong_factor_threshold')`, `config('hiking.route_fit.elevation_reference')`, `config('hiking.hike_mode.checkpoint_arrival_radius_m')`, `config('hiking.cache.public_ttl_seconds')`, `config('hiking.map.*')`.

- [ ] **Step 1: Tulis test yang gagal**

```php
// tests/Unit/HikingConfigTest.php
public function test_route_fit_thresholds_come_from_config(): void
{
    config()->set('hiking.route_fit.label_threshold_fit', 0.99);

    $service = app(RouteFitService::class);
    $reflection = new \ReflectionMethod($service, 'label');

    // Skor 0.90 harus turun ke PERLU_PERSIAPAN ketika ambang dinaikkan ke 0.99.
    $factors = [new FactorScore(CompatibilityFactor::EXPERIENCE_MATCH, 0.9, 0.3, 'x')];
    $this->assertSame(RouteFitLabel::PERLU_PERSIAPAN, $reflection->invoke($service, 0.90, $factors));
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=test_route_fit_thresholds_come_from_config`
Expected: FAIL — ambang masih konstanta kelas, hasilnya `COCOK`.

- [ ] **Step 3: Buat `config/hiking.php`**

```php
<?php

return [
    // Mesin Route Fit (PRD §27-29). Kalibrasi bobot ada di tabel recommendation_rules;
    // nilai di sini adalah ambang dan referensi yang tidak per-pengguna.
    'route_fit' => [
        'label_threshold_fit' => 0.75,
        'label_threshold_prepare' => 0.50,
        'critical_factor_floor' => 0.34,
        'strong_factor_threshold' => 0.75,
        'elevation_reference' => [1 => 600, 2 => 1000, 3 => 1600, 4 => 2200],
    ],

    'hike_mode' => [
        'checkpoint_arrival_radius_m' => 75,
        'earth_radius_m' => 6371000,
    ],

    'conditions' => [
        'community_recent_days' => 30,
        'community_report_limit' => 10,
        'weather_current_hours' => 12,
        'weather_aging_hours' => 24,
    ],

    'cache' => [
        'public_ttl_seconds' => 300,
    ],

    'map' => [
        'style_url' => env('MAP_STYLE_URL'),
        'raster_tiles' => [env('MAP_TILE_URL', 'https://tile.opentopomap.org/{z}/{x}/{y}.png')],
        'attribution' => env('MAP_ATTRIBUTION', '© OpenTopoMap (CC-BY-SA) © OpenStreetMap contributors'),
        'max_zoom' => 17,
    ],

    'uploads' => [
        'report_photo_max_kb' => 4096,
        'report_photo_max_dimension' => 2000,
    ],
];
```

- [ ] **Step 4: Ganti konstanta menjadi pembacaan config**

Di `RouteFitService`: hapus `LABEL_THRESHOLD_FIT`, `LABEL_THRESHOLD_PREPARE`, ganti `0.34` — semuanya baca `config('hiking.route_fit.*')`. Di `FactorScore::isWeak()`/`isStrong()` dan enam titik `0.75` pada `CompatibilityScorer` serta satu di `RecommendationExplanationService`, baca `config('hiking.route_fit.strong_factor_threshold')`. Pindahkan `ELEVATION_REFERENCE` ke config.

- [ ] **Step 5: Jalankan seluruh suite**

Run: `php artisan test`
Expected: 87 lolos.

- [ ] **Step 6: Pint dan commit**

```bash
vendor/bin/pint && git add -A && git commit -m "refactor: pusatkan nilai domain ke config/hiking.php"
```

### Task 2: Design token dan komponen UI

**Files:**
- Modify: `resources/css/app.css`, `tailwind.config.js`
- Create: `resources/views/components/ui/button.blade.php`, `resources/views/components/ui/alert.blade.php`, `resources/views/components/ui/empty-state.blade.php`
- Test: `tests/Feature/DesignSystemTest.php`

**Interfaces:**
- Produces: `<x-ui.button variant="primary|secondary|danger" :href="..." type="submit">`, `<x-ui.alert variant="info|warning|danger">`, `<x-ui.empty-state title="..." description="...">`.

- [ ] **Step 1: Tulis test yang gagal**

```php
// tests/Feature/DesignSystemTest.php
public function test_brand_colour_is_not_hardcoded_in_views(): void
{
    $hits = [];
    foreach (\Illuminate\Support\Facades\File::allFiles(resource_path('views')) as $file) {
        if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'ui')) {
            continue; // komponen ui adalah satu-satunya tempat warna merek boleh disebut
        }
        if (preg_match('/emerald-\d{3}/', $file->getContents())) {
            $hits[] = $file->getRelativePathname();
        }
    }

    $this->assertSame([], $hits, 'Warna merek harus lewat komponen ui, bukan ditulis di view.');
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=test_brand_colour_is_not_hardcoded_in_views`
Expected: FAIL — mendaftar belasan view.

- [ ] **Step 3: Tulis token di `resources/css/app.css`**

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Design token. Ubah warna merek, radius, dan spasi aplikasi dari sini saja. */
@layer base {
    :root {
        --brand-50: 236 253 245;
        --brand-100: 209 250 229;
        --brand-600: 5 150 105;
        --brand-700: 4 120 87;
        --warn-100: 254 243 199;
        --warn-900: 120 53 15;
        --danger-100: 255 228 230;
        --danger-900: 136 19 55;
        --radius-control: 0.375rem;
    }
}
```

Di `tailwind.config.js`, daftarkan `brand`, `warn`, `danger` sebagai warna yang membaca custom property tersebut lewat `rgb(var(--brand-600) / <alpha-value>)`.

- [ ] **Step 4: Buat komponen tombol**

```blade
{{-- resources/views/components/ui/button.blade.php --}}
@props(['variant' => 'primary', 'href' => null])

@php
    $base = 'inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium min-h-11 '
        .'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50';
    $styles = match ($variant) {
        'secondary' => 'border border-gray-300 bg-white text-gray-800 hover:bg-gray-50 focus-visible:ring-gray-500',
        'danger' => 'bg-danger-600 text-white hover:bg-danger-700 focus-visible:ring-danger-500',
        default => 'bg-brand-600 text-white hover:bg-brand-700 focus-visible:ring-brand-600',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" wire:navigate {{ $attributes->merge(['class' => $base.' '.$styles]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $base.' '.$styles]) }}>{{ $slot }}</button>
@endif
```

`min-h-11` memenuhi ukuran target sentuh WCAG 2.2 (PRD §87); `focus-visible:ring` memenuhi focus visibility.

- [ ] **Step 5: Buat `alert.blade.php` dan `empty-state.blade.php`, lalu ganti seluruh pemakaian di view**

Sapu seluruh `resources/views`, ganti tombol manual menjadi `<x-ui.button>`, blok pesan menjadi `<x-ui.alert>`, dan blok "belum ada data" menjadi `<x-ui.empty-state>`.

- [ ] **Step 6: Jalankan test dan build aset**

Run: `php artisan test --filter=DesignSystemTest` lalu `npm run build`
Expected: PASS, build sukses.

- [ ] **Step 7: Pint dan commit**

```bash
vendor/bin/pint && git add -A && git commit -m "refactor: design token dan komponen ui terpusat"
```

### Task 3: Dokumentasi arsitektur untuk tim

**Files:**
- Create: `docs/ARCHITECTURE.md`, `docs/CONTRIBUTING.md`
- Modify: `CLAUDE.md`

- [ ] **Step 1: Tulis `docs/ARCHITECTURE.md`**

Isi wajib: diagram alur `Profile → Goal → RouteFit → Trip → Preparation → Readiness → Hike → Report → History`; tabel "mau ubah apa, sentuh file mana" (ubah ambang label → `config/hiking.php`; ubah bobot faktor → tabel `recommendation_rules` lewat `/admin`; ubah warna/tombol → `resources/views/components/ui`; tambah faktor kompatibilitas → `App\Enums\CompatibilityFactor` + `CompatibilityScorer`); daftar service dan tanggung jawab tunggalnya; aturan otoritas data PRD §43.

- [ ] **Step 2: Tulis `docs/CONTRIBUTING.md`**

Isi wajib: alur TDD yang dipakai proyek; perintah `php artisan test`, `vendor/bin/pint`; aturan bahwa nilai domain baru masuk `config/hiking.php`, bukan konstanta kelas; aturan bahwa view tidak menyebut warna merek langsung; cara menjalankan suite PostGIS.

- [ ] **Step 3: Commit**

```bash
git add docs/ CLAUDE.md && git commit -m "docs: peta arsitektur dan panduan kontribusi"
```

---

## FASE 1 — Semantik keselamatan

### Task 4: Faktor bernilai unknown (F-01)

**Files:**
- Modify: `app/Services/RouteFit/FactorScore.php`, `app/Services/RouteFit/CompatibilityScorer.php`, `app/Services/RouteFitService.php`
- Test: `tests/Unit/RouteFitServiceTest.php`

**Interfaces:**
- Produces: `FactorScore::$isUnknown` (bool, default false), konstruktor menerima argumen bernama `isUnknown:`; `RouteFitResult::hasUnknownFactors(): bool`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_a_trail_without_characteristics_is_never_labelled_cocok(): void
{
    $user = $this->beginnerWithProfile();
    $trail = Trail::factory()->create([
        'distance_km' => null, 'elevation_gain_m' => null,
        'estimated_duration_minutes' => null, 'terrain_character' => null,
    ]);

    $result = app(RouteFitService::class)->evaluate($user, null, $trail);

    $this->assertNotSame(RouteFitLabel::COCOK, $result->label);
    $this->assertTrue($result->hasUnknownFactors());
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=test_a_trail_without_characteristics_is_never_labelled_cocok`
Expected: FAIL — label sekarang `COCOK`.

- [ ] **Step 3: Tambah penanda unknown pada `FactorScore` dan `RouteFitResult`**

Pada `FactorScore`, tambah `public readonly bool $isUnknown = false` sebagai parameter konstruktor terakhir agar pemanggil lama tetap jalan.

Pada `RouteFitResult`, tambah:

```php
public function hasUnknownFactors(): bool
{
    foreach ($this->factors as $factor) {
        if ($factor->isUnknown) {
            return true;
        }
    }

    return false;
}
```

`factorsToArray()` ikut menyertakan `is_unknown` supaya jejak audit pada `recommendation_results.matched_factors` merekam data mana yang belum ada saat run itu dijalankan (PRD §31).

- [ ] **Step 4: Tandai faktor tanpa data di `CompatibilityScorer`**

`physicalDemandRank()` diganti `physicalDemand(): array{rank:int, known:bool}` — `known` bernilai false bila `distance_km`, `elevation_gain_m`, dan `estimated_duration_minutes` seluruhnya null. `experienceMatch()` meneruskan `isUnknown: ! $known` dan detailnya berbunyi "Karakteristik fisik jalur ini belum tersedia, sehingga kecocokan belum dapat dinilai."

Titik lain yang sudah memakai skor netral `0.6` — `durationMatch`, `elevationGainMatch`, `tripPreference` — ikut diberi `isUnknown: true`. `terrainMatch` ditandai unknown bila `terrain_character` null.

- [ ] **Step 5: Turunkan label bila ada unknown**

Di `RouteFitService::label()`, sebelum pencocokan ambang:

```php
$unknownCritical = array_filter(
    $factors,
    fn (FactorScore $f) => $f->isUnknown && in_array($f->factor, self::CRITICAL_FACTORS, true)
);

if ($unknownCritical !== []) {
    return RouteFitLabel::KURANG_COCOK;
}

$hasUnknown = array_filter($factors, fn (FactorScore $f) => $f->isUnknown) !== [];
```

lalu setelah label dihitung, bila `$hasUnknown` dan label `COCOK`, turunkan ke `PERLU_PERSIAPAN`.

- [ ] **Step 6: Tambahkan data yang hilang ke penjelasan**

Di `RecommendationExplanationService`, faktor unknown masuk ke bagian "What to Watch" dengan kalimat yang menyebut data mana yang belum ada — bukan disembunyikan (PRD §30, §91).

- [ ] **Step 7: Jalankan suite**

Run: `php artisan test`
Expected: hijau. Test lama `test_beginner_on_easy_route_is_labelled_cocok` memakai factory berdata lengkap sehingga tetap lolos; bila gagal, periksa factory-nya mengisi ketiga kolom fisik.

- [ ] **Step 8: Pint dan commit**

```bash
vendor/bin/pint && git add -A && git commit -m "fix: data jalur yang hilang menurunkan label, bukan menaikkannya (F-01)"
```

### Task 5: Gerbang publikasi §110 (F-02)

**Files:**
- Modify: `app/Models/Trail.php`, `app/Livewire/Admin/TrailManager.php`
- Test: `tests/Feature/TrailPublishGateTest.php`

**Interfaces:**
- Produces: `Trail::publishabilityReport(): array<int, string>` — daftar syarat yang belum terpenuhi; kosong berarti boleh dipublikasi.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_a_trail_without_required_data_cannot_be_published(): void
{
    $trail = Trail::factory()->create(['distance_km' => null, 'data_source_id' => null, 'is_published' => false]);

    $this->assertNotEmpty($trail->publishabilityReport());

    $this->actingAs($this->admin());
    Livewire::test(TrailManager::class)
        ->call('edit', $trail->id)
        ->set('is_published', true)
        ->call('save')
        ->assertHasErrors('is_published');

    $this->assertFalse($trail->fresh()->is_published);
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=test_a_trail_without_required_data_cannot_be_published`
Expected: FAIL — method belum ada.

- [ ] **Step 3: Implementasi `publishabilityReport()`**

```php
/**
 * PRD §110: syarat minimum sebelum jalur boleh dipublikasikan.
 *
 * @return array<int, string> alasan yang belum terpenuhi; kosong berarti lolos
 */
public function publishabilityReport(): array
{
    $missing = [];

    if ($this->data_source_id === null) {
        $missing[] = 'Sumber data belum ditetapkan.';
    }

    if ($this->distance_km === null || $this->elevation_gain_m === null || $this->estimated_duration_minutes === null) {
        $missing[] = 'Karakteristik dasar (jarak, elevation gain, estimasi durasi) belum lengkap.';
    }

    if ($this->checkpoints()->count() === 0) {
        $missing[] = 'Jalur belum memiliki checkpoint.';
    }

    if (static::spatialSupported() && $this->readGeoJson('geometry') === null) {
        $missing[] = 'Geometri jalur belum tersedia.';
    }

    return $missing;
}
```

- [ ] **Step 4: Tegakkan di `TrailManager::save()`**

Sebelum menyimpan, bila `$this->is_published` true dan report tidak kosong, panggil `addError('is_published', 'Jalur belum dapat dipublikasikan: '.implode(' ', $report))` lalu `return`.

- [ ] **Step 5: Tampilkan daftar kekurangan di view admin**

`trail-manager.blade.php` menampilkan `publishabilityReport()` tiap baris sebagai daftar, sehingga kurator tahu persis apa yang harus dilengkapi.

- [ ] **Step 6: Jalankan suite, Pint, commit**

```bash
php artisan test && vendor/bin/pint && git add -A && git commit -m "feat: gerbang publikasi kualitas data jalur (F-02)"
```

### Task 6: Status segmen dan restricted area masuk mesin (F-03, F-04)

**Files:**
- Modify: `app/Services/OfficialStatusService.php`, `app/Services/RouteFitService.php`, `app/Services/ConditionAggregatorService.php`, `app/Models/Trail.php`
- Test: `tests/Unit/SegmentRestrictionTest.php`

**Interfaces:**
- Produces: `OfficialStatusService::segmentRestrictionsForTrail(Trail $trail): array<int, array{segment: string, status: OfficialStatusValue, reason: ?string}>`; `Trail::intersectingRestrictedAreas(): Collection`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_a_closed_segment_warns_and_caps_the_label(): void
{
    $trail = Trail::factory()->create();                       // data lengkap, status trail OPEN
    $segment = TrailSegment::factory()->for($trail)->create(['name' => 'Kalimati - Puncak']);
    OfficialStatus::factory()->create([
        'statusable_type' => TrailSegment::class,
        'statusable_id' => $segment->id,
        'scope' => StatusScope::SEGMENT->value,
        'status' => OfficialStatusValue::CLOSED->value,
        'reason' => 'Aktivitas vulkanik',
    ]);

    $result = app(RouteFitService::class)->evaluate($this->advancedHiker(), null, $trail);

    $this->assertTrue($result->eligible, 'Segmen tertutup membatasi, bukan mengeksklusi (PRD §26).');
    $this->assertNotSame(RouteFitLabel::COCOK, $result->label);
    $this->assertStringContainsString('Kalimati - Puncak', implode(' ', $result->warnings));
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=test_a_closed_segment_warns_and_caps_the_label`
Expected: FAIL — tidak ada peringatan segmen.

- [ ] **Step 3: Implementasi `segmentRestrictionsForTrail()`**

Satu query: ambil id seluruh segmen jalur, lalu `OfficialStatus::whereIn('statusable_id', $ids)->where('statusable_type', TrailSegment::class)->currentlyEffective()` diurutkan, dikelompokkan per segmen, ambil yang terbaru per segmen, saring yang `CLOSED` atau `RESTRICTED`.

- [ ] **Step 4: Sambungkan ke `RouteFitService`**

`warningsFor()` menerima daftar pembatasan segmen dan menghasilkan kalimat per segmen: `sprintf('Segmen %s berstatus %s. %s', $nama, $label, $alasan)`. `label()` membatasi hasil maksimal `PERLU_PERSIAPAN` bila daftar tidak kosong.

- [ ] **Step 5: Implementasi `Trail::intersectingRestrictedAreas()`**

```php
public function intersectingRestrictedAreas(): Collection
{
    if (! static::spatialSupported()) {
        return collect();
    }

    return RestrictedArea::query()
        ->whereRaw('ST_Intersects(geometry, (SELECT geometry FROM trails WHERE id = ?))', [$this->getKey()])
        ->currentlyEffective()
        ->get();
}
```

- [ ] **Step 6: Tampilkan pada Trail Detail dan agregator kondisi**

`ConditionAggregatorService::warnings()` menambahkan peringatan area terbatas; `trail-detail.blade.php` mendapat bagian "Pembatasan pada jalur ini" memakai `<x-ui.alert variant="warning">`.

- [ ] **Step 7: Jalankan suite, Pint, commit**

```bash
php artisan test && vendor/bin/pint && git add -A && git commit -m "feat: status segmen dan restricted area masuk mesin route fit (F-03, F-04)"
```

---

## FASE 2 — Bug korektif

### Task 7: Kebocoran filter pencarian (F-05)

**Files:**
- Modify: `app/Livewire/Trails/TrailIndex.php:43-45`
- Test: `tests/Feature/TrailIndexTest.php`

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_search_never_exposes_unpublished_trails(): void
{
    $mountain = Mountain::factory()->create(['name' => 'Gunung Rahasia']);
    $hidden = Trail::factory()->for($mountain)->create(['name' => 'Jalur Draft', 'is_published' => false]);

    $this->actingAs(User::factory()->create());

    Livewire::test(TrailIndex::class)
        ->set('search', 'Rahasia')
        ->assertDontSee('Jalur Draft');
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=test_search_never_exposes_unpublished_trails`
Expected: FAIL — jalur draft tampil.

- [ ] **Step 3: Kurung kondisi pencarian**

```php
->when($this->search, fn ($query, $search) => $query->where(
    fn ($group) => $group->where('name', 'like', "%{$search}%")
        ->orWhereHas('mountain', fn ($q) => $q->where('name', 'like', "%{$search}%"))
))
```

- [ ] **Step 4: Jalankan test, Pint, commit**

```bash
php artisan test --filter=TrailIndexTest && vendor/bin/pint && git add -A && git commit -m "fix: pencarian jalur tidak lagi menembus filter published (F-05)"
```

### Task 8: Zona waktu cuaca (F-06)

**Files:**
- Create: `database/migrations/2026_09_20_000100_add_forecast_at_to_weather_snapshots.php`, `database/migrations/2026_09_20_000110_add_timezone_to_mountains.php`, `app/Support/Timezone.php`
- Modify: `app/Services/WeatherService.php`, `app/Models/WeatherSnapshot.php`, `app/Models/Mountain.php`
- Test: `tests/Unit/WeatherServiceTest.php`

**Interfaces:**
- Produces: kolom `weather_snapshots.forecast_at` (UTC), `mountains.timezone` (string); `Mountain::$timezone`; `Timezone::forTrail(Trail $trail): string`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_forecast_instants_are_stored_in_utc(): void
{
    Http::fake([
        '*' => Http::response(['data' => [['cuaca' => [[[
            'utc_datetime' => '2026-09-19 15:00:00',
            'local_datetime' => '2026-09-19 22:00:00',
            't' => 27, 'hu' => 88, 'ws' => 3.6, 'wd' => 'S', 'tcc' => 20,
            'weather_desc' => 'Cerah', 'analysis_date' => '2026-09-19T12:00:00',
        ]]]]]], 200),
    ]);

    $trail = Trail::factory()->create(['weather_adm4_code' => '31.71.03.1001']);
    app(WeatherService::class)->refreshForTrail($trail);

    $snapshot = WeatherSnapshot::first();
    $this->assertSame('2026-09-19 15:00:00', $snapshot->forecast_at->utc()->format('Y-m-d H:i:s'));
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=test_forecast_instants_are_stored_in_utc`
Expected: FAIL — kolom belum ada.

- [ ] **Step 3: Migrasi**

Tambah `forecast_at` (timestamp, index) pada `weather_snapshots`; backfill `forecast_at = local_datetime - interval '7 hours'` untuk baris lama; ganti unique menjadi `(adm4_code, forecast_at)`. Tambah `timezone` (string, default `Asia/Jakarta`) pada `mountains`.

- [ ] **Step 4: Ubah `WeatherService::normalize()`**

Ambil `forecast_at` dari `Carbon::parse($entry['utc_datetime'], 'UTC')`; `local_datetime` tetap disimpan apa adanya sebagai nilai tampilan asal BMKG. `updateOrCreate` berkunci `(adm4_code, forecast_at)`. `forecastForTrail()` dan `freshness()` memakai `forecast_at`.

- [ ] **Step 5: Buat `app/Support/Timezone.php`**

```php
class Timezone
{
    public const SUPPORTED = ['Asia/Jakarta' => 'WIB', 'Asia/Makassar' => 'WITA', 'Asia/Jayapura' => 'WIT'];

    public static function forTrail(Trail $trail): string
    {
        return $trail->mountain?->timezone ?? 'Asia/Jakarta';
    }

    public static function label(string $timezone): string
    {
        return self::SUPPORTED[$timezone] ?? $timezone;
    }
}
```

- [ ] **Step 6: Tampilkan waktu dalam zona gunung**

Setiap tampilan waktu cuaca memakai `->setTimezone(Timezone::forTrail($trail))` dan diberi sufiks `Timezone::label(...)`, misalnya `19 Sep 2026 22:00 WIB`. Atribusi BMKG wajib tampil di dekatnya.

- [ ] **Step 7: Jalankan suite, Pint, commit**

```bash
php artisan test && vendor/bin/pint && git add -A && git commit -m "fix: prakiraan cuaca disimpan UTC dan ditampilkan per zona gunung (F-06)"
```

### Task 9: Readiness idempoten dan konfirmasi yang bertahan (F-07, F-08)

**Files:**
- Modify: `app/Services/ReadinessService.php`, `app/Livewire/Trips/ReadinessDashboard.php`, `resources/views/livewire/trips/readiness-dashboard.blade.php`
- Test: `tests/Feature/ReadinessDashboardTest.php`

**Interfaces:**
- Produces: `ReadinessService::compute(TripPlan $trip): ReadinessAssessment` (objek nilai, tanpa efek samping); `ReadinessService::record(TripPlan $trip, ReadinessAssessment $assessment): ReadinessCheck`; `ReadinessService::evaluate()` tetap ada sebagai `record(compute())` untuk pemanggil lama.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_viewing_readiness_does_not_write_rows(): void
{
    $trip = $this->tripForOwner();
    $this->actingAs($trip->user);

    $this->get(route('trips.readiness', $trip))->assertOk();
    $this->get(route('trips.readiness', $trip))->assertOk();
    $this->get(route('trips.readiness', $trip))->assertOk();

    $this->assertSame(0, ReadinessCheck::count());
}

public function test_pre_departure_confirmation_survives_a_reload(): void
{
    $trip = $this->readyTripForOwner();
    $this->actingAs($trip->user);

    Livewire::test(ReadinessDashboard::class, ['trip' => $trip])->call('confirmPreDeparture');

    Livewire::test(ReadinessDashboard::class, ['trip' => $trip])
        ->assertSet('preDepartureConfirmed', true);
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=ReadinessDashboardTest`
Expected: FAIL — 3 baris tertulis; konfirmasi hilang.

- [ ] **Step 3: Pecah `ReadinessService`**

Buat `App\Services\Readiness\ReadinessAssessment` (readonly: `state`, `routeFitSnapshot`, `preparationState`, `conditions`, `explanation`). `compute()` mengembalikan objek ini tanpa menyentuh database tulis. `record()` yang membuat baris `ReadinessCheck`.

- [ ] **Step 4: Ubah `ReadinessDashboard`**

`mount()` memanggil `compute()` dan membaca `ReadinessCheck::where('trip_plan_id', ...)->latest('id')->first()` untuk `pre_departure_confirmed`, lalu mengisi properti publik `preDepartureConfirmed`. `recompute()` memanggil `record()`. `confirmPreDeparture()` memanggil `record()` lebih dulu bila belum ada baris, menandainya, lalu men-set `preDepartureConfirmed = true`.

- [ ] **Step 5: Tampilkan statusnya di view**

Tambahkan penanda "Pre-departure check sudah dikonfirmasi pada ..." memakai `<x-ui.alert variant="info">` ketika `preDepartureConfirmed` true, supaya pengguna melihat konfirmasinya.

- [ ] **Step 6: Jalankan suite, Pint, commit**

```bash
php artisan test && vendor/bin/pint && git add -A && git commit -m "fix: halaman readiness idempoten dan konfirmasi pre-departure bertahan (F-07, F-08)"
```

### Task 10: Checkpoint berikutnya di Hike Mode (F-09, F-17)

**Files:**
- Modify: `app/Livewire/Trips/HikeMode.php`, `database/migrations/2026_09_20_000120_add_reached_sequence_to_hiking_sessions.php`
- Test: `tests/Unit/HikeModeCheckpointTest.php`

**Interfaces:**
- Produces: kolom `hiking_sessions.reached_checkpoint_sequence` (unsigned int, nullable).

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_next_checkpoint_is_the_one_being_approached(): void
{
    // Tiga pos berurutan; pendaki 100 m sebelum Pos 3.
    $component = Livewire::test(HikeMode::class, ['trip' => $trip])
        ->call('updatePosition', $justBeforePos3Lat, $justBeforePos3Lng);

    $component->assertSet('nextCheckpoint.sequence', 3);
}

public function test_position_outside_valid_range_is_rejected(): void
{
    Livewire::test(HikeMode::class, ['trip' => $trip])
        ->call('updatePosition', 999.0, 999.0)
        ->assertHasErrors();
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=HikeModeCheckpointTest`
Expected: FAIL — mengembalikan sequence 4; posisi tak valid diterima.

- [ ] **Step 3: Validasi rentang posisi**

Di awal `updatePosition()`:

```php
$this->validate(
    ['latitude' => ['required', 'numeric', 'between:-90,90'], 'longitude' => ['required', 'numeric', 'between:-180,180']],
    [],
    [],
    ['latitude' => $latitude, 'longitude' => $longitude]
);
```

Gunakan `Validator::make(compact('latitude', 'longitude'), $rules)->validate()` bila bentuk di atas tidak cocok dengan versi Livewire.

- [ ] **Step 4: Ganti logika pemilihan checkpoint**

```php
$radius = (int) config('hiking.hike_mode.checkpoint_arrival_radius_m');
$session = $this->trip->hikingSession;
$reached = $session?->reached_checkpoint_sequence ?? 0;

foreach ($checkpoints as $checkpoint) {
    if ($checkpoint['lat'] === null) {
        continue;
    }

    $distance = $this->haversineMeters($this->latitude, $this->longitude, $checkpoint['lat'], $checkpoint['lng']);

    if ($distance <= $radius && $checkpoint['sequence'] > $reached) {
        $reached = $checkpoint['sequence'];
    }
}

$session?->update(['reached_checkpoint_sequence' => $reached]);

// Berikutnya = pos pertama dalam urutan yang belum tercapai.
$next = collect($checkpoints)->firstWhere(fn ($c) => $c['sequence'] > $reached)
    ?? collect($checkpoints)->last();
```

- [ ] **Step 5: Jalankan suite, Pint, commit**

```bash
php artisan test && vendor/bin/pint && git add -A && git commit -m "fix: checkpoint berikutnya memakai radius kedatangan dan urutan (F-09, F-17)"
```

### Task 11: Judul halaman (F-10)

**Files:**
- Modify: `resources/views/layouts/app.blade.php:8`, `resources/views/layouts/guest.blade.php:8`, `.env`, `.env.example`, `routes/web.php`
- Test: `tests/Feature/PageTitleTest.php`

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_each_page_has_its_own_title(): void
{
    $this->actingAs(User::factory()->create())
        ->get(route('trails.index'))
        ->assertSee('<title>Jelajahi Jalur</title>', false);
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=test_each_page_has_its_own_title`
Expected: FAIL — judulnya `Laravel`.

- [ ] **Step 3: Alirkan `$title`**

Kedua layout: `<title>{{ $title ?? config('app.name') }}</title>`. Set `APP_NAME` pada `.env` dan `.env.example` ke nama aplikasi sebenarnya. Halaman `Route::view` diberi judul lewat argumen data ketiga: `Route::view('dashboard', 'dashboard', ['title' => 'Dasbor'])`.

- [ ] **Step 4: Jalankan suite, Pint, commit**

```bash
php artisan test && vendor/bin/pint && git add -A && git commit -m "fix: judul halaman per komponen kini benar-benar dipakai (F-10)"
```

### Task 12: Validasi jalur dan penjaga transisi trip (F-11, F-12, F-13)

**Files:**
- Modify: `app/Livewire/Trips/TripForm.php:64-72`, `app/Livewire/Trips/TripShow.php`, `app/Enums/TripStatus.php`
- Test: `tests/Feature/TripLifecycleTest.php`

**Interfaces:**
- Produces: `TripStatus::canTransitionTo(TripStatus $target): bool`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_a_trip_cannot_be_created_on_an_unpublished_trail(): void
{
    $hidden = Trail::factory()->create(['is_published' => false]);

    Livewire::actingAs($this->hiker())->test(TripForm::class)
        ->set('trail_id', $hidden->id)
        ->set('name', 'Coba')
        ->set('planned_date', now()->addDay()->toDateString())
        ->set('trip_type', TripType::TEKTOK->value)
        ->call('save')
        ->assertHasErrors('trail_id');
}

public function test_a_cancelled_trip_cannot_be_started(): void
{
    $trip = $this->tripForOwner(['status' => TripStatus::CANCELLED->value]);

    Livewire::actingAs($trip->user)->test(TripShow::class, ['trip' => $trip])->call('startHike');

    $this->assertSame(TripStatus::CANCELLED, $trip->fresh()->status);
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=TripLifecycleTest`
Expected: FAIL pada keduanya.

- [ ] **Step 3: Perketat validasi jalur**

```php
'trail_id' => ['required', Rule::exists('trails', 'id')->where(
    fn ($q) => $q->where('is_published', true)->whereNull('archived_at')
)],
```

- [ ] **Step 4: Tambah `canTransitionTo()` pada `TripStatus`**

```php
public function canTransitionTo(self $target): bool
{
    return in_array($target, match ($this) {
        self::DRAFT => [self::PLANNED, self::CANCELLED],
        self::PLANNED => [self::READY_FOR_DEPARTURE, self::IN_PROGRESS, self::CANCELLED],
        self::READY_FOR_DEPARTURE => [self::IN_PROGRESS, self::PLANNED, self::CANCELLED],
        self::IN_PROGRESS => [self::COMPLETED, self::CANCELLED],
        self::COMPLETED, self::CANCELLED => [],
    }, true);
}
```

- [ ] **Step 5: Tegakkan di `TripShow` dan `ReadinessDashboard`**

`startHike()`, `complete()`, `cancel()`, dan `confirmPreDeparture()` memeriksa `canTransitionTo()` lebih dulu; bila tidak sah, `session()->flash('status', 'Status trip tidak memungkinkan tindakan ini.')` lalu `return`.

- [ ] **Step 6: Validasi `completion_state` dengan enum**

`'completion_state' => ['required', new Enum(CompletionState::class)]`.

- [ ] **Step 7: Jalankan suite, Pint, commit**

```bash
php artisan test && vendor/bin/pint && git add -A && git commit -m "fix: validasi jalur terpublikasi dan penjaga transisi status trip (F-11, F-12, F-13)"
```

---

## FASE 3 — Keamanan dan privasi

### Task 13: Foto privat, tersaji ber-otorisasi, dan terlihat moderator (F-14, F-15)

**Files:**
- Create: `app/Http/Controllers/ReportPhotoController.php`
- Modify: `config/filesystems.php:89`, `routes/web.php`, `app/Policies/TrailConditionReportPolicy.php`, `resources/views/livewire/moderation/moderation-queue.blade.php`, `resources/views/livewire/trails/trail-detail.blade.php`
- Test: `tests/Feature/ReportPhotoAccessTest.php`

**Interfaces:**
- Produces: route bernama `reports.photo` (`GET /reports/{report}/photo`); kemampuan policy `viewPhoto`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_a_pending_report_photo_is_not_public(): void
{
    Storage::fake('local');
    $report = TrailConditionReport::factory()->create([
        'moderation_status' => ModerationStatus::PENDING->value,
        'photo_path' => 'condition-reports/x.jpg',
    ]);
    Storage::disk('local')->put('condition-reports/x.jpg', 'isi');

    $this->actingAs(User::factory()->create())
        ->get(route('reports.photo', $report))
        ->assertForbidden();
}

public function test_a_moderator_can_see_the_photo_to_moderate_it(): void
{
    // ... setup sama
    $this->actingAs($this->moderator())->get(route('reports.photo', $report))->assertOk();
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=ReportPhotoAccessTest`
Expected: FAIL — route belum ada.

- [ ] **Step 3: Ganti disk default menjadi privat**

`config/filesystems.php`: `'report_photos_disk' => env('REPORT_PHOTOS_DISK', 'local')`.

- [ ] **Step 4: Tambah kemampuan policy**

```php
public function viewPhoto(User $user, TrailConditionReport $report): bool
{
    return $report->user_id === $user->id
        || $user->isModerator()
        || $report->moderation_status === ModerationStatus::APPROVED;
}
```

- [ ] **Step 5: Buat controller penyaji**

```php
public function __invoke(TrailConditionReport $report): StreamedResponse
{
    $this->authorize('viewPhoto', $report);
    abort_if(blank($report->photo_path), 404);

    $disk = Storage::disk(config('filesystems.report_photos_disk'));
    abort_unless($disk->exists($report->photo_path), 404);

    return $disk->response($report->photo_path);
}
```

Daftarkan di dalam grup `auth`: `Route::get('reports/{report}/photo', ReportPhotoController::class)->name('reports.photo')`.

- [ ] **Step 6: Tampilkan fotonya**

Antrean moderasi mengganti teks "Laporan menyertakan foto." dengan `<img src="{{ route('reports.photo', $report) }}" alt="Foto kondisi jalur dari laporan {{ $report->id }}" class="mt-2 max-h-64 rounded-md">`. Trail Detail menampilkan foto laporan yang sudah `APPROVED` dengan `alt` yang deskriptif.

- [ ] **Step 7: Jalankan suite, Pint, commit**

```bash
php artisan test && vendor/bin/pint && git add -A && git commit -m "fix: foto laporan privat, ber-otorisasi, dan dapat dimoderasi (F-14, F-15)"
```

### Task 14: Pembersihan EXIF (F-16)

**Files:**
- Create: `app/Support/ImageSanitizer.php`
- Modify: `app/Livewire/Reports/ConditionReportForm.php:71-76`
- Test: `tests/Unit/ImageSanitizerTest.php`

**Interfaces:**
- Produces: `ImageSanitizer::sanitize(string $absolutePath, int $maxDimension): void` — menulis ulang berkas tanpa metadata.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_exif_gps_is_removed_from_uploaded_photos(): void
{
    $path = $this->fixtureJpegWithGpsExif();          // helper menulis EXIF GPS palsu
    $this->assertArrayHasKey('GPSLatitude', exif_read_data($path));

    ImageSanitizer::sanitize($path, 2000);

    $exif = @exif_read_data($path);
    $this->assertTrue($exif === false || ! isset($exif['GPSLatitude']));
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=ImageSanitizerTest`
Expected: FAIL — kelas belum ada.

- [ ] **Step 3: Implementasi dengan GD**

Baca dengan `imagecreatefromstring(file_get_contents($path))`, hitung ulang dimensi bila melebihi `$maxDimension` dengan menjaga rasio, `imagescale()`, lalu tulis ulang `imagejpeg()`/`imagepng()`/`imagewebp()` sesuai tipe asli. Re-encode GD tidak membawa segmen EXIF, sehingga GPS hilang. Lepaskan sumber daya dengan `imagedestroy()`.

- [ ] **Step 4: Panggil setelah unggahan tersimpan**

Di `ConditionReportForm::save()`, setelah `store()`, panggil `ImageSanitizer::sanitize($disk->path($photoPath), config('hiking.uploads.report_photo_max_dimension'))`. Bungkus dengan try/catch yang mencatat log dan tetap meneruskan — foto rusak tidak boleh menggagalkan laporan.

- [ ] **Step 5: Jalankan suite, Pint, commit**

```bash
php artisan test && vendor/bin/pint && git add -A && git commit -m "feat: bersihkan EXIF pada foto laporan (F-16)"
```

### Task 15: Rate limit (F-18)

**Files:**
- Modify: `app/Livewire/Reports/ConditionReportForm.php`, `app/Livewire/Goals/GoalForm.php`
- Test: `tests/Feature/RateLimitTest.php`

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_report_submission_is_rate_limited(): void
{
    $user = User::factory()->create();

    for ($i = 0; $i < 6; $i++) {
        Livewire::actingAs($user)->test(ConditionReportForm::class)
            ->set('trail_id', $this->trail->id)
            ->set('hike_date', now()->subDay()->toDateString())
            ->set('tags', ['MUDDY'])
            ->call('save');
    }

    $this->assertLessThanOrEqual(5, TrailConditionReport::where('user_id', $user->id)->count());
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=RateLimitTest`
Expected: FAIL — 6 laporan tersimpan.

- [ ] **Step 3: Pasang penghitung**

Di awal `save()`:

```php
$key = 'report-submit:'.auth()->id();

if (RateLimiter::tooManyAttempts($key, 5)) {
    $this->addError('form', sprintf(
        'Terlalu banyak laporan dikirim. Coba lagi dalam %d detik.',
        RateLimiter::availableIn($key)
    ));

    return;
}

RateLimiter::hit($key, 3600);
```

Pola yang sama untuk `GoalForm::save()` dengan kunci `recommendation-run:` dan batas 20 per jam — mesin rekomendasi mahal.

- [ ] **Step 4: Jalankan suite, Pint, commit**

```bash
php artisan test && vendor/bin/pint && git add -A && git commit -m "feat: rate limit pada submit laporan dan run rekomendasi (F-18)"
```

### Task 16: Retensi saat akun dihapus (F-19)

**Files:**
- Create: `database/migrations/2026_09_20_000130_anonymise_reports_on_user_delete.php`
- Modify: `app/Models/User.php`, `app/Models/TrailConditionReport.php`
- Test: `tests/Feature/AccountDeletionRetentionTest.php`

**Interfaces:**
- Produces: `TrailConditionReport::authorLabel(): string` — nama penulis atau "Pendaki terdahulu".

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_deleting_an_account_anonymises_approved_reports(): void
{
    $user = User::factory()->create();
    $report = TrailConditionReport::factory()->for($user)->create([
        'moderation_status' => ModerationStatus::APPROVED->value,
    ]);

    $user->delete();

    $report->refresh();
    $this->assertNull($report->user_id);
    $this->assertSame('Pendaki terdahulu', $report->authorLabel());
}

public function test_deleting_an_account_removes_orphaned_photo_files(): void
{
    Storage::fake('local');
    // laporan PENDING milik user, berfoto; setelah user dihapus berkasnya harus ikut hilang
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=AccountDeletionRetentionTest`
Expected: FAIL — laporan ikut terhapus oleh cascade.

- [ ] **Step 3: Migrasi foreign key**

Drop constraint lama pada `trail_condition_reports.user_id`, jadikan kolom nullable, pasang ulang dengan `nullOnDelete()`.

- [ ] **Step 4: Implementasi `authorLabel()` dan pembersih berkas**

```php
public function authorLabel(): string
{
    return $this->user?->name ?? 'Pendaki terdahulu';
}
```

Di `TrailConditionReport::booted()`, pada event `deleting`, hapus berkas foto dari disk bila ada.

- [ ] **Step 5: Ganti seluruh pemakaian `$report->user->name` di view menjadi `$report->authorLabel()`**

- [ ] **Step 6: Jalankan suite, Pint, commit**

```bash
php artisan test && vendor/bin/pint && git add -A && git commit -m "feat: anonimisasi laporan komunitas saat akun dihapus (F-19)"
```

---

## FASE 4 — Performa

### Task 17: Hapus N+1 mesin rekomendasi (F-20, F-23)

**Files:**
- Modify: `app/Services/RouteFitService.php`, `app/Services/OfficialStatusService.php`, `app/Services/WeatherService.php`
- Create: `database/migrations/2026_09_20_000140_drop_trail_id_from_weather_snapshots.php`
- Test: `tests/Feature/RecommendationQueryBudgetTest.php`

**Interfaces:**
- Produces: `RouteFitService::evaluate(User $user, ?HikingGoal $goal, Trail $trail, ?array $weights = null, ?OfficialStatusValue $status = null)`; `OfficialStatusService::effectiveStatusesForTrails(Collection $trails): array<int, OfficialStatusValue>` berkunci `trail_id`.

- [ ] **Step 1: Tulis test anggaran query yang gagal**

```php
public function test_recommendation_stays_within_the_query_budget(): void
{
    $user = $this->hikerWithProfile();
    Trail::factory()->count(12)->create(['is_published' => true]);
    $goal = $this->goalFor($user);

    $count = 0;
    DB::listen(function () use (&$count) { $count++; });

    app(RouteFitService::class)->recommend($user, $goal);

    $this->assertLessThan(10, $count, "Anggaran query terlampaui: {$count}");
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=test_recommendation_stays_within_the_query_budget`
Expected: FAIL — sekitar 58 query.

- [ ] **Step 3: Hitung bobot sekali per run**

`recommend()` memanggil `weights()` sekali, mengopernya ke tiap `evaluate()` dan memakai nilai yang sama untuk `rules_evaluated`.

- [ ] **Step 4: Preload status resmi**

`effectiveStatusesForTrails()` mengambil seluruh status trail dalam satu query dan seluruh status mountain dalam satu query, lalu menerapkan aturan kaskade §42 di memori. `recommend()` memanggilnya sekali dan mengoper hasilnya per jalur.

- [ ] **Step 5: Bulk insert hasil**

Ganti perulangan `RecommendationResult::create()` dengan satu `RecommendationResult::insert($rows)`, menyertakan `created_at`/`updated_at` manual.

- [ ] **Step 6: Lepas `weather_snapshots.trail_id`**

Migrasi menghapus foreign key dan kolomnya; `WeatherService::normalize()` berhenti menuliskannya.

- [ ] **Step 7: Jalankan suite, Pint, commit**

```bash
php artisan test && vendor/bin/pint && git add -A && git commit -m "perf: mesin rekomendasi di bawah 10 query dan snapshot cuaca lepas dari trail (F-20, F-23)"
```

### Task 18: Cuaca sekali per agregasi, refresh per area, cache publik (F-21, F-22, F-24)

**Files:**
- Modify: `app/Services/ConditionAggregatorService.php:26-36,108-120`, `app/Console/Commands/RefreshWeatherSnapshots.php`, `app/Services/OfficialStatusService.php`
- Test: `tests/Feature/WeatherRefreshTest.php`

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_one_api_call_per_reference_area(): void
{
    Http::fake(['*' => Http::response(['data' => []], 200)]);

    Mountain::factory()->has(Trail::factory()->count(5)->state([
        'weather_adm4_code' => '35.07.17.2002', 'is_published' => true,
    ]))->create();

    $this->artisan('weather:refresh');

    Http::assertSentCount(1);
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=test_one_api_call_per_reference_area`
Expected: FAIL — 5 panggilan.

- [ ] **Step 3: Kelompokkan refresh per `adm4`**

Perintah mengambil `Trail::published()->whereNotNull('weather_adm4_code')->get()->unique('weather_adm4_code')` lalu memanggil `refreshForTrail()` sekali per area, dan melaporkan jumlah area, bukan jumlah jalur.

- [ ] **Step 4: Hitung konteks cuaca sekali**

`forTrail()` menghitung `$weather = $this->weather->contextForTrail($trail)` satu kali, memakainya untuk `weather_context`, dan mengopernya ke `warnings($trail, $reports, $weather)`.

- [ ] **Step 5: Cache data publik**

`OfficialStatusService::snapshotForTrail()` dan `WeatherService::contextForTrail()` dibungkus `Cache::remember("trail:{$trail->id}:status"...)` dengan TTL `config('hiking.cache.public_ttl_seconds')`. Tambahkan komentar bahwa hanya data publik yang boleh masuk cache bersama (PRD §97). Batalkan cache saat status resmi diubah dari admin.

- [ ] **Step 6: Jalankan suite, Pint, commit**

```bash
php artisan test && vendor/bin/pint && git add -A && git commit -m "perf: satu panggilan BMKG per area, konteks cuaca sekali hitung, cache data publik (F-21, F-22, F-24)"
```

### Task 19: Dimensi kondisi pada readiness (F-25)

**Files:**
- Modify: `app/Services/ReadinessService.php`
- Test: `tests/Unit/ReadinessServiceTest.php`

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_stale_weather_prevents_ready(): void
{
    $trip = $this->tripWithCompletePreparationAndOpenStatus();
    WeatherSnapshot::factory()->create([
        'adm4_code' => $trip->trail->weather_adm4_code,
        'fetched_at' => now()->subDays(3),          // basi
    ]);

    $assessment = app(ReadinessService::class)->compute($trip);

    $this->assertSame(ReadinessState::NEEDS_PREPARATION, $assessment->state);
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=test_stale_weather_prevents_ready`
Expected: FAIL — hasilnya `READY`.

- [ ] **Step 3: Baca dimensi ketiga di `determineState()`**

Sebelum mengembalikan `READY`, periksa: `official_status` bernilai UNKNOWN, konteks cuaca `available === false` atau `freshness === STALE`, adanya peringatan komunitas ber-tag caution, dan adanya pembatasan segmen. Bila salah satu benar, kembalikan `NEEDS_PREPARATION` dan tambahkan alasannya ke `explain()` dengan kalimat yang menyebut penyebabnya.

- [ ] **Step 4: Jalankan suite, Pint, commit**

```bash
php artisan test && vendor/bin/pint && git add -A && git commit -m "feat: kondisi terkini ikut menentukan state readiness (F-25)"
```

---

## FASE 5 — Realita lapangan

### Task 20: Peta yang berguna dan di-bundle (F-26, F-27, F-34 sebagian)

**Files:**
- Modify: `package.json`, `resources/js/app.js`, `resources/views/livewire/trips/hike-mode.blade.php`, `resources/views/livewire/trails/trail-detail.blade.php`
- Test: `tests/Feature/MapConfigTest.php`

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_hike_mode_does_not_load_maps_from_a_cdn(): void
{
    $html = $this->actingAs($this->owner)->get(route('trips.hike', $this->trip))->getContent();

    $this->assertStringNotContainsString('cdnjs.cloudflare.com', $html);
    $this->assertStringContainsString('OpenTopoMap', $html);   // atribusi wajib
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=MapConfigTest`
Expected: FAIL — masih memuat dari cdnjs, atribusi belum ada.

- [ ] **Step 3: Pasang MapLibre lewat npm**

```bash
npm install maplibre-gl@^4.7.1
```

Impor di `resources/js/app.js` dan ekspos sebagai `window.maplibregl`; impor CSS-nya di `resources/css/app.css`.

- [ ] **Step 4: Bangun style dari config**

Kirim `config('hiking.map')` ke view; bila `style_url` kosong, rakit style raster dari `raster_tiles`, `attribution`, dan `max_zoom`. Hapus kedua tag CDN.

- [ ] **Step 5: Perbaiki semantik aksesibilitas peta**

Ganti `role="img"` pada kontainer peta dengan `role="region"` plus `aria-label="Peta jalur dan checkpoint"`, dan sediakan daftar checkpoint berbentuk teks di sebelahnya sebagai alternatif non-visual.

- [ ] **Step 6: Jalankan suite, build, Pint, commit**

```bash
php artisan test && npm run build && vendor/bin/pint && git add -A && git commit -m "feat: peta topografi di-bundle dengan atribusi dan sumber tile dari config (F-26, F-27)"
```

### Task 21: Model perizinan pendakian (F-28)

**Files:**
- Create: `database/migrations/2026_09_20_000150_create_permit_requirements_table.php`, `app/Models/PermitRequirement.php`, `app/Services/PermitService.php`, `database/factories/PermitRequirementFactory.php`
- Modify: `app/Services/RouteFitService.php`, `app/Services/PreparationService.php`, `resources/views/livewire/trails/trail-detail.blade.php`, `app/Livewire/Admin/TrailManager.php`
- Test: `tests/Unit/PermitServiceTest.php`

**Interfaces:**
- Produces: `PermitService::bookingWarningFor(Trail $trail, ?CarbonInterface $targetDate): ?string`.

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_a_target_date_inside_the_closing_window_produces_a_warning(): void
{
    $trail = Trail::factory()->create();
    PermitRequirement::factory()->for($trail)->create([
        'authority' => 'TN Bromo Tengger Semeru',
        'booking_url' => 'https://bookingsemeru.bromotenggersemeru.org',
        'booking_closes_days_before' => 2,
    ]);

    $warning = app(PermitService::class)->bookingWarningFor($trail, now()->addDay());

    $this->assertStringContainsString('H-2', $warning);
    $this->assertStringContainsString('TN Bromo Tengger Semeru', $warning);
}

public function test_no_permit_data_produces_no_false_warning(): void
{
    $this->assertNull(app(PermitService::class)->bookingWarningFor(Trail::factory()->create(), now()->addDay()));
}
```

- [ ] **Step 2: Jalankan, pastikan gagal**

Run: `php artisan test --filter=PermitServiceTest`
Expected: FAIL — tabel dan service belum ada.

- [ ] **Step 3: Migrasi**

Kolom: `id`, `trail_id` nullable constrained cascade, `mountain_id` nullable constrained cascade, `authority`, `booking_url` nullable, `daily_quota` unsigned nullable, `booking_opens_days_before` unsigned nullable, `booking_closes_days_before` unsigned nullable, `guide_required` boolean default false, `max_duration_days` unsigned nullable, `notes` text nullable, `source` nullable, `source_url` nullable, `verified_at` nullable, timestamps. Index `(trail_id)` dan `(mountain_id)`.

- [ ] **Step 4: Implementasi `PermitService::bookingWarningFor()`**

Ambil aturan jalur, jatuh ke aturan gunung bila tidak ada. Bila tidak ada keduanya, kembalikan null — jangan mengarang peringatan (PRD §95 berlaku dua arah: tidak tahu bukan berarti bermasalah). Bila `booking_closes_days_before` terisi dan `targetDate` kurang dari itu dari hari ini, kembalikan kalimat yang menyebut penyelenggara, batas H-berapa, dan URL booking.

- [ ] **Step 5: Sambungkan sebagai peringatan, bukan pengecualian**

`RouteFitService::warningsFor()` menambahkan hasilnya bila ada. Jangan masukkan ke `hardConstraintFailures()` — aturan izin bisa berubah dan data kita bisa basi.

- [ ] **Step 6: Hasilkan item persiapan yang menautkan URL resmi**

`PreparationService::generateFor()` menambahkan satu item kategori `LOGISTICS` berlabel "Booking izin pendakian (SIMAKSI)" dengan `description` berisi penyelenggara dan URL, ditandai `is_critical = true` bila aturannya ada.

- [ ] **Step 7: Bagian Perizinan di Trail Detail dan CRUD admin**

Tampilkan penyelenggara, kuota harian, jendela booking, kewajiban pemandu, catatan, serta sumber dan tanggal verifikasi mengikuti PRD §60.

- [ ] **Step 8: Jalankan suite, Pint, commit**

```bash
php artisan test && vendor/bin/pint && git add -A && git commit -m "feat: model perizinan pendakian dengan peringatan jendela booking (F-28)"
```

---

## FASE 6 — Pengujian dan CI

### Task 22: E2E core journey (F-30)

**Files:**
- Create: `tests/Feature/CoreJourneyTest.php`

- [ ] **Step 1: Tulis test perjalanan penuh**

Satu test yang berjalan berurutan: register → isi profil lewat `ProfileSetup` → buat goal lewat `GoalForm` → buka `RecommendationResults` dan pastikan ada kandidat dengan label dan penjelasan → buat trip lewat `TripForm` → konfirmasi seluruh item kritis lewat `PreparationChecklist` → buka `ReadinessDashboard` dan panggil `confirmPreDeparture` → pastikan status trip menjadi `READY_FOR_DEPARTURE` dan event analitik `pre_departure_check_completed` tercatat.

- [ ] **Step 2: Jalankan sampai hijau**

Run: `php artisan test --filter=CoreJourneyTest`

- [ ] **Step 3: Commit**

```bash
vendor/bin/pint && git add -A && git commit -m "test: E2E core journey PRD §100 (F-30)"
```

### Task 23: Suite PostGIS (F-29)

**Files:**
- Create: `tests/Spatial/SpatialTestCase.php`, `tests/Spatial/TrailGeometryTest.php`
- Modify: `phpunit.xml`

- [ ] **Step 1: Tambah testsuite `Spatial` pada `phpunit.xml`**

Diarahkan ke `tests/Spatial`, tidak ikut dalam suite default.

- [ ] **Step 2: Buat `SpatialTestCase`**

Pada `setUp()`, bila `env('SPATIAL_TEST_DSN')` kosong atau koneksi gagal, panggil `$this->markTestSkipped('Postgres + PostGIS tidak tersedia.')`. Bila tersedia, arahkan koneksi ke DSN tersebut dan jalankan migrasi.

- [ ] **Step 3: Tulis test geometri**

Tulis LINESTRING ke jalur, baca kembali sebagai GeoJSON, pastikan koordinatnya cocok; pastikan `scopeNearby()` benar-benar menyaring; pastikan `intersectingRestrictedAreas()` menemukan poligon yang memotong dan mengabaikan yang tidak.

- [ ] **Step 4: Commit**

```bash
vendor/bin/pint && git add -A && git commit -m "test: suite PostGIS terpisah untuk kode spasial (F-29)"
```

### Task 24: CI, env, dan sisa scaffolding (F-31, F-32, F-33)

**Files:**
- Modify: `.github/workflows/ci.yml`, `.env.example`, `routes/web.php`
- Delete: `resources/views/awal.blade.php`

- [ ] **Step 1: Samakan versi PHP dan tambah job PostGIS**

`php-version: '8.5'`. Tambah service `postgis/postgis:16-3.4` pada job, jalankan `php artisan test --testsuite=Spatial` dengan `SPATIAL_TEST_DSN` terisi.

- [ ] **Step 2: Rapikan `.env.example`**

Isi blok DB dengan konfigurasi `pgsql` yang sebenarnya dipakai proyek, tanpa kredensial nyata. Tambah `REPORT_PHOTOS_DISK=local`, `MAP_TILE_URL`, `MAP_ATTRIBUTION`, dan `APP_NAME` yang benar.

- [ ] **Step 3: Hapus route dan view `/awal`**

- [ ] **Step 4: Jalankan suite, Pint, commit**

```bash
php artisan test && vendor/bin/pint && git add -A && git commit -m "chore: CI selaras, env example benar, scaffolding dibersihkan (F-31, F-32, F-33)"
```

### Task 25: Audit aksesibilitas alur inti (F-34)

**Files:**
- Modify: view pada alur inti — `onboarding`, `goals`, `recommendations`, `trips`, `reports`
- Test: `tests/Feature/AccessibilityTest.php`

- [ ] **Step 1: Tulis test yang gagal**

```php
public function test_every_form_control_on_the_core_flow_has_a_label(): void
{
    foreach ([route('onboarding'), route('goals.create'), route('trips.create')] as $url) {
        $html = $this->actingAs($this->hiker())->get($url)->getContent();

        preg_match_all('/<(input|select|textarea)\b[^>]*\bid="([^"]+)"/i', $html, $controls);
        foreach ($controls[2] as $id) {
            $this->assertMatchesRegularExpression(
                '/<label[^>]*for="'.preg_quote($id, '/').'"/i',
                $html,
                "Kontrol #{$id} pada {$url} tidak punya label."
            );
        }
    }
}
```

- [ ] **Step 2: Jalankan, pastikan gagal, lalu perbaiki tiap kontrol yang disebut**

- [ ] **Step 3: Periksa manual sisa kriteria**

Pesan error form memakai `aria-describedby` yang menunjuk pesan; seluruh tombol memakai `<x-ui.button>` sehingga target sentuh dan focus ring konsisten; status tidak pernah hanya berbeda warna — sudah dipenuhi `fit-badge` dan `status-badge`.

- [ ] **Step 4: Jalankan suite, Pint, commit**

```bash
php artisan test && vendor/bin/pint && git add -A && git commit -m "fix: label, focus, dan target sentuh pada alur inti (F-34)"
```

---

## Verifikasi akhir

- [ ] `php artisan test` hijau seluruhnya
- [ ] `php artisan test --testsuite=Spatial` hijau pada mesin dengan PostGIS
- [ ] `vendor/bin/pint --test` bersih
- [ ] `npm run build` sukses
- [ ] Anggaran query rekomendasi di bawah 10 untuk 12 jalur
- [ ] Seluruh F-01 sampai F-34 punya test yang menutupnya
- [ ] `docs/ARCHITECTURE.md` mencerminkan struktur akhir
