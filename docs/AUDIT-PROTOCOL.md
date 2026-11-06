# Protokol Audit Kelengkapan Proyek

Berkas ini adalah **canonical owner** untuk protokol audit kelengkapan proyek dan
komponen. Isinya dipindahkan utuh dari `CLAUDE.md` §62–§85 (baris 1663–2328 pada
revisi sebelum pemangkasan), tanpa perubahan semantik.

- **Sifat:** audit-only / on-demand. Berkas ini **bukan** bagian dari kontrak
  always-on. Ia dibaca ketika pemilik produk meminta audit atau ketika sebuah
  checklist acuan eksternal diberikan, bukan pada setiap sesi.
- **Provenance:** dipindahkan dari `CLAUDE.md` atas keputusan pemilik produk (D1),
  dengan seluruh checklist A–N, evidence standard, scoring, dan fase audit
  dipertahankan apa adanya.

## Gerbang persetujuan tetap milik CLAUDE.md

Protokol ini menjelaskan **cara** mengaudit. Ia tidak memberi wewenang untuk bertindak
atas hasilnya.

- **R-018 — Git approval.** Push, merge, tag, release, force push, dan penulisan ulang
  riwayat memerlukan persetujuan eksplisit pemilik. Lihat `CLAUDE.md`.
- **R-021 — Product-direction approval.** Perubahan yang berdampak pada arah produk,
  Route Fit, batas MVP, arsitektur navigasi, hierarki otoritas, kebijakan privasi, atau
  arsitektur basis data memerlukan persetujuan eksplisit pemilik. Lihat `CLAUDE.md`.

§84 pada berkas ini ("Implementation Phase After Audit") menyatakan bahwa perubahan kode
tidak boleh dilakukan sebelum rencana audit disetujui, dan bahwa pekerjaan berhenti
setelah batch CRITICAL untuk meminta persetujuan berikutnya. Kewajiban itu berasal dari
R-018 dan R-021 dan tetap berlaku penuh; berkas ini tidak dapat melonggarkannya.

---

# 62. AUDIT MODE — PROJECT + COMPONENT COMPLETENESS

Use this mode whenever the owner provides a reference component checklist/specification or explicitly asks to audit against an external reference.

The audit must adapt the reference to THIS PROJECT. Do NOT blindly force unrelated e-commerce concepts into a hiking product.

The audit standard is:
> “Same functional/architectural intent or stronger, adapted to the mountain-hiking domain.”

---

# 63. AUDIT INPUTS

Expected placeholders:
- `{{PATH_FILE_ACUAN}}` = reference file path;
- `{{PATH_PROJECT}}` = project root;
- `{{TECH_STACK}}` = actual stack;
- `{{DOMAIN}}` = mountain hiking domain.

If the reference cannot be read:
- state exactly which path failed;
- request pasted content or accessible format;
- STOP if the audit cannot be performed honestly.

If the project root cannot be accessed:
- report the failure;
- STOP.

Do not invent evidence.

---

# 64. AUDIT PHASE 0 — ACCESS CONFIRMATION

Before auditing:
1. Confirm reference file readable: YES/NO.
2. Confirm project root accessible: YES/NO.
3. List the files successfully inspected.
4. List failed paths.
5. Confirm commands/tools available.
6. If essential source cannot be read, stop rather than guessing.

---

# 65. AUDIT CHECKLIST A — FUNCTIONAL MODULES

For each item use:
- ✅ ADA;
- ⚠️ SEBAGIAN;
- ❌ TIDAK ADA;
- ❓ PERLU KLARIFIKASI;
- ➖ N/A only with explicit reason.

### A1
Hiker authentication: login/register/logout/password recovery.

### A2
Hiker profile and experience management.

### A3
Hiking goal creation/configuration.

### A4
Mountain management (admin CRUD + source).

### A5
Trail management (admin CRUD + publish gate).

### A6
Trail segment management.

### A7
Checkpoint management.

### A8
Geospatial route data / PostGIS handling.

### A9
Route Fit Engine.

### A10
Recommendation explanation/explainability.

### A11
Search/discovery.

### A12
Filter by relevant route characteristics.

### A13
Trail detail.

### A14
Pertimbangkan: save/shortlist.

### A15
Comparison up to 5.

### A16
Read-only comparison sharing.

