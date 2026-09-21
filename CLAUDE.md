# CLAUDE.md
# Product + UX/HCI + Engineering + Audit Operating Contract
# Personalized Mountain Planning & Readiness Platform

> STATUS: MASTER AGENT CONTRACT
> PURPOSE: Prevent product, UX, architecture, data, security, testing, and operational misscommunication across Claude Code sessions.
> LANGUAGE: Bahasa Indonesia for explanations/recaps unless code/tooling requires otherwise.
> PRODUCT PRIORITY: HCI/IMK + UI/UX first, then product logic, architecture/scalability, security/privacy, reliability, observability, data consistency, and marketing/growth.

---

# 0. NON-NEGOTIABLE OPERATING RULES

Before changing application code, schema, UX, configuration, tests, data, deployment, or documentation that affects product behavior:

1. Read this `CLAUDE.md` completely.
2. Read `HANDOVER.md` completely if present.
3. Read `AGENTS.md` completely if present.
4. Read `PRD.md` completely before product/domain/UX changes.
5. Read `IMPLEMENTATION-PRD.md` if present.
6. Read relevant files under `docs/`, `docs/superpowers/`, `.superpowers/`, task/backlog files, and design/spec files.
7. Inspect the actual repository/runtime state. Documentation is context; actual runtime/repository state is implementation truth.
8. Inventory ALL installed skills/capabilities and evaluate them before starting the task.
9. Apply antislop continuously during work and perform a final antislop pass before declaring completion.
10. Research deeply when uncertainty materially affects a decision.
11. Test and cross-check meaningful changes.
12. Never silently alter product direction, business rules, privacy behavior, scope, architecture, or data authority.
13. Never push/merge/release to remote Git without explicit owner approval.
14. Never access Supabase credentials/production data without explicit owner approval.
15. Never declare success based solely on code existence, a screenshot, or a green local test suite.
16. If documentation conflicts with actual code/database/runtime, report the discrepancy before altering architecture or data.

---

# 1. PRODUCT OWNER INTENT

The owner explicitly wants **ATM — Amati, Tiru, Modifikasi** from proven products such as AllTrails, Traveloka, and Strava.

The target is NOT visual imitation.
The target is equivalent or better **HCI/IMK, UI, UX, and decision-support impact**.

The owner explicitly prioritizes:

1. IMK/HCI;
2. UI/UX;
3. product logic and information architecture;
4. architecture and scalability;
5. security and privacy/compliance;
6. reliability and failure management;
7. operational readiness and observability;
8. data management and consistency;
9. marketing/SEO/growth.

The agent is expected to combine roles:
- lead engineer;
- UI/UX designer;
- product designer;
- software engineer;
- QA/audit engineer;
- security/privacy reviewer;
- technical product manager.

The goal is enterprise-minded quality, not unnecessary enterprise complexity.

---

# 2. ATM FRAMEWORK

For every reference pattern, use all three steps.

## 2.1 AMATI
Study:
- problem solved;
- context of use;
- user mental model;
- interaction pattern;
- information hierarchy;
- decision mechanism;
- feedback loop;
- trust mechanism;
- motivation/retention mechanism;
- operational constraints;
- measurable UX effect.

## 2.2 TIRU
Take only the underlying mechanism/principle.

Do NOT copy:
- branding;
- colors/visual identity;
- pixel-level page layout;
- proprietary text;
- source code;
- assumptions that only work in another domain.

## 2.3 MODIFIKASI
Adapt to:
- Indonesian mountain hiking;
- local users;
- Mountain != Trail domain model;
- via/basecamp concepts;
- official route status;
- weather data reality;
- outdoor/mobile context;
- weak connectivity;
- privacy/location sensitivity;
- community condition reports;
- preparation/readiness;
- actual product problem.

Never add a competitor feature merely because the competitor has it.

---

# 3. REFERENCE PRODUCT DNA

## 3.1 AllTrails — CONFIDENCE THROUGH CONTEXT

Study mechanisms such as:
- trail discovery;
- filtering;
- route characteristics;
- conditions;
- reviews/community intelligence;
- saved routes/lists;
- route/activity context.

Our effect target:
> "Saya memahami ongkos dan konteks jalur sebelum berangkat."

Our adaptation:
- distance;
- elevation gain/loss;
- duration;
- terrain;
- technical demand;
- navigation complexity;
- segment/checkpoint context;
- official status;
- weather context;
- recent trail conditions;
- preparation gaps.

## 3.2 Traveloka — DECISION CONVENIENCE

Study mechanisms such as:
- search;
- filters;
- saving/collections;
- comparison;
- choice narrowing;
- delaying commitment without losing candidates.

Our effect target:
> "Saya dapat mempersempit pilihan, menyimpan kandidat, membandingkan, lalu memutuskan."

Our adaptation:
- `Pertimbangkan`;
- save/shortlist;
- compare max 5;
- read-only comparison sharing;
- create trip from chosen trail.

## 3.3 Strava — CONTINUITY + PERSONAL PROGRESS

