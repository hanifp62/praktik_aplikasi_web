# Official park/trail apps and official-data input: presentation, offline behaviour, authoring, provenance (state as of September 2026)

Conventions used in these notes:
- Every source was accessed on **2026-09-25**; each citation carries `[acc. 2026-09-25]`.
- `[OLDER: <date>]` means the finding is dated before 2026 and may no longer be current.
- `[UNVERIFIED]` means the claim was seen only in a search-result summary, a third-party source, or a page that could not be fetched (HTTP 403/404/unreachable). It was not confirmed on the primary page.
- Quotes come from the page text as extracted by the fetch tool. "Paraphrase" marks text that is not a verbatim quote.
- "INFERENCE" = my reasoning from the cited findings. "RECOMMENDATION" = a design implication for MEMOUNT. Neither is a sourced fact.

---

## 1. What do official park apps provide, how do they show alerts/closures (timestamps, scope, severity), and what works offline vs needs a connection?

### Takeaway
Offline downloads are documented for only three official apps: US NPS, Parks Canada and NSW NPWS. All three put **stable content** in them: maps, trail descriptions, grades, facilities and self-guided tours. No offline documentation was found for Korea's KNPS app or Singapore's NParks NEAR app. **Alerts and closures stay online-first.** The NPS explicitly leaves alerts, calendar updates and real-time updates out of its offline save; only whatever was loaded before losing signal stays on the device. NZ's Plan My Walk says it cannot receive new alerts offline. Alerts are structured by category or severity, by scope (park → area → trail or section) and by dates, but how much date detail is kept varies widely. The NPS API has only a `lastIndexedDate`. NSW shows "Applies from" and "Last reviewed". Parks Canada bulletins carry an issue date, an effective date, "Until further notice", the legal basis and the superintendent's sign-off.

### Cited Findings

