# Catatan riset 02: PRD NgodingPakeAI (working tree PRD.md, 412 baris) + features/*.md

Sumber dibaca penuh 2026-09-23. Identik dengan jelajah-gunung.zip dan `plan get` (beda newline akhir saja).

## Isi yang BERNILAI dan belum ada / lebih mutakhir dari PRD lama
- Alur 8 kata kerja: Jelajah → Pilih → Rencanakan → Siapkan → Cek → Mendaki → Laporkan → Berkembang (padanan loop §7, lebih mudah dibaca awam).
- Tujuan 6: Confidence, Decision Convenience, Route Fit, Readiness, Continuity, Provenance ("jalur tanpa geometri = belum terbit, tidak direkomendasikan" — selaras §110/R-022).
- Prinsip: "informasi tidak pernah dikarang; tampilkan keterangan jujur" (selaras R-007/R-013/§47).
- NFR mutakhir: Core Web Vitals LCP ≤ 2,5 s, **INP < 200 ms**, **CLS < 0,1**; WCAG 2.2 + skip link + ARIA live region + target 44×44; OWASP **2025**, SSRF, HSTS, cookie aman, kunci versi dependensi; audit aksi kritis; responsif.
- Fitur per fase (Fase 1 Jelajah+Detail, Fase 2 Pertimbangkan+Perjalanan+Kabar, Fase 3 Pendakian&Progres+Akun, Fase 4 Konsol) dan 8 spesifikasi dengan "Selesai bila" (acceptance criteria yang bisa diuji) + 140 task.
- Alur pengelola & pakar wilayah (kirim sebagai "menunggu verifikasi admin"; alasan aksi terkunci).
- Arsitektur per domain (selaras modular monolith ARCHITECTURE.md aturan 10), mermaid diagram, "spatial first", peta bertahap (GeoJSON disederhanakan / vector tiles), mode hemat jaringan (baca konten yang sudah dibuka).
- Catatan: mesin rekomendasi rule-based, bukan AI (selaras §85).

