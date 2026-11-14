# Audit keamanan W9

Audit ini memetakan daftar test keamanan kritis `CLAUDE.md` bagian 4 ke test yang ada, menandai tingkat buktinya
(R-023), dan mencatat celah yang ditutup gelombang W9. Setiap rujukan berbentuk `berkas::test` diperiksa
`AuditCitationTest`, jadi test yang dirujuk benar-benar ada.

Tingkat bukti:
- **FACT**: test Unit atau Feature yang lulus di suite SQLite, atau keluaran perintah yang dicatat.
- **INFERENCE**: kesimpulan dari kode yang dibaca, belum diuji langsung.
- **PERLU KLARIFIKASI**: butuh PostgreSQL, Supabase, jaringan, atau izin pemilik. Belum boleh disebut lulus.

Test di `tests/Spatial` hanya berjalan dengan `SPATIAL_TEST_DSN` dan dilewati di mesin tanpa PostgreSQL lokal.
Semuanya PERLU KLARIFIKASI di lokal sampai CI Spatial lulus (R-024).

## Daftar kritis

| Butir | Bukti | Tingkat | Celah dan tindakan W9 |
|---|---|---|---|
| Auth bypass | `tests/Feature/RouteAccessMatrixTest.php::test_every_route_is_either_public_by_design_or_closed_to_guests`, `tests/Feature/WriteRouteGuardTest.php::test_every_write_route_needs_a_session_unless_it_opens_one`, `tests/Feature/Auth/AuthenticationTest.php::test_the_sixth_attempt_is_locked_out_even_with_the_right_password`, `tests/Feature/Auth/AuthenticationTest.php::test_a_guest_cannot_call_logout`, `tests/Feature/TripOwnershipTest.php::test_guests_are_redirected_to_login` | FACT | Sapuan rute tulis W9 Tugas 5 menutup celah aturan per rute. Sisa: rute `livewire/update` dan `livewire/upload-file` dari paket Livewire dan Volt yang tidak dipakai komponen apa pun masih terpasang tanpa sesi (Konflik W9) |
| IDOR | `tests/Feature/TripOwnershipTest.php::test_another_user_cannot_open_someone_elses_trip`, `tests/Feature/TripOwnershipTest.php::test_another_user_cannot_act_on_someone_elses_trip`, `tests/Feature/TripOwnershipTest.php::test_a_preparation_item_is_only_found_within_its_own_trip`, `tests/Feature/ReportPhotoAccessTest.php::test_a_stranger_cannot_open_a_pending_report_photo`, `tests/Feature/ProfilePhotoTest.php::test_only_the_owner_is_served_the_photo`, `tests/Feature/ConsiderationTest.php::test_one_hikers_shortlist_is_invisible_to_another`, `tests/Feature/ComparisonShareTest.php::test_only_the_owner_can_revoke_and_others_get_not_found`, `tests/Feature/TripReminderTest.php::test_another_user_cannot_touch_someone_elses_reminders`, `tests/Feature/HikeTrackRecordingTest.php::test_a_hiker_cannot_write_into_someone_elses_track` | FACT | Trip orang lain dijawab 403, bukan 404, sampai pemilik memutus Q5 |
| Eskalasi hak | `tests/Feature/AdminAccessTest.php::test_hikers_cannot_reach_admin_pages`, `tests/Feature/AdminAccessTest.php::test_moderators_cannot_reach_admin_pages`, `tests/Feature/RouteAccessMatrixTest.php::test_an_ordinary_hiker_cannot_reach_any_admin_page`, `tests/Feature/Auth/RegistrationTest.php::test_a_registrant_cannot_make_themselves_an_admin`, `tests/Feature/AuthorityMemberPolicyTest.php::test_a_hiker_cannot_promote_themselves_through_the_profile`, `tests/Feature/AuthorityMemberPolicyTest.php::test_the_recorder_never_verifies_their_own_record`, `tests/Feature/ExpertGeometryAccessTest.php::test_the_credential_does_not_reach_another_mountain` | FACT | Tidak ada celah baru |
| Mass assignment | `tests/Feature/MassAssignmentSweepTest.php::test_no_model_opens_every_column_to_mass_assignment`, `tests/Feature/MassAssignmentSweepTest.php::test_no_http_code_passes_the_whole_request_on`, `tests/Feature/OfficialStatusVisibilityTest.php::test_provenance_controls_are_not_mass_assignable`, `tests/Feature/TripLifecycleTest.php::test_ownership_and_status_cannot_be_smuggled_in` | FACT | W9 menambah dua sapuan umum. Kolom `role` ada di daftar fillable `User`; yang menjaganya adalah aturan validasi formulir, dan test peran di baris ini membuktikannya |
| Unggahan tidak aman | `tests/Feature/ProfilePhotoTest.php::test_malicious_or_oversized_uploads_are_refused_without_a_trace`, `tests/Feature/ProfilePhotoTest.php::test_an_uploaded_photo_is_stored_without_metadata_and_scaled_down`, `tests/Feature/MountainPhotoLicenseTest.php::test_svg_scripts_and_tiny_images_are_refused`, `tests/Feature/EvidenceArchiveTest.php::test_only_real_images_and_pdfs_within_the_limit_are_accepted`, `tests/Feature/ReportPhotoAccessTest.php::test_the_photo_disk_is_private_by_default`, `tests/Feature/WriteRouteGuardTest.php::test_private_files_have_no_signed_url_route` | FACT | W9 Tugas 5 mematikan `serve` disk lokal, sehingga rute `storage/{path}` bertanda tangan bawaan Laravel hilang. Disk produksi bukti, foto gunung, dan foto profil menunggu keputusan pemilik |
| Rate limiting | `tests/Feature/RateLimitTest.php::test_the_limit_is_per_user_not_global`, `tests/Feature/WriteRouteGuardTest.php::test_every_write_route_is_rate_limited_or_says_why_not`, `tests/Feature/WriteRouteGuardTest.php::test_registration_is_rate_limited_per_address`, `tests/Feature/WriteRouteGuardTest.php::test_asking_for_a_password_reset_link_is_rate_limited_per_address`, `tests/Feature/ComparisonShareTest.php::test_opening_links_is_rate_limited_per_address`, `tests/Feature/TrustedProxyTest.php::test_a_forwarded_address_is_ignored_while_no_proxy_is_trusted` | FACT | Batas per alamat IP baru benar di balik proxy bila `TRUSTED_PROXIES` diisi sesuai platform deploy (butir izin akhir). Rute tulis telah memiliki pembatas laju per domain |
| Kebocoran secret | `tests/Feature/SecretsNotInRepoTest.php::test_no_committable_file_contains_the_pattern`, `tests/Feature/SecretsNotInRepoTest.php::test_the_example_environment_file_carries_no_real_values`, `tests/Feature/InertiaErrorPageTest.php::test_a_server_error_hides_its_details_from_hikers_but_not_from_developers`, `tests/Feature/LocationPrivacyTest.php::test_internal_fit_score_is_never_serialised_to_the_client` | FACT untuk isi repo; PERLU KLARIFIKASI untuk riwayat git yang sudah di-push | Ref proyek Supabase dan Workspace ID sudah terbaca publik (Q21), keduanya pengenal, bukan kredensial |
| RLS allow dan deny | `tests/Spatial/ComparisonShareSchemaTest.php`, `tests/Spatial/AuthorityMemberSchemaTest.php`, `tests/Spatial/DataErrorReportSchemaTest.php`, `tests/Spatial/VolcanoStatusSchemaTest.php`, dan `tests/Spatial/MountainPhotoSchemaTest.php` memeriksa `relrowsecurity` tabel baru; migrasi `2026_10_12_020612_aktifkan_rls_di_semua_tabel_aplikasi` menyalakan RLS tabel lama | PERLU KLARIFIKASI | Suite Spatial dilewati tanpa PostGIS lokal. Uji allow dan deny untuk peran `anon` dan `authenticated` Supabase butuh akses Supabase (Q22) |
| Akses lokasi privat | `tests/Feature/LocationPrivacyTest.php::test_no_other_client_code_requests_geolocation`, `tests/Feature/LocationPrivacyTest.php::test_hiking_session_location_is_hidden_from_serialisation`, `tests/Feature/HikeTrackRecordingTest.php::test_nothing_is_stored_when_the_hiker_never_turned_recording_on`, `tests/Feature/HikeTrackRecordingTest.php::test_one_hiker_cannot_delete_another_hikers_track`, `tests/Feature/TrackPreferenceTest.php::test_recording_starts_off_and_can_be_turned_on` | FACT | Tidak ada celah baru |
| Privasi pada penghapusan akun | `tests/Feature/PersonalDataDeletionTest.php::test_deleting_the_account_removes_every_w7_personal_record`, `tests/Feature/AccountDeletionRetentionTest.php::test_an_anonymised_report_shows_a_neutral_author_label`, `tests/Feature/ProfilePhotoTest.php::test_deleting_the_account_deletes_the_photo_file`, `tests/Feature/HikeTrackRecordingTest.php::test_deleting_the_account_takes_the_track_with_it`, `tests/Feature/HistoryPrivacyTest.php::test_with_the_switch_on_pages_encrypt_their_history_and_logging_out_clears_it`, `tests/Feature/ShareLinkRetentionTest.php::test_links_that_ended_before_the_retention_are_pruned_and_the_rest_stay` | FACT di SQLite; PERLU KLARIFIKASI untuk cascade di PostgreSQL | Enkripsi riwayat dan pemangkasan tautan bagikan dibangun W9 di balik sakelar yang mati sampai pemilik memutus kebijakannya di `docs/KEBIJAKAN-DATA-PRIBADI.md` |

