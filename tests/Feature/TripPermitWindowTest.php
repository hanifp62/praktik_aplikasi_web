<?php

namespace Tests\Feature;

use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Jendela pemesanan izin bergerak relatif terhadap tanggal rencana yang sudah dipilih.
 *
 * Peringatan izin hanya pernah muncul sekali, pada saat rekomendasi. Setelah trip
 * dibuat, alur trip tidak menyebut izin sama sekali: tanggalnya terus mendekat, jendela
 * pemesanannya tetap, dan tidak ada yang memberi tahu pemiliknya.
 *
 * Pendaki Semeru yang merencanakan empat puluh hari di muka diberi tahu "pemesanan baru
 * dibuka H-30", lalu tidak pernah diberi tahu apa pun lagi, termasuk ketika pemesanannya
 * benar-benar dibuka dan ketika akhirnya ditutup H-2. Ia sampai di pos tanpa izin.
 *
 * Kuota harian sengaja tidak ikut dinilai. Sistem ini tidak tersambung ke sistem
 * pemesanan mana pun, jadi ia tidak dapat tahu kuota sudah habis atau belum, dan §43
 * melarangnya berbicara seolah ia otoritasnya.
 */
class TripPermitWindowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Pukul 08:00 UTC, jauh dari 17:00 UTC tempat tanggal WIB sudah berganti hari
     * sementara tanggal UTC belum. planned_date di bawah dibangun dari
     * Carbon::now('Asia/Jakarta') lalu dibandingkan dengan now() biasa (UTC) di
     * PermitService; tanpa jangkar ini keduanya bisa berselisih satu hari tergantung
     * jam sungguhan saat suite berjalan.
     */
    private const HARI_DIUJI_UTC = '2026-09-10 08:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse(self::HARI_DIUJI_UTC, 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function tripBerjarak(int $hari, bool $denganIzin = true): TripPlan
    {
        $trail = Trail::factory()->easy()->create();

        if ($denganIzin) {
            PermitRequirement::factory()->create([
                'trail_id' => $trail->id,
                'authority' => 'Balai Besar TN Bromo Tengger Semeru',
                'booking_opens_days_before' => 30,
                'booking_closes_days_before' => 2,
            ]);
        }

        return TripPlan::factory()->create([
            'user_id' => User::factory()->create()->id,
            'trail_id' => $trail->id,
            'planned_date' => Carbon::now('Asia/Jakarta')->addDays($hari)->toDateString(),
        ]);
    }

    /**
     * Kalimat izin yang dikirim server ke halaman trip (React), atau string kosong bila
     * halaman tidak menyebut izin sama sekali.
     */
    private function izin(TripPlan $trip): string
    {
        $props = $this->actingAs($trip->user)->get(route('trips.show', $trip))->assertOk()->viewData('page')['props'];

        return $props['izin']['pesan'] ?? '';
    }

    public function test_it_says_when_booking_has_not_opened_yet(): void
    {
        $pesan = $this->izin($this->tripBerjarak(40));
        $this->assertStringContainsString('Pemesanan izin', $pesan);
        $this->assertStringContainsString('belum dibuka', $pesan);
    }

    /**
     * Keadaan yang paling penting dan justru satu-satunya yang tidak pernah berupa
     * peringatan: pemesanannya sedang terbuka dan pendaki harus bertindak sekarang.
     */
    public function test_it_says_when_booking_is_open_and_names_the_deadline(): void
    {
        $pesan = $this->izin($this->tripBerjarak(10));
        $this->assertStringContainsString('sedang dibuka', $pesan);
        $this->assertStringContainsString('H-2', $pesan);
    }

    public function test_it_says_when_booking_has_closed(): void
    {
        $pesan = $this->izin($this->tripBerjarak(1));
        $this->assertStringContainsString('sudah ditutup', $pesan);
    }

    /**
     * Penyelenggaranya disebut karena pendaki harus tahu kepada siapa ia berurusan,
     * dan karena sistem ini bukan otoritasnya (§43).
     */
    public function test_it_names_the_authority(): void
    {
        $pesan = $this->izin($this->tripBerjarak(10));
        $this->assertStringContainsString('Balai Besar TN Bromo Tengger Semeru', $pesan);
    }

    public function test_a_trail_without_a_permit_requirement_says_nothing(): void
    {
        $pesan = $this->izin($this->tripBerjarak(10, denganIzin: false));
        $this->assertStringNotContainsString('Pemesanan izin', $pesan);
    }

    /**
     * Pukul 18:00 UTC sudah pukul 01:00 keesokan harinya di WIB (UTC+7): persis jendela
     * tujuh jam yang dijelaskan di docblock Timezone::earliestDateInIndonesia(). Rencana
     * yang jatuh H-1 menurut WIB, dengan jendela tutup H-2, harus terbaca DITUTUP. Bila
     * PermitService memakai now() mentah (UTC, masih membaca tanggal kemarin), acuan
     * "hari ini"-nya mundur satu hari sehingga daysAhead ikut lebih besar satu, dan
     * rencana yang sama justru terbaca masih TERBUKA padahal tenggatnya sudah lewat.
     */
    public function test_it_says_booking_closed_when_wib_date_has_rolled_over_but_utc_has_not(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-10 18:00:00', 'UTC'));

        $trail = Trail::factory()->easy()->create();

        PermitRequirement::factory()->create([
            'trail_id' => $trail->id,
            'authority' => 'Balai Besar TN Bromo Tengger Semeru',
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 2,
        ]);

        $trip = TripPlan::factory()->create([
            'user_id' => User::factory()->create()->id,
            'trail_id' => $trail->id,
            'planned_date' => '2026-09-12',
        ]);

        $pesan = $this->izin($trip);
        $this->assertStringContainsString('sudah ditutup', $pesan);
        $this->assertStringNotContainsString('sedang dibuka', $pesan);
    }
}
