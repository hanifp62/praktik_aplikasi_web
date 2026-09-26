# Technical brief: offline field pack for an installable PWA using MapLibre GL JS (state as of 2026-09-25)

All sources were accessed 2026-09-25 unless a different date is given. Browser-support facts come from the published datasets: MDN browser-compat-data 8.1.3 (`__meta.timestamp` 2026-09-24T13:25:51Z, read from https://cdn.jsdelivr.net/npm/@mdn/browser-compat-data/data.json) and web-features 3.40.0 (read from https://cdn.jsdelivr.net/npm/web-features/data.json). Package versions and release dates come from the npm registry (`npm view <pkg> time`, read-only; nothing was installed). The tile-size numbers are this researcher's own measurement on 2026-09-25: 37 requests in total (22 OpenTopoMap tiles, 14 OpenFreeMap tiles and 1 TileJSON), sent with a descriptive User-Agent and 300 ms spacing; method under Q4. Evidence levels follow CLAUDE.md R-023. **Cited Findings** are FACT. **Inferences** are tagged INFERENCE, RECOMMENDATION or HYPOTHESIS. **Gaps** are OPEN ISSUE or PERLU KLARIFIKASI. Several fetch summaries gave wrong years for GitHub release pages (for example "MapLibre 5.11.0 – 2024"), so every release date below comes from npm registry timestamps instead.

## Q1. Storage APIs: Cache Storage vs IndexedDB vs OPFS, quota, persist(), eviction and detection

### Takeaway
IndexedDB, Cache Storage and OPFS all draw on one per-origin quota. When the browser evicts, it deletes the whole origin at once. The design therefore has to assume the pack can disappear, and check for that every time the app opens. Quota is generous on every target: about 60% of disk per origin in Chromium and Safari 17+, and at most 10 GiB best-effort in Firefox. Persistence is only a request. Chromium and Safari decide silently by heuristics, and Firefox desktop shows a prompt. On iOS, data written by script in a Safari tab falls under the 7-day cap. Home Screen web apps are exempt from that cap, but their storage is separate from Safari's, so a pack has to be downloaded inside the installed app.

### Cited Findings
- **Chrome/Chromium quota:** best-effort and persistent both allow up to 60% of total disk per origin. The browser uses at most 80% of disk across all origins — [MDN, Storage quotas and eviction criteria (last modified 2026-01-05)](https://developer.mozilla.org/en-US/docs/Web/API/Storage_API/Storage_quotas_and_eviction_criteria)
- **Firefox quota:** best-effort is the smaller of 10% of disk or 10 GiB (a group limit per eTLD+1). Persistent is up to 50% of disk, capped at 8 TiB — [MDN](https://developer.mozilla.org/en-US/docs/Web/API/Storage_API/Storage_quotas_and_eviction_criteria)
- **Safari/WebKit quota (macOS 14 / iOS 17 and later):**
  - Browser apps get about 60% of disk per origin and 80% overall.
  - Non-browser apps that embed WebView get about 15% per origin and 20% overall.
  - Cross-origin frames get about 1/10 of the parent's quota.
  - Older Safari started at 1 GiB and prompted the user to raise it.
  - Sources: [MDN](https://developer.mozilla.org/en-US/docs/Web/API/Storage_API/Storage_quotas_and_eviction_criteria); [WebKit blog, "Updates to Storage Policy" (2023-08-10, Safari 17.0)](https://webkit.org/blog/14403/updates-to-storage-policy/)
- **Home Screen Web Apps (standalone)** get "the same origin quota and overall quota as when it is opened in a browser app" — [WebKit blog 2023](https://webkit.org/blog/14403/updates-to-storage-policy/)
- **Shared quota:** IndexedDB, Cache API, OPFS and WebAssembly code caching all count against the same quota. Web Storage is capped separately at 10 MiB (5 MiB localStorage plus 5 MiB sessionStorage) — [MDN](https://developer.mozilla.org/en-US/docs/Web/API/Storage_API/Storage_quotas_and_eviction_criteria)
- **Over-quota error:** exceeding quota with IndexedDB, Cache or OPFS "fails with a `QuotaExceededError` exception" — [MDN](https://developer.mozilla.org/en-US/docs/Web/API/Storage_API/Storage_quotas_and_eviction_criteria)
- **LRU eviction:** under storage pressure, or when the browser-wide maximum is exceeded, the least-recently-used best-effort origins are evicted. Persistent origins are skipped — [MDN](https://developer.mozilla.org/en-US/docs/Web/API/Storage_API/Storage_quotas_and_eviction_criteria)
- **Whole-origin eviction:** "When an origin's data is evicted by the browser, all of its data, not parts of it, is deleted at the same time. If the origin had stored data by using IndexedDB and the Cache API for example, then both types of data are deleted." — [MDN](https://developer.mozilla.org/en-US/docs/Web/API/Storage_API/Storage_quotas_and_eviction_criteria)
- **WebKit eviction exclusions:** WebKit evicts by origin with an LRU policy. An origin can be "excluded from eviction if it has active page at the time of eviction, or its storage is in persistent mode" — [WebKit blog 2023](https://webkit.org/blog/14403/updates-to-storage-policy/)
- **Safari proactive 7-day eviction:** with cross-site tracking prevention on, "If an origin has no user interaction, such as click or tap, in the last seven days of browser use, its data created from script will be deleted." Home Screen web apps are not subject to this — [MDN](https://developer.mozilla.org/en-US/docs/Web/API/Storage_API/Storage_quotas_and_eviction_criteria)
- **What the 7-day cap covers** (primary source, 2020-03-24): "Indexed DB, LocalStorage, Media keys, SessionStorage, Service Worker registrations and cache". "Web applications added to the home screen are not part of Safari and thus have their own counter of days of use." Deletion of first-party data in such apps "would be considered a serious bug" — [WebKit blog, "Full Third-Party Cookie Blocking and More"](https://webkit.org/blog/10218/full-third-party-cookie-blocking-and-more/)
- **Home Screen storage isolation:** Home Screen web apps do not share storage with Safari. WebKit engineer Brent Fulgham (2022-02-01): "The current behavior (on Apple platforms) is by design. Home Screen apps are created as isolated entities without shared state with the browser." The bug is still NEW (reported 2018-01-19) — [WebKit bug 181849](https://bugs.webkit.org/show_bug.cgi?id=181849)
- **iOS/iPadOS 26 install behaviour:** "By default, every website added to the Home Screen opens as a web app" (users can turn off "Open as Web App"), and "there are now zero requirements for 'installability' in Safari" — [WebKit, "WebKit Features in Safari 26.0" (2025-09-15)](https://webkit.org/blog/17333/webkit-features-in-safari-26-0/)
- **persist() in Safari:** WebKit "grants a request based on heuristics like whether the website is opened as a Home Screen Web App". "Starting in Safari 17.0 … the Storage API is fully supported" — [WebKit blog 2023](https://webkit.org/blog/14403/updates-to-storage-policy/)
- **persist() in Chrome:** grants depend on site engagement, whether the site is installed or bookmarked, and notification permission. "If a site is considered important, the persistent storage permission is automatically granted, otherwise it is silently denied." A denied site can ask again later. Persistent data is deleted "only … if the user chooses to remove it using their site settings." The article is from 2020-05-12, so the heuristics may have changed — [web.dev, Persistent storage](https://web.dev/articles/persistent-storage)
- **persist() in Firefox:** Firefox shows a UI popup. Safari and most Chromium browsers "automatically approve or deny … and do not show any prompts" — [MDN](https://developer.mozilla.org/en-US/docs/Web/API/Storage_API/Storage_quotas_and_eviction_criteria)
- **Anecdotal 2025 Chrome test** (secondary; one developer, Chrome version and platform not stated): installing the app, bookmarking it and granting notifications each left persistence "not granted", with no dialog shown. `permissions.query` reported `state: 'prompt'` — [desgrange.net (2025-10-06)](https://blog.desgrange.net/post/2025/10/06/how-persistent-storage-permission-chrome.html)
- **Mobile support by browser version** (from [BCD 8.1.3](https://github.com/mdn/browser-compat-data), resolved data file above):

  | Feature | Chrome Android | Samsung Internet | Firefox Android | Safari iOS |
  |---|---|---|---|---|
  | `StorageManager.estimate()` | 61 | 8.0 | 57 | 17 |
  | `persist()` / `persisted()` | 55 | 6.0 | 57 | 15.2 |
  | OPFS `getDirectory()` | 109 | 21.0 | 111 | 15.2 |
  | `createSyncAccessHandle()` (worker) | 109 | 21.0 | 111 | 15.2 |
  | `FileSystemFileHandle.createWritable()` | 109 | 21.0 | 111 | 26 |
  | Cache API | 40 | 4.0 | 41 | 11.3 |
  | Storage Buckets (`navigator.storageBuckets`) | 122 | 26.0 | no | no |

- **Baseline status** (web-features 3.40.0, [data](https://cdn.jsdelivr.net/npm/web-features/data.json), [repo](https://github.com/web-platform-dx/web-features)):
  - `storage-manager`: Baseline widely available (low 2023-09-18, high 2026-03-18)
  - `origin-private-file-system`: widely available (low 2023-03-27, high 2025-09-27)
  - `indexeddb`: widely available (high 2024-03-20)
  - `web-locks`: widely available (high 2024-09-14)
  - `compression-streams`: widely available (high 2025-11-09)
  - `storage-buckets`: not Baseline (Chromium 122 only)
- **Storage Buckets:** a site can create multiple buckets "where the browser may choose to delete each bucket independently of other buckets." With `persisted: true`, "Storage will not be cleared except by explicit user action". `durability` is `'strict'` or `'relaxed'`. The article says Chromium 122 shipped only IndexedDB per bucket — [Chrome for Developers, Storage Buckets](https://developer.chrome.com/blog/storage-buckets). **Conflict:** BCD now lists `StorageBucket.caches`, `getDirectory`, `persist`, `expires` and `setExpires` as supported from Chrome 122 (all marked experimental) — [BCD api/StorageBucket.json](https://raw.githubusercontent.com/mdn/browser-compat-data/main/api/StorageBucket.json)
- **Real PWA storage split:** Trail Pocket stores large package files in OPFS "where supported", and "IndexedDB stores package metadata, download state and app records". Cache API is its fallback — [Trail-Pocket-Max README](https://github.com/appbuilderlee2/Trail-Pocket-Max)

### Inferences
- **RECOMMENDATION (storage layout):**
  - **IndexedDB, one database per signed-in user** (for example `memount-offline-<hash(userId)>`). It holds the pack manifest (packId, packVersion, createdAt, dataAsOf per source, byte counts, status), the stable trail data (GeoJSON LineString, checkpoints with distances, elevation profile) and the user's plan and checklist. Any dynamic "last known" snapshot goes in a separate store with `fetchedAt` and `source`.
  - **Tiles, one of two options:**
    - (a) a single per-trip PMTiles file in OPFS, read through `FileSource`;
    - (b) per-tile responses in a pack-specific Cache Storage cache such as `pack-<packId>-v<version>`.
  - All of this lives in the one shared quota described above.
- **INFERENCE (detection):** eviction removes the whole origin at once (MDN). So a missing IndexedDB manifest on app start is a reliable "pack evicted" signal. A partial pack, such as tiles gone while the manifest remains, is not produced by browser eviction. It comes from failed downloads, app bugs or manual partial clearing, so an integrity check (Q2) is still needed. No eviction event was found in the sources consulted; see Gaps.
- **RECOMMENDATION (persistence):**
  - Call `navigator.storage.persist()` inside the user's "download pack" click handler, never on page load, and store the result.
  - If `persisted()` is false, show honest copy along the lines of "the browser may delete this pack when storage runs low".
  - The design must work without persistence, because Chromium may deny it silently, per the 2020 web.dev article and the 2025 anecdote above.
- **RECOMMENDATION (capacity):** call `navigator.storage.estimate()` before downloading and require `quota − usage` ≥ pack bytes × a safety margin. The margin is an assumption; see Gaps. `estimate()` exists only from Safari iOS 17 onwards, so older iOS falls back to catching `QuotaExceededError`.
- **RECOMMENDATION (iOS):** tell iPhone users to "Add to Home Screen" and then download the pack inside the installed app. A pack downloaded in a Safari tab is invisible to the Home Screen app (WebKit bug 181849) and is subject to the 7-day cap. Since iOS 26, any site added to the Home Screen opens as a web app by default.
- **INFERENCE (per-pack isolation on Chromium):** Storage Buckets (`expires`, `persist`, per-bucket eviction) fit per-trip packs well, but are Chromium-only and experimental. Use them only as progressive enhancement behind feature detection, with the same code paths working without them.

### Gaps
- **OPEN ISSUE: Firefox for Android persist().** A search snippet pointing to a Mozilla support thread (questions/1303061) says Android shows no prompt and returns `false`. The thread would not load, so this is unverified. Bugzilla 1457973 was not checked.
- **OPEN ISSUE: Samsung Internet eviction.** No primary source found on Samsung Internet-specific eviction, or on whether Smart Anti-Tracking affects first-party IndexedDB, Cache or OPFS. The Samsung Internet developer blog post on v17 returned HTTP 403. A search snippet claims v17 makes "all client-side cookies expire after 7 days"; that is about cookies only and is unverified.
- **OPEN ISSUE: storage sharing on Android installs.** Whether Chrome Android installed PWAs (WebAPK) share storage with the Chrome tab was not verified this session. HYPOTHESIS: they share one profile, unlike iOS.
- **OPEN ISSUE: no eviction event.** No eviction event or callback was found in any source read. The Storage Standard itself was not fetched.
- **OPEN ISSUE: quota overhead.** How much quota overhead per stored response Cache Storage or IndexedDB adds (for example, Chrome padding opaque responses) was not verified. CORS tile responses are not opaque, but a safety margin is still prudent.
- **OPEN ISSUE: eviction frequency.** No field data exists on how often eviction happens on low-storage Android phones.

## Q2. Service worker strategy for a route-scoped pack: user-triggered download, versioning, integrity, update, deletion; Workbox vs hand-written; Background Sync, Periodic Sync, Background Fetch

### Takeaway
The standard pattern is the offline cookbook's "on user interaction": the page itself fetches the pack and writes it into pack-specific stores. The service worker then only needs to do three things:
- keep the versioned app shell precached;
- when a navigation fails, serve an offline shell document that contains no content;
- optionally serve tile URLs from the pack cache.

Background Sync, Periodic Background Sync and Background Fetch are Chromium-only and not Baseline, so every flow needs a foreground fallback. Workbox 7.4.1 is maintained (owned by Chrome's Aurora team, MIT licence) and its runtime modules are small. Its Vite integration, vite-plugin-pwa, pulls in a 37-dependency build tree. A hand-written service worker adds no dependencies.

### Cited Findings
- **Offline cookbook, "On user interaction":** "Give the user a 'Read later' or 'Save for offline' button. When it's clicked, fetch what you need from the network and pop it in the cache." The example calls `caches.open('mysite-article-' + id)` and then `cache.addAll(urls)` from the page. "The Cache API is available from pages and service workers" — [web.dev, The Offline Cookbook (page date shown 2014-12-09)](https://web.dev/articles/offline-cookbook)
- **addAll is all-or-nothing:** "If any of the resources fail to be fetched, the `cache.addAll()` call rejects." The article recommends deleting old caches in `activate` — [web.dev offline cookbook](https://web.dev/articles/offline-cookbook)
- **Workbox status:** "From now on, Chrome's Aurora team will be the new owners of Workbox." Licence is MIT — [GoogleChrome/workbox README](https://github.com/GoogleChrome/workbox). The latest release is 7.4.1 (2026-05-04). Earlier: 7.4.0 on 2025-11-19, 7.3.0 on 2024-10-29 — [npm workbox-precaching](https://www.npmjs.com/package/workbox-precaching)
- **Dependency footprint** (npm registry, 2026-09-25):
  - `workbox-precaching` 7.4.1 depends on workbox-core, workbox-routing and workbox-strategies.
  - `workbox-routing`, `workbox-strategies` and `workbox-range-requests` each depend only on workbox-core.
  - `workbox-window` depends on workbox-core and @types/trusted-types.
  - `workbox-build` 7.4.1 has 37 direct dependencies, including Babel, Rollup plugins and ajv.
  - `vite-plugin-pwa` 1.3.0 depends on debug, pretty-bytes, tinyglobby, workbox-build and workbox-window.
  - `serwist` 9.5.12 (a Workbox fork) depends on idb and @serwist/utils, and `@serwist/vite` 9.5.12 has 6 dependencies.
  - Sources: [npm workbox-build](https://www.npmjs.com/package/workbox-build), [npm vite-plugin-pwa](https://www.npmjs.com/package/vite-plugin-pwa), [npm serwist](https://www.npmjs.com/package/serwist)
- **Range requests:** a cached full response does not satisfy a request that carries a `Range` header. `RangeRequestsPlugin` / `createPartialResponse()` slice the cached response into 206 Partial Content, or return 416 when the range can't be satisfied — [Workbox, workbox-range-requests](https://developer.chrome.com/docs/workbox/modules/workbox-range-requests)
- **Background Sync:** "Limited availability", not Baseline, secure context only. Usage is `registration.sync.register(tag)` from the page plus a `sync` event in the service worker — [MDN, Background Synchronization API (last modified 2024-04-22)](https://developer.mozilla.org/en-US/docs/Web/API/Background_Synchronization_API). BCD: `SyncManager` in Chrome Android 49 and Samsung 5.0; not in Android WebView, Firefox Android or Safari iOS — [BCD](https://github.com/mdn/browser-compat-data). web-features `background-sync`: baseline=false — [web-features](https://cdn.jsdelivr.net/npm/web-features/data.json)
- **Periodic Background Sync:**
  - Runs only after a person "has installed it on their device, and has launched it as a distinct application".
  - "Chrome is using a site engagement score to determine if and how often" syncs happen.
  - Syncs run only "on a network that the device has previously connected to".
  - "The timing of synchronizations are not controlled by developers."
  - The permission is named `periodic-background-sync`.
  - Source: [Chrome for Developers (updated 2025-08-19)](https://developer.chrome.com/docs/capabilities/periodic-background-sync)
  - Support: Chrome Android 80 and Samsung 13.0; not Firefox or Safari (BCD). web-features baseline=false.
- **Background Fetch:** "If the user closes pages to your site after step 1, that's ok, the download will continue." The browser shows progress. Results go into Cache Storage in `backgroundfetchsuccess`. The browser rejects the fetch "if the user has opted out of downloads" — [Chrome for Developers, Background Fetch (2018-12-03)](https://developer.chrome.com/blog/background-fetch). Support: Chrome Android 74 and Samsung 11.0; not Firefox, Safari or WebView (BCD). web-features baseline=false.
- **Other Baseline-relevant service-worker features** (web-features):
  - `js-modules-service-workers` (`type: 'module'` service workers): Baseline newly available since 2026-01-13, when Firefox 147 shipped it.
  - `service-workers-static-routes` (Static Routing API): Chrome 123 and Safari 27; not Baseline.
  - `web-locks`: widely available.
  - Source: [web-features data](https://cdn.jsdelivr.net/npm/web-features/data.json)
- **Field practice:** Trail Pocket's service worker "handles resumable HTTP Range downloads", and the app validates downloads before installing them, with "corrupted package detection and repair/re-download support" — [Trail-Pocket-Max README](https://github.com/appbuilderlee2/Trail-Pocket-Max)
- **Vite asset manifest:** `build.manifest` generates "a mapping of non-hashed asset filenames to their hashed versions", written to `.vite/manifest.json` in the output directory. The page describes Vite v8.3.1 — [Vite build options](https://vite.dev/config/build-options)

### Inferences
- **RECOMMENDATION (pack lifecycle):**
  - States: `none → downloading (staging) → verifying → ready(vN) → stale (the server has vN+1) → downloading vN+1 (staging) → verifying → ready(vN+1) → vN deleted`, plus `deleted` and `failed`.
  - Never delete vN before vN+1 has verified, so a failed refresh at the trailhead still leaves a usable pack.
- **RECOMMENDATION (manifest from the server):**
  - Pack identity: `packId`, `packVersion` (a content hash of the stable inputs), `generatedAt`, `dataAsOf` per stable dataset.
  - Resources: `[ {url, bytes, sha256} ]`.
  - Offline display: attribution strings (Q5) and an optional `suggestedExpiry`.
  - The page downloads with limited concurrency (for example 4–6 parallel requests) and retries. It shows progress and writes to staging.
  - Web Locks (Baseline) stop two tabs downloading the same pack.
- **INFERENCE (addAll):** `cache.addAll()` is atomic but reports no progress. With hundreds of tiles on a slow network, one failure discards everything. Batched `cache.put()` calls with per-item retries, followed by an explicit verification pass, suit field networks better.
- **RECOMMENDATION (integrity):**
  - Per-tile packs: check every manifest URL with `cache.match` and compare body sizes.
  - Single-file PMTiles: check the file size, then compute SHA-256 with `crypto.subtle.digest`. Note: `digest()` takes a whole buffer, which is fine for tens of MiB but costs memory on low-RAM phones. This is an INFERENCE, not verified this session.
  - Mark `ready` only after verification passes.
- **RECOMMENDATION (service worker boundaries):**
  - Name app-shell caches `app-shell-<buildHash>`, and have `activate` delete only obsolete `app-shell-*` caches, never `pack-*`.
  - Keep dynamic endpoints (official status, weather, community reports) network-only in the service worker.
  - "Last known" values live in IndexedDB with `fetchedAt` and `source`. They are always shown as "last known as of …" (R-012, R-013) and never re-served as HTTP responses.
- **RECOMMENDATION (background APIs):** a read-only pack needs no Background Sync. Checklist edits can be local-first with an outbox that is replayed when the app is next in the foreground and online. Background Fetch can be optional progressive enhancement for large packs on Chromium only. Periodic Sync is unsuitable, because it needs install plus engagement and its timing is unpredictable.
- **INFERENCE (dependency cost, for the owner's approval decision):**

  | Option | Runtime cost | Build-time cost | Notes |
  |---|---|---|---|
  | Hand-written service worker + small build script that reads Vite's manifest | 0 packages | 0 packages (custom script) | Full control; more code to test; must implement precache versioning yourself |
  | Workbox runtime modules (precaching/routing/strategies/range-requests) | 4–5 MIT packages, all on workbox-core | Needs a separate service-worker bundle step | Maintained (7.4.1, May 2026) |
  | vite-plugin-pwa 1.3.0 | workbox-window (+core) | workbox-build (37 deps) + 4 others | Generates the precache manifest automatically |
  | Serwist 9.5.12 | serwist (+idb, @serwist/utils) | @serwist/vite (6 deps) | Workbox fork |

### Gaps
- **OPEN ISSUE:** the date Workbox moved to the Aurora team, and its long-term roadmap, were not found.
- **OPEN ISSUE:** how Background Fetch payloads count against quota, and how Chrome shows them on Samsung devices, was not researched.
- **OPEN ISSUE:** no primary source was found on streaming hashes in Web Crypto. The memory behaviour of `digest()` on 30–40 MiB inputs on low-end Android is unmeasured.

## Q3. Inertia.js 3 + React: offline behaviour, serving an offline trail view, and pitfalls (409, CSRF)

### Takeaway
Inertia v3 visits are XHR requests from its built-in XHR client, with `X-Inertia` headers. The client accepts only responses that carry `X-Inertia: true` and a JSON page object. Anything else fires a cancelable `httpException` and shows an error modal. Network failures fire a cancelable `networkError`. Offline therefore has to be designed on purpose. The cleanest option is a separate offline document route, outside Inertia, that the service worker serves when a navigation fails, plus a `networkError` handler that sends the user there. Having the service worker fabricate Inertia page objects works in principle, but it ties the service worker to Inertia's protocol internals and to asset versions.

### Cited Findings
- **Request headers:** `X-Inertia` ("Set to true…"), `X-Inertia-Version` ("The current asset version…") and `X-Requested-With: XMLHttpRequest`, plus `X-Inertia-Partial-*` for partial reloads — [Inertia docs, The Protocol (v3)](https://inertiajs.com/the-protocol)
- **Initial page load:** "The very first request to an Inertia app is just a regular, full-page browser request". The page object sits in a `<script type="application/json">` element — [Inertia protocol](https://inertiajs.com/the-protocol)
- **Response contract:** the server must send `X-Inertia: true`, `Vary: X-Inertia` and `Content-Type: application/json`. "Any other response, such as an HTML document, plain JSON, or an error page, is treated as an exception. The client fires a cancelable HTTP exception event and, unless you cancel it, shows an error modal" — [Inertia v3, The Protocol](https://inertiajs.com/docs/v3/core-concepts/the-protocol)
- **Asset version mismatch:** the server replies "409 Conflict with the target URL in an X-Inertia-Location header", and the client performs a full-page visit — [Inertia protocol](https://inertiajs.com/the-protocol). External redirects also use 409 + `X-Inertia-Location`. A 302 after PUT, PATCH or DELETE becomes 303 — [Inertia v3 protocol](https://inertiajs.com/docs/v3/core-concepts/the-protocol)
- **v3 HTTP client and React requirement:** "Axios has been replaced with a built-in XHR client". "The React adapter now requires React 19" — [Inertia v3 upgrade guide](https://inertiajs.com/docs/v3/getting-started/upgrade-guide). The upgrade guide says nothing about service workers or offline (checked 2026-09-25).
- **Release dates:** `@inertiajs/react` 3.0.0 was published 2026-03-25. The latest is 3.7.1 (2026-09-11), with dependencies `@inertiajs/core`, `es-toolkit` and `laravel-precognition` — [npm @inertiajs/react](https://www.npmjs.com/package/@inertiajs/react)
- **Router events:** `networkError` is cancelable and "fires on unexpected XHR errors such as network interruptions". `httpException` is cancelable and fires when "a non-Inertia response is received". Other events include `navigate`, `location` (cancelable, before full-page navigation) and `prefetching`/`prefetched` — [Inertia v3, Events](https://inertiajs.com/docs/v3/advanced/events)
- **CSRF:** "Inertia's HTTP client automatically checks for the existence of an `XSRF-TOKEN` cookie and, when present, includes the token in an `X-XSRF-TOKEN` header for every request it makes". A 419 is not a valid Inertia response, so it "is shown in a modal". The docs recommend redirecting back with a flash message — [Inertia v3, CSRF protection](https://inertiajs.com/docs/v3/security/csrf-protection)
- **History encryption:** it uses `window.crypto.subtle`, with the key in sessionStorage, and is HTTPS-only. Enable it globally, per request with `Inertia::encryptHistory()`, or with the `EncryptHistory` middleware. `Inertia::clearHistory()` or `router.clearHistory()` rotate the key. The docs recommend clearing history at logout — [Inertia v3, History encryption](https://inertiajs.com/docs/v3/security/history-encryption)

### Inferences
- **INFERENCE:** Inertia visits are XHR, not navigations, so a service-worker rule of "documents always from network" never sees them. Offline, a visit fails with `networkError`. Without a handler, the user gets either a thrown error or a stuck page.
- **RECOMMENDATION (option A, preferred): a dedicated offline shell route** (for example `/luring`, or `/luring/{packId}` resolved on the client).
  - It is a static document with no server-rendered user data, plus its own small Vite entry (not an Inertia page) that renders packs from IndexedDB/OPFS.
  - The service worker precaches this document and its hashed JS/CSS (listed from Vite's manifest) as part of the `app-shell-<buildHash>` version.
  - The navigation handler stays network-first for every document. Only on network failure does it return the offline shell, which shows only locally stored packs and their "as of" labels.
  - In the Inertia app, `router.on('networkError', e => { e.preventDefault(); … })` can offer "Open the offline pack", which does a full navigation to the offline shell.
  - This keeps the current policy intact: no server content is cached. The only cached document is a content-free shell.
- **RECOMMENDATION (option B, not recommended): the service worker answers Inertia XHRs** with synthesized JSON (`X-Inertia: true`, `Vary: X-Inertia`, `{component, props, url, version}`). Downsides:
  - The service worker is coupled to Inertia's protocol and page component names.
  - Cached props could be mistaken for server-authoritative data.
  - Synthesized pages enter browser history.
  - A service worker built for an older asset version could serve a component name that no longer exists after a deploy.
- **INFERENCE (409 after a deploy):** after reconnecting, a client with a stale bundle gets 409 → a full reload → the document is fetched from the network (policy unchanged). The offline shell and its assets must be precached together in the same service-worker install step. That way the shell's JS can never be from a different build than the shell HTML.
- **RECOMMENDATION (queued writes and CSRF):**
  - Do not replay writes from the service worker with stored tokens.
  - Replay the outbox from the page when it is visible, online and has a live session, so the current `XSRF-TOKEN` cookie is used.
  - Handle 419 and 401 by asking the user to sign in again.
  - Send a client-generated idempotency key per queued action so retries can't duplicate.
  - A multi-day trip can outlive the Laravel session lifetime; this is a HYPOTHESIS to confirm against the app's session config.

### Gaps
- **OPEN ISSUE:** no Inertia documentation, issue or discussion about service workers or offline was found in the v3 docs checked (upgrade guide, protocol, events).
- **OPEN ISSUE:** how Inertia's XHR client behaves when a service worker returns a synthesized response is untested.

## Q4. Offline map tiles with MapLibre GL JS: PMTiles, corridor extraction, basemaps, sizes, glyphs/sprites, addProtocol, terrain

### Takeaway
MapLibre GL JS has no persistent tile cache, and its maintainer says to use `addProtocol` for offline (December 2025). MapLibre 4.7 has had the promise-based `addProtocol` since 4.0. A pack can therefore do either of these:
- register the `pmtiles` protocol and read a per-trip PMTiles file from OPFS through `FileSource`;
- register a custom protocol that reads XYZ tiles from Cache Storage, which adds no dependency.

Sizes, measured 2026-09-25 on an 11×11 km box over Gede-Pangrango:
- z10–z16 is 541 tiles, about 14.4 MiB of OpenTopoMap PNG.
- OpenFreeMap vector tiles for z10–z14 are about 2 MiB decoded.
- A modelled 20 km trail corridor with a ±1 km buffer needs 268 tiles to z16 (about 7.6 MiB raster), or 836 tiles to z17 (about 20.7 MiB).

Glyphs and sprites must be packed too. The alternatives are rendering labels as HTML markers or using MapLibre ≥5.11's local-font fallback. MapLibre is now at 6.11.2, two majors past 4.7.

### Cited Findings
- **Versions** (npm registry):
  - maplibre-gl 4.7.0: 2024-09-06
  - 4.7.1: 2024-09-23
  - 5.0.0: 2024-12-31
  - 5.11.0: 2025-11-03
  - 6.0.0: 2026-07-22
  - `latest` dist-tag is 6.11.2 (registry last modified 2026-09-24)
  - Source: [npm maplibre-gl](https://www.npmjs.com/package/maplibre-gl)
- **v4.0.0 breaking changes:** "Changes `addProtocol` to be promise-based without the usage of callbacks and cancelable". v4.0.0 also added the ability to import a script in the worker thread and call `addProtocol`/`removeProtocol` there — [MapLibre v4.0.0 release](https://github.com/maplibre/maplibre-gl-js/releases/tag/v4.0.0)
- **addProtocol API:**
  - Signature: `addProtocol(customProtocol: string, loadFn: AddProtocolAction)`. The handler gets `params` and an `AbortController` and returns `Promise<GetResourceResponse>`.
  - "This will happen in the main thread, and workers might call it if they don't know how to handle the protocol."
  - Documented examples cover tiles (ArrayBuffer) and images (encoded bytes or ImageBitmap).
  - Source: [MapLibre docs, addProtocol](https://maplibre.org/maplibre-gl-js/docs/API/functions/addProtocol/)
- **v6.0.0 breaking changes** include:
  - ESM-only distribution;
  - WebGL1 removed, so WebGL2 is required;
  - `Map` composes `Camera` instead of extending it;
  - style-spec v25;
  - `GeoJSONSource.setData` return value removed.
  - Also: "ESM build loads its worker as a real URL, so `worker-src blob:` is no longer required".
  - Source: [MapLibre CHANGELOG](https://github.com/maplibre/maplibre-gl-js/blob/main/CHANGELOG.md)
- **v5.11.0 local fonts:** "If the stylesheet lacks a `glyphs` property … render all text using local or system fonts". Also: "If a required glyph PBF is unavailable … try to render it locally instead of crashing" — [MapLibre v5.11.0 release](https://github.com/maplibre/maplibre-gl-js/releases/tag/v5.11.0)
- **Maintainers on caching:** MapLibre GL JS caches tiles in memory and relies on the browser's HTTP cache. Asked about a persistent cache: "No, there's not plans to support this", because GL JS "is mostly intended for web where offline is less of a concern". The advice is to use `addProtocol` — [maplibre-gl-js discussion #6910 (Dec 2025, maintainer HarelM)](https://github.com/maplibre/maplibre-gl-js/discussions/6910)
- **Style spec:**
  - `glyphs` is "a URL template for loading signed-distance-field glyph sets in PBF format" with `{fontstack}` and `{range}` (256 code points) — [Style spec, glyphs](https://maplibre.org/maplibre-style-spec/glyphs/)
  - Source `maxzoom`: "Data from tiles at the maxzoom are used when displaying the map at higher zoom levels".
  - `bounds`: "no tiles outside of the given bounds are requested".
  - Raster `tileSize` defaults to 512.
  - raster-dem `encoding` is `terrarium`, `mapbox` or `custom`.
  - Source for the last four: [Style spec, sources](https://maplibre.org/maplibre-style-spec/sources/)
- **PMTiles with MapLibre:** `let protocol = new Protocol(); maplibregl.addProtocol("pmtiles", protocol.tile);`, with source URLs of the form `pmtiles://…` for `vector`, `raster` and `raster-dem` (`encoding: terrarium`) — [Protomaps docs, PMTiles for MapLibre](https://docs.protomaps.com/pmtiles/maplibre)
- **FileSource:** `new FileSource(file: File)` uses "the Browser's File API", with `getBytes(offset, length)` — [pmtiles typedoc, FileSource](https://pmtiles.io/typedoc/classes/FileSource.html)
- **pmtiles package:** 4.5.0 (2026-08-10); its only dependency is `fflate`; unpacked size 380,312 bytes (all builds) — [npm pmtiles](https://www.npmjs.com/package/pmtiles)
- **pmtiles extract:**
  - Flags: `--bbox=MIN_LON,MIN_LAT,MAX_LON,MAX_LAT`, `--region=REGION.geojson` (Polygon, MultiPolygon, Feature, FeatureCollection), `--maxzoom`, `--minzoom`, `--download-threads`, `--overfetch`.
  - "The source archive may be local or remote" (read with HTTP range requests).
  - `pmtiles verify` checks archive ordering and header correctness.
  - Source: [Protomaps docs, PMTiles CLI](https://docs.protomaps.com/pmtiles/cli)
- **Maintainer advice on offline PMTiles** (Aug 2025): "For the best interactive use cases you should store a full subpyramid up to the zoom 0 tile". The efficient extract algorithm in go-pmtiles is "difficult to port to JavaScript because it depends on Roaring Bitmaps" — [PMTiles discussion #588](https://github.com/protomaps/PMTiles/discussions/588)
- **Protomaps basemap:**
  - Daily builds at maps.protomaps.com/builds; planet z0–15 is "roughly 120 gigabytes".
  - "each additional zoom level roughly doubles the size".
  - "hotlinking to these downloads are discouraged. Instead, you should copy the tileset to your own Cloud Storage".
  - Licence is ODbL as a "Produced Work".
  - Source: [Protomaps docs, Downloads](https://docs.protomaps.com/basemaps/downloads)
  - The `@protomaps/basemaps` style package is 5.7.2 with no runtime dependencies listed — [npm](https://www.npmjs.com/package/@protomaps/basemaps)
  - Fonts and sprites can be downloaded for self-hosting or offline use. Fonts are under the SIL Open Font License (search summary; repository not fetched) — [protomaps/basemaps-assets](https://github.com/protomaps/basemaps-assets)
- **OpenFreeMap:** the planet TileJSON (fetched 2026-09-25) gives `maxzoom=14` and tiles at `https://tiles.openfreemap.org/planet/20260913_164504_pt/{z}/{x}/{y}.pbf`. Responses are gzip with `cache-control: public, max-age=315360000` — [tiles.openfreemap.org/planet](https://tiles.openfreemap.org/planet). "We also provide weekly full planet downloads both in Btrfs and MBTiles formats" — [openfreemap.org](https://openfreemap.org/)
- **OpenTopoMap tile responses** (measured 2026-09-25): `image/png` with `cache-control: max-age=604800` (7 days) — [a.tile.opentopomap.org](https://a.tile.opentopomap.org/10/816/531.png)
- **Measurement** (this researcher, 2026-09-25).
  - Setup: bbox `[106.93, -6.83, 107.03, -6.73]`, about 11×11 km over the Gede-Pangrango massif, West Java. Up to 4 evenly spaced tiles sampled per zoom. Sizes are body bytes: PNG for OpenTopoMap; decoded MVT for OpenFreeMap, with gzip wire size also recorded. Sources: [OpenTopoMap tiles](https://a.tile.opentopomap.org/), [OpenFreeMap TileJSON](https://tiles.openfreemap.org/planet).

  | Zoom | Tiles in bbox | OpenTopoMap avg bytes (n) | OpenTopoMap est. total | OpenFreeMap avg decoded bytes (n) |
  |---|---|---|---|---|
  | z10 | 1 | 49,298 (1) | 0.05 MiB | 74,951 (1) |
  | z11 | 1 | 55,340 (1) | 0.05 MiB | 70,787 (1) |
  | z12 | 4 | 55,146 (4) | 0.21 MiB | 40,421 (4) |
  | z13 | 9 | 50,187 (4) | 0.43 MiB | 18,354 (4) |
  | z14 | 36 | 46,291 (4) | 1.59 MiB | 44,372 (4; one outlier of 168,141) |
  | z15 | 110 | 32,102 (4) | 3.37 MiB | not served (maxzoom 14) |
  | z16 | 380 | 24,151 (4) | 8.75 MiB | not served (maxzoom 14) |
  | Total | 541 | — | ≈14.4 MiB | ≈1.97 MiB (z10–14) |

  OpenFreeMap gzip wire sizes were about 63–79% of decoded size (for example z10: 52,218 bytes on the wire vs 74,951 decoded; the z14 outlier: 113,090 vs 168,141).
- **Plugin precedent:** makinacorpus/maplibre-offline-pmtiles stores PMTiles in OPFS and supports MVT and MLT. It suggests "keep files between 10MB and 100MB" for reliable mobile downloads. It "does not handle sprite and font caching automatically" (MIT, MapLibre as a peer dependency) — [makinacorpus/maplibre-offline-pmtiles](https://github.com/makinacorpus/maplibre-offline-pmtiles)
- **Terrain (Mapterhorn):**
  - Terrarium-encoded WebP, 512 px.
  - `planet.pmtiles` covers z0–12 globally; regional files cover z13–17.
  - Extracting subsets with `pmtiles extract --bbox` is documented.
  - Source: [Mapterhorn, Data access](https://mapterhorn.com/data-access/)
  - `attribution.json` lists 151 sources. The global one is "COPERNICUS GLO-30" under the "COPERNICUS full, free and open license". A scan for Indonesia or Southeast Asia found no Indonesian national high-resolution source — [download.mapterhorn.com/attribution.json](https://download.mapterhorn.com/attribution.json)
- **Workers and the service worker:** Chromium's "existing code allows only dedicated workers to inherit the controller" for blob script URLs. A later change (date not captured; there is an enterprise policy `SharedWorkerBlobURLFixEnabled`) aligns shared workers with the spec — [blink-dev PSA "SharedWorker script inherit controller for blob script URL"](https://groups.google.com/a/chromium.org/g/blink-dev/c/hClP93e4MLk) (search summary; thread not fetched). A search snippet says Firefox handles blob workers differently after `clients.claim()` — [w3c/ServiceWorker #1554](https://github.com/w3c/ServiceWorker/issues/1554) (not fetched).

### Inferences
- **INFERENCE (corridor model; method in Gaps, calculation by this researcher):** a synthetic 20 km zig-zag trail near 6.8°S was buffered, and every tile whose centre lies within buffer + half its diagonal was counted.
  - With a ±1 km buffer: z10–16 needs 268 tiles (≈7.6 MiB OpenTopoMap PNG at the measured averages). z10–17 needs 836 tiles (≈20.7 MiB, using the z16 average for z17); the bounding box would need 3,274.
  - With a ±2 km buffer: z10–16 needs 451 tiles (≈12.3 MiB) and z10–17 needs 1,570 (≈38.1 MiB).
  - A corridor (`pmtiles extract --region corridor.geojson`, or a server-side tile list) cuts tile count by roughly 2.5–4× compared with the bounding box.
  - Stopping at z16 halves the pack compared with z17. MapLibre overzooms from the source `maxzoom`.
- **RECOMMENDATION (tile option matrix, for the owner to decide):**
  - **A. Raster XYZ from a tile host MEMOUNT is licensed to prefetch from** (see Q5).
    - Tiles are listed in the manifest and stored in Cache Storage.
    - A custom protocol such as `memount-pack://{z}/{x}/{y}` runs on the main thread and returns `caches.match(...)` bytes.
    - No new runtime dependency; integrity is checked per tile; roughly 7–40 MiB per trip.
    - Keeps the current OpenTopoMap look, including contours and hillshade, and the dark-mode raster dimming.
  - **B. Per-trip PMTiles** generated on the server with `pmtiles extract --region <corridor> --maxzoom 16` from an archive MEMOUNT hosts.
    - One resumable download goes into OPFS and is read through `FileSource` and the `pmtiles` protocol.
    - Integrity is a single hash.
    - Requires approving `pmtiles` (+`fflate`) as a new runtime dependency.
  - **C. Vector basemap** (Protomaps/OpenFreeMap-derived).
    - Smallest: about 2 MiB to z14 for the 11 km box.
    - Needs a style plus glyphs and sprites.
    - Loses OpenTopoMap's contours and hillshade unless DEM and contour generation are added, which means more dependencies and work.
- **RECOMMENDATION (bounds and maxzoom):** set the pack source's `bounds` to the pack bbox and `maxzoom` to the pack's highest zoom. MapLibre will then neither request missing tiles nor show holes past maxzoom.
- **RECOMMENDATION (labels on 4.7):** if checkpoint labels use symbol layers with `text-field`, the glyph PBFs for the font stack must be in the pack. Range `0-255` covers basic Latin for Indonesian text; this is an INFERENCE, so verify with the actual label text. The simpler alternative is HTML markers for checkpoints. The local-font fallback needs MapLibre ≥5.11.
- **INFERENCE (worker interception):** MapLibre 4.7's UMD build runs its tile fetches in a blob-URL worker, so whether the service worker intercepts them depends on blob-worker controller inheritance. Serving pack tiles through `addProtocol`, which runs on the main thread, avoids that dependency entirely.
- **INFERENCE (upgrading MapLibre):** moving to 6.x is not required for the pack. It is a framework change (ESM-only, WebGL2 required, API breaks) and needs explicit owner approval under R-020/R-021.
- **INFERENCE (terrain):** 3D terrain is not needed for the field pack; OpenTopoMap raster already bakes in hillshade and contours. If the vector option is chosen, Mapterhorn gives Indonesia only z≤12 (GLO-30, about 30 m). That works for overview hillshade but is coarse at z15–16.

### Gaps
- **OPEN ISSUE (measurement scope):** one area, at most 4 tiles per zoom. Real MEMOUNT trails need measuring: 3–5 trails, the full corridor tile list, and both light and dark styles. No Protomaps z15 or Mapterhorn DEM tile sizes were measured.
- **OPEN ISSUE (corridor model):** synthetic 8-leg polyline totalling 20 km, starting at 106.95°E, 6.85°S; tiles counted by centre distance ≤ buffer + half-diagonal. It is an approximation of polygon–tile intersection, not a real trail.
- **OPEN ISSUE (no public raster PMTiles):** there is no public OpenTopoMap-style raster PMTiles archive. Option B with an OpenTopoMap look would need self-rendering or a licensed raster source; self-hosting OpenTopoMap was not researched.
- **OPEN ISSUE (addProtocol coverage):** the docs do not say whether 4.7's `addProtocol` also intercepts glyph and sprite URLs that use a custom scheme. Verify in a spike.
- **OPEN ISSUE (bundle size):** the minified and gzipped bundle cost of `pmtiles` in a Vite build was not measured.

## Q5. Tile licensing and usage policies for offline caching

### Takeaway
The two servers MEMOUNT would most naturally prefetch from are the most restrictive:
- tile.openstreetmap.org forbids offline use and prefetching outright;
- OpenTopoMap's public server welcomes app use only as long as mass downloads don't strain it. A per-trip prefetch against OpenTopoMap needs their consent or a self-hosted or licensed source.

Commercial providers range from "client cache only" (MapTiler; Stadia allows ≤100 MB in mobile apps) to "offline caching allowed, pre-downloading needs the Small Business plan" (Thunderforest). OSM-derived builds (Protomaps, OpenFreeMap downloads) can be self-hosted under ODbL. They must be attributed to "OpenStreetMap" with a link to /copyright, and the offline view must show that attribution too.

### Cited Findings
- **OSMF tile usage policy (tile.openstreetmap.org):**
  - "Bulk downloading is any pre-emptive fetching of tiles other than those a user is actively viewing."
  - "Offline use is not permitted on `tile.openstreetmap.org`."
  - Explicitly prohibited examples: "Download city/country for offline use" and "Save area for later".
  - "Prefetch/offline patterns … will be blocked without notice."
  - Clients must honour caching headers, or cache for at least 7 days; send a unique User-Agent and a valid Referer; and not hide attribution.
  - "This policy may change at any time."
  - Source: [OSMF Tile Usage Policy](https://operations.osmfoundation.org/policies/tiles/)
- **OpenTopoMap:**
  - Licence CC-BY-SA. Required attribution: "Kartendaten: © OpenStreetMap-Mitwirkende, SRTM | Kartendarstellung: © OpenTopoMap (CC-BY-SA)".
  - Commercial use is allowed.
  - Use in apps "ist jederzeit gerne möglich, sofern unser Server z.B. durch Massendownloads nicht zu stark beansprucht wird" ("is always welcome, provided our server isn't overloaded by mass downloads, for example").
  - No uptime guarantee. They would like a short note about the usage.
  - Source: [opentopomap.org/about](https://opentopomap.org/about)
- **Protomaps builds:** "hotlinking to these downloads are discouraged … copy the tileset to your own Cloud Storage". ODbL "Produced Work" requires OpenStreetMap attribution — [Protomaps downloads](https://docs.protomaps.com/basemaps/downloads)
- **OpenFreeMap:**
  - "there are no limits on the number of map views or requests"; commercial use allowed.
  - Attribution "OpenFreeMap © OpenMapTiles Data from OpenStreetMap".
  - Weekly planet downloads.
  - Source: [openfreemap.org](https://openfreemap.org/)
  - The ToS (last updated 2026-09-09) forbids users to "Attempt to collect data from the service in automated ways without permission" and makes no availability promise — [OpenFreeMap ToS](https://openfreemap.org/tos/)
- **MapTiler Cloud:**
  - "Results of requests can be stored in a temporary personal cache (browser cache, mobile app cache, etc.) for use by a single end-user only."
  - "it is prohibited to batch or excessive bulk download of map tiles".
  - Server-side caching and redistribution are prohibited; on-screen attribution is required.
  - No date is shown.
  - Source: [MapTiler Cloud terms](https://www.maptiler.com/terms/cloud/)
- **Stadia Maps** (ToS last updated 2026-03-18):
  - Prohibits "bulk downloading of data, except for the purpose of caching small amounts of data for offline use in a mobile application, not to exceed 100MB cached at a time per device".
  - Proxying and caching are prohibited except for that exception, cacheable static maps, and "standard client-side caching (server-side caching is prohibited)".
  - Source: [Stadia Maps ToS](https://stadiamaps.com/terms-of-service/)
- **Thunderforest:**
  - "Tiles may be cached in-browser and on-device for offline use." "Apps may retain tiles beyond the HTTP Expiry date".
  - Bulk downloads need "the Small Business plan or higher".
  - "Caching proxies or other redistribution methods are not permitted".
  - Attribution to "Thunderforest" and "OpenStreetMap contributors", with links.
  - Source: [Thunderforest terms](https://www.thunderforest.com/terms/)
- **OSMF Attribution Guidelines** (adopted 2021-06-25):
  - "Attribution must be to 'OpenStreetMap'"; "© OpenStreetMap contributors" remains acceptable. It should link to openstreetmap.org/copyright.
  - On interactive maps, typically in a corner.
  - It may be collapsed if dismissible, collapsed on interaction, or auto-hidden after 5 s, but must remain findable.
  - Attribution at app startup does not need repeating.
  - "Substantial extractions" become Derivative Databases under ODbL.
  - Source: [OSMF Licence/Attribution Guidelines](https://osmfoundation.org/wiki/Licence/Attribution_Guidelines)

### Inferences
- **INFERENCE:** prefetching an OpenTopoMap corridor per trip (hundreds to about 1,600 tiles per pack, times the number of users) is plausibly the "Massendownloads" their page warns about. PERLU KLARIFIKASI with OpenTopoMap before building on their public server, or switch pack tiles to a self-hosted or licensed source.
- **RECOMMENDATION:** the pack manifest carries every required attribution string, for basemap, data and DEM. The offline view shows them in a map corner, following the OSMF guidelines.
- **INFERENCE:** if MEMOUNT's trail geometry or checkpoints are derived from OSM, packaging them may create ODbL obligations (derivative database or produced work). This needs a licence review by the owner; this brief does not decide it.

### Gaps
- **PERLU KLARIFIKASI:** whether an installable PWA counts as a "mobile application" under Stadia's 100 MB exception. The ToS does not say.
- **OPEN ISSUE:** OpenTopoMap gives no numeric threshold for "Massendownloads"; the feasibility and cost of self-hosting OpenTopoMap rendering were not researched.
- **OPEN ISSUE:** pricing for MapTiler, Stadia and Thunderforest plans that allow pre-downloading was not researched.

## Q6. Field-mode web APIs: Screen Wake Lock, Geolocation when hidden, enableHighAccuracy, Vibration, Device Memory

### Takeaway
Screen Wake Lock is Baseline (newly available since March 2025), and on iOS it works reliably in Home Screen apps only from 18.4. The Geolocation spec delivers positions only to documents that are fully active and visible, so web field mode is foreground-only. The screen must stay on (wake lock) for continuous tracking, and iOS explicitly offers no background GPS for Home Screen PWAs. Vibration does nothing on iOS or Firefox Android and must never carry critical alerts. Device Memory is Chromium-only.

### Cited Findings
- **Screen Wake Lock:**
  - "Baseline 2025 – Newly available … Since March 2025"; HTTPS only.
  - "previously acquired locks are automatically released when document becomes inactive"; the lock can also be released for low battery or power save.
  - The recommended pattern re-acquires the lock on `visibilitychange`. Permissions-Policy `screen-wake-lock` (default `self`).
  - Source: [MDN, Screen Wake Lock API (last modified 2026-09-11)](https://developer.mozilla.org/en-US/docs/Web/API/Screen_Wake_Lock_API)
  - web-features `screen-wake-lock`: baseline low 2025-03-31 — [web-features](https://cdn.jsdelivr.net/npm/web-features/data.json)
- **Wake Lock by browser (BCD):** Chrome Android 84, Samsung 14.0, Firefox Android 126, Safari iOS 18.4. Safari iOS 16.4–18.3 was partial; the note says it "Does not work in standalone Home Screen Web Apps. See [bug 254545](https://webkit.org/b/254545)" — [BCD](https://github.com/mdn/browser-compat-data)
- **Geolocation spec** (W3C Candidate Recommendation Snapshot 2026-03-26; previously a Recommendation on 2022-09-01):
  - "If document's visibility state is 'hidden', wait for the following page visibility change steps".
  - Watch updates go only to fully active, visible documents; otherwise "the updates get silently dropped".
  - `enableHighAccuracy` "can result in slower response times or increased power consumption".
  - Time spent waiting for visibility or permission does not count toward `timeout`.
  - Source: [W3C Geolocation](https://www.w3.org/TR/geolocation/)
- **Geolocation support:** `geolocation` is Baseline widely available (since 2018-01-29). `geolocation-element` (the `<geolocation>` element) is Chrome 144 only and not Baseline — [web-features](https://cdn.jsdelivr.net/npm/web-features/data.json)
- **Field precedent:** "iOS does not guarantee continuous background GPS for a Home Screen PWA. Screen lock, app switching or browser suspension can interrupt JavaScript and location updates". The app records gaps rather than assuming movement was continuous — [Trail-Pocket-Max README](https://github.com/appbuilderlee2/Trail-Pocket-Max)
- **Vibration (BCD):**
  - Chrome Android 32. "Beginning in Chrome 60, this method requires a user gesture. Otherwise it returns `false`."
  - Samsung 2.0.
  - Firefox desktop: removed in 129.
  - Firefox Android 79+: partial, "Vibration is disabled. If the window is visible, then `navigator.vibrate()` returns `true`, but no vibration takes place".
  - Safari iOS: no.
  - web-features `vibration`: not Baseline.
  - Sources: [BCD api/Navigator.json](https://raw.githubusercontent.com/mdn/browser-compat-data/main/api/Navigator.json), [web-features](https://cdn.jsdelivr.net/npm/web-features/data.json)
- **Device Memory (BCD):**
  - Chrome Android 63 and Samsung 8.0; not Firefox or Safari.
  - "From Chrome 147, reported values are 1, 2, 4, and 8" on Android, and 2, 4, 8, 16 and 32 on desktop. Before 147 the values were 0.25–8.
  - web-features `device-memory`: not Baseline.
  - Sources: [BCD api/Navigator.json](https://raw.githubusercontent.com/mdn/browser-compat-data/main/api/Navigator.json), [web-features](https://cdn.jsdelivr.net/npm/web-features/data.json)

### Inferences
- **RECOMMENDATION (field mode is foreground-only):**
  - Start `watchPosition` only while the field map is visible, with high accuracy on and a bounded `maximumAge`. Stop on `visibilitychange` to hidden.
  - Show "last fix at HH:MM, ±N m" and mark gaps, never an interpolated position.
  - Request the wake lock only from an explicit "Field mode" action; release it on exit and re-acquire it on return to visibility.
  - Warn about battery drain; the spec itself acknowledges the extra power use.
- **RECOMMENDATION (alerts):** never rely on vibration for safety-relevant cues (none on iOS; a silent no-op on Firefox Android). Always pair a visual state with text, and optionally audio.
- **INFERENCE (adaptive quality):** `deviceMemory` can tune MapLibre's in-memory cache and the pack's maximum zoom on Chromium only; everything else uses conservative defaults. Since Chrome 147, Android reports at least 1, so it cannot identify devices under 1 GB.

### Gaps
- **OPEN ISSUE:** no first-party Chrome Android or WebKit documentation was found describing behaviour specific to screen-off or backgrounded tabs beyond the W3C spec. The iOS evidence is the Trail Pocket README and old forum posts found by search, which are not authoritative.
- **OPEN ISSUE:** no measured battery-drain figures for `enableHighAccuracy` plus wake lock on mid-range Android were found.

## Q7. Security and privacy of on-device pack data (logout, account deletion, shared devices, encryption, CSRF on replay)

### Takeaway
Client-side storage gives no confidentiality against XSS or anyone who can access the device profile. Encrypting with a key stored in the same browser adds little. The real controls are:
- keep personal data in the pack to a minimum;
- scope every store to the user;
- wipe explicitly in JS on logout or user switch, backed up by `Clear-Site-Data`;
- expire packs after the trip.

Account deletion cannot reach a device that is offline. Local data can only be wiped the next time the app is online and learns the account is gone, which the canonical privacy policy must state (MEMOUNT: `docs/KEBIJAKAN-DATA-PRIBADI.md` is the owner; not decided here).

### Cited Findings
- **OWASP on client-side storage:**
  - "do not assume client-side storage provides confidentiality. Do not store session tokens, credentials, or other secrets in IndexedDB unless they are encrypted", using "a key that is not itself recoverable from the browser".
  - "A single Cross-Site Scripting vulnerability can read or write any data in IndexedDB; treat its contents as untrusted input on read".
  - People "with read access to the browser profile directory on disk can read or modify the stored data".
  - "Do not store session identifiers in local storage".
  - Source: [OWASP HTML5 Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/HTML5_Security_Cheat_Sheet.html)
- **Clear-Site-Data:**
  - `"storage"` clears localStorage, sessionStorage, IndexedDB (`deleteDatabase` for each database), service-worker registrations (unregister), Web SQL, and FileSystem API data.
  - `"cookies"` "affects the entire registered domain, including subdomains".
  - MDN's sign-out example: `Clear-Site-Data: "cache", "cookies", "storage", "executionContexts", "prefetchCache", "prerenderCache"`. Secure contexts only.
  - Source: [MDN, Clear-Site-Data (last modified 2025-11-21)](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Clear-Site-Data)
- **Clear-Site-Data support (BCD):**
  - `"storage"`: Chrome Android 61, Samsung 8.0, Firefox Android 63, Safari iOS 17.
  - `"cache"`: partial in Chromium 127+ (Samsung 28.0 partial), Firefox 138, Safari 17.
  - `"executionContexts"`: Safari removed it in 18.3; Chrome never shipped it.
  - Source: [BCD](https://github.com/mdn/browser-compat-data)
  - **Conflict:** the MDN page summary reads "Widely available … since September 2023", but web-features 3.40.0 lists `clear-site-data` as baseline=false, because Chrome's `"cache"` support is partial — [web-features](https://cdn.jsdelivr.net/npm/web-features/data.json)
- **Inertia history encryption:** clear history at logout with `Inertia::clearHistory()` / `router.clearHistory()`. Encrypted history uses Web Crypto with the key in sessionStorage — [Inertia v3, History encryption](https://inertiajs.com/docs/v3/security/history-encryption)
- **CSRF on replayed requests:** Inertia sends `X-XSRF-TOKEN` from the `XSRF-TOKEN` cookie, and a 419 shows a modal unless handled — [Inertia v3, CSRF](https://inertiajs.com/docs/v3/security/csrf-protection)
- **Storage Buckets expiry (Chromium only):** `expires`/`setExpires` exist from Chrome 122 (experimental) — [BCD api/StorageBucket.json](https://raw.githubusercontent.com/mdn/browser-compat-data/main/api/StorageBucket.json)

### Inferences
- **RECOMMENDATION (logout wipe, defence in depth):**
  1. The client deletes the user's pack data before calling logout: `caches.delete('pack-*')`, `indexedDB.deleteDatabase('memount-offline-<hash>')`, and OPFS `removeEntry(dir, {recursive: true})`.
  2. The logout response also sends `Clear-Site-Data: "storage"` (and `"cookies"` if appropriate). Two cautions: `"storage"` also unregisters the service worker, and MDN's list does not explicitly name the Cache API or OPFS, so step 1 remains necessary.
  3. Call `Inertia::clearHistory()`.
  - A logout performed offline can only run step 1; the server-side session ends later.
- **RECOMMENDATION (user switch):**
  - Key every offline store by a hash of the user ID.
  - On sign-in, if packs exist for another user, delete them or at least never show them.
  - Never store auth tokens in IndexedDB. The Laravel session cookie is HttpOnly and managed by the server.
- **RECOMMENDATION (shared devices and retention):**
  - Offer "Delete pack" and "Delete all offline data".
  - Auto-expire packs N days after the trip end date: an app-level check on open, with Storage Buckets `expires` as a Chromium enhancement.
  - The retention period is a policy decision for `docs/KEBIJAKAN-DATA-PRIBADI.md`.
- **INFERENCE (encryption at rest):** a non-extractable Web Crypto key kept in IndexedDB is readable by the same XSS and sits in the same profile, which OWASP's "key that is not itself recoverable from the browser" rules out. A passphrase-derived key adds friction in the field (cold, gloves, fatigue). The practical protections are data minimisation, OS device lock, full-disk encryption, and a strict CSP against XSS.
- **INFERENCE (account deletion):** the server cannot erase an offline device. Wipe the device when the app next gets 401 or 410 for the pack owner, and state this limit in the privacy policy (OPEN ISSUE for the policy owner).
- **RECOMMENDATION (queued writes):** replay from the page with a live session, an idempotency key, and 419/401 re-authentication (see Q3). If a location track is ever recorded, keep it local and do not sync it by default, in line with the existing location-privacy rules.

### Gaps
- **OPEN ISSUE:** whether Clear-Site-Data `"storage"` also clears Cache Storage and OPFS in each engine is not stated on the MDN page read. Test it per browser.
- **OPEN ISSUE:** the pack's personal-data contents (for example emergency contacts, other group members' names) and how long they are kept need a policy decision; out of scope for this research.

## Q8. Testing offline (CI and manual) and real-world examples of offline maps on the web

### Takeaway
Playwright can drive offline service-worker tests reliably only in Chromium. WebKit has an open bug (2026-09-18) where offline emulation breaks navigations the service worker serves, and service-worker APIs are documented as Chromium-only. Lighthouse dropped its PWA category in v12 (April 2024), so installability and offline checks now happen in DevTools and in real-device tests. Offline maps in a browser PWA work in open-source hiking apps such as Trail Pocket, which stores PMTiles in OPFS. CalTopo's offline mode is a mobile-app feature, so it is not a browser precedent.

### Cited Findings
- **Playwright and service workers:**
  - "Service workers are only supported on Chromium-based browsers."
  - Requests made by the service worker are reported on the BrowserContext, with `request.serviceWorker()`.
  - `serviceWorkers: 'block'` disables them.
  - A frame's requests bypass `context.route` if a service-worker fetch handler exists.
  - Wait for activation through `registration.active.state === 'activated'` or `controllerchange`.
  - Source: [Playwright, Service Workers](https://playwright.dev/docs/service-workers)
- **Playwright version and known bugs:**
  - Latest `@playwright/test` is 1.63.0 — [npm](https://www.npmjs.com/package/@playwright/test)
  - Issue #42775 (opened 2026-09-18, open, milestone 1.64): after `context.setOffline(true)`, WebKit fails every service-worker-served navigation ("WebKit encountered an internal error"), while Chromium passes the same cases — [microsoft/playwright#42775](https://github.com/microsoft/playwright/issues/42775)
  - Older issue #2311 (2020): `setOffline` did not affect requests while service workers were active (Chromium and Firefox); closed, resolution not captured — [microsoft/playwright#2311](https://github.com/microsoft/playwright/issues/2311)
- **Pitfall — dev server has no service worker:** a project's issue reports that its tests ran against the dev server, "where vite-plugin-pwa registers no service worker at all" (secondary; search result title) — [tomnewport/slovarchik#665](https://github.com/tomnewport/slovarchik/issues/665)
- **Lighthouse:** "As per Chrome's updated Installability Criteria, Lighthouse has removed the PWA category" in v12.0.0, published to npm 2024-04-22. The latest is 13.5.0. Sources: [Lighthouse v12.0.0 release](https://github.com/GoogleChrome/lighthouse/releases/tag/v12.0.0), [npm lighthouse](https://www.npmjs.com/package/lighthouse), [issue #15535](https://github.com/GoogleChrome/lighthouse/issues/15535)
- **Chrome DevTools:** the Service Workers pane has "Offline", "Update on reload" and "Bypass for network"; Cache Storage has a read-only viewer; one button unregisters service workers and clears storage — [Chrome DevTools, PWA (page date 2016-07-25)](https://developer.chrome.com/docs/devtools/progressive-web-apps)
- **Real-world examples:**
  - **Trail Pocket Max** (open-source hiking PWA, v4.2.8, 273 commits): PMTiles in OPFS, IndexedDB metadata, Cache API fallback, resumable Range downloads in the service worker, validation with re-download. Seven South Australia regions total about 522.7 MiB — [Trail-Pocket-Max](https://github.com/appbuilderlee2/Trail-Pocket-Max)
  - **makinacorpus/maplibre-offline-pmtiles:** OPFS-backed plugin — [GitHub](https://github.com/makinacorpus/maplibre-offline-pmtiles)
  - **gps-map:** "An offline capable map viewer with GPS integration"; a PWA that loads PMTiles from device or cloud storage (search summary; not fetched) — [bmcbride/gps-map](https://github.com/bmcbride/gps-map)
  - **MapComplete:** its maintainer asked in July 2025 about adding offline mode with PMTiles — [PMTiles discussion #588](https://github.com/protomaps/PMTiles/discussions/588)
  - **CalTopo:** "New on the CalTopo mobile app is Offline mode!" (2025-08-25, mobile app ≥1.21, paid plans) — [CalTopo blog](https://blog.caltopo.com/2025/08/25/new-feature-offline-mode/). A search snippet says the CalTopo Desktop app runs a local server that you open in the browser (localhost:8080) — [CalTopo training](https://training.caltopo.com/all_users/desktop/download) (not fetched).

### Inferences
- **RECOMMENDATION (CI):** a Playwright Chromium project against a production build (`vite build` plus the Laravel app, not the dev server):
  1. Load online and wait for the service worker to be activated and controlling.
  2. Download the pack through the UI and assert the manifest status is `ready` with the expected counts.
  3. `context.setOffline(true)`, then navigate or reload to a trail page.
  4. Assert the offline shell renders the trail line, the checkpoints and the "as of" labels.
  5. Assert no dynamic status or weather appears as current.
  6. Delete the pack, then assert storage is empty.
  - Mark WebKit offline service-worker tests as a known issue (#42775) until it is fixed.
  - Unit-test the manifest and integrity logic separately.
- **RECOMMENDATION (manual matrix):**
  - Devices: Android Chrome and Samsung Internet (installed and in a tab), and iPhone Safari (installed Home Screen app vs tab), all in airplane mode.
  - Scenarios: kill-and-reopen, a low-storage device, a pack older than 7 days on iOS in a tab (7-day cap) vs installed, and a deploy while offline followed by reconnecting (the 409 path).
  - Record evidence with remote DevTools (Application pane: storage, cache, service worker).
- **INFERENCE:** there is no widely used commercial browser-only precedent. The known web examples are open-source PWAs, while CalTopo and similar products put offline in native apps. That is a reason to keep the pack small (single-digit to tens of MiB) and the feature narrowly scoped.

### Gaps
- **OPEN ISSUE:** what MapComplete actually shipped offline could not be verified. A search summary claims version 0.57.0 (2025-11-05) added an "offline foreground manager", but no primary source was fetched.
- **OPEN ISSUE:** no published measurements (success rate, pack sizes, eviction incidents) exist from production web apps shipping offline maps.
- **OPEN ISSUE:** the dt.in.th and The Green Report articles on Playwright offline testing appeared in search results but were not fetched.
