# PRODUCT REQUIREMENTS DOCUMENT (PRD)

## Personalized Mountain Planning & Readiness Platform

**Document Version:** PRD v2.0 FINAL  
**Product Status:** Concept LOCKED — Development Baseline  
**Platform:** Responsive Web Application / PWA-ready  
**Primary Framework:** Laravel Framework 13.32.0  
**Runtime:** PHP 8.5  
**Database:** PostgreSQL / Supabase  
**Geospatial Database:** PostGIS  
**Map Renderer:** MapLibre-compatible  
**Weather Source:** BMKG  
**Official Mountain/Volcanic Status:** Authoritative official sources such as Badan Geologi/MAGMA and route/park/basecamp management  
**Accessibility Target:** WCAG 2.2 AA  
**Primary Users:** Mountain hikers, especially beginners through experienced hikers  
**Team:** 5 developers/students  
**MVP Type:** Production-like academic/portfolio MVP

---

# 1. PRODUCT EXECUTIVE SUMMARY

## 1.1 Product Definition

Personalized Mountain Planning & Readiness Platform adalah aplikasi web yang membantu pendaki menentukan:

> **jalur mana yang paling sesuai dengan profil dan rencana pendakiannya, apa tantangan jalur tersebut, apa yang perlu dipersiapkan, bagaimana kondisi terbaru, dan apa yang dapat dipelajari dari pengalaman pendakian sebelumnya.**

Produk tidak diposisikan sebagai sekadar:

- katalog gunung;
- aplikasi GPS;
- aplikasi checklist;
- aplikasi cuaca;
- media sosial;
- atau marketplace pendakian.

Produk merupakan:

> **personalized hiking decision-support platform.**

---

# 2. FINAL PRODUCT CONCEPT

## 2.1 Core Problem

Informasi pendakian tersedia dalam banyak sumber, tetapi tersebar.

Pendaki dapat harus menggabungkan:

- profil pengalaman pribadi;
- informasi gunung;
- informasi jalur;
- jarak;
- elevation gain;
- durasi;
- terrain;
- tingkat teknis;
- navigasi;
- cuaca;
- status resmi;
- kondisi lapangan;
- perlengkapan;
- pengalaman pendaki lain.

Masalah utama bukan:

> “Pendaki tidak dapat menemukan informasi gunung.”

Masalah yang lebih penting:

> **Pendaki kesulitan mengubah berbagai informasi tersebut menjadi keputusan yang relevan dengan dirinya dan rencana pendakiannya.**

---

# 3. PRODUCT OPPORTUNITY

Produk mengubah proses:

```text
Cari Gunung
↓
Baca Informasi
↓
Cari Review
↓
Cari Cuaca
↓
Cari Checklist
↓
Tentukan Sendiri
```

menjadi:

```text
Profil Pendaki
↓
Rencana Pendakian
↓
Route Fit
↓
Alasan + Tantangan
↓
Preparation Plan
↓
Official Status + Weather + Condition
↓
Pre-departure Check
↓
Hike
↓
Report
```

---

# 4. PRODUCT VISION

> **Membantu pendaki mengambil keputusan pendakian yang lebih terinformasi dengan menghubungkan profil pendaki, karakteristik jalur, rencana perjalanan, dan kondisi terbaru dalam satu alur yang dapat dijelaskan.**

---

# 5. PRODUCT MISSION

1. Mempermudah pemilihan jalur berdasarkan konteks pengguna.
2. Memberikan recommendation yang dapat dijelaskan.
3. Mengubah rekomendasi menjadi preparation plan.
4. Membedakan informasi resmi, terverifikasi, komunitas, dan interpretasi sistem.
5. Menyediakan informasi penting menjelang dan selama pendakian.
6. Membentuk feedback loop melalui trail condition report.

---

# 6. PRODUCT NORTH STAR

> **Membantu pengguna berpindah dari “ingin mendaki” menjadi “memahami jalur yang dipilih, persiapan yang dibutuhkan, kondisi yang perlu diperiksa, dan hasil perjalanan.”**

---

# 7. CORE PRODUCT LOOP

```text
PROFILE
   ↓
ROUTE FIT
   ↓
PLAN
   ↓
PREPARE
   ↓
CHECK
   ↓
HIKE
   ↓
REPORT
   ↓
IMPROVE
```

Semua fitur utama harus memiliki hubungan dengan loop tersebut.

---

# 8. PRODUCT DIFFERENTIATOR

## Explainable Mountain–Hiker Fit Engine

Core differentiator bukan:

> “memiliki banyak gunung.”

Bukan:

> “memiliki GPS.”

Bukan:

> “memiliki review.”

Bukan:

> “memiliki checklist.”

Melainkan:

> **menghubungkan karakteristik pendaki dengan karakteristik jalur dan konteks perjalanan, kemudian menjelaskan alasan kecocokan tersebut.**

---

# 9. PRODUCT POSITIONING

### Produk bukan

> “Aplikasi daftar gunung Indonesia.”

### Produk bukan

> “Strava untuk pendaki.”

### Produk bukan

> “Aplikasi keselamatan pendakian.”

### Produk adalah

> **Platform perencanaan, persiapan, dan decision-support pendakian yang dipersonalisasi.**

---

# 10. TARGET USERS

## Persona 1 — Beginner

Karakteristik:

- pengalaman terbatas;
- sulit membaca karakteristik jalur;
- cenderung mengandalkan rekomendasi;
- belum mengetahui preparation requirement;
- membutuhkan informasi sederhana dan terstruktur.

Primary need:

> “Jalur mana yang realistis untuk saya?”

---

## Persona 2 — Intermediate

Karakteristik:

- sudah memiliki beberapa pengalaman;
- memahami data dasar jalur;
- mulai memiliki preferensi;
- ingin membandingkan rute.

Primary need:

> “Jalur mana yang paling sesuai dengan target saya?”

---

## Persona 3 — Advanced/Experienced

Karakteristik:

- memahami medan;
- memahami navigasi;
- memiliki histori perjalanan;
- membutuhkan data kondisi yang aktual.

Primary need:

> “Apa kondisi terbaru dari jalur yang ingin saya ambil?”

---

## Persona 4 — Admin/Data Curator

Primary need:

> “Bagaimana menjaga agar informasi jalur, status, source, dan community report tetap akurat dan terkontrol?”

---

# 11. JOBS TO BE DONE

### JTBD-01

Ketika saya ingin mendaki, saya ingin menemukan jalur yang sesuai dengan pengalaman dan rencana saya.

### JTBD-02

Ketika saya memilih jalur, saya ingin memahami tantangan spesifik jalur tersebut.

### JTBD-03

Ketika saya mendekati hari pendakian, saya ingin memeriksa status resmi, cuaca, dan kondisi terbaru.

### JTBD-04

