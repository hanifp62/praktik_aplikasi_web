# Mountain Core — Canonical Domain & Product Rules

Berkas ini adalah **canonical owner** untuk R-001–R-014 dan R-022.

- **Provenance:** USER-PROVIDED CANONICAL SOURCE. Isi Statement, Scope, dan Invariant
  disalin dari sumber canonical pemilik produk, bukan diturunkan dari CLAUDE.md,
  DESIGN.md, PRD.md, riwayat sesi, atau inferensi agen.
- **Transitional duplication:** CLAUDE.md §11–§25 masih memuat representasi lama untuk
  rule-rule ini. Representasi tersebut berstatus PRESENT_NONCANONICAL / transitional
  dan berlaku sampai reachability `.claude/rules/` terbukti pada sesi baru. Ketika
  keduanya berbeda, berkas ini yang berlaku.
- **Ownership lain:** R-015–R-017 dimiliki `DESIGN.md`. R-018–R-021 dan R-023–R-024
  dimiliki `CLAUDE.md`. Berkas ini tidak memuat keduanya.

---

## R-001 | Mountain ≠ Trail

**Statement.** Mountain dan Trail adalah entitas/domain yang berbeda; Trail merupakan
objek operasional yang memiliki geometri, checkpoint, karakteristik, status, dan data
rute sendiri.

**Scope.** Model domain, data model, rekomendasi, planning, dan seluruh workflow yang
membedakan Mountain dari Trail.

**Invariant.** Jangan memperlakukan Mountain sebagai pengganti Trail atau menganggap
seluruh keputusan terhadap Mountain otomatis berlaku terhadap setiap Trail.

---

## R-002 | Trail adalah unit keputusan

**Statement.** Keputusan rekomendasi, kecocokan, persiapan, dan evaluasi dilakukan pada
level Trail, bukan semata-mata pada level Mountain.

**Scope.** Recommendation, Route Fit, preparation, comparison, planning, dan evaluation.

**Invariant.** Mountain-level information tidak boleh digunakan sebagai pengganti
karakteristik Trail ketika keputusan membutuhkan informasi rute.

---

## R-003 | Route Fit adalah differentiator inti

**Statement.** Route Fit menjadi inti mekanisme rekomendasi dan harus mempertimbangkan
kecocokan pengguna terhadap karakteristik rute.

**Scope.** Recommendation engine, recommendation explanation, planning, readiness, dan
user-facing fit decisions.

**Invariant.** Route Fit harus tetap menjadi konsep inti dalam recommendation flow dan
tidak boleh direduksi menjadi ranking sederhana berdasarkan satu atribut seperti MDPL.

---

## R-004 | Public fit labels terbatas

**Statement.** Label kecocokan yang boleh ditampilkan kepada publik hanya:
COCOK / PERLU PERSIAPAN / KURANG COCOK.

**Scope.** User-facing recommendation dan Route Fit presentation.

**Invariant.** Jangan memperkenalkan label publik tambahan yang mengubah kontrak ini
tanpa explicit product approval.

---

## R-005 | Tidak ada false precision untuk safety/fit

**Statement.** Sistem tidak boleh menampilkan persentase atau probabilitas
keselamatan/kecocokan kepada publik sebagai kepastian atau jaminan.

**Scope.** Recommendation UI, safety-related presentation, readiness, fit explanation,
dan public output.

**Invariant.** Internal calculation atau confidence mechanism tidak boleh berubah
menjadi klaim publik berupa jaminan keselamatan atau kepastian fit.

---

## R-006 | UNKNOWN ≠ LOW

**Statement.** Nilai/data yang UNKNOWN tidak boleh diperlakukan sebagai nilai rendah
atau kondisi buruk.

**Scope.** Data normalization, recommendation, scoring, readiness, filtering, dan
presentation.

**Invariant.** UNKNOWN harus tetap direpresentasikan sebagai UNKNOWN dan tidak boleh
secara diam-diam dipetakan menjadi LOW.

---

## R-007 | Missing data bukan nilai rendah

**Statement.** Data yang hilang/tidak tersedia tidak boleh secara implisit dikonversi
menjadi nilai rendah dan tidak boleh diisi dengan asumsi atau fabrikasi.

