# Data gunung dan jalur pendakian Indonesia: sumber, lisensi, mutu data, dan program data bertahap (per 23 September 2026)

> **Cara membaca.** Semua hitungan diambil langsung oleh peneliti pada 23 Sep 2026 (UTC)
> lewat layanan publik. Teks kuerinya ada di §1h supaya bisa diulang. Label bukti mengikuti
> CLAUDE.md R-023: **FACT**, **INFERENCE**, **RECOMMENDATION**, **OPEN ISSUE**, ditambah
> **UNVERIFIED** untuk klaim yang belum bisa dicek ke sumber primer di sesi ini. "Sekunder"
> berarti media atau blog, bukan penerbit datanya. Konteks proyek (tabel `mountains`,
> `trails`, `official_statuses`, `data_sources`, seed 17 gunung, 29 jalur tanpa geometri)
> berasal dari brief tugas, bukan dari riset ini.

## 1. Sumber otoritatif dan terbuka untuk daftar gunung, puncak, dan gunung api (nama, koordinat, elevasi)

### Takeaway
Riset ini tidak menemukan satu pun sumber resmi yang terbuka, bisa diunduh massal, dan memuat seluruh gunung
Indonesia beserta koordinat dan elevasinya. Tiga daftar terbuka terbesar (Wikidata 28.125 item gunung,
OSM 23.792 node puncak/gunung api, GeoNames sekitar 28 ribu baris) sebagian besar berasal dari **hulu yang
sama**, yaitu NGA GEOnet Names Server (AS). Karena itu, kesepakatan di antara ketiganya bukan verifikasi
independen. Elevasi jarang ada di sana, dan kalaupun ada, asalnya sering tidak jelas. Otoritas Indonesia
yang relevan ada dua. BIG mengurus nama baku lewat Gazeter RI/SINAR; edisi 2021 baru memuat 97 toponim
relief. PVMBG/MAGMA mengurus gunung api: 69 gunung api dipantau dengan level aktivitas, dan media menyebut
127 gunung api aktif.

### Cited Findings

#### 1a. Tabel hitungan (semua diambil 23 Sep 2026 kecuali disebut lain)

| Sumber | Yang dihitung | Jumlah | Punya koordinat | Punya elevasi | Cara memperoleh |
|---|---|---|---|---|---|
| Wikidata | item `P17=Indonesia` dengan `P31/P279*` gunung (Q8502) | 28.125 | 28.026 (99,6%) | 304 (1,1%) | kueri Q1 |
| Wikidata | item `P17=Indonesia` dengan `P31/P279*` gunung api (Q8072) | 144 | 139 | 135 | kueri Q2 |
| Wikidata | item `P17=Indonesia` yang punya Smithsonian volcano ID (P1886) | 159 | – | – | kueri Q4 |
| Wikidata | item gunung (Q8502 dan subkelasnya) yang punya artikel id.wikipedia | 403 | 336 | 202 | kueri Q10/Q17 |
| Wikidata | item yang langsung bertipe bukit (Q54050) / summit (Q207326) | 4.230 / 2 | – | – | kueri Q13 |
| OpenStreetMap | node `natural=peak` | 23.568 | semua (node) | 4.107 (17,4%) | Overpass |
| OpenStreetMap | node `natural=volcano` | 224 | semua | 141 | Overpass |
| GeoNames | kode fitur MT / HLL / MTS / PK / VLC | 19.091 / 8.524 / 352 / 78 / 19 | semua | 13.471 / 5.574 / – / 26 / 15 (kolom `elevation`) | dump `ID.zip` |
| MAGMA (PVMBG) | gunung api yang tampil dengan level aktivitas | 69 | tidak dihitung | tidak dihitung | halaman tingkat aktivitas |
| BIG, Gazeter RI 2021 | toponim relief (gunung, bukit, dsb.) | 97 dari 21.430 nama | – | – | artikel surveyor BIG |
| Smithsonian GVP | gunung api Holosen untuk Indonesia | **tidak bisa dihitung** (HTTP 403) | – | – | lihat 1g |

