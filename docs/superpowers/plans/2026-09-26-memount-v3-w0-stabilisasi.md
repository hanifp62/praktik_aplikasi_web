# Memount v3 W0 Stabilisasi Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menutup 20 test merah (5 gagal, 15 error) menurut niat test-nya, sehingga suite Unit dan Feature hijau, `npx tsc --noEmit` nol galat terhadap keluaran Wayfinder yang segar, `npm run build` sukses, dan Pint bersih sebelum redesain W1 dimulai (DK-46).

**Architecture:** Tujuh perbaikan kode yang saling lepas kecuali rantai Simpanan dan Banding: pemantauan tugas penjadwal, satu test navigasi yang basi, backend catatan simpanan FR-22 (rute, Form Request, layanan, props), halaman Simpanan dengan `KartuSimpanan` dan `AksiPilihan`, halaman Banding yang benar-benar memakai props-nya, kartu pengingat di halaman trip, dan penutup celah XSS atribusi MapLibre (keputusan pemilik Q20). Sesudah kode, delapan spesifikasi `docs/fitur/` ditulis dari keadaan kode yang sudah diperbaiki. Tidak ada migrasi, dependensi, atau perubahan versi.

**Tech Stack:** PHP 8.5.10, Laravel 13.31.0, Inertia Laravel 3.3.4, `@inertiajs/react` 3.7.1, React 19.3, TypeScript 5.9.3, Vite 8.3, Tailwind CSS 3.4.19, Laravel Wayfinder 0.1.21 (plugin Vite `@laravel/vite-plugin-wayfinder` 0.1.10 menjalankan `wayfinder:generate` pada `buildStart`), PHPUnit 12.5.35, Pint 1.32.0, SQLite in-memory untuk test.

**Spec:** `docs/superpowers/specs/2026-09-25-memount-v3-prototype-design.md` (§3.5 keadaan repo, §4 DK-46, §6.6 Simpanan, §6.7 Bandingkan, §9.3 catatan simpanan, §9.4 penjadwal). Rencana induk: `docs/superpowers/plans/2026-09-26-memount-v3-induk.md` (bagian W0). Isi wajib spesifikasi fitur: `docs/superpowers/plans/2026-09-23-konsolidasi-dokumen.md` Tugas 11.

## Global Constraints

- Versi dikunci (R-020): PHP 8.5.10, Laravel 13.31.0, Inertia Laravel 3.3.4, `@inertiajs/react` 3.7.1, React 19.3, TypeScript 5.9.3, Vite 8.3, Tailwind 3.4.19, Wayfinder 0.1.21, PHPUnit 12.5.35, Pint 1.32.0. W0 tidak mengubah versi apa pun.
- Tidak ada dependensi baru, runtime maupun dev (spec §15 Q3 dan Q17).
- Tidak ada migrasi baru dan tidak ada `php artisan migrate` dalam bentuk apa pun; tidak ada akses Supabase; `.env` tidak dibaca atau dicetak (R-019, DK-23). Test memakai SQLite in-memory dari `phpunit.xml`.
- Tidak ada commit dan push tanpa izin pemilik (R-018). Seluruh migrasi React belum di-commit (374 entri `git status`), jadi `git add` per jalur pada berkas yang sudah berubah sebelum W0 (misalnya `routes/web.php`, `SavedRoutesController.php`, `Saved.tsx`) ikut membawa perubahan lama itu. Keputusan pemilik 26 September 2026: belum ada commit sampai tonggak M1; setiap langkah commit dilewati dan berkas yang berubah dicatat di rekap, lalu diff ditinjau pemilik di M1 (DK-58).
- Bahasa Indonesia, sapaan "Anda", tanpa em dash (antislop R-02), tanpa kata "aman" dan "layak berangkat" (R-012, R-014).
- Label kecocokan publik hanya "Cocok", "Perlu persiapan", "Kurang cocok" (R-004); "Belum dinilai" adalah keadaan UNKNOWN, bukan label keempat. Tidak ada skor atau persen untuk kecocokan dan kesiapan (R-005, BR-09).
- Token warna hanya di `resources/css/app.css`; komponen tidak memakai warna mentah, `bg-white`, `text-black`, atau varian `dark:` (`ColourContrastTest`, `DesignSystemTest`, `InertiaLayoutTest`).
- Konvensi yang disapu test sumber React dan wajib dipenuhi berkas baru: setiap `<button>`, `<Tautan>`, dan `<Link>` mentah punya `min-h-11`, atau kontrolnya memakai komponen `Button` (`TapTargetTest`); setiap `<Button type="submit">` membawa `processing={...}` (`ButtonFeedbackTest`); setiap `<InputError>` dan petunjuk ber-id `...-hint` punya id yang disebut lagi di `aria-describedby` (`KeyboardAndScreenReaderTest`); blok pesan lewat `Alert`, bukan `<div role="status">` (`DesignSystemTest`); setiap `.map(` yang menggambar elemen di `resources/js/pages/` didahului pemeriksaan panjang (`EmptyStateCoverageTest`); `TrailRow.tsx` tidak diubah (`TrailRowTest` mengunci literal `<article className="group relative`).
- Berkas milik koordinator (rencana induk): `routes/web.php` (Tugas 3) dan `HANDOVER.md` (Tugas 9) tidak disunting paralel. Tugas 3, 4, dan 5 berurutan karena berbagi komponen Simpanan dan Banding. Tugas 7 berjalan setelah Tugas 1 sampai 6 karena status implementasinya membaca kode yang sudah diperbaiki.
- Pint per tugas dijalankan pada jalur berkas tugas itu (`vendor/bin/pint --format agent <berkas>`), karena `--dirty` di pohon kerja ini ikut menyentuh berkas di luar tugas. `vendor/bin/pint --dirty --format agent` dijalankan sekali di Tugas 9.
- Setiap perubahan rute diikuti `php artisan wayfinder:generate`. Keluarannya (`resources/js/routes`, `resources/js/actions`, `resources/js/wayfinder`) diabaikan git dan tidak di-commit.
- Gerbang gelombang (rencana induk): suite Unit dan Feature hijau, `npx tsc --noEmit` nol galat, `npm run build` sukses, Pint bersih, Delivery Gate antislop, `HANDOVER.md` §0 diperbarui. Tangkapan 390 px dan 1440 px serta klik-lewat di peramban butuh aplikasi berjalan dengan basis data; `.env` menunjuk Supabase, dan migrasi catatan kemungkinan belum dijalankan di sana (INFERENCE: `HANDOVER.md` bagian 6 mencatat migrasi terakhir yang dijalankan `2026_09_21_000100`, dan keenam migrasi bertanggal 25 September belum dilacak git; belum diverifikasi `migrate:status` karena butuh izin R-019). Jadi keduanya menunggu Q1 (data contoh SQLite lokal) atau Q2 dan dicatat sebagai sisa, tidak diklaim.
- Skill (CLAUDE.md §7): CORE `superpowers:test-driven-development`, `laravel-best-practices`, `testing-best-practices`, `superpowers:verification-before-completion`; SUPPORTING `ecc:laravel-security` (Tugas 3), `tailwindcss-development` dan `ecc:react-patterns` (Tugas 4 sampai 6); CROSS-CUTTING `antislop` dan `antislop-copywriting` (teks antarmuka dan dokumen); N/A skill animasi, visual, dan desain baru (redesain mulai W1) serta `migration` (tidak ada migrasi).

## Review Focus

- **Catatan pribadi berisi markup atau untaian 500 karakter tanpa spasi di layar 360 px:** tersimpan apa adanya, dirender sebagai teks, dan tidak melebarkan halaman. Test: `SavedTrailNoteTest::test_a_note_with_markup_is_kept_as_text_and_never_rendered_as_html` (Tugas 3) dan `SavedTrailNoteTest::test_a_long_unbroken_note_wraps_inside_its_card` (Tugas 4).
- **Tamu, pendaki lain, atau klien rusak menyentuh catatan lewat rute baru:** ditolak dan catatan utuh; permintaan tanpa kolom `catatan` tidak pernah menghapus diam-diam. Test: `SavedTrailNoteTest::test_a_guest_cannot_delete_a_note` dan `SavedTrailNoteTest::test_a_request_without_the_note_field_changes_nothing` (Tugas 3).
- **Jalur tersimpan yang ditarik dari terbit (R-022):** tetap tampil bersama catatannya, tetapi tidak dapat dicentang untuk banding, dan alasannya disebut. Test: `SavedRoutesTest::test_a_saved_trail_that_is_no_longer_published_cannot_be_picked_for_comparison` (Tugas 4).
- **Tugas terjadwal yang belum pernah berjalan atau berhenti berjam-jam:** halaman Kesehatan Sistem menyebutnya dan menyalakan peringatan, tidak ikut terbaca "Berjalan normal". Test: `SystemHealthPageTest::test_the_reminder_task_that_never_ran_raises_the_alarm` dan `SchedulerHealthTest::test_the_hourly_reminder_task_is_overdue_after_three_hours_without_a_run` (Tugas 1).
- **Trip yang sudah dimulai, selesai, atau dibatalkan:** halaman trip tidak menawarkan sakelar pengingat yang pasti ditolak server dan menjelaskan sebabnya. Test: `TripReminderTest::test_the_trip_page_offers_no_switch_once_the_trip_has_started` (Tugas 6).

---

## File Structure

### Keadaan awal terukur (FAKTA, 26 September 2026)

- `php artisan test --compact --testsuite=Unit,Feature` dijalankan ulang saat menyusun rencana ini: 1.252 test, 1.231 lulus, 5 gagal, 15 error, 1 dilewati. Error: 12 di `SavedTrailNoteTest` (rute belum ada) dan 3 di `SavedRoutesTest` (komponen belum ada). Gagal: `AuthenticationTest::test_navigation_menu_can_be_rendered`, `NoDeadViewDataTest::test_every_prop_passed_to_a_react_page_is_actually_used`, `PrdReferenceIntegrityTest::test_the_eight_feature_specs_follow_the_template`, `SavedTrailNoteTest::test_the_note_is_shown_on_the_owners_saved_card`, `SchedulerHealthTest::test_every_scheduled_task_is_actually_monitored`. Spec v3 §3.5 menulis "13 error, 1 gagal" untuk `SavedTrailNoteTest`; keluaran nyata 12 error dan 1 gagal dari 16 test (3 sudah lulus: Policy, Banding, hapus akun).
- `app/Http/Controllers/Trails/SavedTrailNoteController.php` dan `app/Http/Requests/Trails/UpdateSavedTrailNoteRequest.php` sudah ada sebagai rintisan kosong hasil `make:*` (controller tanpa method; `authorize()` mengembalikan `false`). Tugas 3 mengisinya, tidak membuat ulang.
- `npx tsc --noEmit` lulus hanya karena keluaran Wayfinder di `resources/js/routes/` basi (dibangkitkan 25 September 2026 01:51, sebelum `routes/web.php` diubah pukul 07:23). Dibangkitkan segar ke scratchpad, `tsc` gagal dengan satu galat: `resources/js/pages/Trails/Saved.tsx(10,24): error TS2307: Cannot find module '@/routes/trails/compare'`. Plugin Wayfinder menjalankan `wayfinder:generate` pada `buildStart` dan Wayfinder 0.1.21 memangkas berkas basi, jadi `npm run build` gagal di titik yang sama. Di runtime, tombol "Hapus" di Simpanan mengirim DELETE ke `/trails/compare/{id}`, rute yang tidak ada; rute yang benar `trails.saved.remove`.
- `resources/js/components/trails/banding/TabelBanding.tsx` mengetik `hapus` sebagai `{ metode, url }` dan memanggil `router.delete(kolom.hapus.url)`, padahal `RouteComparisonController` mengirim untaian URL GET (dijaga `RouteComparisonTest::test_removing_a_column_opens_the_comparison_without_it_and_never_touches_the_saved_list`). "Hapus dari perbandingan" memanggil `router.delete(undefined)`.
- `resources/js/pages/Trails/Compare.tsx` membaca `modeTautan` dan `tautanBagikan` yang tidak pernah dikirim (`RouteComparisonTest::test_the_page_no_longer_offers_a_link_that_needs_an_account` menuntut keduanya tidak ada). `pilihan` lolos `NoDeadViewDataTest` hanya karena kata "pilihan" di komentar JSX baris 39 di dalam blok `modeTautan`; menghapus blok mati itu membuat `pilihan` merah, jadi Tugas 5 merender pemilih yang sungguhan.
- Kelima prop yang dilaporkan mati diikat test lain: `jumlahTersimpan`, `pesan`, dan `pilihan` oleh `RouteComparisonTest`; `batasBanding` oleh `SavedRoutesTest::test_the_list_states_its_size_against_the_configured_limits`; `pengingat` oleh `TripReminderTest`. Mencabutnya berarti melonggarkan test itu, jadi keputusan W0 untuk semuanya: dipakai halaman, sesuai spec v3 §6.6, §6.7, dan PRD §149.
- Menambah `trips:kirim-pengingat` ke `SchedulerHealthService::TUGAS` akan memerahkan `SchedulerHealthTest::test_a_recent_successful_run_is_healthy` dan `SystemHealthPageTest::test_a_healthy_scheduler_raises_no_alarm`, karena fixture keduanya hanya mencatat dua tugas.
- `vendor/bin/pint --test --format agent` gagal di lima berkas: rintisan `SavedTrailNoteController.php` dan empat test rintisan FR-25 (`PersonalTargetTest`, `OwnedEquipmentTest`, `ManualHikeRecordTest`, `PersonalDataWriteGuardTest`) yang hanya membuka `/` dan mengimpor kelas tak terpakai.
- Migrasi FR-25 `create_personal_targets_table` dan `create_user_owned_items_table` hanya berisi `id` dan `timestamps`; `add_source_and_recorded_values_to_hiking_history_table` kosong. Spec v3 §3.2 menyebut skema itu sudah menampung data prototype; itu tidak sesuai kode (dilaporkan, tidak diubah di W0).

### Berkas

| Berkas | Aksi | Tugas | Tanggung jawab |
|---|---|---|---|
| `app/Services/SchedulerHealthService.php` | Ubah | 1 | Daftar pantau tugas terjadwal `TUGAS` |
| `resources/js/pages/Admin/Health.tsx` | Ubah | 1 | Kalimat akibat tugas berhenti ikut menyebut pengingat |
| `tests/Feature/SchedulerHealthTest.php`, `tests/Feature/SystemHealthPageTest.php` | Ubah | 1 | Fixture menyapu `TUGAS`; test tugas pengingat |
| `tests/Feature/Auth/AuthenticationTest.php` | Ubah | 2 | Label lima tab pemilik |
| `routes/web.php` (milik koordinator) | Ubah | 3 | Rute `trails.saved.note.update` dan `trails.saved.note.destroy` |
| `app/Http/Requests/Trails/UpdateSavedTrailNoteRequest.php` | Ubah (isi rintisan) | 3 | Validasi `catatan` |
| `app/Http/Controllers/Trails/SavedTrailNoteController.php` | Ubah (isi rintisan) | 3 | Tulis dan hapus catatan |
| `app/Services/ConsiderationService.php` | Ubah | 3 | `simpananMilik()` dan `simpanCatatan()` |
| `app/Http/Controllers/Trails/SavedRoutesController.php` | Ubah | 3, 4 | Props `trails.*.catatan` (3) dan `batasCatatan` (4) |
| `resources/js/pages/Trails/Saved.tsx` | Ubah | 3, 4 | Impor rute hapus yang benar (3); kartu dan pilihan banding (4) |
| `resources/js/components/trails/types.ts` | Ubah | 4 | Tipe `JalurTersimpan` |
| `resources/js/components/trails/banding/AksiPilihan.tsx` | Buat | 4 | Hitungan pilihan dan tautan banding |
| `resources/js/components/trails/banding/KartuSimpanan.tsx` | Buat | 4 | Kartu simpanan: centang, catatan, hapus |
| `resources/js/components/trails/banding/PemilihBanding.tsx` | Buat | 5 | Mengganti pilihan di halaman Banding |
| `resources/js/components/trails/banding/TabelBanding.tsx` | Ubah | 5 | `hapus` sebagai tautan GET, penanda `tersimpan` |
| `resources/js/pages/Trails/Compare.tsx` | Ubah | 5 | Memakai `pilihan`, `jumlahTersimpan`, `pesan` |
| `app/Http/Controllers/Trails/RouteComparisonController.php` | Ubah (docblock) | 5 | Tidak lagi menyebut kelas yang tidak ada |
| `tests/Feature/SavedTrailNoteTest.php` | Ubah | 3, 4 | Test baru |
| `tests/Feature/SavedRoutesTest.php` | Ubah | 4 | Test baru |
| `tests/Feature/RouteComparisonTest.php` | Ubah | 5 | Test baru |
| `resources/js/components/trips/PengingatTrip.tsx` | Buat | 6 | Kartu pengingat FR-24 |
| `resources/js/pages/Trips/Show.tsx` | Ubah | 6 | Memakai `pengingat` |
| `tests/Feature/TripReminderTest.php` | Ubah | 6 | Test baru |
| `docs/fitur/01-jelajah-rute.md` sampai `docs/fitur/08-konsol-pengelola.md` | Buat | 7 | Delapan spesifikasi fitur |
| `resources/js/components/peta/usePetaDasar.ts`, `tests/Feature/MapComponentTest.php` | Ubah | 8 | Kontrol atribusi bawaan MapLibre dimatikan (CVE-2026-85061, keputusan pemilik Q20) dan test regresinya |
| `tests/Feature/PersonalTargetTest.php`, `OwnedEquipmentTest.php`, `ManualHikeRecordTest.php`, `PersonalDataWriteGuardTest.php` | Ubah (Pint saja) | 9 | Impor tak terpakai dibuang; isinya menunggu W7 |
| `HANDOVER.md` (milik koordinator) | Ubah | 9 | §0 dengan angka nyata |

---

### Task 1: Tugas pengingat masuk daftar pantau penjadwal

**Files:**
- Modify: `app/Services/SchedulerHealthService.php:29-51`
- Modify: `resources/js/pages/Admin/Health.tsx:83-87` dan `:168-172`
- Test: `tests/Feature/SchedulerHealthTest.php`, `tests/Feature/SystemHealthPageTest.php`

**Interfaces:**
- Consumes: jadwal `Schedule::command('trips:kirim-pengingat')->hourly()->withoutOverlapping(50)` di `routes/console.php:23`; `App\Listeners\RecordScheduledTaskRun::taskKey()` mencocokkan perintah dengan kunci `TUGAS` lewat `str_contains`.
- Produces: `SchedulerHealthService::TUGAS['trips:kirim-pengingat'] = ['judul' => 'Pengiriman pengingat perjalanan', 'jadwal' => 'Setiap jam, pada menit ke-0', 'toleransi_jam' => 3]`. Laporan `report()` kini tiga baris; indeks 2 adalah tugas pengingat.

- [ ] **Step 1: Tulis dua test yang gagal**

Tambahkan ke `tests/Feature/SchedulerHealthTest.php`, tepat sesudah method `test_a_task_that_simply_stopped_running_is_overdue()`:

```php
    /**
     * trips:kirim-pengingat berjalan tiap jam. Dua jam tanpa jalan masih wajar; lebih
     * dari tiga jam berarti penjadwalnya berhenti, dan pengingat yang jatuh tempo baru
     * terkirim ketika ia berjalan lagi.
     */
    public function test_the_hourly_reminder_task_is_overdue_after_three_hours_without_a_run(): void
    {
        $this->catat('trips:kirim-pengingat', ScheduledTaskRun::SUCCESS, now()->subHours(2));

        $this->assertSame(SchedulerHealthService::SEHAT, $this->baris('trips:kirim-pengingat')['keadaan']);

        $this->travel(2)->hours();

        $this->assertSame(SchedulerHealthService::TERLAMBAT, $this->baris('trips:kirim-pengingat')['keadaan']);
    }
```

Tambahkan ke `tests/Feature/SystemHealthPageTest.php`, tepat sesudah method `test_a_stopped_scheduler_is_named_as_overdue()`:

```php
    /**
     * Tugas yang baru didaftarkan dan belum pernah berjalan di server ini menyalakan
     * peringatan, tidak ikut terbaca normal hanya karena dua tugas lainnya sehat.
     */
    public function test_the_reminder_task_that_never_ran_raises_the_alarm(): void
    {
        foreach (['weather:refresh', 'data:freshness-check'] as $tugas) {
            ScheduledTaskRun::create([
                'task' => $tugas,
                'status' => ScheduledTaskRun::SUCCESS,
                'ran_at' => now()->subHour(),
            ]);
        }

        $this->actingAs($this->admin())
            ->get(route('admin.health'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('perluDiurus', true)
                ->where('tugas.2.kunci', 'trips:kirim-pengingat')
                ->where('tugas.2.judul', 'Pengiriman pengingat perjalanan')
                ->where('tugas.2.keadaanLabel', 'Belum pernah berjalan'));

        $this->assertStringContainsString('pengingat perjalanan berhenti terkirim', File::get(resource_path('js/pages/Admin/Health.tsx')));
    }
```

- [ ] **Step 2: Jalankan dan pastikan gagal karena alasan yang benar**

Run: `php artisan test --compact tests/Feature/SchedulerHealthTest.php tests/Feature/SystemHealthPageTest.php`
Expected: FAIL pada tiga test: `test_every_scheduled_task_is_actually_monitored` (pesan menyebut `trips:kirim-pengingat` tidak terdaftar), `test_the_hourly_reminder_task_is_overdue_after_three_hours_without_a_run` ("Tugas trips:kirim-pengingat tidak ada di laporan."), dan `test_the_reminder_task_that_never_ran_raises_the_alarm` (`perluDiurus` false dan `tugas.2` tidak ada).

- [ ] **Step 3: Daftarkan tugasnya di `TUGAS`**

Ganti docblock dan konstanta `TUGAS` di `app/Services/SchedulerHealthService.php` dengan:

```php
    /**
     * Tugas terjadwal beserta jarak maksimal antar-jalan yang masih wajar.
     *
     * Toleransinya sengaja lebih longgar dari jadwalnya. weather:refresh berjalan pukul
     * 6 dan 18 sehingga jarak normalnya 12 jam, tetapi antrean yang padat atau server
     * yang sibuk dapat menggesernya sedikit. Ambang yang dipasang persis 12 jam akan
     * menyala merah setiap beberapa hari tanpa ada yang rusak, dan penanda yang sering
     * menyala palsu berhenti dibaca.
     *
     * trips:kirim-pengingat berjalan tiap jam, jadi toleransinya tiga jam: satu atau dua
     * jam yang terlewat masih wajar, tetapi pengingat yang jatuh tempo baru terkirim
     * ketika penjadwal berjalan lagi, dan pada hari keberangkatan tidak dikirim sama sekali.
     *
     * @var array<string, array{judul: string, jadwal: string, toleransi_jam: int}>
     */
    public const TUGAS = [
        'weather:refresh' => [
            'judul' => 'Pembaruan prakiraan BMKG',
            'jadwal' => 'Dua kali sehari, pukul 06:00 dan 18:00 WIB',
            'toleransi_jam' => 14,
        ],
        'data:freshness-check' => [
            'judul' => 'Pemeriksaan kesegaran status resmi',
            'jadwal' => 'Setiap hari pukul 07:00 WIB',
            'toleransi_jam' => 26,
        ],
        'trips:kirim-pengingat' => [
            'judul' => 'Pengiriman pengingat perjalanan',
            'jadwal' => 'Setiap jam, pada menit ke-0',
            'toleransi_jam' => 3,
        ],
    ];
```

- [ ] **Step 4: Jalankan lagi dan lihat dua fixture yang tertinggal**

Run: `php artisan test --compact tests/Feature/SchedulerHealthTest.php tests/Feature/SystemHealthPageTest.php`
Expected: `test_every_scheduled_task_is_actually_monitored` dan `test_the_hourly_reminder_task_is_overdue_after_three_hours_without_a_run` PASS. FAIL pada `SchedulerHealthTest::test_a_recent_successful_run_is_healthy` ("Failed asserting that true is false."), `SystemHealthPageTest::test_a_healthy_scheduler_raises_no_alarm` (`perluDiurus`), dan `test_the_reminder_task_that_never_ran_raises_the_alarm` (kalimat `Health.tsx` belum diubah). Dua kegagalan pertama adalah celah fixture: keduanya hanya mencatat dua tugas.

- [ ] **Step 5: Buat fixture menyapu `TUGAS`**

Ini bukan pelonggaran: penegasannya tetap sama ("semua tugas berjalan baru-baru ini berarti tidak ada yang perlu diurus"), hanya fixture yang kini mencakup setiap tugas yang dipantau, termasuk yang didaftarkan besok.

Di `tests/Feature/SchedulerHealthTest.php`, ganti method `test_a_recent_successful_run_is_healthy()` dengan:

```php
    /**
     * Setiap tugas yang dipantau berjalan baru-baru ini. Fixture-nya menyapu
     * SchedulerHealthService::TUGAS, bukan daftar tulisan tangan, supaya tugas yang
     * didaftarkan besok ikut tercakup.
     */
    public function test_a_recent_successful_run_is_healthy(): void
    {
        foreach (array_keys(SchedulerHealthService::TUGAS) as $kunci) {
            $this->catat($kunci, ScheduledTaskRun::SUCCESS, now()->subHour());
        }

        $this->assertSame(SchedulerHealthService::SEHAT, $this->baris('weather:refresh')['keadaan']);
        $this->assertFalse($this->service()->needsAttention());
    }
```

Di `tests/Feature/SystemHealthPageTest.php`, ganti method `test_a_healthy_scheduler_raises_no_alarm()` dengan:

```php
    public function test_a_healthy_scheduler_raises_no_alarm(): void
    {
        foreach (array_keys(SchedulerHealthService::TUGAS) as $tugas) {
            ScheduledTaskRun::create([
                'task' => $tugas,
                'status' => ScheduledTaskRun::SUCCESS,
                'ran_at' => now()->subHour(),
            ]);
        }

        $this->actingAs($this->admin())
            ->get(route('admin.health'))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page): void {
                $page->where('perluDiurus', false)->has('tugas', count(SchedulerHealthService::TUGAS));

                foreach (range(0, count(SchedulerHealthService::TUGAS) - 1) as $i) {
                    $page->where("tugas.{$i}.keadaanLabel", 'Berjalan normal');
                }
            });
    }
```

- [ ] **Step 6: Sebut pengingat di kalimat akibat pada halaman Kesehatan**

Di `resources/js/pages/Admin/Health.tsx`, ganti isi `<Alert variant="warning">` di dalam `{perluDiurus ? (` menjadi:

```tsx
                    <Alert variant="warning">
                        Ada tugas terjadwal yang tidak berjalan sebagaimana mestinya. Selama itu berlangsung, prakiraan cuaca berhenti diperbarui, status resmi yang kedaluwarsa berhenti diperiksa, dan pengingat perjalanan berhenti terkirim.
                    </Alert>
```

Lalu ganti atribut `description` pada `EmptyState` bertajuk "Tidak ada tugas terjadwal yang terdaftar" menjadi:

```tsx
                            description="Daftar yang kosong di halaman ini bukan kabar baik. Selama tidak ada tugas terdaftar, prakiraan cuaca tidak pernah diperbarui, status resmi yang kedaluwarsa tidak pernah diperiksa, pengingat perjalanan tidak pernah terkirim, dan tidak ada yang akan memberi tahu."
```

- [ ] **Step 7: Jalankan dan pastikan lulus**

Run: `php artisan test --compact tests/Feature/SchedulerHealthTest.php tests/Feature/SystemHealthPageTest.php`
Expected: PASS, 20 test (SchedulerHealthTest 10, SystemHealthPageTest 10), 0 gagal.

Run: `php artisan test --compact --filter=test_the_task_runs_every_hour`
Expected: PASS (jadwal tiap jam tidak berubah).

- [ ] **Step 8: Rapikan format**

Run: `vendor/bin/pint --format agent app/Services/SchedulerHealthService.php tests/Feature/SchedulerHealthTest.php tests/Feature/SystemHealthPageTest.php`
Expected: `{"result":"pass"}` atau berkas diperbaiki tanpa galat.

- [ ] **Step 9: Commit**

Commit hanya bila pemilik sudah mengizinkan commit; bila belum, lewati langkah ini dan catat berkas yang berubah di rekap.

```bash
git add app/Services/SchedulerHealthService.php resources/js/pages/Admin/Health.tsx tests/Feature/SchedulerHealthTest.php tests/Feature/SystemHealthPageTest.php
git commit -m "fix: pantau tugas trips:kirim-pengingat di kesehatan penjadwal"
```

### Task 2: Label navigasi di `AuthenticationTest` mengikuti lima tab pemilik

**Files:**
- Test: `tests/Feature/Auth/AuthenticationTest.php:91-108`

**Interfaces:**
- Consumes: `export const permukaan` di `resources/js/layouts/NavLinks.tsx` (label `Beranda`, `Jelajah`, `Trip`, `Mendaki`, `Progres`).
- Produces: tidak ada antarmuka baru.

Alasan mengubah test: test ini masih menuntut lima permukaan lama (Jelajah, Pertimbangkan, Perjalanan, Progres, Kabar). Pemilik menggantinya dengan lima tab (DK-25, PRD §145 lewat GR-02; label Indonesia asumsi A1 spec v2), dan `NavigationConsistencyTest::test_the_react_navigation_lists_the_same_five_surfaces_in_order` sudah menegakkan label baru itu. Test lama bertentangan dengan keputusan pemilik, jadi yang diperbarui adalah harapannya, bukan kodenya.

