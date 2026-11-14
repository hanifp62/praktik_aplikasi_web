# Kandidat rilis W9

Pohon kerja sesudah W1 sampai W9 dan Sprint 1 sampai 4 pasca-audit adalah kandidat rilis. Commit, push, migrasi di
Supabase, dan deploy tetap menunggu izin pemilik (R-018, R-019); permintaannya disusun satu kali di akhir gelombang W9.
Dokumen ini memuat gerbang lokal yang dijalankan agen, syarat rilis yang belum dapat dijalankan tanpa izin, urutan
deploy migrasi per berkas, dan ringkasan perubahan per gelombang.

## Gerbang lokal

Diisi dengan keluaran perintah saat verifikasi akhir dijalankan, bukan disalin dari simulasi (R-023). Setiap perintah
artisan selain `php artisan test`, juga `npm run build`, berawalan `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=`.

| Gerbang | Perintah | Hasil |
|---|---|---|
| Suite Unit dan Feature | `php artisan test --compact --testsuite=Unit,Feature` | 3.002 test, 3.001 lulus, 1 dilewati, 0 gagal (21.203 assertion, 56,2 dtk) |
| Suite bawaan (Spatial dilewati tanpa DSN) | `php artisan test --compact` | 3.104 test, 3.001 lulus, 103 dilewati (1 Feature + 102 Spatial tanpa `SPATIAL_TEST_DSN`; menunggu CI PostGIS, syarat rilis 1), 0 gagal |
| Pratinjau | `php artisan test --compact --testsuite=Pratinjau` | 57 test, 57 lulus, 0 gagal (1.137 assertion, 1,9 dtk); mencakup seluruh 57 halaman React (`PreviewCoverageTest`) |
| TypeScript | `node_modules/.bin/tsc --noEmit` | 0 galat |
| Build | `DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= npm run build` | Sukses (840 ms, 891 modul) |
| Ukuran CSS aplikasi, sama dengan build produksi bersih (W2-C10) | ukuran `public/build/assets/app-*.css` sesudah build | `public/build/assets/app-CayOtsKQ.css`: 84.371 B |
| Format PHP | `vendor/bin/pint --format agent` | `{"tool":"pint","result":"passed"}` (tanpa berkas kotor) |
| Tanpa em dash di kode dan dokumen antarmuka (audit 001 dicatat sebagai temuan audit 002) | `LC_ALL=C grep -rl $'\xE2\x80\x94' app resources tests database config DESIGN.md docs/ARCHITECTURE.md docs/fitur docs/audit` | 0 kecocokan di `app`, `resources`, `tests`, `database`, `config`, `DESIGN.md`, `docs/ARCHITECTURE.md`, `docs/fitur`, dan dokumen audit W9 (hanya muncul di 2 berkas arsip `audit-001`) |

## Syarat rilis yang menunggu izin

Semuanya PERLU KLARIFIKASI sampai dijalankan dengan izin. Tidak satu pun boleh ditulis lulus dari suite SQLite (R-024).

1. **Suite Spatial di CI lulus.** `test.yml` menjalankannya di PostGIS sesudah push yang diizinkan. Termasuk kunci baris
   W4 dan W9 (`tests/Spatial/UserRowLockTest.php`, `tests/Spatial/PreparationFirstVisitLockTest.php`), indeks dan CHECK
   Sprint 1 (`tests/Spatial/SchemaConstraintSpatialTest.php`), serta test skema W7 dan W8 (`tests/Spatial/OfficialDataSchemaTest.php`).
2. **Status migrasi Supabase.** `php artisan migrate:status` terhadap Supabase atas izin baca (Q22) sebelum deploy, lalu
   setiap migrasi baru di tabel berikut dipasang per berkas dengan `--path` dan diperiksa lagi sesudahnya. Kode gelombang
   mana pun tidak di-deploy sebelum migrasinya (ruling W3-8b). Baris 1 sampai 14 tercatat sudah dipasang pada 15 Oktober
   2026 (`HANDOVER.md` bagian 0), tetapi tetap wajib dikonfirmasi ulang lewat `migrate:status`; baris 15 sampai 32 belum
   dipasang di Supabase.
3. **Audit dependensi.** `security.yml` menjalankan `composer audit` dan `npm audit`. Upgrade Tailwind 4.3 dan MapLibre
   6.14 sudah masuk pada GR-08 (`a6c8f11`), tetap diverifikasi ulang oleh CI pada saat PR/push.
4. **RLS di migrasi baru.** Seluruh tabel aplikasi sampai 12 Oktober 2026 dinyalakan RLS-nya di migrasi #14; tabel baru
   W8 (`authority_members`, `data_error_reports`, `volcano_statuses`, `mountain_photos`) ikut menyalakan RLS di migrasi
   masing-masing pada PostgreSQL. Tetap diverifikasi di Supabase sesudah migrasi dipasang (Q22).