Ketika saya selesai mendaki, saya ingin melaporkan kondisi jalur agar pengguna berikutnya mendapatkan informasi tambahan.

---

# 12. PRODUCT GOALS

## G1 — Route Selection

Membantu user menemukan jalur yang relevan.

## G2 — Preparation

Membantu user mengetahui kebutuhan persiapan.

## G3 — Information Trust

Menampilkan source dan freshness.

## G4 — Continuity

Menyimpan trip history.

## G5 — Data Feedback

Menggunakan trail reports sebagai informasi tambahan.

---

# 13. NON-GOALS

MVP tidak bertujuan menjadi:

- emergency command center;
- medical application;
- marketplace;
- open trip platform;
- porter booking;
- social-media clone;
- advanced navigation platform;
- offline navigation platform;
- wearable platform;
- autonomous AI safety system.

---

# 14. CORE DOMAIN MODEL

Model produk:

```text
HIKER
   │
   ├── PROFILE
   ├── PREFERENCES
   └── HIKING HISTORY
          │
          ▼
       TRIP PLAN
          │
          ▼
       TRAIL
          │
    ┌─────┴─────┐
    ▼           ▼
 SEGMENTS   CHECKPOINTS
    │
    ▼
 CONDITIONS
```

---

# 15. CRITICAL DOMAIN DECISION

## Mountain ≠ Trail

Satu gunung dapat mempunyai:

```text
Mountain
 ├── Trail A
 ├── Trail B
 └── Trail C
```

Karena itu, difficulty dan route fit **tidak boleh hanya melekat pada gunung**.

Difficulty dan route characteristics berada terutama pada level **trail**.

---

# 16. ROUTE CHARACTERISTICS

Setiap trail dapat mempunyai:

- distance;
- elevation gain;
- elevation loss;
- estimated duration;
- technical demand;
- terrain;
- navigation complexity;
- camping availability;
- water availability;
- route geometry;
- checkpoint;
- segment characteristics.

---

# 17. DIFFICULTY MODEL

Jangan menyimpan:

> Difficulty = HARD

sebagai satu-satunya fakta.

Gunakan multidimensional characteristics:

```text
Physical Demand
Technical Demand
Navigation Complexity
Terrain Character
Environmental Consideration
```

Overall difficulty dapat menjadi **derived label**, bukan satu-satunya source of truth.

---

# 18. MDPL RULE

MDPL hanya merupakan:

> **descriptive attribute**

bukan primary difficulty determinant.

Primary recommendation lebih dipengaruhi:

> distance + elevation gain + duration + terrain + technical + navigation + context.

Contoh:

```text
Mountain A
2.000 MDPL
Elevation Gain 1.400 m

Mountain B
3.000 MDPL
Elevation Gain 700 m
```

Gunung B tidak otomatis lebih berat hanya karena lebih tinggi.

---

# 19. MAIN USER FLOW

```text
Landing
↓
Register/Login
↓
Profile Setup
↓
Experience Setup
↓
Hiking Goal
↓
Route Fit
↓
Recommendation
↓
Why This Route?
↓
Trail Detail
↓
Create Trip
↓
Preparation Plan
↓
Readiness Check
↓
Official Status
↓
Weather
↓
Recent Trail Condition
↓
Pre-departure Check
↓
Hike Mode
↓
Trip Complete
↓
Trail Condition Report
↓
History
```

---

# 20. FR-01 AUTHENTICATION

## Features

- register;
- login;
- logout;
- password reset;
- email verification jika diperlukan;
- session handling.

## Acceptance Criteria

**Given** user belum authenticated.

**When** membuka private page.

**Then** system meminta authentication.

**Given** user logout.

**Then** protected session tidak dapat digunakan kembali.

---

# 21. FR-02 HIKER PROFILE

Minimum profile:

- experience level;
- hiking experience;
- completed hikes;
- terrain experience;
- navigation experience;
- preferred duration;
- preferred trip type;
- region preference.

Experience level:

```text
BEGINNER
INTERMEDIATE
ADVANCED
EXPERT
```

---

# 22. PROFILE PRINCIPLE

Self-reported experience adalah:

> **input**

bukan certification.

Histori pendakian adalah:

> **evidence/context**

bukan automatic promotion.

User yang menyelesaikan satu pendakian sulit tidak otomatis berubah:

```text
BEGINNER → INTERMEDIATE
```

---

# 23. FR-03 HIKING GOAL

User mengisi:

- target date;
- region;
- trip type;
- expected duration;
- preferred challenge;
- optional elevation preference.

Trip type:

```text
TEKTOK
CAMPING
MULTI_DAY
```

---

# 24. FR-04 ROUTE FIT ENGINE

Route Fit adalah jantung sistem.

Input:

```text
Hiker Profile
+
Trip Goal
+
Trail Characteristics
+
Current Status
```

Output:

```text
COCOK
PERLU PERSIAPAN
KURANG COCOK
```

---

# 25. ROUTE FIT ENGINE — THREE-LAYER MODEL

## Layer 1 — Hard Constraint

Menentukan:

> Apakah route masih eligible?

## Layer 2 — Compatibility

Menentukan:

> Seberapa sesuai route dengan kemampuan dan context?

## Layer 3 — Preference

Menentukan:

> Seberapa sesuai dengan preferensi user?

---

# 26. HARD CONSTRAINTS

Contoh:

```text
Official Status = CLOSED
→ EXCLUDE
```

```text
Trip duration target = 1 day
Route estimated duration = multi-day
→ EXCLUDE FROM DEFAULT RECOMMENDATION
```

```text
Required route access condition not satisfied
→ EXCLUDE
```

Hard constraints bersifat deterministic.

---

# 27. COMPATIBILITY FACTORS

Initial model:

| Faktor | Initial Weight |
|---|---:|
| Experience Match | 30% |
| Duration Match | 15% |
| Terrain Match | 15% |
| Technical Match | 15% |
| Elevation Gain Match | 10% |
| Navigation Match | 10% |
| Trip Preference | 5% |

Bobot ini adalah:

> **V1 configuration**

bukan hukum alam.

Setelah user research, hasil usability testing, dan evaluasi dataset, bobot boleh dikalibrasi.

---

# 28. INTERNAL SCORE

System boleh memiliki:

```text
fit_score
```

untuk internal ranking.

Contoh:

```text
fit_score = 82.4
```

Tetapi angka tersebut:

> **tidak ditampilkan sebagai safety score kepada user.**

---

# 29. PUBLIC ROUTE FIT LABEL

User hanya melihat:

### COCOK

Karakteristik route secara umum compatible dengan profile/context.

### PERLU PERSIAPAN

Route masih relevan tetapi terdapat gap yang perlu diselesaikan.

### KURANG COCOK

Ada mismatch yang cukup besar terhadap profil atau rencana.

---

# 30. ROUTE EXPLAINABILITY

