
Mountain Adventure Platform – Research & Strategy
Our goal is to build one unified mountain adventure platform that fuses the best aspects of AllTrails, Strava, Traveloka, Komoot, Wikiloc, Gaia GPS, PeakVisor, Outdooractive, and our original “Route Fit”/trust system. In other words, users should feel the combined impact of those reference apps — confidence, decision support, continuity, intelligence, community insight, spatial depth, visual mountain context, and safety information — all within one cohesive experience. Below we summarize key features from each reference, relevant UX/HCI patterns, and resulting architecture and roadmap recommendations.

Reference Products & Key Impacts
Discovery & Confidence (AllTrails) – AllTrails provides a massive trail database and filtering (by difficulty, length, etc.), with detailed trail info and maps. AllTrails Pro lets users download offline maps and see their GPS location on trail. In our platform, we must similarly let hikers “know” each trail (distance, elevation, time, difficulty) and build confidence with route details and trust indicators before they set out.

Decision Convenience (Traveloka) – Traveloka excels at streamlined search/filter and booking workflows. By analogy, our “Consider” and “Plan” phases should use multi-select filters and comparison views so users easily find and shortlist trails, then proceed to planning without confusion. (For example, clearly show active filters and result counts.) This simplifies choices just like Traveloka simplifies flight/hotel booking.

Continuity & Progression (Strava) – Strava tracks activities (running, hiking) and shows personal progress over time. It emphasizes a personal journey (not a public leaderboard). Our app should likewise save each user’s hikes, display improvements (e.g. faster time or more gain than last time), and suggest “next routes” tailored to their goals. As on Strava, users can see their historical performance and friends’ shared activities, fostering a sense of ongoing progression.

Route Intelligence (Komoot) – Komoot lets users plan sport-specific routes (cycling, hiking) with filters for distance or difficulty. It provides turn-by-turn navigation (including offline mode) so users really understand the route ahead. We should import that intelligence: our trail detail must include a rich route overview (geometry, elevation profile, technical demands) and offline-capable navigation support. In short, hikers should “really understand the journey” just as Komoot’s users do.

Community Knowledge (Wikiloc & Outdooractive) – Wikiloc is built on user-contributed GPS tracks and photos, while Outdooractive aggregates official park maps, trail conditions, weather, etc. We must blend both: offer community reports (trail condition, photos) plus authoritative status (official closures or updates). Crucially, our trust model will rank sources (official > admin-verified > community > AI suggestions) so that community insights inform but never mislead the official data.

Spatial Depth (Gaia GPS) – Gaia GPS is prized for its detailed offline topo maps and multiple map layers. It lets users download maps and plan routes from different map sources. We should make our maps similarly powerful: support multiple tile layers (topo, satellite, terrain), and full offline use. Every map view should be interactive (zoom/pan) with a focus on ease of understanding (“map as workspace” rather than static image).

Mountain Visualization (PeakVisor) – PeakVisor uses photos and 3D terrain to identify peaks and show elevation context. We won’t need AR, but we can give users a strong sense of the mountain’s shape and key points. For example, trail detail could include a 3D terrain preview or a labeled elevation chart, so users “visually know” the mountain’s profile (much as PeakVisor’s users do by snapping photos).

Outdoor Context (Outdooractive) – Outdooractive integrates challenges, weather info, and contextual layers (e.g. geology, flora) on its maps. We should likewise embed relevant context: live weather forecast for the trail, recent condition reports, and any ongoing challenges or community events in the region.

Summary: the experience impact we want is that hikers “feel the benefits” of each reference: confidence from detailed info (AllTrails), ease of choice (Traveloka), continuity of progress (Strava), deep route insight (Komoot), crowd knowledge (Wikiloc), rich maps (Gaia), mountain context (PeakVisor), and safety context (Outdooractive).

UI/UX & HCI Design Principles
To realize that fusion, our UI must follow modern UX best practices:

Adaptive, Map-first Layout: The core screens (e.g. Explore, Trail Detail) should be designed for all viewport sizes. Mobile vs desktop layouts may differ: for example, a map-and-list “Explore” view on desktop can show map on left and trail list on right, while on mobile it may stack or use a bottom sheet. Apple’s Human Interface Guidelines advise that maps be fully interactive (zoom/pan) and maintain search/filter context consistently across devices. We should use progressive disclosure: show essential trail info in the list, then reveal more on detail pages.

Rich Filters & Search: Emulating e-commerce best practices (Baymard Institute findings), the filter panel must make applied filters and counts clearly visible. Mobile filter controls can use bottom sheets or drawers. For example, users might filter by distance range, difficulty, region, etc., and see the number of results change instantly. They should be able to pick multiple filter values at once (e.g. “Hiking + biking + distance 10–20 km”). This matches Traveloka’s approach where search facets are always visible and result counts update in real time.

Clarity & Hierarchy: Visual design should be calm and trustworthy. Use a clean, uncluttered style (like AllTrails) with ample whitespace. Key info (distance, gain, time, fit badges, status alerts) should stand out. Text labels should be clear and explanatory (e.g. explain why a trail is a “good fit” for the user). We should prefer an editorial tone: use icons and color (e.g. greens for “good”, reds for “warning”) consistently but explain them (“Reason: steep terrain”). Accessibility must be baked in: high-contrast colors for map overlays, large enough touch targets, and readable fonts.

Accessibility: Follow WCAG guidelines. In particular, the interface must reflow for mobile (no two-dimensional scrolling when width ≤320px). All functions must be operable via keyboard (or adaptive input). That means clear focus indicators, ARIA labels for icons, and no hidden content. For example, if tooltips or menus appear on hover, they must remain accessible by keyboard and not trap focus. Ensuring accessibility will improve usability for all users.

Interactive Maps: Given the map-centric nature, our maps should behave as data-driven tools. We’ll use MapLibre GL (or Leaflet) to render vector tiles of trails and markers. Clustering or segmentation should be used when zoomed out to avoid overcrowding. Key map UI patterns: tapping a trail in map or list should highlight it, search should zoom to results, etc. We should follow Apple/Google map guidelines: keep user location visible (optional tracking), allow toggling base layers (satellite/terrain), and enable pinch-zoom/drag. For example, Komoot’s design of “filter by distance/difficulty and then navigate” is a model: filters narrow down choices, then the map guides them along the path.

Mobile & Desktop Parity: Some screens have both “user” and “admin” facets. For users, “Trail Detail” is customer-facing. For admins/moderators, a separate dashboard UI should exist, but share design language. Both UIs must use the same design system (colors, fonts, spacing) so the product feels unified. We’ll apply “experience parity”: the same core domain models and terminology (e.g. “trail”, “checkpoint”, “report”) appear in both user and admin screens, just with more control.

Data Architecture & Platform Design
Achieving these UX goals requires a solid data and system architecture:

PostGIS Backend: Store trails as spatial data (linestrings) and checkpoints as points in PostgreSQL with PostGIS. Index all geometry (GIST index) to allow fast queries (e.g. “find trails near lat/lng” or “does trail X intersect area Y?”). This enables map-based filtering and routing queries. For example, generating a user’s route fit involves querying trail length, sum of ascent, difficulty score, and matching against the user’s profile – all easily done via spatial SQL and attributes.

Vector Tiles & Caching: We should serve maps via vector tiles for performance. As one expert notes, “Vector tiles are really small, enabling global high resolution maps, fast map loads, and efficient caching”. We should pre-generate or cache vector tiles (e.g. with a tool like Tegola or Tippecanoe/Mapbox-GL) rather than hitting the database on every pan/zoom. In practice, this means a caching layer or MBTiles store so that tiles refresh only periodically. The goal is to avoid querying PostGIS on every map interaction: “Calling directly into the DB for every tile… should only be done during development/testing… you will almost always want a service that has a caching strategy”.

Geometry Simplification: To speed up tile rendering, reduce trail geometry resolution at lower zooms. For distant zoom levels, drop vertices so the tile size stays small. (This is common vector-tile practice: only include as much detail as fits the pixel scale.)

Trust/Provenance Data: Every piece of content (trail source, user report, weather data) must carry provenance metadata. We’ll record: source (official agency, admin entry, community member, AI-derived), timestamp (freshness), and verifier (which admin approved it). The UI will display badges like “Source: Official” or “Last update: 3 days ago” so that users know how much to trust it. Moderators will use the same data to prioritize moderation queues.

Feature Modules: Major modules include: Route Fit engine (calculating suitability), Trip Planner/Readiness (dates, gear checklist, autopopulating to-do items), Hike Tracker (start/pause/resume, GPS logging), Condition Reporter (form to upload photos and notes). Each module should be backed by its own Laravel controllers and API endpoints. We will design clean Domain Models (Trail, Mountain, Checkpoint, Trip, Report) rather than simple CRUD forms. For instance, rather than an “Admin > Trails” list of text fields, we envision a guided Wizard: define geometry, set parameters, assign sources, then a review step. This reflects a data-first approach (“Experience → Domain → Data”, not just “Form → Table”).

Integration Points: We’ll integrate external services for weather (e.g. open-meteo API), mapping (GeoJSON/vector tile hosting), and possibly user authentication (OAuth providers for social login). For payments/bookings, Traveloka had a focus on transactions; if we ever add a store (e.g. equipment or guided tours), we’d need a secure payment flow.

CI/CD and Quality: We already added GitHub Actions for linting and tests. Going forward, we’ll include front-end tests (e.g. Playwright/Cypress for key flows like search/filter/map). Every code push should trigger unit tests and a staging deploy. We’ll also set up performance monitoring (e.g. Lighthouse audits for page load and accessibility) to catch regressions.

Prioritized Roadmap
Based on the above, a pragmatic rollout plan is:

Data Preparation (Phase 2 blocker): Acquire or generate real GIS data. Right now many trails lack geometry and checkpoints lack coordinates. We must import GPX files or shapefiles and seed the PostGIS tables. Only then can Route Fit and mapping work. Milestone: All published trails have valid geometry; checkpoints have lat/lng.

Map & Backend Setup: Implement the mapping pipeline. Choose a tile server or pipeline (e.g. Tegola with GeoJSON from PostGIS, or generate MBTiles with Tippecanoe). Serve these tiles to MapLibre in the frontend. Ensure spatial queries (e.g. “trails near me”) are performant via indexes. Milestone: Interactive map in “Explore” shows all trails and allows zooming/panning with vector tiles.