5. **Pembersihan PII snapshot kondisi lama** (`memount:bersihkan-snapshot-kondisi`, butir izin akhir 18) sebelum atau
   bersama deploy kode.
6. **Proxy tepercaya dan rahasia operasional.** `TRUSTED_PROXIES`, `CRON_SECRET`, dan opsional
   `OPERATIONAL_ALERT_WEBHOOK_URL` diisi sesuai platform deploy; tanpa `TRUSTED_PROXIES`, batas laju per alamat IP di
   balik proxy berlaku untuk semua pengguna sekaligus.
7. **Kurasi status resmi lewat meja kurator** sesudah kurator pertama diberi peran `curator` dan keanggotaan badan resmi
   (Q11).

## Urutan deploy migrasi

Migrasi awal program v3 adalah sesudah `2026_09_21_000100`. Semua migrasi sesudahnya dicantumkan dalam urutan nama
berkas dan dijaga lengkap oleh `tests/Feature/ReleaseMaterialsTest.php`. Baris 1 sampai 14 tercatat sudah dipasang di
Supabase pada 15 Oktober 2026 (`HANDOVER.md` bagian 0, 65 dari 65); baris 15 sampai 32 adalah migrasi baru dari Sprint 1
sampai Sprint 3 (termasuk W7 dan W8) yang menunggu izin pemilik per migrasi.

| # | Berkas | Gelombang | Pemeriksaan sebelum dipasang |
|---|---|---|---|
| 1 | `2026_09_25_000917_add_plan_details_to_trip_plans_table` | M1 / W4 (terpasang 15 Okt) | Konfirmasi `Ran` di `migrate:status` |
| 2 | `2026_09_25_000918_create_trip_reminders_table` | M1 / W4-W5 (terpasang 15 Okt) | Konfirmasi `Ran` di `migrate:status` |
| 3 | `2026_09_25_000919_add_source_and_recorded_values_to_hiking_history_table` | Rintisan FR-25 (terpasang 15 Okt) | Konfirmasi `Ran` di `migrate:status` |
| 4 | `2026_09_25_000920_create_personal_targets_table` | Rintisan FR-25 (terpasang 15 Okt) | Konfirmasi `Ran` di `migrate:status` |
| 5 | `2026_09_25_000921_create_user_owned_items_table` | Rintisan FR-25 (terpasang 15 Okt) | Konfirmasi `Ran` di `migrate:status` |
| 6 | `2026_09_25_002314_add_note_to_trail_considerations_table` | M1 / W3 (terpasang 15 Okt) | Konfirmasi `Ran` di `migrate:status` |
| 7 | `2026_09_28_000100_create_comparison_shares_table` | W3 (terpasang 15 Okt) | Konfirmasi `Ran` di `migrate:status` |
| 8 | `2026_10_07_000100_add_unique_trip_plan_index_to_hiking_sessions_table` | W6 (terpasang 15 Okt) | Konfirmasi `Ran` di `migrate:status` |
| 9 | `2026_10_07_000200_add_pause_to_hiking_sessions_table` | W6 (terpasang 15 Okt) | Konfirmasi `Ran` di `migrate:status` |
| 10 | `2026_10_07_000300_add_cancellation_reason_to_trip_plans_table` | W6 (terpasang 15 Okt) | Konfirmasi `Ran` di `migrate:status` |
| 11 | `2026_10_07_000400_add_recorded_values_to_hiking_history_table` | W6 (terpasang 15 Okt) | Konfirmasi `Ran` di `migrate:status` |
| 12 | `2026_10_07_000500_add_return_and_registration_to_trip_plans_table` | W6 (terpasang 15 Okt) | Konfirmasi `Ran` di `migrate:status` |
| 13 | `2026_10_07_000600_add_last_resumed_at_to_hiking_sessions_table` | W6 (terpasang 15 Okt) | Konfirmasi `Ran` di `migrate:status` |
| 14 | `2026_10_12_020612_aktifkan_rls_di_semua_tabel_aplikasi` | Hardening RLS (terpasang 15 Okt) | Konfirmasi `Ran` di `migrate:status` |
| 15 | `2026_10_15_000100_null_on_delete_moderation_actions_moderator_id` | Sprint 1 (GAP-03) | FK `moderation_actions.moderator_id` menjadi nullable `nullOnDelete` agar hapus akun moderator tidak ditolak |
| 16 | `2026_10_15_000200_add_search_filter_and_fk_indexes` | Sprint 1 (GAP-04) | Indeks pencarian, filter katalog, dan FK; periksa waktu kunci tabel pada jam sepi |
| 17 | `2026_10_15_000300_add_non_negative_checks_and_unique_prep_constraint` | Sprint 1 (GAP-05) | Pastikan tidak ada baris negatif di `trails`/`checkpoints` dan tidak ada duplikat `(trip_plan_id, template_id)` |
| 18 | `2026_10_15_000400_add_source_to_hiking_history_table` | Sprint 2 / W7 | Hitungan riwayat tanpa `trip_plan_id` harus 0 sebelum CHECK dipasang (Konflik W7 nomor 1) |
| 19 | `2026_10_15_000500_complete_personal_targets_table` | Sprint 2 / W7 | Hitungan `personal_targets` harus 0 sebelum kolom dan CHECK dilengkapi |
| 20 | `2026_10_15_000600_complete_user_owned_items_table` | Sprint 2 / W7 | Hitungan `user_owned_items` harus 0 sebelum kolom dilengkapi |
| 21 | `2026_10_15_000700_add_owned_item_to_trip_preparation_items_table` | Sprint 2 / W7 | Aditif (`user_owned_item_id` nullable `nullOnDelete`) |
| 22 | `2026_10_15_000800_add_note_to_trip_preparation_items_table` | Sprint 2 / W7 | Aditif (`note` nullable) |
| 23 | `2026_10_15_000900_add_interest_regions_to_user_preferences_table` | Sprint 2 / W7 | Aditif (`interest_regions` JSON nullable) |
| 24 | `2026_10_15_001000_add_provenance_to_official_statuses_table` | Sprint 3 / W8 | Kueri pemeriksaan Konflik W8 nomor 1 dan 11 sebelum dipasang |
| 25 | `2026_10_15_001100_create_authority_members_table` | Sprint 3 / W8 | Aditif, RLS menyala pada PostgreSQL |
| 26 | `2026_10_15_001200_create_data_error_reports_table` | Sprint 3 / W8 | Aditif, RLS menyala pada PostgreSQL |
| 27 | `2026_10_15_001300_create_volcano_statuses_table` | Sprint 3 / W8 | Aditif, RLS menyala pada PostgreSQL |
| 28 | `2026_10_15_001400_create_mountain_photos_table` | Sprint 3 / W8 | Aditif, RLS menyala pada PostgreSQL |
| 29 | `2026_10_15_001500_make_trail_characteristics_nullable` | Sprint 3 / W8 | Nilai lama tidak diubah; kurasi jalur lama di butir izin akhir 6 |
| 30 | `2026_10_15_001600_clear_copied_elevation_loss_on_trails` | Sprint 3 / W8 | Migrasi data: mengosongkan `elevation_loss_m` yang disalin sama persis dari `elevation_gain_m` |
| 31 | `2026_10_15_001700_add_kind_to_trip_preparation_items_table` | Sprint 3 / W8 | Aditif (`kind` nullable) |
| 32 | `2026_10_15_001800_mark_permit_preparation_items` | Sprint 3 / W8 | Migrasi data: menandai `kind = 'permit'` dan menyelaraskan label butir izin lama |