Setiap recommendation harus memiliki:

### Why It Fits

- experience compatible;
- duration compatible;
- terrain compatible.

### What to Watch

- high elevation gain;
- technical segment;
- moderate navigation.

### Preparation Gap

- equipment belum confirmed;
- route briefing belum selesai;
- weather belum diperiksa.

---

# 31. RECOMMENDATION AUDIT

Setiap recommendation run menyimpan:

- engine version;
- input snapshot;
- rules evaluated;
- matched rules;
- failed rules;
- warnings;
- generated_at.

Tujuan:

- debugging;
- reproducibility;
- audit;
- research.

---

# 32. FR-05 RECOMMENDATION RESULT

Recommendation page menampilkan:

```text
Trail Name
Mountain
Route Fit
Distance
Elevation Gain
Estimated Duration
Technical Level
Terrain
Navigation
Current Status
Current Condition
Preparation Gap
Why This Route?
```

---

# 33. FR-06 ROUTE COMPARISON

User dapat membandingkan route.

Perbandingan:

- distance;
- elevation gain;
- duration;
- terrain;
- technical;
- navigation;
- camping;
- status;
- recent reports.

Comparison tidak menghasilkan:

> “Winner.”

Sistem hanya membantu user memahami perbedaan.

---

# 34. FR-07 TRAIL DETAIL

Section:

1. Overview
2. Route
3. Elevation
4. Checkpoints
5. Terrain
6. Technical sections
7. Navigation
8. Preparation
9. Official status
10. Weather
11. Recent conditions
12. Sources

---

# 35. FR-08 TRIP PLAN

Trip memiliki:

```text
DRAFT
PLANNED
READY_FOR_DEPARTURE
IN_PROGRESS
COMPLETED
CANCELLED
```

Fields:

- trip name;
- route;
- date;
- start time;
- trip type;
- notes.

---

# 36. FR-09 PREPARATION PLAN

Preparation bukan generic checklist.

Preparation harus dikaitkan dengan route.

Categories:

### Equipment

- footwear;
- lighting;
- weather protection;
- navigation;
- emergency essentials.

### Route Knowledge

- route review;
- checkpoint review;
- technical segment review.

### Logistics

- starting point;
- transportation;
- permit/access;
- camping plan;
- emergency contact.

### Weather

- forecast checked;
- warning checked.

### Official Status

- latest status checked.

---

# 37. PREPARATION STATUS

Checklist menggunakan:

```text
CONFIRMED
NOT_CONFIRMED
NOT_APPLICABLE
```

Jangan menginterpretasikan:

> unchecked = equipment tidak dimiliki.

Contoh:

> Headlamp — NOT CONFIRMED

bukan:

> Headlamp — NOT AVAILABLE.

---

# 38. FR-10 READINESS MODEL

Readiness dibagi menjadi tiga dimensi:

```text
ROUTE FIT
+
PREPARATION READINESS
+
CURRENT CONDITIONS
```

---

# 39. READINESS STATES

### READY

- route fit acceptable;
- critical preparation confirmed;
- official access compatible;
- no critical unresolved issue.

### NEEDS PREPARATION

- preparation gap;
- incomplete pre-departure check;
- non-critical concern.

### NOT RECOMMENDED

Digunakan terbatas untuk kondisi product-level yang jelas, misalnya:

- official closure;
- explicit official restriction;
- hard incompatibility.

Bukan berdasarkan:

> “system merasa user secara medis tidak mampu.”

---

# 40. SAFETY SEMANTICS

Sistem tidak pernah mengatakan:

> “Anda aman.”

atau:

> “Gunung ini aman.”

Sistem mengatakan:

> “Status resmi saat ini OPEN.”

atau:

> “Terdapat peringatan kondisi yang perlu diperhatikan.”

---

# 41. FR-11 OFFICIAL STATUS

Status:

```text
OPEN
RESTRICTED
CLOSED
UNKNOWN
```

Official status harus memiliki:

- source;
- source URL;
- published_at;
- fetched_at;
- verified_at;
- effective_at;
- expires_at jika tersedia;
- scope;
- reason;
- notes.

---

# 42. STATUS SCOPE

Status tidak hanya milik mountain.

Status dapat berada pada:

```text
MOUNTAIN
TRAIL
SEGMENT
AREA
```

Contoh valid:

```text
Mountain = OPEN

Trail A = OPEN
Trail B = CLOSED
Trail C = RESTRICTED
```

Karena itu:

> OPEN pada level mountain tidak otomatis berarti semua trail OPEN.

---

# 43. OFFICIAL DATA PRIORITY

Authority hierarchy:

```text
OFFICIAL
    ↓
ADMIN VERIFIED
    ↓
COMMUNITY
    ↓
AI INTERPRETATION
```

AI tidak pernah menjadi source authority.

---

# 44. FR-12 WEATHER

BMKG saat ini menyediakan data prakiraan terbuka dalam JSON untuk 3 hari dengan interval 3 jam dan pembaruan dua kali sehari. Data tersebut berbasis wilayah administrasi tingkat IV dan penggunaan pada aplikasi wajib mencantumkan BMKG sebagai sumber.

Karena itu sistem tidak boleh mengatakan:

> “Cuaca puncak gunung X”

hanya berdasarkan forecast wilayah administratif.

System menampilkan:

> **Prakiraan area sekitar jalur**

dengan reference area yang didefinisikan pada trail.

---

# 45. WEATHER ARCHITECTURE

```text
Laravel Scheduler
       ↓
BMKG API
       ↓
Validate
       ↓
Normalize
       ↓
Store Weather Snapshot
       ↓
Cache
       ↓
Application UI
```

Browser tidak melakukan request BMKG secara langsung untuk setiap user.

---

# 46. WEATHER DATA

Minimum:

- local_datetime;
- weather description;
- temperature;
- humidity;
- wind speed;
- wind direction;
- cloud cover;
- visibility;
- analysis date;
- source;
- fetched_at.

---

# 47. WEATHER FAILURE RULE

Jika API gagal:

```text
Current Fetch Failed
```

system tidak mengarang data.

UI:

> “Data cuaca belum berhasil diperbarui. Data terakhir tersedia pada ...”

Jika tidak ada historical data:

> “Data cuaca tidak tersedia.”

Bukan:

> “Cuaca baik.”

---

# 48. FR-13 TRAIL CONDITION REPORT

Community feature utama.

User dapat melaporkan:

### Required

- route;
- hike date;
- condition.

### Optional

- segment;
- photo;
- note.

Condition tags:

```text
MUDDY
WET
SLIPPERY
DRY
CROWDED
QUIET
LOW_VISIBILITY
GOOD_MARKING
POOR_MARKING
WATER_AVAILABLE
WATER_SCARCE
```

---

# 49. COMMUNITY REPORT PRINCIPLE

Community report adalah:

> **supplementary field intelligence**

bukan:

> official status.

---

# 50. CONFLICT RULE

Jika:

```text
Official = OPEN
Community = “route blocked”
```

system menampilkan:

> **Status Resmi: OPEN**

dan:

> **Laporan Komunitas: terdapat hambatan pada segmen tertentu**

System tidak otomatis mengubah status menjadi CLOSED.

---

# 51. COMMUNITY FRESHNESS

Setiap report mempunyai:

- hike date;
- report created_at;
- moderation status.

User harus dapat melihat:

> “Dilaporkan 2 hari lalu berdasarkan pendakian tanggal ...”

---

# 52. FR-14 HIKING HISTORY

History menampilkan:

- trip;
- route;
- date;
- trip type;
- preparation completion;
- completion state;
- personal notes;
- submitted report.

---

# 53. FR-15 HIKE MODE

MVP hanya menyediakan:

- current position;
- route visualization;
- next checkpoint;
- distance to checkpoint;
- essential route information.

MVP **tidak menjanjikan**:

- continuous background tracking;
- advanced off-route detection;
- offline navigation;
- emergency command center.

---

# 54. PLAN MODE VS HIKE MODE

## PLAN MODE

Full information.

## HIKE MODE

Minimal information.

Tujuannya mengurangi cognitive load ketika user sedang bergerak.

---

# 55. MAP POLICY

MVP:

> online map.

Jangan menggunakan standard `tile.openstreetmap.org` sebagai basis offline download/prefetch. OSM secara eksplisit melarang bulk download dan offline use pada standard tile server; penggunaan juga harus mengikuti attribution dan caching requirements.

Future offline:

- self-hosted tiles;
- atau provider yang mengizinkan offline usage.

---

# 56. FR-16 ADMIN

Admin mengelola:

### Mountain

- create;
- edit;
- archive.

### Trail

- create;
- edit;
- archive.

### Checkpoint

- create;
- edit;
- reorder.

### Source

- register;
- verify.

### Official status

- manage metadata.

### Community

- moderate.

---

# 57. FR-17 MODERATION

Community report states:

```text
PENDING
APPROVED
REJECTED
FLAGGED
REMOVED
```

Moderator actions:

- approve;
- reject;
- flag;
- remove.

---

# 58. FR-18 DATA SOURCE MANAGEMENT

Source object:

```text
source_name
source_type
source_url
source_owner
retrieved_at
verified_at
freshness_policy
verification_status
```

---

# 59. DATA FRESHNESS MODEL

Setiap source memiliki policy berbeda.

Contoh:

```text
BMKG
→ refresh based on provider schedule

Official status
→ follow source update cadence

Community report
→ relative freshness based on hike date
```

Tidak boleh ada:

> universal rule “data > 24 jam = stale”.

---

# 60. DATA TRUST MODEL

Setiap data penting minimal memiliki:

```text
SOURCE
SOURCE TYPE
TIMESTAMP
VERIFICATION STATE
SCOPE
```

Contoh:

> Status Jalur B  
> Source: Pengelola  
> Verified: 18 Sep 2026  
> Scope: Jalur B  
> Status: OPEN

---

# 61. FR-19 AUDIT LOG

Critical actions:

- role change;
- official status change;
- trail change;
- mountain change;
- moderation;
- recommendation rule change;
- source change.

Fields:

```text
actor
action
entity
entity_id
timestamp
before
after
```

---

# 62. FR-20 ANALYTICS

MVP analytics:

```text
Profile Completion
Route Recommendation Viewed
Route Selected
Trip Created
Preparation Started
Pre-departure Check Completed
Trip Completed
Condition Report Submitted
```

Funnel:

```text
Profile
↓
Goal
↓
Recommendation
↓
Route Selection
↓
Preparation
↓
Check
↓
Hike
↓
Report
```

---

# 63. NORTH STAR METRIC

> **Percentage of planned trips that complete the core planning workflow from route selection through pre-departure check.**

Bukan:

> number of registered users.

---

# 64. DATABASE MODEL

## Identity

```text
users
profiles
user_preferences
user_experience
```

## Geography

```text
mountains
trails
trail_segments
checkpoints
restricted_areas
```

## Conditions

```text
official_statuses
weather_snapshots
trail_condition_reports
```

## Planning

```text
trip_plans
preparation_templates
preparation_items
trip_preparation_items
readiness_checks
```

## Activity

```text
hiking_sessions
hiking_history
```

## Recommendation

```text
recommendation_runs
recommendation_results
recommendation_rules
```

## Governance

```text
data_sources
moderation_actions
audit_logs
```

---

# 65. GEOSPATIAL MODEL

PostGIS digunakan untuk:

```text
Mountain → POINT
Trail → LINESTRING
Segment → LINESTRING
Checkpoint → POINT
Restricted Area → POLYGON
```

Supabase menyediakan PostGIS sebagai database extension untuk spatial data dan geo queries.

---

# 66. SPATIAL INDEX

Spatial fields harus memiliki spatial index.

Target query:

- nearby trail;
- nearby checkpoint;
- point-to-route context;
- restricted area;
- bounding area.

---

# 67. PHOTO STORAGE

Community photo:

```text
Supabase Storage
```

Policy:

- user ownership;
- MIME validation;
- file-size limit;
- private upload path;
- controlled read access;
- safe file naming;
- moderation.

Supabase Storage mendukung access control melalui RLS pada `storage.objects`; operasi storage sebaiknya melalui Storage API, bukan memodifikasi storage schema secara langsung.

---

# 68. BACKUP REQUIREMENT

Backup database tidak sama dengan backup object Storage. Supabase menyatakan database backups tidak mencakup object yang disimpan melalui Storage API.

Karena itu production plan harus mempunyai:

```text
Database Backup
+
Storage Backup/Replication Strategy
```

---

# 69. TECHNICAL ARCHITECTURE

```text
                    USER
                      │
                      ▼
             Responsive Web/PWA
                      │
                      ▼
            Laravel 13.32.0
                      │
        ┌─────────────┼─────────────┐
        │             │             │
        ▼             ▼             ▼
      Auth        Domain Logic    Admin
        │             │
        │             ├── Route Fit
        │             ├── Preparation
        │             ├── Readiness
        │             └── Condition
        │
        ▼
 PostgreSQL / Supabase
        │
   ┌────┴────┐
   ▼         ▼
PostGIS   Storage
   │
   └──── External Sources
            ├── BMKG
            └── Official Sources
```

---

# 70. LARAVEL ARCHITECTURE

Framework baseline:

> **Laravel Framework 13.32.0 + PHP 8.5**

Laravel 13 requires PHP 8.3+, sehingga PHP 8.5 kompatibel. Laravel 13 memiliki security support hingga Maret 2028.

---

# 71. DOMAIN SERVICE STRUCTURE

