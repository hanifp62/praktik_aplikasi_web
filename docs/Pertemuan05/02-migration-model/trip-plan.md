# Rencana trip: tabel `trip_plans` dan model `TripPlan` (padanan Booking)

Rencana trip menghubungkan satu pendaki dengan satu jalur pada tanggal tertentu, seperti
booking menghubungkan pemesan dengan ruangan. Rencana ini milik pribadi pendaki dan tidak
mengurangi kuota apa pun di pengelola gunung.

## Migrasi

Berkas pembuat: [`database/migrations/2026_09_19_000410_create_trip_plans_table.php`](../../../database/migrations/2026_09_19_000410_create_trip_plans_table.php)

| Kolom | Tipe | Aturan | Asal migrasi |
|---|---|---|---|
| `id` | bigint | primary key | pembuat |
| `user_id` | foreign key ke `users` | wajib, `cascadeOnDelete` | pembuat |
| `trail_id` | foreign key ke `trails` | wajib, `cascadeOnDelete` | pembuat |
| `hiking_goal_id` | foreign key ke `hiking_goals` | boleh kosong, `nullOnDelete` | pembuat |
| `name` | string | wajib | pembuat |
| `planned_date` | date | wajib | pembuat |
| `end_date` | date | boleh kosong | `2026_09_25_000917_add_plan_details_to_trip_plans_table` |
| `expected_return_at` | timestamp (UTC) | boleh kosong | `2026_10_07_000500_add_return_and_registration_to_trip_plans_table` |
| `registration_number` | string(60) | boleh kosong | `2026_10_07_000500_...` |
| `start_time` | time | boleh kosong | pembuat |
| `participants_count` | unsigned tinyint | boleh kosong | `2026_09_25_000917_...` |
| `trip_type` | string (enum) | wajib | pembuat |
| `notes` | text | boleh kosong | pembuat |
| `reminders_enabled` | boolean | bawaan `false` | `2026_09_25_000917_...` |
| `status` | string (enum) | bawaan `DRAFT`, berindeks | pembuat |
| `cancellation_reason` | string(20) | boleh kosong | `2026_10_07_000300_add_cancellation_reason_to_trip_plans_table` |
| `completed_at` | timestamp | boleh kosong | pembuat |

Kolom tambahan semuanya boleh kosong. Komentar di migrasi `000917` menjelaskan alasannya:
kosong berarti pendaki belum menyebutnya, bukan "satu hari" atau "nol orang" (R-007).
Pengingat mati sampai pendaki menyalakannya, karena surel pengingat adalah kontak keluar dari
aplikasi.

## Model

Berkas: [`app/Models/TripPlan.php`](../../../app/Models/TripPlan.php)

```php
#[Fillable([
    'user_id', 'trail_id', 'hiking_goal_id', 'name', 'planned_date', 'end_date',
    'expected_return_at', 'registration_number',
    'start_time', 'participants_count', 'trip_type', 'notes', 'reminders_enabled',
    'status', 'cancellation_reason', 'completed_at',
])]
class TripPlan extends Model
```

`user_id` dan `status` memang ada di `Fillable`, karena service mengisinya. Yang mencegah
pengguna mengirim keduanya dari formulir adalah Form Request: kedua kolom tidak ada di aturan
validasi, dan controller hanya meneruskan `$request->validated()` ke service. Test
`test_the_owner_and_the_status_come_from_the_server_not_the_form` dan
`test_ownership_and_status_cannot_be_smuggled_in` menjaga perilaku ini.

| Relasi | Tipe | Tujuan |
|---|---|---|
| `user()` | `BelongsTo` | `User` |
| `trail()` | `BelongsTo` | `Trail` |
| `hikingGoal()` | `BelongsTo` | `HikingGoal` |
| `preparationItems()` | `HasMany` | `TripPreparationItem` |
| `readinessChecks()` | `HasMany` | `ReadinessCheck` |
| `latestReadinessCheck()` | `HasOne`, `latestOfMany()` | `ReadinessCheck` |
| `latestConfirmedReadinessCheck()` | `HasOne`, `ofMany()` | `ReadinessCheck` terkonfirmasi |
| `hikingSession()` | `HasOne` | `HikingSession` |
| `reminders()` | `HasMany` | `TripReminder` |
| `history()` | `HasOne` | `HikingHistory` |

| Method lain | Fungsi |
|---|---|
| `ringkasan()` | satu baris: jenis trip, tanggal atau rentang tanggal, jumlah peserta |
| `tanggalRencanaLewat()` | rencana belum dimulai tetapi tanggalnya sudah lewat menurut zona gunung |
| `masihDirencanakan()` | belum dimulai dan tanggalnya belum lewat |
| `trailIsWithdrawn()` | jalur trip ini sudah diarsipkan admin |
| `readinessIsStale()` | status resmi jalur berubah sejak cek kesiapan terakhir |
| `preparationCompletionPercent()` | persentase butir persiapan yang sudah dikonfirmasi |
| `hasUnconfirmedCriticalItems()` | masih ada butir kritis yang belum dikonfirmasi |

## Siklus hidup di luar model

Pembuatan, perubahan, penghapusan, dan perpindahan status tidak dilakukan langsung lewat
`TripPlan::create()` di controller, tetapi lewat `App\Services\Trips\TripLifecycle`. Service
ini membuat daftar persiapan dari jalur yang dipilih, menjadwalkan pengingat, mencatat
analitik, dan menolak perpindahan status yang tidak sah. Rincian alurnya ada di
[`../05-crud-rencana-trip/README.md`](../05-crud-rencana-trip/README.md).

## Factory dan seeder

- Factory: `database/factories/TripPlanFactory.php`. Bawaannya membuat pengguna dan jalur
  baru, jenis `CAMPING`, status `PLANNED`, dan tanggal satu minggu dari sekarang.
- Seeder: hanya `DemoSeeder` yang membuat rencana trip (trip Gede via Cibodas, tiga trip
  selesai, satu trip batal). `DatabaseSeeder` tidak membuat rencana trip.