Study mechanisms such as:
- activity lifecycle;
- history;
- personal comparison;
- route/activity discovery;
- community;
- reasons to return.

Our effect target:
> "Pengalaman sebelumnya berharga dan saya dapat melihat perkembangan diri saya."

Our adaptation:
- hiking history;
- elevation-gain progression;
- distance/duration progression;
- route-complexity exposure;
- self-comparison.

Do NOT make contribution quantity a core leaderboard metric.

---

# 4. PRODUCT THESIS

The product is NOT:
- mountain CRUD;
- generic tourism directory;
- GPS-only app;
- review-only app;
- social-media clone;
- marketplace;
- booking platform;
- medical system;
- SAR command center;
- "Strava for mountains";
- "Traveloka for mountains";
- "AllTrails clone".

The product IS:
> A personalized mountain planning and readiness platform that helps hikers discover, understand, compare, prepare for, check, complete, and learn from hiking routes.

Primary user job:
> "Bantu saya memilih jalur yang sesuai dengan profil dan rencana saya, memahami alasannya, mengetahui apa yang perlu disiapkan, dan memahami kondisi terkini sebelum berangkat."

---

# 5. CORE EXPERIENCE LOOP

`PROFILE → ROUTE FIT → PLAN → PREPARE → CHECK → HIKE → REPORT → IMPROVE`

All major product features must relate clearly to this loop.

---

# 6. FIVE USER-FACING SURFACES

## JELAJAH
Question:
> "Apa yang tersedia dan mana yang relevan?"

Includes:
- search;
- filters;
- discovery;
- recommendation;
- Route Fit;
- trail detail;
- basic route visualization.

## PERTIMBANGKAN
Question:
> "Pilihan mana yang benar-benar ingin saya pertimbangkan?"

Includes:
- save;
- shortlist;
- compare max 5;
- remove;
- optional notes if implemented;
- read-only shared comparison.

## PERJALANAN
Question:
> "Bagaimana saya merencanakan dan mempersiapkannya?"

Includes:
- trip;
- date/time;
- trip type;
- preparation;
- readiness;
- pre-departure check.

## KABAR
Question:
> "Apa kondisi terkini dan mana yang dapat dipercaya?"

Includes:
- official status;
- weather context;
- recent conditions;
- community reports;
- freshness/source.

Kabar is NOT a generic social feed.

## PROGRES
Question:
> "Bagaimana perkembangan saya?"

Includes:
- hiking history;
- personal progression;
- completed routes;
- elevation gain progression;
- route complexity progression.

---

# 7. HCI / IMK OPERATING MODEL

Use human-centred design throughout the lifecycle.

Workflow:
`RESEARCH → CONTEXT OF USE → IA → INTERACTION DESIGN → PROTOTYPE → USABILITY TEST → ITERATE → IMPLEMENT → MEASURE`

Do not equate:
- visual polish with usability;
- feature count with product quality;
- responsive CSS with good mobile UX;
- green tests with production correctness.

Core principles:
- progressive disclosure;
- explainability;
- source transparency;
- no false precision;
- context-aware interaction;
- low cognitive load in field mode;
- mobile-first;
- accessibility by design;
- predictable states;
- meaningful empty/error/fallback states.

---

# 8. CONTEXT OF USE

## PLAN MODE
Typical context:
- home/campus;
- stable connection;
- more attention;
- planning/comparison.

May show:
- filters;
- comparison;
- detailed route information;
- charts;
- preparation;
- history.

## HIKE MODE
Typical context:
- mobile;
- outdoor glare;
- one-hand interaction;
- fatigue;
- limited attention;
- weak connectivity;
- battery constraints.

Show only:
- current position if permission;
- route;
- next checkpoint;
- distance;
- critical warnings;
- essential information.

Hike Mode must not become a miniature dashboard.

---

# 9. UX OUTCOMES TO MEASURE

These are hypotheses/targets until validated.

1. Discoverability — user can find relevant routes.
2. Comprehension — user understands route characteristics.
3. Comparability — user sees trade-offs.
4. Confidence — user understands why and from where.
5. Preparedness — user knows what remains.
6. Continuity — user understands progression and has a reason to return.

Suggested validation targets:
- decision effectiveness ≥90% test participants selecting a relevant route without moderator intervention;
- decision comprehension ≥80% correctly stating two recommendation reasons;
- comparison efficiency ≥80% comparing three routes within two minutes in defined test;
- trust comprehension ≥90% distinguishing official vs community information;
- SUS target ≥80 for the defined study.

Do not fabricate these metrics.

---

# 10. ACCESSIBILITY

Target: WCAG 2.2 AA.

Minimum:
- semantic HTML;
- keyboard access;
- visible focus;
- accessible labels;
- accessible errors;
- adequate contrast;
- status not conveyed by color alone;
- mobile reflow/zoom;
- screen-reader walkthrough;
- touch-friendly controls.

For primary outdoor/mobile controls, prefer approximately 44x44 CSS px for practical usability, while respecting WCAG requirements.