- [ ] **Step 1: Jalankan test yang merah**

Run: `php artisan test --compact --filter=test_navigation_menu_can_be_rendered`
Expected: FAIL, diff menunjukkan harapan `['Jelajah', 'Pertimbangkan', 'Perjalanan', 'Progres', 'Kabar']` lawan nyata `['Beranda', 'Jelajah', 'Trip', 'Mendaki', 'Progres']`.

- [ ] **Step 2: Perbarui harapan dan docblock-nya**

Ganti method `test_navigation_menu_can_be_rendered()` beserta docblock-nya di `tests/Feature/Auth/AuthenticationTest.php` dengan:

```php
    /**
     * Navigasi React: identitas yang dibutuhkan menu dikirim server, dan kelima tab
     * pemilik (DK-25, PRD §145) tertulis di sumber navigasi yang dirender klien, di luar
     * jangkauan HTTP. Urutan tab dan aturan tab aktif dijaga NavigationConsistencyTest.
     */
    public function test_navigation_menu_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.user.id', $user->id)
                ->where('auth.user.name', $user->name));

        preg_match_all("/label: '([^']+)'/", File::get(resource_path('js/layouts/NavLinks.tsx')), $cocok);
        $this->assertSame(['Beranda', 'Jelajah', 'Trip', 'Mendaki', 'Progres'], $cocok[1]);
    }
```

- [ ] **Step 3: Jalankan dan pastikan lulus**

Run: `php artisan test --compact tests/Feature/Auth/AuthenticationTest.php tests/Feature/NavigationConsistencyTest.php`
Expected: PASS semua.

- [ ] **Step 4: Rapikan format**

Run: `vendor/bin/pint --format agent tests/Feature/Auth/AuthenticationTest.php`
Expected: `{"result":"pass"}`.

- [ ] **Step 5: Commit**

Commit hanya bila pemilik sudah mengizinkan commit; bila belum, lewati langkah ini dan catat berkas yang berubah di rekap.

```bash
git add tests/Feature/Auth/AuthenticationTest.php
git commit -m "test: label navigasi mengikuti lima tab pemilik"
```

### Task 3: Catatan simpanan FR-22 di server

**Files:**
- Modify: `routes/web.php:38` (impor) dan `:109` (rute), berkas milik koordinator
- Modify: `app/Http/Requests/Trails/UpdateSavedTrailNoteRequest.php` (isi rintisan)
- Modify: `app/Http/Controllers/Trails/SavedTrailNoteController.php` (isi rintisan)
- Modify: `app/Services/ConsiderationService.php` (dua method baru sesudah `remove()`)
- Modify: `app/Http/Controllers/Trails/SavedRoutesController.php`
- Modify: `resources/js/pages/Trails/Saved.tsx:10` (satu baris impor)
- Test: `tests/Feature/SavedTrailNoteTest.php`

**Interfaces:**
- Consumes: `TrailConsideration::CATATAN_MAKS` (500), kolom `trail_considerations.note` dari migrasi `2026_09_25_002314_add_note_to_trail_considerations_table`, `TrailConsiderationPolicy::update(User, TrailConsideration): bool`, config `hiking.rate_limits.personal_writes_per_minute` (30).
- Produces:
  - Rute `PUT trails/saved/{trail}/catatan` bernama `trails.saved.note.update` dan `DELETE trails/saved/{trail}/catatan` bernama `trails.saved.note.destroy`; `{trail}` adalah id jalur (angka), bukan id baris simpanan. Wayfinder: `import { update, destroy } from '@/routes/trails/saved/note'`, dipanggil `update.url({ trail: id })`.
  - `ConsiderationService::simpananMilik(User $user, int $trailId): TrailConsideration` (404 bila bukan simpanan pengguna itu; relasi `trail:id,name` dimuat).
  - `ConsiderationService::simpanCatatan(TrailConsideration $simpanan, ?string $catatan): ?string` (kosong atau spasi menjadi null; mengembalikan isi tersimpan).
  - Flash `pengumuman`: `Catatan untuk {nama jalur} disimpan.` atau `Catatan untuk {nama jalur} dihapus.`
  - Props `Trails/Saved`: setiap `trails.*` membawa `catatan: string|null`.

- [ ] **Step 1: Tulis test yang gagal**

Di `tests/Feature/SavedTrailNoteTest.php`, perbaiki docblock kelas yang menyebut test yang tidak ada. Ganti:

```php
 * kartu Tersimpan miliknya, dan tidak pernah ikut ke halaman Banding maupun tautan
 * bagikan (yang terakhir dijaga SharedSelectionPageTest). Baris dicari di dalam simpanan
```

dengan:

```php
 * kartu Tersimpan miliknya, dan tidak pernah ikut ke halaman Banding maupun tautan
 * bagikan (yang terakhir dijaga test tautan bagikan FR-23 ketika fitur itu dibangun;
 * sekarang belum ada). Baris dicari di dalam simpanan
```

Lalu tambahkan empat test ini tepat sesudah method `test_a_guest_cannot_write_a_note()`:

```php
    public function test_a_guest_cannot_delete_a_note(): void
    {
        $user = User::factory()->create();
        $jalur = $this->tersimpan($user, catatan: 'Tetap');

        $this->delete(route('trails.saved.note.destroy', $jalur->id))->assertRedirect(route('login'));

        $this->assertSame('Tetap', $this->catatan($user, $jalur));
    }

    /**
     * Permintaan tanpa kolom catatan bukan perintah menghapus: klien yang rusak atau
     * permintaan buatan tangan tidak boleh mengosongkan catatan diam-diam.
     */
    public function test_a_request_without_the_note_field_changes_nothing(): void
    {
        $user = User::factory()->create();
        $jalur = $this->tersimpan($user, catatan: 'Tetap');

        $this->actingAs($user)
            ->put(route('trails.saved.note.update', $jalur->id), [])
            ->assertSessionHasErrors(['catatan' => 'Catatan pribadi tidak terkirim. Muat ulang halaman lalu coba lagi.']);

        $this->assertSame('Tetap', $this->catatan($user, $jalur));
    }

    /**
     * Catatan ditulis bebas oleh pendaki. Isinya disimpan apa adanya, props membawa teks
     * mentah, JSON halaman menyandikan tandanya, dan kartunya merender teks (tanpa
     * dangerouslySetInnerHTML, dijaga SavedRoutesTest).
     */
    public function test_a_note_with_markup_is_kept_as_text_and_never_rendered_as_html(): void
    {
        $user = User::factory()->create();
        $jalur = $this->tersimpan($user);
        $catatan = '<b>Bawa</b> <script>alert(1)</script>';

        $this->actingAs($user)->put(route('trails.saved.note.update', $jalur->id), ['catatan' => $catatan])->assertRedirect();

        $this->assertSame($catatan, $this->catatan($user, $jalur));

        $respons = $this->actingAs($user)->get(route('trails.saved'));

        $respons->assertInertia(fn (AssertableInertia $page) => $page->where('trails.0.catatan', $catatan));
        $respons->assertDontSee('<script>alert(1)</script>', escape: false);
    }

    /**
     * Jalur tanpa catatan mengirim null, bukan string kosong yang terbaca sebagai
     * catatan (R-007: yang tidak ada tetap tidak ada).
     */
    public function test_a_saved_trail_without_a_note_sends_null(): void
    {
        $user = User::factory()->create();
        $this->tersimpan($user);

        $this->actingAs($user)->get(route('trails.saved'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('trails.0.catatan', null));
    }
```

- [ ] **Step 2: Jalankan dan pastikan gagal karena alasan yang benar**

Run: `php artisan test --compact tests/Feature/SavedTrailNoteTest.php`
Expected: 20 test, 3 lulus, 2 gagal, 15 error. Error berbunyi `Route [trails.saved.note.update] not defined.` atau `Route [trails.saved.note.destroy] not defined.`; gagal berbunyi `Property [trails.0.catatan] does not exist.`

- [ ] **Step 3: Daftarkan rute catatan**

Di `routes/web.php`, tambahkan impor sesudah `use App\Http\Controllers\Trails\SavedRoutesController;`:

```php
use App\Http\Controllers\Trails\SavedTrailNoteController;
```

Lalu ganti baris rute hapus simpanan:

```php
    Route::delete('trails/saved/{trail}', [ConsiderationController::class, 'destroy'])->whereNumber('trail')->name('trails.saved.remove');
```

dengan:

```php
    Route::delete('trails/saved/{trail}', [ConsiderationController::class, 'destroy'])->whereNumber('trail')->name('trails.saved.remove');
    // Catatan pribadi pada jalur tersimpan (FR-22). Jalur dari rute dan pemilik dari sesi,
    // tidak pernah dari badan permintaan; dibatasi laju seperti tulisan pribadi lain.
    Route::put('trails/saved/{trail}/catatan', [SavedTrailNoteController::class, 'update'])->whereNumber('trail')->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,catatan-simpanan')->name('trails.saved.note.update');
    Route::delete('trails/saved/{trail}/catatan', [SavedTrailNoteController::class, 'destroy'])->whereNumber('trail')->middleware('throttle:'.config('hiking.rate_limits.personal_writes_per_minute').',1,catatan-simpanan')->name('trails.saved.note.destroy');
```

- [ ] **Step 4: Isi Form Request**

Ganti seluruh isi `app/Http/Requests/Trails/UpdateSavedTrailNoteRequest.php` dengan:

```php
<?php

namespace App\Http\Requests\Trails;

use App\Models\TrailConsideration;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Catatan pribadi pada jalur tersimpan (FR-22).
 *
 * Hanya `catatan` yang divalidasi dan dibaca, jadi `user_id`, `trail_id`, atau `note`
 * di badan permintaan tidak pernah sampai ke basis data. Otorisasi tingkat objek
 * terjadi di controller: barisnya dicari di simpanan pengguna yang masuk (404 bagi yang
 * lain), lalu TrailConsiderationPolicy menjaga lapis kedua.
 *
 * Kolomnya wajib hadir walau boleh kosong: kosong berarti menghapus, tetapi permintaan
 * yang tidak membawa kolom itu sama sekali bukan perintah menghapus.
 */
class UpdateSavedTrailNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'catatan' => ['present', 'nullable', 'string', 'max:'.TrailConsideration::CATATAN_MAKS],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'catatan.present' => 'Catatan pribadi tidak terkirim. Muat ulang halaman lalu coba lagi.',
            'catatan.string' => 'Catatan pribadi harus berupa teks.',
            'catatan.max' => 'Catatan pribadi tidak boleh lebih dari '.TrailConsideration::CATATAN_MAKS.' karakter.',
        ];
    }
}
```

`max` pada untaian dihitung per karakter (`mb_strlen`), jadi 500 huruf beraksen diterima (`test_exactly_500_characters_is_accepted_even_when_they_are_multibyte`).

- [ ] **Step 5: Tambahkan dua method layanan**

Di `app/Services/ConsiderationService.php`, tambahkan tepat sesudah method `remove()`:

```php
    /**
     * Baris simpanan milik $user untuk satu jalur, atau 404.
     *
     * Dicari di dalam simpanan pengguna yang masuk, bukan lewat id baris dari klien:
     * baris pendaki lain dan jalur yang belum disimpan sama-sama tidak ditemukan, jadi
     * jawabannya tidak membocorkan simpanan siapa pun (PRD §77).
     */
    public function simpananMilik(User $user, int $trailId): TrailConsideration
    {
        return TrailConsideration::query()
            ->whereBelongsTo($user)
            ->where('trail_id', $trailId)
            ->with('trail:id,name')
            ->firstOrFail();
    }

    /**
     * Menulis catatan pribadi (FR-22) lalu mengembalikan isi yang tersimpan.
     *
     * Kosong atau hanya spasi berarti menghapus: kolomnya kembali null, bukan string
     * kosong yang terbaca sebagai catatan. `note` sengaja tidak fillable, jadi ditulis
     * lewat forceFill setelah pemanggil memastikan barisnya milik penulisnya
     * (simpananMilik() dan TrailConsiderationPolicy).
     */
    public function simpanCatatan(TrailConsideration $simpanan, ?string $catatan): ?string
    {
        $bersih = trim((string) $catatan);

        $simpanan->forceFill(['note' => $bersih === '' ? null : $bersih])->save();

        return $simpanan->note;
    }
```

- [ ] **Step 6: Isi controller**

Ganti seluruh isi `app/Http/Controllers/Trails/SavedTrailNoteController.php` dengan:

```php
<?php

namespace App\Http\Controllers\Trails;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trails\UpdateSavedTrailNoteRequest;
use App\Services\ConsiderationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Catatan pribadi pada jalur tersimpan (FR-22).
 *
 * Barisnya dicari di dalam simpanan pengguna yang masuk
 * (ConsiderationService::simpananMilik()), jadi id jalur dari klien tidak pernah
 * menunjuk baris orang lain: baris pendaki lain dan jalur yang belum disimpan sama-sama
 * 404. TrailConsiderationPolicy menjaga lapis kedua. Pemilik datang dari sesi dan jalur
 * dari rute; field lain di badan permintaan diabaikan karena hanya `catatan` yang
 * divalidasi. Hasilnya diumumkan lewat flash `pengumuman` yang dibaca wilayah
 * role="status" di halaman Simpanan.
 */
class SavedTrailNoteController extends Controller
{
    public function update(UpdateSavedTrailNoteRequest $request, int $trail, ConsiderationService $consideration): RedirectResponse
    {
        $simpanan = $consideration->simpananMilik($request->user(), $trail);
        $this->authorize('update', $simpanan);

        $catatan = $consideration->simpanCatatan($simpanan, $request->validated('catatan'));

        return back()->with(
            'pengumuman',
            'Catatan untuk '.$simpanan->trail->name.($catatan === null ? ' dihapus.' : ' disimpan.')
        );
    }

    public function destroy(Request $request, int $trail, ConsiderationService $consideration): RedirectResponse
    {
        $simpanan = $consideration->simpananMilik($request->user(), $trail);
        $this->authorize('update', $simpanan);

        $consideration->simpanCatatan($simpanan, null);

        return back()->with('pengumuman', 'Catatan untuk '.$simpanan->trail->name.' dihapus.');
    }
}
```

- [ ] **Step 7: Kirim catatan ke halaman Simpanan**

Ganti seluruh isi `app/Http/Controllers/Trails/SavedRoutesController.php` dengan:

```php
<?php

namespace App\Http\Controllers\Trails;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrailRowResource;
use App\Models\TrailConsideration;
use App\Services\ConsiderationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Daftar rute tersimpan, terbaru dulu, beserta catatan pribadinya (FR-22).
 *
 * Membaca simpanan di basis data lewat ConsiderationService::simpanan(), sumber yang sama
 * dengan baki di Jelajah dan pilihan bawaan halaman Banding. Simpanan yang hidup di sesi
 * hilang setiap kali pendaki keluar, karena Logout memanggil Session::invalidate().
 *
 * Catatan hanya dikirim ke halaman ini, milik pemiliknya; halaman Banding dan tautan
 * bagikan tidak pernah membawanya.
 */
class SavedRoutesController extends Controller
{
    public function __invoke(Request $request, ConsiderationService $consideration): Response
    {
        $simpanan = $consideration->simpanan($request->user());

        return Inertia::render('Trails/Saved', [
            'title' => 'Rute Tersimpan',
            'trails' => $simpanan->map(fn (TrailConsideration $baris) => [
                ...(new TrailRowResource($baris->trail, null, true))->resolve($request),
                'terbit' => $baris->trail->is_published,
                'catatan' => $baris->note,
            ])->values()->all(),
            'batas' => $consideration->batasSimpanan(),
            'batasBanding' => $consideration->batasBanding(),
            'pengumuman' => session('pengumuman', ''),
        ]);
    }
}
```

- [ ] **Step 8: Jalankan dan pastikan lulus**

Run: `php artisan test --compact tests/Feature/SavedTrailNoteTest.php`
Expected: PASS, 20 test, 0 gagal, 0 error.

- [ ] **Step 9: Bangkitkan Wayfinder dan saksikan impor basi di Simpanan**

Run: `php artisan wayfinder:generate`
Expected: `[Wayfinder] Generated actions in ...` dan `[Wayfinder] Generated routes in ...`. Folder `resources/js/routes/trails/compare/` hilang (dipangkas), `resources/js/routes/trails/saved/index.ts` mengekspor `remove`, dan `resources/js/routes/trails/saved/note/index.ts` mengekspor `update` serta `destroy`.

Run: `npx tsc --noEmit`
Expected: FAIL tepat satu galat: `resources/js/pages/Trails/Saved.tsx(10,24): error TS2307: Cannot find module '@/routes/trails/compare' or its corresponding type declarations.` Ini cacat yang sama dengan tombol "Hapus" yang mengirim DELETE ke `/trails/compare/{id}`.

- [ ] **Step 10: Arahkan impor ke rute hapus yang benar**

Di `resources/js/pages/Trails/Saved.tsx`, ganti:

```tsx
import { remove } from '@/routes/trails/compare';
```

dengan:

```tsx
import { remove } from '@/routes/trails/saved';
```

Run: `npx tsc --noEmit`
Expected: selesai tanpa keluaran (0 galat).

- [ ] **Step 11: Jalankan test tetangga**

Run: `php artisan test --compact tests/Feature/SavedRoutesTest.php tests/Feature/ConsiderationTest.php tests/Feature/RouteComparisonTest.php tests/Feature/RateLimitTest.php`
Expected: hanya 3 error di `SavedRoutesTest` (`KartuSimpanan.tsx` dan `AksiPilihan.tsx` belum ada; ditutup Tugas 4); selebihnya PASS.

- [ ] **Step 12: Rapikan format**

Run: `vendor/bin/pint --format agent routes/web.php app/Http/Requests/Trails/UpdateSavedTrailNoteRequest.php app/Http/Controllers/Trails/SavedTrailNoteController.php app/Services/ConsiderationService.php app/Http/Controllers/Trails/SavedRoutesController.php tests/Feature/SavedTrailNoteTest.php`
Expected: `{"result":"pass"}` atau berkas diperbaiki tanpa galat.

- [ ] **Step 13: Commit**

Commit hanya bila pemilik sudah mengizinkan commit; bila belum, lewati langkah ini dan catat berkas yang berubah di rekap.

```bash
git add routes/web.php app/Http/Requests/Trails/UpdateSavedTrailNoteRequest.php app/Http/Controllers/Trails/SavedTrailNoteController.php app/Services/ConsiderationService.php app/Http/Controllers/Trails/SavedRoutesController.php resources/js/pages/Trails/Saved.tsx tests/Feature/SavedTrailNoteTest.php
git commit -m "feat: catatan pribadi pada jalur tersimpan di server (FR-22)"
```

### Task 4: Halaman Simpanan dengan `KartuSimpanan` dan `AksiPilihan`

**Files:**
- Create: `resources/js/components/trails/banding/AksiPilihan.tsx`
- Create: `resources/js/components/trails/banding/KartuSimpanan.tsx`
- Modify: `resources/js/components/trails/types.ts` (tipe baru sesudah `BarisJalur`)
- Modify: `resources/js/pages/Trails/Saved.tsx` (tulis ulang)
- Modify: `app/Http/Controllers/Trails/SavedRoutesController.php` (prop `batasCatatan`)
- Test: `tests/Feature/SavedRoutesTest.php`, `tests/Feature/SavedTrailNoteTest.php`

**Interfaces:**
- Consumes: dari Tugas 3, rute Wayfinder `remove` (`@/routes/trails/saved`), `update` dan `destroy` (`@/routes/trails/saved/note`), props `trails.*.catatan`; dari kode yang ada, `compare` (`@/routes/trails`), `TrailRow({ trail: BarisJalur, aksiSimpan?: boolean })`, `fokusGalat(galat, urutan)` di `@/components/akun/fokusGalat`, `Checkbox`, `Label`, `Textarea`, `InputError` di `@/components/ui/Field`.
- Produces:
  - `export interface JalurTersimpan extends BarisJalur { terbit: boolean; catatan: string | null }` di `@/components/trails/types`.
  - `export function AksiPilihan({ dipilih, maks, tersedia }: { dipilih: number[]; maks: number; tersedia: number })` di `@/components/trails/banding/AksiPilihan` (dipakai lagi Tugas 5).
  - `export function KartuSimpanan({ jalur, batasCatatan, pilihan, onPilih }: { jalur: JalurTersimpan; batasCatatan: number; pilihan: { dipilih: boolean; penuh: boolean } | null; onPilih: (id: number) => void })`, yang merender satu `<li>`.
  - Prop halaman `Trails/Saved`: `batasCatatan: number` (= `TrailConsideration::CATATAN_MAKS`).

- [ ] **Step 1: Tulis test yang gagal**

Tambahkan ke `tests/Feature/SavedRoutesTest.php`, tepat sesudah method `test_the_list_states_its_size_against_the_configured_limits()`:

```php
    /**
     * R-022: jalur yang ditarik dari terbit tetap tampil bersama catatannya, tetapi tidak
     * dapat dicentang untuk banding, dan alasannya disebut di kartunya.
     */
    public function test_a_saved_trail_that_is_no_longer_published_cannot_be_picked_for_comparison(): void
    {
        $user = User::factory()->create();
        $ditarik = $this->jalur(['name' => 'Jalur Ditarik']);
        app(ConsiderationService::class)->toggle($user, $ditarik);
        $ditarik->update(['is_published' => false]);

        $this->daftar($user)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('trails.0.name', 'Jalur Ditarik')
            ->where('trails.0.terbit', false));

        $kartu = $this->sumber('js/components/trails/banding/KartuSimpanan.tsx');
        $this->assertStringContainsString('!jalur.terbit', $kartu);
        $this->assertStringContainsString('Jalur ini sedang tidak terbit, jadi tidak dapat dibandingkan.', $kartu);
        $this->assertStringContainsString('disabled={alasanTidakDapatDipilih !== null}', $kartu);
        $this->assertStringContainsString('trails.filter((t) => t.terbit)', $this->sumber());
    }

    /**
     * Regresi 26 September 2026: halaman ini mengimpor `remove` dari keluaran Wayfinder
     * yang basi (`@/routes/trails/compare`), sehingga "Hapus" mengirim DELETE ke alamat
     * yang tidak dilayani. Rute hapus simpanan adalah trails.saved.remove.
     */
    public function test_removal_goes_through_the_saved_list_route(): void
    {
        $kartu = $this->sumber('js/components/trails/banding/KartuSimpanan.tsx');

        $this->assertStringContainsString("import { remove } from '@/routes/trails/saved';", $kartu);
        $this->assertStringNotContainsString('@/routes/trails/compare', $kartu);
        $this->assertStringNotContainsString('@/routes/trails/compare', $this->sumber());
    }

    /**
     * DK-05: yang dicentang dihitung terhadap batas banding dari config, dan batasnya
     * disebut sebelum ditabrak.
     */
    public function test_the_selection_states_its_count_against_the_comparison_limit(): void
    {
        $aksi = $this->sumber('js/components/trails/banding/AksiPilihan.tsx');

        $this->assertStringContainsString('Dipilih {jumlah} dari {maks}.', $aksi);
        $this->assertStringContainsString('Pilih minimal 2 jalur untuk membandingkan.', $aksi);
        $this->assertStringContainsString('Batas banding tercapai.', $aksi);
        $this->assertStringContainsString('maks={batasBanding}', $this->sumber());
    }
```

Di `tests/Feature/SavedTrailNoteTest.php`, tambahkan impor `use Illuminate\Support\Facades\File;` sesudah `use Illuminate\Foundation\Testing\RefreshDatabase;`, lalu tambahkan tepat sesudah method `test_the_note_is_shown_on_the_owners_saved_card()`:

```php
    /**
     * Batas catatan di formulir datang dari konstanta yang sama dengan aturan
     * validasinya, jadi hitungan di layar tidak pernah menyebut angka yang berbeda dari
     * yang ditolak server.
     */
    public function test_the_page_states_the_note_limit_from_the_validation_constant(): void
    {
        $user = User::factory()->create();
        $this->tersimpan($user);

        $this->actingAs($user)->get(route('trails.saved'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('batasCatatan', TrailConsideration::CATATAN_MAKS));

        $this->assertStringContainsString('{panjang} dari {batasCatatan} karakter', File::get(resource_path('js/components/trails/banding/KartuSimpanan.tsx')));
    }

    /**
     * Catatan 500 karakter tanpa spasi (misalnya tautan yang ditempel) tidak boleh
     * melebarkan halaman di layar 360 px. overflow-wrap: anywhere mengecilkan lebar
     * minimum kolomnya, sedangkan break-word bawaan body tidak; kolom isi kartu boleh
     * menyempit sampai nol (minmax(0,1fr)).
     */
    public function test_a_long_unbroken_note_wraps_inside_its_card(): void
    {
        $kartu = File::get(resource_path('js/components/trails/banding/KartuSimpanan.tsx'));

        $this->assertMatchesRegularExpression('/<p className="[^"]*\[overflow-wrap:anywhere\][^"]*">\{jalur\.catatan\}<\/p>/', $kartu);
        $this->assertStringContainsString('grid-cols-[2.75rem_minmax(0,1fr)]', $kartu);
        $this->assertStringContainsString('<div className="min-w-0', $kartu);
    }
```

- [ ] **Step 2: Jalankan dan pastikan gagal karena alasan yang benar**

Run: `php artisan test --compact tests/Feature/SavedRoutesTest.php tests/Feature/SavedTrailNoteTest.php`
Expected: `SavedRoutesTest` 19 test, 13 lulus, 6 error (`File does not exist at path ...KartuSimpanan.tsx` atau `...AksiPilihan.tsx`). `SavedTrailNoteTest` 22 test, 20 lulus, 1 gagal (`Property [batasCatatan] does not exist.`), 1 error (berkas `KartuSimpanan.tsx` tidak ada).

- [ ] **Step 3: Tambahkan tipe `JalurTersimpan`**

Di `resources/js/components/trails/types.ts`, tambahkan tepat sesudah penutup `interface BarisJalur`:

```ts
/** Baris halaman Simpanan: baris jalur ditambah keadaan terbit dan catatan pribadi (FR-22). */
export interface JalurTersimpan extends BarisJalur {
    terbit: boolean;
    catatan: string | null;
}
```

- [ ] **Step 4: Buat `AksiPilihan.tsx`**

Buat `resources/js/components/trails/banding/AksiPilihan.tsx`:

```tsx
import { Button } from '@/components/ui/Button';
import { compare } from '@/routes/trails';

interface AksiPilihanProps {
    /** Id jalur yang dicentang, dalam urutan dicentang: urutan kolom di halaman Banding. */
    dipilih: number[];
    /** Batas banding dari server (hiking.saved_trails.compare_max, DK-05). */
    maks: number;
    /** Jalur terbit yang dibandingkan bila tidak ada yang dicentang. */
    tersedia: number;
}

/**
 * Aksi banding untuk sebuah pilihan (DK-05). Tanpa centang, tautannya telanjang
 * (`compare.url()`) dan halaman Banding membandingkan simpanan terbaru yang masih terbit;
 * dengan centang, pilihannya dibawa di ?trails= dalam urutan dicentang. Hitungan terhadap
 * batas disebut sebelum batasnya ditabrak, lewat wilayah role="status" supaya pembaca
 * layar mendengar setiap perubahan.
 */
export function AksiPilihan({ dipilih, maks, tersedia }: AksiPilihanProps) {
    const jumlah = dipilih.length;

    return (
        <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
            <p role="status" className="text-sm text-secondary">
                {jumlah === 0 ? (
                    <>Centang sampai {maks} jalur untuk memilih yang dibandingkan.</>
                ) : (
                    <>
                        Dipilih {jumlah} dari {maks}.{' '}
                        {jumlah < 2 ? 'Pilih minimal 2 jalur untuk membandingkan.' : jumlah >= maks ? 'Batas banding tercapai.' : null}
                    </>
                )}
            </p>

            {jumlah >= 2 ? (
                <Button size="sm" href={compare.url({ query: { trails: dipilih.join(',') } })}>
                    Bandingkan {jumlah} jalur
                </Button>
            ) : jumlah === 0 && tersedia >= 2 ? (
                <Button size="sm" variant="secondary" href={compare.url()}>
                    Bandingkan {Math.min(tersedia, maks)} simpanan terbaru
                </Button>
            ) : null}
        </div>
    );
}
```

- [ ] **Step 5: Buat `KartuSimpanan.tsx`**

Buat `resources/js/components/trails/banding/KartuSimpanan.tsx`:

