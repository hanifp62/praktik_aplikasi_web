<?php

namespace Tests\Feature;

use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\PermitService;
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

    /**
     * Halaman Rencanakan (spec v3 bagian 6.8, W4 Tugas 3) memakai keadaan jendela yang sama,
     * tetapi menyebut tanggalnya untuk hari yang dipilih: kapan dibuka dan kapan ditutup,
     * siapa pengelolanya, dan bahwa Memount hanya menautkan sistem resmi.
     *
     * Ruling W5-G1 (R-007): klaim "menautkan" hanya ditulis ketika tautannya sungguh tampil.
     * Keadaan BELUM_DIBUKA tidak pernah punya tautan (PermitService::pemesananDariJendela()),
     * jadi kalimat penutupnya mengaku tidak memesan atau menjual izin, bukan "menautkan".
     */
    public function test_the_plan_page_names_the_booking_dates_for_the_chosen_day(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        PermitRequirement::factory()->create([
            'trail_id' => $trail->id,
            'authority' => 'Balai Besar TNGGP',
            'booking_url' => 'https://booking.example.test',
            'booking_opens_days_before' => 30,
            'booking_closes_days_before' => 3,
            'daily_quota' => null,
        ]);
        $user = User::factory()->create();
        $izin = fn (string $tanggal): ?array => $this->actingAs($user)
            ->get(route('trips.create', ['trail' => $trail->id, 'tanggal' => $tanggal]))
            ->viewData('page')['props']['izin'];

        $this->assertSame([
            'keadaan' => 'terbuka',
            'judul' => 'Pemesanan izin terbuka sampai Rab, 7 Okt',
            'keterangan' => 'Dibuka sejak Kam, 10 Sep di sistem resmi Balai Besar TNGGP (H-30 sampai H-3). Memount menautkan sistem resmi dan tidak menjual izin.',
            'tautan' => ['href' => 'https://booking.example.test', 'teks' => 'Pesan di sistem resmi, paling lambat Rab, 7 Okt', 'label' => 'Pesan'],
            'kuota' => 'Kuota harian jalur ini belum ada sumber terverifikasi. Pastikan saat memesan izin.',
        ], $izin('2026-10-10'));

        // M1 tinjauan akhir W4: jendela yang belum dibuka atau sudah tutup tidak mengajak memesan.
        $belum = $izin('2026-11-20');
        $this->assertSame(['belum', 'Pemesanan izin dibuka mulai Rab, 21 Okt', null], [$belum['keadaan'], $belum['judul'], $belum['tautan']]);
        // Ruling W5-G1: tanpa tautan yang tampil (selalu null untuk keadaan ini), kalimat
        // penutup tidak mengklaim "menautkan" walau aturan ini punya booking_url tercatat.
        $this->assertStringNotContainsString('menautkan', $belum['keterangan']);
        $this->assertStringContainsString('Memount tidak memesan atau menjual izin.', $belum['keterangan']);
        $this->assertSame(['tutup', 'Pemesanan izin sudah tutup, hari terakhirnya Rab, 9 Sep', null], [$izin('2026-09-12')['keadaan'], $izin('2026-09-12')['judul'], $izin('2026-09-12')['tautan']]);

        $tanpaTanggal = $izin('');
        $this->assertSame('tanpaTanggal', $tanpaTanggal['keadaan']);
        $this->assertSame('Pilih tanggal naik untuk melihat kapan pemesanannya dibuka dan ditutup. Dikelola Balai Besar TNGGP (H-30 sampai H-3).', $tanpaTanggal['keterangan']);
    }

    /**
     * Kuota hanya disebut sebagai aturan yang tercatat, tidak pernah sebagai sisa kuota
     * (§43); kuota tanpa verifikasi disebut belum terverifikasi. Aturan tanpa jendela yang
     * tercatat tidak ditulis "sedang dibuka" (R-007), dan jalur tanpa aturan tidak mendapat
     * kotak izin sama sekali.
     */
    public function test_the_plan_page_never_invents_a_quota_or_a_window(): void
    {
        $user = User::factory()->create();
        $izin = function (array $aturan) use ($user): ?array {
            $trail = Trail::factory()->published()->easy()->create();

            if ($aturan !== []) {
                PermitRequirement::factory()->create(['trail_id' => $trail->id, 'authority' => 'Balai Taman Nasional'] + $aturan);
            }

            return $this->actingAs($user)->get(route('trips.create', ['trail' => $trail->id, 'tanggal' => '2026-10-10']))->viewData('page')['props']['izin'];
        };

        $terverifikasi = $izin(['daily_quota' => 600, 'verified_at' => now(), 'booking_opens_days_before' => 30, 'booking_closes_days_before' => 3]);
        $this->assertSame('Kuota harian tercatat 600 pendaki. Sisa kuota hanya ada di sistem resmi.', $terverifikasi['kuota']);

        $belumTerverifikasi = $izin(['daily_quota' => 600, 'verified_at' => null, 'booking_opens_days_before' => 30, 'booking_closes_days_before' => 3]);
        $this->assertSame('Kuota harian tercatat 600 pendaki, belum terverifikasi. Pastikan saat memesan izin.', $belumTerverifikasi['kuota']);

        $tanpaJendela = $izin(['booking_opens_days_before' => null, 'booking_closes_days_before' => null]);
        $this->assertSame('tanpaJendela', $tanpaJendela['keadaan']);
        $this->assertSame('Jalur ini mewajibkan izin', $tanpaJendela['judul']);
        $this->assertSame('Jendela pemesanannya belum tercatat di Memount. Periksa ke Balai Taman Nasional.', $tanpaJendela['keterangan']);

        $this->assertNull($izin([]));
    }

    /**
     * Ruling W4-FE (R-007): aturan yang mencatat tanggal tutup tetapi tidak tanggal buka tidak
     * pernah ditulis "terbuka". Rencanakan menyebut tanggal buka belum tercatat dan hari terakhir
     * pemesanannya, dengan tautan netral "Periksa", bukan ajakan memesan; ruang kerja menulis
     * keadaan yang sama; dan mesin kecocokan (W3) tidak mendapat peringatan baru.
     */
    public function test_a_half_recorded_window_is_never_written_as_open(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        PermitRequirement::factory()->create([
            'trail_id' => $trail->id,
            'authority' => 'Balai Besar TNGGP',
            'booking_url' => 'https://booking.example.test',
            'booking_opens_days_before' => null,
            'booking_closes_days_before' => 3,
        ]);
        $user = User::factory()->create();
        $trip = TripPlan::factory()->create(['user_id' => $user->id, 'trail_id' => $trail->id, 'planned_date' => '2026-10-10']);

        $izin = $this->actingAs($user)->get(route('trips.create', ['trail' => $trail->id, 'tanggal' => '2026-10-10']))->viewData('page')['props']['izin'];
        $pesanRuangKerja = $this->izin($trip);

        $this->assertSame('bukaBelumTercatat', $izin['keadaan']);
        $this->assertSame('Tanggal buka pemesanan izin belum tercatat', $izin['judul']);
        $this->assertSame('Hari terakhir pemesanan Rab, 7 Okt. Periksa jendelanya di sistem resmi Balai Besar TNGGP (ditutup H-3). Memount menautkan sistem resmi dan tidak menjual izin.', $izin['keterangan']);
        $this->assertSame(['href' => 'https://booking.example.test', 'teks' => 'Periksa di sistem resmi', 'label' => 'Periksa'], $izin['tautan']);
        $this->assertStringContainsString('belum tercatat', $pesanRuangKerja);
        $this->assertStringNotContainsString('sedang dibuka', $pesanRuangKerja);
        $this->assertNull(app(PermitService::class)->bookingWarningFor($trail, Carbon::parse('2026-10-10')));
    }

    /**
     * W4-FK N4: judul dan tautan izin Rencanakan berasal dari satu jendela. Bila keadaan jendela
     * berganti di antara dua pembacaan "hari ini" (pergantian tengah malam), judul "terbuka"
     * tidak boleh bersanding dengan tautan dari keadaan lain. Muat ulang parsial "izin" saja,
     * supaya hanya pembacaan Rencanakan sendiri yang dihitung.
     */
    public function test_the_plan_page_writes_the_permit_title_and_link_from_one_window(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        $aturan = PermitRequirement::factory()->create(['trail_id' => $trail->id, 'authority' => 'Balai Besar TNGGP', 'booking_url' => 'https://booking.example.test', 'booking_opens_days_before' => 30, 'booking_closes_days_before' => 3]);
        $user = User::factory()->create();
        $query = ['trail' => $trail->id, 'tanggal' => '2026-10-10'];
        $versi = (string) $this->actingAs($user)->get(route('trips.create', $query))->viewData('page')['version'];
        $terbuka = ['state' => PermitService::BOOKING_TERBUKA, 'message' => '', 'requirement' => $aturan, 'buka' => Carbon::parse('2026-09-10'), 'tutup' => Carbon::parse('2026-10-07')];
        $this->partialMock(PermitService::class, fn ($mock) => $mock->shouldReceive('bookingWindowFor')->andReturn($terbuka, ['state' => PermitService::BOOKING_DITUTUP] + $terbuka));

        $izin = $this->actingAs($user)->get(route('trips.create', $query), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $versi,
            'X-Inertia-Partial-Component' => 'Trips/Create',
            'X-Inertia-Partial-Data' => 'izin',
        ])->assertOk()->json('props.izin');

        $this->assertSame('terbuka', $izin['keadaan']);
        $this->assertSame(['href' => 'https://booking.example.test', 'teks' => 'Pesan di sistem resmi, paling lambat Rab, 7 Okt', 'label' => 'Pesan'], $izin['tautan']);
    }

    /**
     * Rencanakan membaca "hari ini" dua kali: sekali di lantai tanggal, sekali di jendela
     * pemesanan. Bila tengah malam lewat di antaranya, jendelanya tidak ada lagi; halaman tetap
     * terbuka tanpa kotak izin, bukan gagal dan bukan menebak keadaan jendelanya (R-007).
     */
    public function test_the_plan_page_writes_no_permit_state_when_the_window_is_gone(): void
    {
        $trail = Trail::factory()->published()->easy()->create();
        PermitRequirement::factory()->create(['trail_id' => $trail->id, 'booking_opens_days_before' => 30, 'booking_closes_days_before' => 3]);
        $this->partialMock(PermitService::class, fn ($mock) => $mock->shouldReceive('bookingWindowFor')->andReturn(null));

        $respons = $this->actingAs(User::factory()->create())
            ->get(route('trips.create', ['trail' => $trail->id, 'tanggal' => '2026-10-10']));

        $respons->assertOk();
        $this->assertNull($respons->viewData('page')['props']['izin']);
    }
}