Test actual screens; do not declare accessibility merely because CSS classes exist.

---

# 11. DOMAIN MODEL

Critical:
`MOUNTAIN != TRAIL`

Model:
`Mountain → Trail → Segment → Checkpoint`

Primary decision unit is usually TRAIL.

### Mountain
- identity;
- region;
- elevation;
- location;
- management area;
- description;
- source.

### Trail
- mountain_id;
- name/via;
- geometry;
- distance;
- elevation gain/loss;
- duration;
- terrain;
- physical/technical demand;
- navigation complexity;
- camping/water characteristics;
- weather reference area;
- source/verification.

### Segment
- trail_id;
- sequence;
- geometry;
- terrain;
- technical level;
- navigation complexity;
- distance/elevation;
- notes.

### Checkpoint
- trail/segment;
- sequence;
- coordinate;
- elevation;
- distance from start;
- estimated time;
- notes.

---

# 12. DIFFICULTY MODEL

MDPL is descriptive only.

Never use:
`higher MDPL = automatically harder`.

Recommendation should consider:
- physical demand;
- elevation gain;
- distance;
- duration;
- terrain;
- technical demand;
- navigation complexity;
- trip context.

Overall difficulty may be a derived label, but underlying characteristics must remain available.

---

# 13. ROUTE FIT ENGINE

Core inputs:
- Hiker Profile;
- Hiking Goal;
- Trail characteristics;
- official status;
- relevant current context.

Three layers:

### Eligibility / hard constraints
Examples:
- CLOSED → exclude active recommendation;
- explicit restriction → exclude/restrict according to rule;
- fundamental duration incompatibility → exclude from default recommendation.

### Compatibility
Possible factors:
- experience;
- duration;
- terrain;
- technical demand;
- elevation gain;
- navigation.

### Preference
Possible factors:
- tektok/camping;
- region;
- duration preference;
- challenge preference.

Do not hard-code business weights into UI code.

---

# 14. ROUTE FIT OUTPUT

Public labels ONLY:
- `COCOK`;
- `PERLU PERSIAPAN`;
- `KURANG COCOK`.

Internal numeric scoring may exist for ranking, but MUST NEVER be exposed to UI.

Do not expose:
- Fit %;
- safety %;
- success probability;
- accident probability.

---

# 15. ROUTE FIT EXPLAINABILITY

Every recommendation must answer:

## Why it fits
- experience alignment;
- duration alignment;
- terrain alignment;
- preference alignment where applicable.

## What to watch
- elevation gain;
- technical segments;
- navigation;
- environmental/current conditions.

## Preparation gaps
- NOT_CONFIRMED preparation;
- route briefing not completed;
- weather not checked;
- status not checked.

The explanation must be understandable without technical docs.

---

# 16. RECOMMENDATION REPRODUCIBILITY

Persist enough context to reproduce a recommendation:
- engine_version;
- rule/config version;
- profile snapshot;
- goal snapshot;
- trail snapshot;
- relevant condition snapshot;
- evaluated rules;
- matched factors;
- warnings;
- generated_at.

Identical snapshot + identical configuration should yield consistent classification.

---

# 17. PUBLICATION GATE — USE BOTH LAYERS

## 17.1 Publication Gate

A trail CANNOT be PUBLISHED if critical data is missing.

Minimum critical publication data:
- geometry;
- checkpoint;
- source;
- distance;
- elevation gain;
- duration;
- terrain;
- technical characteristics;
- navigation characteristics.

Incomplete trail:
- `DRAFT / INCOMPLETE`;
- not eligible for recommendation;
- admin sees missing-data checklist.

## 17.2 Partial Data After Publication

A published trail MAY still have non-critical UNKNOWN fields.

Example:
- water availability = UNKNOWN;
- camping detail = UNKNOWN;
- recent condition = no report.

Rules:
- `UNKNOWN != LOW`;
- never invent missing data;
- show truthful state;
- Route Fit knows whether field is required/warning-only/optional.

This is LOCKED as the combined policy equivalent to the prior "option 3" decision.

---

# 18. OFFICIAL VS COMMUNITY

Authority hierarchy:
`OFFICIAL → ADMIN VERIFIED → COMMUNITY → AI INTERPRETATION`

Community never overrides official status.
AI never overrides official status.

If official = OPEN and community = blocked:
- official stays OPEN;
- community warning shown separately;
- verification advice may be shown.

---

# 19. OFFICIAL STATUS

Valid:
- OPEN;
- RESTRICTED;
- CLOSED;
- UNKNOWN.

Scope:
- mountain;
- trail;
- segment;
- area.

Mountain OPEN does NOT imply every trail OPEN.

Track when available:
- source;
- source URL;
- scope;
- published_at;
- effective_at;
- expires_at;
- retrieved_at;
- verified_at;
- restriction details;
- notes.

`UNKNOWN` must never silently become `OPEN`.
`OPEN` does not mean `SAFE`.

---

# 20. WEATHER

Preferred:
`Scheduler/Job → BMKG → Validate → Normalize → Store Snapshot → Cache → UI`