## Ringkasan perubahan per gelombang dan sprint

| Tahap | Rencana acuan | Cakupan utama | Migrasi baru |
|---|---|---|---|
| W1 sampai W6 (sudah di `main`) | Rencana induk v3 W1 sampai W6 | Fondasi desain, kerangka dua lebar, klaster Jelajah, Trip, Beranda, Mendaki dan shell luring | 14 (terpasang 15 Okt) |
| Sprint 1 (`CRITICAL`) | Rencana pasca-audit Sprint 1 (`GAP-01`..`GAP-06`) | `OfficialStatus::isCurrentlyEffective()`, rute kontribusi ahli React, retensi & hapus akun moderator, indeks & CHECK DB, JSON-LD & noindex duplikat | 3 (#15 sampai #17) |
| Sprint 2 (`HIGH` + W7) | `docs/superpowers/plans/2026-10-08-memount-v3-w7-progres-profil.md` | Anggaran kueri `PreparationService`, `ModerationService`, seluruh Progres & Profil v3 (target, pencapaian, catatan manual, foto profil privat, wilayah minat, turunan pengalaman, perlengkapan pribadi) | 6 (#18 sampai #23) |
| Sprint 3 (`MEDIUM` + W8) | `docs/superpowers/plans/2026-10-08-memount-v3-w8-data-resmi.md` | Meja kurator dengan terbit asimetris & orang kedua, arsip bukti privat, status gunung api PVMBG, foto gunung berlisensi, koreksi data publik, pengingat hari buka izin, pos sepanjang jalur, segmen jalur admin, `PeringatanOperasi` + `RUNBOOK-OPERASI.md`, konsolidasi modal/tabel/status kosong | 9 (#24 sampai #32) |
| Sprint 4 (`LOW` + W9) | `docs/superpowers/plans/2026-10-09-memount-v3-w9-penutup.md` | Halaman galat React+Blade satu sumber, format jam/kosakata/tanpa klaim keselamatan, penjaga rute tulis & proxy tepercaya, kunci baris PostgreSQL, anggaran 500 kandidat, benchmark Route Fit, pemicu HTTP Cron `/api/cron/{tugas}`, cakupan pratinjau 57 halaman, audit WCAG+keamanan+antislop 002, kandidat rilis & materi Q15 | 0 |