Recommendation logic tidak diletakkan seluruhnya di Controller.

Recommended:

```text
RouteFitService
PreparationService
ReadinessService
ConditionAggregatorService
WeatherService
OfficialStatusService
RecommendationExplanationService
```

Controller hanya menangani:

```text
Request
→ Service
→ Response
```

---

# 72. ROUTE FIT SERVICE

Input:

```text
HikerProfile
TripGoal
Trail
CurrentConditions
```

Output:

```text
RouteFitResult
```

dengan:

```text
eligibility
label
internal_score
matched_factors
warnings
explanation
engine_version
```

---

# 73. CONDITION AGGREGATOR

Condition aggregator menggabungkan:

```text
Official Status
+
Weather Snapshot
+
Community Reports
```

tetapi tidak mencampur authority.

Output:

```text
official_status
weather_context
community_context
freshness
warnings
```

---

# 74. READINESS SERVICE

Readiness membaca:

```text
RouteFitResult
PreparationState
OfficialStatus
CurrentConditions
```

Output:

```text
READY
NEEDS_PREPARATION
NOT_RECOMMENDED
```

---

# 75. SECURITY ARCHITECTURE

```text
Browser
 ↓
Authentication
 ↓
Authorization
 ↓
Validation
 ↓
Business Rules
 ↓
Database Authorization
 ↓
Storage Authorization
```

---

# 76. LARAVEL AUTHORIZATION

Gunakan:

- Policies;
- Gates;
- role checks.

Tidak boleh mengandalkan:

```text
request.user_id
request.role
request.is_admin
```

sebagai sumber kebenaran.

---

# 77. OBJECT LEVEL AUTHORIZATION

Contoh:

```text
/trips/123
```

Tidak otomatis berarti user boleh membaca trip 123.

Server harus memverifikasi:

```text
trip.owner_id === authenticated_user.id
```

atau policy yang setara.

---

# 78. SUPABASE RLS

Jika tabel exposed melalui Supabase Data API, RLS harus digunakan bersama privileges/grants yang sesuai. Supabase menjelaskan bahwa RLS membatasi row yang dapat diakses, tetapi grants tetap menentukan apakah role dapat mengakses tabel sama sekali.

Prinsip:

```text
Laravel Policy
+
PostgreSQL/Supabase RLS
```

sebagai defense-in-depth.

---

# 79. SERVICE ROLE KEY

Supabase service role/secret key:

> **server-side only.**

Tidak boleh berada di:

- JavaScript browser;
- source code;
- Git;
- frontend configuration.

Supabase secara eksplisit menyatakan service-role/secret key tidak aman untuk diekspos ke frontend karena dapat melewati RLS.

---

# 80. INPUT VALIDATION

Semua:

- form;
- query parameter;
- file upload;
- route creation;
- community report;
- admin update

harus divalidasi.

---

# 81. FILE UPLOAD SECURITY

Validation:

```text
allowed MIME
max size
extension
ownership
randomized storage path
```

Tambahan production:

- malware scanning;
- image processing;
- EXIF stripping jika diperlukan.

---

# 82. LOCATION PRIVACY

Location data:

> **private by default.**

Public report tidak boleh secara otomatis mempublikasikan:

- precise start/end location user;
- continuous GPS path;
- private hiking track.

User hanya membagikan informasi yang secara eksplisit dipilih.

---

# 83. DATA MINIMIZATION

System hanya mengumpulkan data yang dibutuhkan.

Jangan meminta:

> continuous GPS

ketika user hanya ingin melihat recommendation.

Location permission hanya diminta ketika diperlukan.

---

# 84. PRIVACY CONTROLS

User dapat:

- melihat profile;
- mengubah profile;
- menghapus account;
- mengontrol visibility;
- menghapus personal content jika tidak diperlukan untuk integrity.

---

# 85. AI POLICY

## MVP

AI **tidak menjadi decision-maker**.

Recommendation:

> deterministic/rule-based.

Explanation:

> rule-based template.

---

# 86. FUTURE AI

AI dapat digunakan untuk:

- natural-language explanation;
- summary of trail reports;
- trip recap;
- conversational search.

AI tidak boleh:

- menentukan status gunung;
- mendiagnosis kesehatan;
- mengubah official status;
- menjamin keselamatan;
- mengarang data.

---

# 87. ACCESSIBILITY

Target:

> **WCAG 2.2 AA**

WCAG 2.2 merupakan W3C Recommendation dan menambahkan criteria yang relevan untuk focus visibility, target size, accessible authentication, serta kebutuhan pengguna mobile dan pengguna dengan keterbatasan tertentu.

Minimum:

- semantic HTML;
- keyboard navigation;
- visible focus;
- accessible labels;
- contrast;
- touch targets;
- screen reader support;
- accessible form errors;
- status tidak hanya menggunakan warna.

---

# 88. MOBILE-FIRST UX

Primary:

> mobile.

Secondary:

> desktop.

Karena:

- perencanaan dapat dilakukan di desktop;
- penggunaan informasi lapangan lebih mungkin melalui mobile.

---

# 89. UX PRINCIPLE — PROGRESSIVE DISCLOSURE

Urutan informasi:

```text
Apa ini?
↓
Cocokkah untuk saya?
↓
Mengapa?
↓
Apa tantangannya?
↓
Apa yang harus dipersiapkan?
↓
Bagaimana kondisi sekarang?
```

---

# 90. UX PRINCIPLE — EXPLAINABILITY

User harus dapat memahami:

> “Kenapa sistem merekomendasikan jalur ini?”

Tanpa membuka dokumentasi teknis.

---

# 91. UX PRINCIPLE — NO FALSE PRECISION

Jangan:

> Safety 91.72%

Jangan:

> Probability of success 88%.

Jangan:

> Safe mountain.

Gunakan:

> Cocok.

> Perlu persiapan.

> Kurang cocok.

> Official status OPEN.

---

# 92. OFFICIAL VS COMMUNITY UI

Contoh:

```text
OFFICIAL STATUS
OPEN
Source: Pengelola Jalur
Updated: ...

COMMUNITY CONDITION
Muddy at Segment 3
Reported: 2 days ago
```

Keduanya harus visually distinct.

---

# 93. DATA FRESHNESS UI

States:

```text
CURRENT
AGING
STALE
UNKNOWN
```

Tetapi threshold ditentukan per source.

---

# 94. RELIABILITY REQUIREMENTS

## External Source Failure

Jangan mengarang data.

## Recommendation Failure

Fallback:

> browse/filter manually.

## Weather Failure

Display last known data with timestamp.

## Status Failure

Display:

> UNKNOWN

bukan:

> OPEN.

---

# 95. DEFAULT-SAFE INFORMATION PRINCIPLE

System lebih baik berkata:

> **UNKNOWN**

daripada membuat asumsi:

> OPEN.