Do not call external weather APIs directly on every browser page load without a documented reason.

Weather wording must match source granularity.
If forecast is area-level:
> "Prakiraan area sekitar jalur"

Do not claim summit weather unless source actually supports it.

Always show:
- source;
- forecast time;
- retrieved/update time;
- reference area.

Failure:
- last known snapshot + timestamp if available;
- else WEATHER_UNAVAILABLE;
- never fabricate/default to good weather.

---

# 21. COMMUNITY TRAIL CONDITION INTELLIGENCE

Community is trail intelligence, not a generic social feed.

Report fields:
- trail;
- hike_date;
- optional segment;
- condition tags;
- note;
- optional photo.

Tags may include:
- MUDDY;
- WET;
- SLIPPERY;
- DRY;
- CROWDED;
- QUIET;
- LOW_VISIBILITY;
- GOOD_MARKING;
- POOR_MARKING;
- WATER_AVAILABLE;
- WATER_SCARCE.

Every report should expose:
- hike date;
- created date;
- moderation state;
- verification/approval state.

---

# 22. COMMUNITY MODERATION

States:
- PENDING;
- APPROVED;
- REJECTED;
- FLAGGED;
- REMOVED.

Community submissions never automatically become official status.
Moderation actions are auditable.

---

# 23. ACCOUNT DELETION / PRIVACY

Approved/published community reports MAY be retained as anonymized community knowledge when documented privacy/retention policy permits it.

On account deletion:
- identity removed/anonymized;
- published approved report may remain with neutral author label;
- private notes deleted;
- precise private GPS deleted;
- personal photos deleted by default unless documented retention/license basis exists;
- drafts deleted;
- pending/rejected content follows documented policy;
- minimal governance/audit data retained only where necessary.

Never publicly expose after deletion:
- email;
- full name;
- username;
- profile photo;
- precise private GPS.

Treat this area as subject to Indonesian PDP obligations and verify current legal guidance before public production.

---

# 24. PREPARATION

Preparation is route-specific, not generic.

Categories:
- equipment;
- route knowledge;
- logistics;
- weather check;
- official status check;
- general physical preparation.

Item states:
- CONFIRMED;
- NOT_CONFIRMED;
- NOT_APPLICABLE.

`NOT_CONFIRMED != user does not own item`.

No medical diagnosis/clearance.

---

# 25. READINESS

Readiness is decision support, not medical/legal clearance.

Inputs:
- Route Fit;
- preparation;
- official status;
- current conditions.

Outputs:
- READY;
- NEEDS_PREPARATION;
- NOT_RECOMMENDED.

Use NOT_RECOMMENDED conservatively for:
- explicit official closure/restriction;
- hard product-level incompatibility.

Never say:
- user is medically fit;
- user is safe;
- mountain is safe.

---

# 26. COMPARISON / PERTIMBANGKAN

Maximum compare set: 5 trails.

Comparison should show trade-offs, not a winner.

Useful fields:
- Route Fit label;
- distance;
- elevation gain;
- duration;
- terrain;
- technical demand;
- navigation;
- trip type support;
- official status;
- recent condition;
- preparation gap.

Do not expose ranking/winner language.

Shared comparison:
- read-only;
- never writes to another user's private shortlist.

---

# 27. PROGRESS

Progress is self-comparison.

Possible dimensions:
- elevation gain;
- distance;
- duration;
- route complexity;
- completed routes.

Do not:
- automatically certify expertise from one trip;
- create contribution leaderboards as core;
- reward quantity over data quality.

---

# 28. TRIP STATE MACHINE

Valid states:
- DRAFT;
- PLANNED;
- READY_FOR_DEPARTURE;
- IN_PROGRESS;
- COMPLETED;
- CANCELLED.

Invalid transitions must be rejected.

---

# 29. MAP / HIKE MODE SCOPE

MVP:
- basic route visualization where geometry exists.

MVP+:
- current GPS;
- next checkpoint;
- distance to checkpoint;
- field-oriented Hike Mode;
- elevation profile.

Future:
- full offline maps;
- background tracking;
- off-route detection;
- live sharing;
- advanced navigation.

Map-first is not the default UX when data geometry is incomplete.
Preferred hierarchy:
`Route Summary → Fit → Why → Challenges → Preparation → Map`

---

# 30. PHOTO POLICY

Do NOT use unverified community photos as the default face/hero of a trail card.

Reasons:
- identity/privacy concerns;
- metadata;
- licensing/provenance;
- source authority.

Photos are supporting evidence after appropriate policy/moderation.

---

# 31. MVP

MVP proves the core decision/planning loop.

Required:
- authentication;
- hiker profile;
- hiking goal;
- mountain/trail/segment/checkpoint;
- basic route visualization;
- Route Fit;
- recommendation explanation;
- Pertimbangkan save/compare;
- trip planner;
- preparation;
- readiness;
- official status;
- weather;
- recent trail conditions;
- community reports;
- moderation;
- history;
- personal progress;
- admin;
- audit;
- security;
- automated testing.