### A17
Trip planning.

### A18
Route-specific preparation.

### A19
Readiness check.

### A20
Official mountain/trail/segment status.

### A21
Weather integration/snapshot/cache.

### A22
Community trail-condition reports.

### A23
Community moderation.

### A24
Hiking history.

### A25
Personal progression.

### A26
Basic Hike Mode / current GPS if MVP+ implemented.

### A27
Admin dashboard/governance.

### A28
Source/provenance/freshness management.

### A29
Audit logging.

### A30
Account deletion/privacy flow.

### A31
Notification mechanism if explicitly in scope.

### A32
SEO/public discovery surfaces.

### A33
Share/referral surfaces where implemented.

---

# 66. AUDIT CHECKLIST B — DATABASE TABLES / PERSISTENCE

Audit actual migrations/models/schema, not documentation only.

### Identity
B1 `users`
B2 roles/permissions/admin authorization
B3 `profiles`
B4 preferences/experience/hiking goals where modeled separately

### Geography/domain
B5 `mountains`
B6 `trails`
B7 `trail_segments`
B8 `checkpoints`
B9 restricted areas/geofences if in scope

### Data trust/current state
B10 `data_sources`
B11 `official_statuses`
B12 `weather_snapshots`
B13 freshness/verification state if separate
B14 `trail_condition_reports`
B15 report media/storage references if implemented

### Planning
B16 `trips`
B17 preparation templates
B18 trip preparation items
B19 readiness records/snapshots where implemented

### Recommendation
B20 `recommendation_runs`
B21 `recommendation_results`
B22 route/trail snapshot in recommendation result if implemented
B23 engine/rule version storage where implemented

### Consideration/progression
B24 `trail_considerations`
B25 follows/saved entities if in scope
B26 hiking sessions/history
B27 `hike_track_points` if MVP+
B28 progression aggregates if persisted separately

### Governance/testing/ops
B29 `audit_logs`
B30 usability sessions/studies if implemented
B31 scheduled task/job runs if implemented
B32 report-thanks/engagement tables if implemented
B33 sessions/password-reset structures where framework/application require them

A missing table is NOT automatically a gap if the same concern is correctly modeled in another table/service. Explain the mapping.

---

# 67. AUDIT CHECKLIST C — RELATIONS / FOREIGN KEYS

### Identity/domain
C1 profile → users
C2 hiking goals/preferences → users
C3 mountain/trail relation
C4 trail segment → trail
C5 checkpoint → trail/segment

### Planning/recommendation
C6 trip → users
C7 trip → trail
C8 preparation item → trip/template
C9 recommendation run → users
C10 recommendation result → recommendation run/trail

### Conditions/data
C11 official status → subject/source
C12 weather snapshot → trail/reference area/source
C13 report → user/trail/optional segment
C14 report media → report/storage ownership

### Consideration/progress
C15 consideration → user/trail
C16 hike session/history → user/trail/trip
C17 track points → hike session where implemented

### Governance
C18 audit log → actor/user where appropriate
C19 source references are consistent

Also check:
- correct onDelete/onUpdate behavior;
- no orphan records after allowed deletion;
- privacy/deletion behavior is intentional.

---

# 68. AUDIT CHECKLIST D — PK / INDEX / CONSTRAINTS

D1 PK on each persisted entity/table.
D2 Unique constraints on truly unique values (email/slug/source identifiers/etc.).
D3 NOT NULL on mandatory fields.
D4 CHECK constraints where supported/appropriate for state/range integrity.
D5 Foreign-key indexes or planner-appropriate indexes.
D6 Search indexes for actual query patterns.
D7 Status indexes.
D8 Date/time indexes for reports/history/jobs/status snapshots as needed.
D9 Geospatial indexes for PostGIS fields.
D10 Composite indexes for proven frequent multi-column filters.
D11 Unique constraint for user+trail consideration if only one active consideration is allowed.
D12 Indexes/constraints for recommendation snapshots/versioning.
D13 No duplicate integrity rule implemented only at application layer when DB constraint is appropriate.

Do not add indexes without evidence/query need.

---

# 69. AUDIT CHECKLIST E — DATA TYPES / DATABASE STANDARDS

Adapt to actual PostgreSQL/Supabase stack; DO NOT force MySQL-specific rules.

