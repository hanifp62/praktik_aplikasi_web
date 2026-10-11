<?php

namespace Tests\Feature;

use App\Enums\ExperienceLevel;
use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Enums\TripStatus;
use App\Models\Mountain;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\User;
use App\Services\PreparationService;
use App\Services\Trips\DaftarPengingat;
use App\Services\Trips\TripReminderScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tenggat terdekat di Beranda (spec v3 bagian 6.1, prototype 01, rencana induk W5 butir 2):
 * jendela pemesanan izin, hari prakiraan BMKG untuk tanggal mulai terbit, dan pengingat surel,
 * dari yang terdekat, dalam zona gunungnya. Waktu dibekukan pada Jumat 25 September 2026
 * pukul 11.00 WITA.
 */
class DashboardDeadlineTest extends TestCase
{
    use RefreshDatabase;

    private const PENGELOLA = 'Balai Taman Nasional Gunung Rinjani';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-25 03:00:00', 'UTC'));
    }

    /**
     * @param  array<string, mixed>|null  $izin  aturan izin tingkat gunung; null tanpa aturan
     */
    private function trip(string $tanggal, ?array $izin = null): TripPlan
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => ExperienceLevel::INTERMEDIATE->value, 'completed_at' => now()]);
        $gunung = Mountain::factory()->create(['name' => 'Gunung Rinjani', 'timezone' => 'Asia/Makassar']);
        $jalur = Trail::factory()->for($gunung)->published()->create(['name' => 'Rinjani via Sembalun']);

        if ($izin !== null) {
            PermitRequirement::factory()->create(array_merge(['mountain_id' => $gunung->id, 'authority' => self::PENGELOLA, 'booking_url' => 'https://contoh.test/erinjani'], $izin));
        }

        return TripPlan::factory()->for($user->fresh())->for($jalur)->create([
            'name' => 'Rinjani bersama Dimas',
            'planned_date' => $tanggal,
            'status' => TripStatus::PLANNED->value,
        ]);
    }

    private function nyalakanPengingat(TripPlan $trip): void
    {
        $trip->update(['reminders_enabled' => true]);
        app(TripReminderScheduler::class)->sinkronkan($trip->fresh());
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tenggat(TripPlan $trip): ?array
    {
        return $this->actingAs($trip->user)->get(route('dashboard'))->assertOk()->viewData('page')['props']['tenggat'];
    }

    /**
     * Prakiraan BMKG terbit di awal Rabu 7 Oktober, sebelum surel H-3 pukul 07.00; pemesanan
     * izin ditutup H-2 (Kamis) jatuh di antara dua surel. Semua dalam WITA.
     */
    public function test_the_deadlines_are_listed_nearest_first_in_the_mountains_zone(): void
    {
        $trip = $this->trip('2026-10-10', ['booking_opens_days_before' => 30, 'booking_closes_days_before' => 2]);
        $this->nyalakanPengingat($trip);

        $tenggat = $this->tenggat($trip);
        $baris = $tenggat['baris'];

        $this->assertSame('WITA', $tenggat['zona']);
        $this->assertSame([
            ['Prakiraan BMKG untuk 10 Okt mulai terbit', 'Rabu, 7 Oktober 2026'],
            ['Pengingat surel pukul 07.00', 'Rabu, 7 Oktober 2026'],
            ['Pemesanan izin ditutup (H-2)', 'Kamis, 8 Oktober 2026'],
            ['Pengingat surel pukul 07.00', 'Jumat, 9 Oktober 2026'],
        ], array_map(fn (array $b) => [$b['judul'], $b['kapan']['lengkap']], $baris));
        $this->assertSame(['tanggal' => '8', 'bulan' => 'Okt', 'tahun' => null, 'lengkap' => 'Kamis, 8 Oktober 2026'], $baris[2]['kapan']);
        $this->assertSame('Izin dipesan di sistem resmi '.self::PENGELOLA.'. Memount menautkan sistem resmi dan tidak menjual izin.', $baris[2]['keterangan']);
        $this->assertSame(['label' => 'Buka sistem resmi', 'href' => 'https://contoh.test/erinjani'], $baris[2]['tautan']);
        $this->assertSame('BMKG menerbitkan prakiraan untuk 3 hari ke depan. Sebelum itu belum ada prakiraan untuk tanggal Anda, dan Memount tidak menebaknya.', $baris[0]['keterangan']);
        $this->assertSame(['3 hari sebelum berangkat.', 'Sehari sebelum berangkat.'], [$baris[1]['keterangan'], $baris[3]['keterangan']]);
    }

    /**
     * Pemesanan yang belum dibuka menyebut hari bukanya; pengingat izin tidak menjadi baris
     * sendiri melainkan keterangan hari itu.
     */
    public function test_a_window_not_yet_open_names_its_opening_day_and_the_reminder_hour(): void
    {
        $trip = $this->trip('2026-11-20', ['booking_opens_days_before' => 30, 'booking_closes_days_before' => 3]);
        $this->nyalakanPengingat($trip);

        $baris = $this->tenggat($trip)['baris'];

        $this->assertSame(['Pemesanan izin dibuka (H-30)', 'Rabu, 21 Oktober 2026'], [$baris[0]['judul'], $baris[0]['kapan']['lengkap']]);
        $this->assertStringEndsWith('Pengingat surel dikirim pukul 07.00 hari itu.', $baris[0]['keterangan']);
        $this->assertCount(4, $baris);
    }

    public function test_a_permit_already_handled_drops_its_deadline(): void
    {
        $trip = $this->trip('2026-10-10', ['booking_opens_days_before' => 30, 'booking_closes_days_before' => 2]);
        TripPreparationItem::create([
            'trip_plan_id' => $trip->id,
            'category' => PreparationCategory::LOGISTICS->value,
            'label' => PreparationService::PERMIT_LABEL,
            'is_critical' => true,
            'status' => PreparationStatus::CONFIRMED->value,
        ]);

        $this->assertSame(['Prakiraan BMKG untuk 10 Okt mulai terbit'], array_column($this->tenggat($trip)['baris'], 'judul'));
    }

    /**
     * Pemesanan yang sudah ditutup sementara butir izinnya belum dikonfirmasi tetap ditulis,
     * sebagai peringatan; prakiraan yang sudah terbit tidak lagi menjadi tenggat.
     */
    public function test_a_closed_window_without_a_confirmed_permit_is_a_warning(): void
    {
        $trip = $this->trip('2026-09-27', ['booking_opens_days_before' => 30, 'booking_closes_days_before' => 3]);

        $baris = $this->tenggat($trip)['baris'];

        $this->assertCount(1, $baris);
        $this->assertSame(['Pemesanan izin sudah ditutup (H-3)', 'Kamis, 24 September 2026', true], [$baris[0]['judul'], $baris[0]['kapan']['lengkap'], $baris[0]['peringatan']]);
        $this->assertSame('Butir izin di persiapan belum dikonfirmasi. Bila izin belum dipesan, tanyakan langsung ke '.self::PENGELOLA.'.', $baris[0]['keterangan']);
    }

    /**
     * R-007: aturan izin tanpa jendela yang tercatat tetap disebut, tanpa tanggal, di akhir
     * daftar; tidak ditulis sedang dibuka.
     */
    public function test_a_window_never_recorded_is_named_without_a_date(): void
    {
        $trip = $this->trip('2026-10-10', ['booking_opens_days_before' => null, 'booking_closes_days_before' => null]);

        $akhir = $this->tenggat($trip)['baris'][1];

        $this->assertSame(['Jendela pemesanan izin belum tercatat', null], [$akhir['judul'], $akhir['kapan']]);
        $this->assertStringStartsWith('Periksa jendelanya di sistem resmi.', $akhir['keterangan']);
    }

    /**
     * Ruling W5-P8: tanggal buka pemesanan tidak tercatat tetapi tanggal tutup tercatat
     * (PermitService::BOOKING_BUKA_BELUM_TERCATAT) tetap menyebut tanggal tutupnya; tanggal
     * buka tidak dikarang (R-007). Kalimatnya berasal dari TenggatIzin, sumber yang sama
     * dengan lembar Pengingat (DaftarPengingat), supaya keduanya tidak pernah berbeda.
     */
    public function test_a_window_whose_opening_date_is_unrecorded_names_its_closing_date(): void
    {
        $trip = $this->trip('2026-10-10', ['booking_opens_days_before' => null, 'booking_closes_days_before' => 5]);

        $baris = $this->tenggat($trip)['baris'];

        $this->assertSame(
            ['Tanggal buka pemesanan izin belum tercatat; pemesanan ditutup (H-5)', 'Senin, 5 Oktober 2026', false],
            [$baris[0]['judul'], $baris[0]['kapan']['lengkap'], $baris[0]['peringatan']]
        );
        $this->assertSame('Izin dipesan di sistem resmi '.self::PENGELOLA.'. Memount menautkan sistem resmi dan tidak menjual izin.', $baris[0]['keterangan']);
        $this->assertSame(['label' => 'Buka sistem resmi', 'href' => 'https://contoh.test/erinjani'], $baris[0]['tautan']);
    }

    public function test_the_year_is_written_when_it_is_not_this_year(): void
    {
        $kapan = $this->tenggat($this->trip('2027-01-07'))['baris'][0]['kapan'];

        $this->assertSame(['tanggal' => '4', 'bulan' => 'Jan', 'tahun' => '2027', 'lengkap' => 'Senin, 4 Januari 2027'], $kapan);
    }

    /**
     * Penanda kalender hanya visual; pembaca layar mendapat tanggal lengkapnya, dan zona disebut
     * sekali di samping judul bagian.
     */
    public function test_the_calendar_marker_is_visual_and_the_full_date_is_read_aloud(): void
    {
        $sumber = File::get(resource_path('js/components/beranda/TenggatTerdekat.tsx'));

        $this->assertStringContainsString('{b.kapan ? <p className="sr-only">{b.kapan.lengkap}</p> : null}', $sumber);
        $this->assertSame(1, substr_count($sumber, 'aria-hidden="true"'));
        $this->assertStringContainsString('Waktu gunung, {tenggat.zona}', $sumber);
    }

    /**
     * Ruling W5-3a: tautan keluar ke sistem resmi (data sama dengan lembar Pengingat,
     * Pengingat.tsx:188-196) dibuka di tab baru dengan rel="noopener noreferrer", supaya tab
     * baru itu tidak bisa membalik mengendalikan tab Memount lewat window.opener (reverse
     * tabnabbing).
     */
    public function test_the_official_link_opens_safely_in_a_new_tab(): void
    {
        $sumber = File::get(resource_path('js/components/beranda/TenggatTerdekat.tsx'));

        $this->assertStringContainsString('target="_blank"', $sumber);
        $this->assertStringContainsString('rel="noopener noreferrer"', $sumber);
    }

    /**
     * Ruling W5-FA butir 4 (I1 tinjauan akhir W5, P4c): aturan izin dikoreksi dari H-30 ke H-14
     * sesudah pengingat dijadwalkan. Baris tenggat menyebut hari buka menurut aturan sekarang,
     * dan tidak menjanjikan surel hari itu selama baris pengingatnya masih tersimpan untuk hari
     * buka lama. Sesudah penjadwal menyelaraskan barisnya ke hari buka baru, janji itu kembali.
     */
    public function test_a_corrected_opening_rule_promises_no_mail_on_a_day_it_was_not_scheduled_for(): void
    {
        Notification::fake();
        $trip = $this->trip('2026-11-20', ['booking_opens_days_before' => 30, 'booking_closes_days_before' => 3]);
        $this->nyalakanPengingat($trip);
        PermitRequirement::query()->sole()->update(['booking_opens_days_before' => 14]);

        $baris = $this->tenggat($trip)['baris'][0];

        $this->assertSame(['Pemesanan izin dibuka (H-14)', 'Jumat, 6 November 2026'], [$baris['judul'], $baris['kapan']['lengkap']]);
        $this->assertSame('Izin dipesan di sistem resmi '.self::PENGELOLA.'. Memount menautkan sistem resmi dan tidak menjual izin.', $baris['keterangan']);

        // Penjadwal pada hari buka lama (Rabu 21 Oktober 07.05 WITA) menyelaraskan baris IZIN.
        $this->travelTo(Carbon::parse('2026-10-20 23:05:00', 'UTC'));
        $this->artisan('trips:kirim-pengingat')->run();

        $this->assertStringEndsWith('Pengingat surel dikirim pukul 07.00 hari itu.', $this->tenggat($trip)['baris'][0]['keterangan']);
    }

    /**
     * Ruling W5-FE (M2, P6): Beranda dan lembar Pengingat memakai satu predikat pengingat yang
     * akan datang (TripReminder::akanDatang()). Rabu 7 Oktober 08.00 WITA, sesudah jam surel H-3
     * lewat tetapi sebelum penjadwal sempat jalan, keduanya hanya menyebut surel H-1.
     */
    public function test_a_reminder_whose_hour_has_passed_is_neither_a_deadline_nor_on_the_sheet(): void
    {
        $trip = $this->trip('2026-10-10');
        $this->nyalakanPengingat($trip);

        $this->travelTo(Carbon::parse('2026-10-07 00:00:00', 'UTC'));

        $baris = $this->tenggat($trip)['baris'];

        $this->assertSame([['Pengingat surel pukul 07.00', 'Jumat, 9 Oktober 2026', 'Sehari sebelum berangkat.']], array_map(fn (array $b) => [$b['judul'], $b['kapan']['lengkap'], $b['keterangan']], $baris));
        $this->assertSame(['Sehari sebelum berangkat'], array_column(app(DaftarPengingat::class)->untuk($trip->user)['pengingat'], 'jenis'));
    }

    /**
     * Ruling W5-FF (M3, P7): tautan sistem resmi baris tenggat lewat penentu satu sumber berbasis
     * filled() (PermitService::tautanPeriksa(), yang juga dipakai
     * PermitService::pemesananDariJendela()), bukan truthy. Alamat berisi spasi saja, yang bisa
     * datang dari impor data tanpa TrimStrings, tidak menjadi tautan kosong.
     *
     * Ruling W5-G1 (R-007): tanpa tautan yang tampil, kalimat penutup tidak mengklaim "menautkan"
     * sistem resmi; ia mengaku tidak memesan atau menjual izin sama sekali.
     */
    public function test_a_blank_booking_address_never_becomes_a_link(): void
    {
        $trip = $this->trip('2026-10-10', ['booking_opens_days_before' => 30, 'booking_closes_days_before' => 2, 'booking_url' => '   ']);

        $izin = collect($this->tenggat($trip)['baris'])->firstWhere('judul', 'Pemesanan izin ditutup (H-2)');

        $this->assertNotNull($izin);
        $this->assertNull($izin['tautan']);
        $this->assertStringNotContainsString('menautkan', $izin['keterangan']);
        $this->assertStringContainsString('Memount tidak memesan atau menjual izin.', $izin['keterangan']);
    }

    /**
     * Trip tanpa aturan izin, tanpa pengingat, dan yang prakiraannya sudah terbit tidak punya
     * tenggat; tanpa trip mendatang juga tidak.
     */
    public function test_deadlines_belong_to_an_upcoming_trip_only(): void
    {
        $trip = $this->trip('2026-09-27');

        $this->assertNull($this->tenggat($trip));

        $trip->update(['status' => TripStatus::CANCELLED->value, 'planned_date' => '2026-10-10']);

        $this->assertNull($this->tenggat($trip));
    }
}