System lebih baik berkata:

> **NEEDS PREPARATION**

daripada memberi false confidence.

---

# 96. PERFORMANCE TARGETS

MVP engineering targets:

- fast first content;
- optimized database queries;
- indexed geospatial query;
- cached external data;
- paginated report data;
- optimized images.

Target dapat digunakan:

```text
LCP ≤ 2.5 sec
Critical API p95 ≤ 1 sec
```

sebagai **engineering target**, bukan public SLA.

---

# 97. CACHING

Cache:

- public mountain metadata;
- trail metadata;
- weather snapshots;
- official status snapshots.

Private data tidak boleh dibagikan melalui shared cache.

---

# 98. JOBS / SCHEDULER

Background jobs menangani:

- weather refresh;
- official source fetch;
- stale data checks;
- image processing;
- analytics aggregation.

Laravel 13 memiliki dukungan queue dan queue routing yang dapat digunakan untuk memisahkan pekerjaan background dari request user.

---

# 99. OBSERVABILITY

Monitor:

### Application

- exception;
- latency;
- HTTP status.

### Jobs

- success;
- failure;
- retry;
- execution time.

### Integrations

- BMKG success/failure;
- official source status.

### Product

- recommendation success;
- report success.

---

# 100. TESTING STRATEGY

## Unit Tests

- route fit;
- readiness;
- freshness;
- preparation rules.

## Feature Tests

- auth;
- trip ownership;
- report ownership;
- admin actions.

## Integration Tests

- BMKG;
- weather cache;
- source integration;
- storage.

## E2E Tests

Core journey:

```text
Register
→ Profile
→ Goal
→ Recommendation
→ Select
→ Plan
→ Prepare
→ Check
```

## Security Tests

- IDOR;
- privilege escalation;
- mass assignment;
- file access;
- injection;
- rate limit.

---

# 101. ROUTE FIT TEST MATRIX

Minimum test:

| User | Route | Expected |
|---|---|---|
| Beginner | Easy | COCOK |
| Beginner | Moderate | PERLU PERSIAPAN |
| Beginner | Highly Technical | KURANG COCOK |
| Experienced | Moderate | COCOK |
| Any | Closed | EXCLUDED |
| Any | Restricted | Warning / restricted |
| Any | Multi-day vs 1-day goal | Exclude from default |

Actual thresholds harus ditetapkan dalam configuration/rule table.

---

# 102. READINESS TEST MATRIX

### Case A

```text
Route Fit = COCOK
Preparation = complete
Official = OPEN
```

Result:

> READY

### Case B

```text
Route Fit = COCOK
Preparation = incomplete
Official = OPEN
```

Result:

> NEEDS PREPARATION

### Case C

```text
Official = CLOSED
```

Result:

> NOT RECOMMENDED / route unavailable

### Case D

```text
Official = UNKNOWN
```

Result:

> current condition cannot be confidently verified.

---

# 103. COMMUNITY REPORT TEST MATRIX

### Case A

Official OPEN + community muddy:

> OPEN + condition warning.

### Case B

Official CLOSED + community says open:

> CLOSED remains authoritative.

### Case C

No community report:

> “No recent community report available.”

---

# 104. EDGE CASES

## User Profile incomplete

System:

> meminta data minimal.

## No route fits

System:

> menjelaskan kenapa.

## Multiple routes fit

System:

> menampilkan beberapa kandidat.

## External API unavailable

System:

> fallback.

## Data stale

System:

> timestamp + warning.

## User deletes account

System:

> personal data handling follows retention/deletion policy.

---

# 105. MVP SCOPE — MUST HAVE

## Core

1. Authentication
2. Profile
3. Experience
4. Hiking Goal
5. Mountain database
6. Trail database
7. Checkpoint
8. Route Fit Engine
9. Explainability
10. Trail detail
11. Trip planner
12. Preparation
13. Readiness
14. Official status
15. BMKG weather
16. Community condition report
17. History
18. Admin
19. Moderation
20. Audit
21. Security
22. Testing

---

# 106. MVP+ — SHOULD HAVE

1. Basic Hike Mode
2. Basic GPS
3. Route comparison
4. Photo reports
5. PWA installability
6. Elevation profile
7. Saved routes

---

# 107. FUTURE

## Navigation

- offline maps;
- off-route;
- route recording.

## Safety

- live location sharing;
- SOS;
- emergency contact workflow.

## Community

- groups;
- clubs;
- events;
- social feed.

## Commerce

- gear;
- guides;
- porters;
- booking.

## Intelligence

- adaptive recommendation;
- predictive condition;
- AI coaching.

---

# 108. MVP DATASET

Do not attempt the entire Indonesian mountain catalogue.

Recommended initial dataset:

```text
5–10 mountains
2–5 trails per mountain
3–10 checkpoints per trail
```

Purpose:

> test product logic, not quantity.

---

# 109. DATA CURATION PROCESS

```text
Source Discovery
↓
Data Entry
↓
Validation
↓
Spatial Validation
↓
Admin Review
↓
Publish
↓
Freshness Monitoring
```

---

# 110. INITIAL DATA QUALITY REQUIREMENT

Trail cannot be published as production-ready unless minimum:

- source;
- route geometry;
- basic characteristics;
- checkpoints;
- status metadata.

---

# 111. PRODUCT RESEARCH REQUIREMENT

Before full UI lock:

### Beginner

5–8 users

### Experienced

3–5 users

### Basecamp/Guide/Manager

2–3 participants if accessible.

---

# 112. RESEARCH QUESTIONS

Ask about actual behavior:

> Ceritakan pendakian terakhir Anda.

> Bagaimana memilih gunung?

> Sumber informasi apa yang digunakan?

> Bagaimana menentukan kesulitan?

> Bagaimana mengetahui kondisi terbaru?

> Apa informasi yang pernah ternyata salah?

> Bagaimana mempersiapkan pendakian?

Jangan memimpin user:

> “Apakah Anda mau menggunakan aplikasi seperti ini?”

---

# 113. UX VALIDATION TASKS

User harus mampu:

### Task 1

Menemukan route yang relevan.

### Task 2

Menjelaskan mengapa route cocok.

### Task 3

Menemukan preparation gap.

### Task 4

Menemukan official status.

### Task 5

Menemukan weather.

### Task 6

Menemukan recent condition report.

---

# 114. ACCEPTANCE CRITERIA — CORE PRODUCT

## Recommendation

**Given** user telah menyelesaikan profile.

**When** user membuat hiking goal.

**Then** Route Fit Engine mengevaluasi eligible routes.

**And** closed route tidak menjadi active recommendation.

**And** setiap result mempunyai explanation.

---

# 115. ACCEPTANCE CRITERIA — PREPARATION

**Given** user telah memilih trail.

**When** preparation plan dibuat.

**Then** system menghasilkan preparation requirements berdasarkan trail.