## KONFLIK dengan aturan kanonis / PRD lama / kode (kandidat register konflik)
K1 Banding: PRD baru F-06 & fitur 03 "hingga 3 jalur" vs §33 FR-06 + §145.1 "maks 5" + kode `ConsiderationService::BATAS = 5`.
K2 Skor tampil ke pengguna: F-03 "nilai kecocokan", tabel banding "skor kecocokan", F-08 + fitur 04 "skor kesiapan (angka dan kategori)" vs R-004, R-005, R-014, BR-09, §28, §39, §91.
K3 Label kecocokan: DB enum `cocok/cukup/kurang`, fitur 01 "cocok / cukup / kurang" vs R-004 (COCOK / PERLU PERSIAPAN / KURANG COCOK).
K4 Kesiapan berbasis "kebugaran" (fitur 04) vs R-014 (bukan medical clearance) + §36 (kategori fisik = persiapan, bukan penilaian medis) + §39 (NOT RECOMMENDED hanya closure/restriction/hard incompatibility).
K5 Status resmi: `dibuka/ditutup/dibatasi/waspada` TANPA UNKNOWN vs §41 OPEN/RESTRICTED/CLOSED/UNKNOWN + R-006 + BR-07. "Waspada" = konsep baru.
K6 Hierarki kepercayaan "Resmi → Admin → Pakar → Komunitas" (label Pakar; AI hilang) vs R-009 OFFICIAL > ADMIN VERIFIED > COMMUNITY > AI. Catatan: alur pakar di PRD baru tetap "menunggu verifikasi admin". Kode punya Authority + ExpertCredential (cek urutan otoritas di kode).
K7 Peta sebagai antarmuka utama vs HANDOVER §8 "peta-dulu ditunda" (0 geometri, ARCHITECTURE "Yang belum ada") + §142 "Map: online MVP". Realitas data: jalur belum bergeometri.
K8 Kesegaran universal ">7 hari = perlu diperbarui" (fitur 05) vs §59 (ambang per sumber, tanpa aturan universal) + §93.
K9 Laporan "cerita/tips" + nama pengirim + reaksi "jempol" vs §145.1 "Kabar bukan feed sosial generik", §49 supplementary, §82 privasi; kode punya report_thanks (terima kasih).
K10 Masuk dengan Google (OAuth) + Sanctum (fitur 07) = penyedia eksternal baru (R-021 "semantik penyedia eksternal") + implikasi kebijakan data pribadi (docs/KEBIJAKAN-DATA-PRIBADI.md).
K11 Pengingat email/notifikasi (fitur 04) = scope baru; mail driver saat ini `log`; scheduler produksi belum jalan (HANDOVER §9).
K12 Rencana perjalanan: jumlah peserta + tanggal selesai (fitur 04) vs FR-08 field (date, start time, trip type, notes). Tambahan aditif (skema).
K13 Target personal per bulan (jarak/ketinggian/frekuensi, `user_goals`) vs FR-03 hiking goal per trip. Konsep baru (Strava-like). Harus tunduk R-015 (self-comparison) dan BR-11.
K14 Catat pendakian manual bebas (fitur 06) vs FR-14 (history dari trip). Konsep baru; tidak boleh menaikkan level otomatis (BR-11).
K15 Arsitektur: Redis sebagai cache/antrean (PRD baru §5, §7) vs konfigurasi nyata cache/queue `database` + §99 ("Horizon/Redis bila kompleksitas menuntut").
K16 SKEMA PRD baru §6 = usulan greenfield (uuid, kolom bahasa Indonesia, ±15 tabel tidak ada: user_goals, saved_trails, comparison_sets/items, itinerary_items, gear_items, trip_checklists, readiness_records, reminders, trail_reports, report_media, report_reactions, hike_logs, hike_positions, publication_requests, role_permissions, rule_versions, status_history, freshness_logs) vs 50 migrasi nyata yang sudah jalan di Supabase (HANDOVER §6) + R-021 (arsitektur DB).
K17 Cuaca per gunung (`weather_snapshots.mountain_id`) vs BR-15 (reference area per jalur) + kode (migrasi drop_trail_id_from_weather_snapshots — cek model cuaca nyata).
K18 Warna "biru = informasi" vs DESIGN.md (biru = komunitas, §92).
K19 Kelola peran dengan matriks `role_permissions` vs kode (role enum + Policy/Gate). Perubahan arsitektur otorisasi.
K20 Pencarian kalimat sederhana (fitur 01) vs §86 (conversational search = FUTURE AI). Boleh bila rule-based; implementasi agen sebelumnya (alias 5 kota, "pemula"→LOW) bermasalah (F3).
K21 Bagikan tautan tanpa akun (fitur 03) vs kode (trails.compare di grup auth; shared link read-only). Permukaan publik baru → privasi & keamanan (token, kedaluwarsa).
K22 Catatan pribadi per jalur (fitur 03) vs §145.1 "catatan opsional bila diimplementasikan" — kompatibel, belum ada di kode.
K23 Detail jalur tanpa geometri: fitur 02 "tidak bisa dibuka atau tampil pesan belum lengkap" vs kode (bagian "menunggu" Jelajah menaut ke detail jalur belum terbit) — kompatibel dengan opsi pesan.

## Verifikasi kode atas konflik (2026-09-23)
- K6: `app/Enums/SourceType.php` SUDAH punya ACCREDITED_EXPERT ("Ahli bersertifikat", rank 3) di antara ADMIN_VERIFIED (2) dan COMMUNITY (4), AI_INTERPRETATION rank 5; komentar mengutip PRD §43. Teks kanonis R-009 (mountain-core.md) belum menyebut tingkat ini → yang tertinggal adalah TEKS ATURAN, bukan kode. Keputusan pemilik: amandemen R-009 agar menyebut tingkat ahli (invariant "yang lebih rendah tidak menggantikan yang lebih tinggi" tetap). Istilah UI: kode "Ahli bersertifikat" vs PRD baru "Pakar" → pilih satu.
- K17: kode memakai `trails.weather_adm4_code` + `weather_reference_area` (TrailFactory) → reference area per jalur, selaras BR-15. PRD baru `weather_snapshots.mountain_id` salah terhadap kode.
- AGENTS.md HEAD bukan konten proyek: hanya bootstrap generik Laravel Boost (prasyarat + install Boost). Working tree = hasil boost:install + ngodingpakeai init. Tidak ada isi proyek yang hilang.
- docs/CONTRIBUTING.md SUDAH ADA (setup, perintah harian). README.md = bawaan Laravel.

