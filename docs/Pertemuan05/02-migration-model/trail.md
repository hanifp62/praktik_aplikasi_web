# Jalur: tabel `trails` dan model `Trail` (padanan Room)

Jalur adalah objek yang dipilih pendaki, sama seperti ruangan yang dipilih pemesan. Bedanya,
jalur punya karakteristik yang menentukan kecocokan (jarak, tanjakan, tingkat teknis) dan
harus lolos gerbang kelengkapan data sebelum boleh tampil ke pendaki.

## Migrasi

Berkas pembuat: [`database/migrations/2026_09_19_000220_create_trails_table.php`](../../../database/migrations/2026_09_19_000220_create_trails_table.php)

| Kolom | Tipe | Aturan | Catatan |
|---|---|---|---|
| `id` | bigint | primary key | |
| `mountain_id` | foreign key ke `mountains` | wajib, `cascadeOnDelete` | |
| `name` | string | wajib | |
| `slug` | string | unik | dibentuk dari nama + id gunung, dipakai di URL `/trails/{slug}` |
| `description` | text | boleh kosong | |
| `distance_km` | decimal(6,2) | boleh kosong | kosong berarti belum diketahui (R-007) |
| `elevation_gain_m`, `elevation_loss_m` | unsigned int | boleh kosong | |
| `elevation_profile` | JSON | boleh kosong | dari migrasi `2026_09_20_000700` |
| `estimated_duration_minutes` | unsigned int | boleh kosong | |
| `technical_demand` | string (enum) | bawaan `MODERATE` | `TechnicalDemand` |
| `terrain_character` | JSON (daftar enum) | boleh kosong | `TerrainCharacter` |
| `navigation_complexity` | string (enum) | bawaan `MODERATE` | `NavigationComplexity` |
| `water_availability` | string (enum) | bawaan `UNKNOWN` | `WaterAvailability` |
| `camping_available` | boolean | bawaan `false` | |
| `starting_point` | string | boleh kosong | basecamp atau pintu masuk |
| `weather_adm4_code` | string | boleh kosong, berindeks | kode wilayah untuk prakiraan BMKG |
| `weather_reference_area` | string | boleh kosong | nama wilayah acuan cuaca (R-013) |
| `data_source_id` | foreign key ke `data_sources` | boleh kosong, `nullOnDelete` | |
| `is_published` | boolean | bawaan `false`, berindeks | jalur selalu lahir sebagai draf |
| `archived_at` | timestamp | kosong berarti aktif | pengganti hapus permanen |
| `geometry` | geography(LINESTRING, 4326) + indeks GiST | hanya PostgreSQL | garis jalur di peta |

## Model

Berkas: [`app/Models/Trail.php`](../../../app/Models/Trail.php)

```php
#[Fillable([
    'mountain_id', 'name', 'slug', 'description', 'distance_km', 'elevation_gain_m',
    'elevation_loss_m', 'elevation_profile', 'estimated_duration_minutes', 'technical_demand', 'terrain_character',
    'navigation_complexity', 'water_availability', 'camping_available', 'starting_point',
    'weather_adm4_code', 'weather_reference_area', 'data_source_id', 'is_published', 'archived_at',
])]
#[Hidden(['geometry'])]
class Trail extends Model
```

`casts()` mengubah `technical_demand`, `navigation_complexity`, dan `water_availability`
menjadi enum; `terrain_character` dan `elevation_profile` menjadi array; `is_published` dan
`camping_available` menjadi boolean.

| Relasi | Tipe | Tujuan |
|---|---|---|
| `mountain()` | `BelongsTo` | `Mountain` |
| `checkpoints()` | `HasMany`, urut `sequence` | `Checkpoint` |
| `segments()` | `HasMany`, urut `sequence` | `TrailSegment` |
| `officialStatuses()` | `MorphMany` | `OfficialStatus` |
| `conditionReports()` | `HasMany` | `TrailConditionReport` (laporan komunitas) |
| `weatherSnapshots()` | `HasMany` | `WeatherSnapshot` |
| `dataSource()` | `BelongsTo` | `DataSource` |
| `preparationTemplates()` | `HasMany` | `PreparationTemplate` |

## Gerbang publikasi di model

`Trail::booted()` memasang penjaga pada event `saving`. Setiap kali jalur disimpan dalam
keadaan `is_published = true`, model memanggil `publishabilityReport()`. Bila daftarnya tidak
kosong, model melempar `PublicationGateViolation` dan penyimpanan dibatalkan.

`publishabilityReport()` memeriksa lima syarat:

1. sumber data ditetapkan (`data_source_id`);
2. jarak, tanjakan, dan estimasi durasi terisi;
3. ada minimal satu pos (`checkpoints`);
4. ada minimal satu status resmi (`officialStatuses`);
5. geometri tersedia (hanya diperiksa di PostgreSQL).

Penjaga ini bekerja dua arah. Jalur tidak dapat lahir dalam keadaan terbit, dan jalur yang
sudah terbit tidak dapat disunting sampai kehilangan data kritisnya. Kurator yang perlu
mengoreksi data kritis menarik jalurnya dari publikasi dulu, menyunting, lalu menerbitkan
lagi. Test: `PublicationInvariantTest` (18 test).

| Method lain | Fungsi |
|---|---|
| `scopeActive()` | belum diarsipkan |
| `scopePublished()` | terbit dan belum diarsipkan; dipakai pencarian dan rekomendasi |
| `terbit()` | padanan `scopePublished()` untuk satu objek |
| `scopeDataComplete()` | sisi SQL dari syarat kelengkapan, untuk menyaring kandidat rekomendasi |
| `scopeWithPublishabilityData()` | memuat hitungan pos dan status sekaligus supaya daftar admin tidak N+1 |
| `awaitingData()` | daftar data yang belum ada, dalam bahasa pendaki |

## Factory dan seeder

- Factory: `database/factories/TrailFactory.php`, dengan state `easy()`,
  `highlyTechnical()`, `published()`, dan `unpublished()`. State `published()` membuat
  sumber data, satu pos, dan status resmi `UNKNOWN` lebih dulu, lalu baru menerbitkan,
  sehingga factory pun melewati gerbang yang sama.
- Seeder: `MvpDatasetSeeder` (7 jalur draf), `AwaitingTrailSeeder` (29 jalur menunggu
  data), `DemoSeeder` (11 jalur contoh). Lihat [`../03-seeder.md`](../03-seeder.md).