**And** status setiap requirement dapat dilacak.

---

# 116. ACCEPTANCE CRITERIA — WEATHER

**Given** trail mempunyai weather reference area.

**When** weather page dibuka.

**Then** system menampilkan forecast area yang relevan.

**And** BMKG disebut sebagai source.

**And** timestamp tersedia.

BMKG mewajibkan atribusi sumber ketika data digunakan dalam aplikasi.

---

# 117. ACCEPTANCE CRITERIA — OFFICIAL STATUS

**Given** trail memiliki status OFFICIAL CLOSED.

**When** user membuka recommendation.

**Then** trail tidak menjadi active recommendation.

---

# 118. ACCEPTANCE CRITERIA — COMMUNITY

**Given** user selesai mendaki.

**When** user membuat report.

**Then** report masuk moderation.

**And** report belum dianggap official.

---

# 119. ACCEPTANCE CRITERIA — AUTHORIZATION

**Given** user A memiliki trip A.

**When** user B membuka trip A.

**Then** request ditolak.

---

# 120. ACCEPTANCE CRITERIA — LOCATION

**Given** user belum menggunakan Hike Mode.

**When** browsing recommendation.

**Then** system tidak meminta continuous GPS permission.

---

# 121. ASSUMPTIONS

1. User mempunyai smartphone/browser modern.
2. GPS hanya digunakan ketika dibutuhkan.
3. Data awal trail tersedia dan dapat diverifikasi.
4. External source tidak selalu menyediakan API.
5. Official status dapat berbeda antara mountain dan trail.
6. Community report bersifat supplementary.
7. Dataset MVP bersifat curated.
8. PWA tidak berarti full offline navigation.

---

# 122. PRODUCT RISKS

## R1 — Wrong Data

Impact: HIGH

Mitigation:

- source;
- timestamp;
- verification;
- freshness.

## R2 — False Safety Confidence

Impact: VERY HIGH

Mitigation:

- no safety score;
- no safety guarantee;
- official status prominence.

## R3 — Community Misinformation

Impact: HIGH

Mitigation:

- moderation;
- date;
- source label.

## R4 — Scope Explosion

Impact: HIGH

Mitigation:

- strict MVP.

## R5 — External API failure

Impact: HIGH

Mitigation:

- cache;
- fallback;
- freshness.

## R6 — GPS Privacy

Impact: HIGH

Mitigation:

- private-by-default;
- data minimization.

---

# 123. PRODUCTION READINESS

## Academic MVP

Acceptable once:

- core flow works;
- tests pass;
- security controls exist;
- data is curated;
- source tracking exists.

## Closed Beta

Additional:

- user research;
- monitoring;
- real user feedback;
- incident handling.

## Public Production

Requires more:

- formal data-source arrangements;
- legal/privacy review;
- operational monitoring;
- backup and restore;
- storage backup;
- field validation;
- safety review;
- terms/privacy policy;
- mature moderation;
- reliable mapping infrastructure.

---

# 124. SUPABASE PRODUCTION CONSIDERATIONS

Supabase production guidance includes reviewing RLS, expected load, availability, and backup/recovery strategy. Free-plan projects may be paused after inactivity, and downloadable database backups are limited compared with paid tiers.

Untuk academic prototype hal tersebut dapat diterima, tetapi:

> deployment infrastructure harus dinilai terpisah dari product architecture.

---

# 125. VERSION CONTROL

Repository:

```text
main
develop
feature/*
fix/*
```

Pull request wajib melalui review minimal satu anggota lain.

Tidak boleh semua orang push perubahan besar langsung ke main.

---

# 126. CI/CD

```text
Push
↓
Lint
↓
Static Analysis
↓
Unit Test
↓
Feature Test
↓
Security Check
↓
Build
↓
Deploy
↓
Smoke Test
```

---

# 127. TEAM OF FIVE

## Member 1 — PM/Product

- requirements;
- user research;
- backlog;
- acceptance criteria;
- documentation.

## Member 2 — Backend

- Laravel;
- services;
- auth;
- business logic.

## Member 3 — Database/GIS

- Supabase;
- PostgreSQL;
- PostGIS;
- data ingestion.

## Member 4 — Frontend/UX

- UI;
- responsive;
- accessibility;
- map interaction.

## Member 5 — QA/Security/DevOps

- testing;
- CI/CD;
- security;
- deployment;
- monitoring.

---

# 128. DEVELOPMENT PHASES

## Phase 1 — Discovery

Output:

- user research;
- personas;
- JTBD;
- validated assumptions.

## Phase 2 — UX

Output:

- information architecture;
- user flow;
- wireframe;
- prototype;
- usability test.

## Phase 3 — Foundation

Output:

- Laravel;
- Supabase;
- database;
- authentication;
- authorization.

## Phase 4 — Core Recommendation

Output:

- profile;
- trail data;
- Route Fit Engine;
- explanation.

## Phase 5 — Planning

Output:

- trip;
- preparation;
- readiness.

## Phase 6 — Condition

Output:

- BMKG;
- official status;
- community report.

## Phase 7 — Basic Hike Mode

Output:

- map;
- GPS;
- checkpoint.

## Phase 8 — Hardening

Output:

- security;
- accessibility;
- performance;
- testing.

---

# 129. DEFINITION OF DONE

Feature dianggap Done apabila:

```text
Requirement implemented
+
Validation implemented
+
Authorization tested
+
Error state implemented
+
Unit/Feature test
+
Mobile tested
+
Accessibility checked
+
Code reviewed
+
Documentation updated
```

---

# 130. PRODUCT GOVERNANCE

Setiap perubahan PRD harus mengidentifikasi:

```text
Requirement changed
Reason
Impact
Affected modules
Security impact
Data impact
Scope impact
```

Tidak boleh menambah fitur hanya karena:

> “fitur ini menarik.”

---

# 131. FEATURE GATE

Fitur baru hanya boleh masuk MVP jika menjawab minimal satu:

1. meningkatkan route selection;
2. meningkatkan preparation;
3. meningkatkan current-condition awareness;
4. meningkatkan field usability;
5. meningkatkan data trust.

Jika tidak:

> Future scope.

---

# 132. FINAL LOCKED BUSINESS RULES

## BR-01

Mountain ≠ Trail.

## BR-02

MDPL bukan primary difficulty determinant.

## BR-03

Elevation gain adalah salah satu faktor penting route difficulty.

## BR-04

Official CLOSED tidak menjadi active recommendation.

## BR-05

Official OPEN tidak berarti “aman”.

## BR-06

Community report tidak mengoverride official status.

## BR-07

UNKNOWN lebih baik daripada asumsi OPEN.

## BR-08

Recommendation harus explainable.

## BR-09

Numeric internal score tidak menjadi public safety score.

## BR-10

Unchecked preparation ≠ equipment tidak dimiliki.