## Lapisan pertahanan

| Lapisan | Bukti |
|---|---|
| Peramban | `tests/Feature/SecurityHeadersTest.php::test_the_page_cannot_be_framed_by_another_site`, `tests/Feature/SecurityHeadersTest.php::test_an_authenticated_page_carries_the_headers_too` |
| Autentikasi | baris Auth bypass di atas |
| Otorisasi | baris IDOR dan Eskalasi hak di atas |
| Validasi | `tests/Feature/TripLifecycleTest.php::test_an_unknown_completion_state_is_a_validation_error_not_a_crash`, `tests/Feature/HikeTrackRecordingTest.php::test_an_impossible_coordinate_is_refused` |
| Aturan bisnis | `tests/Feature/TripLifecycleTest.php::test_a_trip_cannot_be_created_on_an_unpublished_trail` |
| Basis data dan RLS | baris RLS di atas; kunci baris per pendaki dan per trip di `tests/Spatial/UserRowLockTest.php` dan `tests/Spatial/PreparationFirstVisitLockTest.php` (W9 Tugas 7), serta PreDepartureConfirmLockTest dari gelombang perbaikan akhir W4 |
| Penyimpanan | `tests/Feature/ReportPhotoAccessTest.php::test_guests_are_never_served_a_photo`, `tests/Feature/EvidenceArchiveTest.php::test_a_path_not_written_by_the_archive_is_never_served` |
| Jejak audit | `tests/Feature/AuditLogChangeTest.php::test_deleting_a_permit_rule_logs_what_was_deleted` |

