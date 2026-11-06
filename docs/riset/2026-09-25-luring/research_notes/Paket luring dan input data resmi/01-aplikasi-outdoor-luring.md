# How leading outdoor & navigation apps implement offline use (state as of 2026-09-25)

Scope and method. Apps covered with primary evidence: AllTrails, Gaia GPS, onX Backcountry, Komoot, FarOut, Strava, Google Maps, Organic Maps, OsmAnd, Outdooractive, CalTopo. Covered with weaker evidence: Wikiloc (help pages return HTTP 403 to automated fetch, so only search-result snippets), Garmin Explore (support FAQ pages 403, so only Garmin Forums), PeakVisor (only a 2018 vendor article). Garmin Connect (watch map manager) was not researched. **Every source below was accessed on 2026-09-25.** The Zendesk help centres of AllTrails, Gaia GPS, Komoot and onX block direct fetches (403). Their article text was read through the public Zendesk Help Center API (`/api/v2/help_center/en-us/articles/{id}.json`) using the same article IDs. The citations use the human-readable article URL, and the "upd." date is the article's own `updated_at`. Quotes come from a summarising fetch tool, so minor normalisation is possible. Evidence tags: **[official]** = vendor help/docs/pricing; **[press]** = vendor press release or trade press; **[forum]** = user posts on vendor forums (anecdotal); **[snippet]** = seen only in a search-result summary (the page itself was not read, so confidence is lower). Reddit could not be searched because it is blocked for this agent's user agent. App-store reviews were not read.

## Q1. What exactly is stored offline, and what is the unit of download (region, route, guide, trip)?

### Takeaway
There are two units. (a) **Area/region tile packs**: Google Maps, Organic Maps, OsmAnd, Gaia GPS, onX, CalTopo, PeakVisor, Wikiloc maps, Outdooractive map sections and AllTrails "Areas". (b) **Object-centric packs**: one trail, park, route or guide that bundles geometry, descriptive content and a map corridor. Examples are AllTrails trail/park downloads, Komoot routes, Outdooractive routes, FarOut guides, Wikiloc "Available offline" trails, Strava routes and Gaia's track/route buffer. The richest object packs are AllTrails Parks, Outdooractive routes and FarOut guides. They carry text, elevation data, photos and a *bounded snapshot* of community content. AllTrails, for example, stores the 10 most recent reviews per trail. This is the closest analogue to MEMOUNT's "offline field pack".