E1 IDs use PostgreSQL-appropriate integer/UUID strategy consistently.
E2 Monetary fields, if any, use suitable exact numeric/DECIMAL types.
E3 Names/titles use bounded VARCHAR/text appropriately.
E4 Long descriptions use TEXT.
E5 Timestamps use timezone-aware semantics consistently where required.
E6 Boolean fields use BOOLEAN.
E7 Database encoding is appropriate to PostgreSQL (UTF-8).
E8 Collation/locale policy is documented for PostgreSQL; do NOT blindly require `utf8mb4_unicode_ci`.
E9 Passwords use framework-supported secure hashing (Argon2/bcrypt as configured).
E10 Geometry columns use suitable PostGIS type/SRID.
E11 Numeric ranges prohibit impossible negative values where domain rules require.
E12 Enum/status values are constrained consistently (DB enum/check/reference table according to architecture).

---

# 70. AUDIT CHECKLIST F — TIMESTAMP / AUDIT TRAIL

F1 created_at on persisted user/domain entities where appropriate.
F2 updated_at where mutable.
F3 deleted_at/soft delete where policy requires it.
F4 created_by/updated_by or actor linkage for admin-governed data where appropriate.
F5 audit_logs for critical actions.
F6 official status history/provenance.
F7 order-like/trip state history where history matters.
F8 moderation history.
F9 recommendation snapshot timestamp.
F10 data-source fetch/verification timestamps.
F11 job execution timestamps.
F12 account-deletion/anonymization event trace where legally/operationally appropriate.

Do not force created_by/updated_by onto every system table if actor identity does not conceptually apply; document the rationale.

---

# 71. AUDIT CHECKLIST G — SECURITY / PRIVACY

G1 secure password hashing.
G2 no CVV/payment card storage unless a future payment integration has an explicit compliant architecture; for current MVP payment may be N/A.
G3 tokenized payment gateway only if payment enters scope; otherwise N/A with reason.
G4 RBAC/policies.
G5 object-level authorization/IDOR protection.
G6 input validation/sanitization/normalization.
G7 rate limiting.
G8 CSRF protection.
G9 XSS-safe output/escaping.
G10 SQL injection prevention through parameterized ORM/query builder/raw query discipline.
G11 audit logs for critical actions.
G12 RLS where exposed Supabase tables require it.
G13 storage object authorization.
G14 secret management.
G15 location privacy.
G16 account deletion/anonymization behavior.
G17 community content moderation/security.
G18 external integration secret isolation.
G19 secure headers/session/cookie settings as appropriate.
G20 dependency/security vulnerability checks.

When a reference checklist contains e-commerce-specific payment requirements, classify them as N/A unless payment is genuinely part of current product scope.

---

# 72. AUDIT CHECKLIST H — DOCUMENTATION / MIGRATION / KNOWLEDGE

H1 migration files exist and are source-controlled.
H2 seeders/factories exist for required demo/test data.
H3 ERD/domain diagram exists or equivalent schema documentation.
H4 data dictionary exists or can be generated.
H5 API/route/domain-service documentation exists where applicable.
H6 schema versioning through migrations.
H7 PRD/implementation PRD synchronized with code.
H8 business rules documented.
H9 source/provenance policy documented.
H10 privacy/deletion policy documented.
H11 README setup instructions current.
H12 task backlog traces to user stories/acceptance criteria.
H13 CLAUDE/agent operating instructions current.
H14 runbook/operational instructions for scheduled jobs/external integrations where needed.

---

# 73. AUDIT CHECKLIST I — PERFORMANCE / SCALABILITY

I1 No N+1 on primary flows.
I2 Eager/batch loading used appropriately.
I3 Pagination on unbounded lists.
I4 Cache where query/provider cost justifies it.
I5 Search indexes/full-text only when justified by actual search design.
I6 Filter indexes.
I7 Geospatial indexes.
I8 Bounded recommendation queries.
I9 External API calls are scheduled/cached rather than per-user-page where appropriate.
I10 Image/storage optimization where media exists.
I11 Payload/page size acceptable on mobile.
I12 Critical endpoint p95 measured in representative environment.
I13 Queue/background jobs used for long-running/non-interactive work.
I14 No microservice fragmentation without evidence.

---

