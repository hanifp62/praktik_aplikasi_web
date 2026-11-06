# 07 Akun dan masuk

**FR utama:** FR-01 Autentikasi (§20), FR-02 Profil pendaki (§21), FR-26 Verifikasi kode multi-kanal (§151). Tujuan pendakian (FR-03) dicatat di spesifikasi 04 sesuai tabel FR di PRD.
**Bahan:** features/07-akun-masuk.md dari NgodingPakeAI (keluaran alat di root, diabaikan git), `docs/PRD.md`, spec v3 bagian 6.15 dan 6.16.
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
4. **Lupa kata sandi.** Given pendaki meminta tautan atur ulang, When tautan dipakai sebelum kedaluwarsa, Then kata sandi baru berlaku; jawaban tidak mengungkap keberadaan akun (perilaku persisnya menunggu keputusan pemilik, spec v3 bagian 15 Q5).
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
- **Menunggu pemilik:** spec v2 P4 (penyedia kode dan data nomor telepon), spec v3 bagian 15 Q5 (jawaban lupa kata sandi, verifikasi email, 404 atau 403 untuk IDOR).