### Cited Findings
**AllTrails**
- Each trail is downloaded from its card: "Tap the download arrow in the lower-right corner of the card for the route". The user then picks map type(s) by tapping the download button again. Downloads are listed under Saved > Downloads. Plus/Peak only: "With Plus and Peak membership plans, you can download maps to your phone for offline use." — [official] [AllTrails Help: How to download maps to your phone for offline use, upd. 2026-06-17, acc. 2026-09-25](https://support.alltrails.com/hc/en-us/articles/37213318235028-How-to-download-maps-to-your-phone-for-offline-use)
- Offline Areas download "all the trails and map tiles within that area for offline use". "Each downloaded area can include up to 500 trails. If your selected area contains more than 500 trails, we'll prioritize the most popular ones in the area." — [official] [AllTrails Help: Download custom areas for offline use, upd. 2026-06-17, acc. 2026-09-25](https://support.alltrails.com/hc/en-us/articles/37758009767444-Download-custom-areas-for-offline-use)
- Offline Parks contain complete park maps, the best trail routes and essential park information including permits and fees. Offline trail pages contain descriptions, elevation data, trail lengths, hiking times, reviews and photos. "Each trail within the park will download the 10 most recent reviews so that you have the most up-to-date and relevant information." — [official] [AllTrails Help: Downloading parks for offline use, upd. 2026-08-11, acc. 2026-09-25](https://support.alltrails.com/hc/en-us/articles/37212750759956-Downloading-parks-for-offline-use)
- The Plus tier lists "Offline trails", "Offline parks" and "Offline areas" as three separate offline products. — [official] [AllTrails Help: The benefits of AllTrails premium membership, upd. 2026-09-16, acc. 2026-09-25](https://support.alltrails.com/hc/en-us/articles/37200882853140-The-benefits-of-AllTrails-premium-membership)

**Gaia GPS**
- The user drags a rectangle, chooses map sources and a resolution (Low/Medium/High), then names the map and assigns it to a folder. "Offline routing data downloads automatically." Overlapping downloads are allowed and duplicate tiles are not re-downloaded. — [official] [Gaia GPS Help: Download Maps for Offline Use, upd. 2026-07-28, acc. 2026-09-25](https://help.gaiagps.com/hc/en-us/articles/360047131513-Download-Maps-for-Offline-Use)
- The track/route/area download "downloads 5 map tiles on either side of your route or track". — [snippet] [Gaia GPS Help: Download Maps for a Track, Route, or Area in iOS, acc. 2026-09-25](https://help.gaiagps.com/hc/en-us/articles/115003524827-Download-Maps-for-a-Track-Route-or-Area-in-iOS)
- User objects are kept separate from map packs: "Your tracks, routes, and waypoints are not tied to your membership level". — [official] [Gaia GPS Help: Membership Options, upd. 2026-09-21, acc. 2026-09-25](https://help.gaiagps.com/hc/en-us/articles/115003524547-Membership-Options-Free-Premium-and-Premium-with-Outside)

**onX Backcountry**
- "Offline Maps save aerial imagery, topography, and trail data directly to your device." The user positions a box and chooses a detail level (Low/Medium/High, or "Topo (only)" for very large areas). "A single Offline Map has a size limit of 3 GB." Offline Maps "are saved to individual devices and can't be transferred." — [official] [onX Backcountry Help: How to use Offline Maps, upd. 2026-07-21, acc. 2026-09-25](https://onxbackcountry.zendesk.com/hc/en-us/articles/4420353475981-How-to-use-Offline-Maps)
- Premium includes "Offline Access to 3D Maps, Layers, Waypoints, Tracking". — [official] [onX Backcountry pricing page, acc. 2026-09-25](https://www.onxmaps.com/backcountry/app/pricing)

**Komoot**
- There are two units, individual planned routes and regional maps: "Once downloaded, the route is stored on your device and can be used for offline navigation at any time." Premium/World Pack users can also download custom areas anywhere. Downloads work only in the mobile app, not on the website. — [official] [Komoot Help: Download routes and maps for offline use, upd. 2026-09-24, acc. 2026-09-25](https://support.komoot.com/hc/en-us/articles/10356476920986-Download-routes-and-maps-for-offline-use)

**FarOut (formerly Guthook)**
- The unit is a trail guide. "The file size for our guides can be as small as 200KB or as large as 8MB, depending on the length of the trail and which offline maps and photos you choose to download." Offline map resolution is capped ("we cap the resolution of the offline maps"). Waypoint photos are optional downloads. Comments are available offline once downloaded. — [official] [FarOut Help, acc. 2026-09-25](https://faroutguides.com/help/)
- "Once you've completed the initial setup of the app, all of our trail guides work offline." Available offline: maps, elevation profile, waypoint list, and waypoint detail pages with photos. — [official] [FarOut Features, acc. 2026-09-25](https://faroutguides.com/features/)

**Outdooractive**
- A route download stores "the relevant map section showing the route as well as all the descriptive texts, details, and media", and navigation is included. A map section is at most about 40 × 40 km. The user can save all zoom levels or only "the last two zoom levels". "For navigation, you have to download the route. It is not enough to simply save a single section of the map." — [official] [Outdooractive: Use maps offline, acc. 2026-09-25](https://www.outdooractive.com/en/knowledgepage/saving-routes-and-maps-offline/37514067/)

**Strava**
- "Offline Route Maps: Routes can be downloaded to your phone for offline use." (subscription feature). — [official] [Strava Help: Strava Subscription Features, acc. 2026-09-25](https://support.strava.com/en-us/articles/15402044-strava-subscription-features)
- Press release of 2026-06-11: "download routes for use in low- or no-connectivity areas, so cell dead zones don't mean navigational dead ends" (subscriber-only). — [press] [Strava press release, 2026-06-11, acc. 2026-09-25](https://press.strava.com/articles/strava-adds-new-features-for-hiking-making-the-outdoor-experience-more-discoverable-navigable-and-social)

**Google Maps**
- The unit is a user-adjusted rectangle ("Select your own map") or the area around a searched place. Offline driving navigation works only if "the entire route is within the offline map". "When you're offline, transit, bicycling, or walking directions are unavailable." — [official] [Google Maps Help (Android): Download areas & navigate offline, acc. 2026-09-25](https://support.google.com/maps/answer/6291838?hl=en&co=GENIE.Platform%3DAndroid)

**Organic Maps**
- The unit is a region/country file built from OpenStreetMap. A maintainer said (2024) that map files contain OSM data in Organic Maps' own format "plus Wikipedia articles and elevation data". — [forum/maintainer, 2024-03] [GitHub organicmaps discussion #7516, acc. 2026-09-25](https://github.com/orgs/organicmaps/discussions/7516)
- Bookmarks and tracks can be imported and exported in "KML, KMZ, KMB, GPX or GeoJSON (JSON) format". — [official] [Organic Maps FAQ, acc. 2026-09-25](https://organicmaps.app/faq/)

**OsmAnd**
- The unit is a country or region. Resources are separate downloads: standard vector map, road-only map, contour lines, Wikipedia and others. "There is no single option to download all world maps at once." — [official] [OsmAnd docs: Download Maps, acc. 2026-09-25](https://osmand.net/docs/user/start-with/download-maps/)

**CalTopo**
- The unit is quadrants of a 15×15-minute grid, per layer, at standard/high/max resolution. A CalTopo "map" is a set of saved objects, and the base layers under it must be downloaded separately. — [official] [CalTopo Training: Offline Use, acc. 2026-09-25](https://training.caltopo.com/all_users/mobile/offline)

**PeakVisor** (old source)
- 3D satellite maps are downloaded per area via "Map Coverage". "There's no limit on the amount of downloads or storage time of 3D maps." — [official, **dated 2018-05-28**] [PeakVisor: Manage Offline 3D Maps, acc. 2026-09-25](https://peakvisor.com/en/news/manage-offline-3D-maps.html). An Alps-sized area takes about 1 GB. — [snippet, same page]

**Wikiloc**
- A trail is saved via the "Available offline" button. This needs Premium, except for Wikiloc ORG trails. Following a trail offline requires separately downloading the free offline map of the area first. — [snippet] [Wikiloc Help: Trails available offline, acc. 2026-09-25](https://help.wikiloc.com/article/908-trails-available-offline); [snippet] [Wikiloc Help: How to navigate a trail offline, acc. 2026-09-25](https://help.wikiloc.com/article/732-navigate-trail-offline); [snippet] [Wikiloc Help: Download offline maps, acc. 2026-09-25](https://help.wikiloc.com/article/470-download-offline-maps)

**Garmin Explore**
- An offline map is an area plus the chosen layers. "Without an Outdoor Maps+ subscription you get TopoActive maps (called Topo in the app), satellite imagery and the basemap." (Explore 4.0 era; posts dated ">1 year ago", i.e. 2024–2025.) — [forum] [Garmin Forums: Garmin Explore 4.0 Update Offline Maps..., acc. 2026-09-25](https://forums.garmin.com/apps-software/mobile-apps-web/f/garmin-explore-ios/388421/garmin-explore-4-0-update-offline-maps-functionality-only-for-purchased-maps)

### Inferences
- Trail-centric apps converge on an **object pack**: one trail, park or route with its geometry, a map corridor and descriptive data. General navigation apps use area packs. MEMOUNT treats the Trail as the decision unit (R-002), so a per-Trail or per-TripPlan pack fits its domain better than an area pack.
- Outdooractive states outright that a map section alone is not enough and only the route download enables navigation. Gaia builds a tile corridor (5 tiles on each side). Together this suggests MEMOUNT's pack = geometry + checkpoints/distances + elevation profile + corridor tiles + the user's plan/checklist, never tiles alone.
- Size discipline is common and explicit. FarOut keeps guides at 200 KB–8 MB with capped map resolution. Outdooractive offers "last two zoom levels". onX caps a map at 3 GB, and AllTrails caps an area at 500 trails. For low/mid Android phones, capping the zoom range around the trail corridor is the proven lever.
- AllTrails' "10 most recent reviews" is precedent for putting a **capped snapshot of dynamic community data** inside a pack.

### Gaps
- Exact zoom ranges are undocumented for almost every app. Only the resolution tiers (Gaia, onX, CalTopo) and Outdooractive's "last two zoom levels" were found.
- The fetched article does not say whether a single AllTrails *trail* download (as opposed to a park) includes reviews and photos offline.
- For Strava's phone offline routes, which base-map tiles are stored is not documented in the official pages read.
- Wikiloc's and Garmin's own help pages could not be read (403). PeakVisor's current (2025–2026) offline behaviour was not verified. Garmin Connect was not researched.

## Q2. Download UX: start, size estimate, progress, pause/resume, Wi-Fi-only, storage management, deletion, expiry/refresh, "ready for offline" verification

### Takeaway
The dominant flow is: pick object/area → choose layers/detail → progress → an explicit **downloaded-state indicator** on the object → a central Downloads/storage list with delete. Only CalTopo shows download size *and* free device space before downloading, and PeakVisor (2018) shows area size. Others enforce caps instead (AllTrails "Zoom in"; onX 3 GB; Gaia tile limits). Freshness mechanisms differ:
- Google Maps: expiry, plus auto-update on Wi-Fi within 15 days of expiry.
- onX: "Update Available" flag.
- OsmAnd: Updates tab.
- CalTopo: delete and re-download.
- Organic Maps: map data tied to app releases.

onX, CalTopo, Outdooractive and Garmin Explore offer an explicit **Offline Mode** toggle. No vendor documents an integrity check that proves a pack is complete.

### Cited Findings
- **AllTrails.**
  - Download state is a circle on the card: "Maps with a filled-in black circle are downloaded", "hollow circle … are not downloaded". "A synced map is not the same as a downloaded map." Troubleshooting says to check free storage and delete old maps. — [official] [AllTrails Help: Why can't I view my map when I'm offline?, upd. 2026-02-03, acc. 2026-09-25](https://support.alltrails.com/hc/en-us/articles/37214062033172-Why-can-t-I-view-my-map-when-I-m-offline)
  - If an area is too big, "Zoom in" replaces the download button. Areas cannot be renamed, and deleting an area removes all its trails. — [official] [AllTrails custom areas, upd. 2026-06-17](https://support.alltrails.com/hc/en-us/articles/37758009767444-Download-custom-areas-for-offline-use)
  - There is a Wi-Fi-only download preference under Settings > Map downloads (paraphrase), and individual trails cannot be removed from a park. — [official] [AllTrails parks, upd. 2026-08-11](https://support.alltrails.com/hc/en-us/articles/37212750759956-Downloading-parks-for-offline-use)
  - "If you're already offline and without an internet connection, you will not be able to download new maps." — [official] [AllTrails Help: How to use your downloaded maps for navigation, upd. 2026-02-03, acc. 2026-09-25](https://support.alltrails.com/hc/en-us/articles/37213942810132-How-to-use-your-downloaded-maps-for-navigation)
  - "before going offline, make sure you're logged into the app." — [snippet] [AllTrails download article](https://support.alltrails.com/hc/en-us/articles/37213318235028-How-to-download-maps-to-your-phone-for-offline-use)
- **Gaia GPS.**
  - Progress is shown on a Download Status screen. On iOS the app must stay in the foreground with the screen on during downloads. Users are advised to test downloads in airplane mode before a trip.
  - "MapBox sources have a 10,000 tile limit per download with a combined limit of 160,000 tiles for all MapBox sources."
  - A downloaded area cannot be expanded (download a new overlapping one instead), and individual layers cannot be deleted from a layered map. Deletion is via Archive (iOS) or long-press > trash (Android).
  - — [official] [Gaia GPS Help: Download Maps for Offline Use, upd. 2026-07-28, acc. 2026-09-25](https://help.gaiagps.com/hc/en-us/articles/360047131513-Download-Maps-for-Offline-Use)
  - Conflicting figure: a user post cites a "100,000 tile limit" per map. — [forum, created 2024-03-12, upd. 2026-08-28] [Gaia GPS community: Things to know about downloading maps, acc. 2026-09-25](https://help.gaiagps.com/hc/en-us/community/posts/22011749439511-Things-to-know-about-downloading-maps)
- **onX.**
  - A map saved on the Web Map must still be downloaded on the phone "before it's ready to use offline". A red-and-white outline or a download icon marks a map that "hasn't been downloaded to your device yet".
  - "Go Offline" in the Offline Maps menu switches to offline use, and the red "Offline" button switches back.
  - "Update Available" means "New map data is available for that area. Tap it to update before heading out."
  - If detail looks wrong, delete and re-download on "a strong Wi-Fi signal".
  - — [official] [onX: How to use Offline Maps, upd. 2026-07-21, acc. 2026-09-25](https://onxbackcountry.zendesk.com/hc/en-us/articles/4420353475981-How-to-use-Offline-Maps)
- **Komoot.** "There is no global offline switch in komoot. Content must be downloaded in advance, either as individual routes or as regional maps." — [official] [Komoot Help, upd. 2026-09-24, acc. 2026-09-25](https://support.komoot.com/hc/en-us/articles/10356476920986-Download-routes-and-maps-for-offline-use)
- **Google Maps.**
  - "When your offline maps expire in 15 days or less and you're connected to Wi-Fi, Google Maps tries to update the area automatically." An "Auto-update offline maps" toggle exists.
  - Maps can be stored on an SD card, but "If you change the way you save maps, you have to download the map again."
  - "Recommended maps" suggests downloads "Based on the places you're going to in the future". Some countries cannot be downloaded at all.
  - — [official] [Google Maps Help (Android), acc. 2026-09-25](https://support.google.com/maps/answer/6291838?hl=en&co=GENIE.Platform%3DAndroid)
  - iOS: "You must update the offline maps you've downloaded on your phone or tablet before they expire." The current help page states no fixed expiry period. — [official] [Google Maps Help (iOS), acc. 2026-09-25](https://support.google.com/maps/answer/6291838?hl=en&co=GENIE.Platform%3DiOS)
  - Older information: in 2018 Google reportedly extended offline retention from 30 days to 1 year. — [snippet, **dated 2018-08-25**; page returned 403] [gHacks, acc. 2026-09-25](https://www.ghacks.net/2018/08/25/google-maps-increased-offline-retention/)
- **OsmAnd.**
  - The Local tab gives "an overview of the storage usage for all OsmAnd data on your device", sorted largest first, with Type / Created / Size for each item.
  - Standard maps update monthly (2nd–5th of the month). Paid Live Updates run hourly, daily or weekly and use "about 2-4% of the full map size per month". "The date shown for a map in the Local tab does not reflect Live Updates."
  - An "Unsupported maps" notice appears for replaced regions, and "Delete all" asks for confirmation.
  - — [official] [OsmAnd docs: Maps & Resources, acc. 2026-09-25](https://osmand.net/docs/user/personal/maps-resources/)
- **Organic Maps.**
  - "Maps data is bound to app releases, which happen about once a month." The map creation date is shown in the About screen. A user request to show map dates during download was called "reasonable" by maintainers. — [forum/maintainer, **2024-03**] [GitHub #7516, acc. 2026-09-25](https://github.com/orgs/organicmaps/discussions/7516)
  - Conflicting claim: the website and stores say maps are "refreshed twice a month". — [snippet] [organicmaps.app, acc. 2026-09-25](https://organicmaps.app/)
  - FAQ: if outdated maps won't update, delete and re-download them, and check free space. — [snippet] [Organic Maps FAQ: I can't download (update) maps, acc. 2026-09-25](https://organicmaps.app/faq/map/cant-download-maps/)
- **CalTopo.**
  - A "Show Coverage For" dropdown shows layer availability per quadrant before download. "Download size is displayed at the bottom of the screen along with available device space."
  - Completed quadrants "turn blue for standard resolution and purple for high or max resolution".
  - Downloaded layers must be deleted and re-downloaded to pick up updates. The Offline Mode cloud icon turns orange when active.
  - — [official] [CalTopo Training: Offline Use, acc. 2026-09-25](https://training.caltopo.com/all_users/mobile/offline)
  - Offline Mode (mobile v1.21+, **2025-08-25**) makes the app "pull map layers ONLY from your downloaded storage", which removes lag time. — [official] [CalTopo blog: New Feature: Offline Mode, acc. 2026-09-25](https://blog.caltopo.com/2025/08/25/new-feature-offline-mode/)
- **Outdooractive.** The download icon appears only after zooming in far enough. "Overlapping areas are not downloaded twice." Downloads are managed in Settings > Downloads (iOS) or Storage Management (Android). An "offline mode" in the three-dot menu stops the app from trying to connect. — [official] [Outdooractive: Use maps offline, acc. 2026-09-25](https://www.outdooractive.com/en/knowledgepage/saving-routes-and-maps-offline/37514067/)
- **PeakVisor** (**2018**). Shows "the name of the selected area and its size required on your device" and progress in the status bar, and recommends Wi-Fi ("some mountains are very huge!"). — [official, dated 2018-05-28] [PeakVisor, acc. 2026-09-25](https://peakvisor.com/en/news/manage-offline-3D-maps.html)
- **Wikiloc.** Downloading over Wi-Fi is recommended because files are large. — [snippet] [Wikiloc Help: Download offline maps, acc. 2026-09-25](https://help.wikiloc.com/article/470-download-offline-maps). Dedicated help articles exist on switching between online and offline maps and on managing offline maps (titles only): [Wikiloc: switch online/offline maps](https://help.wikiloc.com/article/623-how-to-switch-between-online-and-offline-maps), [Wikiloc: manage offline maps](https://help.wikiloc.com/article/622-how-to-manage-your-offline-maps-on-the-wikiloc-app) (acc. 2026-09-25)
- **FarOut.** Downloads live in Settings > Manage Downloads (iOS) or Menu > Download Manager (Android). Content can be deleted and re-downloaded at any time. — [official] [FarOut Help, acc. 2026-09-25](https://faroutguides.com/help/)

### Inferences
- The most safety-relevant UX element is a **binary "ready offline" state on the object itself**: the AllTrails filled/hollow circle, the onX red-white outline and the CalTopo blue/purple quadrants. No vendor documents verifying that a pack is complete. Gaia's status screen is reported as unreliable (Q7). MEMOUNT can differentiate with a manifest + hash check and a single state such as "Siap luring / Belum lengkap", ideally with the pack's total size and the phone's free space shown beforehand, as CalTopo does.
- Explicit Offline Mode toggles exist because apps stall while trying a flaky network. CalTopo says its mode removes "lag time". This matches Indonesian trailheads, where signal is intermittent and weak rather than absent. For a PWA, "network-first with long timeouts" is the failure mode to avoid (INFERENCE; web mechanics belong to other researchers).
- For stable data, onX's visible "Update Available" plus Google's "auto-update on Wi-Fi before expiry" is a better model than silent expiry. Letting data expire *while in the field* would violate the owner's rule of never removing the user's safety pack unexpectedly (INFERENCE).

### Gaps
- Official pause/resume behaviour was found for no app. Gaia resume is documented only in a user post.
- A size estimate before download is not documented for AllTrails, Komoot, Gaia or Google.
- Google's current expiry length is not stated on its help page (the "1 year" figure is a 2018 report).
- No vendor documents an automatic completeness or integrity check.

## Q3. How dynamic data (closures, alerts, conditions, weather, community freshness) is handled offline, and how "last updated/last synced" is shown

### Takeaway
Most vendors **do not make live layers available offline**. Gaia: "Many maps are regularly updated and cannot be downloaded". onX weather requires a connection, and Google has no traffic offline. Where dynamic content does travel offline, it is a **bounded snapshot**: AllTrails downloads the 10 most recent reviews per park trail, and FarOut downloads comments that the user refreshes manually. Explicit staleness visuals are rare. The best documented example is onX's grey avalanche-forecast state: "hasn't updated the forecast for this area in more than 24 hours. Use caution." No official source was found that shows a "last synced at <time>" label for an offline pack.

### Cited Findings
- **Gaia GPS.** "Many maps are regularly updated and cannot be downloaded." "Check those weather and feature maps before you leave wifi or cell service." Current wildfires update daily and satellite detections several times a day. Smoke and air-quality layers are forecasts that "may not always be accurate". — [official blog, **dated 2022-09-13**] [Gaia GPS blog: wildfire and smoke maps, acc. 2026-09-25](https://blog.gaiagps.com/wildfire-and-smoke-maps/)
- **onX weather.** "If weather conditions aren't loading, make sure the App is up to date and that you're connected to the internet." — [official] [onX: Viewing weather conditions, upd. 2026-07-20, acc. 2026-09-25](https://onxbackcountry.zendesk.com/hc/en-us/articles/360051747492-Viewing-weather-conditions)
- **onX avalanche forecasts.** The source is the American Avalanche Association. The grey rating means "The American Avalanche Association hasn't updated the forecast for this area in more than 24 hours. Use caution." Disclaimer: "Forecasts show the expected frequency of avalanches for an area, but specific slopes within that area, especially those steeper than 35°, may be more dangerous than the surrounding region." — [official] [onX: Viewing Avalanche Forecasts and Incident Reports, upd. 2026-09-16, acc. 2026-09-25](https://onxbackcountry.zendesk.com/hc/en-us/articles/360056221571-Viewing-Avalanche-Forecasts-and-Incident-Reports)
- **AllTrails Parks.** The 10 most recent reviews per trail are downloaded "so that you have the most up-to-date and relevant information". Park information syncs when the page is visited within cell range, and offline users keep the data from their last connection (paraphrase). — [official] [AllTrails parks, upd. 2026-08-11, acc. 2026-09-25](https://support.alltrails.com/hc/en-us/articles/37212750759956-Downloading-parks-for-offline-use)
- **AllTrails Trail Conditions** (Plus/Peak). Covers "Temperature, Precipitation, Snow accumulation, Air quality, Sun and moon", with data from "Meteomatics.com and Tomorrow.io". The article says nothing about offline availability or how freshness is displayed. — [official] [AllTrails Help: Trail Conditions, upd. 2026-09-12, acc. 2026-09-25](https://support.alltrails.com/hc/en-us/articles/36933535617300-Trail-Conditions)
- **FarOut.** Comments are available offline after download and are refreshed manually: "Tap Comment updates and tap OK" (iOS) or "Force reload data" (Android). — [official] [FarOut Help, acc. 2026-09-25](https://faroutguides.com/help/). "Water waypoints offer the most up-to-date water information, gathered from trusted sources and checked by other users." — [official] [FarOut Features, acc. 2026-09-25](https://faroutguides.com/features/). Version 14.0.4 added comment filtering by type (water sources, camping, connectivity, conditions on trail, trail magic, lost-and-found). — [snippet] [FarOut App Store listing, acc. 2026-09-25](https://apps.apple.com/us/app/farout-guides/id605447532)
- **Komoot.** "Weather on route" is a Premium feature (no offline statement). Auto-rerouting, route editing and live tracking require internet. — [official] [Komoot plans, upd. 2026-09-25](https://support.komoot.com/hc/en-us/articles/10163258809626-komoot-plans-Maps-and-Premium); [Komoot download article, upd. 2026-09-24](https://support.komoot.com/hc/en-us/articles/10356476920986-Download-routes-and-maps-for-offline-use) (acc. 2026-09-25)
- **Google Maps.** "If you're offline when you drive, you can't get traffic info or alternate routes." — [official] [Google Maps Help (Android), acc. 2026-09-25](https://support.google.com/maps/answer/6291838?hl=en&co=GENIE.Platform%3DAndroid)
- **CalTopo.** With Offline Mode on, online overlays such as weather stations may still work if data is available. — [official] [CalTopo blog, 2025-08-25, acc. 2026-09-25](https://blog.caltopo.com/2025/08/25/new-feature-offline-mode/)

### Inferences
- Documented practice falls into three patterns:
  - (1) Hide live layers offline (Gaia, onX weather).
  - (2) Carry bounded snapshots (AllTrails reviews, FarOut comments).
  - (3) Degrade stale signals visually after a threshold (onX grey at >24 h).
- MEMOUNT's "last known as of <time>" rule is **stricter than any documented competitor** and matches pattern 3. A concrete adaptation: official status, weather and community reports inside the pack render in a distinct "stale" state, with an absolute timestamp, the source and the reference area (R-013). After a threshold they are never styled as current, and OPEN is never shown as a current fact offline (R-012).
- AllTrails words its snapshot as "the most up-to-date and relevant information". MEMOUNT should *not* copy that framing, because a snapshot must never imply freshness (R-005/R-013).
- The onX 24-hour threshold with a "Use caution" label is the clearest precedent for **time-based degradation of a safety signal**.

### Gaps
- How FarOut shows comment age (relative or absolute timestamps) could not be verified from the official pages read.
- Whether AllTrails Trail Conditions and alerts are visible offline, and how their age is shown, is not documented.
- No vendor documentation was found on showing official closures offline with their age.
- No official "last synced" indicator for offline packs was found for any app.

## Q4. Sync after reconnect: queued recordings, pending uploads, conflicts, "last synced"

### Takeaway
Every app records offline with the phone's GPS and uploads later. Strava documents the clearest queue states: "Pending Uploads" with Retry on iOS, "Unsync'd Activities" with Sync on Android, plus Delete for a corrupted upload. The reverse direction (online plan → device) is a known confusion point. AllTrails says "a synced map is not the same as a downloaded map", and onX web-saved maps must be downloaded on the phone. CalTopo's Offline Mode keeps syncing objects and tracks when data is available. **No vendor documents conflict resolution** for offline edits.

### Cited Findings
- **Strava.** iOS: "Your phone may show 'Pending Uploads.' Tap Retry to prompt your phone to attempt the sync again." Android: "Select Unsync'd Activities, then tap Sync". A corrupted activity can be deleted from "Unsynced Activities", and Android users can export a .GPX file for manual upload. — [official] [Strava Help: Troubleshooting Syncing, acc. 2026-09-25](https://support.strava.com/en-us/articles/15402026-troubleshooting-syncing)
- **AllTrails.** Desktop-created routes sync automatically to the app, but "A synced map is not the same as a downloaded map. You must download a map to your phone to use it offline" — [official] [AllTrails: Why can't I view my map when I'm offline?, upd. 2026-02-03, acc. 2026-09-25](https://support.alltrails.com/hc/en-us/articles/37214062033172-Why-can-t-I-view-my-map-when-I-m-offline). "Load the map into Navigate, not just in Saved >> Downloads." — [official] [AllTrails: Troubleshooting common issues, upd. 2026-08-11, acc. 2026-09-25](https://support.alltrails.com/hc/en-us/articles/360057736711-Troubleshooting-common-issues)
- **onX.** Offline maps saved on the Web Map appear in the phone's list but must be downloaded there. To fix a missing map, log in to the same account on both devices, reopen the app, or pull to refresh the list. — [official] [onX: How to use Offline Maps, upd. 2026-07-21, acc. 2026-09-25](https://onxbackcountry.zendesk.com/hc/en-us/articles/4420353475981-How-to-use-Offline-Maps)
- **CalTopo.** Offline Mode "will still attempt to sync new map objects and tracks to the server" — [official] [CalTopo Training, acc. 2026-09-25](https://training.caltopo.com/all_users/mobile/offline). It lets the user "stay connected to data for other purposes, such as receiving text messages or syncing live tracking" — [official] [CalTopo blog, 2025-08-25, acc. 2026-09-25](https://blog.caltopo.com/2025/08/25/new-feature-offline-mode/)
- **Gaia GPS.** Tracks recorded on a connected Garmin device can "automatically sync" to the Gaia account. — [official] [Gaia GPS: Conserve Battery Life on iOS, upd. 2026-04-27, acc. 2026-09-25](https://help.gaiagps.com/hc/en-us/articles/115003639168-Conserve-Battery-Life-on-iOS)
- **FarOut.** Comments sync when the phone reconnects to Wi-Fi or cell service (paraphrase). — [official] [FarOut Help, acc. 2026-09-25](https://faroutguides.com/help/). Check-ins send to followers only when the phone has Wi-Fi or cell service. — [snippet] [FarOut App Store listing, acc. 2026-09-25](https://apps.apple.com/us/app/farout-guides/id605447532)

### Inferences
- MEMOUNT should keep a visible per-item queue for anything the user creates offline (checklist ticks, notes, condition reports), using states like Strava's (pending → sent / failed + retry). A report created offline must keep its **capture time**, not its upload time. Otherwise a 3-day-old community observation would appear fresh on sync, which would break the freshness rule and could mislead other hikers (R-010/R-013).
- "Synced ≠ downloaded" is a documented source of user error. MEMOUNT should expose one state per pack ("on this phone, complete as of <time>") rather than two ambiguous ones.

### Gaps
- No vendor documents conflict handling when the same object is edited on two devices while offline.
- AllTrails' behaviour when uploading a recording made offline is not described in the fetched articles.
- No "last synced" timestamp UI is documented in the official sources read.

## Q5. Battery and GPS modes for field use; how accuracy is shown

### Takeaway
Dedicated "battery saver" modes are rare. Vendors mostly give advice: airplane mode, dim the screen, turn off the compass, or record on a watch. OsmAnd is the most configurable. It records every 5 s by default, turns GPS on per point at intervals of 30 s or more, and has minimum-accuracy and minimum-displacement filters. **Android OEM battery optimisation killing background recording** is a recurring, officially acknowledged failure (OsmAnd, Gaia's Huawei article, Organic Maps FAQ).

### Cited Findings
- **Gaia GPS (iOS).** Advice: "Lower the brightness"; restrict location services to Gaia; "Record your hike in Airplane Mode to block your device from searching for cellular data"; collapse the compass band (Settings > Map Layout > Toggle Compass Off); record on a Garmin device instead to "significantly conserve battery life". — [official] [Gaia GPS: Conserve Battery Life on iOS, upd. 2026-04-27, acc. 2026-09-25](https://help.gaiagps.com/hc/en-us/articles/115003639168-Conserve-Battery-Life-on-iOS). Related Android articles: [Conserve Battery Life on Android](https://help.gaiagps.com/hc/en-us/articles/115003638328-Conserve-Battery-Life-on-Android) (title only); Huawei users should disable energy optimisations that interfere with recording — [snippet] [Gaia: Huawei Devices Track Recording Issues](https://help.gaiagps.com/hc/en-us/articles/360010457434-Huawei-Devices-Track-Recording-Issues); [Gaia: Android - Track recording inaccurate or has spikes](https://help.gaiagps.com/hc/en-us/articles/360035349774-Android-Track-recording-inaccurate-or-has-spikes) (title only) (all acc. 2026-09-25)
- **OsmAnd.** Logging interval: "The default is 5 seconds." Filters: a minimum-accuracy filter ("Filters out location points below a minimum accuracy indication threshold") and minimum displacement (5 m suggested). Auto-split: new segment after a 6-minute gap, new track after 2 hours, new file on date change. "If the notification is removed, Android will automatically stop the recording." The app also opens a dialog pointing to Android battery-optimisation settings. — [official] [OsmAnd docs: Trip Recording, acc. 2026-09-25](https://osmand.net/docs/user/plugins/trip-recording/). At intervals of 30 s or more GPS is switched on only for each point, about 1.2 %/h battery at a 30 s interval, with "some noticeable effect on accuracy". — [snippet] [OsmAnd docs: Track recording issues, acc. 2026-09-25](https://osmand.net/docs/user/troubleshooting/track-recording-issues/)
- **Organic Maps.** The FAQ has an entry "Why tracks are not recorded reliably in background on Android?" — [official] [Organic Maps FAQ, acc. 2026-09-25](https://organicmaps.app/faq/)
- **Wikiloc.** Recommends downloading the free offline maps and following trails offline to save battery. — [snippet] [Wikiloc: How to navigate a trail offline, acc. 2026-09-25](https://help.wikiloc.com/article/732-navigate-trail-offline)
- **onX / FarOut.** GPS works without cell service. "Your GPS location updates normally even in airplane mode" — [official] [onX, upd. 2026-07-21](https://onxbackcountry.zendesk.com/hc/en-us/articles/4420353475981-How-to-use-Offline-Maps). "If you are in airplane mode or do not have any internet connection, the app will use the device's built-in GPS." — [official] [FarOut Help](https://faroutguides.com/help/) (acc. 2026-09-25)

### Inferences
- For a web PWA like MEMOUNT, the realistic battery mechanism is "pack downloaded + airplane mode + screen-off guidance". Reliable background GPS tracking in a browser is a separate platform question that other researchers should cover (INFERENCE).
- Showing accuracy honestly (an accuracy radius in metres, and never snapping the user onto the trail line) aligns with R-005. OsmAnd's accuracy filters show that noisy points are an expected, handled condition.

### Gaps
- None of the fetched official sources documents how any app *visually* shows GPS accuracy (circle, metres, HDOP).
- AllTrails, Komoot and Strava battery or recording-interval settings were not found in the sources read.

## Q6. Paywall position: which offline features are free vs paid

### Takeaway
In trail apps, offline is the **main monetisation lever**:
- Paid: AllTrails (Plus/Peak), Gaia GPS (Premium, and a lapse locks already-downloaded maps), Strava (subscriber), Outdooractive (Pro), CalTopo (any paid tier), Wikiloc trail downloads (Premium).
- onX: one free offline map; unlimited offline maps need Premium.
- Free or cheap: Google Maps; Organic Maps; OsmAnd (limited count, unlimited with Pro/Maps+); Garmin Explore (TopoActive, satellite and basemap free); Komoot (one free region plus one-time region purchases, or Premium); FarOut (lifetime per-guide purchase, or FarOut Unlimited).

### Cited Findings
- **AllTrails.** Offline trails/parks/areas, Trail Conditions and wrong-turn alerts are Plus features. Peak adds advanced map layers, a route assistant and custom routes. — [official] [AllTrails benefits, upd. 2026-09-16, acc. 2026-09-25](https://support.alltrails.com/hc/en-us/articles/37200882853140-The-benefits-of-AllTrails-premium-membership). Peak launched 2025-05-12. — [press/snippet] [TechCrunch, 2025-05-12, acc. 2026-09-25](https://techcrunch.com/2025/05/12/alltrails-debuts-a-80-year-membership-that-includes-ai-powered-smart-routes)
- **Gaia GPS.** Free members: "You cannot download maps to use when offline and out of service." On lapse: "You will automatically be switched over to the free membership level… you will no longer be able to access your downloaded maps or download any new maps." — [official] [Gaia GPS: Membership Options, upd. 2026-09-21, acc. 2026-09-25](https://help.gaiagps.com/hc/en-us/articles/115003524547-Membership-Options-Free-Premium-and-Premium-with-Outside)
- **onX Backcountry.** Free: "save one offline map". Premium/Elite: "unlimited offline maps". Prices: Premium $29.99/yr, Elite $99.99/yr (a promotional first-year price was shown). — [official] [onX pricing, acc. 2026-09-25](https://www.onxmaps.com/backcountry/app/pricing)
- **Strava.**
  - Offline routes are subscriber-only, while Beacon is free in the Strava app. — [official] [Strava Subscription Features, acc. 2026-09-25](https://support.strava.com/en-us/articles/15402044-strava-subscription-features)
  - Apple Watch route following and watch route sync are free; offline routes and off-route alerts are subscriber-only. — [press] [Strava press release 2026-06-11, acc. 2026-09-25](https://press.strava.com/articles/strava-adds-new-features-for-hiking-making-the-outdoor-experience-more-discoverable-navigable-and-social)
  - On Apple Watch (Jan 2026), offline route following needs a Strava subscription. Komoot's watch offline navigation "is available to all komoot users with an unlocked map region—Premium is not required". — [press] [9to5Mac, 2026-01-28, acc. 2026-09-25](https://9to5mac.com/2026/01/28/apple-watch-offline-map-routes-debut-for-strava-and-komoot-apps/)
- **Komoot.**
  - Current help: new users get "one free single region of your choice". Single region, region bundle and World Pack are "one-time purchases" that include offline functionality and lifelong updates. Premium adds worldwide maps, Weather on route and more. — [official] [Komoot: komoot plans, upd. 2026-09-25, acc. 2026-09-25](https://support.komoot.com/hc/en-us/articles/10163258809626-komoot-plans-Maps-and-Premium)
  - Purchased regions are permanent. Region access that came *through Premium* ends when Premium ends. — [official] [Komoot: Find and manage regions, upd. 2026-09-23, acc. 2026-09-25](https://support.komoot.com/hc/en-us/articles/10168872476186-Find-and-manage-regions)
  - **Older, 2025:** from 2025-02-27, new users need Premium to sync routes to external devices, while map unlocks remain purchasable and a free trial region is given. — [3rd-party, **2025-03**] [DC Rainmaker, acc. 2026-09-25](https://www.dcrainmaker.com/2025/03/komoots-expanded-paywalls-trying-to-make-sense-of-it.html)
  - **Conflict:** a search summary and the headline of [the5krunner, 2025-03-11](https://the5krunner.com/2025/03/11/komoot-confirms-we-dont-want-any-new-customers-in-ridiculous-pricing-move/) suggest new users could not buy region packs. This conflicts with DC Rainmaker and with Komoot's own help as of Sep 2026, so the current official help is treated as authoritative.
- **Outdooractive.** Offline is "Included in: Pro". — [official] [Outdooractive, acc. 2026-09-25](https://www.outdooractive.com/en/knowledgepage/saving-routes-and-maps-offline/37514067/)
- **CalTopo.** Using the mobile app offline requires "a paid subscription". — [official] [CalTopo Training, acc. 2026-09-25](https://training.caltopo.com/all_users/mobile/offline). Offline Mode is available on all paid tiers. — [official] [CalTopo blog, 2025-08-25, acc. 2026-09-25](https://blog.caltopo.com/2025/08/25/new-feature-offline-mode/)
- **OsmAnd.** "OsmAnd Pro or Maps+" users "can download an unlimited number of maps", which implies a limit on the free tier. — [official] [OsmAnd: Download Maps, acc. 2026-09-25](https://osmand.net/docs/user/start-with/download-maps/). Live Updates are a paid feature. — [official] [OsmAnd: Maps & Resources, acc. 2026-09-25](https://osmand.net/docs/user/personal/maps-resources/)
- **FarOut.** Guide purchases are lifetime and include "all future updates to the app and to the trail info for free". — [official] [FarOut Help, acc. 2026-09-25](https://faroutguides.com/help/). A "FarOut Unlimited" subscription also exists. — [official] [FarOut Features, acc. 2026-09-25](https://faroutguides.com/features/)
- **Wikiloc.** Trail downloads need Premium, except Wikiloc ORG trails. Offline maps are free. — [snippet] [Wikiloc: Trails available offline](https://help.wikiloc.com/article/908-trails-available-offline); [Wikiloc: Download offline maps](https://help.wikiloc.com/article/470-download-offline-maps) (acc. 2026-09-25)
- **Organic Maps.** Free, with no tracking or personal data collection. — [snippet] [organicmaps.app, acc. 2026-09-25](https://organicmaps.app/)

### Inferences
- Gaia's behaviour is a **safety anti-pattern** that MEMOUNT should explicitly avoid: packs already on the phone become inaccessible when an entitlement lapses. The AllTrails advice to be logged in before going offline shows the same dependency on session state. RECOMMENDATION: once a MEMOUNT pack is on the device, reading it should not depend on a live session, token refresh or subscription check. This fits the owner's decision that safety-relevant offline data stays free.
- Free offline in Google Maps, Organic Maps and Komoot's single region shows that offline can be a baseline expectation rather than a premium feature, and Indonesian beginner users are likely to compare against Google Maps (INFERENCE).

### Gaps
- PeakVisor's current pricing for offline maps and OsmAnd's exact free download limit are not stated in the sources read.

## Q7. Known complaints and failure modes of offline features

### Takeaway
The recurring failures are mostly **silent**: the user believes a pack is present, but in the field it is missing, locked, expired, incomplete or not loaded. The best-documented cases:
1. Downloads stall and progress is misreported (Gaia; a detailed user post).
2. The download needs the app in the foreground with the screen on (Gaia iOS; official).
3. "Synced vs downloaded" and "loaded into Navigate" confusion (AllTrails; official troubleshooting).
4. Losing the entitlement locks packs (Gaia; official).
5. An offline-only setting is not retained, so the app reverts to online (Garmin Explore; forum).
6. Empty packs ("0 layers 0 bytes") followed by a crash (Garmin Explore; forum).
7. Expiry surprises (Google; community thread titles).
8. Storage full (AllTrails, Organic Maps).
9. Old downloads become incompatible after an app update (Gaia post; Organic Maps FAQ).
10. Background recording killed by battery optimisation (Android; several vendors).

### Cited Findings
- **Gaia GPS (user post).**
  - The progress screen is unreliable: "THE DOWNLOAD PROGRESS PAGE PROVIDES COMPLETELY WRONG, CONFUSING, EVEN DECEPTIVE NUMBERS AND THE PROGRESS BARS ARE EQUALLY WRONG".
  - Downloads stall: "many downloads will stall, requiring the user to babysit the long downloads", and a long-press resume often gives no confirmation.
  - "If you downloaded maps a long time ago, the latest software may not work with it."
  - The author spent "four full days" on one state-wide download.
  - — [forum, created 2024-03-12, upd. 2026-08-28; anecdotal] [Gaia GPS community, acc. 2026-09-25](https://help.gaiagps.com/hc/en-us/community/posts/22011749439511-Things-to-know-about-downloading-maps)
- **Gaia GPS (official).** On iOS the app must stay in the foreground with the screen on while downloading. — [official] [Gaia: Download Maps for Offline Use, upd. 2026-07-28](https://help.gaiagps.com/hc/en-us/articles/360047131513-Download-Maps-for-Offline-Use). Downloaded maps are locked when the subscription lapses. — [official] [Gaia: Membership Options, upd. 2026-09-21](https://help.gaiagps.com/hc/en-us/articles/115003524547-Membership-Options-Free-Premium-and-Premium-with-Outside) (acc. 2026-09-25)
- **AllTrails.** "If you aren't able to see your maps offline, they may not be downloaded", with checks for storage and the filled circle — [official] [AllTrails: Why can't I view my map when I'm offline?, upd. 2026-02-03](https://support.alltrails.com/hc/en-us/articles/37214062033172-Why-can-t-I-view-my-map-when-I-m-offline). Other troubleshooting entries: "My map disappears from Navigate when I'm offline", "Map not loaded into Navigate correctly", and "I have an AllTrails paid subscription and can't download maps", for which the fix is to delete and reinstall — [official] [AllTrails: Troubleshooting common issues, upd. 2026-08-11](https://support.alltrails.com/hc/en-us/articles/360057736711-Troubleshooting-common-issues) (acc. 2026-09-25)
- **Garmin Explore.** After version 4.0 one user saw only an Outdoor Maps+ purchase link and no free layers. Another saved an offline map with "0 layers 0 bytes", after which the app crashed. Reinstalling restored access. — [forum, 2024–2025] [Garmin Forums thread 388421, acc. 2026-09-25](https://forums.garmin.com/apps-software/mobile-apps-web/f/garmin-explore-ios/388421/garmin-explore-4-0-update-offline-maps-functionality-only-for-purchased-maps). A bug report is titled "'Offline Maps Only' setting not retained & app defaults to online maps" (the app reverts to online after a restart). — [forum/snippet] [Garmin Forums thread 421026, acc. 2026-09-25](https://forums.garmin.com/apps-software/mobile-apps-web/f/garmin-explore-android/421026/bug-offline-maps-only-setting-not-retained-app-defaults-to-online-maps)
- **Google Maps.** Community thread titles (content not readable by the tool) include "Offline maps expire on the same day after download" ([thread 63097531](https://support.google.com/maps/thread/63097531/offline-maps-expire-on-the-same-day-after-download?hl=en)), "Google offline maps seem to expire even though they say they are good for a year" ([thread 63977852](https://support.google.com/maps/thread/63977852/google-offline-maps-seem-to-expire-even-though-they-say-they-are-good-for-a-year?hl=en)) and "my offline maps are still expired in 30 days" ([thread 22691215](https://support.google.com/maps/thread/22691215/hi-my-offline-maps-are-still-expired-in-30-days-is-there-any-setting-for-the-one-year-expire-date?hl=en)). — [forum, titles only, dates not read; acc. 2026-09-25]
- **Strava.** Community threads (fetch returned 403, titles only) include "off line use of maps seems impossible (?!?)" ([9891](https://communityhub.strava.com/archived-strava-features-chat-5/off-line-use-of-maps-seems-impossible-9891)), "Offline maps iphone not working" ([9167](https://communityhub.strava.com/archived-strava-features-chat-5/offline-maps-iphone-not-working-9167)), "Use of downloaded routes for offline use has become useles again but in a new way" ([13403](https://communityhub.strava.com/archived-strava-features-chat-5/use-of-downloaded-routes-for-offline-use-has-become-useles-again-but-in-a-new-way-13403)) and "Need to be able to Download Base maps!" ([idea 20225](https://communityhub.strava.com/t5/ideas/need-to-be-able-to-download-base-maps/idi-p/20225)). — [forum, titles only; acc. 2026-09-25]
- **Organic Maps.** Downloads fail on network or firewall errors or when free space runs out, and very outdated maps may need deleting and re-downloading. — [snippet] [Organic Maps FAQ: I can't download (update) maps, acc. 2026-09-25](https://organicmaps.app/faq/map/cant-download-maps/). Users found it unintuitive that map updates require an app update. — [forum, 2024] [GitHub #7516, acc. 2026-09-25](https://github.com/orgs/organicmaps/discussions/7516)
- **OsmAnd.** "If the notification is removed, Android will automatically stop the recording." — [official] [OsmAnd Trip Recording, acc. 2026-09-25](https://osmand.net/docs/user/plugins/trip-recording/)
- **Komoot pricing backlash (2025).** Commentators characterised the change as "we don't want any new customers". — [3rd-party, 2025-03] [DC Rainmaker](https://www.dcrainmaker.com/2025/03/komoots-expanded-paywalls-trying-to-make-sense-of-it.html); [the5krunner, 2025-03-11](https://the5krunner.com/2025/03/11/komoot-confirms-we-dont-want-any-new-customers-in-ridiculous-pricing-move/) (acc. 2026-09-25)

### Inferences
- The shared root cause is that the pack's actual usability is **not verified at the moment it matters**. Vendors push the check onto users: Gaia says to test in airplane mode, and AllTrails says to check the black circle while still online. MEMOUNT can productise this as a pre-departure "Uji mode luring" check. It would open the pack from local storage only and report exactly what is and isn't available (geometry, checkpoints, elevation, plan, checklist, tiles), each with its as-of time.
- Session-, entitlement- and app-version-dependence (Gaia lapse; Gaia old downloads; Organic Maps data tied to app versions; AllTrails login advice) is a second root cause. RECOMMENDATION: version the pack schema and keep old packs readable after an app update until a new pack is successfully written. Never delete the old pack first.

### Gaps
- Reddit (r/hiking, r/Ultralight, r/CampingandHiking) could not be searched because it is blocked for this agent. App-store reviews were not read.
- No direct evidence was found of iOS evicting offline maps from native apps. Browser/PWA storage eviction is a separate topic for other researchers.
- The Google community threads could not be read (JS-rendered). Dates and Google's responses are unknown.

## Q8. Which mechanisms are best evidenced or most praised, and which are criticised

### Takeaway
**Best evidenced** (officially documented by three or more vendors):
- Object packs that bundle content (AllTrails, Outdooractive, FarOut, Komoot, Wikiloc).
- An explicit downloaded-state indicator plus a central Downloads/storage list with delete (AllTrails, onX, CalTopo, OsmAnd, Outdooractive, FarOut).
- Caps on area or size (AllTrails, onX, Gaia, Outdooractive).
- Wi-Fi guidance or a Wi-Fi-only setting (AllTrails, onX, PeakVisor, Wikiloc, Google).
- An explicit offline-mode toggle (onX, CalTopo, Outdooractive, Garmin).
- Record locally and upload later through a queue (Strava, CalTopo, FarOut).

**Distinctive and worth copying:**
- Google: auto-update on Wi-Fi before expiry.
- onX: "Update Available" flag and a grey stale-forecast state with a 24 h threshold.
- CalTopo: size plus free-space display and per-layer coverage check.
- AllTrails: capped snapshot of the 10 most recent reviews.
- OsmAnd: storage overview sorted by size.

**Criticised:** unreliable progress and stalling downloads (Gaia), entitlement-locked packs (Gaia), expiry surprises (Google), "synced vs downloaded" confusion (AllTrails), settings that don't persist (Garmin) and paywalling (Komoot 2025).

### Cited Findings
- The cross-vendor evidence is in Q1–Q7 above. The individual citations are not repeated here.
- Third-party "best offline hiking app 2026" comparison blogs (e.g. HikeLoad) praise Gaia's "rock-solid offline performance". These are SEO-style sites whose testing method is not stated, so they are **low credibility**. — [3rd-party/snippet] [HikeLoad, acc. 2026-09-25](https://hikeload.com/blog/best-hiking-navigation-apps-offline-2026)

### Inferences
- Amati–tiru–modifikasi mapping for MEMOUNT (INFERENCE/RECOMMENDATION, not product decisions):
  - **Tiru (copy mechanism):**
    - AllTrails/Outdooractive object pack (geometry, text, elevation, media, corridor tiles).
    - CalTopo "size + free space before download".
    - onX "Update Available" and Offline Mode toggle.
    - Google "auto-refresh stable data on Wi-Fi before it ages out".
    - Strava "Pending/Retry" queue.
    - onX grey stale state.
    - FarOut manual "update comments before leaving signal".
  - **Modifikasi (adapt to MEMOUNT rules):**
    - Every dynamic item carries an absolute "as of" time, the source and the reference area, and is never styled as current. Official status offline says "last known OPEN as of <time>" and never "OPEN" alone (R-012/R-013).
    - Snapshots are never framed as "most up-to-date".
    - Readiness must not depend on login or subscription.
    - Completeness is verified by the app, not by the user.
    - Packs are never auto-deleted by expiry while the user may be in the field.
  - **Hindari (avoid):** paywalled safety data, entitlement-locked packs, silent expiry, and progress UIs that can misreport.
- No competitor documents showing *all* dynamic data with its age inside an offline pack. MEMOUNT's approved design would be a genuine differentiator, but there is also no external evidence yet on how users understand "last known" labels. It should be usability-tested (see `docs/PROTOKOL-UJI-KEGUNAAN.md`) rather than assumed (OPEN ISSUE).

### Gaps
- No credible tested review (e.g. OutdoorGearLab, Outside/GearJunkie, The Verge) was retrieved within the time budget. Claims about which mechanism is "praised" rest on vendor documentation and forums, not independent testing.
- No quantitative data was found (for example, the share of users who reach a trail with an incomplete pack).
