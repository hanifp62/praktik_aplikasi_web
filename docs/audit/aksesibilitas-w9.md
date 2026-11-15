# Audit aksesibilitas W9 (WCAG 2.2 AA)

Audit ini memetakan kriteria WCAG 2.2 AA yang relevan bagi Memount ke test yang menjaganya dan ke langkah
klik-lewat yang membuktikannya di layar (`CLAUDE.md` bagian 6). Kelas CSS bukan bukti: test sumber menjaga bahwa
aturan ditulis, sedangkan perilaku di layar dibuktikan klik-lewat koordinator di
`docs/audit/klik-lewat/2026-10-09-daftar-periksa-w9.md`. Setiap rujukan `berkas::test` diperiksa `AuditCitationTest`.

Tingkat bukti mengikuti R-023: FACT untuk test yang lulus atau keluaran peramban yang dicatat, PERLU KLARIFIKASI
untuk yang belum diperiksa di layar. Uji dengan pembaca layar dan pengguna nyata mengikuti
`docs/PROTOKOL-UJI-KEGUNAAN.md` dan hasilnya tidak dikarang.

| Kriteria | Bukti otomatis | Bukti di layar | Tingkat |
|---|---|---|---|
| 1.1.1 Konten nontext | `tests/Feature/TrustGrammarTest.php::test_the_fit_label_pairs_an_icon_with_a_word_and_never_a_number`, `tests/Feature/CheckpointJourneyAccessibilityTest.php::test_checkpoint_journey_exposes_pace_to_screen_readers_including_when_geometry_is_missing` | Ikon dekoratif tidak dibacakan di pohon aksesibilitas | FACT untuk sumber; layar PERLU KLARIFIKASI |
| 1.3.1 Info dan relasi | `tests/Feature/AccessibilityTest.php::test_each_page_has_exactly_one_first_level_heading`, `tests/Feature/KeyboardAndScreenReaderTest.php::test_every_page_has_a_main_landmark`, `tests/Feature/KeyboardAndScreenReaderTest.php::test_every_error_and_hint_in_the_react_ui_is_referenced_by_its_control` | Pohon aksesibilitas tiap pratinjau | FACT untuk sumber |
| 1.4.1 Penggunaan warna | `tests/Feature/AccessibilityTest.php::test_status_is_never_conveyed_by_colour_alone`, `tests/Feature/TrustGrammarTest.php::test_the_access_status_is_carried_by_shape_and_word` | Pratinjau dengan `forced-colors: active` | FACT untuk sumber |
| 1.4.3 Kontras minimum | `tests/Feature/ColourContrastTest.php::test_text_pairings_meet_aa`, `tests/Feature/ColourContrastTest.php::test_field_themes_meet_their_thresholds`, `tests/Feature/ColourContrastTest.php::test_every_pairing_holds_in_each_combination_of_app_and_field_theme` | Teks di atas foto gunung diukur di titik terang | FACT untuk token |
| 1.4.4 dan 1.4.10 Ubah ukuran dan reflow | `tests/Feature/TapTargetTest.php::test_no_grid_forces_three_columns_on_a_narrow_screen` | `scrollWidth` sama dengan `clientWidth` di 320 px pada setiap halaman pendaki | PERLU KLARIFIKASI sampai klik-lewat |
| 1.4.11 Kontras nonteks | `tests/Feature/ColourContrastTest.php::test_non_text_pairings_meet_the_ui_threshold`, `tests/Feature/ColourContrastTest.php::test_the_border_of_an_interactive_control_is_visible`, `tests/Feature/ScreenReaderAndTargetTest.php::test_empty_progress_segments_are_outlined_with_the_control_border` | Ruas kosong langkah trip terlihat di kedua tema | FACT untuk token |
| 1.4.12 Spasi teks | tidak ada test otomatis | Pratinjau dengan bookmarklet spasi teks WCAG: tidak ada teks terpotong | PERLU KLARIFIKASI |
| 2.1.1 dan 2.1.2 Papan ketik | `tests/Feature/KeyboardAndScreenReaderTest.php::test_a_keyboard_user_can_skip_straight_to_the_content`, `tests/Feature/CheckpointJourneyAccessibilityTest.php::test_account_menu_closes_on_escape_and_restores_focus_to_trigger` | Jalur papan ketik per halaman di daftar periksa | FACT untuk sumber; layar PERLU KLARIFIKASI |
| 2.3.3 Animasi dari interaksi | `tests/Feature/MotionTest.php::test_reduced_motion_is_honoured_everywhere_at_once`, `tests/Feature/MotionTest.php::test_reducing_motion_shortens_it_rather_than_removing_the_feedback` | Lembar dan toast dengan `prefers-reduced-motion: reduce` | FACT untuk sumber |
| 2.4.3 Urutan fokus | `tests/Feature/ActiveHikeStripTest.php` menjaga strip pendakian dijangkau sesudah isi halaman; `tests/Feature/ErrorPageFocusTest.php::test_the_inertia_error_page_moves_focus_to_its_title` | Tab mengikuti urutan tampilan; sesudah aksi yang ditolak 429, fokus berada di judul galat | PERLU KLARIFIKASI sampai klik-lewat |
| 2.4.7 Fokus terlihat | `tests/Feature/FocusIndicatorTest.php::test_nothing_removes_the_focus_outline_without_replacing_it`, `tests/Feature/ColourContrastTest.php::test_the_focus_ring_is_distinguishable_from_the_page` | Cincin fokus di tema terang, gelap, dan lapangan | FACT untuk sumber |
| 2.4.11 Fokus tidak tertutup | tidak ada test otomatis; `scroll-padding` menghitung lapisan kerangka (W2) | Isian terakhir Rencanakan tidak tertutup bar aksi saat difokus | PERLU KLARIFIKASI |
| 2.5.8 Ukuran target | `tests/Feature/TapTargetTest.php::test_every_react_button_and_in_app_link_can_be_hit_by_a_thumb`, `tests/Feature/ScreenReaderAndTargetTest.php::test_the_mountain_link_in_the_trail_header_is_a_full_touch_target` | Kendali di `main` paling sedikit 44 x 44 px | FACT untuk sumber |
| 3.1.1 Bahasa halaman | `tests/Feature/AccessibilityTest.php::test_the_language_of_the_document_is_declared` | Atribut `lang="id"` | FACT |
| 3.3.1 dan 3.3.3 Identifikasi dan saran galat | `tests/Feature/KeyboardAndScreenReaderTest.php::test_a_field_error_is_tied_to_the_input_it_belongs_to`, `tests/Feature/InertiaErrorPageTest.php::test_a_validation_error_still_returns_to_the_form` | Galat dibacakan dan fokus pindah ke isian pertama yang salah | FACT untuk sumber |
| 3.3.2 Label atau instruksi | `tests/Feature/AccessibilityTest.php::test_every_form_control_on_the_core_flow_has_a_label`, `tests/Feature/AccountPagesSourceTest.php::test_every_checkbox_on_the_account_pages_has_a_label` | Nama aksesibel setiap isian di pohon aksesibilitas | FACT untuk sumber |
| 4.1.2 Nama, peran, nilai | `tests/Feature/AccessibilityTest.php::test_the_primary_button_carries_a_visible_focus_ring_and_a_touch_target` | Sakelar dan pilihan tunggal membacakan keadaannya | PERLU KLARIFIKASI di layar |
| 4.1.3 Pesan status | `tests/Feature/ScreenReaderAndTargetTest.php::test_a_save_announced_by_its_undo_toast_is_not_announced_again_by_the_page`, `tests/Feature/ScreenReaderAndTargetTest.php::test_a_removal_announced_by_its_undo_toast_is_not_announced_again_by_the_page`, `tests/Feature/InertiaErrorPageTest.php::test_a_throttled_inertia_action_says_how_long_to_wait_and_keeps_the_page_it_came_from` | Satu pengumuman per aksi dengan pembaca layar | FACT untuk props; pembaca layar PERLU KLARIFIKASI |