# 74. AUDIT CHECKLIST J — TESTING / QUALITY

J1 Unit tests.
J2 Feature tests.
J3 Integration tests.
J4 E2E tests.
J5 Security tests.
J6 CI/CD pipeline.
J7 PostgreSQL-compatible integration tests for DB-critical behavior.
J8 PostGIS tests where geospatial logic exists.
J9 Regression tests for fixed production defects.
J10 Authorization/role tests.
J11 Data freshness/fallback tests.
J12 Recommendation determinism tests.
J13 Publication-gate tests.
J14 UNKNOWN vs LOW tests.
J15 Official-vs-community precedence tests.
J16 Account-deletion/privacy tests.
J17 Accessibility tests/checks.
J18 Mobile 400px/reflow checks.
J19 Performance/load tests at an appropriate scale.
J20 External integration failure tests.
J21 Scheduler/job tests.
J22 Database migration verification.

Testing score MUST NOT use only test count. Quality considers whether the right risks have coverage.

---

# 75. AUDIT CHECKLIST K — OBSERVABILITY / OPERATIONS

K1 Structured/application logging.
K2 Error tracking.
K3 Metrics/monitoring.
K4 Health check endpoint where appropriate.
K5 Job/queue monitoring.
K6 External-provider health/failure visibility.
K7 Data freshness monitoring.
K8 Audit log visibility for authorized admins.
K9 Application/runtime environment verification.
K10 Deployment/scheduler verification.
K11 Alerting for critical failure paths where appropriate.
K12 Product telemetry for critical journey events.
K13 Operational runbook for recovery/failure.
K14 Backup/restore strategy appropriate to data and storage.

---

# 76. AUDIT CHECKLIST L — UX/HCI / PRODUCT EXPERIENCE

This category is mandatory for this project because UX/HCI is the owner's first priority.

L1 Five surfaces are coherent: Jelajah, Pertimbangkan, Perjalanan, Kabar, Progres.
L2 Core loop is visible in actual interactions.
L3 Route Fit is understandable without technical docs.
L4 Recommendation has explainable reasons.
L5 Progressive disclosure is implemented.
L6 Compare supports trade-offs rather than winner/ranking.
L7 Official vs community is visually distinct.
L8 UNKNOWN is visibly different from a low value.
L9 Current condition has freshness/source context.
L10 Empty states are truthful and useful.
L11 Error/fallback states do not invent data.
L12 Mobile behavior at 400px verified.
L13 Hike Mode has reduced cognitive load where implemented.
L14 Primary actions are reachable and understandable.
L15 Accessibility criteria tested.
L16 User can tell what happens next at major decision points.
L17 No unnecessary feature/visual clutter.
L18 Copy is concise, specific, and non-generic.
L19 UX is validated with users, not only internal opinions.
L20 UX improvements are backed by measurable outcomes where feasible.

---

# 77. AUDIT CHECKLIST M — DATA TRUST / GOVERNANCE

M1 Every critical published trail has required provenance.
M2 Official status is scoped and timestamped.
M3 Community report provenance is visible.
M4 Freshness state exists for relevant dynamic data.
M5 Recommendation snapshots are reproducible.
M6 Source hierarchy is enforced.
M7 Publication gate is enforced in code, not only admin convention.
M8 Partial data is truthful (`UNKNOWN`, not invented/low-scored).
M9 Account deletion preserves/erases data according to policy.
M10 Personal location is private by default.
M11 Data retention is documented.
M12 Moderation/audit supports traceability.
M13 Seed/curated dataset has source metadata.
M14 Conflicting data does not silently overwrite authoritative state.

---

# 78. AUDIT CHECKLIST N — PRODUCT GROWTH / MARKETING

N1 Public pages have clear user/search intent.
N2 People-first content strategy.
N3 Indexable public trail pages are technically accessible.
N4 Canonical/metadata/sitemap basics implemented where appropriate.
N5 Structured data is truthful and supported by visible content.
N6 Search → trail page → Route Fit activation path exists.
N7 Shareable comparison/trip surface exists where in scope.
N8 Community contribution loop exists.
N9 Personal progression supports retention.
N10 Growth does not compromise data trust or safety language.
N11 Marketing claims match actual product behavior.
N12 Analytics measure acquisition → activation → planning → return → contribution.