```tsx
import { router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { fokusGalat } from '@/components/akun/fokusGalat';
import { TrailRow } from '@/components/trails/TrailRow';
import type { JalurTersimpan } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { Checkbox, InputError, Label, Textarea } from '@/components/ui/Field';
import { kelas } from '@/lib/kelas';
import { remove } from '@/routes/trails/saved';
import { destroy as ruteHapusCatatan, update as ruteSimpanCatatan } from '@/routes/trails/saved/note';

interface KartuSimpananProps {
    jalur: JalurTersimpan;
    /** Batas panjang catatan dari server (TrailConsideration::CATATAN_MAKS). */
    batasCatatan: number;
    /** Keadaan centang banding, atau null bila simpanan belum cukup untuk dibandingkan. */
    pilihan: { dipilih: boolean; penuh: boolean } | null;
    onPilih: (id: number) => void;
}

/**
 * Satu jalur tersimpan: baris jalur, centang untuk banding, catatan pribadi (FR-22), dan
 * penghapusan dari simpanan.
 *
 * Catatan hanya milik pemiliknya: tampil di sini, tidak pernah ikut ke halaman Banding
 * maupun tautan bagikan, dan dirender sebagai teks. Kolom isi boleh menyempit sampai nol
 * (minmax(0,1fr)) dan catatannya memakai overflow-wrap: anywhere, jadi untaian tanpa
 * spasi tidak melebarkan halaman di layar 360 px.
 *
 * Jalur yang ditarik dari terbit tetap tampil supaya catatannya tidak hilang, tetapi tidak
 * dapat dicentang: halaman Banding hanya membandingkan jalur terbit (R-022). Garis bawah
 * TrailRow dinetralkan dari sini ([&>article]:border-b-0) supaya satu kartu hanya punya
 * satu garis pemisah; TrailRow sendiri tidak diubah.
 */
export function KartuSimpanan({ jalur, batasCatatan, pilihan, onPilih }: KartuSimpananProps) {
    const [menyunting, setMenyunting] = useState(false);
    const [menghapus, setMenghapus] = useState(false);
    const [menghapusCatatan, setMenghapusCatatan] = useState(false);
    const form = useForm({ catatan: jalur.catatan ?? '' });

    const idPilih = `pilih-${jalur.id}`;
    const idCatatan = `catatan-${jalur.id}`;
    const panjang = Array.from(form.data.catatan).length;
    const alasanTidakDapatDipilih =
        pilihan === null
            ? null
            : !jalur.terbit
              ? 'Jalur ini sedang tidak terbit, jadi tidak dapat dibandingkan.'
              : pilihan.penuh && !pilihan.dipilih
                ? 'Batas banding sudah tercapai. Lepas satu centang untuk memilih jalur ini.'
                : null;

    const hapus = () => {
        setMenghapus(true);
        router.delete(remove.url({ trail: jalur.id }), { preserveScroll: true, onFinish: () => setMenghapus(false) });
    };

    const mulaiSunting = () => {
        form.setData('catatan', jalur.catatan ?? '');
        form.clearErrors();
        setMenyunting(true);
    };

    const batalSunting = () => {
        form.clearErrors();
        setMenyunting(false);
    };

    const simpanCatatan = (e: FormEvent) => {
        e.preventDefault();
        form.put(ruteSimpanCatatan.url({ trail: jalur.id }), {
            preserveScroll: true,
            onSuccess: () => setMenyunting(false),
            onError: (galat) => fokusGalat(galat, [['catatan', idCatatan]]),
        });
    };

    const hapusCatatan = () => {
        setMenghapusCatatan(true);
        router.delete(ruteHapusCatatan.url({ trail: jalur.id }), {
            preserveScroll: true,
            onSuccess: () => setMenyunting(false),
            onFinish: () => setMenghapusCatatan(false),
        });
    };

    return (
        <li className={kelas('grid border-b border-subtle', pilihan === null ? 'grid-cols-1' : 'grid-cols-[2.75rem_minmax(0,1fr)]')}>
            {pilihan === null ? null : (
                <div className="pt-4">
                    <label
                        htmlFor={idPilih}
                        className={kelas('inline-flex min-h-11 min-w-11 items-center justify-center', alasanTidakDapatDipilih === null && 'cursor-pointer')}
                    >
                        <Checkbox
                            id={idPilih}
                            checked={pilihan.dipilih}
                            disabled={alasanTidakDapatDipilih !== null}
                            onChange={() => onPilih(jalur.id)}
                            aria-describedby={alasanTidakDapatDipilih ? `pilih-${jalur.id}-hint` : undefined}
                        />
                        <span className="sr-only">Pilih {jalur.name} untuk dibandingkan</span>
                    </label>
                </div>
            )}

            <div className="min-w-0 pb-5 [&>article]:border-b-0 [&>article]:pb-3">
                <TrailRow trail={jalur} aksiSimpan={false} />

                {alasanTidakDapatDipilih ? (
                    <p id={`pilih-${jalur.id}-hint`} className="text-xs text-muted">
                        {alasanTidakDapatDipilih}
                    </p>
                ) : null}

                {menyunting ? (
                    <form onSubmit={simpanCatatan} className="mt-3 space-y-2" noValidate>
                        <Label htmlFor={idCatatan}>Catatan pribadi</Label>
                        <Textarea
                            id={idCatatan}
                            name="catatan"
                            rows={3}
                            value={form.data.catatan}
                            onChange={(e) => form.setData('catatan', e.target.value)}
                            aria-invalid={form.errors.catatan ? true : undefined}
                            aria-describedby={form.errors.catatan ? `catatan-${jalur.id}-hint catatan-${jalur.id}-error` : `catatan-${jalur.id}-hint`}
                        />
                        <p id={`catatan-${jalur.id}-hint`} className={kelas('text-xs', panjang > batasCatatan ? 'font-medium text-danger-900' : 'text-muted')}>
                            Hanya Anda yang dapat membaca catatan ini. {panjang} dari {batasCatatan} karakter.
                        </p>
                        <InputError id={`catatan-${jalur.id}-error`} messages={form.errors.catatan} />
                        <div className="flex flex-wrap gap-2">
                            <Button type="submit" size="sm" processing={form.processing}>
                                Simpan catatan
                            </Button>
                            {jalur.catatan ? (
                                <Button variant="ghost" size="sm" processing={menghapusCatatan} onClick={hapusCatatan}>
                                    Hapus catatan
                                </Button>
                            ) : null}
                            <Button variant="ghost" size="sm" onClick={batalSunting}>
                                Batal
                            </Button>
                        </div>
                    </form>
                ) : jalur.catatan ? (
                    <div className="mt-2">
                        <p className="text-xs font-medium text-muted">Catatan pribadi, hanya terlihat oleh Anda</p>
                        <p className="mt-1 whitespace-pre-line text-sm text-primary [overflow-wrap:anywhere]">{jalur.catatan}</p>
                    </div>
                ) : null}

                <div className="mt-2 flex flex-wrap gap-x-2">
                    {menyunting ? null : (
                        <Button variant="ghost" size="sm" className="-ml-3" onClick={mulaiSunting}>
                            {jalur.catatan ? 'Ubah catatan' : 'Tulis catatan'}
                            <span className="sr-only"> untuk {jalur.name}</span>
                        </Button>
                    )}
                    <Button variant="ghost" size="sm" className={menyunting ? '-ml-3' : undefined} processing={menghapus} onClick={hapus}>
                        Hapus dari simpanan<span className="sr-only">: {jalur.name}</span>
                    </Button>
                </div>
            </div>
        </li>
    );
}
```

- [ ] **Step 6: Tulis ulang `Saved.tsx`**

Ganti seluruh isi `resources/js/pages/Trails/Saved.tsx` dengan:

```tsx
import { usePage } from '@inertiajs/react';
import { useState, type ReactNode } from 'react';
import { AksiPilihan } from '@/components/trails/banding/AksiPilihan';
import { KartuSimpanan } from '@/components/trails/banding/KartuSimpanan';
import type { JalurTersimpan } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { index as trailsIndex } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    trails: JalurTersimpan[];
    batas: number;
    batasBanding: number;
    batasCatatan: number;
    pengumuman: string;
}

/**
 * Daftar rute tersimpan (DK-05: simpan longgar, banding sempit), dari simpanan di basis
 * data: tetap ada setelah keluar dan masuk lagi, juga di perangkat lain.
 *
 * Tombol simpan tidak diulang di baris: setiap baris di sini sudah tersimpan. Yang ada
 * adalah centang untuk memilih sampai batasBanding jalur yang dibandingkan, catatan
 * pribadi (FR-22), dan "Hapus dari simpanan" yang menyebut dirinya penghapusan. Tanpa
 * centang, aksi banding membandingkan simpanan terbaru yang masih terbit. Centang baru
 * ditawarkan bila ada minimal dua simpanan, supaya tidak ada kontrol yang tidak berbuat
 * apa-apa.
 */
export default function Saved() {
    const { trails, batas, batasBanding, batasCatatan, pengumuman } = usePage<Props>().props;
    const [dipilih, setDipilih] = useState<number[]>([]);
    const tersedia = trails.filter((t) => t.terbit).length;
    const berlaku = (ids: number[]) => ids.filter((id) => trails.some((t) => t.id === id && t.terbit));
    // Jalur yang baru dihapus atau ditarik dari terbit tidak boleh tertinggal di pilihan.
    const pilihanBerlaku = berlaku(dipilih);

    const pilih = (id: number) =>
        setDipilih((sebelum) => {
            const d = berlaku(sebelum);

            return d.includes(id) ? d.filter((x) => x !== id) : d.length >= batasBanding ? d : [...d, id];
        });

    return (
        <div className="py-8">
            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Rute Tersimpan"
                    description="Jalur yang Anda simpan dari Jelajah. Tersimpan di akun Anda, jadi tetap ada setelah keluar dan masuk lagi, juga di perangkat lain."
                />

                {trails.length === 0 ? (
                    <EmptyState
                        title="Belum ada rute tersimpan"
                        description="Tekan “Simpan rute” pada jalur di halaman Jelajah. Jalur yang Anda simpan terkumpul di sini dan bisa dibandingkan berdampingan."
                    >
                        <Button href={trailsIndex.url()}>Cari jalur untuk disimpan</Button>
                    </EmptyState>
                ) : (
                    <>
                        <div className="mb-3 space-y-2">
                            <p className="text-sm text-muted">
                                {trails.length} rute tersimpan, maksimal {batas}.
                            </p>

                            {trails.length >= 2 ? (
                                <AksiPilihan dipilih={pilihanBerlaku} maks={batasBanding} tersedia={tersedia} />
                            ) : (
                                <p className="text-sm text-secondary">Simpan satu jalur lagi untuk membandingkan.</p>
                            )}
                        </div>

                        <ul className="border-t border-subtle">
                            {trails.map((t) => (
                                <KartuSimpanan
                                    key={t.id}
                                    jalur={t}
                                    batasCatatan={batasCatatan}
                                    pilihan={trails.length >= 2 ? { dipilih: pilihanBerlaku.includes(t.id), penuh: pilihanBerlaku.length >= batasBanding } : null}
                                    onPilih={pilih}
                                />
                            ))}
                        </ul>
                    </>
                )}

                <p role="status" className="sr-only">{pengumuman}</p>
            </div>
        </div>
    );
}

Saved.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

- [ ] **Step 7: Kirim batas catatan dari konstanta validasi**

Ganti seluruh isi `app/Http/Controllers/Trails/SavedRoutesController.php` dengan:

```php
<?php

namespace App\Http\Controllers\Trails;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrailRowResource;
use App\Models\TrailConsideration;
use App\Services\ConsiderationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Daftar rute tersimpan, terbaru dulu, beserta catatan pribadinya (FR-22).
 *
 * Membaca simpanan di basis data lewat ConsiderationService::simpanan(), sumber yang sama
 * dengan baki di Jelajah dan pilihan bawaan halaman Banding. Simpanan yang hidup di sesi
 * hilang setiap kali pendaki keluar, karena Logout memanggil Session::invalidate().
 *
 * Catatan hanya dikirim ke halaman ini, milik pemiliknya; halaman Banding dan tautan
 * bagikan tidak pernah membawanya. Batas catatan dikirim dari konstanta yang sama dengan
 * aturan validasinya, supaya hitungan di formulir tidak menyebut angka yang berbeda dari
 * yang ditolak server.
 */
class SavedRoutesController extends Controller
{
    public function __invoke(Request $request, ConsiderationService $consideration): Response
    {
        $simpanan = $consideration->simpanan($request->user());

        return Inertia::render('Trails/Saved', [
            'title' => 'Rute Tersimpan',
            'trails' => $simpanan->map(fn (TrailConsideration $baris) => [
                ...(new TrailRowResource($baris->trail, null, true))->resolve($request),
                'terbit' => $baris->trail->is_published,
                'catatan' => $baris->note,
            ])->values()->all(),
            'batas' => $consideration->batasSimpanan(),
            'batasBanding' => $consideration->batasBanding(),
            'batasCatatan' => TrailConsideration::CATATAN_MAKS,
            'pengumuman' => session('pengumuman', ''),
        ]);
    }
}
```

- [ ] **Step 8: Periksa tipe**

Run: `npx tsc --noEmit`
Expected: selesai tanpa keluaran (0 galat).

- [ ] **Step 9: Jalankan test fitur dan sapuan konvensi**

Run: `php artisan test --compact tests/Feature/SavedRoutesTest.php tests/Feature/SavedTrailNoteTest.php`
Expected: PASS, 41 test (SavedRoutesTest 19, SavedTrailNoteTest 22), 0 gagal.

Run: `php artisan test --compact tests/Feature/TapTargetTest.php tests/Feature/ButtonFeedbackTest.php tests/Feature/KeyboardAndScreenReaderTest.php tests/Feature/DesignSystemTest.php tests/Feature/ColourContrastTest.php tests/Feature/EmptyStateCoverageTest.php tests/Feature/InertiaLayoutTest.php tests/Feature/TrailRowTest.php tests/Feature/SurfaceSystemTest.php`
Expected: PASS semua.

Run: `php artisan test --compact tests/Feature/NoDeadViewDataTest.php`
Expected: masih FAIL pada `test_every_prop_passed_to_a_react_page_is_actually_used`, tetapi daftarnya tinggal tiga baris: `RouteComparisonController mengirim jumlahTersimpan ke Trails/Compare`, `RouteComparisonController mengirim pesan ke Trails/Compare`, `TripController mengirim pengingat ke Trips/Show`. Baris `SavedRoutesController mengirim batasBanding ke Trails/Saved` hilang, dan `batasCatatan` tidak muncul.

- [ ] **Step 10: Rapikan format**

Run: `vendor/bin/pint --format agent app/Http/Controllers/Trails/SavedRoutesController.php tests/Feature/SavedRoutesTest.php tests/Feature/SavedTrailNoteTest.php`
Expected: `{"result":"pass"}` atau berkas diperbaiki tanpa galat.

- [ ] **Step 11: Commit**

Commit hanya bila pemilik sudah mengizinkan commit; bila belum, lewati langkah ini dan catat berkas yang berubah di rekap.

```bash
git add resources/js/components/trails/banding/AksiPilihan.tsx resources/js/components/trails/banding/KartuSimpanan.tsx resources/js/components/trails/types.ts resources/js/pages/Trails/Saved.tsx app/Http/Controllers/Trails/SavedRoutesController.php tests/Feature/SavedRoutesTest.php tests/Feature/SavedTrailNoteTest.php
git commit -m "feat: kartu simpanan dengan catatan pribadi dan pilihan banding"
```

### Task 5: Halaman Banding memakai props-nya

**Files:**
- Modify: `resources/js/components/trails/banding/TabelBanding.tsx` (tulis ulang)
- Create: `resources/js/components/trails/banding/PemilihBanding.tsx`
- Modify: `resources/js/pages/Trails/Compare.tsx` (tulis ulang)
- Modify: `app/Http/Controllers/Trails/RouteComparisonController.php:16-27` (docblock saja)
- Test: `tests/Feature/RouteComparisonTest.php`

**Interfaces:**
- Consumes: props dari `RouteComparisonController::show()` yang tidak berubah: `pilihan: { maks: number; opsi: Array<{ id: number; name: string; mountain: string | null; dipilih: boolean; tersimpan: boolean }> }`, `jumlahTersimpan: number`, `pesan: string[]`, `jalur[].tersimpan: boolean`, `jalur[].hapus: string` (URL GET), ditambah `baris` dan `beda` dari `RouteComparisonTable::susun()`. `AksiPilihan({ dipilih, maks, tersedia })` dari Tugas 4.
- Produces: `export interface OpsiBanding` dan `export function PemilihBanding({ pilihan, urutan }: { pilihan: { maks: number; opsi: OpsiBanding[] }; urutan: number[] })` di `@/components/trails/banding/PemilihBanding`; `KolomJalur` kini `{ ...; tersimpan: boolean; hapus: string }`.

Keputusan prop: `jumlahTersimpan`, `pesan`, dan `pilihan` dipakai halaman (bukan dicabut) karena `RouteComparisonTest` mengikat ketiganya dan spec v3 §6.7 membutuhkan pemilih serta keterangan jujur tentang yang tidak ikut dibandingkan. `modeTautan` dan `tautanBagikan` dibuang dari halaman karena server tidak mengirimnya lagi; tombol bagikan kembali setelah FR-23 dibangun (DK-40).

- [ ] **Step 1: Tulis test yang gagal**

Di `tests/Feature/RouteComparisonTest.php`, tambahkan impor `use Illuminate\Support\Facades\File;` sesudah `use Illuminate\Foundation\Testing\RefreshDatabase;`, lalu perbaiki docblock kelas yang menyebut test yang tidak ada. Ganti:

```php
 * Tautan ?trails= lama yang pernah dibagikan tetap terbuka apa adanya; berbagi ke orang
 * tanpa akun kini lewat tautan bagikan (ShareLinkTest).
```

dengan:

```php
 * Tautan ?trails= lama yang pernah dibagikan tetap terbuka apa adanya; berbagi ke orang
 * tanpa akun menunggu tautan bagikan FR-23, yang belum dibangun.
```

Lalu tambahkan tepat sesudah method `test_removing_the_last_column_leaves_an_explicitly_empty_selection()`:

```php
    /**
     * Regresi 26 September 2026: tabel membaca `hapus` sebagai objek {metode, url} dan
     * memanggil router.delete, padahal server mengirim tautan GET (test di atas). Tombol
     * "Hapus dari perbandingan" mengirim DELETE ke alamat kosong.
     */
    public function test_removing_a_column_in_the_table_is_a_plain_link(): void
    {
        $tabel = File::get(resource_path('js/components/trails/banding/TabelBanding.tsx'));

        $this->assertStringContainsString('hapus: string;', $tabel);
        $this->assertStringContainsString('href={j.hapus}', $tabel);
        $this->assertStringNotContainsString('router.delete', $tabel);
    }

    /**
     * Yang tidak ikut dibandingkan disebut di halaman, bukan hanya di props, dan halaman
     * kosong membedakan "belum memilih" dari "belum punya cukup simpanan".
     */
    public function test_the_page_names_what_was_left_out_and_why_nothing_is_compared(): void
    {
        $halaman = File::get(resource_path('js/pages/Trails/Compare.tsx'));

        $this->assertMatchesRegularExpression('/pesan\.length > 0 \? \(\s*<Alert/', $halaman);
        $this->assertStringContainsString('pesan.map((p) => (', $halaman);
        $this->assertStringContainsString('pilihan.opsi.length >= 2', $halaman);
        $this->assertStringContainsString('Lihat daftar rute tersimpan ({jumlahTersimpan})', $halaman);
        $this->assertStringNotContainsString('modeTautan', $halaman);
        $this->assertStringNotContainsString('tautanBagikan', $halaman);
    }

    /**
     * Pemilih di halaman ini menawarkan pilihan dari server dan menghitungnya terhadap
     * batas banding yang sama dengan halaman Tersimpan.
     */
    public function test_the_selector_lets_the_reader_change_the_selection_within_the_limit(): void
    {
        $pemilih = File::get(resource_path('js/components/trails/banding/PemilihBanding.tsx'));

        $this->assertStringContainsString('<PemilihBanding pilihan={pilihan} urutan={jalur.map((j) => j.id)} />', File::get(resource_path('js/pages/Trails/Compare.tsx')));
        $this->assertStringContainsString('pilihan.opsi.map((o) => (', $pemilih);
        $this->assertStringContainsString('<AksiPilihan dipilih={dipilih} maks={pilihan.maks}', $pemilih);
    }
```

- [ ] **Step 2: Jalankan dan pastikan gagal karena alasan yang benar**

Run: `php artisan test --compact tests/Feature/RouteComparisonTest.php`
Expected: 16 test, 13 lulus, 2 gagal (`hapus: string;` tidak ditemukan; pola `pesan.length > 0` tidak cocok), 1 error (`File does not exist at path ...PemilihBanding.tsx`).

- [ ] **Step 3: Tulis ulang `TabelBanding.tsx`**

Ganti seluruh isi `resources/js/components/trails/banding/TabelBanding.tsx` dengan:

```tsx
import type { ReactNode } from 'react';
import { FitLine } from '@/components/trails/FitLine';
import type { FitRingkas } from '@/components/trails/types';
import { Button } from '@/components/ui/Button';
import { StatusBadge, type StatusResmi } from '@/components/ui/StatusBadge';
import { Tautan } from '@/components/ui/Tautan';
import { kelas } from '@/lib/kelas';

export interface KolomJalur {
    id: number;
    name: string;
    mountain: string | null;
    url: string;
    fit: FitRingkas | null;
    status: { status: StatusResmi; label: string | null };
    /** Jalur ini ada di simpanan pembaca; tautan lama dapat membawa jalur yang tidak ia simpan. */
    tersimpan: boolean;
    /** Tautan GET ke perbandingan yang sama tanpa kolom ini. Tidak pernah menghapus simpanan. */
    hapus: string;
}

export interface BarisBanding {
    label: string;
    nilai: string[];
    berbeda: boolean | null;
}

interface TabelBandingProps {
    jalur: KolomJalur[];
    baris: BarisBanding[];
    beda: { fit: boolean | null; status: boolean | null };
    sembunyikanSama: boolean;
}

const sel = 'p-4 align-top';
// Label aspek menempel di kiri ketika tabel digeser di layar sempit, supaya pembaca
// tidak kehilangan baris mana yang sedang ia baca.
const kepalaBaris = 'sticky left-0 z-20 bg-surface p-4 text-left align-top font-medium text-muted';

/**
 * Jalur berdampingan tanpa pemenang (R-016): yang ditandai hanya apakah sebuah aspek
 * berbeda antarjalur, tidak pernah nilai mana yang lebih baik. Aspek yang sama
 * diredupkan dan diberi label "Sama", sehingga perbedaannya yang terbaca lebih dulu.
 *
 * "Hapus dari perbandingan" adalah tautan GET ke perbandingan tanpa kolom itu
 * (RouteComparisonController::tautan()); halaman ini tidak pernah menulis ke simpanan.
 */