Explore/List UI: Redesign the “Jelajah/Explore” screen. Replace current filter form with a modern search UI: a visible filter panel (with checkboxes, sliders for distance/elevation, etc.) and a dynamic list of trails. Add an interactive map component (powered by MapLibre) that highlights selected trails. Each trail card should show key info (distance, gain, “fit badge”, last reported status) and action buttons (e.g. “Details”, “Save”, “Compare”). Milestone: Users can filter trails and see results on both list and map, with no break in context.

Trail Detail Page: Overhaul the trail detail. At top, show the trail name, a hero image or map snapshot, and the “fit” status (e.g. “Good for you” with brief explanation). Sections to include: Route Summary (length, gain, time, difficulty, checkpoints, terrain types); Map & Elevation (interactive elevation chart plus route line on map); Current Conditions (official status, weather widget, latest community reports); and Your Plan (buttons to “Plan Trip” or “Check Readiness”). This page should educate the user: e.g. a tooltip or “Why?” link explaining the fit algorithm. Milestone: Trail detail feels like a mini-datacenter – all factors considered, not just raw data fields.

Trip Planning & Readiness: Implement the trip creation flow. Once a trail is chosen, the planner should guide the user through date/time selection, team size, and an adaptive checklist (gear, permits, fitness). Display progress bars (e.g. “3/8 items ready”). Integrate a “check fitness” step using our Route Fit logic again just before finalizing. Milestone: Users can save a planned trip, see its summary (“Patik Banteng on Sept 22–23”), and get reminders to prepare (akin to Traveloka’s pre- and post-booking screens).

Hike Tracking & Report: Build the in-hike experience. The app should allow the user to “start hike” and record GPS path (optionally with live stats). Upon completion, summarize the hike (distance, time, ascent) and compare to previous hikes on that trail (“+200m gain since last time”). Prompt the user to submit a “trail report” (conditions, photos) which feeds the community. This mimics Strava’s after-hike screen combined with AllTrails’ community updates.

News & Alerts (Kabar): Create a news feed section with categories: Official (park notices, closures), Conditions (weather updates), Community (recent reports/photos). Each item should be tagged with its source and timestamp. This section fuses Outdooractive and Wikiloc style updates. Milestone: On login, user sees any alerts (e.g. sudden closure due to weather), reinforcing trust.

Admin/Moderator Dashboards: Parallel to user features, build an operations console. For Admins/Experts, show data health (“X trails need geometry”, “Y status needs review”), and guides to add missing info. For Moderators, show a queue of incoming community reports, with details (photo, text, trail, age) and quick action buttons (approve/reject). This ensures behind-the-scenes data curation. It also closes the governance loop mentioned in our principles.

Testing & Polish: Throughout development, run thorough tests. Keyboard-navigate every screen, check responsive layouts, and add any needed aria-labels (per WCAG). Use the GitHub Actions pipeline to enforce 100% test coverage for business logic (Route Fit, data validation). Perform usability testing (usability.gov methods) on mockups to ensure filter & planning flows are intuitive.

Codex Agent Roles & Skills
To accomplish the above, the 9Router/Codex environment brings a set of specialized “agent skills.” We’ll inventory and leverage them. Likely relevant skill groups include:

Frontend/CSS Designer: Expertise in Vue3 or Alpine.js, Tailwind CSS (and Flowbite UI components), responsive design, and accessibility. This agent will implement the adaptive layouts, filters, and ensure WCAG compliance.

Backend/Laravel Expert: Proficiency in PHP 8+, Laravel 10, Eloquent models, and database schema design. Will build the core APIs (trails, trips, reports) and integration with PostGIS. Also sets up authentication/permissions and implements the Route Fit logic.

GIS/Mapping Specialist: Knowledge of PostGIS functions (distance, intersects, buffering), vector tile generation (Tegola or Mapbox/Tippecanoe), and MapLibre GL usage. This agent will design how trail data flows from DB to map, optimize spatial indexes, and ensure smooth map interaction (caching strategies, data simplification).

Data Science / Algorithm: Skilled in data analysis and simple ML (if used for personalization). Can refine the Route Fit algorithm, analyze hike data patterns, and possibly cluster trails. Also could handle any recommender tasks (e.g. suggesting next trails).

Testing / DevOps: Familiar with PHPUnit/Pest for PHP, and end-to-end testing frameworks. Sets up continuous integration (GitHub Actions), static analysis (PHPStan), and monitors test coverage. Also manages Docker deployment scripts and performance logging.

UX Researcher: Brings knowledge of HCI and UI trends. Validates design decisions, performs user testing, and refines the UI copy and flow (e.g. onboarding tooltips, error handling). Ensures the product feels “human” and not just a data dump.

Security / Data Governance: Ensures user data and system are secure (OAuth for login, CSRF protection, secure headers). Also designs the trust model: how data provenance is stored, and enforces permissions (only admins can publish trails, only moderators can approve reports, etc.).

Content/Documentation: Writes clear documentation, in-app help, and possibly content (e.g. “why this trail is a fit” explanations). Manages any multi-language concerns (e.g. Indonesian localization) and ensures style consistency.

By combining these agent capabilities, we can iterate efficiently. Next step: we will query the Codex system to pull the actual list of available skills in this environment, and then refine assignments.