---

# 79. AUDIT EVIDENCE STANDARD

For every audit item:
- status;
- concrete evidence;
- impact;
- verification method.

Evidence should include where applicable:
- file path;
- line range;
- class/model/service name;
- migration name;
- table name;
- index/constraint name;
- route name/path;
- controller/service method;
- view/component path;
- test file/test name;
- runtime output;
- screenshot/browser evidence;
- DB status/query result.

No evidence = no PASS.

If evidence is ambiguous:
`❓ PERLU KLARIFIKASI`

---

# 80. AUDIT SCORING

For each category, calculate:
- total applicable items;
- ✅ count;
- ⚠️ count;
- ❌ count;
- ❓ count;
- ➖ N/A count.

Primary audit completeness score:
`✅ / ALL APPLICABLE ITEMS × 100%`

`❓` is NOT a pass and counts against the provisional score until resolved.

N/A items are excluded only when a concrete domain/scope reason exists.

Status thresholds requested by owner:
- 🟢 SEHAT: ≥80%
- 🟡 PERLU PERBAIKAN: 50–79%
- 🔴 KRITIS: <50%

Important:
A high percentage does NOT override any CRITICAL security, privacy, data-integrity, or runtime failure. Report critical gates separately.

---

# 81. AUDIT PHASE 0 — CONFIRM ACCESS

Before auditing, explicitly report:
1. Reference file readable: YES/NO.
2. Project root accessible: YES/NO.
3. Files successfully inspected.
4. Files that failed to read.
5. Commands/tools available.

If an essential reference cannot be read and cannot be reconstructed honestly:
STOP and ask for the missing content.

---

# 82. AUDIT PHASE 1 — INSPECTION

Inspect directly:
- folder structure;
- migrations/schema/DDL;
- models/entities;
- controllers/routes;
- services/domain logic;
- views/UI components;
- config without exposing secrets;
- seeders/factories;
- tests;
- docs/README/PRD;
- Git status/branch;
- environment/runtime versions;
- DB migration state where access is allowed;
- build/lint results.

Do not audit from documentation only.

---

# 83. AUDIT PHASE 2 — REPORT

Produce `AUDIT-REPORT.md` with exactly:

## 1. Ringkasan Eksekutif
- score;
- status;
- top 5 critical/high gaps.

## 2. Metodologi
- reference file;
- project files inspected;
- commands run;
- limits/assumptions.

## 3. Hasil Audit per Category A–N
Table:
`# | Item | Status | Evidence | Impact | Verification`

## 4. Gap Analysis
Table:
`Category | Total | ✅ | ⚠️ | ❓ | ❌ | N/A | %`

Plus critical gaps.

## 5. Components To Complete
For every ⚠️/❌/❓:
- GAP-ID;
- component;
- why important;
- consequence;
- priority: CRITICAL/HIGH/MEDIUM/LOW;
- effort: S/M/L/XL;
- dependencies;
- evidence.

## 6. Implementation Plan
Order by priority + dependency:
- Sprint 1 — CRITICAL;
- Sprint 2 — HIGH;
- Sprint 3 — MEDIUM;
- Sprint 4 — LOW.

Each item:
- files to create/change;
- migration/schema impact;
- model/controller/service/view;
- tests;
- runtime verification;
- UX/security/observability implications.

## 7. Next Action
3–5 concrete next actions in order.

---

# 84. IMPLEMENTATION PHASE AFTER AUDIT

NEVER change code before owner approval of the audit plan when audit mode is explicitly requested.

After explicit approval:
1. Work one prioritized gap at a time.
2. Create migration if schema changes.
3. Update model/service/controller/view as needed.
4. Add tests before/alongside implementation.
5. Run tests.
6. Verify runtime.
7. Verify DB migration state when schema changed.
8. Verify UX/accessibility/security where applicable.
9. Perform antislop pass.
10. Report evidence for the item.
11. Stop after CRITICAL batch and request approval before HIGH unless owner explicitly authorizes the full sequence.

---

# 85. IMPLEMENTATION REPORT PER ITEM

For each completed gap:
- GAP-ID;
- files changed;
- schema/migration changes;
- behavior change;
- tests run;
- test result;
- runtime result;
- security impact;
- UX impact;
- observability impact;
- remaining risks.

---