**United States: NPS App and NPS Data API**
- The NPS App covers "more than 400 national parks" and "was created by park rangers". Listed features include Interactive Maps, Park Tours, Offline Use, and "News, Alerts & Events". Statistics "as of June 2025": over 6M downloads, 4.9/5 on the App Store, 4.8/5 on Google Play. Page last updated May 14, 2026. — [NPS: The NPS App](https://www.nps.gov/subjects/digital/nps-apps.htm) [acc. 2026-09-25]
- "You can download content from entire parks for offline use. It's especially handy if you're exploring remote areas in parks or concerned about data limits." — [NPS: The NPS App](https://www.nps.gov/subjects/digital/nps-apps.htm) [acc. 2026-09-25]
- The "App 101" page (last updated May 5, 2026) says: "Each park site can be downloaded for offline use… look for the download button in the upper right corner". It recommends downloading "while on a strong wifi connection" and warns: "If you delete the app or redownload it to a new device your saved lists and downloaded parks will not transfer." This 2026 page does **not** list which content is excluded offline. — [NPS: App 101 Tips and Tricks](https://www.nps.gov/articles/000/appguide.htm) [acc. 2026-09-25]
- [OLDER: page last updated 2023-02-22] "Select 'Save this park for offline use.' By doing this, most of the app content will be available for use in the park… the exceptions being alerts, calendar updates, and real-time updates—accessing those items before leaving service will at least keep that information in the app." — [NPS Guadalupe Mountains: The NPS App](https://www.nps.gov/gumo/planyourvisit/app.htm) [acc. 2026-09-25]
- Each NPS Data API alert object has exactly these fields: `id`, `url`, `title`, `parkCode`, `description`, `category`, `relatedRoadEvents`, `lastIndexedDate`. There is **no start, effective or expiry field**.
  - Live sample for Yosemite, Grand Canyon and Yellowstone (9 alerts in total) showed the categories "Park Closure", "Danger", "Caution" and "Information".
  - "Glacier Point Road closure": Park Closure, lastIndexedDate 2026-09-24, has relatedRoadEvents.
  - "Inner Canyon Trail Closures": filed under **Danger**, not Park Closure (2026-09-24).
  - "West Thumb Geyser Basin Boardwalk - Partial Closure": Information (2026-09-14).
  - "Stay Away From Bull Elk": Caution (2026-08-26).
  - "Biscuit Basin Closed Due to Hydrothermal Explosion": Park Closure (lastIndexedDate 2026-07-01).
  - Source: [NPS Data API /alerts endpoint](https://developer.nps.gov/api/v1/alerts?parkCode=yose,grca,yell&limit=6). An `api_key` is required; this query used api.data.gov's public `DEMO_KEY`. [acc. 2026-09-25]

**Canada: Parks Canada**
- App page: "Explore offline - No Wi-Fi or cell service needed". It highlights self-guided tours, trip planning, offline maps, accessibility tools and favourites. Alerts, bulletins and trail conditions are **not mentioned**. Date modified 2026-09-23. — [Parks Canada App](https://parks.canada.ca/voyage-travel/app) [acc. 2026-09-25]
- App Store listing: "Access hours, real-time directions, weather forecasts, and local tips".
  - v9.0.349 (May 13, 2025): "…improved image loading when offline".
  - Latest version 9.0.639, dated "September 4" (year not shown): "Small bug fixes and improvements".
  - Source: [App Store: Parks Canada App](https://apps.apple.com/us/app/parks-canada-app/id1232267399) [acc. 2026-09-25]
- Trail conditions are published on the **web, per park**. The Banff table gives each trail a condition (Good / Fair / Caution / Closed / Bear Warning) and a comment prefixed with a date, e.g. "2026-09-24: Biking including pedal-assist e-bikes is allowed; Mainly dry;". The Lake Minnewanka row reads "Bear warning in effect; Check the important bulletins for more information". The page says: "For a complete list of ongoing closures and restrictions, please visit our Important Bulletins page". Page footer: "Date modified: 2026-07-29". — [Parks Canada: Banff trail conditions](https://parks.canada.ca/pn-np/ab/banff/activ/randonnee-hiking/etat-sentiers-trail-conditions) [acc. 2026-09-25]
- Hub page: "Avoid surprises before heading out on Parks Canada trails… make your trip a safe one by checking the latest conditions." Date modified 2026-08-05. — [Parks Canada: Trail conditions](https://parks.canada.ca/voyage-travel/experiences/sports/randonnee-hiking/sentiers-conditions-trails) [acc. 2026-09-25]
- The Banff Important Bulletins page groups items as **Area Closures**, **Restricted Activities** and **Warnings**. Each item has a title, an effective date and a link. Examples:
  - "Area Closure: Point Camp Meadows and Singing Meadows Prescribed Fire" (September 21, 2026)
  - "Area Closure: Lake Minnewanka Trail, Aylmer Pass Trail, and Campgrounds" (September 15, 2026)
  - A standing "Restricted Activity: Firearms prohibited" (June 05, 2018)
  - Page "Date modified: 2025-06-05".
  - Source: [Parks Canada: Banff Important Bulletins](https://parks.canada.ca/pn-np/ab/banff/bulletins) [acc. 2026-09-25]
- Individual bulletin, "AREA CLOSURE - Catwalk and viewpoint #7 on the Johnston Canyon Trail":
  - Legal basis: "Pursuant to Section 36(1) of the National Parks General Regulations, of the Canada National Parks Act"
  - Area "as shown on attached map"
  - Start 2020-10-02, "Until further notice"
  - Reason: "To reduce the risk to visitor safety due to damaged infrastructure"
  - Penalty: "Violators may be charged under the Canada National Parks Act: maximum penalty $25 000"
  - Signed by the Superintendent, Banff Field Unit; issued October 02, 2020; Date modified 2024-02-09
  - Source: [Parks Canada: Banff bulletin 423fad2a](https://parks.canada.ca/pn-np/ab/banff/bulletins/423fad2a-47e9-447b-93c8-2564112ae0d2) [acc. 2026-09-25]
- Other Banff closure bulletins cite "section 7(1) of the National Parks General Regulations" and closures for wildlife (e.g., a hibernating bear) [UNVERIFIED: search summary only] — [Parks Canada: Banff bulletin 55A20DCD](https://parks.canada.ca/pn-np/ab/banff/bulletins/55A20DCD-9037-41D2-AA7B-9AF5FD13DABA) [acc. 2026-09-25]

**Australia: NSW National Parks and Wildlife Service (NPWS)**
- App: "Download a park when you're at home, then view information and maps during your visit, even without reception or internet access." It covers 225+ parks, with distances, track grades, accessibility, facilities and self-guided audio tours. The app page gives **no detail** on how alerts behave in the app or offline. — [NSW National Parks app](https://www.nationalparks.nsw.gov.au/app) [acc. 2026-09-25]
- The same app page reportedly offers "closure alerts by email" [UNVERIFIED: search summary only] — [NSW National Parks app](https://www.nationalparks.nsw.gov.au/app) [acc. 2026-09-25]
- The alerts list template shows a park status of "Open", "Closed areas" or "Closed", with fields `EffectiveFrom`, `LastReviewed` and "Locations Affected". It advises "Always check the weather before you set out" and points to the Rural Fire Service and State Emergency Service. — [NSW NPWS Alerts list](https://www.nationalparks.nsw.gov.au/alerts/alerts-list) [acc. 2026-09-25]
- Populated examples, Blue Mountains National Park:

  | Category | Alert | Applies from | Last reviewed |
  |---|---|---|---|
  | Closed areas | "Parts of Blue Mountains National Park Closed" | Sat 9 Apr 2022, 1.03pm | Thu 24 Sep 2026, 2.42pm |
  | Closed areas | "Mount Hay Road Partial Closure" | Tue 16 Jun 2026 to Wed 16 Dec 2026 | Fri 4 Sep 2026, 1.20pm |
  | Safety alerts | "Remote Walking Track Conditions" (advises navigation skills and personal locator beacons) | Tue 1 Jul 2025 | Thu 30 Jul 2026, 7.50am |

  Source: [NSW NPWS: Blue Mountains NP local alerts](https://www.nationalparks.nsw.gov.au/visit-a-park/parks/blue-mountains-national-park/local-alerts) [acc. 2026-09-25]
- Subscriptions send an automated email when a subscribed park "closes", "is re-opened", "has a future closure published", or when "the date of a future closure changes". An RSS feed is also offered at `https://www.nationalparks.nsw.gov.au/api/rssfeed/get` ("Fires, floods, and park closures"). — [NSW NPWS: Subscribe to alerts](https://www.nationalparks.nsw.gov.au/safety/alerts-subscribe) [acc. 2026-09-25]

**New Zealand: Department of Conservation (DOC) and Plan My Walk**
- DOC: "APIs are available for tracks, huts and campsites" via its developer portal. The DOC Open Spatial Data Portal offers "shp, gdb, kml, GPX" downloads plus "ESRI Rest and GeoJSON" APIs. Disclaimer: "These maps are for reference only, and you shouldn't rely on them for navigation." Notice: "From April 2026, you can expect to see some service URL changes and content being deprecated." — [DOC: Maps and data](https://www.doc.govt.nz/our-work/maps-and-data/) [acc. 2026-09-25]
- The developer portal renders client-side; no API or alert schema could be read from it. — [DOC Developer Portal](https://api.doc.govt.nz/) [acc. 2026-09-25]
- DOC track-page alert example, "Tongariro 2025 fires":
  - No explicit date label was seen; the text refers to fires "In late 2025".
  - It records a 10-year restorative **rāhui** (customary Māori restriction) placed by Hapū Ngāti Hikairo ki Tongariro.
  - It lists conduct rules such as "Stay on the tracks, and do not enter the fire-affected areas off-track."
  - Source: [DOC: Tongariro Alpine Crossing](https://www.doc.govt.nz/parks-and-recreation/places-to-go/central-north-island/places/tongariro-national-park/things-to-do/tracks/tongariro-alpine-crossing/) [acc. 2026-09-25]
- Plan My Walk (NZ Mountain Safety Council) combines four alert types: "MetService weather warnings and watches, Department of Conservation track, hut and campsite alerts, New Zealand Avalanche Advisory forecasts, and special alerts such as a rāhui". Alerts are shown for the trip dates the user selects. The app "doesn't work offline to call for emergency services or receive new alerts once you are out" and is "a planning tool only". — [NZMSC: About Plan My Walk](https://www.mountainsafety.org.nz/about-plan-my-walk) [acc. 2026-09-25]
- [OLDER: 2021-09-21] "Department of Conservation (DOC) provides an automated data feed, known as an API, of over 1000 tracks, and all huts and campsites." "DOC also provides an alert feed, which is the same as that used on its website." Plan My Walk "uses a complex logic that overlays the weather warnings areas and tracks to determine which warnings are applicable to the given track." — [Wilderness Magazine: How the Plan My Walk app was built](https://www.wildernessmag.co.nz/behind-the-scenes-how-the-plan-my-walk-app-was-built/) [acc. 2026-09-25]
- The trip plan can reportedly be downloaded as a PDF for offline use [UNVERIFIED: search summary only] — [NZMSC: Plan My Walk](https://www.mountainsafety.org.nz/plan-my-walk/) [acc. 2026-09-25]

**United States: Recreation.gov and RIDB**
- "The federal recreation data, and derivative information, used on Recreation.gov" is available through REST APIs and bulk downloads. Recreation.gov asks users to credit it: "Data Source: Recreation.gov". — [Recreation.gov: Use and Share Our Data](https://recreation.gov/use-our-data) [acc. 2026-09-25]
- The RIDB streams exposed through a third-party connector are "Activities, Campsites, Events, Facilities, Facility Addresses, Links, Media, Organizations, Permit Entrances, Recreation Areas, Recreation Area Addresses, Tours", with "a rate limit of 50 requests per minute". No alerts stream is listed (secondary source). — [Airbyte: Recreation.gov source connector](https://docs.airbyte.com/integrations/sources/recreation) [acc. 2026-09-25]
- "Each agency assigns data stewards to ensure its data are updated and correct"; RIDB draws on NPS, USFS, BLM, USACE, BOR and FWS [UNVERIFIED: search summary only; the RIDB documentation page did not render] — [Ag Data Commons: RIDB](https://agdatacommons.nal.usda.gov/articles/dataset/Recreation_Information_Database_RIDB_/24661806), [RIDB](https://ridb.recreation.gov/) [acc. 2026-09-25]

**Asia**
- **Korea.** The KNPS app 국립공원탐방알리미 ("National Park Visit Notifier"), per IT동아 (IT Donga), 17 June 2025:
  - The main page shows "실시간 통제 정보" (real-time control/closure information), linking to the KNPS website for detail.
  - Users who register a hiking schedule are notified of "탐방로 통제와 기상특보 등 주요 공지" (trail closures, weather warnings and other key notices).
  - Data comes from the Korea Meteorological Administration and the Korea Tourism Organization.
  - Difficulty filters: 쉬움 (easy), 보통 (moderate), 어려움 (difficult).
  - Offline capability is not mentioned.
  - Source: [Daum / IT동아: 국립공원탐방알리미 앱](https://v.daum.net/v/20250617184200347) [acc. 2026-09-25]
- **Korea.** A third-party guide (published 2024-04-02, updated 2025-11-07) says KNPS closures are posted in Korean on the KNPS website, and: "Updates have been known to occur between 10pm-4am, so be sure you check closures the same day as well, especially during snowy and rainy weather periods." — [The Girl with a Big Bag: Checking trail closures in South Korea](https://thegirlwithabigbag.com/checking-for-trail-closures-southkorea/) [acc. 2026-09-25]
- **Korea.** KNPS reportedly closes about 121 sections (570 km), around 20% of all trails, during dry-season fire-prevention periods [UNVERIFIED: search summary of a site that was unreachable at fetch time] — [K-Parks guide: Trail closure policy](http://kparksguide.co.kr/category/national-parks-tips/trail-closure/) [acc. 2026-09-25]
- **Singapore.** The NParks "NEAR" app is AR-enabled and aimed at the Coast-to-Coast Trail, Round Island Route and Park Connector Network [search summary] — [NParks: NEAR app](https://pcn.nparks.gov.sg/the-pcn-experience/near/) [acc. 2026-09-25]
- **Singapore.** Closures are published on the web Noticeboard with a posted date, scope, start and end dates, reason, and PDF maps of alternative routes. Examples:
  - "Partial Closure of Changi Boardwalk" (posted July 10, 2026), "expected to reopen on 31 December 2026"
  - "Temporary Closure of a section in one-north Park" (posted June 2, 2026), "closed for enhancement works from 2 Jun 26 to 31 Mar 27"
  - Source: [NParks Noticeboard](https://www.nparks.gov.sg/noticeboard) [acc. 2026-09-25]
- **Japan.** The Ministry of the Environment (MoE) publishes trail-closure notices as web pages per national park. Example from Hakusan National Park:
  - Title: "観光新道の一部通行止めについて" (partial closure of the Kankō Shindō trail)
  - Section: "別当出合～別当坂分岐までの区間" (from Bettō Deai to the Bettō-zaka junction)
  - Period: "９月24日（火）" (24 September, Tuesday) to "11月末まで" (until the end of November)
  - Reason: "石川県による登山道改良工事のため" (trail improvement works by Ishikawa Prefecture)
  - Advice: "他の登山道をご利用いただきますようお願いいたします" (please use other trails)
  - Issued by the Chūbu regional environment office, with a phone number
  - **No year and no publication date** were visible in the fetched content
  - Source: [MoE Hakusan NP notice](https://www.env.go.jp/park/page_00831.html) [acc. 2026-09-25]
- **Japan.** Other MoE pages announce closures being lifted (Fuji-Hakone-Izu) and closures of access roads to trailheads (Bandai-Asahi) [search results] — [MoE national parks portal](https://www.env.go.jp/park/) [acc. 2026-09-25]
- **Japan.** The MoE–YAMAP (a commercial hiking app) agreement covers mountain-ecosystem surveys using hikers' posts, not closure feeds [UNVERIFIED: search summary only] — [NIES 環境展望台 news](https://tenbou.nies.go.jp/news-sp/jnews/detail.php?i=33741) [acc. 2026-09-25]

### Inferences
- INFERENCE: Every official app with documented offline support (NPS, Parks Canada, NSW) offers the **stable layer** offline (maps, descriptions, grades, facilities, tours). Where alert behaviour is documented (NPS, Plan My Walk), volatile official status stays online-first. This matches the owner-approved MEMOUNT decision: "offline field pack for stable data only".
- INFERENCE: The NPS feed shows that even a national agency's machine-readable alert feed can lack effective and expiry fields; it has only an indexing timestamp. The consumer (MEMOUNT) must therefore model freshness itself. The absence of an alert must never be read as "open".
- INFERENCE: Category, severity and scope are separate concepts that real systems mix together. The NPS files a trail closure under "Danger" but a road closure under "Park Closure". Parks Canada separates Area Closure / Restricted Activity / Warning. NSW separates "Closed areas" from "Safety alerts". Banff shows "Bear warning" on trails that are not closed. This supports MEMOUNT keeping **operational status** (open / closed / partial / unknown) separate from **hazard or advisory**, in line with "OPEN never means safe".
- INFERENCE: Official closure notices carry a legal basis and a named issuer (Parks Canada cites the regulation section and the superintendent; MoE names the regional office and a phone number). These are natural provenance fields for an "official data input" form.
- INFERENCE: The Japanese MoE example shows that an official notice may omit the year. A curator transcribing it must record when the notice was captured and must not guess the year; unresolved dates should stay UNKNOWN.
- INFERENCE: Customary or indigenous restrictions (rāhui) are handled as a distinct official alert class in NZ (Plan My Walk "special alerts"). An input model needs room for authorities that are not park agencies.

### Gaps
- No 2026-dated NPS page confirms which content is excluded offline; only the 2023 page does. How the app presents cached alerts offline (e.g., "as of" labels) is not documented.
- Parks Canada app: no documentation found on whether bulletins or trail conditions appear in the app, or offline.
- NSW app: in-app and offline alert behaviour not documented on the app page.
- The DOC API alert schema (fields, dates) could not be read; the portal is client-rendered.
- Malaysia (e.g., Sabah Parks / Kinabalu): not researched, no data.
- UK examples: not researched.
- Recreation.gov: how facility alerts are authored and displayed was not researched, and the RIDB data dictionary was not retrieved.

---

## 2. How is freshness and scope labelled ("as of", effective dates, "check with rangers"), and do apps deliberately disable stale alerts offline?

### Takeaway
The strongest freshness patterns are **per item, not per page**.
- NSW shows both "Applies from" and "Last reviewed"; an alert active since April 2022 was last reviewed on 24 Sep 2026.
- Banff puts a date in front of each trail row, while the page footer date is older.
- Parks Canada bulletins carry an issue date, an effective date and "Until further notice".
- NParks gives a posted date plus start and end dates, or "expected to reopen".
- Outdooractive hides community condition reports after 14 days and prompts closure owners to re-verify.

No official app found shows cached alerts offline with an explicit staleness label. What was observed is **exclusion or non-refresh**: the NPS leaves alerts out of offline use and Plan My Walk cannot fetch new alerts. Both tell users to check before losing signal.

### Cited Findings
- NSW combines an effective-period label with a separate review label: "Applies from: Sat 9 Apr 2022, 1.03pm" together with "Last reviewed: Thu 24 Sep 2026, 2.42pm". At access time, last-reviewed ages across the three sample alerts ranged from 1 day (24 Sep) to about 8 weeks (30 Jul). — [NSW NPWS: Blue Mountains local alerts](https://www.nationalparks.nsw.gov.au/visit-a-park/parks/blue-mountains-national-park/local-alerts) [acc. 2026-09-25]
- Banff rows carry their own dates ("2026-09-24: …"), while the page says "Date modified: 2026-07-29". — [Parks Canada: Banff trail conditions](https://parks.canada.ca/pn-np/ab/banff/activ/randonnee-hiking/etat-sentiers-trail-conditions) [acc. 2026-09-25]
- A Parks Canada bulletin with start 2020-10-02 and "Until further notice", issued October 02, 2020, Date modified 2024-02-09. — [Parks Canada: Banff bulletin 423fad2a](https://parks.canada.ca/pn-np/ab/banff/bulletins/423fad2a-47e9-447b-93c8-2564112ae0d2) [acc. 2026-09-25]
- NParks: "expected to reopen on 31 December 2026" (posted July 10, 2026). — [NParks Noticeboard](https://www.nparks.gov.sg/noticeboard) [acc. 2026-09-25]
- Outdooractive:
  - Current Conditions "display for 14 days before becoming hidden as outdated (searchable via time filter)" (paraphrase).
  - Notices and closures receive a "validity date".
  - Closure managers receive an "automatic reminder asking that they verify and update the current status".
  - Source: [Outdooractive: Notices and Closures](https://www.outdooractive.com/en/knowledgepage/notices-and-closures/37514055/) [acc. 2026-09-25]
- NPS [OLDER: 2023-02-22]: alerts, calendar and real-time updates are exceptions to offline use; "accessing those items before leaving service will at least keep that information in the app". — [NPS Guadalupe Mountains: The NPS App](https://www.nps.gov/gumo/planyourvisit/app.htm) [acc. 2026-09-25]
- Plan My Walk "doesn't work offline to call for emergency services or receive new alerts once you are out". — [NZMSC: About Plan My Walk](https://www.mountainsafety.org.nz/about-plan-my-walk) [acc. 2026-09-25]
- "Check before you go" wording:
  - Parks Canada: "make your trip a safe one by checking the latest conditions" — [Parks Canada: Trail conditions](https://parks.canada.ca/voyage-travel/experiences/sports/randonnee-hiking/sentiers-conditions-trails) [acc. 2026-09-25]
  - Parks Canada, reportedly: check "current trail conditions, warnings and closures or visit a Parks Canada visitor centre before heading out" [UNVERIFIED: search summary] — [Parks Canada: Banff trail conditions](https://parks.canada.ca/pn-np/ab/banff/activ/randonnee-hiking/etat-sentiers-trail-conditions) [acc. 2026-09-25]
  - NSW: "Always check the weather before you set out" — [NSW NPWS Alerts list](https://www.nationalparks.nsw.gov.au/alerts/alerts-list) [acc. 2026-09-25]
- DOC data terms (paraphrase): data is provided "as is"; it is not suitable as the only navigation tool and should be used with current NZTopo50 maps and advice from local DOC staff; data may become outdated. — [DOC: Spatial data terms and conditions](https://www.doc.govt.nz/our-work/maps-and-data/terms-and-conditions/) [acc. 2026-09-25]
- KNPS closures reportedly update overnight ("between 10pm-4am"), so the guide advises checking on the day [third-party]. — [The Girl with a Big Bag](https://thegirlwithabigbag.com/checking-for-trail-closures-southkorea/) [acc. 2026-09-25]
- OpenStreetMap: "Our map data is often downloaded and used offline on various devices for several weeks or months. For offline data to be useful, it should at least be expected to remain unchanged in the next few weeks when you map it." — [OSM wiki: Good practice](https://wiki.openstreetmap.org/wiki/Good_practice) [acc. 2026-09-25]

### Inferences
- RECOMMENDATION: Model at least three distinct timestamps for an official status record:
  1. the **effective period** (applies from / until). "Until further notice" means open-ended; it does not mean expired, and it does not mean unknown.
  2. **`last_reviewed_at`**: when a curator last re-confirmed the status against the source.
  3. **`source_published_at` / `captured_at`**.

  NSW shows that "last reviewed" communicates currency even for closures that have run for years.
- RECOMMENDATION: The offline pack should either leave official status out (NPS pattern) or include it only as a **dated snapshot** labelled with the sync time. Once a time-to-live (TTL) passes, the snapshot should degrade to "status unknown — last checked <date time>", **never OPEN**. No evidence was found of any official app presenting cached status as current offline.
- RECOMMENDATION: Show freshness on each row. A page-level "last updated" can mislead: the Banff page footer is about two months older than its rows.
- INFERENCE: NSW review ages of up to about 8 weeks on long-running closures, versus daily updates on Banff trail rows in season, suggest a TTL should depend on the status type (e.g., short for "open / conditions", longer for a legally ordered closure with a stated end date). This is a design hypothesis, not an observed rule.
- INFERENCE: The KNPS overnight updates imply that a pack synced the evening before can miss a closure issued at dawn. A "sync just before departure" prompt plus a visible sync time addresses this.
- INFERENCE: Outdooractive's 14-day hiding of community conditions, and its reminder to closure owners, are platform precedents for TTLs and review reminders.

### Gaps
- No study was found on how users interpret labels such as "last reviewed", "updated" or "as of".
- No official app was found that documents greying out or auto-expiring cached alerts offline. The NPS only documents exclusion, and only on a 2023 page.

---

## 3. How do platforms let authorities or curators author and ingest official data, which data standards exist, and how are provenance and verification represented?

### Takeaway
Two families exist.

**(1) Authority accounts embedded in a platform:**
- **Outdooractive**: official sources (tourism organisations and protected-area authorities) create notices and closures in a content manager, can earn a "verified badge", and closures automatically close the routes they cross and are avoided by routing.
- **AllTrails Public Lands Program**: 250+ agencies edit trail information and send alerts. Secondary sources say alerts can be posted at trail and park level; this is not verified.
- **Trailforks**: designated trail reporters, often the land manager, set the official open/closed status.
- **OuterSpatial**: organisation managers publish areas, trails, points of interest and alerts to apps.

**(2) Neutral intermediaries:**
- **Digitize the Planet (DtP)**: DtP verifies the responsible person at an authority, who then enters protected-area rules and closures once. Consistency is checked automatically and the result is published as open, machine-readable data consumed by Outdooractive and others.

Standards are fragmented:
- OpenTrails (2014, v1.1, limited adoption)
- US FGDC Federal Trail Data Standards (2011) and the planned Federal Trail GIS Schema
- The MAPLand Act, which mandates federal road and trail data with allowed travel modes and seasons by April 2027
- RIDB, which exposes no alert stream
- OSM, whose tags describe legal access and which discourages mapping temporary features

### Cited Findings

**Outdooractive**
- "Notices and Closures are conceptually similar to Current Conditions. These are entered by an official source (such as a DMO or protected area authority) to inform community users about closed trails or areas and any corresponding detours." (DMO = destination management organisation.) — [Outdooractive: How do I create notices and closures?](https://www.outdooractive.com/en/knowledgepage/how-do-i-create-notices-and-closures-/67511668/) [acc. 2026-09-25]
- Notices are shown in yellow and closures in red. "If a route runs through an area affected by a closure, the route's status is automatically set to 'closed'"; routes revert when the closure ends and authors receive notifications. Both are available "as additional map layer on web and app" (paraphrase). — [Outdooractive: Notices and Closures](https://www.outdooractive.com/en/knowledgepage/notices-and-closures/37514055/) [acc. 2026-09-25]
- The Outdooractive Business module glossary lists:
  - "Keep your guests updated with closures, notices, and conditions that display, in real-time, on maps and routes across all Outdooractive platforms"
  - Closed segments are considered by the routing engine
  - "Get your partner verification and be recognized as a quality content source… Add the 'verified badge' to your brand"
  - "Roles and rights management"
  - An API for "Individual export of your content"
  - "GPX download in .gpx and .kml format"
  - "Bidirectional connectors to Tourism Technology Alliance (TTA) members"
  - Source: [Outdooractive Business: Module Glossary](https://business.outdooractive.com/module-glossary) [acc. 2026-09-25]
- "Notices and closures are maintained by Outdooractive staff and by tourism partners using the content manager" [UNVERIFIED: search summary only] — [Outdooractive: Notices and Closures](https://www.outdooractive.com/en/knowledgepage/notices-and-closures/37514055/) [acc. 2026-09-25]

**Digitize the Planet (DtP), Germany / Austria / Switzerland**
- "The non-profit association Digitize the Planet (DtP) pursues the goal of digitizing all relevant regulations on the use of nature and making them available as open data."
  - [OLDER: announced 13.11.2023] Outdooractive integration.
  - "daily updated information of the protected areas and differentiates between activity-specific and general access bans".
  - "As soon as a tour leads through a protected area with an access ban, it is either permanently or temporarily closed, depending on the ban."
  - Source: [DtP: Data Integration Outdooractive](https://digitizetheplanet.org/data-integration-outdooractive/?lang=en) [acc. 2026-09-25]
- Data entry:
  - The responsible authority enters data, or "With a power of attorney, this task can be delegated to third parties (e.g. protected area administration, tourist destination, etc.)"
  - "After verification of the responsible person by DtP, data entry begins with the help of an intuitive guide through the input fields"
  - "the rules entered are automatically checked for consistency/redundancy by our technology and processed into a machine-readable data record in the international standard"
  - Rules are shown as pictograms with explanatory text ("also in English")
  - "available to all user groups as open data"
  - No formal approval step beyond verifying the person is described
  - Source: [DtP: Data Entry](https://digitizetheplanet.org/data-entry/?lang=en) [acc. 2026-09-25]
- API licensing: "data that are not accompanied by specific licensing information are released under CC0 by default"; "Geospatial datasets and image resources include specific licensing information in the payload fields". — [Digitize Platform: API docs](https://platform.digitizetheplanet.org/en/api/docs/) [acc. 2026-09-25]
- [OLDER: 22 Nov 2023] Outdooractive: the integration "ensures exclusively official content from the protected areas"; DtP had "54 members from Germany, Austria and Switzerland" at that time. — [Outdooractive corporate press release](https://corporate.outdooractive.com/press/outdooractive-sets-new-standards-in-nature-conservation-by-natively-integrating-data-from-digitize-the-planet-into-the-platform/) [acc. 2026-09-25]
- DtP aims to reach visitors through "platforms (Komoot, Outdooractive, Google) and mapping applications" [UNVERIFIED: search summary only] — [DtP home](https://digitizetheplanet.org/?lang=en), [DtP NUDGE project](https://digitizetheplanet.org/nudge-projekt/?lang=en) [acc. 2026-09-25]

**AllTrails Public Lands Program**
- [OLDER: press release 5 March 2024] The programme "provides direct support to over 250 land management agencies and counting"; land managers can "view trail-use insights, edit trail information, and send real-time alerts". Named partners include the U.S. National Park Service and The Nature Conservancy. How land-manager accounts are verified is not described. — [PR Newswire: AllTrails Public Lands Program](https://www.prnewswire.com/news-releases/alltrails-announces-public-lands-program-with-new-trail-and-land-management-resources-302079247.html) [acc. 2026-09-25]
- Launch year conflict: secondary coverage says "In 2023, AllTrails launched its Public Lands Program"; the press release is dated March 2024 [UNVERIFIED which is the launch date] — [Advnture](https://www.advnture.com/news/alltrails-public-lands-program) [acc. 2026-09-25]
- Current programme page: "Manage route lines, starting points, and parking locations"; "Communicate rules, regulations, and route recommendations"; "Reduce search and rescue incidents with up-to-date closures, warnings, and alerts". How alerts appear to hikers and how accounts are verified are not described. — [AllTrails Public Lands](https://publiclands.alltrails.com/) [acc. 2026-09-25]
- Land managers can reportedly add an alert "to single or multiple trail pages that you manage" covering "fees, maintenance, closures, events" [UNVERIFIED: search summary; page returned 403] — [AllTrails Public Lands Portal help: Adding an alert](https://publiclandsportal.zendesk.com/hc/en-gb/articles/4417891204244-Adding-an-alert-to-a-trail-page) [acc. 2026-09-25]

**Trailforks**
- "Trail status updates are normally made by official TORC designated trail reporters". Regular users can post, but their status updates "may be pending until a trail reporter can review it". Status (open/closed, shown as green/red) is separate from an optional condition (e.g., "Wet"). "The status will show up on TORC (and TLC, etc.) but the source of the information is Trailforks." For gated areas, "the trail reporter is the land manager". Users can subscribe to status changes. (TORC is a North Carolina trail association.) — [TORC: Trail status updates via Trailforks](https://www.torc-nc.org/trail-status-updates/) [acc. 2026-09-25]
- Trailforks' own help page "Trail Status Doesn't Match Latest Trail Report" exists but returned 403. Per search summary, the latest report is "probably accurate" when the reporter is the land manager [UNVERIFIED] — [Trailforks help 143](https://www.trailforks.com/help/view/143/) [acc. 2026-09-25]

**OuterSpatial (a land-manager platform that publishes to apps)**
- Data Guidebook v2026-06-09:
  - Data is split into three files: Areas, Trail Segments and Points of Interest. "Each file contains data for a single owner organization."
  - Accepted formats: GeoJSON, File Geodatabase, an ArcGIS Server endpoint, or Shapefile ("not recommended due to format limitations"). Projection WGS84 (EPSG:4326).
  - `u_id` is required and "has to stay the same across all data imports".
  - Status field `closed`: "Indicates closure status of the location. If the location is closed, use a 'yes' value. Otherwise the location is displayed as open."
  - Seasons: "Not including these attributes at all indicates that the location is available all year."
  - Source: [OuterSpatial Data Guidebook (PDF)](https://cdn.outerspatial.com/docs/outerspatial-data-guidebook.pdf) [acc. 2026-09-25]
- Subscribing to an organisation or area notifies the user of alerts for that area and its child locations [UNVERIFIED: search summary] — [OuterSpatial Help: Managing Notifications](https://help.outerspatial.com/article/managing-notifications) [acc. 2026-09-25]
- Hawaiʻi DLNR (the state Department of Land and Natural Resources) uses its OuterSpatial app to publish which areas are open or closed during storms (Sept 2026) [UNVERIFIED: search summary] — [Hawaiʻi DLNR news release, 2026-09-23](https://dlnr.hawaii.gov/blog/2026/09/23/nr26-116/) [acc. 2026-09-25]

**Data standards**
- OpenTrails:
  - Five data types: "Trail Segments (GeoJSON), Trailheads (GeoJSON), Named Trails (CSV), Stewards (CSV), Areas (optional)".
  - Founders include the City of Portland, Code for America, Esri, Strava, AllTrails, NRPA and others.
  - Launched spring 2014 [OLDER]; version 1.1.
  - "Implemented by the City of Portland, Oregon and Northeast Ohio (Cuyahoga Valley)".
  - Source: [Azavea Open Data Standards: OpenTrails](https://azavea.gitbooks.io/open-data-standards/content/standards/domain_specific_standards/opentrails.html) [acc. 2026-09-25]
- The OpenTrails converter outputs `trail_segments.geojson`, `named_trails.csv` and `stewards.csv`, and describes itself as "in-development tools". The spec is linked at codeforamerica.org/specifications/trails; field-level details (e.g., steward publisher/licence, any status field) were not verified. — [GitHub: codeforamerica/OpenTrails](https://github.com/codeforamerica/OpenTrails) [acc. 2026-09-25]
- US Federal Trail Data Standards, FGDC-STD-017-2011 [OLDER: 2011; title only, content not fetched] — [FGDC Federal Trail Data Standards](https://www.fgdc.gov/standards/projects/trail-data-standard/Federal_Trail_Data_Standards_FGDC-STD-017-2011.pdf) [acc. 2026-09-25]
- USGS: an interagency Federal GIS Trail Schema based on the FGDC standard was "scheduled to be released by the end of 2023" [OLDER]. It will let USGS "more efficiently aggregate trail data into the USGS nationwide trails dataset". — [USGS National Digital Trails: update federal trails schema](https://www.usgs.gov/national-digital-trails/update-federal-trails-schema) [acc. 2026-09-25]
- MAPLand Act (US law, Public Law 117-114; enacted April 2022 per search summaries, date not on the FWS page):
  - Five data layers, including roads and trails "with allowed modes of travel and the allowed seasons of use", and areas with "regulations or closures related to hunting or recreational shooting".
  - Deadlines: interagency standards by October 2024; easements by April 2026; roads, trails, recreation sites and hunting/shooting restrictions by April 2027.
  - Source: [USFWS: MAPLand implementation](https://www.fws.gov/mapland-implementation) [acc. 2026-09-25]
  - Updates "at least twice per year" and annual progress reports through March 30, 2031 [UNVERIFIED: search summary only; the FWS page states no update frequency] — [USFS: MAPLand update](https://www.fs.usda.gov/inside-fs/out-and-about/mapland-update) [acc. 2026-09-25]
- OSM access tagging:
  - `access=no`: "Public access is prohibited on foot and for any vehicle, for every type of person."
  - "Access tags pre-eminently describe legal permissions/restrictions and should follow ground truth, such as signage combined with legal regulation, rather than guesswork. They do not describe common or typical use."
  - Source: [OSM wiki: Key:access](https://wiki.openstreetmap.org/wiki/Key:access) [acc. 2026-09-25]
- OSM conditional restrictions: temporary closures use the `:conditional` suffix, e.g. `motor_vehicle:conditional=no @ (2018 May 22-2018 Oct 07)`. Routing-software support for conditions varies. — [OSM wiki: Conditional restrictions](https://wiki.openstreetmap.org/wiki/Conditional_restrictions) [acc. 2026-09-25]
- OSM `trail_visibility` takes the values excellent / good / intermediate / bad / horrible / no, with the note that "some people find this tag lacking of verifiability". — [OSM wiki: Key:trail_visibility](https://wiki.openstreetmap.org/wiki/Key:trail_visibility) [acc. 2026-09-25]
- OSM "Don't map temporary events and temporary features" (see quote in Q2). — [OSM wiki: Good practice](https://wiki.openstreetmap.org/wiki/Good_practice) [acc. 2026-09-25]

### Inferences
- INFERENCE: Provenance elements seen across the systems studied:
  - issuer identity (Parks Canada superintendent; MoE regional office and phone)
  - legal basis (regulation section)
  - effective period, including "until further notice"
  - last reviewed (NSW)
  - validity date plus a re-verification reminder (Outdooractive)
  - a verified account or badge (Outdooractive, DtP)
  - a designated reporter (Trailforks)

  None of the platforms publicly documents a per-record audit trail. MEMOUNT's existing audit log therefore goes beyond observed practice (no public counter-evidence found either).
- INFERENCE: DtP's intermediary model is the closest analogue to MEMOUNT, where no official API exists and curators transcribe official sources. The elements are: verify the responsible person; allow documented delegation by power of attorney; check consistency automatically; publish structured records with pictograms and plain-language text.
- RECOMMENDATION: Store **issued by** (the authority), **entered by** (curator or delegate), **verified by** (a second person, where used) and **authorisation basis** (e.g., a delegation document) as separate fields. Outdooractive, AllTrails and Trailforks merge "who entered it" with "whose authority it carries" into a single account role.
- INFERENCE (counter-example): OuterSpatial's import rule, where a blank `closed` field is "displayed as open", directly contradicts MEMOUNT's "UNKNOWN is never OPEN". Any import MEMOUNT builds should map a missing status to UNKNOWN.
- RECOMMENDATION: Outdooractive and DtP propagate closures automatically: a route crossing a closed area becomes "closed", and closed segments are avoided in routing. MEMOUNT can mark a trail as "affected by a segment closure", but should **not automatically set OPEN** when a closure's end date passes. Outdooractive does revert routes to open automatically; MEMOUNT's rules imply a curator or source confirmation first, with UNKNOWN shown in the meantime.
- INFERENCE: OSM is not a source of official closures. Its access tags describe legal access, and its guidance discourages mapping temporary features. Under MEMOUNT's hierarchy, OSM `access` or `trail_visibility` values are at most community-level or system-interpretation context.

### Gaps
- Google Maps / Google Business Profile for parks: not researched, beyond DtP's unverified mention of Google as a target channel.
- Komoot partner or official content: not researched, beyond the same unverified DtP mention.
- AllTrails alert fields (start/end, auto-expiry), how alerts are shown to hikers, and account verification: not verified (help pages returned 403).
- DtP API field names (validity, geometry, provenance): not seen; the OpenAPI explorer was not fetched.
- OpenTrails v1.1 field-level schema (steward publisher/licence, any status field): not verified.
- RIDB data dictionary (e.g., last-updated fields): not retrieved.

---

## 4. How are official GPX/trail geometry and checkpoint (waypoint) lists imported from authorities, and what are the licensing considerations (government licences, OSM ODbL share-alike when mixing)?

### Takeaway
Authorities publish geometry as GIS files or services:
- DOC: shp, gdb, kml, GPX, ESRI REST and GeoJSON.
- OuterSpatial ingests GeoJSON, File Geodatabase, ArcGIS endpoints or Shapefile in WGS84, with a stable `u_id` per feature.
- OpenTrails splits data into segments, named trails, trailheads, stewards and areas.

Official geometry usually comes with "reference only, not for navigation" disclaimers.

Licences vary:
- DOC: CC BY 3.0 New Zealand, with a Crown-copyright attribution line.
- Recreation.gov: asks for "Data Source: Recreation.gov".
- DtP: CC0 by default.

Mixing with OpenStreetMap: ODbL share-alike applies when OSM and non-OSM data are combined **within the same feature type in the same regional cut**. If a feature type is entirely non-OSM within a regional cut, the combination is a Collective Database, and the non-OSM part is not subject to share-alike.

### Cited Findings
- DOC Open Spatial Data Portal formats and the "reference only… shouldn't rely on them for navigation" disclaimer: see Q1. — [DOC: Maps and data](https://www.doc.govt.nz/our-work/maps-and-data/) [acc. 2026-09-25]
- DOC terms: "Creative Commons Attribution 3.0 New Zealand License"; attribution "Crown Copyright: Department of Conservation Te Papa Atawhai [year]". Data is supplied "as is", DOC "makes no warranties or representations as to possible infringement upon copyrights", intellectual property remains with the Crown, and derivative maps must carry the attribution (paraphrase for the last points). — [DOC: Spatial data terms and conditions](https://www.doc.govt.nz/our-work/maps-and-data/terms-and-conditions/) [acc. 2026-09-25]
- OuterSpatial import model:
  - Formats and WGS84 requirement.
  - Required `u_id` "has to stay the same across all data imports".
  - Trails are built from segments. Split segments "Anytime it intersects with another trail" or when attributes differ (e.g., biking yes/no), "so as not to lose this important" information.
  - On re-import: "Any updated location provided will overwrite any changes made to the same location in Manager" (basic info, not detail attributes).
  - "Previously imported locations that are not included in an update will not be deleted."
  - Files are named "Org_Name-Dataset-YearMonthDay.zip".
  - Source: [OuterSpatial Data Guidebook v2026-06-09](https://cdn.outerspatial.com/docs/outerspatial-data-guidebook.pdf) [acc. 2026-09-25]
- OpenTrails separates trail segments, trailheads, named trails, stewards and areas. — [Azavea: OpenTrails](https://azavea.gitbooks.io/open-data-standards/content/standards/domain_specific_standards/opentrails.html) [acc. 2026-09-25]
- Recreation.gov asks for the credit "Data Source: Recreation.gov"; no formal licence document is referenced on that page. — [Recreation.gov: Use and Share Our Data](https://recreation.gov/use-our-data) [acc. 2026-09-25]
- DtP data defaults to CC0; geospatial and image payloads carry their own licence fields. — [Digitize Platform: API docs](https://platform.digitizetheplanet.org/en/api/docs/) [acc. 2026-09-25]
- OSM licence: "If you alter or build upon our data, you may distribute the result only under the same license." Users must credit OSM by displaying its attribution notice and "Make clear that the data is available under the Open Database License." — [OpenStreetMap: Copyright and Licence](https://www.openstreetmap.org/copyright) [acc. 2026-09-25]
- OSMF Horizontal Map Layers guideline ("Endorsed by the OSMF board 2014-06-06") [OLDER, still the published guideline]:
  - "If all data for that Feature Type is from non-OpenStreetMap sources, then the ODbl share-alike conditions do not apply to that Feature Type."
  - "If you use OpenStreetMap data along with non-OpenStreetMap data for a given Feature Type, then the share-alike condition would apply."
  - Example: restaurants added from a non-OSM source only where they are missing from OSM mean "the restaurants added in your non-OpenStreetMap layer must be shared".
  - Source: [OSMF: Horizontal Map Layers guideline](https://osmfoundation.org/wiki/Licence/Community_Guidelines/Horizontal_Map_Layers_-_Guideline) [acc. 2026-09-25]
- OSMF Collective Database guideline ("Endorsed by the OSMF board 2016-06-17"):
  - OSM and non-OSM data "will be considered independent (and thus form a Collective Database rather than a Derivative Database) so long as the data used for a particular data type is either all OSM or all non-OSM within the same regional cut".
  - Qualifying cases include: "the non-OSM and OSM datasets do not reference each other"; non-OSM data "completely replaces a particular type of geometry or data for a primary feature within a regional cut".
  - Source: [OSMF: Collective Database Guideline](https://osmfoundation.org/wiki/License/Community_Guidelines/Collective_Database_Guideline_Guideline) [acc. 2026-09-25]
- OSM contributor rule: "You must not use other maps, including Google Maps and other commercial providers, as a source for changes to OpenStreetMap because they include copyrighted data". — [OSM wiki: Good practice](https://wiki.openstreetmap.org/wiki/Good_practice) [acc. 2026-09-25]
- OSMF also publishes a Produced Work guideline (not fetched). — [OSMF: Produced Work guideline](https://osmfoundation.org/wiki/Licence/Community_Guidelines/Produced_Work_-_Guideline) [acc. 2026-09-25]

### Inferences
- RECOMMENDATION: For every trail geometry and checkpoint, store:
  - source class (official file / OSM / curator-digitised / GPX from an accredited expert)
  - licence and attribution string
  - source URL or document reference
  - acquisition date
  - the "regional cut" it belongs to (e.g., per mountain)

  Avoid patching gaps in an official trail network with OSM ways of the same feature type in the same area, unless MEMOUNT is prepared to release that combined trail dataset under ODbL. Keeping a trail's geometry wholly OSM-sourced or wholly non-OSM-sourced per regional cut keeps the combination a Collective Database. These are community guidelines, not legal advice.
- RECOMMENDATION: Use stable external IDs for segments and checkpoints (the OuterSpatial `u_id` lesson) so re-imports update records rather than duplicate them. Make re-import rules explicit. OuterSpatial silently keeps records missing from a new import and lets imports overwrite manual edits. MEMOUNT should instead flag "missing in new import" and "conflicts with curator edit" for curator review.
- INFERENCE: Even official geometry carries "reference only / not for navigation" disclaimers (DOC). MEMOUNT should label official geometry as authoritative about *which route is official*, not as navigation-grade accuracy, consistent with its no-false-precision rule.
- INFERENCE: OSM's own guidance assumes offline use lasting "several weeks or months" for stable features. This supports scoping the offline pack to slow-changing data.

### Gaps
- No authority standard for **checkpoint/waypoint lists** was found. OpenTrails covers trailheads, OuterSpatial covers points of interest, and GPX waypoints are generic.
- Licences of individual datasets on the DOC Open Spatial Data Portal (which may differ from the CC BY 3.0 NZ terms page) were not verified.
- NPS API terms of use were not fetched.
- No official guidance was found on combining government trail data with OSM beyond the OSMF community guidelines.

---

## 5. Role and permission models: park staff vs platform curators vs community; moderation; showing conflicts between community reports and official status

### Takeaway
The observed hierarchy is: **verified authority accounts** (land manager, protected area, DMO) > **platform staff** > **designated community reporters** (Trailforks trail-association reporters) > **general users**. Official status and community reports are stored and displayed separately:
- Community submissions that would change status wait for official review (Trailforks).
- Community condition posts expire (Outdooractive, 14 days).
- Closures are visually distinct (red) and propagate into route status and routing.

One peer-reviewed source warns that third-party channels that do not match agency messaging can set "inappropriate expectations".

### Cited Findings
- Trailforks/TORC: official designated trail reporters set status; user status updates "may be pending until a trail reporter can review it"; status is separate from condition; in gated areas the reporter is the land manager. — [TORC: Trail status updates via Trailforks](https://www.torc-nc.org/trail-status-updates/) [acc. 2026-09-25]
- Trailforks prefers "local admins from the local trail association" to manage content in their region. Trail reports can be given an issue status "Open, In Progress, Complete or Canceled", and admins can assign users to issues [UNVERIFIED: search summary; page returned 403] — [Trailforks: Trail Reporting feature](https://www.trailforks.com/about/features/trail_reporting/), [Trailforks: About](https://www.trailforks.com/about/) [acc. 2026-09-25]
- Outdooractive: official sources create notices and closures, while community members create "Current Conditions" (see Q3). A partner "verified badge" separates professional content from community content, and there is "Roles and rights management". — [Outdooractive: How do I create notices and closures?](https://www.outdooractive.com/en/knowledgepage/how-do-i-create-notices-and-closures-/67511668/), [Outdooractive Business: Module Glossary](https://business.outdooractive.com/module-glossary) [acc. 2026-09-25]
- DtP: DtP verifies the responsible person; entry can be delegated by power of attorney (see Q3). — [DtP: Data Entry](https://digitizetheplanet.org/data-entry/?lang=en) [acc. 2026-09-25]
- OuterSpatial data belongs to a "single owner organization". Manager UI edits can be overwritten by the next data import. — [OuterSpatial Data Guidebook](https://cdn.outerspatial.com/docs/outerspatial-data-guidebook.pdf) [acc. 2026-09-25]
- AllTrails land managers can edit trail information and send alerts; public materials do not describe how accounts are verified or moderated. — [AllTrails Public Lands](https://publiclands.alltrails.com/) [acc. 2026-09-25]
- Saunders et al. (2019), peer-reviewed:
  - Other trip-planning sources "can offer additional channels for communication… However, in the absence of agency input, these may sometimes establish inappropriate expectations."
  - The field study highlights "the consequences of inconsistency between what is presented by third-party communication not controlled by park management agencies (particularly pre-visit) and on-site safety communication by the management agency".
  - Source: [Saunders, Weiler, Scherrer & Zeppel (2019), preprint via SCU research portal](https://researchportal.scu.edu.au/esploro/fulltext/journalArticle/Best-practice-principles-for-communicating-safety/991012821537602368?repId=1267185910002368&mId=13109799250002368&institution=61SCU_INST); published version: [ScienceDirect, J. Outdoor Recreation and Tourism 25:132–142, doi:10.1016/j.jort.2018.01.006](https://www.sciencedirect.com/science/article/abs/pii/S2213078018300057) (returned 403) [acc. 2026-09-25]

### Inferences
- RECOMMENDATION: MEMOUNT's hierarchy (OFFICIAL > ADMIN VERIFIED > ACCREDITED EXPERT > COMMUNITY > SYSTEM INTERPRETATION) is stricter than, but compatible with, these platforms.
  - Adopt Trailforks' pattern that any non-official input touching status waits for review.
  - Adopt Outdooractive's time-to-live for community conditions.
  - Accredited experts correspond to Trailforks' designated reporters, limited to their licensed area. Unlike Trailforks, where the land manager may *be* the reporter, a MEMOUNT expert's input must never be labelled OFFICIAL.
- RECOMMENDATION: When community reports contradict official status (e.g., official OPEN, community says flooded), show both. Leave the official status untouched, label its source and last-reviewed time, and raise a curator task ("conflict reported, re-check source"). This follows Trailforks' separation of status and condition, and its help topic on mismatches.
- INFERENCE: MEMOUNT is itself one of the "third-party" channels Saunders et al. describe. Reproducing official wording, linking to the official source, and showing the source date reduce the risk of setting expectations the agency did not intend.

### Gaps
- No public documentation was found on how AllTrails or Outdooractive verify that an account belongs to a real authority, beyond "partner verification".
- No study or documented UI was found on showing contradictory community and official information, or on its effect on hikers' decisions.

---

## 6. Evidence of what works: studies or reports on communicating closures and alerts to visitors

### Takeaway
Evidence is thin and mostly descriptive.
- NPS 2023 visitor research (report dated October 2024): the official park website is the second most used pre-trip source (36%); 7% of visitors lacked information they needed; 58% used a device for GPS/navigation; 6–22% had no service, depending on the service.
- A peer-reviewed study of safety communication in Australian parks finds "little evidence" that pre-visit web information alone improves visitor safety, and recommends coordinated, multi-channel messaging across trip stages, including safe alternatives.
- A vendor claim (AllTrails / Olympic National Park: a 66% drop in search-and-rescue incidents) is correlational and not independently verified.

### Cited Findings
- *2023 Socioeconomic Research of National Park Service Visitors* (report dated October 2024), based on "5,363 post-trip visitor surveys… following fieldwork conducted in 24 NPS units in 2023":
  - "The most used resource was previous visits (40%). The second most common source was the official NPS website for the NPS unit (36%), followed by friends/relatives/word of mouth (27%), live here/grew up here (18%), and maps/brochures (15%)."
  - "93% of respondents said that they had the information… that they needed on their trip, while 7% indicated that there was information that they needed but did not have."
  - "Over half of respondents (59%) reported using their personal device to send/receive a text message, and about the same (58%) reported using it for GPS/navigation." GPS/navigation was rated "extremely important" by 29%.
  - "6% of respondents reported that they had no service for this [GPS/navigation]"; "Under a fifth of respondents (ranged from 6% to 22%, depending on the service) reported that they also had no service".
  - Source: [NPS IRMA: 2023 SEM visitor report (PDF)](https://irma.nps.gov/DataStore/DownloadFile/709436) [acc. 2026-09-25]
- Saunders, Weiler, Scherrer & Zeppel (2019), *Journal of Outdoor Recreation and Tourism* 25:132–142:
  - Best-practice principles developed for Queensland Parks and field-tested in Queensland and Victoria.
  - Principle set 2, "Co-ordination": "2a) Multiple media (including non-agency media) delivered at different stages of the visitor experience. In particular, communication delivered during trip planning could encourage appropriate expectations and preparation"; "2h) Clear statements of safe alternatives to popular but potentially hazardous activities or locations."
  - "it is relatively easy for park management agencies to incorporate pre-visit safety messages on their web-sites. Unfortunately there is little evidence that access to this information improves visitor safety. Influencing visitor behaviour may require a more complex and coordinated approach."
  - Figures warn of "Too many signs in one place?" and "Too many messages on one sign?"
  - Source: [Saunders et al. preprint (SCU research portal)](https://researchportal.scu.edu.au/esploro/fulltext/journalArticle/Best-practice-principles-for-communicating-safety/991012821537602368?repId=1267185910002368&mId=13109799250002368&institution=61SCU_INST); [ScienceDirect record](https://www.sciencedirect.com/science/article/abs/pii/S2213078018300057) [acc. 2026-09-25]
- [OLDER: 5 March 2024] "Olympic National Park… has seen a 66% decrease in search and rescue-related incidents on trails since partnering with AllTrails", attributed to being "better equipped to communicate with visitors". This is a vendor press-release claim with no method, baseline or control reported. — [PR Newswire: AllTrails Public Lands Program](https://www.prnewswire.com/news-releases/alltrails-announces-public-lands-program-with-new-trail-and-land-management-resources-302079247.html) [acc. 2026-09-25]
- A study of COVID-era trail behaviour (about 6000 observations) found that trail width, visitor density and signage influenced compliance, and that "Signage is inconsistently significant" [UNVERIFIED: search summary; not fetched] — [PMC9764864](https://pmc.ncbi.nlm.nih.gov/articles/PMC9764864/) [acc. 2026-09-25]
- American Trails guidance: when closing informal trails, "an educational component being critical to communicate a clear rationale for closure" [UNVERIFIED: search summary; not fetched] — [American Trails: Guidance for Managing Informal Trails](https://www.americantrails.org/resources/guidance-for-managing-informal-trails) [acc. 2026-09-25]
- [OLDER: pilot through 23 March 2021] The NPS Virtual Visitor Survey assessed its digital products; no findings on alerts are published on that page. — [NPS: Virtual Visitor Survey](https://www.nps.gov/subjects/digital/virtual-visitor-study.htm) [acc. 2026-09-25]

### Inferences
- INFERENCE: Even in the most visited US parks, 6–22% of visitors report no service for various device uses. This supports an offline pack for stable data. The 7% who lacked needed information point to value in pre-trip completeness. These are US figures and may not transfer to other countries.
- INFERENCE: Saunders et al. find little evidence that pre-visit web information alone improves safety. MEMOUNT should therefore present official status at **decision points**: trip planning, a day-of check, and a sync prompt before departure. When a trail is closed, it should pair the closure with clear alternatives (principle 2h), without inventing alternatives the authority has not stated.
- INFERENCE: Digital alert effectiveness is not rigorously evaluated in the public record. MEMOUNT should not claim safety outcomes for its official-status or offline features without its own measurements; the AllTrails figure is not usable as evidence.

### Gaps
- No controlled study was found comparing push, in-app and web alerts for park closures, or measuring how many hikers check official alerts before starting.
- Not fetched: USFS *Park signs and visitor behavior: A research summary* ([Treesearch 45278](https://research.fs.usda.gov/treesearch/45278)) and Winter (2006) on normative messages and off-trail hiking ([PSW PDF](https://www.fs.usda.gov/psw/publications/winter/psw_2006_winter002.pdf)). Both concern on-site signage rather than digital alerts [acc. 2026-09-25, search results only].
- No research was found on user comprehension of freshness labels ("last reviewed", "as of") or on alert fatigue in park apps.