## PRODUCT.md — isi unik yang wajib selamat saat dilebur
Growth (corong SEARCH→...→PENGGUNA BARU; halaman publik; mekanisme; yang tidak dikejar), Capabilities & Constraints (Route Fit 7 faktor; kesiapan = dukungan keputusan; status berjenjang kaskade; kredensial ahli BNSP/LSP/APGI/SKKNI Muda/Madya/Ahli 3 tahun; perekaman jejak opsional default mati; terminologi pos/jalur/kawasan/pengelola), Brand Commitments (UI bahasa Indonesia; nada mengakui batas; tidak menghukum keputusan benar — batal karena cuaca tetap tercatat; tidak ada perbandingan kecepatan antarpendaki), Evidence on Hand (16 gunung koordinat Wikidata, status Sep 2026, izin Semeru & Rinjani, 8 badan resmi; 29 jalur tanpa karakteristik; geometri/pos/foto/hasil uji belum ada), Product Principles (5), Prioritas mengikat 1 IMK/HCI ... 9 SEO + peran agen, ATM (amati/tiru/modifikasi + larangan), Rujukan (AllTrails/Traveloka [perbandingan maks lima]/Strava), Model kerja IMK, Hasil UX yang diukur (6 outcome + target validasi, "tidak boleh dikarang").

## RISET EKSTERNAL (2026-09-23, dengan sumber)
- AllTrails Peak (Mei 2025, $79/th): custom routes + AI smart routing (lebih pendek/landai/indah), Trail Conditions prediktif (15 faktor, >10 juta titik data; hujan, suhu, salju, kualitas udara, nyamuk) di atas peta, offline areas, community heatmap keramaian. Sumber: techcrunch.com/2025/05/12/alltrails-debuts-a-80-year-membership-that-includes-ai-powered-smart-routes ; alltrails.com/press/alltrails-expands-membership-offering-with-alltrails-peak ; popsci.com/gear/alltrails-peak-subscription-hiking-app
- Strava 2025–2026: Route Saves dari berbagai permukaan, Off-Route Alerts, Route Builder mobile didesain ulang, fitur hiking Juni 2026 (discovery, route planning, navigasi, Live Elevation), Athlete Intelligence (AI insight), ToS/privasi baru 1 Jan 2026 dengan bab AI; Map Visibility menyembunyikan alamat/awal/akhir/peta. Sumber: press.strava.com/articles/strava-adds-new-features-for-hiking-making-the-outdoor-experience-more-discoverable-navigable-and-social ; support.strava.com/hc/en-us/articles/115000173384-Edit-Map-Visibility ; bikeradar.com/news/strava-2026-policies
- OWASP Top 10:2025 (diumumkan Nov 2025, final Jan 2026): baru A03 Software Supply Chain Failures, A10 Mishandling of Exceptional Conditions (failing open, error handling); SSRF dilebur ke Broken Access Control. Sumber: owasp.org/Top10/2025/0x00_2025-Introduction/ ; fastly.com/blog/new-2025-owasp-top-10-list-what-changed-what-you-need-to-know
- WCAG 2.2 (W3C Rec 5 Okt 2023): 9 SC baru — 2.4.11 Focus Not Obscured (AA), 2.4.12 (AAA), 2.4.13 Focus Appearance (AAA), 2.5.7 Dragging Movements (AA), 2.5.8 Target Size Minimum 24×24 (AA), 3.2.6 Consistent Help (A), 3.3.7 Redundant Entry (A), 3.3.8 Accessible Authentication Minimum (AA; izinkan paste/password manager), 3.3.9 (AAA). Sumber: w3.org/TR/WCAG22/ ; tetralogical.com/blog/2023/10/05/whats-new-wcag-2.2/
  → CEK F7: baki simpanan `sticky bottom-0` berisiko melanggar 2.4.11 (fokus tertutup). Verifikasi di peramban sebelum klaim.