#### 1b. Wikidata (data terstruktur CC0)
- FACT: Ada 28.125 item dengan `P17=Q252` (Indonesia) yang merupakan instance gunung atau subkelasnya. Dari jumlah itu, 28.026 (99,6%) punya koordinat (P625) dan hanya 304 (1,1%) punya elevasi (P2044) — [Wikidata Query Service, kueri Q1, 2026-09-23 09:49 UTC](https://query.wikidata.org/)
- FACT: Dari 28.125 item tersebut, 27.899 (99,2%) punya GeoNames ID (P1566) dan 27.894 (99,2%) punya artikel di ceb.wikipedia (bahasa Cebuano). Yang punya artikel id.wikipedia hanya 403 (1,4%) — [WDQS, kueri Q9–Q11, 2026-09-23 09:54 UTC](https://query.wikidata.org/)
- FACT: Dari 403 item gunung yang punya artikel id.wikipedia: 336 punya koordinat, 202 punya elevasi, 133 punya GVP ID, dan 253 punya lokasi administratif (P131) — [WDQS, kueri Q17, 2026-09-23 10:04 UTC](https://query.wikidata.org/)
- FACT: Ada 220 item gunung dengan elevasi ≥1.000 m setelah normalisasi satuan — [WDQS, kueri Q12](https://query.wikidata.org/)
- FACT: Gunung api (Q8072 dan subkelasnya) dengan `P17=Indonesia` berjumlah 144 item: 139 punya koordinat, 135 punya elevasi, 124 punya Smithsonian volcano ID (P1886), dan 133 punya artikel id.wikipedia — [WDQS, kueri Q2 & Q15](https://query.wikidata.org/)
- FACT: 159 item dengan `P17=Indonesia` punya Smithsonian volcano ID. Secara keseluruhan ada 162 item yang GVP ID-nya berawalan "26", dan 159 di antaranya ber-`P17=Indonesia` — [WDQS, kueri Q4 & Q19, 2026-09-23 10:08 UTC](https://query.wikidata.org/). (UNVERIFIED: bahwa awalan "26" adalah kode wilayah GVP untuk Indonesia.)
- FACT: Pada gabungan kelas gunung/gunung api/summit/bukit, hanya 243 item yang pernyataan elevasinya punya referensi apa pun (`prov:wasDerivedFrom`). Pada gabungan yang sama, 21.522 item punya P131 — [WDQS, kueri Q6 & Q7](https://query.wikidata.org/)
- FACT: Kueri yang menggabungkan semua kelas sekaligus (Q3), kueri konflik elevasi gabungan (Q5), dan kueri distribusi kelas (Q8) terkena *time-out* WDQS setelah sekitar 60 detik. Karena itu hitungan dipecah per kelas — [WDQS](https://query.wikidata.org/)
- FACT: "All structured data in the main, property and lexeme namespaces is made available under the Creative Commons CC0 License" — [Wikidata:Licensing](https://www.wikidata.org/wiki/Wikidata:Licensing)

#### 1c. OpenStreetMap (ODbL 1.0)
- FACT: Pada data OSM per `timestamp_osm_base` 2026-09-22T08:45:51Z, di dalam area batas negara Indonesia (relasi 304751 → area 3600304751) terdapat 23.568 node `natural=peak` (0 way/relasi) dan 224 node `natural=volcano`. Dari node peak, 4.107 punya tag `ele` dan 20.853 punya `name`; dari node volcano, 141 punya `ele` — [Overpass API](https://overpass-api.de/api/interpreter)
- FACT: Dari gabungan 23.792 node peak+volcano, 19.192 (80,7%) bertag `source=NGA-GNS`, 502 `source=OpenTopoMap`, 375 `source=BIG`, 117 `source=cyclemap`, 64 `source=gunungbagging.com` (ditambah 31 bertag `NGA-GNS;gunungbagging.com`), 56 `opentopomap/SRTM`, dan 49 `SRTM`. Sebanyak 4.387 node (18,4%) bertag `wikidata=*`, 2.739 node (11,5%) tidak bernama, dan 5 nilai `ele` tidak numerik — [Overpass API, unduhan node lengkap 2026-09-23 10:04 UTC](https://overpass-api.de/api/interpreter)
- FACT: Di Indonesia ada 107 relasi `route=hiking` dan 206 way `highway=path` yang bertag `sac_scale` — [Overpass API](https://overpass-api.de/api/interpreter)
- FACT: Data yang dikembalikan Overpass membawa pernyataan "The data is made available under ODbL" — [Overpass API](https://overpass-api.de/api/interpreter)

#### 1d. GeoNames dan hulunya, NGA GNS
- FACT: Dump `ID.zip` (10.389.517 byte) diunduh 2026-09-23 10:00 UTC. Isinya pada kelas T: MT (gunung) 19.091 baris, HLL (bukit) 8.524, MTS (pegunungan) 352, HLLS 37, PK (puncak) 78, VLC (gunung api) 19, CRTR 1, CLDA 1. Tanggal modifikasi baris MT/PK/VLC berkisar 1994-01-29 sampai 2025-11-18 — [GeoNames dump ID.zip](https://download.geonames.org/export/dump/ID.zip)
- FACT: Kolom `elevation` terisi pada 13.471 dari 19.091 baris MT (70,6%), 5.574 dari 8.524 HLL, 26 dari 78 PK, dan 15 dari 19 VLC. Kolom `elevation` didefinisikan "in meters, integer", sedangkan `dem` adalah "srtm3 or gtopo30, average elevation of 3''x3'' (ca 90mx90m) or 30''x30''". Sumber per baris tidak dinyatakan — [GeoNames readme](https://download.geonames.org/export/dump/readme.txt)
- FACT: Dari 13.471 baris MT yang punya elevasi, 169 sama persis dengan nilai DEM, dan 7.642 lainnya berselisih ≤20 m dari DEM — [analisis peneliti atas ID.zip](https://download.geonames.org/export/dump/ID.zip)
- FACT: Lisensi GeoNames: "This work is licensed under a Creative Commons Attribution 4.0 License" — [GeoNames readme](https://download.geonames.org/export/dump/readme.txt)
- FACT: Pencocokan dilakukan dengan menghapus awalan Gunung/G./Bukit/Puncak/Mount dari nama, lalu mencari node OSM bernama sama dalam radius sekitar 0,3°. Hasilnya, 13.941 dari 19.188 baris GeoNames MT/PK/VLC (72,7%) punya padanan OSM. Jarak median 0,00 km, p75 0,00 km, p90 0,01 km, maksimum 23,45 km; hanya 3,7% berjarak >1 km — [GeoNames](https://download.geonames.org/export/dump/ID.zip) + [Overpass](https://overpass-api.de/api/interpreter)
- FACT (dari ringkasan hasil pencarian; teks asli NGA belum dibuka): GNS memuat sekitar 4,0 juta fitur. Tidak ada syarat atau batasan lisensi untuk penggunaannya. Sitasi yang dianjurkan: "Toponymic information is based on the Geographic Names Database, containing official standard names approved by the United States Board on Geographic Names and maintained by the National Geospatial-Intelligence Agency" — [Indiana University Libraries](https://libraries.indiana.edu/geonet-names-server); [NGA Geographic Names Server](https://geonames.nga.mil/)

#### 1e. Badan Informasi Geospasial (nama rupabumi, RBI, Ina-Geoportal)
- FACT: PP No. 2 Tahun 2021 tentang Penyelenggaraan Nama Rupabumi ditetapkan 6 Januari 2021. PP ini mewajibkan penyusunan Gazeter Republik Indonesia (GRI), yaitu daftar nama rupabumi baku yang dapat diakses publik lewat sistem informasi nama rupabumi kelolaan BIG dan disebarkan ke forum UNGEGN — [SINAR BIG, berita PP 2/2021](https://sinar.big.go.id/detail/berita/penerbitan-peraturan-pemerintah-nomor-2-tahun-2021-tentang-penyelenggaraan-nama-rupabumi); [JDIH BPK](https://peraturan.bpk.go.id/Details/157954/pp-no-2-tahun-2021)
- FACT: Gazeter RI 2021 memuat "total nama rupabumi sejumlah 21.430", terdiri dari pulau 17.000, "toponim relief (gunung, bukit, dan sebagainya) sejumlah 97", toponim perairan 169, dan wilayah administrasi 548. Artikel ini ditulis Aji Putra Perdana, surveyor BIG, 26 Jan 2022 — [Suara Yoursay](https://yoursay.suara.com/kolom/2022/01/26/162521/mengenal-gazeter-republik-indonesia-2021)
- FACT: Ada edisi Gazeter yang lebih baru dalam bentuk PDF, yaitu "Edisi 1-Tahun 2022" dan "Volume 4 Tahun 2024". Keduanya tidak dibuka di sesi ini — [Gazeter 2022](https://sinar.big.go.id/assets/document/gazeter/gazeter_1674633462.pdf); [Gazeter Vol. 4 2024](https://sinar.big.go.id/assets/document/gazeter/gazeter_1744615986.pdf)
- FACT: Beranda SINAR menyediakan pencarian alfabetis, per wilayah, per koordinat, per nama dan kategori, serta fungsi gazeter. Beranda juga memuat pernyataan "Data nama rupabumi yang diunggah pada aplikasi ini akan menjadi milik BIG". Unduhan massal, API, atau lisensi tidak terlihat di beranda (diambil 23 Sep 2026) — [SINAR](https://sinar.big.go.id/)
- FACT (sekunder, dari tutorial): Unduhan data RBI di Ina-Geoportal mensyaratkan akun. Syarat dan ketentuan ada di `/portal-web/termcondition`. Format yang tersedia SHP, GDB, DXF, DWG, dan DGN. Helpdesk: helpdesk.nsdi@big.go.id — [Ina-Geoportal Unduh](https://tanahair.indonesia.go.id/portal-web/unduh); [Lintas Bumi](https://lintasbumi.com/2018/08/12/cara-mengunduh-peta-rupa-bumi-indonesia-rbi-dari-ina-geoportal/)
- FACT: Halaman syarat dan ketentuan Ina-Geoportal hanya berupa aplikasi JavaScript ("You need to enable JavaScript to run this app"), sehingga isinya **tidak terbaca** oleh peneliti — [Ina-Geoportal termcondition](https://tanahair.indonesia.go.id/portal-web/termcondition)
- FACT: API katalog CKAN Ina-Geoportal dikueri 2026-09-23 10:11 UTC. Pencarian "toponimi" menghasilkan 400 dataset yang terpecah per kabupaten/kota (mis. Kab. Temanggung, Kab. Semarang, Kota Manado), dan semua dataset dalam sampel memiliki `license_title` kosong. Pencarian "gunung api" menghasilkan 21 dataset, termasuk "AAtlas_Sebaran_GunungApi" dari BIG (dimodifikasi 2024-11-19). Pencarian "jalur pendakian" menghasilkan 0 dataset — [CKAN Ina-Geoportal](https://tanahair.indonesia.go.id/sdi/api/3/action/package_search?q=toponimi)
- FACT: Dataset "Peta Rupabumi Indonesia Skala 1:25.000 Wilayah LAMPUNG03" di data.go.id diterbitkan BIG (Pusat Pemetaan Rupabumi dan Toponim), tersedia sebagai WMS, dan terakhir diubah 05-09-2024. Lisensinya tidak dicantumkan, dan halamannya menulis "Data berikut ini masih dalam proses pemenuhan Prinsip SDI" — [data.go.id](https://data.go.id/dataset/dataset/peta-rupabumi-indonesia-skala-1-25-000-wilayah-lampung03-referensispasial)

#### 1f. PVMBG / MAGMA Indonesia (otoritas status gunung api)
- FACT: Halaman "Tingkat Aktivitas Gunung Api" dibaca sekitar 2026-09-23 10:00 UTC dan memuat **69** gunung api. Level IV Awas: 0. Level III Siaga: 3 (Merapi, Semeru, Sinabung). Level II Waspada: 24 (Anak Krakatau, Anak Ranakah, Awu, Banda Api, Bromo, Bur Ni Telong, Dempo, Dukono, Gamalama, Ibu, Ili Lewotolok, Iya, Karangetang, Kerinci, Lewotobi Laki-laki, Lokon, Marapi, Raung, Rinjani, Sangeangapi, Slamet, Soputan, Sorikmarapi, Tambora). Level I Normal: 42 (antara lain Agung, Arjuno Welirang, Ciremai, Gede, Ijen, Kelud, Lewotobi Perempuan, Papandayan, Salak, Sumbing, Sundoro, Tangkuban Parahu). Halaman ringkasan tidak menampilkan stempel waktu, dan tautan API maupun unduhan massal tidak terlihat. Catatan hak cipta: "Copyright 2026 © All Rights Reserved. MAGMA Indonesia" — [MAGMA, tingkat aktivitas](https://magma.esdm.go.id/v1/gunung-api/tingkat-aktivitas)
- FACT (sekunder): Indonesia punya 127 gunung api aktif, 69 di antaranya dipantau intensif 24 jam — [Radar Palu/JawaPos](https://radarpalu.jawapos.com/nasional/2604140024/indonesia-punya-127-gunung-api-aktif-69-dipantau-intensif-24-jam)
- FACT: Layanan publik MAGMA mencakup informasi pengamatan visual dan instrumental, level aktivitas, dan rekomendasi (Volcanic Activity Report/VAR), Peta Kawasan Rawan Bencana (KRB), dan VONA untuk penerbangan. MAGMA dikembangkan PVMBG sejak 2015 — [MAGMA, edukasi](https://magma.esdm.go.id/v1/edukasi/magma-indonesia)
- FACT (sekunder): Ada empat level: I Normal, II Waspada, III Siaga, IV Awas. Level III dan IV dikaitkan dengan ancaman bagi area sekitar pusat erupsi, dan pada Level IV juga bagi permukiman — [Liputan6](https://www.liputan6.com/hot/read/8285778/arti-status-normal-waspada-siaga-awas-pada-aktivitas-gunung-berapi)

#### 1g. Smithsonian Global Volcanism Program (GVP)
- FACT: Pada 2026-09-23 10:01–10:02 UTC, halaman daftar negara, halaman info basis data, dan ekspor Excel di volcano.si.edu menolak akses (HTTP 403). Endpoint WFS `webservices.volcano.si.edu` tidak merespons — [GVP daftar Indonesia](https://volcano.si.edu/volcanolist_countries.cfm?country=Indonesia); [GVP VOTW info](https://volcano.si.edu/gvp_votw.cfm)
- UNVERIFIED: Menurut ringkasan mesin pencari, format sitasinya "Global Volcanism Program, 2025. [Database] Volcanoes of the World (v. 5.3.6; 26 May 2026). Distributed by Smithsonian Institution, compiled by Venzke, E.". Tahun dan tanggal rilis dalam sitasi itu tidak konsisten — [GVP VOTW info](https://volcano.si.edu/gvp_votw.cfm)
- UNVERIFIED dan saling bertentangan: situs agregator memberi angka 101, 120, 125, dan 138 gunung api "GVP/Indonesia", masing-masing dengan definisi berbeda. Jangan pakai angka-angka ini — [VolcanoDB](https://volcanodb.com/volcanoes-in-indonesia); [VolcanoAtlas](https://www.volcanosatlas.com/volcanoes-in-indonesia); [QuakeMate](https://quakemate.app/volcanoes-in/indonesia)

#### 1h. Kueri yang dipakai (untuk diulang)
Endpoint `https://query.wikidata.org/sparql` (Accept: text/csv, User-Agent deskriptif).
```sparql
# Q1 gunung (Q8502 + subkelas) di Indonesia
SELECT (COUNT(DISTINCT ?item) AS ?n) (COUNT(DISTINCT ?wc) AS ?withCoord) (COUNT(DISTINCT ?we) AS ?withElev) WHERE {
  ?item wdt:P17 wd:Q252 ; wdt:P31/wdt:P279* wd:Q8502 .
  OPTIONAL { ?item wdt:P625 [] . BIND(?item AS ?wc) }
  OPTIONAL { ?item wdt:P2044 [] . BIND(?item AS ?we) } }
# Q2 = Q1 dengan wd:Q8072 dan tambahan OPTIONAL { ?item wdt:P1886 [] . BIND(?item AS ?wg) }
# Q4 SELECT (COUNT(DISTINCT ?item) AS ?n) WHERE { ?item wdt:P1886 [] ; wdt:P17 wd:Q252 . }
# Q6  gabungan kelas (VALUES ?cls { wd:Q8502 wd:Q8072 wd:Q207326 wd:Q54050 }) ; p:P2044 ?st . ?st prov:wasDerivedFrom [] .
# Q7  gabungan kelas yang sama ; wdt:P131 [] .
# Q9  ... wdt:P31/wdt:P279* wd:Q8502 ; wdt:P1566 [] .            (GeoNames ID)
# Q10 ... ?art schema:about ?item ; schema:isPartOf <https://id.wikipedia.org/> .
# Q11 ... ?art schema:about ?item ; schema:isPartOf <https://ceb.wikipedia.org/> .
# Q12 ... wdt:P31/wdt:P279* wd:Q8502 ; p:P2044/psn:P2044/wikibase:quantityAmount ?m . FILTER(?m >= 1000)
# Q13 VALUES ?cls { wd:Q207326 wd:Q54050 } ?item wdt:P17 wd:Q252 ; wdt:P31 ?cls .   (tipe langsung, GROUP BY ?cls)
# Q17 = Q10 ditambah OPTIONAL untuk P625, P2044, P1886, P131
# Q18 VALUES ?label { "Gunung Semeru"@id "Gunung Slamet"@id ... } ?item rdfs:label ?label ; p:P2044 ?st .
#     ?st psv:P2044 [ wikibase:quantityAmount ?m ] . OPTIONAL { ?st prov:wasDerivedFrom/pr:P854 ?refurl }
#     (tanpa filter kelas, sehingga desa/kecamatan/kabupaten homonim ikut muncul)
# Q19 SELECT (COUNT(DISTINCT ?item) AS ?gvp26) WHERE { ?item wdt:P1886 ?g . FILTER(STRSTARTS(STR(?g),"26")) }
# Q16 item dengan >1 nilai elevasi ternormalisasi:
SELECT ?item ?label (GROUP_CONCAT(DISTINCT STR(?m); separator="|") AS ?values) WHERE {
  { SELECT ?item (COUNT(DISTINCT ?m2) AS ?k) WHERE { ?item wdt:P17 wd:Q252 ; wdt:P31/wdt:P279* wd:Q8502 ;
      p:P2044/psn:P2044/wikibase:quantityAmount ?m2 . } GROUP BY ?item } FILTER(?k > 1)
  ?item p:P2044/psn:P2044/wikibase:quantityAmount ?m .
  OPTIONAL { ?item rdfs:label ?label FILTER(LANG(?label)="id") } } GROUP BY ?item ?label
```
Overpass (`https://overpass-api.de/api/interpreter`):
```
[out:json][timeout:300];
area(id:3600304751)->.id;
node["natural"="peak"](area.id); out count;
node["natural"="volcano"](area.id); out count;
node["natural"="peak"]["ele"](area.id); out count;
node["natural"="peak"]["name"](area.id); out count;
node["natural"="volcano"]["ele"](area.id); out count;
relation["route"="hiking"](area.id); out count;
way["highway"="path"]["sac_scale"](area.id); out count;
```
GVP (belum berhasil dijalankan dari sini):
`https://webservices.volcano.si.edu/geoserver/GVP-VOTW/ows?service=WFS&version=1.0.0&request=GetFeature&typeName=GVP-VOTW:Smithsonian_VOTW_Holocene_Volcanoes&outputFormat=application/json&CQL_FILTER=Country='Indonesia'`.
Cara manualnya: buka volcano.si.edu, ekspor Holocene Volcano List (Excel), lalu saring Country = Indonesia.
Ulangi langkah yang sama untuk daftar Pleistocene.

### Inferences
- INFERENCE: Wikidata, OSM, dan GeoNames bukan tiga saksi independen. Buktinya ada tiga. Pertama, 80,7% node puncak OSM bertag `source=NGA-GNS`. Kedua, 99,2% item gunung Wikidata punya GeoNames ID sekaligus artikel ceb.wikipedia, yang merupakan pola impor bot dari GeoNames (nama bot/proyeknya UNVERIFIED). Ketiga, pasangan GeoNames–OSM bernama sama berjarak median 0,00 km, artinya koordinatnya disalin dari sumber yang sama. Untuk provenance, ketiganya perlu diperlakukan sebagai satu garis keturunan COMMUNITY.
- INFERENCE: Angka Q1 (28.125) hampir sama dengan jumlah GeoNames MT+HLL+MTS+PK (28.045). Artinya, "semua gunung di Wikidata" sebagian besar adalah bukit dan gunung kecil hasil impor gazeter AS, bukan tujuan pendakian. Target "informasi gunung lengkap se-Indonesia" perlu definisi operasional (lihat §4).
- INFERENCE: Elevasi adalah atribut paling langka dan paling lemah provenance-nya. Wikidata punya elevasi untuk 1,1% item dan hanya 243 item dengan referensi. OSM punya elevasi untuk 17,4% puncak. GeoNames punya elevasi untuk 70,6% baris MT, tetapi tanpa sumber per baris. Dengan aturan "missing tetap missing", sebagian besar entri katalog harus tampil tanpa elevasi pada tahap awal.
- INFERENCE: Untuk gunung api, identitas sebaiknya diikat ke tiga kunci: nama/unit PVMBG (untuk status resmi), nomor GVP (lewat Wikidata P1886, CC0), dan QID Wikidata. Nomor GVP tersedia di Wikidata untuk 159 item Indonesia, sehingga crosswalk bisa dibangun tanpa mengakses situs GVP.
- INFERENCE: Otoritas nama (BIG) dan otoritas status gunung api (PVMBG) tidak menyediakan API atau unduhan massal yang ditemukan di sesi ini. Kolom resmi hampir pasti harus diisi lewat kurasi admin, dengan `source_url` per entri dan permintaan data formal.

### Gaps
- Jumlah gunung api Indonesia di GVP (Holosen dan Pleistosen), versi basis data, dan ketentuan pakainya belum diketahui karena akses diblokir 403. Cara memperolehnya ada di §1h.
- Belum diketahui apakah Gazeter RI setelah 2021 (edisi 2022 dan Vol. 4 2024) menambah toponim relief di atas 97, dan apakah entrinya memuat koordinat atau elevasi. PDF-nya belum dibuka.
- Belum ditemukan unduhan massal, API, atau lisensi resmi untuk data SINAR/Gazeter.
- Belum ditemukan API publik MAGMA maupun ketentuan penggunaan ulang selain catatan "All Rights Reserved".
- Akurasi posisi GNS untuk Indonesia belum diketahui. Tidak ditemukan sumber yang menyatakannya.
- DEMNAS (DEM nasional BIG) sebagai rujukan pemeriksaan elevasi belum diriset, termasuk lisensi dan resolusinya.
- Angka 127 gunung api aktif baru berasal dari media sekunder. Dokumen primer PVMBG, termasuk definisi kelompok yang dihitung, belum dibuka.

## 2. Sumber resmi informasi pendakian dan jalur, geometri jalur resmi, larangan scraping platform komersial, dan alternatif legal

### Takeaway
Status akses pendakian resmi datang dari Kementerian Kehutanan (Ditjen KSDAE) dan tiap Balai (Besar) Taman
Nasional. Status itu diumumkan lewat berita, surat edaran, dan sistem booking per taman nasional. Tidak
ditemukan API ataupun dataset geometri jalur resmi yang terbuka. Sejak 1 Sep 2026, SE.4/KSDAE/SKSDAE/KSA.02.02/B/8/2026
menutup sementara pendakian di 9 lokasi dan menetapkan 4 lokasi "buka terbatas". Cakupannya bertingkat:
taman nasional, gunung tertentu di dalam taman nasional, dan jalur tertentu. AllTrails dan Strava secara
eksplisit melarang scraping. Wikiloc (menurut ringkasan pencarian) melarang ekstraksi sistematis dan
penggunaan komersial tanpa izin tertulis. Jalur legal yang tersedia: rekaman GPS milik sendiri, kemitraan
tertulis dengan Balai TN, OSM di bawah ODbL, dan kontribusi pengguna dengan pemberian lisensi eksplisit.

### Cited Findings

#### 2a. Status akses resmi (Kemenhut / Balai TN)
- FACT: Kemenhut menerbitkan pengaturan status pendakian gunung di kawasan konservasi pada Senin, 31 Agustus 2026, melalui Surat Edaran **SE.4/KSDAE/SKSDAE/KSA.02.02/B/8/2026** bertanggal 31 Agustus 2026. SE ini berlaku mulai 1 September 2026 **tanpa tanggal berakhir** — [Kemenhut](https://www.kehutanan.go.id/news/antisipasi-puncak-kemarau-kemenhut-atur-status-pendakian-gunung-di-kawasan-konservasi-berbasis-manajemen-risiko)
- FACT: Lokasi yang **tutup sementara** (risiko tinggi): Gunung Semeru (di TN Bromo Tengger Semeru), TN Kerinci Seblat, TN Gunung Ciremai, TN Tambora, TN Manusela, TN Bukit Baka Bukit Raya, TN Gunung Gede Pangrango, TN Gunung Leuser, dan TN Lorentz (khusus jalur Ilaga, Tsinga, Sugapa, Ugimba). Lokasi yang **buka terbatas** (risiko sedang): TN Gunung Rinjani, TN Gunung Merbabu, SM Dataran Tinggi Hyang (Argopuro), dan TN Gunung Halimun Salak. Booking daring dihentikan untuk lokasi yang ditutup, dan publik diminta menghubungi kantor TN atau memantau kehutanan.go.id — [Kemenhut](https://www.kehutanan.go.id/news/antisipasi-puncak-kemarau-kemenhut-atur-status-pendakian-gunung-di-kawasan-konservasi-berbasis-manajemen-risiko); [ANTARA, 31 Agu 2026](https://www.antaranews.com/berita/5718707/menhut-tutup-sementara-pendakian-9-taman-nasional-rawan-karhutla)
- FACT: Ringkasan otomatis mesin pencari yang diterima peneliti menyebut Gunung Merapi dan Gunung Merbabu termasuk yang ditutup. Halaman resmi Kemenhut dan artikel ANTARA yang dibuka langsung **tidak** mencantumkan Merapi dan menempatkan Merbabu di kategori "buka terbatas". Kesalahannya berasal dari ringkasan otomatis, bukan dari artikel media tertentu — [Kemenhut](https://www.kehutanan.go.id/news/antisipasi-puncak-kemarau-kemenhut-atur-status-pendakian-gunung-di-kawasan-konservasi-berbasis-manajemen-risiko); [ANTARA](https://www.antaranews.com/berita/5718707/menhut-tutup-sementara-pendakian-9-taman-nasional-rawan-karhutla)
- FACT (sekunder): Rinjani punya enam jalur resmi: Aik Berik, Sembalun, Senaru, Tete Batu, Timbanuh, dan Torean-Senange — [Tempo](https://www.tempo.co/gaya-hidup/mengenal-6-jalur-resmi-pendakian-gunung-rinjani-1915231). Menurut ringkasan pencarian, pemesanan wajib lewat aplikasi resmi e-Rinjani; artikel persis yang menjadi sumbernya tidak pasti — [Tempo](https://www.tempo.co/hiburan/tahapan-yang-harus-dilalui-sebelum-mendaki-gunung-rinjani-1895346); [Visit Lombok Island](https://www.visitlombokisland.com/cara-booking-online-rinjani/). Klaim bahwa e-Rinjani "tersinkronisasi dengan database pusat Kemenhut" UNVERIFIED — [Infinity Himalaya](https://infinity-himalaya.id/wisata-trekking-pegunungan/gunung-rinjani/)
- FACT: Sistem booking resmi BBTN Gunung Gede Pangrango berada di booking.gedepangrango.org. Halamannya dirender dengan JavaScript, dan hasil curl tidak memuat teks status (diambil 23 Sep 2026) — [booking.gedepangrango.org](https://booking.gedepangrango.org/). Menurut sumber sekunder, pintu masuknya Cibodas, Gunung Putri, dan Selabintana — [Kumbaya](https://blog.kumbaya.id/2026/06/05/panduan-simaksi-online-pendakian-2026/); [Infogepang](https://infogepang.com/peta-jalur-pendakian-gunung-gede-pangrango/)
- FACT: Domain booking Semeru yang muncul di hasil pencarian (`bookingsemeru.bromotenggersemeru.org`) gagal di-resolve DNS (ENOTFOUND), dan halaman SOP `bromotenggersemeru.id/peraturan/semeru` membalas HTTP 403 pada 23 Sep 2026 — [Booking Semeru](https://bookingsemeru.bromotenggersemeru.org/); [SOP Semeru](https://bromotenggersemeru.id/peraturan/semeru)
- UNVERIFIED dan saling bertentangan (sekunder): satu sumber menyebut Semeru dijadwalkan buka 10 April 2026, sumber lain menyebut buka 24 April 2026 dengan batas sampai Ranu Kumbolo — [Anak Gunung](https://anakgunung.com/jalur-pendakian-gunung-semeru-dijadwalkan-buka-10-april-2026/); [Mounture](https://mounture.com/berita/syarat-pendakian-gunung-semeru-2026-booking-online-kuota-terbatas-wajib-ikuti-sop/)

#### 2b. Geometri jalur resmi
- FACT: Pencarian "jalur pendakian" di katalog CKAN Ina-Geoportal menghasilkan 0 dataset. Pencarian "taman nasional" menghasilkan 3 dataset, semuanya batas kawasan di tingkat daerah (mis. "Kawasan Hutan TN Gunung Merapi Kabupaten Sleman Tahun 2018") dalam format WMS/WFS dengan lisensi kosong (kueri 2026-09-23 10:11 UTC) — [CKAN Ina-Geoportal](https://tanahair.indonesia.go.id/sdi/api/3/action/package_search?q=jalur%20pendakian)
- FACT: API CKAN di data.go.id tidak tersedia di jalur `/api/3/action/package_search` (HTTP 404; situs berbasis Next.js) — [data.go.id](https://data.go.id/)
- FACT (sekunder): Situs pihak ketiga menyebut peta resmi TNGGP menggambarkan jalur beserta pos dan shelter. Peta itu berupa gambar atau PDF, bukan data vektor terbuka yang ditemukan — [Infogepang](https://infogepang.com/peta-jalur-pendakian-gunung-gede-pangrango/). Situs "WebGIS Kemen LHK" berdomain .com, sehingga status resminya UNVERIFIED — [webgiskemenlhk.com](https://webgiskemenlhk.com/)

#### 2c. Ketentuan platform komersial (mengapa tidak boleh di-scrape)
- FACT: Ketentuan AllTrails (efektif 17 Desember 2025), §4.1, melarang penggunaan "software or automated agents or scripts … to generate automated searches, requests, or queries to (or to strip, scrape, or mine data from) the Products". §2.5 melarang "copy, store, reproduce, transmit, modify … or create derivative works". §5.1.3 menyatakan "Materials from the Products may not be copied or distributed, or republished … without our prior written consent". Pengguna memiliki User Material-nya, tetapi memberi AllTrails lisensi luas; peta dan konten tetap milik AllTrails atau pemberi lisensinya — [AllTrails Terms](https://www.alltrails.com/terms)
- FACT: Ketentuan Strava (efektif 1 Januari 2026) menyatakan: "Automated access to or collection of data from the Services—by any means, including data mining, robots, screen scraping, scripts, or similar data-gathering tools … and crawlers—is prohibited. This prohibition applies regardless of whether you are logged into a Strava account" — [Strava Terms](https://www.strava.com/legal/terms)
- FACT (dari ringkasan pencarian; halaman membalas HTTP 403 saat dibuka): ketentuan Wikiloc melarang, tanpa izin tertulis, penautan langsung ke Maps di server Wikiloc, penggunaan dalam produk atau aplikasi, dan penggunaan komersial lain, serta melarang "repeated or systematic extraction" konten — [Wikiloc Terms](https://www.wikiloc.com/wikiloc/terms_en.html)

#### 2d. OSM untuk jalur
- FACT: Rute pendakian di OSM dimodelkan sebagai relasi `type=route` + `route=hiking` dengan `network=iwn/nwn/rwn/lwn`. Tag yang dianjurkan: `name`, `operator`, `distance`, `osmc:symbol`, `roundtrip`. Peran anggota relasi: main, alternative, excursion, approach, connection — [OSM Wiki route=hiking](https://wiki.openstreetmap.org/wiki/Tag:route%3Dhiking)
- FACT: `sac_scale` berjenjang dari hiking, mountain_hiking, demanding_mountain_hiking, alpine_hiking, demanding_alpine_hiking, sampai difficult_alpine_hiking. Wiki menyatakan tag ini "subjective" dan dipasang pada way (`highway=path/footway/track`) — [OSM Wiki sac_scale](https://wiki.openstreetmap.org/wiki/Key:sac_scale)
- FACT: Seluruh Indonesia saat ini hanya punya 107 relasi `route=hiking` dan 206 way path yang bertag `sac_scale` — [Overpass API](https://overpass-api.de/api/interpreter)

### Inferences
- INFERENCE: Status resmi terbit dalam tiga cakupan sekaligus: kawasan (TN Kerinci Seblat), gunung di dalam kawasan (Gunung Semeru di TNBTS, bukan Bromo), dan jalur (empat jalur Lorentz). Ini cocok dengan `official_statuses.scope` (area/mountain/trail/segment). SE tanpa tanggal akhir berarti status tetap berlaku sampai ada pengumuman baru, bukan kedaluwarsa otomatis.
- INFERENCE: Dua status resmi yang berbeda bisa berlaku bersamaan dan tidak boleh dilebur. Ciremai berstatus **Level I Normal** di MAGMA tetapi **tutup sementara** menurut Kemenhut. Rinjani berstatus Level II Waspada dan buka terbatas. Semeru berstatus Level III Siaga dan tutup. Contoh ini menegakkan R-012 (OPEN ≠ SAFE) dan juga kebalikannya: Normal ≠ boleh didaki.
- INFERENCE: Ringkasan otomatis mesin pencari salah mencatat daftar penutupan (Merapi/Merbabu), dan sumber sekunder saling bertentangan soal tanggal buka Semeru (10 vs 24 April). Status akses hanya boleh diambil dari halaman penerbit resmi, dengan nomor SE dan tautan.
- INFERENCE: Sistem booking TN (e-Rinjani, booking.gedepangrango.org) adalah kanal otoritatif untuk kuota dan penutupan harian, tetapi bukan data terbuka. Halamannya berbasis JavaScript, API-nya tidak diketahui, dan ketentuannya belum dibaca. Kanal ini sebaiknya ditautkan sebagai "cek di kanal resmi", bukan di-scrape.
- INFERENCE: Belum ada sumber geometri jalur resmi yang terbuka. Golden trail pertama hampir pasti harus berasal dari survei GPS pemilik sendiri atau data yang diserahkan Balai TN lewat perjanjian tertulis.
- INFERENCE: Cakupan jalur OSM di Indonesia kecil (107 relasi), sehingga OSM belum bisa menjadi sumber utama jalur. Trek hasil survei pemilik bisa dikontribusikan balik ke OSM, tetapi versi yang ada di OSM lalu menjadi ODbL. Ini keputusan strategis pemilik.

### Gaps
- Belum ada konfirmasi apakah Balai TN (Rinjani, Merbabu, Gede Pangrango, TNBTS) menerbitkan trek GPX/SHP resmi atau bersedia berbagi lewat perjanjian. Ini perlu surat permintaan data.
- URL resmi, ketentuan pakai, dan ketersediaan API e-Rinjani belum terverifikasi.
- Domain resmi booking Semeru yang aktif pada September 2026 belum terverifikasi (DNS gagal).
- Teks lengkap ketentuan Wikiloc (403) serta API Agreement Strava belum dibaca langsung.
- Keberadaan dan ketentuan akun media sosial resmi Balai TN sebagai kanal status belum diriset.

## 3. Mutu data: varian nama dan duplikasi, selisih elevasi, presisi koordinat, klasifikasi gunung api, perubahan wilayah administrasi, dan provenance per field

### Takeaway
Masalah mutu di data terbuka Indonesia terukur dan nyata. Nama generik lokal sangat beragam. Nama gunung
bertabrakan dengan nama desa, kecamatan, dan kabupaten. Nilai elevasi saling bertentangan, dan referensinya
sering situs komunitas. Klasifikasi gunung api tidak konsisten antarsumber. Pemekaran Papua 2022 serta
kode wilayah 2025 mengubah atribut provinsi. Rekonsiliasinya butuh provenance per field, kunci identitas
eksternal, dan aturan konflik yang tidak memilih nilai diam-diam.

### Cited Findings

#### 3a. Varian nama, istilah generik, homonim
- FACT: Kata pertama nama pada 27.712 baris GeoNames MT/PK/VLC/HLL: Gunung 7.956, Bukit 2.228, Pasir 2.209, Bulu 1.496, Buttu 1.185, Tubu 816, Doro 800, Golo 623, Buntu 620, Dolok 618, Huidu 564, Palindi 482 — [GeoNames ID.zip](https://download.geonames.org/export/dump/ID.zip)
- FACT: Pada 23.792 node puncak dan gunung api OSM: Gunung 5.699, tanpa nama 2.739, Bulu 1.492, Bukit 1.462, Buttu 1.164, Pasir 1.085, Doro 647, Buntu 621, Golo 596, Huidu 562 — [Overpass API](https://overpass-api.de/api/interpreter)
- FACT: Label "Gunung Gede" dipakai untuk gunung Q1145123 (2.958 m) dan juga untuk dua desa: Q13093324 "desa di Kabupaten Blitar" (P2044 = 60 m) dan Q13547565 "desa di Kabupaten Lebak" (553 m). "Puncak Jaya" adalah gunung Q1045888 (4.884 m) sekaligus Q27771 "kabupaten di Provinsi Papua Tengah" (620 m). "Gunung Kerinci" adalah gunung Q827728 (3.805 m) sekaligus Q9196334 "kecamatan di Kabupaten Kerinci, Jambi" (1.391 m) — [Wikidata Q13093324](https://www.wikidata.org/wiki/Q13093324); [Q13547565](https://www.wikidata.org/wiki/Q13547565); [Q27771](https://www.wikidata.org/wiki/Q27771); [Q9196334](https://www.wikidata.org/wiki/Q9196334); [WDQS kueri Q18](https://query.wikidata.org/)
- FACT: MAGMA memperlakukan "Arjuno Welirang" sebagai satu gunung api (Level I), sedangkan Wikidata punya item terpisah "Gunung Arjuno" (3.339 m). MAGMA juga memisahkan "Lewotobi Laki-laki" (Level II) dan "Lewotobi Perempuan" (Level I), serta mencantumkan "Marapi" (II), "Merapi" (III), dan "Sorikmarapi" (II) sebagai entri berbeda — [MAGMA](https://magma.esdm.go.id/v1/gunung-api/tingkat-aktivitas); [WDQS kueri Q18](https://query.wikidata.org/)
- FACT: Wikidata punya "Gunung Sumbing Jambi" (Q1087196) terpisah dari Sumbing lain. Halaman ringkasan MAGMA menulis "Sumbing" tanpa penanda provinsi — [Wikidata Q1087196](https://www.wikidata.org/wiki/Q1087196); [MAGMA](https://magma.esdm.go.id/v1/gunung-api/tingkat-aktivitas)

#### 3b. Selisih elevasi dan kualitas referensi
- FACT: Ada 13 item gunung Indonesia di Wikidata dengan lebih dari satu nilai elevasi, antara lain Gunung Agung 3.031 | 3.142, Gunung Slamet 3.432 | 3.428, Gunung Marapi 2.885 | 2.891, Gunung Soputan 1.952 | 1.784, Gunung Karangetang 1.827 | 1.784, Gunung Sumbing Jambi 3.371 | 2.507, dan Gunung Manglayang 1.818 | 1.835 — [WDQS kueri Q14/Q16, 2026-09-23 10:04 UTC](https://query.wikidata.org/)
- FACT: URL referensi elevasi (P854) untuk sejumlah gunung besar: Slamet 3.432 → datagunung.com (nilai 3.428 tanpa URL), Gede 2.958 → datagunung.com, Arjuno 3.339 → datagunung.com, Kerinci 3.805 → peakbagger.com. Puncak Jaya 4.884, Merapi 2.910, dan Lawu 3.265 tidak punya URL referensi. Kueri hanya memeriksa P854; jenis referensi lain belum diperiksa — [WDQS kueri Q18](https://query.wikidata.org/)
- FACT: Sebanyak 64 node puncak OSM bertag `source=gunungbagging.com` dan 31 bertag `NGA-GNS;gunungbagging.com`, sehingga situs komunitas ikut menjadi sumber — [Overpass API](https://overpass-api.de/api/interpreter)

#### 3c. Presisi dan asal koordinat
- FACT: Pasangan GeoNames–OSM bernama sama berjarak median 0,00 km dan p90 0,01 km. Hanya 3,7% yang berjarak >1 km, dengan maksimum 23,45 km — [GeoNames](https://download.geonames.org/export/dump/ID.zip) + [Overpass](https://overpass-api.de/api/interpreter)
- FACT: 753 dari 27.712 baris gunung/bukit GeoNames (2,7%) punya lintang dan bujur tepat pada menit busur bulat, yang menandakan pembulatan ke kisi satu menit — [GeoNames ID.zip](https://download.geonames.org/export/dump/ID.zip)
- FACT: Koordinat GeoNames dinyatakan dalam "decimal degrees (wgs84)" — [GeoNames readme](https://download.geonames.org/export/dump/readme.txt)

#### 3d. Klasifikasi gunung api vs gunung
- FACT: Wikidata: 144 item bertipe gunung api (124 punya GVP ID), padahal 159 item Indonesia punya GVP ID. Jadi sedikitnya 35 item ber-GVP ID tidak bertipe gunung api (159 − 124) — [WDQS kueri Q2 & Q4](https://query.wikidata.org/)
- FACT: GeoNames hanya memberi kode VLC pada 19 baris. OSM punya 224 node `natural=volcano`. MAGMA memantau 69 gunung api dan media menyebut 127 gunung api aktif — [GeoNames](https://download.geonames.org/export/dump/ID.zip); [Overpass](https://overpass-api.de/api/interpreter); [MAGMA](https://magma.esdm.go.id/v1/gunung-api/tingkat-aktivitas); [Radar Palu](https://radarpalu.jawapos.com/nasional/2604140024/indonesia-punya-127-gunung-api-aktif-69-dipantau-intensif-24-jam)

#### 3e. Perubahan wilayah administrasi
- FACT: UU 14/2022 (Papua Selatan: Merauke, Boven Digoel, Mappi, Asmat), UU 15/2022 (Papua Tengah: Nabire, Puncak Jaya, Paniai, Mimika, Puncak, Dogiyai, Intan Jaya, Deiyai), dan UU 16/2022 (Papua Pegunungan: Jayawijaya, Pegunungan Bintang, Yahukimo, Tolikara, Mamberamo Tengah, Yalimo, Lanny Jaya, Nduga) ditandatangani 25 Juli 2022 — [Kompas](https://nasional.kompas.com/read/2022/07/29/10473321/tiga-uu-tentang-pemekaran-wilayah-papua-resmi-diundangkan); [JDIH BPK UU 14/2022](https://peraturan.bpk.go.id/Details/217796/uu-no-14-tahun-2022)
- FACT: UU 29/2022 membentuk Provinsi Papua Barat Daya dari sebagian Papua Barat — [JDIH BPK UU 29/2022](https://peraturan.bpk.go.id/Details/232726/uu-no-29-tahun-2022)
- FACT (sekunder, nomor keputusan bertentangan): Kepmendagri tahun 2025 tentang kode dan data wilayah mencatat 38 provinsi, 416 kabupaten, 98 kota, 7.285 kecamatan, 8.496 kelurahan, dan 75.266 desa. Satu repositori menyebut nomornya 300.2.2-2138, repositori lain 300.2.2-2430 — [GitHub yonatanyl](https://github.com/yonatanyl/KODE-WILAYAH-KEPMENDAGRI-2025); [GitHub cahyadsn](https://github.com/cahyadsn/wilayah). Rilis resmi Ditjen Bina Adwil belum dibuka — [Kemendagri Bina Adwil](https://ditjenbinaadwil.kemendagri.go.id/berita/detail/kemendagri-mutakhirkan--kode-data-wilayah-administrasi-pemerintahan--dan-pulau-di-seluruh-indonesia)
- FACT: Wikidata sudah mendeskripsikan Kabupaten Puncak Jaya (Q27771) sebagai "kabupaten di Provinsi Papua Tengah" — [Wikidata Q27771](https://www.wikidata.org/wiki/Q27771)

### Inferences
- RECOMMENDATION (provenance per field): simpan setiap nilai kandidat sebagai baris tersendiri. Isinya: entitas, field, nilai, satuan, `data_source_id`, ID rekaman sumber (QID, node OSM, nomor GVP, ID SINAR bila ada), `source_url`, `retrieved_at`, `published_at` (tanggal terbit oleh penerbit), lisensi, tingkat otoritas, `verification_state` (UNVERIFIED / ADMIN_VERIFIED / REJECTED), `verified_by`/`verified_at`, dan metode (survei, peta, DEM, turunan). Nilai yang ditampilkan dipilih dari tingkat otoritas tertinggi yang tidak ditolak. Ini perubahan skema, jadi OPEN ISSUE: butuh persetujuan pemilik (R-021).
- RECOMMENDATION (aturan konflik): jangan merata-ratakan dan jangan memilih diam-diam. Bila dua sumber setingkat berbeda, misalnya Slamet 3.428 vs 3.432 m, UI menampilkan nilai itu sebagai "berbeda antarsumber" beserta keduanya, atau menampilkan nilai hasil verifikasi admin dengan catatan. Ambang selisih yang dianggap konflik adalah keputusan produk (OPEN ISSUE).
- RECOMMENDATION (deduplikasi): kandidat duplikat dicari dengan jarak ≤2 km ditambah nama ternormalisasi. Normalisasi membuang istilah generik (Gunung, Bukit, Puncak, Pasir, Bulu, Buttu, Doro, Golo, Dolok, Huidu, Buntu). Makna istilah per bahasa daerah UNVERIFIED di catatan ini. Entitas administratif (P31 desa, kecamatan, kabupaten) dikeluarkan dari kandidat. Penggabungan wajib dikonfirmasi admin dan dicatat di log.
- INFERENCE (identitas): R-001 hanya membedakan Mountain dan Trail, tetapi data nyata punya tiga lapis: massif/gunung, puncak bernama, dan unit gunung api PVMBG. Contohnya Arjuno-Welirang (satu unit PVMBG, dua puncak) dan Lewotobi (dua unit PVMBG). Status PVMBG perlu diikat ke unit gunung api, bukan ke nama gunung populer. Apakah perlu entitas "puncak" atau "unit gunung api" terpisah adalah OPEN ISSUE skema.
- INFERENCE (koordinat): koordinat garis keturunan GNS perlu diberi tanda "presisi tidak diketahui". Satu menit lintang sekitar 1,85 km (definisi mil laut), sehingga baris dengan koordinat menit bulat bisa meleset lebih dari 1 km. Koordinat puncak untuk UI peta dan rekomendasi sebaiknya naik ke ADMIN_VERIFIED lewat pencocokan manual dengan sumber resmi (RBI/BIG) atau survei.
- INFERENCE (wilayah): provinsi dan kabupaten sebaiknya menjadi field turunan hasil spatial join koordinat dengan batas wilayah resmi, disertai versi batasnya (mis. Kepmendagri 2025). Nilainya tidak diketik manual. Semua gunung di Papua yang provinsinya tercatat sebelum 2022 perlu dihitung ulang. Pemetaannya tidak boleh ditebak dari nama kabupaten lama.
- INFERENCE (klasifikasi gunung api): "gunung api" perlu dipisah menjadi (a) dipantau PVMBG, yang bersifat OFFICIAL dan menentukan apakah status Level ditampilkan, dan (b) punya nomor GVP (rujukan ilmiah). Sumbernya jangan tag generik OSM/GeoNames (hanya 19 VLC di GeoNames).

### Gaps
- Makna tiap istilah generik daerah (Bulu, Buttu, Doro, Golo, Dolok, Huidu, Pasir, Tubu, Palindi) belum dirujuk ke sumber linguistik atau Gazeter.
- Belum diketahui apakah SINAR/Gazeter memberi ID stabil per toponim yang bisa dipakai sebagai kunci eksternal.
- Nilai elevasi resmi (BIG, PVMBG, atau Balai TN) untuk gunung-gunung yang nilainya konflik belum dicari.
- Nomor resmi Kepmendagri kode wilayah 2025 belum pasti (2138 vs 2430).
- Overlap antara 403 item gunung ber-idwiki, 144 item gunung api Wikidata, dan 69 unit MAGMA belum dihitung.

## 4. Program data bertahap: katalog dulu, jalur kemudian

### Takeaway
Program yang realistis dan tidak mengarang data dimulai dari katalog inti sekitar ratusan entitas yang bisa
diverifikasi, bukan 28 ribu entri gazeter. Tahapannya: katalog deskriptif (fase 1), dua jenis status resmi
yang disimpan terpisah (fase 2), sedikit golden trail yang disurvei sendiri atau diperoleh dari mitra resmi
(fase 3), lalu kontribusi komunitas dan ahli yang dimoderasi (fase 4). Setiap field membawa sumber, dan
setiap kekosongan ditampilkan jujur sebagai "belum ada sumber terverifikasi".

### Cited Findings
- FACT: Seed saat ini berisi 17 gunung dari Wikidata/Wikipedia Indonesia (berlabel COMMUNITY, dua koreksi manual), 29 jalur tanpa geometri, dan 0 jalur bergeometri. Sudah ada pengelola gunung dan impor geometri GPX untuk admin — konteks proyek (brief tugas).
- FACT: Kandidat katalog inti yang terukur: 403 item gunung Wikidata ber-idwiki (202 dengan elevasi, 336 dengan koordinat), 144 item gunung api Wikidata, dan 69 gunung api MAGMA — [WDQS](https://query.wikidata.org/); [MAGMA](https://magma.esdm.go.id/v1/gunung-api/tingkat-aktivitas)
- FACT: SE Kemenhut 31 Agu 2026 menyebut 13 lokasi pendakian (9 tutup sementara, 4 buka terbatas) — [Kemenhut](https://www.kehutanan.go.id/news/antisipasi-puncak-kemarau-kemenhut-atur-status-pendakian-gunung-di-kawasan-konservasi-berbasis-manajemen-risiko)
- FACT: Ekstraksi sistematis fitur OSM dianggap substansial. Ambang "insubstantial" yang jelas adalah kurang dari 100 fitur, dan ekstraksi kecil berulang dihitung sebagai satu ekstraksi besar — [OSMF Substantial Guideline](https://osmfoundation.org/wiki/Licence/Community_Guidelines/Substantial_-_Guideline)
- FACT: Menurut pedoman Collective Database, menggabungkan database lain dengan OSM sambil "removing any duplicate objects" menghasilkan Derivative Database yang terkena share-alike — [OSMF Collective Database Guideline](https://osmfoundation.org/wiki/Licence/Community_Guidelines/Collective_Database_Guideline_Guideline)
- FACT: Tidak ditemukan dataset jalur pendakian resmi di katalog Ina-Geoportal (0 hasil) — [CKAN Ina-Geoportal](https://tanahair.indonesia.go.id/sdi/api/3/action/package_search?q=jalur%20pendakian)

### Inferences

#### Fase 1: Katalog gunung nasional (deskriptif, tanpa jalur)
- RECOMMENDATION (cakupan): **Tier A (inti)** adalah gabungan 69 unit gunung api MAGMA, 403 gunung ber-idwiki, dan gunung di 13 lokasi SE Kemenhut. Setelah deduplikasi jumlahnya diperkirakan ratusan; angka pastinya belum dihitung (lihat Gaps §3). **Tier B** adalah entri garis keturunan GNS (sekitar 28 ribu) yang **tidak** diimpor massal ke tabel publik dan hanya ditambahkan per kasus saat admin mengkurasi.
- RECOMMENDATION (sumber per field):
  - Nama baku: BIG SINAR/Gazeter (OFFICIAL), dicari manual oleh admin dengan `source_url` per entri. Nama alternatif: label dan alias Wikidata (COMMUNITY).
  - Koordinat puncak: Wikidata P625 (COMMUNITY, CC0) sebagai titik awal. Naik ke ADMIN_VERIFIED setelah dicocokkan dengan RBI/BIG (OFFICIAL, lisensi perlu dikonfirmasi) atau survei.
  - Elevasi: hanya dari sumber bernama. Kosong bila tidak ada. Nilai GeoNames/OSM hanya boleh masuk sebagai kandidat COMMUNITY berlabel lisensi, tidak pernah sebagai nilai resmi.
  - Provinsi dan kabupaten: turunan dari spatial join dengan batas resmi, atau Wikidata P131 sebagai kandidat.
  - Penanda gunung api: daftar MAGMA (OFFICIAL) ditambah nomor GVP dari Wikidata P1886 (CC0).
- RECOMMENDATION (metode impor): job impor massal idempoten dengan kunci QID untuk Wikidata. Teks SPARQL dan waktu pengambilan disimpan di `data_sources`. Hasil impor masuk sebagai COMMUNITY/UNVERIFIED, dan admin memverifikasi lewat UI yang sudah ada. OSM tidak diimpor massal ke tabel inti karena risiko Derivative Database. Bila ingin memanfaatkan OSM, simpan sebagai lapisan referensi internal terpisah; pemakaian internal bukan "Publicly Use" menurut ODbL 4.5.
- RECOMMENDATION (validasi): koordinat berada di dalam wilayah Indonesia; bukan entitas administratif; lolos cek duplikat ≤2 km + nama ternormalisasi; elevasi 0–5.000 m hanya sebagai sanity check; selisih besar terhadap DEM hanya ditandai, **tidak pernah menimpa**; setiap field punya `source_url`, `retrieved_at`, dan lisensi yang dikenal.
- RECOMMENDATION (UI saat kosong): tampilkan "Elevasi: belum ada sumber terverifikasi", bukan "0" atau "-". Daftar yang diurutkan menurut elevasi menaruh entri tanpa elevasi di kelompok terpisah "tanpa data", bukan di urutan terbawah seolah paling rendah (R-006/R-007). Setiap field punya lencana sumber, misalnya "Wikidata · komunitas · diambil 23 Sep 2026".

#### Fase 2: Cakupan status resmi
- RECOMMENDATION (dua keluarga status): (a) **status bahaya gunung api**, yaitu Level I–IV PVMBG beserta teks rekomendasinya, per unit gunung api; (b) **status akses**, yaitu tutup sementara / buka terbatas / buka dari Kemenhut atau Balai TN, per kawasan, gunung, jalur, atau segmen. Level PVMBG **tidak dipetakan** ke OPEN/RESTRICTED/CLOSED. Menyimpannya terpisah kemungkinan butuh perubahan skema, jadi OPEN ISSUE yang perlu persetujuan.
- RECOMMENDATION (pemetaan status akses): "tutup sementara" → CLOSED dan "buka terbatas" → RESTRICTED, dengan `scope` sesuai teks SE (mis. Lorentz → 4 jalur, Semeru → gunung). `published_at` = tanggal SE, `effective_from` = 1 Sep 2026, `effective_until` = kosong ("sampai pengumuman baru"). Tabel pemetaan ini perlu disahkan pemilik.
- RECOMMENDATION (metode): input admin dengan `source_url` wajib, karena API MAGMA belum ditemukan dan halamannya "All Rights Reserved". Job terjadwal hanya boleh *mendeteksi perubahan* dan memberi tahu admin, dan itu pun setelah ada izin tertulis PVMBG untuk akses otomatis. Status tanpa sumber resmi menjadi UNKNOWN.
- RECOMMENDATION (kesegaran): tampilkan waktu terbit dan waktu diambil. Setelah ambang tertentu (keputusan produk, OPEN ISSUE) tampilkan "mungkin sudah berubah — cek MAGMA / kanal TN" beserta tautannya.
- RECOMMENDATION (UI): kedua status tampil berdampingan, lengkap dengan sumber dan waktu. Tidak ada kata "aman". Contoh: "Level I Normal (PVMBG) · Pendakian: tutup sementara (SE Kemenhut 31 Agu 2026)".

#### Fase 3: Jalur prioritas dengan geometri (golden trail)
- RECOMMENDATION (kriteria): jalur yang namanya tercantum sebagai jalur resmi Balai TN, status aksesnya diketahui, dan bisa disurvei pemilik sendiri atau diserahkan Balai TN secara tertulis. Mulai dari 1–3 jalur saja.
- RECOMMENDATION (sumber geometri): (1) GPX survei pemilik (catat perangkat, tanggal, akurasi); (2) data Balai TN lewat perjanjian; (3) way OSM hanya bila lapisan jalur itu siap dirilis di bawah ODbL. **Tidak pernah** dari AllTrails, Strava, atau Wikiloc.
- RECOMMENDATION (karakteristik): jarak dan elevation gain dihitung dari geometri dan DEM. Hasilnya diberi label "dihitung dari trek survei + DEM [nama DEM]", bukan disajikan sebagai data resmi. Durasi hanya ditampilkan dari SOP resmi atau dari beberapa survei nyata; estimasi berbasis rumus adalah keputusan produk (OPEN ISSUE, risiko false precision R-005). Pos/checkpoint diambil dari SOP atau booking resmi, dengan koordinat survei.
- RECOMMENDATION (validasi, gerbang R-022): LineString kontinu tanpa loncatan titik yang tidak wajar; titik awal dekat pintu resmi dan titik akhir dekat puncak (ambang ditetapkan admin); panjang dibandingkan dengan jarak resmi bila ada; minimal dua survei atau konfirmasi Balai TN sebelum naik ke ADMIN_VERIFIED; semua data kritis (sumber, geometri, karakteristik dasar, pos, metadata status) wajib ada sebelum PUBLISHED.
- RECOMMENDATION (UI): jalur yang belum lolos gerbang tidak masuk rekomendasi. Halaman gunung menulis "Jalur resmi: Sembalun, Senaru, … — data jalur belum lengkap, belum ditampilkan", bukan garis perkiraan.

#### Fase 4: Kontribusi komunitas dan ahli dengan moderasi
- RECOMMENDATION: kontribusi GPX, laporan kondisi, dan foto dari pengguna masuk antrean moderasi. Pengguna memberi lisensi eksplisit lewat ToS; pilihan lisensinya keputusan hukum (OPEN ISSUE). Metadata privasi GPX (stempel waktu, titik awal dekat rumah) dibersihkan sesuai `docs/KEBIJAKAN-DATA-PRIBADI.md`. Laporan komunitas tidak pernah mengubah status resmi dan diberi label "laporan komunitas, [tanggal]" (R-010).
- RECOMMENDATION: ACCREDITED_EXPERT (mis. pemandu bersertifikat) perlu kriteria akreditasi tertulis sebelum dipakai. Kriteria itu belum diriset.

### Gaps
- Ukuran Tier A setelah deduplikasi belum dihitung.
- Nama dan lisensi DEM yang layak dipakai (DEMNAS BIG, SRTM, dan lain-lain) belum diriset.
- Skema sertifikasi pemandu gunung di Indonesia untuk tingkat ACCREDITED_EXPERT belum diriset.
- Kesediaan Balai TN atau PVMBG bermitra belum diketahui. Ini perlu surat resmi.

## 5. Kewajiban hukum dan atribusi per sumber, serta cara menampilkannya di aplikasi

### Takeaway
Wikidata (CC0) dan GNS (tanpa batasan lisensi) paling aman dipakai. GeoNames mewajibkan atribusi (CC BY 4.0).
OSM mewajibkan atribusi dan share-alike untuk Derivative Database yang dipakai publik (ODbL). Sumber
pemerintah Indonesia belum punya lisensi eksplisit yang terbaca: MAGMA menulis "All Rights Reserved",
syarat Ina-Geoportal tidak terbaca, dan field lisensi di katalog kosong. Platform komersial (AllTrails,
Strava, Wikiloc) melarang scraping. Aplikasi perlu registri lisensi per sumber, lencana sumber per field,
atribusi di peta, dan halaman "Sumber & Lisensi".

### Cited Findings
- FACT: Data terstruktur Wikidata berlisensi CC0, setara domain publik. Teks di namespace lain berlisensi CC BY-SA 4.0 — [Wikidata:Licensing](https://www.wikidata.org/wiki/Wikidata:Licensing)
- FACT: ODbL 4.2: bila menyebarkan Database atau Derivative Database secara publik, sertakan lisensi dan pertahankan pemberitahuannya. 4.3: Produced Work yang dipakai publik wajib memuat pemberitahuan bahwa kontennya berasal dari Database. 4.4: "Any Derivative Database that You Publicly Use must be only under the terms of: This License…". 4.5: share-alike tidak berlaku pada Collective Database, Produced Work tidak menciptakan Derivative Database, dan penggunaan internal bukan penggunaan publik. 4.6: wajib menawarkan salinan terbaca mesin dari seluruh Derivative Database atau berkas berisi semua perubahannya — [ODbL 1.0](https://opendatacommons.org/licenses/odbl/1-0/)
- FACT: Atribusi harus kepada "OpenStreetMap" dan menjelaskan bahwa datanya di bawah ODbL, sebaiknya bertaut ke openstreetmap.org/copyright. Pada peta interaktif, atribusi ditaruh di pojok peta dan boleh diciutkan asal tetap bisa diakses. Hasil geocoding tidak perlu membawa atribusi per hasil selama tidak membentuk Derivative Database. Pedoman ini diadopsi 25 Juni 2021 — [OSMF Attribution Guidelines](https://osmfoundation.org/wiki/Licence/Attribution_Guidelines)
- FACT: Sebuah gabungan adalah Collective Database bila "the data used for a particular data type is either all OSM or all non-OSM within the same regional cut". Atribut non-OSM boleh ditempelkan ke fitur OSM tanpa share-alike asalkan seluruh atribut itu di wilayah tersebut berasal dari data non-OSM. Pedoman ini didukung Dewan OSMF 17 Juni 2016 — [OSMF Collective Database Guideline](https://osmfoundation.org/wiki/Licence/Community_Guidelines/Collective_Database_Guideline_Guideline)
- FACT: GeoNames berlisensi CC BY 4.0 — [GeoNames readme](https://download.geonames.org/export/dump/readme.txt)
- FACT (ringkasan pencarian): GNS tanpa syarat lisensi, dengan sitasi yang dianjurkan NGA/BGN (teks di §1d) — [Indiana University Libraries](https://libraries.indiana.edu/geonet-names-server)
- FACT: MAGMA menampilkan "Copyright 2026 © All Rights Reserved. MAGMA Indonesia" — [MAGMA](https://magma.esdm.go.id/v1/gunung-api/tingkat-aktivitas)
- FACT (ringkasan pencarian atas teks UU): UU 28/2014 Pasal 43 menyatakan bahwa pengumuman, pendistribusian, komunikasi, dan/atau penggandaan "segala sesuatu yang dilaksanakan oleh atau atas nama pemerintah" tidak dianggap pelanggaran hak cipta, kecuali dinyatakan dilindungi oleh peraturan perundang-undangan, oleh pernyataan pada ciptaan tersebut, atau ketika ciptaan itu diumumkan, didistribusikan, dikomunikasikan, dan/atau digandakan — [pasal.id Pasal 43](https://pasal.id/peraturan/uu/uu-no-28-tahun-2014/pasal-43); [JDIH BPK UU 28/2014](https://peraturan.bpk.go.id/details/38690)
- FACT (ringkasan pencarian atas teks UU; nomor pasal perlu dicek di teks resmi): UU 4/2011 menyatakan Informasi Geospasial Dasar (IGD) "bersifat terbuka", dapat diakses dan diperoleh setiap orang (disebut Pasal 42) — [JDIH BPK UU 4/2011](https://peraturan.bpk.go.id/Details/39136/uu-no-4-tahun-2011)
- FACT: SINAR menyatakan data nama rupabumi yang *diunggah* ke aplikasinya menjadi milik BIG — [SINAR](https://sinar.big.go.id/)
- FACT: Field lisensi kosong pada dataset katalog Ina-Geoportal yang disampel, dan tidak dicantumkan pada dataset RBI di data.go.id — [CKAN Ina-Geoportal](https://tanahair.indonesia.go.id/sdi/api/3/action/package_search?q=toponimi); [data.go.id](https://data.go.id/dataset/dataset/peta-rupabumi-indonesia-skala-1-25-000-wilayah-lampung03-referensispasial)
- FACT: AllTrails, Strava, dan Wikiloc melarang scraping atau ekstraksi sistematis (kutipan di §2c) — [AllTrails](https://www.alltrails.com/terms); [Strava](https://www.strava.com/legal/terms); [Wikiloc](https://www.wikiloc.com/wikiloc/terms_en.html)

### Inferences
- RECOMMENDATION (registri lisensi per sumber, disimpan di `data_sources`):

| Sumber | Lisensi / dasar | Kewajiban | Tampilan di aplikasi |
|---|---|---|---|
| Wikidata | CC0 | Tidak wajib secara hukum | Lencana "Wikidata (CC0)" dan tautan QID; tetap dicantumkan demi provenance |
| OpenStreetMap | ODbL 1.0 | Atribusi; share-alike untuk Derivative Database yang dipakai publik; tawarkan DB atau berkas perubahan | "© OpenStreetMap" bertaut ke /copyright di pojok peta; bila lapisan jalur berbasis OSM, sediakan unduhan lapisan itu di bawah ODbL |
| GeoNames | CC BY 4.0 | Atribusi dan tanda bila diubah | "GeoNames (CC BY 4.0)" pada field yang berasal darinya |
| NGA GNS | Tanpa batasan (menurut ringkasan) | Sitasi dianjurkan | Kalimat sitasi NGA/BGN di halaman Sumber |
| PVMBG/MAGMA | "All Rights Reserved"; Pasal 43 UU 28/2014 berpotensi relevan tetapi ada pengecualian "pernyataan pada ciptaan" | Belum jelas | Tampilkan level sebagai fakta resmi dengan "Sumber: PVMBG – MAGMA Indonesia, [waktu]" dan tautan; jangan salin peta KRB atau teks VAR panjang tanpa izin |
| BIG (SINAR, RBI, Ina-Geoportal) | UU 4/2011: IGD terbuka; ketentuan portal belum terbaca | Belum jelas; kemungkinan atribusi | "Sumber: Badan Informasi Geospasial"; minta konfirmasi tertulis sebelum redistribusi massal |
| Kemenhut / Balai TN | Pengumuman pemerintah | Sebut penerbit dan nomor SE | "SE.4/KSDAE/…/2026, Kemenhut, 31 Agu 2026" dan tautannya |
| AllTrails / Strava / Wikiloc | Ketentuan melarang | – | Tidak dipakai |

- INFERENCE (risiko ODbL): mengimpor 23 ribu puncak OSM lalu melakukan deduplikasi dengan Wikidata dan GeoNames ke tabel `mountains` jelas merupakan ekstraksi sistematis (>100 fitur). Hasilnya Derivative Database, dan katalog gunung wajib dibuka di bawah ODbL begitu dipakai publik. Bila pemilik tidak ingin itu, OSM hanya dipakai sebagai peta dasar (Produced Work dengan atribusi) atau referensi internal.
- INFERENCE (sumber pemerintah): status Level PVMBG dan isi SE Kemenhut adalah fakta publik resmi, sehingga mengutipnya dengan atribusi dan tautan berisiko rendah. Otomatisasi pengambilan dan penyalinan konten kreatif (peta, teks panjang) sebaiknya menunggu izin tertulis. Ini inferensi, bukan nasihat hukum; perlu tinjauan hukum.
- RECOMMENDATION (UI atribusi): (1) kontrol atribusi di pojok peta; (2) popover "Sumber" per field (nama sumber, tingkat otoritas, tanggal terbit dan diambil, tautan, lisensi); (3) halaman "Sumber & Lisensi" berisi daftar sumber, lisensi, kalimat sitasi (GNS, GVP), dan tanggal pembaruan; (4) bila ada lapisan ODbL, tautan unduhan lapisan tersebut.
- RECOMMENDATION: jangan menyalin teks artikel Wikipedia ke deskripsi gunung. Lisensi teks Wikipedia (CC BY-SA) belum diverifikasi di sesi ini dan memuat kewajiban share-alike. Ambil fakta dari Wikidata saja.
- OPEN ISSUE (tingkat otoritas): GVP adalah rujukan ilmiah, bukan otoritas Indonesia; usulannya ACCREDITED_EXPERT hanya untuk identitas dan riwayat erupsi, tidak pernah untuk status. GNS, GeoNames, Wikidata, dan OSM diusulkan COMMUNITY. Menetapkan tingkat ini termasuk semantik hierarki otoritas, sehingga perlu persetujuan pemilik (R-009, R-021).

### Gaps
- Isi syarat dan ketentuan Ina-Geoportal (hak redistribusi, penggunaan komersial, kalimat atribusi) belum terbaca karena halamannya hanya JavaScript. Perlu dibuka manual di peramban atau ditanyakan ke BIG.
- Ketentuan pakai data GVP dan kalimat sitasi versi terkini belum terverifikasi (403).
- Posisi hukum MAGMA "All Rights Reserved" terhadap Pasal 43 UU 28/2014 belum dikaji. Butuh tinjauan hukum atau izin PVMBG.
- Perpres 39/2019 (Satu Data Indonesia) dan kebijakan lisensi portal data.go.id belum dibaca di sesi ini.
- Teks asli Pasal 43 UU 28/2014 dan Pasal 42 UU 4/2011 belum dibaca langsung; yang dikutip di sini berasal dari ringkasan pencarian.
