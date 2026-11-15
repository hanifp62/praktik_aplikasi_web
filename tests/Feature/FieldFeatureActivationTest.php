<?php

namespace Tests\Feature;

use App\Enums\TripStatus;
use App\Models\Checkpoint;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\Process\Process;
use Tests\TestCase;

/**
 * Verifikasi aktivasi terkendali fitur paket lapangan luring dan alat keselamatan (GAP-15),
 * serta indikator selisih jarak dan tanjakan pos berikutnya tanpa sinyal GPS.
 */
class FieldFeatureActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_field_pack_and_safety_routes_stay_closed_until_enabled_and_serve_full_payloads_when_enabled(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 03:00:00', 'UTC'));

        $user = User::factory()->create(['name' => 'Rina Pendaki']);
        $trail = Trail::factory()->published()->create(['name' => 'Merbabu via Selo']);
        Checkpoint::factory()->for($trail)->create([
            'sequence' => 2,
            'name' => 'Basecamp Selo',
            'elevation_m' => 1860,
            'latitude' => -7.45,
            'longitude' => 110.44,
        ]);
        Checkpoint::factory()->for($trail)->create([
            'sequence' => 3,
            'name' => 'Sabana 1',
            'elevation_m' => 2580,
            'latitude' => -7.46,
            'longitude' => 110.45,
        ]);

        $trip = TripPlan::factory()->create([
            'user_id' => $user->id,
            'trail_id' => $trail->id,
            'status' => TripStatus::PLANNED->value,
            'planned_date' => '2026-10-20',
            'start_time' => '06:00',
        ]);

        // Bawaan konfigurasi menutup kedua rute sampai diaktifkan.
        config(['hiking.field_pack.enabled' => false, 'hiking.field_safety.enabled' => false]);
        $this->actingAs($user)->get(route('trips.pack', $trip))->assertNotFound();
        $this->actingAs($user)->get(route('trips.print', $trip))->assertNotFound();

        // Ketika diaktifkan, manifest paket lapangan, bagian lapangan, versi cetak, dan jam kembali berfungsi penuh.
        config(['hiking.field_pack.enabled' => true, 'hiking.field_safety.enabled' => true]);

        $manifest = $this->actingAs($user)->getJson(route('trips.pack', $trip))->assertOk()->json();
        $this->assertSame(['lapangan', 'rencana', 'dinamis'], array_column($manifest['bagian'], 'nama'));
        $this->assertNotEmpty($manifest['versi']);

        $lapangan = $this->actingAs($user)->getJson(route('trips.pack.section', [$trip, 'lapangan']))->assertOk()->json();
        $this->assertCount(3, $lapangan['pos']);

        $this->actingAs($user)
            ->get(route('trips.print', $trip))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Trips/Print'));

        $this->actingAs($user)
            ->from(route('trips.show', $trip))
            ->put(route('trips.return.update', $trip), [
                'kembali_tanggal' => '2026-10-20',
                'kembali_jam' => '17:00',
                'registrasi' => 'REG-SEL-01',
            ])
            ->assertRedirect(route('trips.show', $trip))
            ->assertSessionHas('status');

        $this->assertSame('REG-SEL-01', $trip->fresh()->registration_number);
    }

    public function test_checkpoint_to_checkpoint_distance_and_climb_summary_works_offline_without_gps(): void
    {
        $garis = [[110.0, -7.0], [110.0, -6.99100679], [110.00906, -6.99100679]];
        $posAwal = ['urutan' => 1, 'lng' => 110.0, 'lat' => -7.0, 'elevasi' => 1850];
        $posTujuan = ['urutan' => 2, 'lng' => 110.00906, 'lat' => -6.99100679, 'elevasi' => 2210];
        $posTanpaKoordinat = ['urutan' => 3, 'lng' => null, 'lat' => null, 'elevasi' => 2500];

        [$penuh, $hanyaKetinggian, $posPertama] = $this->hitung([
            ['ringkasanRuasPos', [$garis, $posAwal, $posTujuan]],
            ['ringkasanRuasPos', [null, $posTujuan, $posTanpaKoordinat]],
            ['ringkasanRuasPos', [$garis, null, $posTujuan]],
        ]);

        $this->assertSame("2.210\u{A0}mdpl · dari pos sebelumnya 2,0\u{A0}km, ↑360\u{A0}m", $penuh);
        $this->assertSame("2.500\u{A0}mdpl · dari pos sebelumnya ↑290\u{A0}m", $hanyaKetinggian);
        $this->assertSame("2.210\u{A0}mdpl", $posPertama);
    }

    /**
     * @param  array<int, array{0: string, 1: array<int, mixed>}>  $panggilan
     * @return array<int, mixed>
     */
    private function hitung(array $panggilan): array
    {
        $skrip = 'import { pathToFileURL } from "node:url";'
            .'const modul = await import(pathToFileURL('.json_encode(resource_path('js/components/mendaki/hitung.ts')).').href);'
            .'const panggilan = '.json_encode($panggilan, JSON_THROW_ON_ERROR).';'
            .'process.stdout.write(JSON.stringify(panggilan.map(([nama, argumen]) => modul[nama](...argumen))));';

        $proses = new Process(['node', '--experimental-strip-types', '--input-type=module', '-'], base_path(), null, $skrip, 60);
        $proses->run();

        $this->assertTrue($proses->isSuccessful(), 'Node gagal menjalankan hitung.ts: '.mb_substr($proses->getErrorOutput(), 0, 600));

        return json_decode($proses->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }
}