- Core Web Vitals: LCP < 2,5 s, INP < 200 ms (menggantikan FID Maret 2024), CLS < 0,1 — dinilai pada persentil ke-75 data lapangan. Sumber: corewebvitals.io/core-web-vitals
- UU PDP No. 27/2022: berlaku penuh Oktober 2024 (termasuk sanksi administratif); notifikasi kegagalan pelindungan data tertulis paling lambat 3×24 jam. Sumber: peraturan.bpk.go.id/Details/229798/uu-no-27-tahun-2022 ; jdih.komdigi.go.id/produk_hukum/view/id/832
- Izin pendakian Indonesia 2026: Rinjani wajib e-Rinjani/e-Simaksi (esimaksi.rinjaninationalpark.id), kuota ±150/jalur/hari, jalur ditutup 1 Jan–31 Mar 2026 (mitigasi hidrometeorologi); Semeru booking online (bookingsemeru.bromotenggersemeru.org) kuota 200/hari. → Produk MENAUTKAN sistem resmi, tidak mereplikasi booking (non-goal §13). Sumber: blog.kumbaya.id/2026/06/05/panduan-simaksi-online-pendakian-2026/ ; esimaksi.rinjaninationalpark.id
- BMKG data terbuka: api.bmkg.go.id/publik/prakiraan-cuaca?adm4=…, 3 hari, 8 prakiraan/hari (3 jam), update 2×/hari, adm4 (Kepmendagri 100.1.1-6117/2022), 60 req/menit/IP, atribusi BMKG wajib. Sumber: data.bmkg.go.id/prakiraan-cuaca/ ; github.com/infoBMKG/data-cuaca
- Peta: PMTiles (satu berkas arsip ubin) + Protomaps basemap OSM, dapat di-self-host tanpa tile server; plugin maplibre-offline-pmtiles untuk offline. Jalur sah untuk "offline future" (§55 melarang bulk OSM standard tiles). Sumber: docs.protomaps.com/pmtiles/maplibre ; github.com/makinacorpus/maplibre-offline-pmtiles
- Laravel 13: rilis 17 Mar 2026, PHP ≥ 8.3, passkey auth, Laravel AI SDK stabil, Reverb database driver, bug fix s/d Q3 2027, keamanan s/d Q1 2028. Sumber: laravel.com/docs/13.x/releases ; laravel-news.com/laravel-13
  → Klaim blog "hanya Livewire 4 kompatibel dengan Laravel 13" TIDAK dipakai: runtime proyek Livewire 3.8.9 + Laravel 13.31.0, 933 test lulus.
- Livewire 4: rilis 14 Jan 2026; single-file components default (sintaks sama dengan Volt class-based), islands, kompatibilitas mundur kuat. Upgrade = keputusan R-020. Sumber: livewire.laravel.com/docs/4.x/upgrading ; laravel-news.com/everything-new-in-livewire-4
- ADR/MADR: simpan di repo (docs/decisions/), Markdown, direview lewat PR; siklus Proposed → Accepted → Deprecated/Superseded; Accepted praktis imutabel. Sumber: adr.github.io/madr/ ; github.com/adr/madr