## Kata yang dibaca pendaki

Bagian ini bukan kriteria WCAG, tetapi menjaga keterbacaan teks yang sama: jam bertitik dan berzona, tahun pada
tanggal di luar tahun berjalan, kosakata Indonesia di kalimat mesin, dan tanpa kata klaim keselamatan.

- `tests/Feature/IndonesianTimeFormatTest.php::test_no_screen_writes_a_clock_time_with_a_colon`
- `tests/Feature/IndonesianTimeFormatTest.php::test_a_short_date_names_its_year_only_outside_the_current_year`
- `tests/Feature/MachineVocabularyTest.php::test_no_screen_says_elevation_gain_or_shouts_a_status_value`
- `tests/Feature/SafetyClaimWordTest.php::test_no_text_a_hiker_reads_claims_safety_or_certainty`
- `tests/Feature/ErrorPagesTest.php::test_the_page_speaks_indonesian_and_offers_a_way_out`

## Yang menunggu

- Uji pembaca layar (TalkBack di Android, NVDA di Windows) dan uji kegunaan 5 sampai 8 pendaki (Q15) dijalankan
  pemilik dengan `docs/PROTOKOL-UJI-KEGUNAAN.md`.
- Perilaku klien yang butuh Playwright atau Vitest (fokus sesudah Urungkan, antrean Persiapan) tercatat sebagai
  keterbatasan di daftar periksa klik-lewat.
