# Catatan riset 01: PRD lama (HEAD:PRD.md, v2.0 FINAL, 3.670 baris, 146 bagian)

Sumber dibaca penuh 2026-09-23. Salinan: `git show HEAD:PRD.md`.

## Identitas
- Nama: Personalized Mountain Planning & Readiness Platform. Responsive web / PWA-ready.
- Baseline tertulis: Laravel **13.32.0** + PHP 8.5, PostgreSQL/Supabase, PostGIS, MapLibre, BMKG, Badan Geologi/MAGMA, WCAG 2.2 AA.
- Tim: 5 developer/mahasiswa. Tipe MVP: production-like academic/portfolio MVP.
- DISKREPANSI D-A: runtime terpasang Laravel **13.31.0** (composer show), bukan 13.32.0. Lapor (R-020).

## Produk (§1–§13)
- §1 decision-support platform yang dipersonalisasi; BUKAN katalog/GPS/checklist/cuaca/sosial/marketplace.
- §2 masalah: bukan "tidak menemukan info", melainkan "kesulitan mengubah info tersebar menjadi keputusan yang relevan bagi dirinya".
- §3 alur lama (cari→baca→review→cuaca→checklist→putuskan sendiri) → alur baru (profil→rencana→Route Fit→alasan→preparation→status+cuaca+kondisi→pre-departure→hike→report).
- §4 visi: keputusan pendakian lebih terinformasi, menghubungkan profil, karakteristik jalur, rencana, kondisi terbaru dalam satu alur yang dapat dijelaskan.
- §5 misi 6 butir: pemilihan jalur kontekstual; rekomendasi dapat dijelaskan; rekomendasi→preparation plan; bedakan resmi/terverifikasi/komunitas/interpretasi; info penting menjelang & selama pendakian; feedback loop lewat laporan kondisi.
- §6 north star (kualitatif): dari "ingin mendaki" ke "memahami jalur, persiapan, kondisi yang perlu diperiksa, hasil perjalanan".
- §7 loop: PROFILE→ROUTE FIT→PLAN→PREPARE→CHECK→HIKE→REPORT→IMPROVE; semua fitur utama wajib terhubung ke loop.
- §8 diferensiator: Explainable Mountain–Hiker Fit Engine.
- §9 posisi: bukan "daftar gunung", bukan "Strava untuk pendaki", bukan "aplikasi keselamatan".
- §10 persona: Beginner, Intermediate, Advanced/Experienced, Admin/Data Curator (+ primary need tiap persona).
- §11 JTBD-01..04 (temukan jalur sesuai; pahami tantangan; periksa status/cuaca/kondisi; laporkan kondisi).
- §12 goals G1 route selection, G2 preparation, G3 information trust, G4 continuity, G5 data feedback.
- §13 non-goals: emergency center, medical, marketplace, open trip, porter booking, social clone, advanced/offline navigation, wearable, autonomous AI safety, mountain CRUD, direktori wisata, GPS semata, ulasan semata, tiruan AllTrails/Traveloka/Strava.

## Domain (§14–§19)
- §14 model: HIKER(PROFILE, PREFERENCES, HISTORY)→TRIP PLAN→TRAIL→SEGMENTS/CHECKPOINTS→CONDITIONS; field per entitas.
- §15 Mountain ≠ Trail (difficulty & fit di level trail). §16 karakteristik rute. §17 difficulty multidimensi (physical, technical, navigation, terrain, environmental; overall = derived). §18 MDPL hanya deskriptif.
- §19 main user flow (landing→...→history, 21 langkah).