## BR-11

User history tidak otomatis menaikkan experience level.

## BR-12

AI tidak menentukan safety atau official status.

## BR-13

Precise GPS private by default.

## BR-14

External data selalu memiliki source + timestamp.

## BR-15

Weather location direpresentasikan sebagai reference area, bukan otomatis kondisi puncak.

---

# 133. FINAL MVP USER JOURNEY

```text
1. USER REGISTER
        ↓
2. BUILD PROFILE
        ↓
3. SET HIKING GOAL
        ↓
4. ROUTE FIT ENGINE
        ↓
5. VIEW RECOMMENDATION
        ↓
6. UNDERSTAND WHY
        ↓
7. SELECT TRAIL
        ↓
8. CREATE TRIP
        ↓
9. PERSONALIZED PREPARATION
        ↓
10. READINESS CHECK
        ↓
11. OFFICIAL STATUS
        ↓
12. WEATHER
        ↓
13. RECENT TRAIL CONDITION
        ↓
14. PRE-DEPARTURE CHECK
        ↓
15. HIKE MODE
        ↓
16. COMPLETE TRIP
        ↓
17. TRAIL CONDITION REPORT
        ↓
18. HISTORY
```

---

# 134. FINAL CORE PRODUCT PILLARS

## PILLAR 1 — FIT

> Jalur mana yang cocok dengan saya?

## PILLAR 2 — PREPARE

> Apa yang perlu saya siapkan?

## PILLAR 3 — CHECK

> Apa kondisi yang perlu saya periksa sebelum berangkat?

## PILLAR 4 — HIKE

> Informasi minimum apa yang saya butuhkan di jalur?

## PILLAR 5 — REPORT

> Apa yang bisa saya kontribusikan untuk pendaki berikutnya?

---

# 135. FINAL TECHNICAL BASELINE

```text
PHP 8.5
Laravel Framework 13.32.0
PostgreSQL
Supabase
PostGIS
Supabase Storage
MapLibre-compatible maps
BMKG API
Official mountain/status sources
Responsive Web
PWA-ready
WCAG 2.2 AA target
```

Laravel 13.32.0 tercatat pada Packagist pada September 2026 dan membutuhkan PHP `^8.3`, sehingga baseline PHP 8.5 sesuai.

---

# 136. FINAL PRODUCT SCOPE

### MVP

```text
PLAN
+
PREPARE
+
CHECK
+
BASIC HIKE
+
REPORT
```

### Future

```text
ADVANCED NAVIGATION
+
OFFLINE MAP
+
LIVE SHARING
+
SOS
+
SOCIAL
+
MARKETPLACE
+
ADVANCED AI
```

---

# 137. FINAL PRODUCT DESCRIPTION

> **Personalized Mountain Planning & Readiness Platform adalah aplikasi web yang membantu pendaki memilih jalur gunung berdasarkan kecocokan antara profil pendaki, karakteristik jalur, dan rencana perjalanan; memahami alasan rekomendasi dan tantangan jalur; mempersiapkan kebutuhan pendakian secara personal; memeriksa status resmi, cuaca, dan kondisi terbaru; menggunakan informasi penting saat berada di jalur; serta berkontribusi melalui laporan kondisi pendakian.**

---

# 138. FINAL PROBLEM STATEMENT

> **Pendaki menghadapi informasi pendakian yang tersebar dan tidak selalu kontekstual terhadap kemampuan, pengalaman, rencana perjalanan, serta kondisi terbaru. Akibatnya, proses memilih jalur dan mempersiapkan pendakian membutuhkan penggabungan informasi dari berbagai sumber dan keputusan manual yang dapat membingungkan, terutama bagi pendaki dengan pengalaman terbatas.**

---

# 139. FINAL SOLUTION STATEMENT

> **Sistem menyediakan decision-support flow yang menggabungkan profil pendaki, karakteristik trail, trip context, official status, weather, dan community condition report untuk menghasilkan rekomendasi jalur yang explainable serta preparation plan yang relevan.**

---

# 140. FINAL DIFFERENTIATOR

> **Explainable Mountain–Hiker Fit Engine**

Bukan:

> “Kami punya database gunung.”

Tetapi:

> **“Kami membantu pengguna memahami mengapa suatu jalur relevan untuk profil dan rencana pendakiannya, apa yang perlu dipersiapkan, dan informasi apa yang masih perlu diperiksa.”**

---

# 141. FINAL PRODUCT LOOP

```text
PROFILE
   ↓
ROUTE FIT
   ↓
PLAN
   ↓
PREPARE
   ↓
CHECK
   ↓
HIKE
   ↓
REPORT
   ↓
IMPROVE
```

---

# 142. FINAL DECISION LOCK

## LOCKED

**Product Concept:**  
Personalized Mountain Planning & Readiness Platform

**Problem:**  
Fragmented information makes personalized hiking planning difficult.

**Target:**  
Beginner → Experienced hikers.

**Core Engine:**  
Explainable Mountain–Hiker Fit Engine.

**Core Data Model:**  
Mountain → Trail → Segment → Checkpoint.

**Primary Workflow:**  
Profile → Route Fit → Plan → Prepare → Check → Hike → Report.

**Data Authority:**  
Official → Admin Verified → Community → AI Interpretation.

**Recommendation:**  
Rule-based + explainable.

**Readiness:**  
Decision support, not medical/legal clearance.

**Map:**  
Online MVP; offline future.

**AI:**  
Not decision-maker; future enhancement.

**Architecture:**  
Laravel + PostgreSQL/Supabase + PostGIS + Storage.

**Baseline:**  
PHP 8.5 + Laravel Framework 13.32.0.

**MVP Dataset:**  
Curated 5–10 mountains.

**Team:**  
5 members.

---

# 143. FINAL PRD STATUS

### Product concept

**FIXED**

### Problem

**FIXED**

### Target user

**FIXED**

### Core workflow

**FIXED**

### Main feature scope

**FIXED**

### Data model

**FIXED**

### Technical baseline

**FIXED**

### Security model

**FIXED**

### Reliability model

**FIXED**

### MVP boundary

**FIXED**

### Remaining work

**Implementation design, not ideation.**

---

# 144. FINAL PRINCIPLE

Semua keputusan development setelah dokumen ini harus mempertahankan satu prinsip:

> **Produk tidak berusaha menjadi aplikasi pendakian dengan fitur sebanyak mungkin. Produk berusaha menjadi sistem yang membantu pendaki mengambil keputusan perjalanan yang lebih terstruktur, dapat dijelaskan, dan berbasis sumber informasi yang jelas.**

Dan satu filter terakhir untuk setiap fitur:

> **“Apakah fitur ini meningkatkan FIT, PREPARE, CHECK, HIKE, atau REPORT?”**

Jika tidak, fitur tersebut berada di luar MVP.