**Scope.** Data ingestion, scoring, recommendation, readiness, display, dan validation.

**Invariant.** Missing data harus tetap missing/unknown atau mengikuti explicit
data-quality policy; jangan mengarang nilai.

---

## R-008 | Critical incomplete Trail tidak masuk rekomendasi

**Posisi:** Recommendation Eligibility Gate. Menegakkan kelengkapan data pada sisi
pipeline rekomendasi. Bandingkan R-022, yang menegakkan hal setara pada sisi siklus
hidup publikasi. Keduanya canonical dan tidak digabung.

**Statement.** Trail yang kehilangan data kritis tidak boleh dipublikasikan sebagai
Trail yang dapat direkomendasikan dan tidak boleh masuk hasil rekomendasi.

**Scope.** Publication, recommendation candidate selection, recommendation output, dan
data quality gate.

**Invariant.** Critical incompleteness adalah exclusion condition untuk publication dan
recommendation.

---

## R-009 | Authority hierarchy

**Statement.** Prioritas sumber/status adalah:
OFFICIAL > ADMIN VERIFIED > COMMUNITY > AI.

**Scope.** Status trail/mountain, condition reports, source resolution, data
reconciliation, dan recommendation context.

**Invariant.** Informasi dengan authority lebih rendah tidak boleh menggantikan
informasi dengan authority lebih tinggi.

---

## R-010 | Community tidak boleh override official

**Statement.** Informasi atau laporan komunitas tidak boleh menggantikan atau menimpa
status resmi.

**Scope.** Community report, official status, condition report, recommendation context,
dan UI.

**Invariant.** Community information dapat menjadi contextual intelligence tetapi tidak
dapat menjadi override atas official status.

---

## R-011 | AI tidak boleh override official

**Statement.** Output atau inferensi AI tidak boleh menggantikan atau menimpa
informasi/status resmi.

**Scope.** AI-generated recommendation context, classification, summarization, dan
status interpretation.

**Invariant.** AI hanya dapat membantu interpretasi atau explanation dalam batas
authority hierarchy dan tidak dapat menjadi authority di atas official.

---

## R-012 | OPEN ≠ SAFE

**Statement.** Status OPEN tidak berarti seluruh Trail aman dan bukan merupakan jaminan
keselamatan.

**Scope.** Status display, recommendation explanation, readiness, warning, dan planning.

**Invariant.** OPEN harus tetap diperlakukan sebagai status operasional, bukan safety
guarantee.

---

## R-013 | Weather memiliki source/freshness/reference

**Statement.** Data cuaca harus memiliki sumber, freshness, dan reference area yang
jelas. Kegagalan retrieval tidak boleh menghasilkan data cuaca palsu/fabrikasi.

**Scope.** Weather ingestion, weather snapshot, recommendation context, planning, dan UI.

**Invariant.** Setiap weather decision-support output harus dapat dilacak ke source dan
freshness; failure harus menghasilkan explicit unavailable/stale handling, bukan
fabricated current weather.

---

## R-014 | Readiness adalah decision support

**Statement.** Readiness memberikan dukungan pengambilan keputusan dan bukan medical
clearance, diagnosis medis, ataupun jaminan keselamatan/legal clearance.

**Scope.** Readiness assessment, preparation guidance, user messaging, recommendation
explanation, dan warnings.

**Invariant.** Sistem tidak boleh mengklaim bahwa user "aman", "sehat", atau "medically
cleared" berdasarkan readiness feature.

---

## R-022 | Publication gate

**Posisi:** Publication Gate. Menegakkan kelengkapan data pada sisi siklus hidup Trail
dan validasi admin. Bandingkan R-008, yang menegakkan hal setara pada sisi pipeline
rekomendasi. Keduanya canonical dan tidak digabung.

**Statement.** Jika data kritis sebuah Trail belum tersedia, Trail tersebut tidak boleh
berstatus PUBLISHED dan tidak boleh masuk rekomendasi.

**Scope.** Trail lifecycle, publishing workflow, recommendation eligibility, dan admin
validation.

**Invariant.** Critical missing data = publication block + recommendation exclusion.