## Fungsional
- FR-01 auth (Given/When/Then). FR-02 profil (BEGINNER/INTERMEDIATE/ADVANCED/EXPERT). §22 profil = input, histori = evidence (BR-11).
- FR-03 goal (TEKTOK/CAMPING/MULTI_DAY). FR-04 Route Fit (COCOK/PERLU PERSIAPAN/KURANG COCOK). §25 tiga lapis (hard constraint, compatibility, preference). §26 hard constraints deterministik. §27 bobot V1 (experience 30, duration 15, terrain 15, technical 15, elevation gain 10, navigation 10, trip preference 5) = konfigurasi, bukan hukum. §28 fit_score internal saja. §29 label publik. §30 explainability (Why It Fits / What to Watch / Preparation Gap). §31 audit run (engine version, snapshots, rules, warnings; reproducible).
- FR-05 hasil rekomendasi (daftar kolom). FR-06 comparison: tanpa winner, **maks 5**, mengikat UI & server.
- FR-07 detail jalur 12 bagian (overview, route, elevation, checkpoints, terrain, technical, navigation, preparation, official status, weather, recent conditions, sources).
- FR-08 trip: 6 state closed set (DRAFT, PLANNED, READY_FOR_DEPARTURE, IN_PROGRESS, COMPLETED, CANCELLED), transisi tidak sah ditolak server-side.
- FR-09 preparation terkait rute (equipment, route knowledge, logistics, weather, official status, general physical — bukan penilaian medis). §37 CONFIRMED/NOT_CONFIRMED/NOT_APPLICABLE (BR-10).
- FR-10 readiness 3 dimensi; §39 READY / NEEDS PREPARATION / NOT RECOMMENDED (terbatas pada closure/restriction/hard incompatibility). §40 tidak pernah "Anda aman".
- FR-11 official status OPEN/RESTRICTED/CLOSED/UNKNOWN + metadata (source, url, published/fetched/verified/effective/expires, scope, reason, notes). §42 scope MOUNTAIN/TRAIL/SEGMENT/AREA. §43 authority OFFICIAL > ADMIN VERIFIED > COMMUNITY > AI.
- FR-12 cuaca BMKG (3 hari, 3 jam, 2×/hari, adm4, atribusi wajib) → "prakiraan area sekitar jalur". §45 scheduler→BMKG→validate→normalize→snapshot→cache→UI. §46 field minimum. §47 gagal ≠ mengarang ("Data cuaca belum berhasil diperbarui...").
- FR-13 laporan kondisi (tags MUDDY..WATER_SCARCE). §49 supplementary. §50 konflik → tampil berdampingan. §51 freshness relatif tanggal pendakian.
- FR-14 history. FR-15 hike mode minimal (tanpa background tracking/off-route/offline/emergency). §54 plan mode vs hike mode. §55 peta online; larang bulk/offline OSM standard tiles.
- FR-16 admin; FR-17 moderasi (PENDING/APPROVED/REJECTED/FLAGGED/REMOVED); FR-18 data source; §59 freshness per sumber (tanpa ambang universal); §60 trust model (timestamp terpisah: retrieved/published/verified).
- FR-19 audit log; FR-20 analytics funnel; §63 north star metric: % trip terencana yang menyelesaikan alur dari pemilihan rute sampai pre-departure check.

## Data & teknik (§64–§74)
- §64 daftar tabel (identity, geography, conditions, planning, activity, recommendation, governance). §65 PostGIS (Mountain POINT, Trail/Segment LINESTRING, Checkpoint POINT, Restricted Area POLYGON). §66 spatial index. §67 foto di Supabase Storage + kebijakan. §68 backup DB ≠ backup storage.
- §69–§74 arsitektur & service (RouteFit, Preparation, Readiness, ConditionAggregator, Weather, OfficialStatus, RecommendationExplanation, + Recommendation, Progress, DataSource). Controller tipis.

## Keamanan & privasi (§75–§86)
- Lapisan: browser→auth→authorization→validation→business rules→DB authorization→storage authorization. Policies/Gates; tidak percaya request.user_id/role. Object-level authz. RLS + grants. Service role server-only. Validasi semua input. Upload aman (MIME, size, ekstensi, ownership, path acak; produksi: malware scan, EXIF). Lokasi private by default; minimisasi data; kontrol privasi (lihat/ubah/hapus akun, visibility).
- §85 AI MVP: bukan decision-maker, rule-based. §86 AI masa depan boleh: NL explanation, ringkasan laporan, trip recap, conversational search; dilarang: status, diagnosis, ubah official, jamin keselamatan, mengarang.