Sources: Industry references and best practices (e.g. [AllTrails “offline maps and GPS” feature, Komoot’s route planning and voice nav, Gaia’s offline topo maps, PeakVisor’s peak recognition, Strava’s activity tracking). We also follow W3C accessibility guidelines for reflow and keyboard use, and GIS mapping advice on vector tiles and caching.


Executive Summary
We propose evolving the current Laravel application into a unified “Mountain Journey Platform” that integrates discovery, decision-support, planning, and progression for hikers, while providing robust tools for admins, moderators, and domain experts. This platform synthesizes the behavioral impact of three primary references—AllTrails (confidence through context), Traveloka (decision convenience), and Strava (continuity and personal progression)—plus secondary influences (Komoot, Wikiloc, Gaia GPS, Outdooractive, PeakVisor) into a single cohesive UX. Our approach covers product vision, user roles and journeys, data/architecture strategy, UI/UX guidelines, trust/evidence models, and a phased roadmap. We emphasize real-data validation (golden trail), accessibility (WCAG 2.2), modern web standards (PWA/SEO/performance), and strong governance (OWASP security, data provenance). Rigorous verification steps (grep/tests/build) and owner approval gates ensure infrastructure reliability before feature development. Diagrams (architecture, timeline, ERD) and tables compare reference products and outline deliverables. Before any code changes, we will inventory available Codex/9Router skills and design an AGENTS.md operating contract. This blueprint is evidence-based (citing recent industry data) and tailored to our agreed vision, with clearly defined Done criteria (L0–L4) and deliverables.

1. Product Vision & Thesis
We are building “Your Personalized Mountain Journey Companion.” Not a mere CRUD directory, this platform helps each user answer: “Which mountain trail should I hike, why is it a good fit for me, how do I plan and prepare, what’s the latest trail status, and how do I track my progress over time?” It merges:

AllTrails-like Confidence – rich context (topography, weather, community condition reports) to build trust in choices.
Traveloka-like Decision Convenience – powerful filters, side-by-side comparisons, and one-click planning workflows for trip organization.
Strava-like Continuity – a personal trail/hike history, goals, and self-improvement metrics (no leaderboards).
Komoot-like Route Intelligence – detailed route geometry, elevation profiles, and waypoints for truly understanding a hike before departure.
Wikiloc/Gaia-like Field Context – offline-capable maps, community-trail media, and layered map sources for “outdoor reliability.”
Outdooractive/PeakVisor-like Trust & Visualization – official sources, safety layers, and panoramic mountain views.
All UI/UX is guided by our domain rules: Trails are decision units; missing data never implies “low” safety; unknown ≠ unfavorable; official > community > AI evidence; openness ≠ safety. Real data must drive features: e.g., we must onboard a golden trail with actual geometry and checkpoints (see Phase 14) before declaring map features “done.” This ensures L3–L4 readiness (real-data and UX validated) before claiming completeness.

2. Reference Benchmarks & Behavioral Impact
Reference	Impact / Experience	Key Features to Emulate	What Not to Copy
AllTrails	Confidence through context	Trail discovery & saved lists; interactive maps; conditions/weather overlay; user reviews/media; offline routing.	Imitate UI theme – instead focus on integrating why a trail fits and current context.
Traveloka	Decision Convenience	Unified search+filter; package deals; one-click booking/plan; centralized itinerary view.	Complex “marketplace” flows – instead streamline filters, comparators, and trip planning (without payment).
Strava	Continuity / Motivation	Activity history; goals; profile stats; route builder; social sharing.	Competition/Leaderboards (we use self-comparison instead).
Komoot	Route Intelligence	Turn-by-turn navigation; multi-sport route planning; elevation & terrain info.	Advanced multi-route optimization – focus on clear single-route explanations.
Wikiloc	Community Knowledge	User-submitted trails, photos, geotagged waypoints; offline GPS tracks.	Unvetted crowd content – enforce moderation and official verification.
Outdooractive	Official Context	Authoritative trail maps (topo layers); closures/alerts; weather overlays.	None – we adopt its trust layers and situational info.
PeakVisor	Terrain Visualization	3D mountain views; peak identification; AR overlay.	Full 3D/AR (beyond scope); instead use elevation profiles and photos.

These references inform our Product DNA: AllTrails’ context + Traveloka’s clarity + Strava’s persistence + Komoot’s detail + Outdooractive’s trust + Wikiloc’s community + Gaia’s map depth. The resulting experience must make users feel:

“This app truly understands me and the trail: it tells me why I should hike it and helps me safely plan and improve.”
Similarly, Admin/Moderator/Expert users must feel their roles are coherently supported (see Section 5).

3. User Roles & Journeys
Hiker (End User):
Goal: Find trails that fit my profile/goals, plan trips, prepare gear, get trail status, navigate safely, and track my progress.
Journey: Discover (search/filter/map) → View Trail Details (map, stats, Route Fit explanation, official/community info) → Save/Compare trails → Plan a Trip (date, itinerary) → Prepare (checklist, weather) → Pre-trip Readiness check → Hike (GPS navigation, checkpoints) → Report (log completion, conditions) → Reflect (review stats, set next goals).
Admin (Content Curator):
Goal: Ensure trail data quality and governance.
Tasks: Manage trail/mountain metadata; approve new trails; verify geometry and checkpoints; flag incomplete data; update official statuses; oversee user accounts/roles; audit logs.
Workflow: Dashboard (Data Health summary) → Edit Mountain/Trail records → Review “incomplete” warnings → Publish/Unpublish trails (based on data completeness & rules) → Approve data sources/official status updates.
Moderator (Community Guardian):
Goal: Manage community contributions and maintain trust.
Tasks: Review community reports/photos; adjudicate condition reports; enforce content policies; respond to flagged safety issues.
Workflow: Queue of pending reports → Inspect report details (text, images, source) → Approve/Reject with feedback → Flag trends to Admin (e.g., repeated user issues).
Expert/Curator (Verified Contributor):
Goal: Contribute domain knowledge (terrain difficulty, segment info) with authoritativeness.
Tasks: Edit trail geometry, add checkpoints, adjust segment difficulty/tags, cite authoritative sources (maps, studies).
Workflow: Check assignment (e.g., mountain area in your expertise) → Use specialized editor to modify geometry and attribute data → Submit changes for review → Track approval status.
Each role’s interface is contextual but shares underlying systems (trails, maps, recommendations, reports). We enforce experience parity: e.g., Admin, Moderator and Hiker may see different facets of “Trail Detail”, but all utilize the same map/trail data. Roles are strictly enforced by policy (no self-approval, official overrides).

4. Fourteen Domain Systems
Our platform naturally decomposes into 14 domain systems:

Identity & Profile: Users, roles, goals, preferences. (Auth via Laravel; Profiles with hiking experience, fitness, etc.)
Geography & Trail Data: Mountains, Trails, Segments, Checkpoints (geometry + metadata). (PostGIS + Laravel models)
Route Intelligence: Recommendation engine (Route Fit) and explanation logic.
Discovery/Search: Filtering by region, difficulty, fit, status. (Full-text / geo search)
Decision/Comparison (Pertimbangkan): Shortlists, multi-route comparison interface.
Trip Planning: Trip entities with dates, itinerary, notes.
Preparation: Equipment lists, logistics, provisioning. (Linked to Trips)
Readiness Check: Pre-hike checklist and confirmations (e.g., "have you checked weather and permits?").
Hiking/Navigation: Live tracking, map guidance, GPS, checkpoint progression.
Conditions & Trust: Official status updates, weather snapshots, trail condition reports.
Community & Reports: User-reported conditions, photos, comments (with moderation).
Progress & History: Hike logs, performance stats, progression goals (no social, just self-improvement).
Governance/Audit: Admin controls, expert curation, audit logs, credential management.
Platform Foundations: Authentication, authorization, security, performance, SEO, PWA infrastructure.
Each “system” spans database schema, Laravel domain models, UI components, and tests. We will ensure modularity (Laravel Domain layering) but not microservices. All systems must comply with our evidence model and UI/UX guidelines.

5. Evidence Model (Static/Runtime/Database/Integration/UX)
We adopt a strict multi-layer evidence approach (building on our canonical rules):

Static: Code existence (routes, classes, config). E.g., “RouteDetail component exists.”
Runtime (Unit Tests): Automated tests (TDD) that verify logic in memory or with SQLite (Feature tests for Laravel).
Database: Data integrity on PostgreSQL/PostGIS (spatial query results, real data constraints, migration correctness).
Integration (CI): Full stack CI runs (npm build, Pint, Unit, Feature, Spatial tests) on PostgreSQL/PostGIS and Node environment.
UX (Browser/Manual): Actual rendering and interaction in browsers. Tasks validated by manual testing or E2E (outside current scope).
No evidence = UNVERIFIED. We will never consider a feature “done” without all relevant evidence layers passing. For example, a Trail Detail UI is not truly done if it renders with fake data; it must load real trail geometry and attributes (L3–L4). We will use this model in acceptance criteria.

6. Definition of Done (L0–L4)
Each feature must satisfy the following levels:

L0 (Planned): Feature is in backlog, with clear user story.
L1 (Code Exists): Implementation is written (controllers, models, components) and compiles.
L2 (Tested): Automated tests for feature logic pass (unit/feature).
L3 (Runtime + Data): Tested on real database data (PostgreSQL/PostGIS) and dev environment. Integration tests pass (CI).
L4 (UX Validated): Human-verified in a browser with realistic data. Meets design, accessibility, and usability expectations. E.g., [UX guidelines].
We aim for L4 on all critical surfaces (Jelajah, Trail Detail, Pertimbangkan, Perjalanan, Kabar, Progres) before moving on.

7. Trust & Provenance Model
All information is tagged with source and authority: official sources outrank community or AI. We display for critical data (e.g., official status, weather forecast, map data):

Value (e.g., “Open / Limited”)
Authority (Official > Admin-verified > Community > AI/Unknown)
Source Name (Park Authority, user handle, model version)
Timestamp/Freshness (when updated)
This is shown in the UI (badges/tooltips) on all data points. For example, an “Open” status will note “Source: Taman Nasional Authority, updated 2 days ago”. We enforce that AI/computed values (like Route Fit score) carry disclaimers and cannot override human data. Missing geometry or checkpoint data causes a trail to be non-recommendable (treated as unknown), not “low fitness.” This aligns with R-013–R-017 domain rules.

8. Route Fit Architecture & Recommendation Engine
Engine: The Route Fit engine (backend service) takes a user profile (experience, goals) and a trail profile (distance, elevation, difficulty) to compute a fit score and explanation. It encapsulates our domain rules (R-001–R-014) and is versioned (we log engine + rule versions).
Snapshots: Each recommendation run creates a snapshot record: linking user, trip context, trail, profile state, and result. We can later audit or explain why a trail was/isfit.
Explainability: The system generates a human-readable explanation (e.g., “Duration and elevation match your recent hikes; technical difficulty within your range”). These are shown in UI. We test the engine via unit tests and confirm with sample data.
Isolation: Engine runs as part of application (Laravel service class) or separate microservice if needed (but not initial plan). Regardless, it must operate deterministically on our PostgreSQL data.
Data Science/ML: We avoid opaque “black box” ML for now. Any algorithmic or ML model must be fully documented (R-021). Early MVP uses rule-based scoring derived from domain rules.

9. Spatial Data Strategy (PostGIS & Golden Trail)
Golden Trail: As Phase 14, we will import or manually create one complete “golden trail” with real geometry (LineString), multiple checkpoints (lat/lon), accurate distance/elevation, and attributes (terrain, navigation difficulty). This trail will power a complete live demo of map/navigation, Route Fit, and checklist flows. No fictional geo-data.
Indexing: We use PostgreSQL with PostGIS. Key indexes: GiST on geometry for fast nearest-trail or region queries; GIN on text for full-text search; B-tree on frequently filtered fields (e.g. status, difficulty).
Vector Tiles: For large datasets (if we store many trails), consider generating vector tiles via a tool (e.g., Tippecanoe or PostGIS’s ST_AsMVT) to serve to MapLibre, ensuring performance.
GeoJSON API: We will expose trail geometry via GeoJSON endpoints (Laravel controllers) with proper pagination/clipping. Map components (MapLibre GL JS) will load in viewports via url templates (no bundling entire DB to front-end).
Spatial Tests: We include spatial tests (PHPUnit with PostGIS) to verify queries (e.g., trails within bbox, correct elevation calcs).
10. Frontend IA & Design System
Adaptive Layout: Use Tailwind + CSS Container Queries. Mobile (bottom nav, single column); Tablet/Desktop (split view: list + map/panel). E.g., on large screens, show Jelajah with map beside filters; on mobile, bottom sheet filters.
Map-First Patterns: Consistent map interface (pan/zoom controls, legend). E.g., Jelajah page shows interactive map (cluster trails), with list and detailed card overlays (similar to AllTrails map). Trail Detail also shows map and elevation profile prominently.
Components: Design a UI Library: buttons, inputs, filters, cards, badges (for fit, status, authority), alert banners. Build these in Blade/Livewire with Tailwind variants. Use official brand colors (green for go/fit, amber for caution, red for warnings).
Typography & Spacing: Establish a clear hierarchy (hero headings, section titles, body, metadata). Ample white space for readability (mobile-friendly touch targets).
Accessibility (WCAG 2.2): Ensure keyboard nav, focus states, contrast (text ≥4.5:1), target sizes ≥44px. Respond to orientation and reflow (e.g., single-column on narrow screens). Use aria labels on map markers and status badges. Provide alt text for images.
Themes: Primarily light mode with outdoor-inspired palette; dark mode optional but not required.
UX Patterns: Show filters persistently; breadcrumb (e.g., Home > Jelajah) and “back” navigation. Use progressive disclosure (e.g., “Why?” toggles explanation).
11. UX Research & Validation Plan
We will conduct manual browser-based testing on all user/role tasks. Define core user tasks (Hiker, Admin, Moderator, Expert) and success criteria:

Hiker tasks (web):

Discover a trail: Use search/filters to find trails matching a profile (test: relevant results appear).
Explain a fit: Select a trail and read Route Fit explanation (test: explanation appears and cites factors).
Compare trails: Save 3 trails, compare their stats side-by-side (test: UI shows comparative table/charts).
Plan a trip: Create a trip, add chosen trail and date (test: trip calendar updated).
Pre-hike readiness: Run through checklist with weather and permit checks (test: prompts appear).
Access official info: On Trail Detail, see official status and weather (test: badges display).
Admin tasks (web):

Identify broken data: See dashboard summary of incomplete trails (test: count matches DB).
Edit mountain/trail: Update a trail’s publication status (test: table view refreshes).
Verify geometry: Approve/reject new GPS data submission (test: status toggles).
Moderator tasks:

Process a report: View a community report, examine photo, mark it resolved (test: report disappears from queue).
Flag trend: Mark an issue category (test: admin dashboard note increments).
Expert tasks:

Contribute geometry: Use map editor to adjust a segment (test: geometry displayed on next page load).
Provide evidence: Attach a source URL to a trail description (test: source link appears).
We’ll measure key metrics (in QA, not actual user testing): success rate and time for these tasks. UI acceptance requires no critical bugs, no WCAG failures, and adequate performance.

For browser verification, we will use manual checks and possibly headless tests (e.g., Selenium) after each major change. Key checklists (works pre / post changes):

Mobile & Desktop screens: Visually inspect on Chrome/Firefox/Safari (at least one).
Browser console: No uncaught JS errors on main pages (Jelajah, Trail Detail).
Performance: Measure Core Web Vitals; aim LCP<2.5s, CLS<0.1.
12. Backend Domain Architecture
We will adopt a modular monolith in Laravel:

Domain Layer (app/Domain): Pure business logic classes (e.g., Entities for Trail, Mountain, Recommendation, Condition, Report). Services (e.g., RecommendationService, NavigationService).
Application Layer (app/Http, app/Livewire, app/Console): Controllers, Livewire components, Commands and Jobs. These orchestrate Domain services.
Infrastructure Layer (app/Infrastructure): Data access (Eloquent Models, Repositories), external API clients (weather, maps), caching, and integration.
Support / Shared: Custom validation rules, observers, mailers, etc.
We enforce strict separation: Controllers only prepare requests and call Domain services. For example, a TrailController will fetch a Trail entity and pass to a TrailPresentationService for view model.

Authorization (Laravel Policies) is also in Domain (e.g., TrailPolicy), ensuring e.g. only Admin can publish a trail.

13. Logical Data Model (ERD)
Key entities (simplified):

has

creates

files

sets

has

contains

contains

receives

from

has

receives

has

receives

from

User

Profile

Trip

Report

Goal

Mountain

Trail

Segment

Checkpoint

Condition

Source

RecommendationResult

PreparationItem

OfficialStatus



Show code
This shows: Users have profiles/goals; Trails belong to Mountains; Trails have geometry (Segments & Checkpoints); Trails get Conditions from Sources; recommendation links Profiles to Trails; Trips link Trails to Dates; etc. (We will refine actual fields with migrations.)

14. Caching, Search & Observability
Caching:
Cache static/public data (mountain list, published trail list, reference tables) in Redis or Laravel’s cache for short TTL.
Invalidate caches on update (see existing tests for status cache invalidation).
Never cache private or user-specific data insecurely.
Search:
Use database full-text search (PostgreSQL tsvector) for trail names/description, combined with filter clauses.
For geosearch (e.g., “trails near me”), use PostGIS “ST_DWithin” queries.
Combine filters (location, difficulty, fit score) in SQL. Possibly integrate a search engine (Algolia/Elastic) in future but not initial scope.
Observability:
Logging: Use Monolog; log all exceptions, failed payments (if any), admin actions.
Monitoring: Instrument query logs and durations; track API error rates (e.g. weather API).
Metrics: Track site performance (use Prometheus/Grafana or Laravel Telescope in dev).
Alerts: Crash reports via Sentry (Laravel exception handler).
15. Security (OWASP Top 10 Compliance)
Auth & Access Control: Use Laravel’s built-in CSRF protection and bcrypt-hashing. Enforce strong policies (no “admin” pages to normal users). Review OWASP A01 (Broken Access Control).
Validation: Sanitize all input (XSS prevention in Blade using escaped output). Validate all uploads (file type/size for photos). Use Laravel’s mass-assignment guard.
Encryption: Use HTTPS everywhere (config), encrypt any sensitive config (APP_KEY). No sensitive data stored in source control.
Dependency Safety: Regular npm audit and composer audit checks. Pin PHP/Node versions (as we did).
Rate Limiting: Throttle critical endpoints (login, report submission).
Secrets: No API keys in repo; use environment or 9Router secrets.
Auditing: Log all admin/expert actions with user ID and timestamp (for review).
16. CI/CD & Testing Strategy
CI Pipeline (GitHub Actions): As fixed in phase 0, steps are: checkout, Setup PHP, Setup Node (v22), composer install, prepare env (.env.example + key:generate), npm ci, npm run build, vendor/bin/pint --test, php artisan test --testsuite=Unit, php artisan test --testsuite=Feature, php artisan test --testsuite=Spatial. PostGIS database (hiking_test) must be enabled for spatial tests. (All steps in one YAML.)
Test Coverage: Write thorough PHPUnit tests for: Trail logic, Route Fit engine, GPS queries, Report handling, user roles, and edge cases. Include regression tests for any past bugs (e.g., test that combined --testsuite is split).
Local Dev Validation: We include explicit steps in docs:
bash
Copy
grep -n "testsuite" .github/workflows/ci.yml
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
vendor/bin/pint --test
Expect exactly one Node install, one build, and two test suites. All pass. (We already verified this in Phase 0.)
Spatial Tests: Use a separate test database (hiking_test) with PostGIS. Include sample data for one trail to test geometry queries.
Linting: We use vendor/bin/pint (Laravel Pint) with a .pint.php or style rules. Also consider style/JS lint (e.g., ESLint) though not in the current plan.
17. Performance, SEO & PWA
Performance (Core Web Vitals):
Aim LCP ≤ 2.5s and CLS < 0.1 by optimizing images (lazy load hero maps/trail images), inlining critical CSS, and minimizing JS. Use Laravel’s built-in caching, optimize queries (N+1 prevention).
Use Vite’s asset hashing and HTTP/2 push for critical assets.
SEO:
Provide server-rendered meta tags for all public pages (trail pages, landing, etc.) via Blade.
Include schema.org structured data (e.g., [Mountain JSON-LD] for trails).
Generate sitemap.xml and robots.txt.
Route names consistent with REST/semantic style.
PWA:
Include a Web App Manifest (icons, theme color) for installability.
Service worker for offline fallback: cache critical assets and optionally trail data JSON (for “recently viewed trails” offline).
Network resilience: handle fetch failures gracefully (e.g., “No connection” messages).
18. Codex/9Router Migration & Skills Inventory
We will freeze Claude usage and onboard Codex via 9Router. First, run an inventory (prompt Codex): list all available skills (.agents/skills + global). Key categories: Laravel best practices, Livewire, Tailwind, testing, security, UX design, GIS/PostGIS, CI/CD, browser automation. Codex should identify overlaps/irrelevancies (e.g., deploying skills aren’t needed until deploy). The inventory will inform the AGENTS.md contract (our new “constitution”).
We define sandbox/approval policies: Quick tasks (planning, code editing) can proceed with normal checks, but Git actions (commit, push, merge), DB writes (Supabase), or design changes require explicit owner approval (as in R-018–R-021). Codex’s agent config on 9Router will enforce these rules.
We recommend using the most capable Codex model available (e.g., cx/gpt-5.3-codex) but not locking it; let 9Router manage model endpoints. No code is committed for this – it’s an operational procedure.
19. Phased Roadmap & Deliverables
We break the work into 15 phases. Each ends with owner approval gate before proceeding.

Phase	Focus	Deliverables	Acceptance Criteria	Risks & Mitigations
0. CI Baseline	Fix CI (assets-before-tests, split suites)	Updated .github/workflows/ci.yml	CI passes: Pint, 104 Unit, 791 Feature, 13 Spatial (evidence of execution)	Incorrect workflow may hide failures (Mitigate by strict grep tests); already done (see prior logs).
1. Repo Audit	Codex onboarding, skill inventory, AGENTS.md draft	Report of current code state vs. vision; proposed AGENTS.md outline; skill list	Codex can run tasks without blind spots; clear decision framework written.	Misalignment between Claude assumptions and reality (Mitigate by thorough doc analysis).
2. Architecture & Product Mapping	High-level diagrams (system, domain, journey)	Mermaid architecture graph; domain mapping doc; updated DESIGN.md	All major systems and data flows documented; no design mismatch.	Missed domain (Mitigate by review meetings).
3. Data Trust Audit	Real vs current data check	Data audit report (numbers of trails, missing geometry/status)	Gap list for Golden Trail phase.	Data gap (Mitigate by liaising with data sources).
4. Golden Trail Preparation	Ingest one complete trail	Golden Trail with geometry/points; maps rendering	Trail Detail view fully functional on golden trail (map, fit, conditions).	Data quality (use known verified trail).
5. Jelajah Redesign	Discovery page overhaul	New IA wireframes; implemented filters + map UI	Hiker tasks T1–T3 pass; accessibility checks done.	UI complexity (Mitigate iterative review).
6. Trail Detail Revamp	Decision page (heaviest content)	New UI for trail info, fit explanation, conditions, map	Includes authority badges, elevation chart; hiker T2 passes.	Data dependencies (mitigate golden trail first).
7. Pertimbangkan (Compare)	Decision workspace	Save/compare UI for 2–5 trails; notes	Hiker T3 passes; mobile & desktop tested.	Scalability (limit compare list).
8. Trip Planning	Itinerary and checklist	Trip entity UI with multi-step planning	Hiker T4 passes; integration with profile/goals.	UX flow confusion (mitigate with prototypes).
9. Preparation & Readiness	Packing list, weather checks	Equipment checklist; pre-trip readiness screens	Checklist covers mandatory items; readiness gate.	Missing data (use defaults).
10. Hiking/Navigation MVP	Live tracking and map	Simple “Hike mode” screen showing current position, next CP	GPS on device or simulator; mobile-tested.	GPS reliability (simulation fallback).
11. Kabar (News/Alerts)	Official & community feed	Kabar dashboard with filter by category (Official, Condition, Community)	Displays at least: status, weather, user reports with timestamps.	Content moderation (limit spam).
12. Progres (History)	Activity logs	Timeline view of past hikes and progress metrics	Strava-like self-comparison charts; data correctness.	Data migration (none).
13. Admin/Moderator UI	Governance consoles	Admin dashboard (data health), Moderator queue UI	Admin tasks complete: create/edit trails, verify statuses; Moderator tasks T1–T2 pass.	Role leaks (comprehensive policies).
14. Security & Performance Hardening	Final polish	OWASP checks done; performance optimized; SEO schema; PWA	LCP<2.5s, no critical vulnerabilities (scan).	Late-found issues (perform early review).
15. UX Testing & Launch Readiness	Validation	Usability test results; bug-fix sprint	All core tasks success ≥90%; no P1 bugs.	Unexpected UX issues (plan buffer).

Prioritized backlog: All “Phase 0” tasks are P0 (must do now). Phase 1–5 are P0 for MVP baseline. 6–9 are P0 for the golden-slice (Trail Selection & Planning). Phases 10–12 add valuable features (navigation, Kabar, Progress) – treat as P1 after core P0. Phases 13–15 are pre-launch quality and enhancements (P1–P2).

20. Verification & Next Actions
Before any code changes, we inventory skills via 9Router’s Codex (ensuring we leverage e.g. laravel-best-practices, tailwindcss-development, testing-best-practices, postgis-tools, etc.). Then we finalize AGENTS.md incorporating project vision (see Section 1) and engineering policies (from above steps). All subsequent tasks are gated: after each phase, we present diff evidence (git diff, test logs) for owner approval before pushing to origin/hardening.

Immediate next steps (to hand off to Codex agent):

Inventory Skills: Run ls .agents/skills and ls ~/.agents/skills via shell; identify relevant skill metadata.
Repository Audit: Generate list of branches, HEAD, uncommitted changes.
AGENTS.md Draft: Create AGENTS.md summarizing vision (sections above).
Planning: Break Phase 0 tasks into Codex assignments (CI fix done), then schedule Phase 1 design work.
Checklist for Codex agent:

 Analyze AGENTS.md and .claude/rules for overriding policies.
 List available Codex skills, determine which to use for tasks (UX, GIS, etc.).
 Prepare sandbox settings (should have git write restricted).
 Align on approval process: no GitHub push without owner.
 Confirm interpretation of product domain and rules (call out any conflicts with existing code).
This comprehensive blueprint should guide our team in building the Mountain Journey Platform step-by-step, ensuring we fuse the best of AllTrails/Traveloka/Strava (and others) in a controlled, evidence-driven manner. Each phase ends with measurable deliverables and documented test results, ready for owner review. Only after full CI green and manual UX validation do we proceed to the next phase. By following this plan, we maximize product value, maintain vision alignment, and uphold high quality and security standards.

Sources: Industry reports (AllTrails ~90M users), W3C/Accessibility guidelines, OWASP Top 10, Google Core Web Vitals recommendations, and product documentation of referenced apps. These and internal domain rules are cited or embedded in our design documentation above.


Executive Summary
This report delivers a comprehensive blueprint for the envisioned Mountain Journey Platform, aligning with the owner’s vision of a personalized hiking/planning experience. It synthesizes lessons from industry leaders (AllTrails, Strava, Traveloka, Komoot, Wikiloc, Outdooractive, PeakVisor) and proposes an evidence-based strategy. We outline the full scope of domains (Identity, Mountain Data, Route Intelligence, Discovery, Decision Support, Trip Planning, Preparation, Readiness, Field Experience, Conditions/Trust, Community, Progression, Governance, Platform Foundation) and estimate effort for each. A multi-phase roadmap (Phase 0…Phase 14) is prioritized by business value and feasibility, with clear deliverables, acceptance criteria, and evidence levels (STATIC, RUNTIME, DATABASE, INTEGRATION, UX). We design a domain-driven Laravel architecture and PostgreSQL/PostGIS schema, including a reproducible Recommendation Engine (Route Fit) with provenance and versioning. We plan caching, search/discovery strategy, observability, and security (threat model, role policies).

On the UX side, we prescribe a design system (typography, color, spacing), adaptive layouts (mobile/tablet/desktop), map-first patterns, progressive disclosure, and WCAG-focused accessibility. We sketch key screens (e.g. “Jelajah” Discovery, Trail Detail, “Pertimbangkan” comparison workspace, Trip Planning, Hike mode, Admin/Moderator/Expert dashboards) with user flows. We compare features from benchmark apps: which mechanisms to adopt (contextual confidence from AllTrails, decision filters from Traveloka, continuity from Strava, route intelligence from Komoot, community trails from Wikiloc, spatial depth from Gaia, mountain visualization from PeakVisor) and which to avoid (fake leaderboards, unnecessary gamification, bypassing trust rules). We outline a Golden Trail data plan for real geometry and checkpoints, including ETL, provenance metadata, and verification workflows.

On the delivery pipeline, we fix the CI baseline (ensuring npm ci and npm run build run before tests, and test suites separate). A detailed CI/CD and testing plan covers unit/feature/spatial tests, acceptance gates, and rollback policy. We also prepare a Codex/9Router agent onboarding plan: inventory available skills, draft an AGENTS.md operating contract, sandbox/approval models, and agent working protocol.

This report concludes with immediate next steps: concrete commands, checks, and evidence to gather (e.g. diagnose CI run #2 logs, perform Codex skill inventory, conduct a read-only audit, secure a Golden Trail dataset). Finally, we list open decisions and required owner approvals (e.g. data acquisition for gold trail, CI gating policies, feature priority trade-offs).

The goal is a mature, secure, and user-centric mountain platform where users feel “confident with context” as in AllTrails, “empowered in decision” like with Traveloka’s filters, and “connected to their journey” like on Strava – all while maintaining our own differentiators of Route Fit, data trust, and readiness.

Scope and Domains
We divide the platform into 13 domains, each covering specific data, functions, and roles:

Identity/Profiles – user accounts, profiles, goals/preferences, experience levels, and permissions (Hiker, Admin, Moderator, Expert).
Mountain & Trail Data – mountains, trails, trail segments, checkpoints, geometry (PostGIS), official status and sources, attributes (distance, elevation, terrain, difficulty, navigation complexity).
Route Intelligence (Recommendation Engine) – user profile & goals + trail data → Route Fit score + explanation. (Reproducible algorithms, rule metadata, versioning, factor weights.)
Discovery (Jelajah) – search and browse interface for trails and mountains. Incorporates spatial search (map + list view), filters (distance, difficulty, status), saved lists.
Decision Workspace (Pertimbangkan) – “consideration table” where saved routes can be compared side-by-side. Allows notes, shared views, removal. Focus on user understanding differences (no leaderboards).
Trip Planning (Perjalanan) – itinerary management (selected trail, date range, type), brief summary of planning progress.
Preparation (Persiapan) – checklist of equipment/logistics tailored to route (e.g. gear list), integration of official requirements (permits, regulations), weather forecasts.
Readiness (Cek Kesiapan) – pre-departure check: verifies trip vs conditions. Presents official alerts, weather updates, trail reports. Decisions (Go/No-go) guided by data (source & freshness).
Field Experience (Hike Mode) – mobile-friendly navigation: interactive map (MapLibre), GPS tracking, checkpoint prompts. Possibly offline support. Real-time stats (distance, elevation, time).
Conditions & Trust (Kabar) – unified feed of official notices (closures, legal changes), current weather, user-reported trail conditions, freshness, all tagged by authority level (Official > Admin-Verified > Community > AI) and timestamps.
Community (Komunitas) – photo uploads, condition reports, trail reviews, Q&A. Tightly moderated (no anonymous data overrides official). Appreciation (likes) but no generic social feed.
Personal Progression (Progres) – user’s hiking history, personal stats (total distance/elevation), comparatives to self (no competition with others). Goal tracking and milestones (e.g. first 100 km, elevation clubs).
Governance & Audit – Admin/Moderator/Expert tools for data quality: dashboards showing data gaps (missing geometry, stale status), moderation queues, expert contributions (geometry editing, source updates) with audit trails and pending-publication gating.
Platform Foundation – cross-cutting concerns: Security (AuthN/Z, data privacy), Performance (caching, pagination, maps), Observability (logging, metrics), Mobile/Web frameworks, CI/CD pipeline, compliance (WCAG, privacy laws).
Effort/Complexity: Estimated from architectural experience:

High complexity (H): Recommendation Engine, PostGIS integration, Hike Mode (mapping & offline), Data Provenance & Trust, UI/UX design system, Security/audit.
Medium (M): Discovery/Filters, Administration console, Community contributions, Trip/Preparation flows, Observability/Caching, Responsive layouts.
Low (L): Identity/Profile (leveraging Laravel auth), static content (landing pages), basic Unit/Feature tests (CI baseline).
These phases are interdependent: e.g. fields in database must exist before UI can display them; domain rules must be encoded before testing can enforce.

Benchmark and Key Insights
We benchmarked eight reference products to extract relevant mechanisms and avoid pitfalls:

AllTrails (Trail Confidence): A massive trail catalog with hand-curated routes. Each trail page shows map, distance, elevation, driving directions, photos/reviews, and community activities. Users discover via map/list and rich filters (activity type, difficulty, length, completions). Core impact: “User feels confident and informed.” Lessons: location-based search (places, parks, trail names) with filters, curated content, and community inputs (photos, reviews) for context.

Traveloka (Decision Convenience): A leading SEA travel-booking site. Its UX revolves around search boxes (flights/hotels), rich filters (dates, class, price range), and guided step-by-step booking flows. The goal impact is a frictionless decision process – clear inputs yield relevant options, and post-selection the itinerary/booking is transparently managed. We apply this to trail search/filters (e.g. distance, difficulty, official status) and to structuring trip planning as a guided 5-step checklist (choose → plan → prepare → check → hike).

Strava (Continuity & Progression): A social fitness app for tracking activities. Key features: GPS activity recording, profile with aggregate stats (distance, elevation), personal bests, and timeline of activities. Social features: follow, kudos, comments, and global segments/leaderboards. We avoid raw leaderboards (contradicts project’s self-competition ethos), but adopt their continuity: users see their hiking history and progression. Impact: “My hikes today build on my past.” We incorporate personal goals and highlight relative improvement (e.g. faster summit, more elevation than last time). We cite Strava’s summary: “Strava is a social network for athletes... [where you] can discover, record, plan and analyze workouts... [and] challenge yourself with...competition between you and your friends, the pros, and your own ‘personal best’... sharing those stats and creating a community around exercise”. In our product, sharing and community is limited, but the concept of personal best remains.

Komoot (Route Intelligence): A multi-sport route planner. Advertises: “Plan the perfect route... sport-specific routes tailored to your needs”. Offers curated route collections, filters (distance, difficulty, transit), turn-by-turn voice navigation and offline maps. Impact: user “understands the trail profile and stays on course.” We adopt Komoot’s style of elevation profiles, route optimization (not just straight lines), and offline nav.

Wikiloc (Community Trails & Offline): A crowd-sourced trail repository. “We are 21.8M members sharing 83.2M outdoor trails and 150.8M photos”. It emphasizes user-recorded GPS tracks, free offline maps, and live tracking. Impact: “Insider field knowledge from peers.” We plan a similar community upload of trail segments, with GPX export and offline use, but governed by our trust hierarchy (community data cannot override official routes without verification).

Gaia GPS (Spatial Depth): A mapping app (especially for offline/backcountry use) with many map layers (topo, satellite, trails), route recording, and synchronization. We take from Gaia the principle that “maps are not mere decorations but interactive decision surfaces.” We commit to a PostGIS-centric backend and lightweight vector data to power MapLibre on the web.

Outdooractive (Official Context): A German platform blending official trail/park data with community. Offers multi-layer maps (topographic, official park maps), official POIs, safety info, and weather. Impact: “User trusts the official data layers.” We integrate official regulations, closures, and weather alerts explicitly into UI (e.g. a Kabar “Official” section).

PeakVisor (Mountain Visualization): Uses AR to identify mountain peaks and provides 3D mountain models. We borrow the idea of contextual orientation: e.g. mountain images and mountain encyclopedias (like a page for each mountain with photo, height, coordinates) to help users “see” the terrain.

Features to Adopt: Contextual trust signals (source, timestamp) from AllTrails and Outdooractive. Advanced filtering and saved lists from Traveloka and Komoot. Offline map capabilities from Komoot/Wikiloc. Personalized goal tracking from Strava. Community tracks/photos from Wikiloc.

Features to Avoid: Leaderboards or gamified social feeds (contrary to self-comparison philosophy). Overloading UI with irrelevant social content. Default assumptions of data completeness (we mark unknowns clearly).

Behavioral Goals: The combined emotional impact should be:

Confidence: Users say “This system has all the info I need to trust my choice” (AllTrails).
Efficiency: “I can filter and decide quickly” (Traveloka).
Continuity: “Each hike builds on my history” (Strava).
Clarity: “I know why this route fits my goals” (Route Fit + Komoot style explanation).
Context: “I see official alerts and real conditions” (Outdooractive + AllTrails).
Exploration: “I can switch between map and list views seamlessly” (AllTrails, Komoot).
Focus: “UI shows me only what I need now” (progressive disclosure).
See Table 1 for feature mapping and priority vs. benchmark:

Reference	Key Impact	Adopted Mechanisms	Avoid/Limit
AllTrails	Confidence via context	Curated trail pages with map, stats, reviews, photos; map & list search; filter by difficulty/type	Overwhelm with irrelevant trails
Traveloka	Decision convenience	Prominent search fields; multi-criteria filters; clear state transitions (discover → plan → pay)	Complex booking flows (we have simpler tasks)
Strava	Continuity & progression	Activity history/log; personal goal tracking; milestones	Public leaderboards (we use self-comparison only)
Komoot	Route intelligence	Sport-specific routing; elevation profile; turn-by-turn nav; offline maps	Rigid A→B routing only, ignoring context
Wikiloc	Community trail knowledge	User-uploaded routes; offline downloads; photos; local tracks	Unverified user data override official info
Gaia GPS	Spatial depth	Multiple map layers (topo, satellite); offline/cached maps	Bloated map data (we’ll use vector tiles)
Outdooractive	Official context	Official trail networks; closures/notices; weather layers	Disconnected commercial content
PeakVisor	Mountain visualization	Mountain detail (photos, geology, peak IDs); AR guidance	Extraneous AR/UI beyond scope

(Citations: AllTrails support, Strava description, Komoot homepage, Wikiloc stats)

Phase Roadmap
We break development into iterative phases, each with explicit deliverables, acceptance criteria, and evidence level (Static code, Runtime behavior, Database state, Integration flows, UX/Browser tests). Definition-of-Done (DoD) follows L0–L4 completion levels: Planned, CodeExists, AutomatedVerified, RuntimeDataVerified, and UXValidated (see later section).

Phase	Focus & Deliverables	Acceptance & Evidence
0. CI Baseline (DONE)	Fix CI workflow (npm ci/build before tests; separate suites). Ensure build+tests pass on all runners.	CI workflow file fixed; verified by CI run: Pint, Unit tests (SQLite), Feature tests (SQLite), Spatial/PostGIS run; no double --testsuite; only ci.yml changed; consistent hiking_test data. Evidence: green CI, git show logs.
1. Code Audit & AGENTS	Read-only audit of current code, config, docs, and agent skills. Produce AGENTS.md draft (operating contract). Inventory Codex/9Router skills.	AGENTS.md outline created; readouts of skill inventory; list of orphaned/legacy instructions. Demonstrate understanding of vision and repo. Evidence: audit report.
2. Architecture Reconnaissance	Map existing code to domains (outline folders, services). Identify missing gaps (schema migrations). Sketch target DDD structure without moving code.	Diagrams of current vs target architecture; identified discrepancies. Evidence: architecture docs.
3. Data & Trust Foundations	Golden Trail creation: ingest at least 1 trail with full data (geometry, checkpoints, metadata). Verify PostGIS usage: spatial indexes, queries. Establish provenance metadata fields.	Golden trail inserted; unit tests on geometry queries (ST_DWithin, etc). Evidence: GIS test logs, UI preview of map.
4. Discovery & Filter UI (Jelajah)	Implement search page: map and list view of trails, filters by key attributes (distance, difficulty, status). Save list. Mobile/desktop adaptive layout.	Functional search + filters; static test of query clauses; cross-browser rendering. Evidence: browser screenshots, UX walk-through.
5. Trail Detail Page	Build detail view: trail summary, map, elevation profile, Route Fit badge & "Why", official status, community stats. Info hierarchy (hero image, key facts, conditions).	All sections present with data from Golden trail. Acceptance: static inspection; test DB returns, map render. Evidence: user test recording clicks.
6. Route Fit Explanation	Refine recommendation engine: ensure reproducibility (rule version, seed values). Show detailed factors (duration, incline, technical).	Unit tests on RouteFit outputs given profile/trail; version tagged. Evidence: rule engine logs, UI explanation tested with mock profiles.
7. Pertimbangkan (Comparison Workspace)	Implement saved routes list, comparison view (max 5), ability to annotate, remove. No ranking.	UI for selecting/sorting; static test comparing fields side-by-side. Evidence: screenshot of sample comparison.
8. Trip Planning + Preparation	Create Trip entity (trail+dates/type), initial plan page summarizing steps. Checklist UI for gear/regulations.	Trip CRUD works; data flows correct; checklist generation. Evidence: local run with sample trip data.
9. Readiness & Check Flow	“Check Readiness” feature: compiles weather forecast, trail alerts. Shows go/no-go signal with justification.	Integration with weather API mock; rule for advisories. Evidence: scenario test where low confidence triggers “Reconsider”.
10. Hike Mode (Field Nav)	MapLibre-powered hiking interface: follow route, show distance/elevation in real-time. Mobile performance optimization.	GPS simulation test; map routing matches geometry; offline caching stub. Evidence: recorded browser+console logs of navigation simulation.
11. Community Reporting & Kabar	Build “Kabar” news feed: official status, weather, latest reports. Reporting form for users (photos, condition).	Data entry to feed appears properly. Moderation queue stub. Evidence: new report passes to queue (test logs).
12. Personal Profile & Progress	Hiker dashboard: history of past trips, cumulative stats (distance, ascent). Progress charts (self-comparison). Goals.	UI shows at least one past hike, stats correct. Evidence: static data verify.
13. Admin & Moderation Console	Admin dashboard: data health (missing data counts, stale sources); moderation queue UI; expert edit wizards.	Functional navigation for Admin role; correct counts from DB. Evidence: test accounts role-restriction enforcement.
14. Security & Performance Hardening	Audit OWASP top10; enforce RBAC policies; cache keypages; finalize CI (security scans). Accessibility audit pass.	Penetration test run; benchmarks meet Core Web Vitals targets. Evidence: scan reports, Lighthouse scores.

Each phase must meet Definition of Done at level L3 (code + tests + runtime working) and move toward L4 (UX validation with users) for major features.

Verification Levels: We tag each deliverable’s evidence: STATIC (code, schemas, config), RUNTIME (manual or automated runtime behavior), DATABASE (data integrity, migrations), INTEGRATION (end-to-end flows), UX (user test video or metrics).

Architecture & Data Design
We adopt a Domain-Driven Design (DDD) approach on Laravel (13) and Postgres/PostGIS.

mermaid
Copy
flowchart LR
    subgraph WEB_SERVER
    UI[User Interface (Livewire/Blade)] -->|calls| CONTROLLER
    CONTROLLER -->|uses| SERVICE
    SERVICE -->|reads/writes| DOMAIN
    end

    subgraph DOMAIN_CORE
    DOMAIN[Domain Entities & Services]
    end

    subgraph INFRASTRUCTURE
    INFRA[Weather API, Map Tiles, Storage, Cache, External Services]
    end

    DB[(PostgreSQL + PostGIS)]
    CONTROLLER -->|query| DB
    SERVICE -->|calls| INFRA
Domain Layer: Contains core entities (User, Profile, Mountain, Trail, Segment, Checkpoint, RecommendationRun, Trip, Report, WeatherAlert, etc.) and business logic services (RouteFitEngine, ModerationService). Follows folder structure app/Domain/{Feature}/....
Application Layer: Livewire components and Controllers orchestrating flows (DiscoveryController, TrailController, TripController, AdminController, etc.).
Infrastructure: Map/Weather integrations, Storage (S3 for uploads), caching (Redis), queue processing (Laravel Queues for heavy tasks like recommendation calculation).
Data Layer (Postgres + PostGIS):
Mountains/Trails: Tables mountains, trails with GeoJSON geometry (trail.geometry as LINESTRING, checkpoints with POINT), and attributes (distance, elevation_gain, technical_difficulty, nav_complexity, surface type). Use GiST spatial index on geometry.
Checkpoints: checkpoints table (trail_id FK, sequence, point geometry).
Recommendation: recommendation_runs (id, user_id, started_at, engine_version, rules_version), recommendation_results (run_id FK, trail_id, score, explanation JSON). This makes Route Fit reproducible (store seed, versions, user snapshot).
Status & Provenance: Tables for official_statuses, sources, freshness_records linked to trails. Each has source URL, timestamp, verified_by (FK to admin user), and validity windows.
Users/Profiles: users (auth) + profiles (preferred activities/goals, skill, fitness). Roles via Laravel’s gates/permissions.
Community: reports (user_id, trail_id, type, description, media_refs), photos (user uploads with geo-tags, FK to report or trail), moderations for each report.
Trips: trips (user, trail, start_date, end_date, type, status). preparations (trip_id, item, done).
Progress: hike_history (user, trail, date, duration, distance, ascent). Summaries can be derived or stored.
(Table) A summary database schema snippet for key tables:

Table	Key Columns (normalized)	Notes/Indexes
mountains	id, name, region, height_m, geom (POINT)	name unique; index on geom
trails	id, mountain_id, name, geometry (LINESTRING), distance_km, elevation_gain	GiST index on geometry
checkpoints	id, trail_id, seq_no, location (POINT)	GiST index; seq ordering
sources	id, trail_id, type (Weather/Status), url, provided_by, provided_at	
official_statuses	id, trail_id, status (open/closed/etc), last_update, source_id, expires_at	
recommendation_runs	id, user_id, started_at, engine_ver, rule_ver, input_profile JSON	
recommendation_results	id, run_id, trail_id, score, explanation JSON	unique(run_id, trail_id)
reports	id, user_id, trail_id, report_type, description, created_at	
users (profiles)	id, name, email, role (enum), fitness_level, preferred_activity	
trips	id, user_id, trail_id, start_date, end_date, type	
photos	id, user_id, trail_id (nullable), report_id (nullable), url, taken_at	

Spatial PostGIS features: ST_Length(geometry) to compute distance, ST_DWithin for nearby search, ST_AsGeoJSON for API. All geometry columns indexed with GIST. Use function indexes if needed for conditions (e.g. check if geometry ~ desired distance).

Recommendation Engine:
Implement an isolated RouteFitEngine service:

Takes user profile+goals + candidate trail data.
Applies a chain of factor functions (duration match, elevation challenge, technical demand, navigation risk, completed state, etc.), each with rule metadata.
Scores weighted sum into categories (COCOK, PERLU PERSIAPAN, KURANG COCOK).
Provenance: On each evaluation, store engine_version and rule_version references (like Git commit or semver). Each rule can be coded with unit tests. Save the RecommendationRun record with a random_seed if any randomness is used.
Reproducibility: Tests generate fixed dummy profiles/trails and assert expected scores.
The UI shows the summary label (COCOK, etc.) and “Why” explanation bullets. The internal numeric scores remain hidden from users.
Caching & Search:

Use Redis for caching frequently accessed data (mountain list, popular trail queries, static lookups). Implement cache invalidation: e.g. update caches on new trail publish or status change (test with sample).
For text search, use full-text search on trails.name, mountains.name. Combine with filter queries. For spatial discovery, allow map-based bounding box and keywords.
Potentially use PostgreSQL’s pg_trgm or a small search index for free-text (if Indonesian locale needed, configure the right collation).
Search/Discovery Flow:

Map View: Users can pan/zoom (MapLibre). As they move, query trails within bounding box (using ST_Intersects). Show as map pins and list.
List View: Keyword/location filter + attribute filters. Order by distance or fit.
Filters: Activity type, length range, difficulty, current status, recommendation result (optional), save status. Traveloka-style facet UI on desktop (side pane) and a drawer on mobile.
Observability:

Logging: Laravel logs for errors, plus custom logs for critical flows (recommendation errors, weather API failures).
Metrics: track response times, query counts (Laravel Telescope in dev).
Health checks: verify DB connection, PostGIS extension present, Redis up, etc.
Use X-Ray or Jaeger (if available) for tracing route from UI click to DB.
CI/CD:

The CI baseline is fixed (Phase 0). For future pipelines, use GitHub Actions or similar for main/hardening branches. Include security scans (e.g. PHPStan, OWASP zap).
Database migrations tested via PHPUnit (Laravel’s RefreshDatabase).
Docker-based worker can include postgis for Spatial tests.
Manual gating: before any push to hardening, require passing all tests plus a human review step.
Security Threat Model:
Focus on OWASP Top 10:

A01 – Broken Access Control: Use Laravel Policies and Gates for all role-restricted actions. E.g. only admin can mark status verified; only owner of profile can edit it; expert role required to edit geometry. Enforce at both UI and server. Audit logs for any data changes.
A02 – Cryptographic Failures: Secure all sensitive data (password, API keys); enforce HTTPS. Laravel uses bcrypt for passwords.
A03 – Injection: All DB queries via Eloquent or parameterized queries. Use PostGIS query builder (no raw SQL with user input).
A05 – Security Misconfig: Ensure .env (with DB creds) not committed; use Laravel config caching.
A07 – Authentication: Use Laravel Sanctum or Passport if API for future mobile; ensure CSRF tokens on forms.
A08 – Software Integrity: Lock composer dependencies to known-good versions; use composer audit.
A09 – Logging: Sensitive info (user data, passwords) should not appear in logs. Log security events.
A10 – Exception Handling: Do not expose stack traces to users.
Role-based policies: For example, Moderation access to only unprocessed Reports; Admin to all data. Respect rule hierarchy (R-018–R-024 require owner approval).

UI/UX System
We adopt a Design System approach. Key principles: calm, clear, trustworthy, outdoorsy.

Typography: Hierarchy from display titles to body text. For example: a tall serif/sans for mountain names, functional sans for body. Headlines (trail name), subhead (mountain name). Ensure Indonesian text fits (UTF-8 fonts).
Color: Semantic palette. Primary brand color (green/teal for outdoors). Secondary accent for positive (blue) and attention (orange). Neutral gray scale for UI surfaces. E.g. green = “good”, orange = “caution”. All color combos WCAG AA.
Spacing & Layout: 8px base unit. Use whitespace to separate concerns. Mobile-first: stacked cards; on desktop, multi-column (e.g. map + list side by side). Tailwind CSS utility classes with container queries.
Iconography: Consistent icon set (simple line icons for activities, status).
Components: Buttons, inputs, selects, filters, badges, cards, modals, toast alerts, cards, map controls. Example: a “FitBadge” component showing COCOK/KURANG COCOK with color coding.
Accessible Patterns: Keyboard-navigable, focus ring, alt text on images, ARIA labels.
Motion: Minimal animations (e.g. map marker appear). No distracting animations.
Adaptive Layouts: Based on screen width:

Mobile (<640px): Bottom nav (Home, Search, MyTrip, Kabar, Progress). Drawer filters. Single-column lists.
Tablet (641-1024px): Side nav or hamburger; two-pane (list+map split) on Jelajah.
Desktop (>1024px): Sidebar menu, multi-column workspaces. E.g. Jelajah shows filter sidebar, map, and list in columns.
Map-First Pattern: Where location matters (Jelajah, Trail Detail, Hike), we show a map or map slice prominently. The map is interactive: pins for trails, and clicking shows a preview card. E.g. on Jelajah map, clicking a trail pin slides up a card with summary and “Select” button.

Progressive Disclosure: Don’t overwhelm. E.g. Trail Detail:

Top: Trail name, primary photo, Route Fit label, brief tagline.
Section 1: Key stats (distance, time, ascent, difficulty).
Section 2: Map with elevation profile (toggle).
Section 3: “Why fit?” explanation bullets.
Section 4: Official status & notices, weather and conditions in timeline.
Section 5: Call-to-actions (Plan Trip, Save, Share). Only show advanced details (media gallery, expert notes) on demand (e.g. via tab or accordion).
Example Screens: (UI flow mermaid diagrams included)

Jelajah: Search+Filters

Trail Card Preview

Trail Detail Page

Plan Trip

Preparation Checklist

Check Readiness

Hike Mode Map

Report Completion

Progres Dashboard



Show code
Jelajah (Explore) Page:

Header with location search.
Filter pane (distance slider, difficulty checkboxes, status toggles).
Map (MapLibre) showing nearby trails, with pins colored by Route Fit status (green=COCOK, amber=PERLU, red=KURANG).
List of trails below or beside map (card per trail with name, distance, time, fit label, save/compare buttons).
Example mock:
sql
Copy
┌────────────────────────────────────────────────────────┐
│ [Search: \"Jawa Barat\"]  [Filter icon] [Map/List toggle] │
│────────────────────────────────────────────────────────│
│ [ Sidebar Filters ]   ┌──────────────────────────────┐  │
│ - Distance: [0-50km]  │  Map (Pan/Zoom)            │  │
│ - Difficulty: Easy... │  ● ● ● (near user)          │  │
│ - Status: ...        │  ● (focus)                │  │
│ [Save filter set]    │                              │  │
│                       └──────────────────────────────┘  │
│ ┌ Trail List ─────────────────────────────────────────┐ │
│ │ [Patak Banteng    COCOK] 13km ●+980m ~7h  ✓Save  │ │
│ │ [G. Merapi      PERLU PERSIAPAN] 10km ●+900m ~6h ✓Save  │ │
│ │ ...                                              │ │
│ └────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────┘
Trail Detail Page:

Top section: Trail Name, Mountain, Route Fit badge, key photo.
Below: Stats panel (distance, elevation, time, terrain, difficulty).
Map view with route overlay and optional elevation chart.
“Why this fit?”: bullet list from engine (e.g. “Duration and incline match your goals”).
Current Condition: Cards for Official Status, Weather, Community Reports, each with source/timestamp.
Actions: buttons [Plan Trip], [Save], [Compare].
“Pertimbangkan” Page:

Lists saved trails horizontally (like cards or table columns).
Allows up to 4-5 to compare. Shows key metrics side-by-side (table: distance, ascent, time, difficulty, status, fit).
Allows adding notes per trail and linking to comments.
Example:
less
Copy
┌─────────────────────────────────────────────────────┐
│ Saved for Comparison                                 │
│ [Patak Banteng] [Merapi] [Salak] [Kei]               │
│ Distance   13km    10km    12km    15km             │
│ Elev Gain  +980m   +900m   +950m   +1200m           │
│ Time       ~7h    ~6h     ~6.5h   ~8h               │
│ Fit        COCOK  KURANG  COCOK   PERLU             │
│ Notes: [Your notes here for Patak Banteng...]        │
│                [Your notes here for Merapi...]      │
└─────────────────────────────────────────────────────┘
Trip Page (Perjalanan):

Summary of current trip plan or offer to create one.
Progress steps: Choice (✓), Plan (✓), Prepare (incomplete), Check (pending). Each clickable to jump steps.
E.g. a vertical timeline with step names and status icons.
Hike Mode:

Fullscreen map with route.
HUD: Distance covered/remaining, time, next checkpoint.
Lower panel: speed, altitude (like the Komoot screenshot).
Admin Console:

Dashboard with Data Health panels: “7 trails missing geometry”, “5 statuses stale >30d”, etc.
Buttons to “Review Data” which list the problematic records.
Moderation Queue: List of pending trail reports with previews; moderators can Approve/Reject with one click.
Expert Interface: Map view to digitize a trail or checkpoints, with guided entry of source and evidence.
All with uniform design but distinct accent colors (e.g. admin sections tinted gold, mod tinted orange) to signal role.
Accessibility Checklist:

Keyboard nav for all components.
Proper aria- labels on map controls.
Focus indicators on interactive elements.
Sufficient color contrast.
Resizable text without breaking layout.
Screen-reader announcements for dynamic updates (e.g. “Trail saved” toast).
Responsive layout reflow support (W3C suggests container queries).
Avoid reliance on color alone (use icons/text in badges).
Error states have clear messages and focus on first invalid field.
Data & Golden Trail Plan
A critical early task is acquiring or creating a Golden Trail dataset with real geometry and checkpoints. Current project data shows 36 trails, 0 geometry; 34 checkpoints, 0 coords (Audit Finding). Without actual spatial data, features like maps, profiles, and readiness can’t be validated.

Plan:

Identify one sample mountain in Indonesia (e.g. “Gunung Papandayan”).
Gather official GPX or create geometry (e.g. from OpenStreetMap or publicly available trail data).
Populate: trail line geometry, checkpoint points (to summit, camp), and key attributes.
Add to DB via migration or seed. Include a real source reference (e.g. park service document).
Also seed sample weather (e.g. BMKG API data) and closure status for that trail.
Write tests: e.g. ST_Length(trails.geometry) > 0, ST_Distance(checkpoints.point, summit_point) < 100m, ensure queries return non-null.
Meanwhile, set up an ETL process (could be manual script initially) to ingest additional trails when data becomes available. Include provenance metadata: for any trail record, store source_url, source_date, and who imported it. E.g. columns source (string), source_date (date), verified_by (user_id).

For missing data (expected common), we should mark trails not PUBLISHED if critical data is absent (geometry or checkpoints) – per rule R-014. Non-published can still appear in Jelajah with a special “No geometry” label (and not recommended to PERTIMBANGKAN or PLAN). This ensures we never display incomplete trails as if they were full candidates.

CI/CD & Testing Strategy
CI Baseline (Phase 0) is now green: tests run with Node build before PHPUnit.

Local Verification: Before any commit: run npm ci && npm run build, vendor/bin/pint --test, php artisan test --testsuite=Unit, php artisan test --testsuite=Feature, and one PostGIS test (if possible: e.g. php artisan test --testsuite=Spatial). Ensure all pass.

CI Pipeline: GitHub Actions workflow triggers on push: Install PHP/Node, npm ci, npm run build, run Pint, run PHPUnit (unit & feature), then set up Postgres+PostGIS service and run spatial tests.

Acceptance Gates:

All commands above exit 0.
No lint or StyleCI errors.
Coverage threshold (e.g. 80% code) is a soft target.
On failure, rollback merge.
Rollback: If a push causes regression, revert to last known good commit. Keep hardening branch deployable.

Testing Plan:

Unit Tests: Domain logic (RouteFit engine functions, Eloquent model scopes, controllers). Use SQLite or in-memory for fast feedback except for spatial queries.
Feature Tests: MVC flows (search page, detail page, etc.) with Laravel’s HTTP testing. Mock external APIs (weather).
Spatial Tests: After CI fix, add tests that run on PostgreSQL (e.g. php artisan test --testsuite=Spatial). For example, test that Trail factory creates valid geometry and Trail::nearby(...) finds expected results.
Integration Tests: End-to-end via something like Laravel Dusk or a JavaScript test runner that interacts with a headless browser. For critical paths (login, search, view detail).
Lint & Style: phpstan, phpcs, pint, and ESLint for JS (Vite). All must pass.
Acceptance Gates (Summary):

Check	Expectation
npm ci && npm run build	exit 0 (build artifacts generated)
php artisan test --testsuite=Unit	All 104 unit tests pass
php artisan test --testsuite=Feature	All 791 functional tests (and 1 skipped) pass
php artisan test --testsuite=Spatial	13 spatial tests execute (PostGIS)
vendor/bin/pint --test	no formatting issues
git diff --check	no whitespace/errors
git status	only expected files modified
CI jobs (Linters/Scans)	no critical security alerts (e.g. npm audit)

Each commit must pass these before merge.

Codex/9Router Agent Plan
We treat Codex as a new engineering agent with a strict operating contract. Key tasks before coding:

Skill Inventory: List all Codex/9Router skills available. This includes repository-local skills (.agents/skills), global user skills, and platform-provided (like laravel-best-practices, tailwindcss-development, etc.). The agent should report names, descriptions, and relevance. We then decide which skills to leverage (e.g. testing, security checks, UI pattern skills).

AGENTS.md Draft: Define high-level mandate (per owner’s visions):

Product Core: Mountain planning & readiness (decision support), not just CRUD.
Vision: Personalized hiking platform; references and domain rules.
Source-of-Truth: Code & docs hierarchy (mountain-core rules, PRD, design docs).
DoD Levels: L0–L4 as above.
Evidence Required: static code ✓, runtime ✓, integration ✓, UX (real data) ✓.
Prohibitions: no commit/push without approval (R-018), no Supabase writes without approval (R-019), no silent version changes (R-020), no skipping tests (R-024).
Agent Workflow: research → small scope → implement → test → share evidence → ask for approval for push.
Stop Conditions: user requested to stop or uncertain (explicit).
References: link to .claude rules, DESIGN, PRD, etc.
Sandbox Model: Codex workspace should be workspace-write, allow safe file edits. Approval mode: normal coding tasks = autopilot, but any Git push/merge or Supabase/migrations = owner approval. 9Router config should NOT allow Git push without explicit blessing (approval_policy: manual for push).

Skill Usage: We should identify skills for:

Research: browser.search queries;
UI/UX: possibly use a skill that knows UI patterns;
Laravel: domain modeling, migrations;
Map/Geo: any GIS skill? Likely none.
Testing: skills to auto-generate tests (phpunit, code coverage).
DevOps: CI/CD configuration, Docker (if exists).
Documentation: Possibly a Markdown formatting skill.
No need to port Claude-specific metadata; rely on openAI knowledge + search.
Prioritized Next Steps
Before code changes, we must complete these to move forward:

CI Run #2 Diagnosis: (Current status: failure). Fetch full logs of Run #2 (CI workflow). Identify first failing step (likely Unit or Feature test on Spatial). This clarifies immediate fix needed (maybe missing DB migration or missing PostGIS support). Skill: monitor CI via 9Router or manually using GitHub CLI (gh).

Codex Skill Inventory: Trigger a code execution: ls .agents/skills and query 9Router for installed skills. If the API is not available, manually list known skill docs. The agent should report the skill names and purposes.

Read-Only Audit: In the repo, run find . -maxdepth 2 -type f and review AGENTS.md, docs, app structure to ensure understanding. Document what canonical decisions already exist (PRODUCT.md, design docs) and any conflicts. No code edits yet.

Golden Trail Acquisition: Immediately search public sources (OpenStreetMap, Indonesian government trail data) for one complete trail. If found, prepare a GPX/GeoJSON. If not, define minimal geometry (a mock LINESTRING) and enter into database. Prepare a migration or seed. This is essential to test spatial features and UX.

Bugfixes (post-diagnosis): Once CI failure is diagnosed, commit minimal fixes (e.g. merge conflict in ci.yml or missing npm ci step was added, etc.). Push to trigger new CI run. This completes Phase 0 and unlocks further development.

Owner Approvals Needed: Confirm that:

It’s okay to push the CI fixes now (owner said yes for Phase 0).
We need permission to push Golden Trail data (if it comes from an external source).
Any data from third parties (e.g. weather API) is allowed.
Clarify if any team preferences for hosting (e.g. Heroku, AWS) or budget constraints.
Approval for AGENTS.md content before making it authoritative.
If CI Run #2 remains failing after fix, we halt until we parse logs. Otherwise, continue with data and architecture tasks.

Next Actions Checklist
Inspect CI logs: Use CLI to download run #2 logs from GitHub (or wait for agents).
Run local verification: Confirm npm ci && npm run build && php artisan test now passes. If not, fix locally.
List Codex skills: Execute (coder input) to list .agents/skills folder and query for cx/gpt-5.2-codex skill list.
Draft AGENTS.md: Outline key mandates and link to decisions (we should finalize with owner).
Obtain Golden Trail data: Research Indonesian trail GPX (e.g. “Gunung Papandayan GPX”) and load into DB. Create migration and test query.
Smoke test PostGIS: Write one raw query: SELECT ST_Length(geometry) FROM trails WHERE id = 1; to verify PostGIS is working.
Owner Decisions Needed:

Approval of updated roadmap and priorities.
Confirmation of real trail for Golden data or permission to use example.
Any prohibited actions (e.g. use of AI-coded parts) or preferences.
Definition of release criteria (beyond test pass): e.g. specific UX tests passed.
Citations: AllTrails search (4 trails); Strava social exercise; Komoot routing/nav; Wikiloc community stats.