## KEPUTUSAN PEMILIK 2026-09-23 (jawaban dialog kedua)
- Simpan/banding, Peta, Otoritas: DIDELEGASIKAN ke riset ("jadikan ini sebagai landasan anda ... riset detail dan mendalam ... informasi terkini ... kompatibel dengan user"). Dipilih opsi rekomendasi: simpan longgar + banding ≤3; peta digerbang data (hapus titik gunung pengganti jalur); amandemen R-009 dengan ACCREDITED EXPERT "Ahli bersertifikat".
- Fitur baru: SEMUA EMPAT masuk MVP (catatan pribadi + tautan bagikan; pengingat + detail rencana; masuk Google/passkey; target + catat pendakian manual) + "saya ingin menggunakan verifikasi kode melalui gmail, pesan, whatsApp dll."

## RISET VERIFIKASI KODE (2026-09-23)
- NIST SP 800-63B-4 final Juli 2025: PSTN/SMS OTP = satu-satunya "restricted authenticator" (§3.2.9); email/VoIP SHALL NOT dipakai untuk out-of-band authentication. Sumber: csrc.nist.gov/pubs/sp/800/63/b/4/final ; blog.typingdna.com/nist-sp-800-63b-rev-4-sms-otp-is-now-a-restricted-authenticator-but-we-have-the-fix/
- WhatsApp Cloud API: OTP = authentication template, ditagih per pesan terkirim sejak Juli 2025; Indonesia termasuk pasar authentication-international; rate card resmi Meta. Gunakan platform resmi (Cloud API/BSP), bukan gateway tak resmi. Sumber: developers.facebook.com/documentation/business-messaging/whatsapp/pricing/authentication-international-rates/ ; authgear.com/post/whatsapp-api-pricing/
- Laravel 13 passkey first-party: laravel/passkeys (Laravel 11–13) + Fortify Features::passkeys() + @laravel/passkeys; proyek memakai Breeze/Volt (bukan Fortify) → integrasi lewat laravel/passkeys langsung; tambah dependensi = butuh persetujuan saat implementasi. Sumber: laravel-news.com/laravel-introduces-first-party-passkey-authentication-support ; laravel.com/framework/docs/fortify
- Penyedia: Twilio Verify (SMS, WhatsApp, voice, email, TOTP; 20–40% pengguna Indonesia memilih WhatsApp), Citcall (lokal; SMS OTP + missed-call), Vonage, Infobip, Plivo, Sinch. Sumber: twilio.com/docs/verify/whatsapp ; engagelab.com/blog/otp-service-provider
- Desain rekomendasi: passkey + email/sandi utama; Google (Socialite) untuk daftar cepat; kode WhatsApp (utama) / SMS (cadangan, restricted) untuk verifikasi nomor & pemulihan; kode email untuk verifikasi kepemilikan email; OTP 6 digit, kedaluwarsa ≤10 menit, sekali pakai, disimpan ter-hash, rate limit & batas percobaan, autocomplete="one-time-code", paste diizinkan (WCAG 3.3.8); kanal lewat Laravel Notifications; nomor telepon = data pribadi (UU PDP) → persetujuan, minimisasi, ikut kebijakan penghapusan.

## Fitur → kode (sementara, perlu verifikasi)
01 Jelajah: TrailIndex (+ ubahan agen sebelumnya), SavedRoutes (baru). 02 Detail: TrailDetail, PublicTrailController. 03 Pertimbangkan: RouteComparison, ConsiderationService, SavedRoutes. 04 Perjalanan: TripForm/TripShow/TripIndex, PreparationChecklist, ReadinessDashboard. 05 Kabar: TrailNews, OfficialStatusService, WeatherService, ConditionReportForm, ModerationQueue. 06 Pendakian: HikeMode, HikeTrackController, HikingHistoryPage, HikeSummary, HikerProgress. 07 Akun: Breeze/Volt auth, ProfileSetup, GoalForm, TrackRecordingPreference. 08 Konsol: Admin\* (Mountain/Trail/Checkpoint/DataSource/OfficialStatus/Permit/Authority/Credential/AuditLog/Analytics/Usability/SystemHealth/TrailGeometryImport), Moderation\ModerationQueue, Contribute\TrailContribution.