MVP does NOT require:
- live GPS;
- background tracking;
- offline navigation;
- SOS;
- advanced AI.

---

# 32. MVP+

MVP+:
- basic Hike Mode;
- current GPS;
- checkpoint field support;
- elevation profile;
- photo condition reports;
- PWA installability;
- richer field interaction.

Future/Phase 2+:
- full offline map;
- off-route detection;
- continuous background tracking;
- live location sharing;
- SOS;
- social groups;
- marketplace;
- guide/porter booking;
- advanced ML/AI.

Do not move Future scope into MVP just because it is attractive.

---

# 33. ARCHITECTURE — MODULAR MONOLITH

Use a modular monolith for this team/project.

Do not introduce microservices without evidence-based need.

Domains:
- Identity;
- Hiker Profile;
- Discovery;
- Recommendation;
- Consideration;
- Trip;
- Preparation;
- Readiness;
- Condition;
- Community;
- Progress;
- Administration.

Preferred flow:
`Request → Validation → Controller → Domain Service → Data layer → Database`

Controllers must remain thin.

---

# 34. DOMAIN SERVICES

Recommended boundaries:
- `RouteFitService`;
- `RecommendationService`;
- `PreparationService`;
- `ReadinessService`;
- `OfficialStatusService`;
- `WeatherService`;
- `ConditionAggregatorService`;
- `ProgressService`;
- `DataSourceService`.

Do not put large business rules inside controllers, Blade templates, or ad-hoc client code.

---

# 35. DATABASE / POSTGRESQL / SUPABASE

Production-like relational truth:
- PostgreSQL/Supabase.

PostGIS types:
- POINT — mountain/checkpoint;
- LINESTRING — trail/segment;
- POLYGON — restricted areas where applicable.

Use spatial indexes for relevant queries.

Do not reduce a geospatial domain to raw lat/long strings if spatial behavior is required.

---

# 36. SCALABILITY / QUERY RULES

Query budget must not grow linearly with dataset size.

Avoid N+1.

Prefer:
- eager loading;
- batch loading;
- joins/aggregates where appropriate;
- pagination;
- caching;
- precomputed summaries;
- spatial indexes;
- background refresh.

Recommendation should use bounded query patterns.

Do not optimize prematurely for microservices.
Optimize first for:
- DB query count;
- indexes;
- payload size;
- cacheability;
- job separation;
- clear domain boundaries.

---

# 37. DATA LINEAGE / FRESHNESS

Every critical data object should be traceable through:
- source;
- source type;
- source URL when appropriate;
- retrieved_at;
- published_at when available;
- verified_at;
- scope;
- freshness state.

Freshness may be:
- CURRENT;
- AGING;
- STALE;
- UNKNOWN.

Do not use one universal freshness threshold for every source.

---

# 38. EXTERNAL INTEGRATION RULE

Preferred:
`Scheduler/Job → External API → Validate → Normalize → Store Snapshot → Cache → UI`

External providers should not become hidden synchronous dependencies of every user page.

Provider failure must not make the UI invent data.

---

# 39. DATA SNAPSHOTS / REPRODUCIBILITY

For recommendation runs retain:
- profile snapshot;
- goal snapshot;
- trail snapshot;
- condition snapshot where applicable;
- engine version;
- rules/config version;
- generated_at.

This supports:
- audit;
- debugging;
- reproducibility;
- trust.

---

# 40. SECURITY

Defense in depth:
`Browser → Laravel Auth → Authorization → Validation → Business Rules → Database Authorization/RLS → Storage Authorization`

Minimum:
- secure password hashing;
- secure sessions;
- Policies/Gates;
- object-level authorization;
- server-side input validation;
- mass-assignment protection;
- rate limiting;
- safe uploads;
- secret management;
- audit logs;
- location privacy;
- RLS where Supabase data is exposed.

Never trust user-supplied ownership/role IDs.

---

# 41. SUPABASE CONTROL

Before accessing Supabase:
- explicit owner permission;
- target environment confirmed;
- intended operation confirmed.

Never:
- print secrets;
- commit credentials;
- put service keys into scripts/source;
- expose service-role keys to browser;
- perform destructive production operations without explicit approval.

For exposed tables:
- correct grants;
- RLS;
- allow tests;
- deny tests.

---

# 42. PRIVACY / INDONESIAN PDP

Treat Indonesia PDP requirements as product/governance concerns.

Principles:
- data minimization;
- purpose limitation;
- transparency;
- retention/deletion controls;
- access control;
- location privacy;
- careful treatment of profiling/automated recommendations;
- secure processing.

Do not collect continuous GPS for browsing.
Do not expose precise personal route/location by default.

Before public production, verify current legal/regulatory guidance and operational obligations.

---

# 43. PROFILING / AUTOMATED RECOMMENDATION TRANSPARENCY

Route Fit uses user profile + route context.

Requirements:
- explain major factors;
- allow profile correction;
- do not use unnecessary sensitive health information;
- position output as decision support;
- do not claim medical/legal authority.