export function TabelBanding({ jalur, baris, beda, sembunyikanSama }: TabelBandingProps) {
    const tampil = (berbeda: boolean | null) => !sembunyikanSama || berbeda !== false;
    const barisTampil = baris.filter((b) => tampil(b.berbeda));
    const adaYangTampil = tampil(beda.fit) || tampil(beda.status) || barisTampil.length > 0;

    const kepala = (label: string, berbeda: boolean | null): ReactNode => (
        <th scope="row" className={kepalaBaris}>
            {label}
            {berbeda === false ? <span className="ml-2 rounded bg-surface-sunken px-1.5 py-0.5 text-xs font-normal text-secondary">Sama</span> : null}
        </th>
    );

    return (
        <div className="relative overflow-x-auto rounded-lg border border-subtle bg-surface">
            <table className="min-w-full text-sm">
                <caption className="sr-only">Perbandingan karakteristik jalur</caption>
                <thead>
                    <tr className="border-b border-subtle text-left">
                        <th scope="col" className={kelas(kepalaBaris, 'font-normal')}>Aspek</th>
                        {jalur.map((j) => (
                            <th key={j.id} scope="col" className={kelas(sel, 'min-w-44')}>
                                <Tautan
                                    href={j.url}
                                    className="block rounded-sm py-1 font-semibold text-primary underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600"
                                >
                                    {j.name}
                                </Tautan>
                                <span className="block text-xs font-normal text-secondary">{j.mountain}</span>
                                {j.tersimpan ? null : <span className="block text-xs font-normal text-muted">Bukan simpanan Anda</span>}
                                <Button variant="ghost" size="sm" className="-ml-3 font-normal" href={j.hapus}>
                                    Hapus dari perbandingan<span className="sr-only">: {j.name}</span>
                                </Button>
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {tampil(beda.fit) ? (
                        <tr className="border-b border-subtle">
                            {kepala('Kecocokan untuk Anda', beda.fit)}
                            {jalur.map((j) => (
                                <td key={j.id} className={sel}>
                                    {j.fit ? <FitLine fit={j.fit} trailId={j.id} /> : <span className="text-sm text-muted">Belum dinilai</span>}
                                </td>
                            ))}
                        </tr>
                    ) : null}

                    {barisTampil.map((b) => (
                        <tr key={b.label} className="border-b border-subtle">
                            {kepala(b.label, b.berbeda)}
                            {b.nilai.map((nilai, i) => (
                                <td key={jalur[i]?.id ?? i} className={kelas(sel, b.berbeda === false ? 'text-secondary' : 'font-medium text-primary')}>
                                    {nilai}
                                </td>
                            ))}
                        </tr>
                    ))}

                    {tampil(beda.status) ? (
                        <tr>
                            {kepala('Status resmi', beda.status)}
                            {jalur.map((j) => (
                                <td key={j.id} className={sel}>
                                    <StatusBadge status={j.status.status} text={j.status.label} />
                                </td>
                            ))}
                        </tr>
                    ) : null}

                    {!adaYangTampil ? (
                        <tr>
                            <td colSpan={jalur.length + 1} className="p-4 text-secondary">
                                Semua aspek yang dibandingkan bernilai sama.
                            </td>
                        </tr>
                    ) : null}
                </tbody>
            </table>
        </div>
    );
}
```

- [ ] **Step 4: Buat `PemilihBanding.tsx`**

Buat `resources/js/components/trails/banding/PemilihBanding.tsx`:

```tsx
import { useState } from 'react';
import { Checkbox } from '@/components/ui/Field';
import { AksiPilihan } from './AksiPilihan';

export interface OpsiBanding {
    id: number;
    name: string;
    mountain: string | null;
    dipilih: boolean;
    tersimpan: boolean;
}

interface PemilihBandingProps {
    pilihan: { maks: number; opsi: OpsiBanding[] };
    /** Id kolom yang sedang dibandingkan, dalam urutan kolomnya. */
    urutan: number[];
}

/**
 * Mengganti jalur yang dibandingkan tanpa kembali ke Simpanan. Pilihan datang dari server
 * (RouteComparisonController::opsi()): simpanan pembaca yang masih terbit, ditambah jalur
 * terpilih dari tautan lama yang bukan simpanannya, supaya tetap dapat dilepas. Terlipat
 * bila sudah ada yang dibandingkan, supaya tabel tetap isi utama halaman; terbuka bila
 * belum ada.
 */
export function PemilihBanding({ pilihan, urutan }: PemilihBandingProps) {
    const [dipilih, setDipilih] = useState<number[]>(() => urutan.filter((id) => pilihan.opsi.some((o) => o.id === id && o.dipilih)));
    const penuh = dipilih.length >= pilihan.maks;

    const ubah = (id: number) =>
        setDipilih((d) => (d.includes(id) ? d.filter((x) => x !== id) : d.length >= pilihan.maks ? d : [...d, id]));

    return (
        <details open={urutan.length === 0 ? true : undefined} className="rounded-lg border border-subtle bg-surface px-4">
            <summary className="flex min-h-11 cursor-pointer items-center rounded-sm text-sm font-medium text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
                Pilih jalur yang dibandingkan
            </summary>

            <ul className="grid gap-x-6 sm:grid-cols-2">
                {pilihan.opsi.map((o) => (
                    <li key={o.id}>
                        <label htmlFor={`banding-${o.id}`} className="flex min-h-11 cursor-pointer items-center gap-3 text-sm">
                            <Checkbox
                                id={`banding-${o.id}`}
                                checked={dipilih.includes(o.id)}
                                disabled={penuh && !dipilih.includes(o.id)}
                                onChange={() => ubah(o.id)}
                            />
                            <span className="min-w-0 [overflow-wrap:anywhere]">
                                <span className="font-medium text-primary">{o.name}</span>
                                {o.mountain ? <span className="text-secondary">, {o.mountain}</span> : null}
                                {o.tersimpan ? null : <span className="block text-xs text-muted">Bukan simpanan Anda</span>}
                            </span>
                        </label>
                    </li>
                ))}
            </ul>

            <div className="py-3">
                <AksiPilihan dipilih={dipilih} maks={pilihan.maks} tersedia={pilihan.opsi.filter((o) => o.tersimpan).length} />
            </div>
        </details>
    );
}
```

- [ ] **Step 5: Tulis ulang `Compare.tsx`**

Ganti seluruh isi `resources/js/pages/Trails/Compare.tsx` dengan:

```tsx
import { usePage } from '@inertiajs/react';
import { useId, useState, type ReactNode } from 'react';
import { PemilihBanding, type OpsiBanding } from '@/components/trails/banding/PemilihBanding';
import { TabelBanding, type BarisBanding, type KolomJalur } from '@/components/trails/banding/TabelBanding';
import { Alert } from '@/components/ui/Alert';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { Checkbox } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AppLayout } from '@/layouts/AppLayout';
import { index as trailsIndex, saved } from '@/routes/trails';
import type { PropsBersama } from '@/types';

interface Props extends PropsBersama {
    pilihan: { maks: number; opsi: OpsiBanding[] };
    jumlahTersimpan: number;
    pesan: string[];
    jalur: KolomJalur[];
    baris: BarisBanding[];
    beda: { fit: boolean | null; status: boolean | null };
    pengumuman: string;
}

/**
 * Banding (FR-06, DK-05): dua sampai tiga jalur berdampingan, tanpa pemenang (R-016).
 *
 * Halaman ini hanya membaca. Tanpa ?trails= yang dibandingkan simpanan terbaru yang masih
 * terbit; dengan ?trails= yang dibandingkan pilihan pembaca. Yang tidak ikut dibandingkan
 * (tidak lagi terbit, melewati batas) disebut lewat `pesan`, tidak dibuang diam-diam.
 * Tombol bagikan baru tampil setelah tautan bagikan FR-23 dibangun (DK-40).
 */
export default function Compare() {
    const { pilihan, jumlahTersimpan, pesan, jalur, baris, beda, pengumuman } = usePage<Props>().props;
    const [sembunyikanSama, setSembunyikanSama] = useState(false);
    const idSaring = useId();

    return (
        <div className="py-8">
            <div className="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
                <PageHeader
                    title="Bandingkan Jalur"
                    description="Perbandingan ini membantu Anda melihat perbedaan antar jalur. Sistem tidak menentukan jalur mana yang terbaik."
                >
                    {jumlahTersimpan > 0 ? (
                        <div className="mt-3">
                            <Button variant="ghost" size="sm" className="-ml-3" href={saved.url()}>
                                Lihat daftar rute tersimpan ({jumlahTersimpan})
                            </Button>
                        </div>
                    ) : null}
                </PageHeader>

                {pesan.length > 0 ? (
                    <Alert variant="info">
                        <ul className="space-y-1">
                            {pesan.map((p) => (
                                <li key={p}>{p}</li>
                            ))}
                        </ul>
                    </Alert>
                ) : null}

                {pilihan.opsi.length > 0 ? <PemilihBanding pilihan={pilihan} urutan={jalur.map((j) => j.id)} /> : null}

                {jalur.length === 0 ? (
                    <Card>
                        <p className="text-sm text-secondary">
                            {pilihan.opsi.length >= 2
                                ? 'Belum ada jalur yang dipilih. Centang dua atau tiga jalur, lalu tekan Bandingkan.'
                                : 'Perbandingan memakai jalur terbit yang Anda simpan. Simpan minimal dua jalur dari Jelajah lebih dulu.'}
                        </p>
                        {pilihan.opsi.length >= 2 ? null : (
                            <Button variant="secondary" className="mt-3" href={trailsIndex.url()}>
                                Jelajahi jalur
                            </Button>
                        )}
                    </Card>
                ) : (
                    <>
                        {jalur.length >= 2 ? (
                            <label htmlFor={idSaring} className="inline-flex min-h-11 cursor-pointer items-center gap-2 text-sm text-secondary">
                                <Checkbox id={idSaring} checked={sembunyikanSama} onChange={(e) => setSembunyikanSama(e.target.checked)} />
                                Sembunyikan aspek yang sama
                            </label>
                        ) : null}

                        <TabelBanding jalur={jalur} baris={baris} beda={beda} sembunyikanSama={sembunyikanSama} />
                    </>
                )}

                <p role="status" className="sr-only">{pengumuman}</p>
            </div>
        </div>
    );
}

Compare.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

- [ ] **Step 6: Luruskan docblock controller**

Di `app/Http/Controllers/Trails/RouteComparisonController.php`, ganti baris docblock:

```php
 * Berbagi ke orang tanpa akun kini lewat tautan bagikan (ShareLinkController).
```

dengan:

```php
 * Berbagi ke orang tanpa akun menunggu tautan bagikan FR-23 (spec v3 §9.3); sampai itu
 * halaman ini tidak menawarkan tombol bagikan (DK-40).
```

- [ ] **Step 7: Periksa tipe**

Run: `npx tsc --noEmit`
Expected: selesai tanpa keluaran (0 galat).

- [ ] **Step 8: Jalankan test fitur, tetangga, dan sapuan**

Run: `php artisan test --compact tests/Feature/RouteComparisonTest.php tests/Feature/ComparisonAsCostTest.php tests/Feature/SavedRoutesTest.php tests/Feature/SavedTrailNoteTest.php`
Expected: PASS semua (RouteComparisonTest 16 test).

Run: `php artisan test --compact tests/Feature/TapTargetTest.php tests/Feature/ButtonFeedbackTest.php tests/Feature/KeyboardAndScreenReaderTest.php tests/Feature/DesignSystemTest.php tests/Feature/ColourContrastTest.php tests/Feature/EmptyStateCoverageTest.php tests/Feature/InertiaLayoutTest.php tests/Feature/NavigationConsistencyTest.php`
Expected: PASS semua.

Run: `php artisan test --compact tests/Feature/NoDeadViewDataTest.php`
Expected: masih FAIL dengan satu baris saja: `TripController mengirim pengingat ke Trips/Show`. Tidak ada baris untuk `pilihan`, `jumlahTersimpan`, atau `pesan`.

- [ ] **Step 9: Rapikan format**

Run: `vendor/bin/pint --format agent app/Http/Controllers/Trails/RouteComparisonController.php tests/Feature/RouteComparisonTest.php`
Expected: `{"result":"pass"}` atau berkas diperbaiki tanpa galat.

- [ ] **Step 10: Commit**

Commit hanya bila pemilik sudah mengizinkan commit; bila belum, lewati langkah ini dan catat berkas yang berubah di rekap.

```bash
git add resources/js/components/trails/banding/TabelBanding.tsx resources/js/components/trails/banding/PemilihBanding.tsx resources/js/pages/Trails/Compare.tsx app/Http/Controllers/Trails/RouteComparisonController.php tests/Feature/RouteComparisonTest.php
git commit -m "fix: halaman banding memakai pilihan, pesan, dan tautan hapus kolom"
```

### Task 6: Pengingat perjalanan tampil di halaman trip

**Files:**
- Create: `resources/js/components/trips/PengingatTrip.tsx`
- Modify: `resources/js/pages/Trips/Show.tsx` (impor, `Props`, destrukturisasi, satu blok JSX sesudah kartu izin)
- Test: `tests/Feature/TripReminderTest.php`

**Interfaces:**
- Consumes: prop `pengingat` dari `TripController::show()` = `TripReminderScheduler::untukHalaman()`: `{ aktif: boolean; dapatDiatur: boolean; jadwal: string; daftar: Array<{ jenis: string; label: string; waktu: string; keadaan: 'menunggu' | 'terkirim' | 'dilewati'; keadaanTeks: string }>; catatan: string | null }`. Rute Wayfinder `update` dari `@/routes/trips/reminders` (`PUT trips/{trip}/pengingat`, badan `{ aktif: boolean }`); controller mengalihkan ke `trips.show` dengan flash `status` atau `penolakan-trip` yang sudah dirender halaman.
- Produces: `export interface PengingatHalaman` dan `export function PengingatTrip({ tripId, pengingat }: { tripId: number; pengingat: PengingatHalaman })` di `@/components/trips/PengingatTrip`.

Keputusan prop: `pengingat` dipakai halaman, tidak dicabut. `TripReminderTest` mengikat isinya, PRD §149 menuntut pengguna dapat menyalakan, mematikan, dan melihat pengingat beserta keadaannya, dan rute `trips.reminders.update` sampai sekarang tidak punya satu pun kontrol di antarmuka.

- [ ] **Step 1: Tulis test yang gagal**

Di `tests/Feature/TripReminderTest.php`, tambahkan impor `use Illuminate\Support\Facades\File;` sesudah `use Illuminate\Support\Carbon;`, lalu tambahkan tepat sesudah method `test_reminders_cannot_be_set_on_a_trip_that_has_started()`:

```php
    /**
     * Sakelar yang pasti ditolak server tidak ditawarkan: setelah trip dimulai, kartu
     * hanya menjelaskan bahwa pengingat tidak dapat diubah lagi.
     */
    public function test_the_trip_page_offers_no_switch_once_the_trip_has_started(): void
    {
        $trip = $this->trip(isian: ['status' => TripStatus::IN_PROGRESS->value, 'reminders_enabled' => true]);

        $this->actingAs($trip->user)->get(route('trips.show', $trip))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pengingat.aktif', true)
            ->where('pengingat.dapatDiatur', false));

        $kartu = File::get(resource_path('js/components/trips/PengingatTrip.tsx'));
        $this->assertMatchesRegularExpression('/\{pengingat\.dapatDiatur \? \(/', $kartu);
        $this->assertStringContainsString('Pengingat tidak dapat diubah lagi: trip ini sudah dimulai, selesai, atau dibatalkan.', $kartu);
    }

    /**
     * Halaman trip menggambar pengingat yang dikirim server: sakelar lewat rute
     * trips.reminders.update dan daftar dengan keadaan kosong yang menjelaskan dirinya.
     */
    public function test_the_trip_page_draws_the_reminder_card(): void
    {
        $halaman = File::get(resource_path('js/pages/Trips/Show.tsx'));
        $kartu = File::get(resource_path('js/components/trips/PengingatTrip.tsx'));

        $this->assertStringContainsString('<PengingatTrip tripId={trip.id} pengingat={pengingat} />', $halaman);
        $this->assertStringContainsString("import { update } from '@/routes/trips/reminders';", $kartu);
        $this->assertStringContainsString('router.put(update.url(tripId), { aktif: !pengingat.aktif }', $kartu);
        $this->assertStringContainsString('pengingat.daftar.length === 0 ? (', $kartu);
    }
```

- [ ] **Step 2: Jalankan dan pastikan gagal karena alasan yang benar**

Run: `php artisan test --compact tests/Feature/TripReminderTest.php`
Expected: 26 test, 24 lulus, 2 error (`File does not exist at path ...PengingatTrip.tsx`).

- [ ] **Step 3: Buat `PengingatTrip.tsx`**

Buat `resources/js/components/trips/PengingatTrip.tsx`:

```tsx
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { update } from '@/routes/trips/reminders';

export interface PengingatHalaman {
    aktif: boolean;
    dapatDiatur: boolean;
    jadwal: string;
    daftar: Array<{
        jenis: string;
        label: string;
        waktu: string;
        keadaan: 'menunggu' | 'terkirim' | 'dilewati';
        keadaanTeks: string;
    }>;
    catatan: string | null;
}

/**
 * Pengingat perjalanan satu trip (FR-24): sakelar surel, jadwal yang berlaku di zona
 * gunungnya, dan keadaan tiap pengingat (menunggu, terkirim, atau dilewati). Semua teks
 * waktu dan keadaan disusun server (TripReminderScheduler::untukHalaman()).
 *
 * Sakelar hanya ditawarkan selama trip belum dimulai; sesudahnya server menolak, jadi
 * tombolnya tidak tampil dan kalimatnya menjelaskan. Isi surel tidak pernah menyatakan
 * jalur atau pendaki aman (R-012).
 */
export function PengingatTrip({ tripId, pengingat }: { tripId: number; pengingat: PengingatHalaman }) {
    const [memproses, setMemproses] = useState(false);

    const ubah = () => {
        setMemproses(true);
        router.put(update.url(tripId), { aktif: !pengingat.aktif }, { preserveScroll: true, onFinish: () => setMemproses(false) });
    };

    return (
        <Card title="Pengingat perjalanan" subtitle={pengingat.jadwal}>
            {pengingat.dapatDiatur ? (
                <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <p className="text-sm text-secondary">{pengingat.aktif ? 'Pengingat surel menyala.' : 'Pengingat surel mati.'}</p>
                    <Button size="sm" variant="secondary" onClick={ubah} processing={memproses}>
                        {pengingat.aktif ? 'Matikan pengingat' : 'Nyalakan pengingat'}
                    </Button>
                </div>
            ) : (
                <p className="text-sm text-secondary">Pengingat tidak dapat diubah lagi: trip ini sudah dimulai, selesai, atau dibatalkan.</p>
            )}

            {pengingat.catatan ? <p className="mt-3 text-sm text-secondary">{pengingat.catatan}</p> : null}

            {pengingat.daftar.length === 0 ? (
                <p className="mt-3 text-sm text-muted">
                    {pengingat.aktif ? 'Belum ada pengingat yang dijadwalkan.' : 'Tidak ada pengingat yang dijadwalkan karena pengingat mati.'}
                </p>
            ) : (
                <ul className="mt-3 divide-y divide-subtle border-y border-subtle">
                    {pengingat.daftar.map((p) => (
                        <li key={p.jenis} className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-3 text-sm">
                            <span className="text-primary">
                                {p.label}
                                <span data-angka className="block text-xs text-muted">
                                    {p.waktu}
                                </span>
                            </span>
                            <span className="text-secondary">{p.keadaanTeks}</span>
                        </li>
                    ))}
                </ul>
            )}
        </Card>
    );
}
```

- [ ] **Step 4: Pasang kartu di `Show.tsx`**

Di `resources/js/pages/Trips/Show.tsx`:

Tambahkan impor sesudah `import { LangkahTrip } from '@/components/trips/LangkahTrip';`:

```tsx
import { PengingatTrip, type PengingatHalaman } from '@/components/trips/PengingatTrip';
```

Tambahkan anggota `Props` sesudah `penolakan: string | null;`:

```tsx
    pengingat: PengingatHalaman;
```

Ganti baris destrukturisasi props dengan:

```tsx
    const { trip, langkah, fit, izin, hasilPendakian, completionStates, penolakan, pengingat, kelola, flash } = usePage<Props>().props;
```

Sisipkan blok ini tepat sesudah penutup blok kartu izin (`) : null}` setelah `<Card title="Izin pendakian">`) dan sebelum `{trip.catatan ? (`:

```tsx
                {/* FR-24. Trip yang sudah lewat tanpa pengingat apa pun tidak mendapat kartu:
                    sakelarnya pasti ditolak server dan daftarnya kosong. */}
                {pengingat.dapatDiatur || pengingat.daftar.length > 0 ? <PengingatTrip tripId={trip.id} pengingat={pengingat} /> : null}
```

- [ ] **Step 5: Periksa tipe**

Run: `npx tsc --noEmit`
Expected: selesai tanpa keluaran (0 galat).

- [ ] **Step 6: Jalankan test fitur, tetangga, dan sapuan**

Run: `php artisan test --compact tests/Feature/TripReminderTest.php tests/Feature/NoDeadViewDataTest.php tests/Feature/TripReactPagesTest.php tests/Feature/TripPlanDetailTest.php tests/Feature/AmbientFitTest.php`
Expected: PASS semua. `NoDeadViewDataTest` kini hijau (2 test).

Run: `php artisan test --compact tests/Feature/TapTargetTest.php tests/Feature/ButtonFeedbackTest.php tests/Feature/DesignSystemTest.php tests/Feature/ColourContrastTest.php tests/Feature/EmptyStateCoverageTest.php tests/Feature/InertiaLayoutTest.php`
Expected: PASS semua.

- [ ] **Step 7: Rapikan format**

Run: `vendor/bin/pint --format agent tests/Feature/TripReminderTest.php`
Expected: `{"result":"pass"}` atau berkas diperbaiki tanpa galat.

- [ ] **Step 8: Commit**

Commit hanya bila pemilik sudah mengizinkan commit; bila belum, lewati langkah ini dan catat berkas yang berubah di rekap.

```bash
git add resources/js/components/trips/PengingatTrip.tsx resources/js/pages/Trips/Show.tsx tests/Feature/TripReminderTest.php
git commit -m "feat: kartu pengingat perjalanan di halaman trip (FR-24)"
```

### Task 7: Delapan spesifikasi fitur `docs/fitur/`

**Files:**
- Create: `docs/fitur/01-jelajah-rute.md`, `docs/fitur/02-detail-rute.md`, `docs/fitur/03-pertimbangkan.md`, `docs/fitur/04-perjalanan-persiapan.md`, `docs/fitur/05-kabar-kondisi.md`, `docs/fitur/06-pendakian-progres.md`, `docs/fitur/07-akun-masuk.md`, `docs/fitur/08-konsol-pengelola.md`
- Test: `tests/Feature/PrdReferenceIntegrityTest.php` (tidak diubah)

**Interfaces:**
- Consumes: konstanta `TEMPLAT_FITUR` di `PrdReferenceIntegrityTest` (sembilan heading persis); nama berkas yang dirujuk tabel FR di `PRD.md` (baris FR-01 sampai FR-27 menunjuk `docs/fitur/01` sampai `08`); bahan `features/0X-*.md`, `PRD.md`, spec v3; keadaan kode setelah Tugas 1 sampai 6.
- Produces: delapan berkas, masing-masing satu judul lalu sembilan heading templat dengan isi wajib rencana konsolidasi Tugas 11 (kriteria Given/When/Then yang diselaraskan ke aturan kanonis K2 sampai K5 dan K8, daftar R/BR/§, tabel data nyata dari migrasi, status per sub-fitur dengan rujukan berkas, pemetaan setiap task NgodingPakeAI, keputusan dan konflik).

Aturan menulis untuk langkah 2 sampai 9:
- `PrdReferenceIntegrityTest` memindai `docs/`: setiap `§N` wajib 1 sampai 161, setiap `BR-NN` 01 sampai 16, setiap `FR-NN` 01 sampai 27. Isi di bawah sudah memenuhi; bila menambah rujukan, periksa dulu heading-nya di `PRD.md`.
- Tanpa em dash; rentang ditulis dengan kata "sampai".
- Status adalah FAKTA dari berkas yang disebut, diperiksa 26 September 2026 dan diasumsikan Tugas 1 sampai 6 sudah selesai. Bila sebuah berkas yang disebut ternyata sudah pindah, tulis jalur nyatanya; jangan menulis status yang tidak dapat ditunjuk berkasnya (R-023).
- Status task NgodingPakeAI di server tidak diperiksa ulang untuk dokumen ini (butuh CLI dan izin pemilik). Kolom "Status" adalah keadaan kode, bukan status di server.

- [ ] **Step 1: Jalankan test kontrak dan lihat kegagalannya**

Run: `php artisan test --compact tests/Feature/PrdReferenceIntegrityTest.php`
Expected: 6 test, 5 lulus, 1 gagal: `docs/fitur/ harus berisi tepat delapan spesifikasi. Failed asserting that actual size 0 matches expected size 8.`

- [ ] **Step 2: Tulis `docs/fitur/01-jelajah-rute.md`**

````markdown
# 01 Jelajah rute

**FR utama:** FR-04 Route Fit Engine (§24), FR-05 Hasil rekomendasi (§32), FR-21 Arsitektur informasi (§145), FR-27 Pencarian kalimat sederhana (§152).
**Bahan:** `features/01-jelajah-rute.md` (NgodingPakeAI), `PRD.md`, spec `docs/superpowers/specs/2026-09-25-memount-v3-prototype-design.md` §6.2.
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna

Pertanyaan pengguna: "Apa yang tersedia, dan mana yang relevan untuk saya?" (§145).

Jelajah adalah titik awal menemukan jalur lewat daftar, peta, pencarian, dan saringan. Yang dipilih adalah jalur, bukan gunung (R-001, R-002). Setiap kandidat membawa label kecocokan beserta alasannya, sehingga pendaki memilih dengan yakin. Jelajah menyajikan kandidat dan alasannya; ia tidak memutuskan untuk pendaki.

## Posisi di loop dan permukaan

- **Loop (§7):** langkah menemukan dan memilih rute, sesudah profil dan tujuan, sebelum rencana perjalanan.
- **Tab:** Jelajah. Rute `/trails` (daftar dan peta), `/trails/{slug}` (spesifikasi 02), `/trails/saved` (spesifikasi 03). Rekomendasi dari tujuan pendakian (`/goals/create`, `/recommendations/{run}`) dicatat di sini karena FR-05, walau pintunya di tab Trip.
- **Layar:** ponsel memilih Daftar atau Peta; layar lebar menampilkan keduanya berdampingan (spec v3 §6.2, prototype 02 dan 15).
- **Community:** laporan kondisi dibuka lewat detail jalur, bukan dari Jelajah langsung (§145).

## Kriteria penerimaan

1. **Daftar.** Given pendaki sudah masuk, When membuka `/trails`, Then tampil jalur terbit dengan nama, gunung dan provinsi, durasi, jarak, tanjakan, dan tuntutan teknis berlabel kata, dua belas per halaman.
2. **Angka yang hilang.** Given jalur tanpa jarak atau tanjakan tercatat, When barisnya tampil, Then nilai itu ditulis belum diketahui, tidak pernah 0 (R-007).
3. **Peta.** Given ada jalur terbit bergeometri, When pendaki memilih tampilan Peta, Then hanya jalur bergeometri yang digambar sebagai garis; titik gunung tidak menggantikan jalur (DK-06), dan jumlah jalur yang belum tergambar disebut.
4. **Saringan.** Given pendaki mengisi saringan jarak, durasi, wilayah, atau tuntutan teknis, When menerapkannya, Then daftar dan hitungannya berubah tanpa kehilangan posisi, dan setiap saringan dapat dilepas.
5. **Tuntutan teknis belum diketahui.** Given saringan tuntutan teknis rendah aktif, When ada jalur dengan tuntutan teknis UNKNOWN, Then jalur itu tidak masuk hasil saringan dan tidak diberi label rendah (R-006).
6. **Kalimat sederhana.** Given pendaki mengetik "gunung dekat Malang untuk pemula", When mencari, Then kalimat itu ditafsirkan menjadi saringan yang terlihat dan dapat dilepas, dan teks aslinya tidak dibuang diam-diam (FR-27).
7. **Label kecocokan.** Given profil pendaki lengkap, When kartu tampil, Then labelnya hanya salah satu dari Cocok, Perlu persiapan, Kurang cocok, dengan ikon dan kata, dan "Kenapa?" membuka alasan per faktor tanpa skor atau persen (R-004, R-005, BR-09; K2, K3).
8. **Profil belum lengkap.** Given profil belum lengkap, When Jelajah dibuka, Then tidak ada label yang ditebak; tampil keadaan "Belum dinilai" dan ajakan mengisi profil (R-006, §91).
9. **Jalur menunggu data.** Given jalur belum terbit karena data kritisnya belum lengkap, When Jelajah dibuka, Then jalur itu tidak masuk hasil, hitungan, maupun rekomendasi, dan tampil terpisah sebagai menunggu data tanpa angka (R-008, R-022).
10. **Simpan.** Given pendaki menekan "Simpan rute" pada kartu, When berhasil, Then keadaannya menjadi "Tersimpan", diumumkan ke pembaca layar, dan jalur tetap tersimpan setelah keluar lalu masuk kembali.
11. **Hasil kosong.** Given saringan tidak menyisakan jalur, When daftar kosong, Then tampil penyebabnya dan saringan mana yang sebaiknya dilonggarkan.
12. **Rekomendasi.** Given pendaki mengisi tujuan pendakian, When rekomendasi dihasilkan, Then hasilnya hanya jalur terbit yang lengkap, jalur berstatus CLOSED tidak menjadi rekomendasi aktif (BR-04), dan setiap hasil membawa alasan (BR-08).

## Aturan yang berlaku

- **Kanonis:** R-001, R-002, R-003, R-004, R-005, R-006, R-007, R-008, R-009, R-022.
- **Aturan bisnis:** BR-01, BR-02, BR-03, BR-04, BR-07, BR-08, BR-09.
- **PRD:** §24 sampai §31 (Route Fit), §32 (hasil rekomendasi), §55 (kebijakan peta), §85 (kebijakan AI: pencarian berbasis aturan), §88 (mobile-first), §89, §90, §91 (pengungkapan bertahap, penjelasan, tanpa presisi palsu), §104 (kasus tepi), §110 (syarat terbit), §114 (kriteria penerimaan rekomendasi), §152 (FR-27).
- **Lintas fitur:** tema §157; bahasa §158 (Inggris belum aktif, DK-40).

## Data dan sumber

| Tabel | Kolom yang dipakai | Migrasi |
|---|---|---|
| `mountains` | `name`, `slug`, `province`, `region`, `elevation_mdpl`, `timezone`, `latitude`, `longitude`, `archived_at` | `create_mountains_table`, `add_timezone_to_mountains`, `tambah_koordinat_biasa_ke_gunung` |
| `trails` | `name`, `slug`, `distance_km`, `elevation_gain_m`, `elevation_loss_m`, `estimated_duration_minutes`, `technical_demand`, `terrain_character`, `navigation_complexity`, `water_availability`, `camping_available`, `is_published`, `archived_at`, `data_source_id`, `elevation_profile`; `geometry` (geography LINESTRING lewat `PostGis::addGeography`, hanya PostgreSQL) | `create_trails_table`, `add_elevation_profile_to_trails_table` |
| `official_statuses` | `status`, `scope`, `verified_at`, `expires_at` (jalur CLOSED bukan rekomendasi aktif) | `create_official_statuses_table` |
| `profiles`, `user_experience`, `user_preferences` | masukan Route Fit: tingkat pengalaman, `terrain_experience`, `navigation_experience`, durasi terlama, tanjakan tertinggi, preferensi | `create_profiles_table`, `create_user_experience_table`, `create_user_preferences_table` |
| `recommendation_rules` | `key`, `category`, `weight`, `active`, `engine_version` | `create_recommendation_rules_table` |
| `hiking_goals`, `recommendation_runs`, `recommendation_results` | tujuan pendakian; jejak audit rekomendasi; `label`, `explanation`, `trail_snapshot`. `internal_score` tidak pernah dikirim ke halaman (BR-09) | `create_hiking_goals_table`, `create_recommendation_runs_table`, `create_recommendation_results_table`, `add_trail_snapshot_to_recommendation_results_table` |
| `trail_considerations` | `user_id`, `trail_id` (simpan dari kartu) | `create_trail_considerations_table` |

Sumber luar: peta dasar dari penyedia yang dikonfigurasi di `config/hiking.php` (kunci `map`), bukan dari basis data. Tidak ada geometri jalur resmi yang dipublikasikan (spec v3 §10.1); geometri diisi admin atau ahli berkredensial (spesifikasi 08).

## State

| Keadaan | Perilaku yang dituntut | Keadaan sekarang |
|---|---|---|
| Kosong karena saringan | Penyebab dan saringan yang dapat dilonggarkan | Ada: `EmptyState` "Tidak ada jalur yang cocok" di `resources/js/pages/Trails/Index.tsx` |
| Pertama kali, profil belum lengkap | Tanpa label tebakan; "Belum dinilai" dan ajakan mengisi profil | Ada: tombol "Isi profil sekarang" di `Trails/Index.tsx`; kecocokan tidak dihitung tanpa profil |
| Data jalur belum lengkap | Tidak dihitung, tidak direkomendasikan, tampil terpisah | Ada: daftar `menunggu` dari `TrailIndexController`, kartu `resources/js/components/trails/Menunggu.tsx` |
| Memuat | Kerangka kartu, bukan area kosong | Sebagian: kunjungan parsial Inertia mempertahankan daftar lama; skeleton kartu menyusul W3 |
| Galat peta | Kalimat jujur, daftar tetap tersedia | Ada: pengakuan gagal gaya peta di `resources/js/components/peta/usePetaDasar.ts` |
| Belum ada jalur bergeometri | Penjelasan, daftar tetap tampil | Sebagian: pilihan Peta hanya muncul bila ada data sebaran; kalimat jumlah jalur yang belum tergambar menyusul W3 |
| Luring | Tidak dijanjikan untuk Jelajah | Service worker hanya menyimpan shell dan halaman `/offline` |

## Status implementasi

| Sub-fitur | Status | Bukti |
|---|---|---|
| Peta rute interaktif | Ada | `resources/js/components/trails/PetaJelajah.tsx`, `app/Services/Trails/PetaSebaranJalur.php` (hanya jalur bergeometri, PostGIS), `tests/Feature/TrailIndexMapTest.php`, `tests/Spatial/PetaSebaranJalurTest.php` |
| Daftar dan kartu rute | Ada | `resources/js/pages/Trails/Index.tsx`, `resources/js/components/trails/TrailRow.tsx`, `app/Http/Resources/TrailRowResource.php`, `tests/Feature/TrailRowTest.php`; 12 jalur per halaman (`TrailSearch::PER_HALAMAN`) |
| Pencarian dan saringan | Ada untuk kata kunci, wilayah, jarak, durasi, tuntutan teknis | `app/Services/Trails/TrailSearch.php`, `resources/js/components/trails/FilterForm.tsx`, `tests/Feature/TrailIndexSearchTest.php` |
| Pencarian kalimat (FR-27) | Sebagian (temuan F3) | `TrailSearch` mengenali lima alias kota (Malang, Surabaya, Bandung, Bogor, Semarang) dan membuang teks pencarian ketika alias cocok; chip tafsiran yang dapat dilepas menyusul W3 |
| Label kecocokan dan "Kenapa?" | Ada | `resources/js/components/trails/FitLine.tsx`, `app/Services/TrailFitService.php`, `tests/Feature/AmbientFitTest.php`, `tests/Feature/FactorBarsDoNotLeakScoreTest.php` |
| Ajakan lengkapi profil | Ada | `resources/js/pages/Trails/Index.tsx` ("Isi profil sekarang") |
| Simpan rute | Ada | Rute `trails.timbang`, `app/Http/Controllers/Trails/ConsiderationController.php`, baki `resources/js/components/trails/ConsiderationTray.tsx`, `tests/Feature/ConsiderationTest.php` |
| Ajakan masuk saat menyimpan (tamu) | Berbeda | Seluruh Jelajah di belakang login (grup `auth` di `routes/web.php`); tamu hanya melihat halaman publik `/pendakian/{slug}` |
| Rekomendasi dari tujuan (FR-05) | Ada | `app/Http/Controllers/Goals/GoalController.php`, `app/Http/Controllers/Recommendations/RecommendationController.php`, `resources/js/pages/Recommendations/Show.tsx`, `resources/js/components/discovery/KartuHasil.tsx`, `resources/js/components/discovery/PanelAlasan.tsx`, `tests/Feature/RecommendationFlowTest.php` |

## Task NgodingPakeAI

Catatan 23 September 2026: task 1 sampai 9 ditandai done oleh agen sebelumnya tetapi belum diverifikasi; task 10 selesai dan terverifikasi. Tabel ini memetakan task ke kode yang sudah ada (aturan brownfield).

| # | Task | Peta ke kode | Status kode |
|---|---|---|---|
| 1 | Bangun halaman Jelajah Rute dengan data tiruan | `resources/js/pages/Trails/Index.tsx` dengan data nyata | Ada |
| 2 | Buat peta interaktif sebaran jalur | `PetaJelajah.tsx`, `PetaSebaranJalur` | Ada; versi 22 September yang memakai koordinat gunung (F2) diganti geometri jalur (DK-06) |
| 3 | Buat kartu daftar jalur dengan metadata | `TrailRow.tsx`, `TrailRowResource` | Ada |
| 4 | Buat pencarian kata kunci dan filter jarak waktu kesulitan | `TrailSearch`, `FilterForm.tsx` | Ada |
| 5 | Dukung pencarian kalimat sederhana di frontend | `TrailSearch` (di server, bukan frontend) | Sebagian (F3) |
| 6 | Tampilkan badge kecocokan pada kartu jalur | `FitLine.tsx` | Ada; label mengikuti R-004 (K3) |
| 7 | Buat panel alasan Kenapa pada badge kecocokan | `FitLine.tsx` | Ada |
| 8 | Tampilkan ajakan lengkapi profil di badge | `Trails/Index.tsx` | Ada |
| 9 | Buat tombol simpan rute pada kartu | `TrailRow.tsx`, rute `trails.timbang` | Ada; versi sesi 22 September diganti `trail_considerations` |
| 10 | Bangun halaman daftar rute tersimpan | `resources/js/pages/Trails/Saved.tsx`, `SavedRoutesController` | Ada (dilanjutkan di spesifikasi 03) |
| 11 | Tambahkan empty state dan saran ubah filter | `Trails/Index.tsx` | Ada |
| 12 | Buat halaman detail jalur dengan ringkasan | Spesifikasi 02 | Ada |
| 13 | Tambahkan ajakan masuk saat menyimpan rute | Tidak ada padanan | Berbeda (Jelajah di belakang login) |
| 14 | Buat skema database jalur dan geometri rute | `create_trails_table` | Ada; skema kanonis adalah migrasi yang sudah berjalan (K16) |
| 15 | Seed data jalur pendakian dengan geometri lengkap | `database/seeders/MvpDatasetSeeder.php` tanpa geometri | Belum ada; data contoh lokal berlabel DATA CONTOH direncanakan W1 (DK-35) |
| 16 | Buat API daftar jalur dengan filter | `TrailIndexController` (props Inertia, bukan API JSON) | Ada, bentuk berbeda |
| 17 | Buat API pencarian kata kunci dan kalimat | `TrailSearch` | Sebagian (F3) |
| 18 | Buat API detail jalur beserta geometri | `TrailDetailController`, `app/Services/Trails/TrailDetailPage.php` | Ada, props Inertia |
| 19 | Buat skema profil dan preferensi pengguna | `create_profiles_table`, `create_user_experience_table`, `create_user_preferences_table` | Ada |
| 20 | Buat layanan skor kecocokan jalur | `app/Services/RouteFitService.php`, `app/Services/RouteFit/CompatibilityScorer.php`, `TrailFitService` | Ada; skor internal tidak tampil (BR-09, K2) |
| 21 | Buat API simpan dan daftar rute tersimpan | `ConsiderationController`, `SavedRoutesController` | Ada |

## Keputusan dan konflik terkait

- **DK-05** simpan longgar, banding sempit (diputuskan atas delegasi pemilik); **DK-06** peta hanya menggambar jalur bergeometri (diputuskan atas delegasi pemilik); **DK-25** lima tab; **DK-35** data contoh hanya SQLite lokal; **DK-40** kontrol yang belum berfungsi tidak ditampilkan.
- **K2** skor kecocokan tidak tampil; **K3** tiga label R-004; **K7** peta bukan antarmuka utama (DK-06); **K16** skema kanonis adalah migrasi; **K20** FR-27 berbasis aturan dengan batas jujur.
- **Temuan:** F2 (peta memakai koordinat gunung, ditutup DK-06), F3 (alias kota, terbuka), F6 (daftar jauh di bawah layar pertama ponsel, redesain W3), F7 (baki simpanan sticky berpotensi menutupi fokus, hipotesis yang belum diverifikasi di peramban).
````

- [ ] **Step 3: Tulis `docs/fitur/02-detail-rute.md`**

````markdown
# 02 Detail rute

**FR utama:** FR-07 Detail jalur (§34), FR-11 Status resmi (§41), FR-12 Cuaca (§44).
**Bahan:** `features/02-detail-rute.md` (NgodingPakeAI), `PRD.md`, spec v3 §6.4.
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna

Pertanyaan pengguna: "Cocokkah jalur ini untuk saya, dan apa yang akan saya hadapi di sana?"

Satu halaman menyatukan ringkasan, peta, pos, profil elevasi, kondisi terkini yang bersumber, laporan pendaki, izin, dan alasan kecocokan, dengan urutan pengungkapan §89. Setiap informasi yang belum ada ditulis jujur, tidak dikarang.

## Posisi di loop dan permukaan

- **Loop (§7):** evaluasi rute sebelum memutuskan dan merencanakan.
- **Tab:** Jelajah. Rute `/trails/{slug}`; jalur belum terbit merender halaman menunggu data (`Trails/Awaiting`). Aksi: simpan, banding, rencanakan trip.
- **Halaman publik:** `/pendakian/{slug}` (Blade, tanpa status resmi dan cuaca, §155); target struktur `/gunung/{slug}/via/{trail}` (§145, diskrepansi D-B).
- **Community:** laporan pendaki termoderasi dengan terima kasih; ikuti gunung untuk kabar di Beranda dan `/kabar`.

## Kriteria penerimaan

1. **Urutan.** Given jalur terbit, When pendaki membuka detailnya, Then tampil empat angka kunci sekali jalan naik, tantangan, kecocokan, profil elevasi, pos, kondisi, laporan, izin, dan sumber, dalam urutan §89.
2. **Angka konsisten dan jujur.** Given nilai yang belum tercatat, When angka tampil, Then ditulis belum diketahui, dan angka yang ada sama dengan kartu daftar dan tabel banding (satu sumber, `TrailFigures`).
3. **Jalur belum lengkap.** Given jalur belum terbit karena data kritis kurang (termasuk geometri di PostgreSQL), When dibuka, Then yang tampil halaman menunggu data yang menyebut apa yang ditunggu, tanpa angka dan tanpa label kecocokan (R-008, R-022, K23).
4. **Peta.** Given jalur bergeometri, When peta tampil, Then garis jalur dan pos berkoordinat digambar; tanpa geometri, tampil kalimat bahwa garis belum dimasukkan dan daftar pos tetap tampil.
5. **Profil elevasi.** Given profil elevasi belum ada, When bagiannya tampil, Then tertulis "Data ketinggian belum tersedia." dan tidak ada grafik kosong.
6. **Pos.** Given pos tercatat, When daftarnya tampil, Then nama, jenis, elevasi, jarak dari awal, dan perkiraan waktu bila ada data tempo; yang belum ada ditulis jujur.
7. **Status resmi.** Given status tercatat, When kondisi tampil, Then Buka, Dibatasi, Tutup, atau Belum diketahui dengan cakupan, sumber, waktu verifikasi, kesegaran, dan kalimat bahwa Buka tidak berarti aman (R-012, R-013, BR-05); tanpa status, Belum diketahui dan tidak dianggap buka (BR-07; K5).
8. **Cuaca.** Given prakiraan BMKG tersedia, When cuaca tampil, Then ada area acuan, waktu ambil, kesegaran, dan sumber (BR-15); bila gagal atau basi, kalimat jujur tanpa angka karangan (§47).
9. **Laporan pendaki.** Given ada laporan, When tampil, Then hanya yang lolos moderasi, ditandai komunitas, terpisah dari status resmi, dengan tanggal mendaki, dan tidak pernah menimpa status resmi (R-010, BR-06).
10. **Kecocokan.** Given profil lengkap, When bagian kecocokan tampil, Then label R-004 dengan definisinya dan faktor per kelompok tanpa skor; tanpa profil, ajakan mengisi profil.
11. **Izin.** Given jalur butuh izin, When bagian izin tampil, Then penyelenggara, jendela pemesanan, kuota bila terverifikasi, dan tautan sistem resmi; Memount menautkan, tidak menjual izin (§153).
12. **Tata letak.** Given layar 360 px, When halaman dibuka, Then satu kolom tanpa gulir horizontal; mulai 1024 px, dua kolom (spec v3 §5.2).

## Aturan yang berlaku

- **Kanonis:** R-001, R-002, R-004, R-005, R-006, R-007, R-008, R-009, R-010, R-011, R-012, R-013, R-022; R-017 (foto komunitas bukan hero, milik `DESIGN.md`).
- **Aturan bisnis:** BR-02, BR-05, BR-06, BR-07, BR-12, BR-14, BR-15.
- **PRD:** §34, §41 sampai §47, §48 sampai §51, §55, §59, §60, §89 sampai §93, §110, §116, §117, §153, §155.

## Data dan sumber

| Tabel | Kolom yang dipakai | Migrasi |
|---|---|---|
| `trails` | angka kunci, karakter medan, `elevation_profile`, `geometry` (PostgreSQL), `data_source_id`, `is_published` | `create_trails_table`, `add_elevation_profile_to_trails_table` |
| `trail_segments` | `sequence`, `name`, `description`, `distance_km`, `elevation_gain_m`, `technical_demand`, `terrain_character`, `geometry` | `create_trail_segments_table` |
| `checkpoints` | `sequence`, `name`, `checkpoint_type`, `elevation_m`, `latitude`, `longitude`, `trail_segment_id` | `create_checkpoints_table`, `add_coordinates_to_checkpoints` |
| `official_statuses` | `statusable`, `scope`, `status`, `source`, `source_url`, `published_at`, `verified_at`, `effective_at`, `expires_at`, `reason` | `create_official_statuses_table` |
| `weather_snapshots` | `reference_area`, `forecast_at`, `weather_description`, `temperature_c`, `humidity_percent`, `wind_speed_kmh`, `analysis_date`, `source`, `fetched_at` | `create_weather_snapshots_table`, `add_forecast_at_to_weather_snapshots`, `drop_trail_id_from_weather_snapshots` |
| `trail_condition_reports`, `report_thanks` | `hike_date`, `condition_tags`, `photo_path`, `note`, `moderation_status`; terima kasih per pendaki | `create_trail_condition_reports_table`, `create_report_thanks_table` |
| `permit_requirements` | `authority`, `booking_url`, `daily_quota`, `booking_opens_days_before`, `booking_closes_days_before`, `guide_required`, `source`, `verified_at` | `create_permit_requirements_table` |
| `data_sources`, `mountain_follows` | sumber data jalur; ikuti gunung | `create_data_sources_table`, `create_mountain_follows_table` |

Sumber luar: prakiraan BMKG per kode wilayah adm4, diambil perintah `weather:refresh` dua kali sehari; peramban tidak memanggil BMKG langsung (§45).

## State

| Keadaan | Perilaku yang dituntut | Keadaan sekarang |
|---|---|---|
| Jalur belum terbit | Halaman menunggu data, tanpa angka dan label | Ada: `TrailDetailController` merender `Trails/Awaiting` |
| Jalur diarsipkan | Tidak dapat dibuka | Ada: 404 (`abort_if` di `TrailDetailController`) |
| Tanpa geometri | Kalimat garis belum dimasukkan, pos tetap tampil | Ada: `tests/Feature/TrailDetailMapTest.php` |
| Tanpa profil elevasi | "Data ketinggian belum tersedia." | Ada: `tests/Feature/ElevationProfileDisplayTest.php` |
| Status UNKNOWN | "Belum diketahui", tidak dianggap buka | Ada: `OfficialStatusValue::UNKNOWN` |
| Cuaca gagal atau basi | Pesan jujur dan sumber, tanpa angka | Ada: cabang `available: false` di `TrailDetailPage::cuaca()` |
| Tanpa laporan terbit | Kalimat dari server | Ada: `komunitas.message` di `resources/js/components/trails/rinci/LaporanKomunitas.tsx` |
| Profil belum lengkap | Ajakan mengisi profil | Ada: "Isi profil sekarang" (`tests/Feature/TrailDetailPageTest.php`) |

## Status implementasi

| Sub-fitur | Status | Bukti |
|---|---|---|
| Ringkasan rute | Ada | `app/Services/Trails/TrailDetailPage.php` (`angkaKunci`, `rincian`), `app/Services/Trails/TrailFigures.php`, `resources/js/pages/Trails/Show.tsx`, `tests/Feature/TrailDetailPageTest.php` |
| Peta dan profil ketinggian | Ada | `resources/js/components/trails/rinci/PetaJalur.tsx`, `resources/js/components/ui/ElevationProfile.tsx`; ruas tercuram diarsir dan keterangan kalimat menyusul W3 |
| Titik penting | Ada | `app/Services/Trails/CheckpointJourney.php`, `app/Services/CheckpointPaceService.php`, `resources/js/components/ui/CheckpointJourney.tsx`, `tests/Feature/CheckpointJourneyTest.php`, `tests/Feature/CheckpointPaceDisplayTest.php` |
| Kondisi dan sumber info | Ada, dengan catatan | `resources/js/components/trails/rinci/Kondisi.tsx`, `resources/js/components/trails/rinci/LabelSumber.tsx`, `app/Services/OfficialStatusService.php`, `app/Services/ConditionAggregatorService.php`, `app/Services/WeatherService.php`. Label kepercayaan memakai `SourceType` (Resmi, Terverifikasi admin, Ahli bersertifikat, Komunitas, Interpretasi sistem), bukan "Pakar" (K6). Prakiraan menampilkan waktu, cuaca, suhu, dan kelembapan untuk delapan titik; angin dan curah hujan belum tampil walau kolom angin ada |
| Laporan pendaki | Ada | `LaporanKomunitas.tsx`, `app/Http/Controllers/Trails/ReportThanksController.php`, `tests/Feature/ReportThanksTest.php` |
| Alasan kecocokan | Ada | `fit.faktor` lewat `app/Support/FaktorKategori.php`, `tests/Feature/AmbientFitTest.php`; halaman penilaian lengkap `/trails/{slug}/kecocokan` menyusul W3 (spec v3 §6.5) |
| Izin resmi | Ada | `perizinan` di `TrailDetailPage`, `tests/Feature/PermitVisibilityTest.php` |
| Jalur belum lengkap | Ada | `Trails/Awaiting`, `tests/Feature/AwaitingDataTest.php` |
| Halaman publik SEO | Ada, rute lama | `app/Http/Controllers/PublicTrailController.php`, `resources/views/public/trail.blade.php` |
| Halaman gunung | Belum ada | Hanya `POST mountains/{mountain}/follow`; `/mountains/{slug}` menyusul W3 (DK-44) |
| Status gunung api PVMBG | Belum ada | DK-43, W8 |

## Task NgodingPakeAI

| # | Task | Peta ke kode | Status kode |
|---|---|---|---|
| 1 | Bangun halaman detail rute dengan data tiruan | `resources/js/pages/Trails/Show.tsx` dengan data nyata | Ada |
| 2 | Buat panel ringkasan rute | `angkaKunci`, `rincian` di `TrailDetailPage` | Ada |
| 3 | Buat peta jalur dan titik akhir | `PetaJalur.tsx` | Ada |
| 4 | Buat grafik profil ketinggian jalur | `ElevationProfile.tsx` | Ada |
| 5 | Buat daftar titik penting di jalur | `CheckpointJourney` | Ada |
| 6 | Buat panel kondisi dan sumber info | `Kondisi.tsx`, `LabelSumber.tsx` | Ada |
| 7 | Buat panel alasan kecocokan faktor | `FaktorKategori`, bagian kecocokan di `Show.tsx` | Ada |
| 8 | Tangani data kosong dengan keterangan jujur | `TrailFigures::BELUM` dan kalimat per bagian | Ada |
| 9 | Terapkan tata letak responsif mobile desktop | `Show.tsx` | Sebagian (dua kolom lengket menyusul W3) |
| 10 | Hubungkan navigasi jelajah ke halaman detail | Seluruh baris `TrailRow` menjadi tautan | Ada |
| 11 | Buat skema dan migrasi tabel jalur | `create_trails_table` | Ada |
| 12 | Buat tabel titik penting dan profil ketinggian | `create_checkpoints_table`, `add_coordinates_to_checkpoints`, `add_elevation_profile_to_trails_table` | Ada |
| 13 | Buat tabel kondisi jalur dan sumber info | `create_official_statuses_table`, `create_trail_condition_reports_table`, `create_data_sources_table`, `create_weather_snapshots_table` | Ada |
| 14 | Buat endpoint detail rute | `TrailDetailController` (props Inertia) | Ada, bentuk berbeda |
| 15 | Buat endpoint profil ketinggian dan titik penting | Props `profilElevasi` dan `pos` | Ada, bentuk berbeda |
| 16 | Buat endpoint kondisi terkini dengan sumber | `ConditionAggregatorService`, props `statusResmi`, `cuaca`, `komunitas` | Ada |
| 17 | Buat layanan alasan kecocokan pengguna | `app/Services/RecommendationExplanationService.php`, `TrailFitService` | Ada |
| 18 | Terapkan validasi rute tanpa geometri | `Trail::publishabilityReport()` (geometri wajib di PostgreSQL), `Trails/Awaiting` | Ada |

## Keputusan dan konflik terkait

- **DK-06** peta hanya jalur bergeometri; **DK-09** hierarki sumber dengan ahli bersertifikat (diputuskan atas delegasi pemilik); **DK-37** foto gunung berlisensi (W8); **DK-43** status gunung api terpisah dan tidak dipetakan ke Buka atau Tutup (W8); **DK-44** halaman gunung terautentikasi (W3).
- **K5** status OPEN, RESTRICTED, CLOSED, UNKNOWN; "waspada" menjadi peringatan kondisi; **K6** label "Pakar" diganti hierarki DK-09; **K8** ambang kesegaran per sumber, bukan tujuh hari untuk semua; **K17** cuaca per area acuan jalur, bukan per gunung; **K23** detail jalur tanpa geometri menjadi halaman menunggu data; **K33** level PVMBG tidak pernah dipetakan ke status akses.
````

- [ ] **Step 4: Tulis `docs/fitur/03-pertimbangkan.md`**

````markdown
# 03 Pertimbangkan

**FR utama:** FR-06 Perbandingan jalur (§33), FR-22 Catatan pribadi (§147), FR-23 Tautan bagikan perbandingan (§148).
**Bahan:** `features/03-pertimbangkan.md` (NgodingPakeAI), `PRD.md`, spec v3 §6.6, §6.7, §9.3.
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna

Pertanyaan pengguna: "Dari jalur yang saya kumpulkan, mana yang paling sesuai dengan rencana saya?"

Pendaki mengumpulkan jalur tanpa batas yang berarti, menandai pertimbangannya sendiri dengan catatan pribadi, lalu membandingkan dua sampai tiga jalur berdampingan tanpa pemenang (R-016). Banding membantu melihat perbedaan; keputusannya tetap milik pendaki.

## Posisi di loop dan permukaan

- **Loop (§7):** antara menemukan rute dan merencanakan trip.
- **Tab:** Simpanan di Jelajah (`/trails/saved`); Banding di Trip (`/trails/compare`), sesuai pemetaan permukaan lama "Pertimbangkan" di §145.
- **Tautan bagikan:** halaman publik baca-saja `/b/{token}` (FR-23), belum dibangun.

## Kriteria penerimaan

1. **Simpan.** Given pendaki menyimpan jalur dari Jelajah atau detail, When membuka Simpanan, Then jalur itu ada, terbaru dulu, dan tetap ada setelah keluar lalu masuk kembali atau di perangkat lain.
2. **Isi kartu.** Given jalur tersimpan, When daftar tampil, Then setiap kartu memuat nama, gunung, durasi, jarak, tanjakan, dan tuntutan teknis; batas teknis simpanan (50) disebut sebelum ditabrak.
3. **Hapus dari simpanan.** Given pendaki menekan "Hapus dari simpanan", When berhasil, Then jalur hilang dari daftar; mengulang aksi tidak mengembalikannya, dan baris pendaki lain tidak pernah tersentuh.
4. **Kosong.** Given belum ada simpanan, When Simpanan dibuka, Then tampil cara menambah jalur dan tautan ke Jelajah.
5. **Pilih untuk banding.** Given dua simpanan atau lebih, When pendaki mencentang, Then hitungan "Dipilih N dari 3" tampil, batas banding dari config (DK-05) ditegakkan sebelum ditabrak, dan jalur yang tidak terbit tidak dapat dicentang dengan alasannya (R-022).
6. **Banding.** Given dua atau tiga jalur dipilih, When Banding dibuka, Then kolom mengikuti urutan pilihan; baris kecocokan (label tanpa skor), waktu, jarak, tanjakan, kecuraman, tuntutan teknis, navigasi, air, berkemah, medan, dan status resmi; yang ditandai hanya apakah sebuah aspek berbeda, tidak pernah mana yang lebih baik (R-016, K2).
7. **Sel kosong.** Given sebuah nilai belum tercatat, When selnya tampil, Then tertulis belum diketahui, bukan 0 atau tebakan.
8. **Pilihan dari tautan lama.** Given `?trails=` berisi jalur yang tidak lagi terbit atau melebihi batas, When Banding dibuka, Then jalur tak terbit tidak ditampilkan dan tidak disebut namanya, yang melewati batas disebut namanya, dan kalimatnya tampil di halaman.
9. **Catatan pribadi.** Given jalur tersimpan, When pemilik menulis, mengubah, atau menghapus catatan, Then catatan paling panjang 500 karakter (dihitung per karakter), kosong berarti menghapus, permintaan tanpa kolom catatan tidak menghapus apa pun, hanya pemilik yang dapat menyentuhnya (404 bagi yang lain), pemilik dan jalur datang dari sesi dan rute, dan penulisan dibatasi laju.
10. **Catatan tampil.** Given catatan ada, When Simpanan dibuka, Then catatan tampil di kartu sebagai teks dan tidak melebarkan halaman di 360 px; catatan tidak pernah tampil di Banding atau tautan bagikan.
11. **Tautan bagikan (FR-23).** Given pendaki membuat tautan dari Simpanan atau Banding, When orang lain membukanya tanpa akun, Then yang tampil jalur yang sama dalam mode baca-saja beserta sumber dan waktu pembaruannya, tanpa catatan dan tanpa label kecocokan pembuatnya; tautan bertoken acak, berkedaluwarsa, dapat dicabut, `noindex`, dan dibatasi laju; tautan tidak valid menampilkan pesan dan cara memperoleh tautan baru.

## Aturan yang berlaku

- **Kanonis:** R-002, R-004, R-005, R-006, R-007, R-008, R-016 (milik `DESIGN.md`), R-022.
- **Aturan bisnis:** BR-07, BR-09.
- **PRD:** §33, §77 (otorisasi tingkat objek), §83 (minimisasi data), §119 (kriteria otorisasi), §145, §147, §148.
- **Privasi:** catatan dihapus bersama akunnya (`docs/KEBIJAKAN-DATA-PRIBADI.md`).

## Data dan sumber

| Tabel atau config | Isi | Asal |
|---|---|---|
| `trail_considerations` | `user_id`, `trail_id`, `note` (paling panjang 500, null bila tidak ada), `created_at` | `create_trail_considerations_table`, `add_note_to_trail_considerations_table` (belum dijalankan di Supabase) |
| `trails`, `official_statuses` | kolom banding dan status resmi | spesifikasi 01 dan 02 |
| `config/hiking.php` | `saved_trails.max` (50), `saved_trails.compare_max` (3), `rate_limits.personal_writes_per_minute` (30) | config |
| Tabel tautan bagikan | hash token, pemilik, daftar jalur, `expires_at` 7 hari, `revoked_at` | usulan spec v3 §9.3; belum ada migrasi |

## State

| Keadaan | Perilaku yang dituntut | Keadaan sekarang |
|---|---|---|
| Belum ada simpanan | Cara menambah dan tautan ke Jelajah | Ada: "Belum ada rute tersimpan" di `resources/js/pages/Trails/Saved.tsx` |
| Satu simpanan | "Simpan satu jalur lagi untuk membandingkan." tanpa centang | Ada |
| Jalur ditarik dari terbit | Tetap tampil dengan catatannya, tidak dapat dicentang, alasannya disebut | Ada: `KartuSimpanan.tsx` |
| Batas banding tercapai | Hitungan dan kalimat batas | Ada: `AksiPilihan.tsx` |
| Catatan ditolak | Pesan di isian, fokus pindah ke isian | Ada: `InputError` dan `fokusGalat` di `KartuSimpanan.tsx` |
| Batas laju tercapai | Pesan jujur | Sebagian: server menjawab 429; pesan khusus di kartu menyusul W3 |
| Banding kosong | Membedakan belum memilih dari belum punya cukup simpanan | Ada: `resources/js/pages/Trails/Compare.tsx` |
| Pilihan dari tautan lama tidak lengkap | Kalimat di halaman | Ada: `pesan` dirender lewat `Alert` di `Compare.tsx` |

## Status implementasi

| Sub-fitur | Status | Bukti |
|---|---|---|
| Daftar simpanan | Ada | `app/Http/Controllers/Trails/SavedRoutesController.php`, `resources/js/pages/Trails/Saved.tsx`, `resources/js/components/trails/banding/KartuSimpanan.tsx`, `ConsiderationService::simpanan()`, `tests/Feature/SavedRoutesTest.php` |
| Pilih untuk banding | Ada (W0) | `resources/js/components/trails/banding/AksiPilihan.tsx`, `resources/js/components/trails/banding/PemilihBanding.tsx`; baki banding mengambang ala prototype 06 menyusul W3 |
| Bandingkan rute | Ada | `app/Http/Controllers/Trails/RouteComparisonController.php`, `app/Services/Trails/RouteComparisonTable.php`, `resources/js/components/trails/banding/TabelBanding.tsx`, `tests/Feature/RouteComparisonTest.php`, `tests/Feature/ComparisonAsCostTest.php`; profil elevasi skala sama, sakelar "Sorot yang berbeda", dan "Jadikan dasar rencana" berbobot setara menyusul W3 |
| Catatan pribadi (FR-22) | Ada (W0) | Rute `trails.saved.note.update` dan `trails.saved.note.destroy`, `app/Http/Controllers/Trails/SavedTrailNoteController.php`, `app/Http/Requests/Trails/UpdateSavedTrailNoteRequest.php`, `ConsiderationService::simpananMilik()` dan `simpanCatatan()`, `app/Policies/TrailConsiderationPolicy.php`, `tests/Feature/SavedTrailNoteTest.php`. Migrasi kolom `note` belum dijalankan di basis data pemilik |
| Bagikan pilihan (FR-23) | Belum ada | Rancangan di spec v3 §9.3; akses tanpa akun menunggu keputusan pemilik (spec v2 P8); tombol bagikan disembunyikan (DK-40) |

## Task NgodingPakeAI

| # | Task | Peta ke kode | Status kode |
|---|---|---|---|
| 1 | Bangun halaman daftar simpanan dengan data tiruan | `Saved.tsx` dengan data nyata | Ada |
| 2 | Buat kartu jalur tersimpan dengan metadata lengkap | `KartuSimpanan.tsx` di atas `TrailRow` | Ada (W0) |
| 3 | Tambah tombol simpan di jelajah dan detail | `TrailRow.tsx`, `resources/js/pages/Trails/Show.tsx`, rute `trails.timbang` | Ada |
| 4 | Implementasi hapus simpanan dan empty state | Rute `trails.saved.remove`, `ConsiderationService::remove()` | Ada |
| 5 | Bangun tabel bandingkan hingga tiga jalur | `TabelBanding.tsx` | Ada; batas 3, bukan 5 (K1) |
| 6 | Buat pemilih jalur dari daftar simpanan | `AksiPilihan.tsx`, `PemilihBanding.tsx` | Ada (W0) |
| 7 | Tampilkan keterangan jujur untuk data kosong | `TrailFigures::BELUM` di `RouteComparisonTable::baris()` | Ada |
| 8 | Buat editor catatan pribadi pada kartu jalur | `KartuSimpanan.tsx` | Ada (W0) |
| 9 | Tampilkan catatan pribadi di daftar simpanan | Props `trails.*.catatan` | Ada (W0) |
| 10 | Buat tombol bagikan dan salin tautan | Tidak ada | Belum ada (FR-23) |
| 11 | Bangun halaman publik hasil bagikan | Tidak ada | Belum ada |
| 12 | Tangani tampilan tautan kedaluwarsa dan tidak valid | Tidak ada | Belum ada |
| 13 | Buat skema dan migrasi jalur simpanan | `create_trail_considerations_table` | Ada |
| 14 | Buat endpoint CRUD jalur simpanan | Rute `trails.timbang`, `trails.saved`, `trails.saved.remove` | Ada |
| 15 | Buat skema dan endpoint catatan pribadi | `add_note_to_trail_considerations_table`, rute catatan | Ada (W0); migrasi belum dijalankan di Supabase |
| 16 | Buat endpoint agregasi data perbandingan jalur | `RouteComparisonTable::susun()` | Ada |
| 17 | Buat skema dan endpoint tautan bagikan | Tidak ada | Belum ada |
| 18 | Buat endpoint publik tautan bagikan | Tidak ada | Belum ada |
| 19 | Tangani validasi kedaluwarsa tautan bagikan | Tidak ada | Belum ada |

## Keputusan dan konflik terkait

- **DK-05** simpan longgar, banding sempit, batas banding 3 (diputuskan atas delegasi pemilik); **DK-07** FR-22 dan FR-23 masuk cakupan; **DK-31** "Jadikan dasar rencana" berbobot setara untuk setiap jalur; **DK-40** tombol bagikan baru tampil setelah FR-23 ada; spec v2 **P8** akses tautan tanpa akun menunggu pemilik.
- **K1** banding 3 menggantikan 5; **K2** tanpa skor kecocokan; **K21** tautan bagikan tanpa akun; **K22** catatan pribadi.
- **Cacat yang ditutup W0:** impor Wayfinder basi membuat "Hapus" di Simpanan mengirim DELETE ke rute yang tidak ada; "Hapus dari perbandingan" memanggil `router.delete` dengan alamat kosong padahal server mengirim tautan GET.
````

- [ ] **Step 5: Tulis `docs/fitur/04-perjalanan-persiapan.md`**

````markdown
# 04 Perjalanan dan persiapan

**FR utama:** FR-03 Tujuan pendakian (§23), FR-08 Rencana perjalanan (§35), FR-09 Rencana persiapan (§36), FR-10 Model kesiapan (§38), FR-24 Pengingat perjalanan (§149).
**Bahan:** `features/04-perjalanan-persiapan.md` (NgodingPakeAI), `PRD.md`, spec v3 §6.8 sampai §6.11.
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna

Pertanyaan pengguna: "Bagaimana saya memilih, merencanakan, dan bersiap?" (§145, tab Trip).

Pendaki menetapkan tanggal, peserta, dan catatan untuk satu jalur, lalu dituntun langkah demi langkah: persiapan, cek kesiapan, dan pengingat menjelang keberangkatan. Kesiapan adalah dukungan keputusan, bukan izin medis atau hukum (R-014, §40).

## Posisi di loop dan permukaan

- **Loop (§7):** Tujuan, lalu Rekomendasi, lalu rencana, Persiapan, dan Cek, sebelum Mendaki.
- **Tab:** Trip. Rute `/goals/create`, `/trips`, `/trips/create`, `/trips/{trip}`, `/trips/{trip}/edit`, `/trips/{trip}/preparation`, `/trips/{trip}/readiness`.
- **Beranda:** kartu perjalanan berikutnya dan tenggat (W5, DK-45).

## Kriteria penerimaan

1. **Buat rencana.** Given jalur terbit, When pendaki mengisi nama, tanggal mulai, tanggal selesai opsional, jam mulai opsional, peserta, jenis trip, dan catatan, Then rencana tersimpan; tanggal selesai sebelum tanggal mulai, rentang melebihi batas config, atau peserta di luar batas ditolak dengan pesan jelas.
2. **Langkah.** Given rencana ada, When halaman trip dibuka, Then langkah berurutan menunjukkan yang sudah selesai dan yang berikutnya; langkah memandu, tidak mengunci.
3. **Ubah dan hapus.** Given pemilik membuka rencananya, When mengubah atau menghapus, Then perubahan langsung terlihat; trip yang sudah berjalan menolak dengan alasan yang disebut sebelum pendaki mencoba; pendaki lain ditolak (§77).
4. **Persiapan.** Given daftar persiapan dibangkitkan dari templat jalur, When pendaki menandai butir, Then keadaannya tersimpan, butir kritis ditandai, dan hitungan butir kritis terlihat; butir yang belum dicentang bukan berarti tidak dimiliki (BR-10).
5. **Kesiapan.** Given cek kesiapan dijalankan, When hasil tampil, Then keadaan kesiapan beserta faktor yang kurang dan hitungan "N dari M butir kritis sudah dikonfirmasi", tanpa skor atau persen (R-005, R-014, K4).
6. **Konfirmasi pra-berangkat.** Given pendaki meninjau semuanya, When mengonfirmasi, Then konfirmasi dicatat dengan waktunya.
7. **Jendela izin.** Given jalur punya aturan izin, When halaman trip dibuka, Then jendela pemesanan relatif tanggal rencana ditulis (belum dibuka, terbuka sampai, sudah tutup) dengan tautan kanal resmi (§153).
8. **Pengingat.** Given trip belum dimulai, When pemilik menyalakan pengingat, Then surel dijadwalkan H-3 dan H-1 pukul 07:00 zona gunung, tidak pernah terkirim dua kali, dan tidak dikirim pada hari keberangkatan; halaman trip menampilkan setiap pengingat dengan waktu, zona, dan keadaan (menunggu, terkirim, dilewati); isi surel tidak menyatakan jalur atau pendaki aman (R-012).
9. **Pengingat setelah trip dimulai.** Given trip sudah dimulai, selesai, atau dibatalkan, When halaman trip dibuka, Then sakelar pengingat tidak ditawarkan dan kalimatnya menjelaskan sebabnya.
10. **Data pendukung kosong.** Given templat persiapan, status resmi, atau prakiraan belum ada, When bagiannya tampil, Then yang belum dinilai disebut jujur, bukan disembunyikan atau dikarang.
11. **Tujuan pendakian.** Given pendaki mengisi tujuan (tanggal, wilayah, jenis trip, durasi, tantangan), When rekomendasi atau rencana dibuat, Then tujuan itu menjadi masukan dan isian awalnya (FR-03).

## Aturan yang berlaku

- **Kanonis:** R-002, R-006, R-007, R-012, R-013, R-014.
- **Aturan bisnis:** BR-04, BR-05, BR-10, BR-11.
- **PRD:** §23, §35, §36, §37, §38, §39, §40, §98 (penjadwal), §102 (matriks test kesiapan), §115 (kriteria penerimaan persiapan), §149, §153, §154 (penjadwal di Vercel).

## Data dan sumber

| Tabel atau config | Kolom atau kunci | Asal |
|---|---|---|
| `trip_plans` | `user_id`, `trail_id`, `hiking_goal_id`, `name`, `planned_date`, `end_date`, `start_time`, `participants_count`, `trip_type`, `notes`, `status`, `reminders_enabled`, `completed_at` | `create_trip_plans_table`, `add_plan_details_to_trip_plans_table` (belum dijalankan di Supabase) |
| `preparation_templates`, `preparation_items` | templat per jalur: `category`, `label`, `description`, `is_critical`, `sort_order`, `applies_when` | `create_preparation_templates_table`, `create_preparation_items_table` |
| `trip_preparation_items` | `category`, `label`, `is_critical`, `status`, `status_updated_at` | `create_trip_preparation_items_table` |
| `readiness_checks` | `computed_state`, `route_fit_snapshot`, `preparation_state`, `official_status_snapshot`, `condition_snapshot`, `explanation`, `pre_departure_confirmed`, `computed_at` | `create_readiness_checks_table` |
| `trip_reminders` | `jenis` (H-3, H-1), `dijadwalkan_untuk`, `sent_at`, `skipped_at` | `create_trip_reminders_table` (belum dijalankan di Supabase) |
| `hiking_goals` | `target_date`, `region`, `trip_type`, `expected_duration_minutes`, `preferred_challenge`, `max_elevation_gain_m`, `notes` | `create_hiking_goals_table` |
| `permit_requirements` | jendela pemesanan dan tautan resmi | `create_permit_requirements_table` |
| `scheduled_task_runs` | pemantauan `trips:kirim-pengingat` | `create_scheduled_task_runs_table` |
| `config/hiking.php` | `trips.max_participants`, `trips.max_span_days`, `reminders.days_before`, `reminders.local_hour`, `rate_limits.personal_writes_per_minute` | config |

## State

| Keadaan | Perilaku yang dituntut | Keadaan sekarang |
|---|---|---|
| Belum ada trip | Kalimat dan jalan ke tujuan pendakian | Ada: "Belum ada rencana trip." di `resources/js/pages/Trips/Index.tsx` |
| Trip tidak dapat diubah atau dihapus | Alasan disebut sebelum mencoba | Ada: `kelola` di `TripController::show()`, `resources/js/components/trips/rencana/HapusRencana.tsx` |
| Waktu pengingat sudah lewat | Kalimat jujur | Ada: `pengingat.catatan` dari `TripReminderScheduler::untukHalaman()` |
| Trip sudah dimulai | Sakelar pengingat tidak ditawarkan | Ada (W0): `resources/js/components/trips/PengingatTrip.tsx` |
| Kesiapan basi | Hasil lama ditandai | Ada: `tests/Feature/StaleReadinessTest.php` |
| Galat validasi rencana | Pesan per isian | Ada: `StoreTripRequest`, `UpdateTripRequest` |

## Status implementasi

| Sub-fitur | Status | Bukti |
|---|---|---|
| Rencana perjalanan | Ada | `app/Http/Controllers/Trips/TripController.php`, `app/Services/Trips/TripLifecycle.php`, `app/Http/Requests/Trips/StoreTripRequest.php`, `app/Http/Requests/Trips/UpdateTripRequest.php`, `app/Http/Requests/Trips/ValidatesTripPlanDetails.php`, `resources/js/pages/Trips/Create.tsx`, `Edit.tsx`, `Show.tsx`, `tests/Feature/TripLifecycleTest.php`, `tests/Feature/TripPlanDetailTest.php`, `tests/Feature/TripOwnershipTest.php` |
| Langkah berurutan | Ada | `app/Services/Trips/TripSteps.php`, `resources/js/components/trips/LangkahTrip.tsx` |
| Checklist perlengkapan | Ada | `app/Http/Controllers/Trips/PreparationController.php`, `app/Services/PreparationService.php`, `resources/js/pages/Trips/Preparation.tsx`; isi awal dari perlengkapan pribadi menyusul W4 dan W7 |
| Cek kesiapan | Ada, label lama | `app/Http/Controllers/Trips/ReadinessController.php`, `app/Services/ReadinessService.php`, `app/Services/Readiness/ReadinessAssessment.php`, `app/Services/Trips/PreDepartureCheck.php`, `resources/js/pages/Trips/Readiness.tsx`, `app/Http/Resources/TripReadinessResource.php`, `tests/Feature/ReadinessDashboardTest.php`. Hitungan tampil sebagai "N dari M item dikonfirmasi" ditambah "K item kritis belum dikonfirmasi"; hitungan khusus butir kritis ala prototype 10 menyusul W4. `ReadinessState` masih berlabel "Siap" dan "Tidak direkomendasikan" (diganti DK-48 di W4) |
| Jendela izin | Ada | `app/Services/PermitService.php`, `tests/Feature/TripPermitWindowTest.php` |
| Pengingat perjalanan (FR-24) | Ada; tampilan di halaman trip sejak W0 | `app/Services/Trips/TripReminderScheduler.php`, `app/Console/Commands/SendTripReminders.php` (tiap jam di `routes/console.php`), `app/Notifications/TripReminderNotification.php`, `app/Http/Controllers/Trips/TripReminderController.php`, `PengingatTrip.tsx`, `tests/Feature/TripReminderTest.php`; dipantau `SchedulerHealthService::TUGAS` (W0). Syarat produksi: penjadwal benar-benar berjalan dan driver surel nyata (K11, K24) |
| Tujuan pendakian (FR-03) | Ada | `app/Http/Controllers/Goals/GoalController.php`, `app/Http/Requests/StoreGoalRequest.php`, `resources/js/pages/Goals/Create.tsx`, `tests/Feature/GoalInputIsUsedTest.php` |
| Indeks trip sebagai ruang kerja | Sebagian | `Trips/Index.tsx` berupa daftar berhalaman; kartu trip terdekat dan kelompok Berlangsung, Mendatang, Selesai menyusul W4 (spec v3 §6.9) |

## Task NgodingPakeAI

| # | Task | Peta ke kode | Status kode |
|---|---|---|---|
| 1 | Bangun halaman utama rencana perjalanan | `resources/js/pages/Trips/Index.tsx`, `Show.tsx` | Ada |
| 2 | Buat form rencana tiket dengan mock | `resources/js/pages/Trips/Create.tsx`, `resources/js/components/trips/rencana/IsianRencana.tsx` (data nyata; "tiket" dibaca sebagai rencana trip) | Ada |
| 3 | Validasi tanggal rencana mulai selesai | `ValidatesTripPlanDetails` | Ada |
| 4 | Tampilkan rencana sebagai langkah berurutan | `TripSteps`, `LangkahTrip.tsx` | Ada |
| 5 | Buat tampilan checklist perlengkapan mock | `Trips/Preparation.tsx` | Ada |
| 6 | Interaksi centang perlengkapan dan hitung sisa | Rute `trips.preparation.update` | Ada |
| 7 | Bangun halaman skor kesiapan diri | `Trips/Readiness.tsx` | Berbeda: keadaan dan faktor, bukan skor (K4) |
| 8 | Rincian faktor kesiapan dan keterangan jujur | `app/Http/Resources/TripReadinessResource.php` | Ada |
| 9 | Buat halaman pengingat perjalanan mock | `PengingatTrip.tsx` di halaman trip, bukan halaman terpisah | Ada (W0) |
| 10 | Toggle pengingat dan daftar status | Rute `trips.reminders.update`, `PengingatTrip.tsx` | Ada (W0) |
| 11 | Buat tabel rencana perjalanan dan migrasi | `create_trip_plans_table`, `add_plan_details_to_trip_plans_table` | Ada |
| 12 | API CRUD rencana dan validasi tanggal | Rute `trips.*`, `TripLifecycle` | Ada |
| 13 | Skema tabel perlengkapan dan status siap | `create_trip_preparation_items_table` | Ada |
| 14 | API checklist perlengkapan perjalanan | `PreparationController` | Ada |
| 15 | Skema data kebugaran dan pengalaman pengguna | `create_user_experience_table` | Berbeda: pengalaman diisi sendiri; kebugaran tidak dinilai (K4) |
| 16 | Service hitung skor kesiapan dan faktor | `ReadinessService`, `ReadinessAssessment` | Berbeda: keadaan, bukan skor |
| 17 | API pengingat dan jadwal keberangkatan | `TripReminderScheduler::jadwal()` dan `untukHalaman()` | Ada |
| 18 | Job pengirim pengingat email dan notifikasi | `SendTripReminders` (surel sinkron) | Ada untuk surel; notifikasi dorong menunggu keputusan pemilik (spec v3 §15 Q6) |
| 19 | Penanganan data pendukung belum tersedia | Kalimat jujur di `PreparationService` dan `ReadinessAssessment` | Ada |

## Keputusan dan konflik terkait

- **DK-07** FR-24 dan detail rencana (tanggal selesai, peserta) masuk cakupan; **DK-45** Beranda berfase; **DK-48** label kesiapan "Persiapan kritis lengkap", "Masih perlu persiapan", "Tidak disarankan dengan rencana ini" (W4); **DK-50** alasan pembatalan (W7).
- **K4** keadaan kesiapan dan faktor, bukan skor kebugaran; **K11** pengingat butuh driver surel dan penjadwal produksi; **K12** peserta dan tanggal selesai sebagai kolom aditif; **K24** penjadwal di Vercel lewat Cron yang memanggil endpoint terautentikasi.
````

- [ ] **Step 6: Tulis `docs/fitur/05-kabar-kondisi.md`**

````markdown
# 05 Kabar dan kondisi

**FR utama:** FR-11 Status resmi (§41), FR-12 Cuaca (§44), FR-13 Laporan kondisi jalur (§48).
**Bahan:** `features/05-kabar-kondisi.md` (NgodingPakeAI), `PRD.md`, spec v3 §6.4, §7, §10.3.
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna

Pertanyaan pengguna: "Bagaimana kondisi jalur yang saya pertimbangkan sekarang, dan dari mana informasinya?"

Status resmi, prakiraan cuaca, dan laporan pendaki tampil terpisah menurut asalnya, masing-masing dengan sumber dan waktu pembaruannya, supaya pendaki menilai sendiri seberapa bisa dipercaya dan seberapa segar informasinya. Kabar adalah intel kondisi jalur, bukan feed sosial (DK-11).

## Posisi di loop dan permukaan

- **Loop (§7):** masukan untuk memilih, merencanakan, dan cek kesiapan; laporan dikirim sesudah mendaki.
- **Lapisan Community (§145):** kondisi dan laporan di detail jalur; kabar gunung yang diikuti di Beranda (W5); halaman lengkap `/kabar` dibuka dari Beranda dan Jelajah, bukan dari tab; formulir laporan `/reports/create` dari ringkasan pendakian di Progres.
- **Moderasi:** antrean di konsol (spesifikasi 08).

## Kriteria penerimaan

1. **Status resmi.** Given status tercatat, When tampil, Then salah satu dari Buka, Dibatasi, Tutup, Belum diketahui, dibedakan bentuk dan kata, bukan warna saja, beserta penerbit, cakupan, waktu terbit, dan waktu verifikasi (K5).
2. **Tanpa status.** Given tidak ada status, When tampil, Then Belum diketahui dan tidak dianggap buka (BR-07, R-006).
3. **Status kedaluwarsa.** Given `expires_at` status lewat, When dibaca, Then status kembali Belum diketahui dan masuk daftar tinjau admin (§98).
4. **Buka bukan jaminan.** Given status Buka, When tampil, Then kalimatnya menyatakan bahwa Buka tidak berarti jalur aman (R-012, BR-05).
5. **Cuaca.** Given prakiraan BMKG tersedia, When tampil, Then ada per waktu dengan area acuan, waktu analisis dan waktu ambil, kesegaran, dan sumber; bila gagal atau basi, kalimat jujur tanpa angka karangan (R-013, BR-15, §47).
6. **Laporan pendaki.** Given laporan dikirim, When belum dimoderasi, Then tidak tampil; When disetujui, Then tampil ditandai komunitas, dengan tag kondisi, tanggal mendaki, nama pelapor, dan foto yang metadata lokasinya sudah dibuang; laporan tidak pernah menimpa status resmi (R-010, BR-06, §50).
7. **Kesegaran.** Given data dari berbagai sumber, When label kesegaran tampil, Then ambangnya per sumber (K8), dan kesegaran laporan dihitung dari tanggal mendaki (§51).
8. **Kirim laporan.** Given pendaki mengirim laporan, When tag belum dipilih, Then pengiriman ditolak dengan pesan; pengiriman dibatasi laju per jam; foto dibatasi ukuran dan dibersihkan.
9. **Terima kasih.** Given laporan terbit, When pendaki lain berterima kasih, Then tercatat satu kali per pendaki, dan pelapor tidak dapat berterima kasih pada laporannya sendiri.
10. **Ikuti gunung.** Given pendaki mengikuti gunung, When membuka `/kabar`, Then kabar dikelompokkan menurut asal (§92).
11. **Tanpa feed sosial.** Given halaman kabar, When tampil, Then tidak ada jenis "cerita", "tips", maupun reaksi jempol (DK-11, K9).

## Aturan yang berlaku

- **Kanonis:** R-006, R-009, R-010, R-011, R-012, R-013; R-017 (milik `DESIGN.md`).
- **Aturan bisnis:** BR-05, BR-06, BR-07, BR-12, BR-13, BR-14, BR-15.
- **PRD:** §41 sampai §51, §57, §59, §60, §81 (unggahan), §82 (privasi lokasi), §92, §93, §97 (cache), §98 (penjadwal), §103 (matriks test laporan komunitas), §116, §117, §118.

## Data dan sumber

| Tabel | Kolom yang dipakai | Migrasi |
|---|---|---|
| `official_statuses` | `statusable` (gunung atau jalur), `scope`, `status`, `data_source_id`, `source`, `source_url`, `published_at`, `fetched_at`, `verified_at`, `effective_at`, `expires_at`, `reason`, `notes`, `recorded_by` | `create_official_statuses_table` |
| `weather_snapshots` | `reference_area`, `forecast_at`, `local_datetime`, `weather_description`, `temperature_c`, `humidity_percent`, `wind_speed_kmh`, `wind_direction`, `cloud_cover_percent`, `visibility_m`, `analysis_date`, `source`, `fetched_at` | `create_weather_snapshots_table`, `add_forecast_at_to_weather_snapshots`, `drop_trail_id_from_weather_snapshots` |
| `trail_condition_reports` | `trail_id`, `trail_segment_id`, `user_id` (kosong setelah akun pelapor dihapus), `hike_date`, `condition_tags`, `photo_path`, `note`, `moderation_status`, `moderated_by`, `moderated_at` | `create_trail_condition_reports_table`, `anonymise_reports_on_user_delete` |
| `report_thanks`, `mountain_follows`, `moderation_actions` | terima kasih, ikuti gunung, keputusan moderasi | `create_report_thanks_table`, `create_mountain_follows_table`, `create_moderation_actions_table` |
| `data_sources`, `authorities` | sumber dan penerbit | `create_data_sources_table`, `buat_tabel_otoritas_dan_kredensial_ahli` |

Sumber luar: prakiraan BMKG (kode wilayah adm4) lewat `weather:refresh`; status buka dan tutup terbit sebagai surat Kemenhut atau Balai dan dicatat admin dengan sumbernya (spec v3 §10.1); level PVMBG belum diinput (DK-43, W8). `config/hiking.php`: `rate_limits.report_submissions_per_hour` (5).

## State

| Keadaan | Perilaku yang dituntut | Keadaan sekarang |
|---|---|---|
| Status belum ada | Belum diketahui | Ada: `OfficialStatusValue::UNKNOWN` berlabel "Belum diketahui" |
| Cuaca gagal atau basi | Kalimat dan sumber, tanpa angka | Ada: `WeatherService` mengembalikan `available: false` beserta pesan |
| Laporan menunggu moderasi | Tidak tampil publik; pelapor tahu keadaannya | Ada untuk publik; keadaan terkirim bagi pelapor menyusul W6 (spec v3 §6.13) |
| Belum mengikuti gunung | Bagian kabar tidak tampil kosong | Ada: bagian cuaca `/kabar` hanya tampil bila ada yang diikuti (`EmptyStateCoverageTest`) |
| Foto gagal dibersihkan | Tidak pernah disimpan dengan metadata lokasinya | Ada: foto dibuang dan laporannya tetap tersimpan (`ImageSanitizer::trySanitize()` di `ConditionReportController`) |

## Status implementasi

| Sub-fitur | Status | Bukti |
|---|---|---|
| Kabar resmi | Ada | `app/Services/OfficialStatusService.php`, `resources/js/components/ui/StatusBadge.tsx`, `app/Http/Controllers/Admin/OfficialStatusController.php`, `app/Console/Commands/CheckDataFreshness.php`, `tests/Feature/OfficialStatusTargetTest.php`, `tests/Feature/StatusCacheInvalidationTest.php`, `tests/Feature/DataFreshnessReviewTest.php` |
| Prakiraan cuaca | Sebagian | `app/Services/WeatherService.php`, `app/Console/Commands/RefreshWeatherSnapshots.php`, `tests/Feature/WeatherRefreshTest.php`; tampil waktu, cuaca, suhu, dan kelembapan untuk delapan titik; angin, curah hujan, dan penanda jam berisiko belum tampil |
| Laporan pendaki | Ada | `app/Http/Controllers/Community/ConditionReportController.php`, `app/Http/Requests/StoreConditionReportRequest.php`, `app/Support/ImageSanitizer.php`, `resources/js/pages/Community/ConditionReport.tsx`, `resources/js/components/trails/rinci/LaporanKomunitas.tsx`, `tests/Feature/CommunityReportModerationTest.php`, `tests/Feature/CommunityPhotoTest.php`; saring per jenis cerita atau tips tidak dibangun (DK-11) |
| Sumber dan kesegaran | Ada | `app/Services/DataFreshnessService.php`, `resources/js/components/community/LabelKesegaran.tsx`, `resources/js/components/ui/Freshness.tsx`, `resources/js/components/trails/rinci/LabelSumber.tsx` |
| Kabar gunung yang diikuti | Ada | `app/Http/Controllers/Community/TrailNewsController.php`, `app/Services/TrailNewsService.php`, `app/Services/Community/KabarJalur.php`, `resources/js/pages/Community/TrailNews.tsx`, `app/Http/Controllers/Trails/MountainFollowController.php`, `tests/Feature/TrailNewsTest.php` |
| Terima kasih | Ada | `app/Http/Controllers/Trails/ReportThanksController.php`, `app/Http/Requests/Trails/ThankReportRequest.php`, `tests/Feature/ReportThanksTest.php` |
| Status gunung api PVMBG | Belum ada | DK-43, W8 |

## Task NgodingPakeAI

| # | Task | Peta ke kode | Status kode |
|---|---|---|---|
| 1 | Bangun halaman Kabar & Kondisi dengan data tiruan | `resources/js/pages/Community/TrailNews.tsx` dengan data nyata | Ada |
| 2 | Buat kartu status resmi jalur dengan label warna | `StatusBadge.tsx` (bentuk dan kata, bukan warna saja) | Ada |
| 3 | Buat panel prakiraan cuaca per waktu | `resources/js/components/trails/rinci/Kondisi.tsx` | Sebagian |
| 4 | Buat daftar laporan pendaki dengan penanda kiriman | `LaporanKomunitas.tsx` | Ada |
| 5 | Tambah filter dan urutkan laporan pendaki | Terbaru dulu | Berbeda: jenis cerita dan tips tidak ada (DK-11) |
| 6 | Tampilkan label sumber dan kesegaran info | `LabelSumber.tsx`, `LabelKesegaran.tsx` | Ada |
| 7 | Buat empty state keterangan info tidak tersedia | Status Belum diketahui, cuaca `available: false` | Ada |
| 8 | Rapikan tampilan responsif halaman kabar | `TrailNews.tsx` | Ada; redesain W5 |
| 9 | Buat skema dan migrasi tabel pengumuman resmi | `create_official_statuses_table` | Ada |
| 10 | Buat skema dan migrasi tabel laporan pendaki | `create_trail_condition_reports_table` | Ada |
| 11 | Buat skema dan migrasi data prakiraan cuaca | `create_weather_snapshots_table` dan dua migrasi lanjutannya | Ada |
| 12 | Buat endpoint pengumuman status resmi jalur | `OfficialStatusService` lewat props Inertia | Ada, bentuk berbeda |
| 13 | Buat endpoint prakiraan cuaca dengan metadata kesegaran | `WeatherService` | Ada, bentuk berbeda |
| 14 | Buat endpoint laporan pendaki disetujui dengan filter | `app/Services/ConditionAggregatorService.php` | Ada |
| 15 | Buat service moderasi laporan pendaki | `app/Http/Controllers/Moderation/ModerationQueueController.php` | Ada (rincian di spesifikasi 08) |
| 16 | Isi seed data kabar resmi dan prakiraan awal | `database/seeders/OfficialStatusSeeder.php` | Ada untuk status: status yang berlaku September 2026 dari pemberitaan (ADMIN_VERIFIED, berbatas waktu), gunung tanpa keterangan dibiarkan UNKNOWN; prakiraan diisi `weather:refresh`, bukan seeder |

## Keputusan dan konflik terkait

- **DK-09** hierarki sumber (diputuskan atas delegasi pemilik); **DK-11** tanpa cerita, tips, dan reaksi jempol; **DK-43** status gunung api terpisah (W8); **DK-36** data dinamis saat luring tampil "terakhir diketahui" (W6).
- **K5** empat status termasuk UNKNOWN; **K8** ambang kesegaran per sumber; **K9** tanpa feed sosial; **K17** cuaca per area acuan jalur; **K33** PVMBG tidak dipetakan ke status akses.
````

- [ ] **Step 7: Tulis `docs/fitur/06-pendakian-progres.md`**

````markdown
# 06 Pendakian dan progres

**FR utama:** FR-14 Riwayat pendakian (§52), FR-15 Mode pendakian (§53), FR-25 Target pribadi dan catatan pendakian manual (§150).
**Bahan:** `features/06-pendakian-progres.md` (NgodingPakeAI), `PRD.md`, spec v3 §6.12 sampai §6.14, §10.
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna

Pertanyaan pengguna di tab Mendaki: "Apa yang saya butuhkan di jalur sekarang?" Di tab Progres: "Bagaimana perkembangan saya?" (§145).

Mode lapangan menemani pendaki di jalur dengan tampilan tenang dan kontrol besar: posisi atas izin, pos berikutnya, sisa jarak, dan perkiraan waktu. Sesudah turun, pendakian tercatat di riwayat dan dibandingkan hanya dengan pendakian pendaki itu sendiri, tanpa papan peringkat (R-015).

## Posisi di loop dan permukaan

- **Loop (§7):** Mendaki, lalu Laporan, lalu Riwayat.
- **Tab Mendaki:** `/mendaki` (pintu tab), mode lapangan `/trips/{trip}/hike`.
- **Tab Progres:** `/progres`, `/history`, `/history/{trip}`; laporan kondisi dari ringkasan pendakian (`/reports/create`).

## Kriteria penerimaan

1. **Mulai.** Given trip siap dimulai, When pendaki memulai pendakian, Then mode lapangan terbuka; lokasi hanya diminta di sini, atas persetujuan di perangkat (§82, BR-13).
2. **Mode lapangan.** Given mode lapangan terbuka, When posisi diketahui, Then peta menampilkan jalur dan posisi, pos berikutnya, sisa jarak, dan perkiraan waktu dari data tempo bila ada; kontrol utama besar dan mudah ditekan.
3. **Data tidak cukup.** Given jalur tanpa pos atau posisi belum diketahui, When mode lapangan tampil, Then ditulis jujur ("Titik penting belum tersedia", lokasi belum tersedia), bukan angka kosong atau tebakan.
4. **Rekam jejak.** Given pendaki menyalakan rekam jejak (mati bawaan), When sinyal kembali setelah turun, Then jejak terkirim; pendaki dapat menghapus jejaknya.
5. **Selesai.** Given pendakian berakhir, When pendaki menandai hasil dan catatan pribadi, Then pendakian tercatat di riwayat dengan keadaannya; angka dari data jalur bila tanpa rekaman.
6. **Riwayat.** Given ada pendakian tercatat, When `/history` dibuka, Then daftar memuat tanggal, jalur, dan keadaan, dan setiap pendakian dapat dibuka ringkasannya.
7. **Grafik progres.** Given kurang dari dua pendakian, When `/progres` dibuka, Then grafik tidak digambar dan kalimatnya mengajak mencatat pendakian; dengan dua atau lebih, grafik membandingkan pendakian pendaki dengan pendakiannya sendiri.
8. **Tanpa kompetisi.** Given halaman Progres, When tampil, Then tidak ada peringkat, skor publik, perbandingan dengan pendaki lain, maupun hadiah untuk kecepatan (R-015, §156).
9. **Target pribadi (FR-25).** Given pendaki menetapkan target jarak, tanjakan, atau frekuensi per bulan, When Progres tampil, Then target dibandingkan dengan pendakian sendiri tanpa streak dan tanpa teguran, dan tidak mengubah label kecocokan maupun tingkat pengalaman (BR-11).
10. **Catat manual (FR-25).** Given pendaki mencatat pendakian di luar trip, When tersimpan, Then ditandai diisi sendiri, dapat diperbaiki dan dihapus, dan tidak menaikkan tingkat pengalaman (BR-11).
11. **Batas cakupan.** Given mode lapangan, When dipakai, Then tidak menjanjikan pelacakan latar, deteksi keluar jalur, navigasi luring penuh, atau SOS (§53).

## Aturan yang berlaku

- **Kanonis:** R-006, R-007, R-012, R-014; R-015 (milik `DESIGN.md`).
- **Aturan bisnis:** BR-11, BR-13.
- **PRD:** §52, §53, §54, §55, §82, §83, §84, §120 (kriteria lokasi), §150, §156.

## Data dan sumber

| Tabel | Kolom yang dipakai | Migrasi |
|---|---|---|
| `hiking_sessions` | `trip_plan_id`, `user_id`, `current_checkpoint_id`, `reached_checkpoint_sequence`, `status`, `started_at`, `ended_at`, `location_updated_at` | `create_hiking_sessions_table`, `add_reached_sequence_to_hiking_sessions` |
| `hike_track_points` | `hiking_session_id`, `recorded_at`, `latitude`, `longitude`, `elevation_m`, `accuracy_m` | `create_hike_track_points_table` |
| `hiking_history` | `user_id`, `trip_plan_id`, `trail_id`, `trail_condition_report_id`, `trip_type`, `completion_state`, `preparation_completion_percent`, `personal_notes`, `completed_at` | `create_hiking_history_table` |
| `user_preferences` | `record_track` (mati bawaan) | `add_track_recording_preference` |
| `checkpoints`, `trails` | pos berurutan, koordinat, `elevation_profile`, `geometry` | spesifikasi 02 |
| `personal_targets`, `user_owned_items` | hanya `id` dan `timestamps` (rintisan kosong) | `create_personal_targets_table`, `create_user_owned_items_table` |
| `hiking_history` (sumber manual) | belum ada kolom; migrasi kosong | `add_source_and_recorded_values_to_hiking_history_table` |

Ketiga migrasi rintisan FR-25 tidak boleh dijalankan di basis data pemilik sebelum W7 mengisinya.

## State

| Keadaan | Perilaku yang dituntut | Keadaan sekarang |
|---|---|---|
| Tidak ada trip yang dapat dimulai | Pintu tab menjelaskan dan menawarkan trip lain | Ada: `tests/Feature/TrackTabTest.php` |
| Izin lokasi ditolak | Kalimat jujur; peta dan pos tetap terbaca | Ada di `resources/js/components/trips/usePendakian.ts`; keadaan khusus prototype 11 menyusul W6 |
| Sinyal hilang | Data yang sudah dibuka tetap terbaca; jejak diantrekan | Sebagian: halaman yang sudah dimuat tetap terbaca, antrean jejak di `resources/js/jejak.js`; paket luring menyusul W6 (DK-36) |
| Riwayat kosong | Kalimat dan ajakan | Ada: prop `kosong` dari `HikerProgressController` |
| Grafik kurang dari dua titik | Tidak digambar, dijelaskan | Ada: penjaga `titik.length < 2` di `resources/js/components/community/GrafikTanjakan.tsx` |

## Status implementasi

| Sub-fitur | Status | Bukti |
|---|---|---|
| Peta navigasi langsung | Ada | `app/Http/Controllers/Trips/HikeModeController.php`, `resources/js/pages/Trips/Hike.tsx`, `resources/js/components/trips/PetaPendakian.tsx`, `usePendakian.ts`, `resources/js/components/trips/TataLetakPendakian.tsx`, `tests/Feature/LocationPrivacyTest.php` |
| Titik berikutnya dan sisa waktu | Ada, sebagian | `app/Services/Trips/HikeCheckpointResolver.php`, `app/Services/CheckpointPaceService.php`, `tests/Feature/HikeModeCheckpointTest.php`; akurasi dan umur posisi, konfirmasi tiba di pos, dan rentang waktu bersumber menyusul W6 |
| Tab Mendaki | Ada | `app/Http/Controllers/Trips/TrackController.php`, `resources/js/pages/Track/Index.tsx`, `tests/Feature/TrackTabTest.php` |
| Rekam jejak | Ada | `app/Http/Controllers/HikeTrackController.php`, `resources/js/jejak.js`, `tests/Feature/TrackPreferenceTest.php`, `tests/Feature/HikeTrackOnSummaryTest.php` |
| Catat pendakian selesai | Ada untuk trip | `app/Http/Controllers/Trips/TripLifecycleController.php` (`complete`), `app/Http/Requests/Trips/CompleteTripRequest.php`, `app/Http/Controllers/Community/HikeSummaryController.php`, `app/Services/Community/HasilPendakian.php`, `tests/Feature/HikeSummaryTest.php` |
| Riwayat dan grafik progres | Ada | `app/Http/Controllers/Community/HikingHistoryController.php`, `app/Http/Controllers/Community/HikerProgressController.php`, `app/Services/HikerProgressService.php`, `app/Services/ProgressLadderService.php`, `GrafikTanjakan.tsx`, `tests/Feature/HikerProgressTest.php`, `tests/Feature/HikingHistoryPageTest.php`, `tests/Feature/ProgressLadderTest.php`, `tests/Feature/ProgressLadderOnTrailDetailTest.php` |
| Target pribadi dan catat manual (FR-25) | Belum ada | Migrasi rintisan kosong (lihat Data dan sumber); `tests/Feature/PersonalTargetTest.php`, `OwnedEquipmentTest.php`, `ManualHikeRecordTest.php` hanya membuka `/` dan tidak menguji fitur (W7) |
| Paket lapangan luring | Belum ada | DK-36, spec v3 §10, W6 |

## Task NgodingPakeAI

| # | Task | Peta ke kode | Status kode |
|---|---|---|---|
| 1 | Bangun halaman mode pendakian dengan data tiruan | `resources/js/pages/Trips/Hike.tsx` dengan data nyata | Ada |
| 2 | Buat komponen peta navigasi posisi langsung | `PetaPendakian.tsx`, `usePendakian.ts` | Ada |
| 3 | Buat panel titik berikutnya dan sisa waktu | `HikeCheckpointResolver`, `CheckpointPaceService` | Ada, sebagian |
| 4 | Buat form catat pendakian selesai | Kartu "Selesaikan trip" di `resources/js/pages/Trips/Show.tsx` | Ada untuk trip; catat manual belum (FR-25) |
| 5 | Buat halaman riwayat daftar pendakian | `resources/js/pages/Community/HikingHistory.tsx` | Ada |
| 6 | Buat komponen grafik progres pendakian | `GrafikTanjakan.tsx` | Ada |
| 7 | Buat halaman detail dan kelola catatan pendakian | `resources/js/pages/Community/HikeSummary.tsx` | Sebagian: ubah dan hapus catatan manual menunggu FR-25 |
| 8 | Tambahkan state offline dan keterangan data kosong | `resources/views/offline.blade.php`, `resources/js/jejak.js`, kalimat kosong per bagian | Sebagian (paket luring W6) |
| 9 | Buat skema dan migrasi sesi pendakian | `create_hiking_sessions_table`, `add_reached_sequence_to_hiking_sessions`, `create_hike_track_points_table` | Ada |
| 10 | Buat skema dan migrasi catatan pendakian | `create_hiking_history_table`; kolom sumber manual belum ada | Sebagian |
| 11 | Buat skema titik penting dan ketinggian jalur | `create_checkpoints_table`, `add_coordinates_to_checkpoints`, `add_elevation_profile_to_trails_table` | Ada |
| 12 | Buat endpoint titik penting dan ketinggian jalur | Props mode lapangan dari `HikeModeController` | Ada, bentuk berbeda |
| 13 | Buat endpoint sesi pendakian dan posisi | Rute `trips.start`, `trips.hike.position`, `hike.track` | Ada |
| 14 | Buat endpoint CRUD catatan pendakian | Tidak ada | Belum ada (FR-25) |
| 15 | Buat endpoint ringkasan progres pendakian | `HikerProgressController`, `HikerProgressService` | Ada |

## Keputusan dan konflik terkait

- **DK-07** FR-25 masuk cakupan; **DK-36** paket lapangan luring berisi data stabil; **DK-49** mode lapangan mengikuti tema pengguna; **DK-50** alasan pembatalan dicatat opsional (W7).
- **K13** target bulanan sebagai target pribadi terhadap diri sendiri; **K14** catat pendakian manual tidak menaikkan pengalaman.
- **Menunggu pemilik:** spec v3 §15 Q9 (ambang data dinamis luring), Q10 (kebijakan data di perangkat), Q14 (telaah hukum dan fitur keselamatan).
````

- [ ] **Step 8: Tulis `docs/fitur/07-akun-masuk.md`**

````markdown
# 07 Akun dan masuk

**FR utama:** FR-01 Autentikasi (§20), FR-02 Profil pendaki (§21), FR-26 Verifikasi kode multi-kanal (§151). Tujuan pendakian (FR-03) dicatat di spesifikasi 04 sesuai tabel FR di PRD.
**Bahan:** `features/07-akun-masuk.md` (NgodingPakeAI), `PRD.md`, spec v3 §6.15, §6.16.
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna

Pertanyaan pengguna: "Siapa saya sebagai pendaki, dan apa pengaturan saya?" (§145, Profil).

Satu akun menyimpan pilihan, rencana, dan riwayat pendaki, dan hanya pemiliknya yang dapat melihatnya. Profil pendaki diisi sendiri dan menjadi masukan kecocokan, bukan sertifikasi (§22).

## Posisi di loop dan permukaan

- **Loop (§7):** Profil adalah langkah pertama.
- **Rute tamu:** `/register`, `/login`, `/forgot-password`, `/reset-password/{token}`, `/verify-email`.
- **Rute masuk:** `/onboarding`, `/profile`; Profil dibuka dari menu akun, bukan tab (§145).

## Kriteria penerimaan

1. **Daftar.** Given pengunjung mengisi nama, email, dan kata sandi, When mendaftar, Then akun dibuat dan pendaki masuk; aturan kata sandi dijelaskan sebelum ditolak; email yang sudah terdaftar dijawab dengan pesan yang menuntun.
2. **Masuk.** Given kredensial salah atau email tidak dikenal, When mencoba masuk, Then jawabannya sama ("Email atau kata sandi tidak cocok.") supaya tidak membocorkan akun; percobaan keenam dikunci sementara.
3. **Keluar.** Given pendaki keluar, When sesi berakhir, Then akses di perangkat itu tertutup, dan data yang tersimpan di akun (simpanan, rencana, riwayat) tetap ada.
4. **Lupa kata sandi.** Given pendaki meminta tautan atur ulang, When tautan dipakai sebelum kedaluwarsa, Then kata sandi baru berlaku; jawaban tidak mengungkap keberadaan akun (perilaku persisnya menunggu keputusan pemilik, spec v3 §15 Q5).
5. **Ganti kata sandi.** Given pendaki masuk, When mengganti kata sandi dengan kata sandi lama, Then berlaku, dan isiannya dikosongkan berhasil maupun gagal.
6. **Profil pendaki.** Given pendaki mengisi tingkat pengalaman, pengalaman medan dan navigasi, durasi terlama, tanjakan tertinggi, dan preferensi, When tersimpan, Then kecocokan dihitung dari isian itu; tanpa profil, kecocokan "Belum dinilai" (R-006).
7. **Rekam jejak.** Given pengaturan privasi, When pendaki belum mengubahnya, Then rekam jejak mati (BR-13).
8. **Hapus akun.** Given pendaki menghapus akun dengan kata sandi, When selesai, Then data pribadi dihapus menurut `docs/KEBIJAKAN-DATA-PRIBADI.md` dan laporan komunitasnya dianonimkan.
9. **Tampilan.** Given pengaturan tampilan, When pendaki memilih tema terang, gelap, atau ikuti sistem, Then berlaku tanpa muat ulang (§157); pilihan bahasa disembunyikan sampai terjemahan Inggris ada (§158, DK-40).
10. **Kepemilikan.** Given pendaki lain mengetahui id milik orang lain, When mencoba membaca atau mengubahnya, Then ditolak di server (§77, §119).
11. **Verifikasi kode (FR-26).** Given kanal surel, WhatsApp, atau SMS, When kode dikirim, Then 6 digit, kedaluwarsa paling lama 10 menit, sekali pakai, disimpan ter-hash, dibatasi laju, dan dapat ditempel (menunggu keputusan pemilik, spec v2 P4).

## Aturan yang berlaku

- **Kanonis:** R-006, R-014 (profil bukan izin), R-019 (kunci penyedia hanya di `.env`).
- **Aturan bisnis:** BR-11, BR-13.
- **PRD:** §20, §21, §22, §75, §76, §77, §80, §82, §83, §84, §119, §151, §157, §158.
- **Privasi:** `docs/KEBIJAKAN-DATA-PRIBADI.md` adalah pemilik aturan penghapusan akun dan retensi.

## Data dan sumber

| Tabel atau penyimpanan | Kolom | Migrasi |
|---|---|---|
| `users` | `name`, `email`, `email_verified_at`, `password`, `role` | `create_users_table`, `add_role_to_users_table` |
| `password_reset_tokens`, `sessions` | token atur ulang; sesi | `create_users_table` |
| `profiles` | `experience_level`, `region_preference`, `bio`, `avatar_path`, `completed_at` | `create_profiles_table` |
| `user_experience` | `completed_hikes_count`, `terrain_experience`, `navigation_experience`, `longest_hike_duration_minutes`, `highest_elevation_gain_m`, `notes` | `create_user_experience_table` |
| `user_preferences` | `preferred_duration`, `preferred_trip_type`, `preferred_challenge`, `max_elevation_gain_preference_m`, `region_preference`, `record_track` | `create_user_preferences_table`, `add_track_recording_preference` |
| Cookie peramban | tema dan tampilan (bukan basis data) | `app/Support/ThemePreference.php`, `app/Support/DisplayPreference.php` |

## State

| Keadaan | Perilaku yang dituntut | Keadaan sekarang |
|---|---|---|
| Galat validasi | Pesan di isian, fokus pindah ke isian pertama yang ditolak | Ada: `resources/js/components/akun/fokusGalat.ts` |
| Email belum diverifikasi | Halaman verifikasi dan kirim ulang | Ada: `app/Http/Controllers/Auth/EmailVerificationPromptController.php` |
| Profil belum lengkap | Kecocokan "Belum dinilai" dan ajakan mengisi profil | Ada: `hasCompletedProfile` di props bersama |
| Terlalu banyak percobaan masuk | Pesan kapan boleh mencoba lagi | Ada: `tests/Feature/Auth/AuthenticationTest.php` |

## Status implementasi

| Sub-fitur | Status | Bukti |
|---|---|---|
| Daftar akun | Ada, tanpa akun Google | `app/Http/Controllers/Auth/RegisteredUserController.php`, `app/Http/Requests/Auth/RegisterRequest.php`, `app/Support/PetunjukKataSandi.php`, `resources/js/pages/Auth/Register.tsx`, `tests/Feature/Auth/RegistrationTest.php`; masuk dengan Google belum ada (K10) |
| Masuk dan keluar | Ada | `app/Http/Controllers/Auth/AuthenticatedSessionController.php`, `app/Http/Requests/Auth/LoginRequest.php`, `app/Actions/Logout.php`, `resources/js/pages/Auth/Login.tsx`, `tests/Feature/Auth/AuthenticationTest.php` |
| Profil dan preferensi | Ada | `app/Http/Controllers/Onboarding/ProfileSetupController.php`, `app/Http/Requests/StoreProfileSetupRequest.php`, `resources/js/pages/Onboarding/ProfileSetup.tsx`, `app/Http/Controllers/Profile/ProfileController.php`, `resources/js/pages/Profile/Edit.tsx`, `resources/js/components/akun/`, `tests/Feature/ProfileTest.php`; onboarding tiga langkah dengan turunan medan dan navigasi menyusul W7 (DK-41) |
| Keamanan kata sandi | Ada | `app/Http/Controllers/Auth/PasswordResetLinkController.php`, `app/Http/Controllers/Auth/NewPasswordController.php`, `ProfileController::password`, `tests/Feature/Auth/PasswordResetTest.php`, `tests/Feature/Auth/PasswordUpdateTest.php` |
| Hapus akun | Ada | `ProfileController::destroy`, `app/Http/Requests/Profile/DestroyAccountRequest.php`, `resources/js/components/akun/HapusAkun.tsx`, migrasi `anonymise_reports_on_user_delete` |
| Tema dan tampilan | Ada | `app/Http/Controllers/ThemePreferenceController.php`, `app/Http/Controllers/DisplayPreferenceController.php`, `tests/Feature/ThemePreferenceTest.php`, `tests/Feature/DisplayPreferenceTest.php` |
| Bahasa | Belum ada | Bahasa Inggris ditunda (§158, DK-40) |
| Verifikasi kode multi-kanal (FR-26) | Belum ada | Menunggu keputusan pemilik (spec v2 P4) |

## Task NgodingPakeAI

| # | Task | Peta ke kode | Status kode |
|---|---|---|---|
| 1 | Bangun layout halaman autentikasi dengan data tiruan | `resources/js/layouts/GuestLayout.tsx`, `resources/js/pages/Auth/` | Ada |
| 2 | Buat form daftar akun email & sosial | `resources/js/pages/Auth/Register.tsx` | Sebagian: tanpa akun sosial |
| 3 | Buat halaman masuk dan keluar akun | `resources/js/pages/Auth/Login.tsx`, rute `logout` | Ada |
| 4 | Buat halaman profil & preferensi pendakian | `resources/js/pages/Profile/Edit.tsx`, `resources/js/pages/Onboarding/ProfileSetup.tsx` | Ada |
| 5 | Buat alur atur ulang & ganti kata sandi | `resources/js/pages/Auth/ForgotPassword.tsx`, `ResetPassword.tsx`, `resources/js/components/akun/FormKataSandi.tsx` | Ada |
| 6 | Buat skema tabel pengguna, sesi & preferensi | `create_users_table`, `create_profiles_table`, `create_user_experience_table`, `create_user_preferences_table` | Ada |
| 7 | Buat API registrasi email & kata sandi | `RegisteredUserController` | Ada |
| 8 | Buat API masuk, keluar & manajemen sesi | `AuthenticatedSessionController` | Ada |
| 9 | Terapkan otorisasi kepemilikan data per akun | Policy (`app/Policies/TripPlanPolicy.php`, `TrailConsiderationPolicy.php`, `HikingGoalPolicy.php`), `tests/Feature/TripOwnershipTest.php` | Ada |
| 10 | Integrasi masuk dengan akun Google (OAuth) | Tidak ada | Belum ada (K10) |
| 11 | Buat API profil & preferensi pengguna | `ProfileSetupController`, `ProfileController` | Ada |
| 12 | Buat API atur ulang & ganti kata sandi | `PasswordResetLinkController`, `NewPasswordController`, `ProfileController::password` | Ada |

## Keputusan dan konflik terkait

- **DK-08** verifikasi kode multi-kanal; **DK-17** mode gelap; **DK-20** bahasa Indonesia utama, Inggris menyusul; **DK-40** pilihan bahasa disembunyikan; **DK-41** onboarding menurunkan pengalaman medan dan navigasi (W7); **DK-42** wilayah minat di preferensi (W7).
- **K10** masuk dengan Google mengikuti DK-07 dan DK-08; **K29** Indonesia sebagai bahasa sumber; **K30** mode gelap ditutup dengan bawaan ikuti sistem.
- **Menunggu pemilik:** spec v2 P4 (penyedia kode dan data nomor telepon), spec v3 §15 Q5 (jawaban lupa kata sandi, verifikasi email, 404 atau 403 untuk IDOR).
````

- [ ] **Step 9: Tulis `docs/fitur/08-konsol-pengelola.md`**

````markdown
# 08 Konsol pengelola

**FR utama:** FR-16 Admin (§56), FR-17 Moderasi (§57), FR-18 Pengelolaan sumber data (§58), FR-19 Log audit (§61), FR-20 Analitik (§62).
**Bahan:** `features/08-konsol-pengelola.md` (NgodingPakeAI), `PRD.md`, spec v3 §10.7.
**Diperiksa terhadap kode:** 26 September 2026, setelah gelombang W0. Status adalah FAKTA dari berkas yang disebut (R-023).

## Tujuan dan pertanyaan pengguna

Pertanyaan pengelola: "Data apa yang kurang atau basi, dan bagaimana memperbaikinya dengan sumber yang jelas?"

Admin, moderator, dan ahli berkredensial menjaga data jalur tetap akurat dan bersumber: melengkapi jalur dan pos, mencatat status resmi dan aturan izin, meninjau kiriman komunitas, dan menelusuri setiap perubahan penting.

## Posisi di loop dan permukaan

- **Loop:** di luar loop pendaki; menjaga mutu data yang dibaca setiap langkah loop.
- **Pintu:** dari menu akun sesuai peran (§145); otorisasi tetap di server (§76).
- **Rute:** `/admin/*` (peran admin), `/moderation` (peran moderator), `/jalur/{trail}/geometri` dan `/jalur/{trail}/pos` (admin atau ahli berkredensial untuk kawasannya, lewat Policy), `/kontribusi` (ahli).

## Kriteria penerimaan

1. **Kesehatan data.** Given admin membuka Kesehatan Sistem, When halaman tampil, Then jumlah jalur yang belum lengkap beserta kekurangannya dan tautan ke jalurnya, tugas terjadwal dengan keadaannya (termasuk yang belum pernah berjalan), dan migrasi yang belum dijalankan di basis data yang dipakai; geometri yang tidak dapat diperiksa ditulis tidak diketahui, bukan 0 (R-006).
2. **Gunung dan jalur.** Given admin, When membuat, mengubah, atau mengarsipkan gunung dan jalur, Then tersimpan dengan sumber; jalur hanya dapat diterbitkan bila gerbang publikasi lolos (R-022), dan gerbang itu dijaga di lapisan data, bukan hanya di satu layar.
3. **Geometri.** Given admin atau ahli berkredensial untuk kawasan jalur itu, When mengunggah GPX atau memilih garis dari OpenStreetMap, Then berkas divalidasi, garis dipratinjau sebelum disimpan, pencarian OSM dibatasi laju, dan tanjakan dapat diterapkan dari garis; ahli di luar kawasannya ditolak.
4. **Pos.** Given pengelola berhak, When menambah, mengubah, memindah urutan, atau menghapus pos, Then pos selalu milik jalur di alamatnya (scopeBindings) dan penghapusan yang merusak perjalanan dijaga.
5. **Status resmi.** Given admin mencatat status, When tersimpan, Then sumber, cakupan, dan waktunya tercatat, dan pencatatnya diambil dari sesi, bukan formulir.
6. **Izin dan sumber data.** Given admin, When mencatat aturan izin atau sumber data, Then sumber dan waktu verifikasi tercatat; sumber dapat diverifikasi atau diperdebatkan.
7. **Moderasi.** Given moderator membuka antrean, When memutuskan setuju, tolak, tandai, atau hapus dengan alasan, Then keputusan langsung memengaruhi yang tampil ke publik dan tercatat.
8. **Kredensial ahli.** Given admin memutuskan kredensial ahli, When disetujui, Then cakupan gunungnya menentukan jalur yang boleh disunting ahli itu.
9. **Audit.** Given aksi penting (terbitkan, moderasi, ubah data), When terjadi, Then tercatat pelaku, waktu, nilai sebelum dan sesudah; log dapat disaring per aksi.
10. **Analitik dan studi kegunaan.** Given admin, When membuka analitik atau studi kegunaan, Then angka berasal dari peristiwa yang benar-benar tercatat; hasil uji kegunaan tidak pernah dikarang (R-023).
11. **Kelola peran.** Given admin perlu mengubah peran pengguna, When fitur ini dibangun, Then perubahan tercatat di audit dan langsung berlaku (belum ada; lihat status).

## Aturan yang berlaku

- **Kanonis:** R-008, R-009, R-010, R-011, R-019, R-022, R-023.
- **Aturan bisnis:** BR-01, BR-04, BR-06, BR-07, BR-12, BR-14, BR-16.
- **PRD:** §56, §57, §58, §59, §60, §61, §62, §76, §77, §78, §79, §81, §98, §99, §110, §113, §118, §153, §159.

## Data dan sumber

| Tabel | Isi | Migrasi |
|---|---|---|
| `mountains`, `trails`, `trail_segments`, `checkpoints` | data gunung, jalur, segmen, pos; `trails.contributed_by`, `contributed_credential_id`, `contributed_at` | migrasi dasar, `buat_tabel_otoritas_dan_kredensial_ahli` |
| `official_statuses`, `permit_requirements`, `restricted_areas` | status resmi, aturan izin, area terlarang | `create_official_statuses_table`, `create_permit_requirements_table`, `create_restricted_areas_table` |
| `data_sources` | `source_name`, `source_type`, `source_url`, `retrieved_at`, `verified_at`, `verification_status` | `create_data_sources_table` |
| `authorities`, `authority_mountain`, `expert_credentials`, `credential_mountain` | otoritas pengelola; kredensial ahli dan cakupan gunungnya | `buat_tabel_otoritas_dan_kredensial_ahli` |
| `moderation_actions`, `audit_logs` | keputusan moderasi; jejak `before` dan `after` | `create_moderation_actions_table`, `create_audit_logs_table` |
| `analytics_events`, `usability_sessions`, `scheduled_task_runs` | corong, sesi uji kegunaan dan SUS, riwayat tugas terjadwal | `create_analytics_events_table`, `create_usability_sessions_table`, `create_scheduled_task_runs_table` |
| `users.role` | `hiker`, `moderator`, `admin` | `add_role_to_users_table` |

## State

| Keadaan | Perilaku yang dituntut | Keadaan sekarang |
|---|---|---|
| Tugas terjadwal belum pernah berjalan | Disebut dan menyalakan peringatan | Ada: `SchedulerHealthService`, termasuk `trips:kirim-pengingat` sejak W0 |
| Tidak ada tugas terdaftar | Kalimat yang menyebut akibatnya | Ada: `resources/js/pages/Admin/Health.tsx` |
| Geometri tidak dapat diperiksa (SQLite) | Tidak diketahui, bukan 0 | Ada: `tests/Feature/SystemHealthPageTest.php` |
| Migrasi belum dijalankan | Disebut di halaman kesehatan | Ada: `app/Services/Admin/MigrationStatus.php` |
| Antrean moderasi kosong | Kalimat jelas | Ada: dijaga `EmptyStateCoverageTest` untuk setiap daftar halaman |

## Status implementasi

| Sub-fitur | Status | Bukti |
|---|---|---|
| Ringkasan kesehatan data | Ada | `app/Http/Controllers/Admin/SystemHealthController.php`, `app/Services/Admin/DataHealthSummary.php`, `app/Services/Admin/TrailDataHealth.php`, `MigrationStatus.php`, `app/Services/SchedulerHealthService.php`, `Health.tsx`, `tests/Feature/SystemHealthPageTest.php`, `tests/Feature/SchedulerHealthTest.php` |
| Kelola jalur dan titik | Ada | `app/Http/Controllers/Admin/MountainController.php`, `TrailController.php`, `TrailGeometryController.php`, `CheckpointController.php`, `app/Services/Admin/GeometryDraft.php`, `app/Services/OpenStreetMapTrails.php`, `app/Http/Requests/Admin/GpxUploadRequest.php`, `resources/js/pages/Admin/Trails.tsx`, `TrailGeometry.tsx`, `Checkpoints.tsx`, `tests/Feature/AdminDataManagementTest.php`, `tests/Feature/TrailGeometryImportTest.php`, `tests/Feature/CheckpointManagementTest.php`, `tests/Feature/CheckpointDeletionGuardTest.php`, `tests/Feature/TrailPublishGateTest.php` |
| Verifikasi dan moderasi | Ada | `app/Http/Controllers/Moderation/ModerationQueueController.php`, `app/Http/Requests/Moderation/ModerateReportRequest.php` (alasan opsional sampai 1000 karakter), `resources/js/pages/Moderation/Queue.tsx`, `tests/Feature/ModerationFlowTest.php` |
| Sumber data, status resmi, izin, otoritas | Ada | `app/Http/Controllers/Admin/DataSourceController.php`, `OfficialStatusController.php`, `PermitController.php`, `AuthorityController.php`, `tests/Feature/OfficialStatusPageTest.php`, `tests/Feature/PermitManagerTest.php`, `tests/Feature/AuthorityManagerTest.php` |
| Kredensial ahli dan kontribusi | Ada | `app/Http/Controllers/Admin/CredentialController.php`, `app/Http/Controllers/Community/TrailContributionController.php`, `app/Policies/TrailPolicy.php` (ahli hanya untuk gunung dalam kredensialnya), `tests/Feature/CredentialManagerTest.php`, `tests/Feature/TrailContributionTest.php`, `tests/Feature/ExpertGeometryAccessTest.php` |
| Log audit | Ada | `app/Http/Controllers/Admin/AuditLogController.php`, `app/Services/AuditLogService.php`, `resources/js/pages/Admin/AuditLogs.tsx` (saring per aksi), `tests/Feature/AuditLogChangeTest.php` |
| Analitik dan studi kegunaan | Ada | `app/Http/Controllers/Admin/AnalyticsController.php`, `app/Services/AnalyticsRecorder.php`, `app/Http/Controllers/Admin/UsabilityStudyController.php`, `app/Services/UsabilityStudyService.php`, `tests/Feature/UsabilityStudyPageTest.php` |
| Kelola peran pengguna | Belum ada | Tidak ada rute atau halaman; `users.role` diubah langsung di basis data. Cakupan wilayah ahli sudah ada lewat `credential_mountain` |
| Meja kurator data resmi | Belum ada | DK-36, spec v3 §10.7, W8 |

## Task NgodingPakeAI

| # | Task | Peta ke kode | Status kode |
|---|---|---|---|
| 1 | Bangun halaman utama Konsol Pengelola | `resources/js/components/admin/AdminNav.tsx` dan halaman `resources/js/pages/Admin/` | Ada, tanpa halaman beranda konsol tersendiri |
| 2 | Buat halaman ringkasan kesehatan data | `resources/js/pages/Admin/Health.tsx` | Ada |
| 3 | Buat halaman daftar dan detail jalur | `resources/js/pages/Admin/Trails.tsx` | Ada |
| 4 | Buat form tambah dan edit jalur | `resources/js/components/admin/FormulirJalur.tsx` | Ada |
| 5 | Buat panel kelola titik penting jalur | `resources/js/pages/Admin/Checkpoints.tsx` | Ada |
| 6 | Buat komponen unggah GPX dengan validasi | `resources/js/pages/Admin/TrailGeometry.tsx`, `GpxUploadRequest` | Ada |
| 7 | Buat halaman antrean verifikasi moderasi | `resources/js/pages/Moderation/Queue.tsx` | Ada |
| 8 | Buat modal keputusan setuju atau tolak | Keputusan beralasan lewat `ModerateReportRequest` | Ada, bentuk tidak diperiksa terhadap "modal" |
| 9 | Buat halaman kelola peran pengguna | Tidak ada | Belum ada |
| 10 | Buat halaman catatan audit dengan filter | `resources/js/pages/Admin/AuditLogs.tsx` | Ada |
| 11 | Terapkan penjagaan izin peran di UI | Menu menurut `isAdmin` dan `isModerator` di props bersama; otorisasi tetap di server | Ada |
| 12 | Buat skema dan migrasi data pengelola | `create_audit_logs_table`, `create_moderation_actions_table`, `buat_tabel_otoritas_dan_kredensial_ahli`, `create_data_sources_table` | Ada |
| 13 | Buat endpoint ringkasan kesehatan data | `DataHealthSummary` | Ada |
| 14 | Buat endpoint CRUD jalur dan titik | Rute `admin.trails.*`, `trails.checkpoints.*` | Ada |
| 15 | Buat auth dan middleware izin wilayah | Middleware `role` (`EnsureUserHasRole`) dan `TrailPolicy` dengan `credential_mountain` | Ada |
| 16 | Buat layanan validasi impor GPX | `TrailGeometryController::preview`, `GeometryDraft` | Ada |
| 17 | Buat endpoint antrean dan moderasi kiriman | Rute `moderation.queue`, `moderation.act` | Ada |
| 18 | Buat endpoint kelola peran dan wilayah | Wilayah lewat keputusan kredensial (`admin.credentials.decide`); peran tidak ada | Sebagian |
| 19 | Buat layanan dan endpoint catatan audit | `AuditLogService`, rute `admin.audit` | Ada |
| 20 | Seed data awal dan uji alur end-to-end | `database/seeders/DatabaseSeeder.php`, `MvpDatasetSeeder.php`, `AuthoritySeeder.php`; uji ujung ke ujung dengan Playwright menunggu keputusan pemilik (spec v3 §15 Q17) | Sebagian |

## Keputusan dan konflik terkait

- **DK-09** hierarki sumber dengan ahli bersertifikat; **DK-36** meja kurator data resmi (W8); **DK-37** foto gunung berlisensi (W8); **DK-43** status gunung api (W8).
- **K6** "Pakar" menjadi ahli bersertifikat; **K19** Policy dan Gate dengan enum peran, bukan matriks `role_permissions`; **K34** jumlah gunung seed PERLU KLARIFIKASI lewat query atas izin pemilik.
- **Menunggu pemilik:** spec v3 §15 Q11 (peran kurator dan penerbit mitra, aturan terbit asimetris), Q13 (kemitraan dan izin PVMBG); fitur kelola peran belum punya keputusan cakupan.
````

- [ ] **Step 10: Jalankan test kontrak dan pastikan lulus**

Run: `php artisan test --compact tests/Feature/PrdReferenceIntegrityTest.php`
Expected: PASS, 6 test, 0 gagal. Bila `test_every_section_id_referenced_anywhere_has_an_anchor_in_the_prd` atau `test_every_business_rule_and_feature_id_referenced_anywhere_exists_in_the_prd` gagal, pesan menyebut ID yang tidak ada; perbaiki rujukan di berkas yang baru ditulis, bukan PRD.

- [ ] **Step 11: Pastikan tanpa em dash**

Run: `grep -c $'\xe2\x80\x94' docs/fitur/*.md`
Expected: setiap berkas `:0`.

- [ ] **Step 12: Commit**

Commit hanya bila pemilik sudah mengizinkan commit; bila belum, lewati langkah ini dan catat berkas yang berubah di rekap.

```bash
git add docs/fitur/01-jelajah-rute.md docs/fitur/02-detail-rute.md docs/fitur/03-pertimbangkan.md docs/fitur/04-perjalanan-persiapan.md docs/fitur/05-kabar-kondisi.md docs/fitur/06-pendakian-progres.md docs/fitur/07-akun-masuk.md docs/fitur/08-konsol-pengelola.md
git commit -m "docs: delapan spesifikasi fitur di docs/fitur"
```

### Task 8: Tutup jalur XSS atribusi MapLibre (CVE-2026-85061) tanpa mengubah versi

**Files:**
- Modify: `resources/js/components/peta/usePetaDasar.ts:78-80` (opsi `attributionControl: false`) dan dua kunci lokal `AttributionControl.*` di `TEKS_PETA` yang mati bersama kontrolnya (dibutuhkan test baru; ruling koordinator R9 saat eksekusi 26 September 2026)
- Modify: `tests/Feature/MapConfigTest.php` (daftar kunci yang wajib dilokalkan tidak lagi memuat kontrol yang tidak dipasang; ruling R9)
- Test: `tests/Feature/MapComponentTest.php`

**Interfaces:**
- Consumes: satu-satunya `new maplibregl.Map(` di `resources/js/components/peta/usePetaDasar.ts`; atribusi teks di `resources/js/components/peta/WadahPeta.tsx` (`Peta: {peta.attribution}`, dijaga `MapComponentTest::test_it_carries_the_tile_attribution_from_config`).
- Produces: tidak ada antarmuka baru. Kontrol atribusi bawaan MapLibre (tombol di pojok peta yang merender HTML atribusi lewat `innerHTML`) tidak dipasang lagi di keempat peta.

Keputusan pemilik 26 September 2026 (spec v3 §15 Q20, jawaban "Ya, masukkan ke W0"). CVE-2026-85061 (GHSA-jrc7-96c5-q579, kritis, CVSS 10.0) ada di `DOM.sanitize()` yang dipakai kontrol atribusi bawaan `maplibre-gl` sampai 6.4.0 untuk merender HTML atribusi; atribut berbahaya yang berurutan lolos sanitasi. Keempat peta (`PetaJelajah`, `PetaJalur`, `PetaPendakian`, `PetaGaris`) sudah menampilkan atribusi sebagai teks React lewat `WadahPeta`, jadi kontrol bawaan dimatikan tanpa kehilangan atribusi wajib OpenStreetMap dan OpenTopoMap (`MapConfigTest::test_the_attribution_is_rendered` tetap menjaganya). Versi `maplibre-gl` tetap 4.7.1 (R-020); upgrade ke 6.x diputuskan terpisah sebelum W3, dan `npm audit` tetap melaporkan advisori ini sampai upgrade.

- [ ] **Step 1: Tulis test yang gagal**

Tambahkan ke `tests/Feature/MapComponentTest.php`, tepat sesudah method `test_it_carries_the_tile_attribution_from_config()`:

```php
    /**
     * CVE-2026-85061 (GHSA-jrc7-96c5-q579): kontrol atribusi bawaan MapLibre sampai 6.4.0
     * merender HTML atribusi lewat innerHTML sesudah DOM.sanitize() yang dapat dilewati.
     * Atribusi sudah tampil sebagai teks React di WadahPeta, jadi kontrol bawaan tidak
     * dipasang dan tidak ada atribusi kustom. Peta hanya dibuat di usePetaDasar, sehingga
     * satu opsi menutup keempat peta.
     */
    public function test_the_built_in_attribution_control_that_renders_html_stays_off(): void
    {
        $dasar = File::get(resource_path('js/components/peta/usePetaDasar.ts'));

        $this->assertMatchesRegularExpression('/new maplibregl\.Map\(\{[^}]*?attributionControl: false/s', $dasar);

        $pembuat = [];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            $isi = $berkas->getContents();
            $jalur = str_replace('\\', '/', $berkas->getRelativePathname());

            if (str_contains($isi, 'new maplibregl.Map(')) {
                $pembuat[] = $jalur;
            }

            $this->assertStringNotContainsString('customAttribution', $isi, $jalur);
            $this->assertStringNotContainsString('AttributionControl', $isi, $jalur);
        }

        $this->assertSame(['components/peta/usePetaDasar.ts'], $pembuat);
    }
```

- [ ] **Step 2: Jalankan dan pastikan gagal karena alasan yang benar**

Run: `php artisan test --compact --filter=test_the_built_in_attribution_control_that_renders_html_stays_off`
Expected: FAIL, "Failed asserting that ... matches PCRE pattern" (opsi `attributionControl: false` belum ada).

- [ ] **Step 3: Matikan kontrol atribusi bawaan**

Di `resources/js/components/peta/usePetaDasar.ts`, ganti:

```ts
        const petaIni = new maplibregl.Map({
            container: el,
            style: konfigurasi.styleUrl || {
```

dengan:

```ts
        const petaIni = new maplibregl.Map({
            container: el,
            // Atribusi tampil sebagai teks di WadahPeta. Kontrol bawaan merender HTML atribusi
            // lewat innerHTML, dan sanitasinya dapat dilewati (CVE-2026-85061, sampai 6.4.0).
            attributionControl: false,
            style: konfigurasi.styleUrl || {
```

Opsi diletakkan sesudah `container` dan sebelum objek `style` yang bersarang, sesuai pola test di Step 1.

- [ ] **Step 4: Jalankan dan pastikan lulus**

Run: `php artisan test --compact tests/Feature/MapComponentTest.php tests/Feature/MapConfigTest.php tests/Feature/TrailIndexMapTest.php tests/Feature/TrailDetailMapTest.php tests/Feature/TrailGeometryPreviewMapTest.php`
Expected: PASS semua.

Run: `npx tsc --noEmit`
Expected: selesai tanpa keluaran (opsi `attributionControl?: false | AttributionControlOptions` ada di tipe `MapOptions` MapLibre 4.7).

- [ ] **Step 5: Rapikan format**

Run: `vendor/bin/pint --format agent tests/Feature/MapComponentTest.php`
Expected: `{"result":"pass"}` atau berkas diperbaiki tanpa galat.

- [ ] **Step 6: Commit**

Pemilik memutuskan belum ada commit sampai tonggak M1 (26 September 2026); lewati langkah ini dan catat berkas yang berubah di rekap. Bila kelak diizinkan:

```bash
git commit -m "fix: matikan kontrol atribusi bawaan MapLibre (CVE-2026-85061), atribusi tetap teks" -- resources/js/components/peta/usePetaDasar.ts tests/Feature/MapComponentTest.php
```

### Task 9: Verifikasi gelombang dan `HANDOVER.md` §0

**Files:**
- Modify: `HANDOVER.md` (baris "Terakhir diperbarui" dan bagian 0), berkas milik koordinator
- Modify (Pint saja): `tests/Feature/PersonalTargetTest.php`, `tests/Feature/OwnedEquipmentTest.php`, `tests/Feature/ManualHikeRecordTest.php`, `tests/Feature/PersonalDataWriteGuardTest.php`

**Interfaces:**
- Consumes: seluruh keluaran Tugas 1 sampai 8.
- Produces: bukti gerbang W0 dan `HANDOVER.md` §0 yang memuat angka nyata, sisa, dan keputusan pemilik.

- [ ] **Step 1: Bangkitkan Wayfinder dan periksa tipe**

Run: `php artisan wayfinder:generate`
Expected: `[Wayfinder] Generated actions in ...` dan `[Wayfinder] Generated routes in ...`.

Run: `npx tsc --noEmit`
Expected: selesai tanpa keluaran (0 galat).

- [ ] **Step 2: Build**

Run: `npm run build`
Expected: `vite build` selesai dengan baris `built in ...` tanpa galat. Plugin Wayfinder membangkitkan ulang rute pada `buildStart`; galat impor rute di tahap ini berarti ada impor basi yang terlewat.

- [ ] **Step 3: Suite penuh**

Run: `php artisan test --compact --testsuite=Unit,Feature`
Expected: 1.269 test, 1.268 lulus, 0 gagal, 0 error, 1 dilewati (test PostGIS). Angka itu adalah 1.252 awal ditambah 17 test baru (Tugas 1: 2, Tugas 3: 4, Tugas 4: 5, Tugas 5: 3, Tugas 6: 2, Tugas 8: 1). Bila angkanya berbeda, berhenti dan cari sebabnya dengan skill `superpowers:systematic-debugging` sebelum menulis `HANDOVER.md`; jangan menulis angka yang tidak keluar dari perintah ini.

- [ ] **Step 4: Pint untuk seluruh berkas yang berubah**

Run: `vendor/bin/pint --dirty --format agent`
Expected: berkas diperbaiki tanpa galat. Selain berkas W0, perintah ini membuang impor tak terpakai di empat test rintisan FR-25 (`PersonalTargetTest`, `OwnedEquipmentTest`, `ManualHikeRecordTest`, `PersonalDataWriteGuardTest`); itu perubahan format saja, isinya tetap menunggu W7.

Run: `vendor/bin/pint --dirty --format agent`
Expected: `{"result":"pass"}` (tidak ada lagi yang diubah).

Run: `php artisan test --compact tests/Feature/PersonalTargetTest.php tests/Feature/OwnedEquipmentTest.php tests/Feature/ManualHikeRecordTest.php tests/Feature/PersonalDataWriteGuardTest.php`
Expected: PASS, 4 test.

- [ ] **Step 5: Em dash di berkas yang tidak disapu test**

`DesignDirectionTest` hanya menyapu `.php`, `.css`, dan `.js`. Periksa berkas `.tsx` dan dokumen yang ditulis W0:

Run: `grep -c $'\xe2\x80\x94' resources/js/components/trails/banding/AksiPilihan.tsx resources/js/components/trails/banding/KartuSimpanan.tsx resources/js/components/trails/banding/PemilihBanding.tsx resources/js/components/trails/banding/TabelBanding.tsx resources/js/components/trips/PengingatTrip.tsx resources/js/components/peta/usePetaDasar.ts resources/js/pages/Trails/Saved.tsx resources/js/pages/Trails/Compare.tsx resources/js/pages/Trips/Show.tsx resources/js/pages/Admin/Health.tsx docs/fitur/*.md`
Expected: setiap berkas `:0`.

- [ ] **Step 6: Delivery Gate antislop**

Muat skill `antislop` dan jalankan Delivery Gate-nya terhadap berkas antarmuka dan dokumen yang berubah di W0 (daftar di Step 5). Catat PASS atau FAIL per butir gate beserta bukti `berkas:baris` di rekap. Perhatian khusus: label kabur, kontrol yang tidak berbuat apa-apa (centang banding tidak tampil bila simpanan kurang dari dua; sakelar pengingat tidak tampil bila ditolak server), keadaan kosong dan galat yang menyebut penyebab, dan tidak ada kata "aman" atau "layak berangkat" di teks antarmuka baru. Butir FAIL diperbaiki di berkasnya, lalu Step 3 diulang.

- [ ] **Step 7: Catat yang tidak dapat diverifikasi W0**

Klik-lewat setiap kontrol baru (Tulis, Simpan, Hapus catatan; centang dan Bandingkan; Hapus dari simpanan; Hapus dari perbandingan; sakelar pengingat; peta tanpa tombol atribusi bawaan dengan atribusi teks yang tetap tampil) dan tangkapan 390 px serta 1440 px butuh aplikasi berjalan dengan basis data. `.env` menunjuk Supabase, migrasi `note` dan pengingat kemungkinan belum dijalankan di sana (INFERENCE, belum diverifikasi `migrate:status`), dan data contoh SQLite lokal menunggu Q1. Jangan menjalankan aplikasi terhadap Supabase dan jangan mengklaim hasil peramban; tulis butir ini sebagai sisa di Step 8.

- [ ] **Step 8: Perbarui `HANDOVER.md` §0**

Ganti tanggal pada baris `Terakhir diperbarui:` di kepala berkas dengan tanggal eksekusi (format "26 September 2026") dan biarkan sisa kalimat baris itu. Lalu di bagian 0, sisipkan blok berikut tepat sebelum paragraf yang diawali `**Langkah berikutnya`, dan hapus paragraf `**Langkah berikutnya...**` lama itu (paragraf terakhir blok ini menggantikannya). Bila tanggal eksekusi bukan 26 September 2026, ganti tanggal di kalimat pertama blok dengan tanggal eksekusi.

```markdown
**W0 Stabilisasi selesai (26 September 2026)** menurut rencana
`docs/superpowers/plans/2026-09-26-memount-v3-w0-stabilisasi.md`:

| Butir | Keadaan |
|---|---|
| Suite Unit dan Feature | 1.269 test, 1.268 lulus, 0 gagal, 0 error, 1 dilewati (PostGIS) |
| Celah MapLibre (Q20) | kontrol atribusi bawaan dimatikan di `usePetaDasar.ts`, atribusi tetap teks lewat `WadahPeta`; `npm audit` tetap melaporkan CVE-2026-85061 sampai upgrade 6.x yang diputuskan sebelum W3 |
| `npx tsc --noEmit` | 0 galat terhadap keluaran Wayfinder yang dibangkitkan ulang |
| `npm run build` | sukses |
| Pint | bersih; impor tak terpakai di empat test rintisan FR-25 ikut dibuang |
| FR-22 catatan simpanan | rute `trails.saved.note.update` dan `.destroy`, Form Request, layanan, props, dan editor di kartu Simpanan |
| Props yang dilaporkan mati | `batasBanding`, `jumlahTersimpan`, `pesan`, `pengingat` kini dipakai halaman, begitu juga `pilihan` yang sebelumnya lolos test hanya lewat komentar; tidak ada yang dicabut karena semuanya diikat test lain |
| Penjadwal | `trips:kirim-pengingat` dipantau `SchedulerHealthService::TUGAS` dengan toleransi 3 jam |
| `docs/fitur/` | delapan spesifikasi dengan sembilan heading templat |
| Cacat tersembunyi yang ditutup | impor Wayfinder basi di Simpanan (Hapus mengirim DELETE ke `/trails/compare/{id}` yang tidak ada, dan `tsc` serta build gagal begitu Wayfinder dibangkitkan); "Hapus dari perbandingan" memanggil `router.delete` dengan alamat kosong; halaman Banding membaca `modeTautan` dan `tautanBagikan` yang tidak pernah dikirim |
| Belum diverifikasi | klik-lewat dan tangkapan 390 px serta 1440 px di peramban (menunggu Q1 atau Q2); status task NgodingPakeAI di server |
| Commit | belum, sesuai keputusan pemilik 26 September 2026: diff ditinjau di tonggak M1 (R-018, DK-58) |

**Butuh keputusan pemilik:**
1. Seri commit di M1: seluruh migrasi React (374 entri `git status`) dan perubahan W0 serta WR belum di-commit; ringkasan diff per kelompok disusun di akhir WR untuk diputuskan pemilik.
2. Migrasi yang kemungkinan belum dijalankan di Supabase (INFERENCE: `HANDOVER.md` bagian 6 mencatat migrasi terakhir yang dijalankan `2026_09_21_000100`; verifikasi dengan `php artisan migrate:status` atas izin, R-019) tetapi dipakai fitur yang sudah tampil: `add_note_to_trail_considerations_table` (catatan simpanan), `add_plan_details_to_trip_plans_table`, dan `create_trip_reminders_table` (pengingat). Bila benar belum, halaman Simpanan dan trip galat di basis data pemilik (Q2, R-024). Bila dijalankan sebelum W7, jalankan per berkas dengan `--path` supaya tiga migrasi rintisan FR-25 tidak ikut.
3. Migrasi rintisan FR-25 (`create_personal_targets_table` dan `create_user_owned_items_table` hanya `id` dan `timestamps`; `add_source_and_recorded_values_to_hiking_history_table` kosong) jangan dijalankan di Supabase sebelum W7 mengisinya. Spec v3 §3.2 keliru menyebut skema itu sudah menampung data prototype.
4. Empat test rintisan (`PersonalTargetTest`, `OwnedEquipmentTest`, `ManualHikeRecordTest`, `PersonalDataWriteGuardTest`) hanya membuka `/` dan memberi lulus palsu. Usul: ditandai dilewati dengan alasan sampai W7, atau langsung diganti di W7.
5. Penjadwal produksi harus menjalankan `trips:kirim-pengingat` tiap jam dengan driver surel nyata (K11, K24); sampai itu Kesehatan Sistem menyebutnya "Belum pernah berjalan".
6. Toleransi 3 jam untuk tugas pengingat adalah ambang operasional usulan agen.
7. Rencana induk menaruh UI catatan di W3; W0 memasang editor dasar karena rute tanpa pemakai di antarmuka mengulang kelas cacat "pipeline tanpa konsumen" yang dijaga `NoDeadViewDataTest`. W3 merestilnya dengan primitif dari W1.

**Langkah berikutnya:** WR Struktur repo dan dokumen tim
(`docs/superpowers/plans/2026-09-26-memount-v3-wr-struktur-repo.md`), lalu W1.
```

Bila Step 3 menghasilkan angka lain yang sudah dijelaskan sebabnya, tulis angka keluaran itu di baris "Suite Unit dan Feature", bukan angka di atas.

Run: `grep -c $'\xe2\x80\x94' HANDOVER.md`
Expected: jumlah sama dengan sebelum langkah ini (blok baru tidak menambah em dash).

- [ ] **Step 9: Commit**

Commit hanya bila pemilik sudah mengizinkan commit; bila belum, lewati langkah ini dan catat berkas yang berubah di rekap.

```bash
git add HANDOVER.md tests/Feature/PersonalTargetTest.php tests/Feature/OwnedEquipmentTest.php tests/Feature/ManualHikeRecordTest.php tests/Feature/PersonalDataWriteGuardTest.php
git commit -m "docs: tutup gelombang W0 stabilisasi di HANDOVER"
```

---

## Self-Review

**1. Cakupan W0 di rencana induk**

| Butir induk W0 | Tugas |
|---|---|
| Catatan simpanan FR-22: rute `trails.saved.note.update` dan `.destroy`, controller, Form Request (≤500 karakter, kosong berarti hapus, batas laju), props `catatan` | Tugas 3 (server dan props), Tugas 4 (editor dan `batasCatatan`) |
| `KartuSimpanan.tsx` dan `AksiPilihan.tsx` sesuai `SavedRoutesTest` | Tugas 4 |
| Label navigasi `AuthenticationTest` mengikuti lima tab | Tugas 2 |
| Props mati dipakai halaman atau dicabut | Tugas 4 (`batasBanding`), Tugas 5 (`jumlahTersimpan`, `pesan`, ditambah `pilihan`), Tugas 6 (`pengingat`); semua dipakai, tidak ada yang dicabut |
| `SchedulerHealthService::TUGAS` memuat `trips:kirim-pengingat` | Tugas 1 |
| Delapan spesifikasi `docs/fitur/0[1-8]-*.md` dengan sembilan heading | Tugas 7 |
| Bukti: suite penuh, `tsc`, build, Pint | Tugas 9 Step 1 sampai 4 |
| Keputusan pemilik Q20: penutup celah MapLibre masuk W0 | Tugas 8 |
| Gerbang gelombang: Delivery Gate antislop, `HANDOVER.md` §0 | Tugas 9 Step 6 dan 8 |
| Gerbang gelombang: tangkapan dan klik-lewat | Tidak dapat dipenuhi tanpa Q1 atau Q2; dicatat sebagai sisa (Tugas 9 Step 7), tidak diklaim |

Dua puluh test merah terpetakan: `SavedTrailNoteTest` 12 error dan 1 gagal (Tugas 3 dan 4), `SavedRoutesTest` 3 error (Tugas 4), `AuthenticationTest` 1 (Tugas 2), `NoDeadViewDataTest` 1 (Tugas 4 sampai 6), `SchedulerHealthTest` 1 (Tugas 1), `PrdReferenceIntegrityTest` 1 (Tugas 7).

Penyimpangan dari rencana induk, masing-masing dengan alasan: editor catatan dasar masuk W0 (induk: W3) karena PRD §147 menuntut menulis, mengubah, dan menghapus catatan dan rute tanpa pemakai adalah kelas cacat yang dijaga `NoDeadViewDataTest`; `pilihan` ikut dirender walau tidak dilaporkan test karena ia lolos hanya lewat komentar yang dibuang Tugas 5; tiga cacat tersembunyi (impor Wayfinder basi, `router.delete` di tabel banding, prop hantu `modeTautan` dan `tautanBagikan`) ditutup dengan test regresi; dua fixture penjadwal diperluas (bukan dilonggarkan) karena menyapu `TUGAS`; harapan label di `AuthenticationTest` diganti karena bertentangan dengan keputusan pemilik DK-25.

**2. Pemindaian placeholder**

Tidak ada "TBD", "TODO", "tambahkan validasi", "tangani kasus tepi", atau "mirip tugas N". Setiap langkah kode memuat isi lengkap berkas atau teks pengganti persisnya. Setiap perintah punya keluaran yang diharapkan, termasuk jumlah test per berkas. Angka di `HANDOVER.md` adalah harapan terhitung (1.252 + 17) dengan aturan berhenti bila keluaran berbeda; tanggal ditulis dari tanggal eksekusi dengan format yang disebut.

**3. Konsistensi nama**

- Layanan: `ConsiderationService::simpananMilik(User $user, int $trailId): TrailConsideration` dan `simpanCatatan(TrailConsideration $simpanan, ?string $catatan): ?string` didefinisikan Tugas 3, dipakai `SavedTrailNoteController` Tugas 3, disebut spesifikasi 03.
- Rute: `trails.saved.note.update`, `trails.saved.note.destroy`, `trails.saved.remove`, `trips.reminders.update`, `trails.compare`, `trails.saved`. Impor Wayfinder: `remove` dari `@/routes/trails/saved`; `update` dan `destroy` dari `@/routes/trails/saved/note`; `update` dari `@/routes/trips/reminders`; `compare`, `saved`, `index` dari `@/routes/trails`.
- Komponen dan tipe: `JalurTersimpan`, `AksiPilihan({ dipilih, maks, tersedia })` (Tugas 4, dipakai lagi Tugas 5), `KartuSimpanan({ jalur, batasCatatan, pilihan, onPilih })`, `PemilihBanding({ pilihan, urutan })`, `OpsiBanding`, `KolomJalur.hapus: string`, `KolomJalur.tersimpan: boolean`, `PengingatTrip({ tripId, pengingat })`, `PengingatHalaman`.
- Props dan flash: `trails.*.catatan`, `batasCatatan`, `batasBanding`, `pilihan`, `jumlahTersimpan`, `pesan`, `pengingat`; flash `pengumuman` (Simpanan), `status` dan `penolakan-trip` (trip).
- Nama test di Review Focus sama persis dengan method yang ditulis di Tugas 1, 3, 4, dan 6.

**4. Review Focus**

Kelima baris punya test di tugas pemilik kodenya: markup dan untaian panjang (Tugas 3 dan 4), tamu dan klien rusak (Tugas 3), jalur ditarik dari terbit (Tugas 4), tugas terjadwal yang belum berjalan atau berhenti (Tugas 1), trip yang sudah dimulai (Tugas 6).

**5. Yang tidak dapat diselesaikan W0 sendiri**

Tujuh keputusan pemilik di blok `HANDOVER.md` Tugas 9 Step 8: baseline commit, migrasi yang belum dijalankan di Supabase, migrasi rintisan FR-25, test rintisan yang lulus palsu, penjadwal dan surel produksi, toleransi 3 jam, dan editor catatan yang dimajukan dari W3.
