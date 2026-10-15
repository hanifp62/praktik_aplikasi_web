# Gunung: tabel `mountains` dan model `Mountain`

Gunung adalah induk jalur, padanan "gedung" pada contoh pemesanan ruangan. Pendaki tidak
merencanakan trip ke gunung; ia merencanakan trip ke salah satu jalurnya (R-001, R-002).

## Migrasi

Berkas pembuat: [`database/migrations/2026_09_19_000210_create_mountains_table.php`](../../../database/migrations/2026_09_19_000210_create_mountains_table.php)

| Kolom | Tipe | Aturan | Asal migrasi |
|---|---|---|---|
| `id` | bigint, auto increment | primary key | pembuat |
| `name` | string | wajib | pembuat |
| `slug` | string | unik, dipakai di URL `/mountains/{slug}` | pembuat |
| `province` | string | boleh kosong | pembuat |
| `region` | string | boleh kosong, berindeks | pembuat |
| `timezone` | string | bawaan `Asia/Jakarta` | `2026_09_20_000110_add_timezone_to_mountains` |
| `elevation_mdpl` | unsigned int | boleh kosong | pembuat |
| `latitude`, `longitude` | decimal(10,7) | boleh kosong | `2026_09_20_000300_tambah_koordinat_biasa_ke_gunung` |
| `description` | text | boleh kosong | pembuat |
| `data_source_id` | foreign key ke `data_sources` | boleh kosong, `nullOnDelete` | pembuat |
| `archived_at` | timestamp | kosong berarti aktif | pembuat |
| `location` | geography(POINT, 4326) + indeks GiST | hanya PostgreSQL | pembuat, lewat `PostGis::addGeography()` |
| `created_at`, `updated_at` | timestamp | otomatis | pembuat |

Koordinat disimpan dua kali dengan sengaja: kolom biasa `latitude` dan `longitude` dibaca
aplikasi di mana pun, sedangkan `location` dipakai kueri spasial PostGIS.
`Mountain::setCoordinates()` menulis keduanya sekaligus.

`timezone` diperlukan karena gunung di Bali dan Lombok memakai `Asia/Makassar`. Tanggal
"hari ini" untuk sebuah trip dihitung menurut zona gunungnya, bukan zona server.

## Model

Berkas: [`app/Models/Mountain.php`](../../../app/Models/Mountain.php)

```php
#[Fillable([
    'name', 'slug', 'province', 'region', 'timezone', 'elevation_mdpl', 'description',
    'data_source_id', 'archived_at', 'latitude', 'longitude',
])]
#[Hidden(['location'])]
class Mountain extends Model
```

| Relasi | Tipe | Tujuan |
|---|---|---|
| `trails()` | `HasMany` | `Trail` |
| `dataSource()` | `BelongsTo` | `DataSource` |
| `officialStatuses()` | `MorphMany` | `OfficialStatus` (status resmi tingkat gunung) |
| `restrictedAreas()` | `HasMany` | `RestrictedArea` |
| `authorities()` | `BelongsToMany` | `Authority` (balai taman nasional dan badan lain) |

| Method lain | Fungsi |
|---|---|
| `scopeActive()` | hanya gunung yang belum diarsipkan |
| `isArchived()` | `true` bila `archived_at` terisi |
| `hasCoordinates()` | `true` bila lintang dan bujur sama-sama ada |
| `responsibleAuthority()` | badan yang berwenang menyatakan status jalur di gunung ini |

## Factory dan seeder

- Factory: `database/factories/MountainFactory.php`.
- Seeder: `MountainSeeder` (16 gunung di Jawa dan Lombok), `MvpDatasetSeeder`, dan
  `DemoSeeder`. Lihat [`../03-seeder.md`](../03-seeder.md).