## UX & NFR (§87–§99)
- §87 WCAG 2.2 AA minimum list. §88 mobile-first. §89 progressive disclosure (Apa ini→Cocok?→Mengapa→Tantangan→Persiapan→Kondisi). §90 explainability. §91 no false precision. §92 official vs community terbedakan visual. §93 freshness CURRENT/AGING/STALE/UNKNOWN (ambang per sumber).
- §94 reliability (fallback browse manual; status gagal → UNKNOWN). §95 default-safe. §96 target LCP ≤ 2,5 s, API kritis p95 ≤ 1 s (engineering target). §97 cache publik saja. §98 jobs. §99 observability + **nama event telemetry sebagai kontrak** (recommendation.generated ... trip.completed).

## Kualitas & cakupan (§100–§129)
- §100 strategi test (unit, feature, integration, E2E core journey, security IDOR/privesc/mass assignment/file/injection/rate limit). §101–§103 matriks. §104 edge cases.
- §105 MVP must-have 26 butir (termasuk #25 Pertimbangkan: save, shortlist, compare maks 5, read-only sharing; #26 personal progress). §106 MVP+ (basic hike mode, GPS, checkpoint field support, photo reports, PWA installability, elevation profile, richer field interaction). §107 future (offline maps, off-route, recording, live sharing, SOS, grup/klub/event/feed sosial, commerce, adaptive/predictive/AI coaching).
- §108 dataset 5–10 gunung, 2–5 jalur, 3–10 checkpoint. §109 kurasi. §110 syarat terbit: source, geometri, karakteristik dasar, checkpoint, metadata status.
- §111 riset: 5–8 pemula, 3–5 berpengalaman, 2–3 pengelola. §112 pertanyaan riset. §113 enam tugas validasi UX. §114–§120 acceptance criteria Given/When/Then.
- §121 asumsi (8). §122 risiko R1 wrong data, R2 false safety confidence (VERY HIGH), R3 community misinformation, R4 scope explosion, R5 external API failure, R6 GPS privacy.
- §123 tahap kesiapan: academic MVP → closed beta → public production (butuh legal/privacy review, backup, safety review, terms/privacy policy, dst.). §124 pertimbangan Supabase. §125 git: main/develop/feature/fix, PR direview minimal 1 anggota. §126 CI/CD. §127 tim lima peran. §128 fase 1–8. §129 DoD.

## Tata kelola (§130–§146)
- §130 perubahan PRD wajib 7 hal (requirement changed, reason, impact, affected modules, security, data, scope impact). §131 feature gate 5 kriteria (route selection, preparation, current-condition awareness, field usability, data trust).
- §132 BR-01..BR-16 (lihat teks). §133–§141 ringkasan "FINAL" (duplikasi bagian awal). §142 decision lock (Map: online MVP, offline future; AI bukan decision-maker; dst.). §143 status (MVP boundary diamandemen GR-01). §144 prinsip + filter "FIT/PREPARE/CHECK/HIKE/REPORT?".
- §145 FR-21 IA lima permukaan (Jelajah, Pertimbangkan [maks 5, catatan opsional, read-only sharing], Perjalanan, Kabar [bukan feed sosial], Progres [self-comparison]). §145.2 URL publik: /gunung/{slug}, /gunung/{slug}/via/{trail}, /panduan/{topic}, /kondisi/{trail}.
- DISKREPANSI D-B: kode memakai `/pendakian/{trail:slug}` (routes/web.php), bukan struktur §145.2.
- §146 GR-01: Pertimbangkan dari MVP+ ke MVP.
