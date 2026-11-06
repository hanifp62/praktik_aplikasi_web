<?php

namespace Tests\Feature;

use App\Enums\AuthorityType;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Models\Authority;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\Trips\TripLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Pesan rencana perjalanan untuk kontak (spec v3 bagian 10.8; rencana induk W6 butir 8; ruling
 * W6-R4): teks WhatsApp atau SMS dengan instruksi eskalasi satu jam sesudah jam kembali, dan
 * pernyataan bahwa Memount tidak memantau perjalanan. Mati sampai pemilik memutus Q14.
 *
 * Diperluas pra-tinjau Tugas 7 (ruling P-R10): jam kembali yang tertinggal oleh trip yang
 * dipindah tidak menghasilkan pesan (PT7-7a), jam kembali hari yang sama harus sesudah jam mulai
 * (PT7-7b), mengubah tanggal atau jam mulai mengosongkan jam kembali (ruling W6-7b), trip yang
 * sudah usai menolak jam kembali baru (PT7-8), kalimat pesan menyebut hanya isian yang ada
 * (PT7-9), batas laju dan sakelar tidak membocorkan rute (PT7-10 M12, PT7-17), serta fokus dan
 * galat jaringan formulir (PT7-2) dan salinan manual pesan yang utuh (PT7-16).
 */
class TripPlanMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-07 03:00:00', 'UTC'));
        config(['hiking.field_safety.enabled' => true]);
    }

    /**
     * Budi berencana camping di Gede via Cibodas 17 sampai 18 Oktober 2026, mulai 07.00, bertiga.
     *
     * @return array{0: User, 1: TripPlan}
     */
    private function rencana(string $zona = 'Asia/Jakarta', string $gunung = 'Gunung Gede', string $provinsi = 'Jawa Barat'): array
    {
        $user = User::factory()->create(['name' => 'Budi Santoso']);
        $trail = Trail::factory()->create([
            'name' => 'Gede via Cibodas',
            'mountain_id' => Mountain::factory()->create(['name' => $gunung, 'province' => $provinsi, 'timezone' => $zona])->id,
        ]);
        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'name' => 'Gede akhir pekan',
            'planned_date' => '2026-10-17',
            'end_date' => '2026-10-18',
            'start_time' => '07:00',
            'participants_count' => 3,
            'status' => TripStatus::PLANNED->value,
        ]);

        return [$user, $trip];
    }

    private function simpanKembali(User $user, TripPlan $trip, array $isian = []): TestResponse
    {
        return $this->actingAs($user)->from(route('trips.show', $trip))->put(route('trips.return.update', $trip), [
            'kembali_tanggal' => '2026-10-18',
            'kembali_jam' => '15:00',
            'registrasi' => null,
            ...$isian,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function keselamatan(User $user, TripPlan $trip): array
    {
        return $this->actingAs($user)->get(route('trips.show', $trip))->viewData('page')['props']['keselamatan'];
    }

    /**
     * Isian formulir ubah rencana dengan nilai trip ini, ditimpa $ubah.
     *
     * @return array<string, mixed>
     */
    private function isianRencana(TripPlan $trip, array $ubah = []): array
    {
        return [
            'name' => $trip->name,
            'planned_date' => '2026-10-17',
            'end_date' => '2026-10-18',
            'start_time' => '07:00',
            'participants_count' => '3',
            'trip_type' => TripType::CAMPING->value,
            'notes' => '',
            ...$ubah,
        ];
    }

    public function test_without_a_return_time_there_is_no_message(): void
    {
        [$user, $trip] = $this->rencana();

        $this->assertNull($this->keselamatan($user, $trip)['pesan']);
        $this->assertSame('Isi perkiraan jam kembali dulu. Tanpa itu, kontak Anda tidak tahu kapan harus bertindak.', $this->keselamatan($user, $trip)['kurang']);
    }

    public function test_the_message_escalates_one_hour_after_the_return_time(): void
    {
        [$user, $trip] = $this->rencana();

        $this->simpanKembali($user, $trip)
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('status', 'Jam kembali disimpan. Pesan rencana sudah memakai jam ini.');

        $this->assertSame('2026-10-18 08:00:00', $trip->fresh()->expected_return_at->utc()->format('Y-m-d H:i:s'));

        $keselamatan = $this->keselamatan($user, $trip);

        $this->assertSame(implode("\n", [
            'Rencana pendakian Budi Santoso',
            'Jalur: Gede via Cibodas, Gunung Gede, Jawa Barat',
            'Mulai: Sabtu, 17 Oktober 2026 pukul 07.00 WIB',
            'Peserta: 3 orang',
            'Perkiraan kembali: Minggu, 18 Oktober 2026 pukul 15.00 WIB',
            '',
            'Bila belum ada kabar dari saya sampai Minggu, 18 Oktober 2026 pukul 16.00 WIB, hubungi basecamp jalur ini, lalu Basarnas 115 atau 112. Sebutkan nama jalur, tanggal mulai, dan jumlah peserta.',
            '',
            'Memount tidak memantau perjalanan ini dan tidak membagikan lokasi saya.',
        ]), $keselamatan['pesan']);
        $this->assertSame(['tanggal' => '2026-10-18', 'jam' => '15:00'], $keselamatan['kembali']);
        $this->assertDoesNotMatchRegularExpression('/\b(aman|dijamin|terjamin|pasti)\b/i', $keselamatan['pesan']);
    }

    /**
     * Jam dibaca dalam zona gunung: 15.00 WITA di Rinjani adalah 07.00 UTC, dan pesannya
     * menulis WITA.
     */
    public function test_the_return_time_is_read_in_the_mountain_zone(): void
    {
        [$user, $trip] = $this->rencana('Asia/Makassar', 'Gunung Rinjani', 'Nusa Tenggara Barat');

        $this->simpanKembali($user, $trip);

        $this->assertSame('2026-10-18 07:00:00', $trip->fresh()->expected_return_at->utc()->format('Y-m-d H:i:s'));
        $this->assertStringContainsString('sampai Minggu, 18 Oktober 2026 pukul 16.00 WITA', $this->keselamatan($user, $trip)['pesan']);
    }

    public function test_the_registration_number_is_named_in_the_message(): void
    {
        [$user, $trip] = $this->rencana();

        $this->simpanKembali($user, $trip, ['registrasi' => 'GPP-1017-0042']);

        $pesan = $this->keselamatan($user, $trip)['pesan'];

        $this->assertStringContainsString("Nomor registrasi: GPP-1017-0042\n", $pesan);
        $this->assertStringContainsString('Sebutkan nama jalur, tanggal mulai, jumlah peserta, dan nomor registrasi.', $pesan);
    }

    public function test_the_return_time_is_validated_and_owned(): void
    {
        [$user, $trip] = $this->rencana();

        $this->simpanKembali($user, $trip, ['kembali_tanggal' => '2026-10-16', 'kembali_jam' => '25:61', 'registrasi' => str_repeat('A', 61)])
            ->assertSessionHasErrors([
                'kembali_tanggal' => 'Tanggal kembali tidak boleh sebelum tanggal mulai trip.',
                'kembali_jam' => 'Tulis jam kembali sebagai jam dan menit, misalnya 15:00.',
                'registrasi' => 'Nomor registrasi paling panjang 60 karakter.',
            ]);

        $this->simpanKembali(User::factory()->create(), $trip)->assertForbidden();

        $this->assertNull($trip->fresh()->expected_return_at);
    }

    public function test_while_the_switch_is_off_nothing_is_offered(): void
    {
        config(['hiking.field_safety.enabled' => false]);
        [$user, $trip] = $this->rencana();

        $this->actingAs($user)->get(route('trips.show', $trip))->assertInertia(fn (AssertableInertia $page) => $page->where('keselamatan', null));
        $this->simpanKembali($user, $trip)->assertNotFound();
        $this->simpanKembali(User::factory()->create(), $trip)->assertNotFound();
    }

    public function test_saving_the_return_time_is_rate_limited(): void
    {
        [$user, $trip] = $this->rencana();

        foreach (range(1, (int) config('hiking.rate_limits.personal_writes_per_minute')) as $ignored) {
            $this->simpanKembali($user, $trip)->assertRedirect();
        }

        $this->simpanKembali($user, $trip)->assertTooManyRequests();
    }

    /**
     * PT7-7a: jam kembali yang tertinggal oleh trip yang dimundurkan di luar formulir rencana
     * tidak menghasilkan pesan eskalasi sebelum pendakian dimulai.
     */
    public function test_a_return_time_left_behind_by_a_moved_trip_makes_no_message(): void
    {
        [$user, $trip] = $this->rencana();
        $this->simpanKembali($user, $trip);
        $trip->update(['planned_date' => '2026-10-24', 'end_date' => '2026-10-25']);

        $keselamatan = $this->keselamatan($user, $trip);

        $this->assertNull($keselamatan['pesan']);
        $this->assertSame('Jam kembali yang tersimpan tidak sesudah mulai trip. Perbarui jam kembali supaya kontak Anda tahu kapan harus bertindak.', $keselamatan['kurang']);
    }

    /**
     * PT7-7b: pada hari mulai, jam kembali harus sesudah jam mulai. Trip tanpa jam mulai diuji
     * terpisah (ruling W6-7i, M-3).
     */
    public function test_the_return_time_must_follow_the_start_on_the_same_day(): void
    {
        [$user, $trip] = $this->rencana();

        $this->simpanKembali($user, $trip, ['kembali_tanggal' => '2026-10-17', 'kembali_jam' => '07:00'])
            ->assertSessionHasErrors(['kembali_jam' => 'Jam kembali harus sesudah jam mulai trip, 07.00.']);
        $this->assertNull($trip->fresh()->expected_return_at);

        $this->simpanKembali($user, $trip, ['kembali_tanggal' => '2026-10-17', 'kembali_jam' => '07:01'])->assertSessionHasNoErrors();
        $this->assertSame('2026-10-17 00:01:00', $trip->fresh()->expected_return_at->utc()->format('Y-m-d H:i:s'));
    }

    /**
     * Ruling W6-7b (PT7-7c, preseden W4-4d): mengubah tanggal atau jam mulai lewat formulir
     * rencana mengosongkan jam kembali, dan ruang kerja meminta jam kembali diisi lagi dengan
     * kalimat persis. Trip yang dimajukan hanya tertutup oleh aturan ini: jam kembali lamanya
     * masih sesudah mulai yang baru.
     */
    public function test_moving_the_start_clears_the_return_time_and_asks_for_it_again(): void
    {
        [$user, $trip] = $this->rencana();
        $minta = 'Tanggal atau jam mulai trip berubah. Isi jam kembali lagi supaya kontak Anda tahu kapan harus bertindak.';

        foreach ([['planned_date' => '2026-10-16', 'end_date' => '2026-10-16'], ['start_time' => '05:30']] as $ubah) {
            $this->simpanKembali($user, $trip, ['registrasi' => 'GPP-1017-0042']);
            $this->assertNotNull($trip->fresh()->expected_return_at);

            $this->actingAs($user)->followingRedirects()->put(route('trips.update', $trip), $this->isianRencana($trip, $ubah))
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->where('keselamatan.pesan', null)
                    ->where('keselamatan.kurang', $minta)
                    ->where('keselamatan.kembali', ['tanggal' => null, 'jam' => null]));

            $this->assertNull($trip->fresh()->expected_return_at);
            $this->assertSame('GPP-1017-0042', $trip->fresh()->registration_number, 'Nomor registrasi bukan bagian dari jam mulai.');
            $this->assertSame('Isi perkiraan jam kembali dulu. Tanpa itu, kontak Anda tidak tahu kapan harus bertindak.', $this->keselamatan($user, $trip)['kurang']);

            $trip->update(['planned_date' => '2026-10-17', 'end_date' => '2026-10-18', 'start_time' => '07:00']);
        }
    }

    /**
     * Ruling W6-7b: perubahan lain di rencana tidak mengosongkan jam kembali. Jam mulai disimpan
     * dalam bentuk kolom time PostgreSQL ('07:00:00') sementara formulir mengirim '07:00': jam
     * yang sama bukan perpindahan (R-024).
     */
    public function test_other_plan_changes_keep_the_return_time(): void
    {
        [$user, $trip] = $this->rencana();
        $trip->forceFill(['start_time' => '07:00:00'])->save();
        $this->simpanKembali($user, $trip);

        $this->actingAs($user)->put(route('trips.update', $trip), $this->isianRencana($trip, ['name' => 'Gede bertiga', 'participants_count' => '4', 'end_date' => '2026-10-19', 'notes' => 'Bawa jas hujan']))
            ->assertRedirect(route('trips.show', $trip));

        $this->assertSame('2026-10-18 08:00:00', $trip->fresh()->expected_return_at?->utc()->format('Y-m-d H:i:s'));
        $this->assertNull($this->keselamatan($user, $trip)['kurang']);
    }

    /**
     * Ruling W6-7g (I-1 usulan A): jam kembali yang ditulis tab lain sesudah model trip dimuat
     * ikut dikosongkan saat mulai berpindah. Pengosongan memakai UPDATE bersyarat pada baris,
     * bukan nilai model yang dimuat di awal permintaan (pola test basi W6-3u).
     */
    public function test_moving_the_start_clears_a_return_time_written_after_the_model_was_loaded(): void
    {
        [$user, $trip] = $this->rencana();
        $dimuat = TripPlan::query()->findOrFail($trip->id);
        TripPlan::query()->whereKey($trip->id)->update(['expected_return_at' => Carbon::parse('2026-10-18 08:00:00', 'UTC')]);

        $this->assertTrue(app(TripLifecycle::class)->update($dimuat, $this->isianRencana($trip, ['planned_date' => '2026-10-16', 'end_date' => '2026-10-16'])));
        $this->assertNull($trip->fresh()->expected_return_at);
    }

    /**
     * Ruling W6-7g (I-1 usulan B): jam kembali yang divalidasi terhadap mulai lama ditolak bila
     * ubah rencana dari tab lain memindah mulai sebelum UPDATE berjalan (compare-and-swap pada
     * tanggal dan jam mulai). Kait retrieved memindah tanggal tepat sesudah pengikatan rute.
     */
    public function test_a_return_time_is_refused_when_the_start_moved_after_the_page_was_loaded(): void
    {
        [$user, $trip] = $this->rencana();
        $ubahan = null;
        TripPlan::retrieved(function (TripPlan $dimuat) use (&$ubahan, $trip): void {
            if ($ubahan !== null && $dimuat->id === $trip->id) {
                DB::table('trip_plans')->where('id', $trip->id)->update($ubahan);
                $ubahan = null;
            }
        });

        // Tanggal mulai berpindah, lalu jam mulai saja yang berpindah: keduanya bagian dari mulai.
        foreach ([['planned_date' => '2026-10-10', 'end_date' => '2026-10-11'], ['start_time' => '05:00']] as $pindah) {
            $ubahan = $pindah;

            $this->simpanKembali($user, $trip)->assertSessionHas('penolakan-trip', 'Jam kembali tidak diubah: tanggal atau jam mulai trip berubah sejak halaman ini dibuka. Periksa rencananya, lalu isi jam kembali lagi.');
            $this->assertNull($trip->fresh()->expected_return_at);

            DB::table('trip_plans')->where('id', $trip->id)->update(['planned_date' => '2026-10-17', 'end_date' => '2026-10-18', 'start_time' => '07:00']);
        }
    }

    /**
     * Ruling W6-7i (M-1 N03): jam kembali tepat pada mulai trip juga basi; eskalasinya jatuh
     * satu jam sesudah pendakian dimulai.
     */
    public function test_a_return_time_exactly_at_the_start_makes_no_message(): void
    {
        [$user, $trip] = $this->rencana();
        TripPlan::query()->whereKey($trip->id)->update(['expected_return_at' => Carbon::parse('2026-10-17 00:00:00', 'UTC')]);

        $keselamatan = $this->keselamatan($user, $trip);

        $this->assertNull($keselamatan['pesan']);
        $this->assertSame('Jam kembali yang tersimpan tidak sesudah mulai trip. Perbarui jam kembali supaya kontak Anda tahu kapan harus bertindak.', $keselamatan['kurang']);
    }

    /**
     * Ruling W6-7i (M-1 N11): tanpa jam kembali, memindah mulai tidak memunculkan kalimat "Isi
     * jam kembali lagi", karena tidak ada yang dikosongkan.
     */
    public function test_moving_the_start_without_a_return_time_asks_for_nothing_new(): void
    {
        [$user, $trip] = $this->rencana();

        $this->actingAs($user)->followingRedirects()->put(route('trips.update', $trip), $this->isianRencana($trip, ['start_time' => '05:30']))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('keselamatan.kurang', 'Isi perkiraan jam kembali dulu. Tanpa itu, kontak Anda tidak tahu kapan harus bertindak.'));
    }

    /**
     * Ruling W6-7i (M-1 N25): aturan jam mulai hanya berlaku pada tanggal mulai. Kembali 06.00
     * keesokan harinya, sebelum jam mulai 07.00, sah.
     */
    public function test_the_start_time_rule_only_applies_on_the_start_date(): void
    {
        [$user, $trip] = $this->rencana();

        $this->simpanKembali($user, $trip, ['kembali_tanggal' => '2026-10-18', 'kembali_jam' => '06:00'])->assertSessionHasNoErrors();
        $this->assertSame('2026-10-17 23:00:00', $trip->fresh()->expected_return_at->utc()->format('Y-m-d H:i:s'));
    }

    /**
     * Ruling W6-7i (M-3, probe P2 tinjauan): trip tanpa jam mulai memakai 00.00 tanggal mulai,
     * sama dengan predikat pesan, jadi validasi tidak lagi menerima jam kembali yang lalu
     * ditolak pesan rencana dengan kabar "Pesan rencana sudah memakai jam ini.".
     */
    public function test_a_trip_without_a_start_time_needs_a_return_after_midnight_of_its_first_day(): void
    {
        [$user, $trip] = $this->rencana();
        $trip->update(['start_time' => null]);

        $this->simpanKembali($user, $trip, ['kembali_tanggal' => '2026-10-17', 'kembali_jam' => '00:00'])
            ->assertSessionHasErrors(['kembali_jam' => 'Jam kembali harus sesudah pukul 00.00 pada tanggal mulai trip.']);
        $this->assertNull($trip->fresh()->expected_return_at);

        $this->simpanKembali($user, $trip, ['kembali_tanggal' => '2026-10-17', 'kembali_jam' => '00:01'])
            ->assertSessionHas('status', 'Jam kembali disimpan. Pesan rencana sudah memakai jam ini.');
        $this->assertNotNull($this->keselamatan($user, $trip)['pesan']);
    }

    /**
     * PT7-8: trip yang sudah selesai atau dibatalkan (misalnya di tab lain) menolak jam kembali
     * baru lewat UPDATE bersyarat, bukan kabar "sudah disimpan" untuk kartu yang sudah lenyap.
     */
    public function test_a_finished_trip_refuses_a_new_return_time(): void
    {
        [$user, $trip] = $this->rencana();

        foreach ([TripStatus::COMPLETED, TripStatus::CANCELLED] as $status) {
            TripPlan::query()->whereKey($trip->id)->update(['status' => $status->value]);

            $this->simpanKembali($user, $trip)
                ->assertRedirect(route('trips.show', $trip))
                ->assertSessionHas('penolakan-trip', 'Jam kembali tidak diubah: trip ini sudah selesai atau dibatalkan.')
                ->assertSessionMissing('status');

            $this->assertNull($trip->fresh()->expected_return_at);
        }
    }

    /**
     * PT7-10 M4: ruang kerja trip yang sudah usai tidak menawarkan pesan rencana.
     */
    public function test_a_finished_trip_offers_no_message(): void
    {
        [$user, $trip] = $this->rencana();
        $trip->update(['status' => TripStatus::COMPLETED->value]);

        $this->actingAs($user)->get(route('trips.show', $trip))->assertInertia(fn (AssertableInertia $page) => $page->where('keselamatan', null));
    }

    /**
     * PT7-9: pesan hanya meminta kontak menyebut isian yang ada di pesan itu.
     */
    public function test_the_message_asks_only_for_what_it_carries(): void
    {
        [$user, $trip] = $this->rencana();
        $trip->update(['participants_count' => null]);
        $this->simpanKembali($user, $trip);

        $pesan = $this->keselamatan($user, $trip)['pesan'];

        $this->assertStringNotContainsString('Peserta:', $pesan);
        $this->assertStringContainsString('Sebutkan nama jalur dan tanggal mulai.', $pesan);
        $this->assertStringNotContainsString('jumlah peserta', $pesan);
    }

    /**
     * PT7-9 dan PT7-10 M1, ruling W6-7a: pengelola resmi gunung disebut nama dan kontaknya,
     * tanpa kurung bertingkat dan tanpa menyebutnya pengelola jalur.
     */
    public function test_the_message_names_the_official_manager_of_the_mountain(): void
    {
        [$user, $trip] = $this->rencana();
        $balai = Authority::create([
            'name' => 'Balai Besar Taman Nasional Gunung Gede Pangrango',
            'slug' => 'bbtnggp',
            'abbreviation' => 'BBTNGGP',
            'type' => AuthorityType::NATIONAL_PARK->value,
            'contact' => '(0263) 512-776',
            'verified_at' => now()->subMonth(),
        ]);
        $balai->mountains()->attach($trip->trail->mountain_id);
        $this->simpanKembali($user, $trip);

        $this->assertStringContainsString(
            'hubungi pengelola resmi di gunung ini, Balai Besar Taman Nasional Gunung Gede Pangrango (BBTNGGP), di (0263) 512-776, lalu Basarnas 115 atau 112. Sebutkan nama jalur, tanggal mulai, dan jumlah peserta.',
            $this->keselamatan($user, $trip)['pesan'],
        );
    }

    /**
     * PT7-10 M11: tanggal dan jam wajib diisi dengan bentuknya; bentuk lain menjadi galat isian,
     * bukan 500 dari pembacaan tanggal.
     */
    public function test_the_return_date_and_time_are_required_in_their_form(): void
    {
        [$user, $trip] = $this->rencana();

        $this->simpanKembali($user, $trip, ['kembali_tanggal' => '', 'kembali_jam' => ''])
            ->assertSessionHasErrors(['kembali_tanggal' => 'Isi tanggal perkiraan kembali.', 'kembali_jam' => 'Isi jam perkiraan kembali.']);

        $this->simpanKembali($user, $trip, ['kembali_tanggal' => '18-10-2026'])
            ->assertSessionHasErrors(['kembali_tanggal' => 'Tulis tanggal kembali sebagai tanggal kalender.']);

        $this->assertNull($trip->fresh()->expected_return_at);
    }

    /**
     * PT7-10 M12: batas laju jam kembali berkunci sendiri, tidak berbagi hitungan dengan
     * formulir rencana.
     */
    public function test_the_return_time_has_its_own_rate_limit_key(): void
    {
        [$user, $trip] = $this->rencana();

        foreach (range(1, (int) config('hiking.rate_limits.personal_writes_per_minute')) as $ignored) {
            $this->actingAs($user)->put(route('trips.update', $trip), $this->isianRencana($trip))->assertRedirect();
        }

        $this->actingAs($user)->put(route('trips.update', $trip), $this->isianRencana($trip))->assertTooManyRequests();
        $this->simpanKembali($user, $trip)->assertRedirect(route('trips.show', $trip));
    }

    /**
     * PT7-17: selama sakelar mati, rute tetap menjawab 404 sesudah batas laju terlampaui.
     * Middleware fitur berjalan sebelum ThrottleRequests, jadi keberadaannya tidak bocor lewat 429.
     */
    public function test_while_the_switch_is_off_the_rate_limit_does_not_reveal_the_route(): void
    {
        config(['hiking.field_safety.enabled' => false]);
        [$user, $trip] = $this->rencana();

        foreach (range(1, (int) config('hiking.rate_limits.personal_writes_per_minute') + 1) as $ignored) {
            $this->simpanKembali($user, $trip)->assertNotFound();
        }
    }

    /**
     * PT7-2 (pola W6-4q2, W6-5h): sesudah Simpan, berhasil atau galat isian atau galat jaringan,
     * fokus kembali ke tombol Simpan di onFinish; galat jaringan diakui belum pasti tersimpan di
     * wilayah status yang selalu terpasang tepat di atas tombol. Bila kartunya lepas karena trip
     * sudah usai, fokus ke wilayah penolakan atau kabar ruang kerja yang selalu terpasang.
     */
    public function test_the_return_form_keeps_focus_and_admits_an_uncertain_save(): void
    {
        $kartu = File::get(resource_path('js/components/trips/KeselamatanTrip.tsx'));

        $this->assertStringContainsString("'Sambungan terputus sebelum jawaban server diterima, jadi belum pasti jam kembali tersimpan. Isian Anda tetap di sini; tekan Simpan jam kembali lagi saat ada sinyal.'", $kartu);
        $this->assertStringContainsString('onNetworkError: () => {', $kartu);
        $this->assertMatchesRegularExpression("/onFinish: \\(\\) => \\{.*?fokusKe\\('kembali-simpan'\\);/s", $kartu);
        $this->assertStringContainsString('<Button id="kembali-simpan" type="submit"', $kartu);
        $this->assertLessThan(strpos($kartu, 'id="kembali-simpan"'), strpos($kartu, "{galatJaringan ? 'Sambungan terputus"));
        $this->assertStringContainsString("const tujuan = ditolak ? 'penolakan-trip' : 'kabar-trip';", $kartu);
        $this->assertStringContainsString('kartuLepas = !page.props.keselamatan;', $kartu);
        $this->assertStringContainsString('aria-invalid={form.errors.registrasi ? true : undefined}', $kartu);

        // Putaran perbaikan 1 (ruling W6-7i, M-1 N01 dan N22): penolakan dibaca dari props halaman
        // baru, dan galat jaringan benar-benar menyalakan kalimatnya.
        $this->assertStringContainsString('ditolak = Boolean(page.props.penolakan);', $kartu);
        $this->assertMatchesRegularExpression('/onNetworkError: \(\) => \{\s*setGalatJaringan\(true\);/', $kartu);
    }

    /**
     * Ruling W6-FE (M-2 tinjauan akhir W6): galat HTTP tanpa header Inertia pada Simpan jam kembali
     * ditulis "belum pasti" di wilayah status yang sama lewat onHttpException yang mengembalikan
     * false, bukan dialog galat bawaan Inertia.
     */
    public function test_an_unreadable_server_answer_to_the_return_time_is_written_near_save(): void
    {
        $kartu = File::get(resource_path('js/components/trips/KeselamatanTrip.tsx'));

        $this->assertMatchesRegularExpression('/onHttpException: \(\) => \{\s*setGalatHttp\(true\);\s*return false;\s*\},/', $kartu);
        $this->assertStringContainsString(": galatHttp ? 'Belum pasti tersimpan: jawaban server tidak dapat dibaca. Muat ulang halaman, lalu periksa jam kembali sebelum menyimpan lagi.' : ''}", $kartu);
        $this->assertStringContainsString("!galatJaringan && !galatHttp && 'sr-only'", $kartu);
    }

    /**
     * PT7-16 dan ruling W6-7c: SMS membuka aplikasi pesan, bukan tab baru, dan salinan manual
     * menyebut pesan, bukan tautan, dalam isian berbaris supaya pemisah barisnya tidak hilang.
     * Bawaan TombolSalin tetap untuk pemakai lama (LembarBagikan).
     */
    public function test_the_message_is_sent_and_copied_whole(): void
    {
        $kartu = File::get(resource_path('js/components/trips/KeselamatanTrip.tsx'));
        $salin = File::get(resource_path('js/components/ui/TombolSalin.tsx'));

        $this->assertMatchesRegularExpression('/<Button variant="secondary" href=\{`sms:\?&body=\$\{encodeURIComponent\(keselamatan\.pesan\)\}`\}>/', $kartu);
        $this->assertMatchesRegularExpression('/<TombolSalin teks=\{keselamatan\.pesan\} label="Salin pesan" berhasil="Pesan disalin\." labelManual="Salin pesan ini secara manual" baris=\{\d+\} \/>/', $kartu);
        $this->assertStringContainsString("labelManual = 'Salin tautan ini secara manual'", $salin);
        $this->assertStringContainsString('<TombolSalin teks={baru.url} label="Salin tautan" />', File::get(resource_path('js/components/trails/banding/LembarBagikan.tsx')));
    }
}