If a future feature creates a high-impact automated decision, stop and perform a privacy/legal review before implementation.

---

# 44. RELIABILITY / FAILURE MANAGEMENT

## Weather failure
- last known snapshot + timestamp;
- else WEATHER_UNAVAILABLE.

## Official source failure
- UNKNOWN/STALE;
- never silently OPEN.

## Recommendation failure
- fallback to browse/filter where possible;
- never fabricate.

## DB failure
- safe user-facing error;
- server-side log;
- no stack trace.

## Storage failure
- safe upload error;
- no false partial-success.

General principle:
> Unknown is better than false confidence.

---

# 45. RELIABILITY / SLO-STYLE TARGETS

These are internal engineering targets, NOT public SLA claims.

Suggested:
- production-like core availability ≥99%;
- critical HTTP 5xx <1%;
- valid recommendation requests ≥99% success or controlled fallback;
- scheduled external refresh ≥95% success;
- critical API p95 ≤1s excluding third-party network time;
- recommendation generation p95 ≤2s for MVP-sized dataset.

Measure before claiming.

---

# 46. OBSERVABILITY

Minimum:
- structured application logs;
- error tracking;
- metrics;
- audit logs;
- scheduled job monitoring;
- external integration health;
- data freshness monitoring.

Product telemetry may include:
- recommendation.generated;
- recommendation.empty;
- recommendation.fallback;
- trail.selected;
- consideration.created;
- trip.created;
- readiness.completed;
- weather.refresh.success/failed;
- official_status.refresh.success/failed;
- report.submitted/approved/rejected;
- trip.completed.

Use Laravel Pulse where useful.
Use Horizon/Redis when queue complexity warrants it.
Do not add observability tooling merely for appearance.

---

# 47. MARKETING / SEO / GROWTH

Marketing is part of product design.

Core funnel:
`SEARCH → PUBLIC TRAIL PAGE → ROUTE FIT → SHORTLIST → TRIP → HIKE → REPORT → PROGRESS → SHARE → NEW USER`

Use people-first SEO.

Public pages may include:
- mountain pages;
- trail/via pages;
- region/intent pages;
- preparation guides;
- current-condition summaries where appropriate.

Possible URL structure:
- `/gunung/{slug}`;
- `/gunung/{slug}/via/{trail}`;
- `/panduan/{topic}`;
- `/kondisi/{trail}`.

Use metadata/canonical/sitemap/structured data only when truthful to visible content and supported by current search guidance.

Do not generate thin AI content at scale for keyword manipulation.

Growth mechanisms:
- SEO;
- comparison sharing;
- community data;
- campus/outdoor-community partnerships;
- personal progression;
- repeat planning.

Do not optimize only for vanity traffic.

---

# 48. COLD-START / COMMUNITY

Community data is augmentation, not the sole foundation.

MVP should have curated seed data with source/provenance.

If no community report:
> “Belum ada laporan kondisi terbaru.”

Never fabricate current condition.

---

# 49. USER RESEARCH PROTOCOL

Suggested research participants:
- 5–8 beginner hikers;
- 3–5 experienced hikers;
- 2–3 guide/basecamp/management stakeholders if accessible.

Study real behavior:
- how users choose;
- information sources;
- trust;
- comparison difficulty;
- preparation behavior;
- route-difficulty interpretation;
- pre-departure checks;
- post-hike reflection.

Do not lead respondents with feature-satisfaction questions.
Prefer:
> “Ceritakan pendakian terakhir yang Anda rencanakan.”

---

# 50. USER TESTING TASKS

Minimum:
1. Find a suitable route.
2. Explain why it is recommended.
3. Compare three routes.
4. Save/select a route.
5. Find preparation gaps.
6. Distinguish official vs community information.
7. Check current status/weather.
8. Complete trip/report flow where implemented.

Measure:
- task success;
- time-on-task;
- error;
- confusion;
- comprehension;
- trust;
- SUS where applicable.

---

# 51. DATABASE / TESTING PARITY

Historical incident:
- tests used SQLite in-memory and all migrations existed there;
- actual application environment lacked migrations;
- tests passed while pages failed.

Rule:
> A green SQLite suite does NOT prove production-like PostgreSQL/Supabase correctness.

SQLite may be used only for fast tests where behavior is truly equivalent.

PostgreSQL-compatible integration tests are REQUIRED for:
- PostGIS;
- concurrency/locking;
- transactions;
- schema constraints;
- real indexes;
- production-like DB behavior.

Whenever migrations/schema change:
- verify actual app DB migration status;
- test runtime against relevant environment;
- do not claim "DB healthy" from tests alone.

---

# 52. SECURITY TESTING

Minimum critical tests:
- auth bypass;
- IDOR/object authorization;
- privilege escalation;
- mass assignment;
- unsafe upload;
- rate limiting;
- secret exposure;
- RLS allow/deny;
- private location access;
- account deletion privacy behavior.

Critical authorization tests: 100% pass before MVP release.

---