## Yang belum dapat dibuktikan agen

- **Audit dependensi.** `composer audit` dan `npm audit` membutuhkan jaringan ke basis data advisori. Statusnya di
  sandbox PERLU KLARIFIKASI dan dijalankan workflow `security.yml` sesudah izin push (Tailwind CSS 4.3 dan MapLibre GL
  6.14 sudah di-upgrade lewat GR-08; Symfony 8.1 dan commonmark 2.10.7 dipulihkan di `a6c8f11`).
- **Suite Spatial.** Termasuk test kunci baris W9 dan W4, cascade, indeks unik, dan RLS. Syarat rilis.
- **Status migrasi Supabase.** `php artisan migrate:status` terhadap Supabase butuh izin baca (Q22).
- **Cookie sesi di produksi.** `SESSION_SECURE_COOKIE` diisi di lingkungan produksi. Nilainya tidak dibaca agen.
- **CSP `script-src`.** `SecurityHeaders` masih mengizinkan `'unsafe-eval'` dan `'unsafe-inline'` dengan alasan Alpine
  dan Livewire, padahal Alpine tidak ada di `package.json` dan tidak ada view Blade ber-`x-data` (FACT, Grep).
  Mengencangkannya menunggu pencabutan Livewire dan klik-lewat di bawah CSP (Konflik 2 rencana W9). PERLU KLARIFIKASI.