# 53. TESTING STRATEGY

## Unit
- Route Fit rules;
- readiness;
- freshness;
- preparation;
- progress calculations.

## Feature
- authentication;
- trip ownership;
- consideration ownership;
- report;
- moderation;
- admin policies.

## Integration
- PostgreSQL;
- PostGIS;
- external data normalization;
- storage;
- scheduler/jobs.

## E2E
Core journey:
`Register → Profile → Goal → Recommendation → Consider → Trip → Prepare → Check`

## Regression
Protect every fixed production defect with a regression test.

---

# 54. KNOWN REGRESSION CASES

Historical production defects that MUST remain regression scenarios:

1. Permit calculation added incorrect seven-hour-per-day increment and could state a permit was still open after closure.
2. Same-day departure displayed “Berangkat besok”.
3. Condition-report freshness displayed newer than actual.
4. Internal key `official_status_closed` leaked to UI.
5. Comparison crashed with `DivisionByZeroError` for zero-distance route.
6. Comparison existed but was unreachable from navigation.

Whenever adjacent code changes, execute/add regression coverage.

---

# 55. CURRENT PROJECT STATE — VERIFY, DO NOT ASSUME

Latest handover (21 September 2026) reports:
- branch `hardening`;
- branch not pushed;
- about 149 commits ahead of main at that point;
- 874 tests: 863 passed, 11 skipped, 0 failed;
- Pint clean;
- build successful;
- 11 skipped tests require `SPATIAL_TEST_DSN`.

Handover also reports nine migrations were subsequently executed against Supabase with zero pending and tables verified.

Older handover text may still mention the migrations as pending; treat such text as historical/stale and verify actual state before any claim.

Historical incident remains important:
- SQLite tests masked missing production migrations.

NEVER trust handover state without a quick runtime verification when the task depends on it.

Known/previously open engineering gaps to verify if still present:
- `lockForUpdate()` real behavior on PostgreSQL;
- timezone guard bypass through intermediary environment variable;
- UX coverage A4 reported 6/12 vs target ≥10/12;
- beginner usability study;
- SUS study;
- screen-reader walkthrough;
- production scheduler.

Do not state these are still open until checked.

---

# 56. LARAVEL ENVIRONMENT

Before application changes, verify:

```sh
php -v
composer -V
php artisan --version
composer show laravel/framework
```

Target runtime discussed by owner:
- PHP 8.5.

Do NOT silently change the Laravel framework version.
If actual version differs from documented target, report the discrepancy.

---

# 57. LARAVEL BOOST

Before application changes, Laravel Boost must be available.

If not installed:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

After Boost installation:
- read `AGENTS.md` again;
- follow generated project-specific instructions.

If Boost already exists:
- do not reinstall blindly;
- inspect/read `AGENTS.md`.

---

# 58. SKILL ORCHESTRATION — ALL INSTALLED SKILLS MUST BE EVALUATED

For EVERY task:

1. Inventory ALL installed/available skills.
2. Read instructions/supporting files for each applicable capability.
3. Build a Skill Applicability Matrix.
4. Classify every skill:
   - `CORE` — directly required;
   - `SUPPORTING` — materially improves correctness/quality/UX/security/architecture/testing/delivery;
   - `CROSS-CUTTING` — should influence work throughout;
   - `NOT-APPLICABLE` — genuinely cannot contribute.
5. Apply all CORE skills.
6. Apply all materially useful SUPPORTING skills.
7. Apply all CROSS-CUTTING skills.
8. Record NOT-APPLICABLE skills with a short reason.

Never skip a skill merely because it was not the first one considered.
Never run irrelevant skills mechanically just to claim usage.
Goal: capability orchestration, not ceremony.

---

# 59. ANTISLOP PROTOCOL

Antislop is applied DURING work and AGAIN at the end.

When available/applicable:
- `antislop` — always-on core filter;
- `antislop-code` — code/comments;
- `antislop-copywriting` — copy/UI text;
- `antislop-human` — human/people representation;
- `antislop-layoutmobile` — responsive/mobile;
- `antislop-ui` — UI/visual.

Use the actual installed skills discovered in the Skill Applicability Matrix.

Continuous workflow:
`PLAN → IMPLEMENT → ANTISLOP CHECK → TEST → CROSS-CHECK → FINAL ANTISLOP PASS → REPORT`

Look for:
- generic/canned copy;
- unnecessary cards/containers;
- fake hierarchy;
- repeated patterns without purpose;
- vague labels;
- decorative elements that do not support decisions;
- boilerplate code/comments;
- fake completeness;
- weak empty/error/fallback states.

Do not remove useful content simply to reduce text.

---

# 60. RESEARCH PROTOCOL

When research is requested or uncertainty affects a decision:

1. Define the question.
2. State known vs unknown.
3. Search primary/official sources first.
4. Use academic/research sources for behavioral/UX claims where possible.
5. Use product documentation for reference-product mechanisms.
6. Compare sources.
7. Separate:
   - FACT;
   - DECISION;
   - INFERENCE;
   - HYPOTHESIS;
   - RECOMMENDATION;
   - OPEN ISSUE.
8. Record impact on product/code.
9. Do not treat one source as support for a broader claim than it actually supports.
10. Never fabricate user research.

---

# 61. CROSS-CHECK PROTOCOL

After any significant change, inspect:

## Product
- solves intended problem?
- reinforces core loop?

## UX/HCI
- reduces friction?
- improves comprehension/decision confidence?
- right context of use?

## Architecture
- correct domain boundary?
- unnecessary coupling?
- scalable query pattern?

## Data
- provenance retained?
- UNKNOWN distinct from LOW?
- snapshot/version needed?

## Security/privacy
- unauthorized access possible?
- location/identity exposed?
- secrets safe?

## Reliability
- external failure handled?
- stale data handled?
- fallback truthful?

## Performance
- query count?
- indexes?
- caching?
- payload size?

## Accessibility
- keyboard?
- touch?
- contrast?
- screen reader?
- 400px mobile?

## Testing
- unit;
- feature;
- integration;
- E2E;
- security;
- regression.

## SEO/marketing
- public content useful/indexable?
- internal linking?
- share/referral loop?

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

# 86. GIT / DELIVERY CONTROL

Git is a client-delivery gate.

Allowed without explicit remote approval:
- local edit;
- local branch;
- local commit.

Requires explicit owner approval:
- git push;
- merge;
- release/tag;
- force push;
- destructive history rewrite.

Before push:
- tests;
- lint/format;
- diff review;
- secret scan;
- migration verification;
- final audit summary.

---

# 87. SUPABASE CONTROL

Before accessing Supabase:
- owner permission;
- target environment confirmed;
- intended operation confirmed.

Never print or store secrets in code/scripts.

For destructive/production operations:
- require explicit approval immediately before execution.

---

# 88. SKILL ORCHESTRATION CHECKPOINT

For every task, include an internal matrix like:

| Skill | Classification | Why | Applied? |
|---|---|---|---|
| ... | CORE/SUPPORTING/CROSS-CUTTING/N/A | ... | YES/NO |

For N/A, state why.

Do not skip skills simply because they were not obvious.
Do not run unrelated skills solely as theater.

---

# 89. LEARNING LOOP

Every completed task records:
1. What was learned.
2. Which assumption was proven/disproven.
3. What new problem was discovered.
4. Reusable pattern.
5. Anti-pattern to avoid.
6. Documentation/PRD/CLAUDE update required?

---

# 90. RECAP PROTOCOL

Every recap must contain:

## Progress
- overall %;
- current task %;
- method used to calculate.

## Completed
- facts.

## Evidence
- tests;
- runtime;
- DB state;
- logs/screenshots if applicable.

## Problems
- known.

## Root Cause
- evidence-based.

## Solution
- changes.

## Remaining
- open gaps.

## Risks
- unresolved risks.

## Learned
- lessons.

## Next Plan
- prioritized next actions.

---

# 91. NO SILENT PRODUCT DECISIONS

Never silently:
- add major features;
- change Route Fit;
- change MVP/MVP+;
- change navigation architecture;
- change authority hierarchy;
- change privacy/deletion policy;
- change database architecture;
- change framework version;
- change external provider semantics;
- introduce monetization/social competition.

For product-impacting changes state:
- current rule;
- reason;
- evidence;
- alternatives;
- impact;
- risks;
- recommendation;
- approval requirement.

---

# 92. DEFINITION OF DONE

A feature is DONE only if applicable:
- requirement implemented;
- acceptance criteria pass;
- validation exists;
- authorization exists;
- data rules enforced;
- errors/fallbacks handled;
- relevant unit/feature/integration/E2E tests pass;
- security considered/tested;
- accessibility checked;
- mobile checked;
- antislop pass completed;
- actual runtime verified;
- documentation updated;
- no critical/high unresolved defect;
- product intent cross-check passed.

---

# 93. FINAL QUALITY BAR

The product is NOT complete merely because:
- all pages render;
- CRUD works;
- a screenshot looks polished;
- tests are green.

The product quality bar is:

### EXPERIENCE
- discoverability;
- comprehension;
- comparison;
- confidence;
- preparedness;
- continuity.

### SYSTEM
- coherent architecture;
- scalable queries;
- correct data lineage;
- controlled failure;
- observability;
- strong authorization/privacy;
- PostgreSQL-correct behavior;
- accessible/mobile interaction.

### PRODUCT
- people-first discovery;
- useful community loop;
- return value through progression;
- honest source/freshness.

---

# 94. FINAL COMMANDMENT

> Do not optimize for more features.
>
> Optimize for a coherent, measurable, trustworthy hiking decision-and-progress experience.
>
> Use ATM to learn from proven products, then modify those mechanisms for the real Indonesian mountain domain.
>
> Research before guessing. Verify before claiming. Test before reporting. Cross-check before declaring done. Use all installed skills deliberately. Apply antislop during the work and again at the end. Never silently change the product contract.
