<?php

namespace Tests\Feature;

use App\Enums\OfficialStatusValue;
use App\Enums\StatusScope;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Services\Trips\TripReminderScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * Runbook operasi (GAP-18) dan hook peringatan kegagalan operasional (GAP-19).
 */
class OperationalFailureAlertTest extends TestCase
{
    use RefreshDatabase;

    private const RAHASIA = 'rahasia-cron-uji-peringatan-operasi';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.cron.secret' => self::RAHASIA]);
    }

    public function test_operational_runbook_exists_is_indexed_and_covers_all_incident_playbooks(): void
    {
        $jalur = base_path('docs/RUNBOOK-OPERASI.md');
        $this->assertFileExists($jalur);

        $isi = File::get($jalur);
        $indeks = File::get(base_path('docs/README.md'));

        $this->assertStringContainsString('[RUNBOOK-OPERASI.md](RUNBOOK-OPERASI.md)', $indeks);
        $this->assertStringNotContainsString("\u{2014}", $isi);

        foreach (['BMKG', 'PVMBG', 'TERLAMBAT', 'CRON_SECRET', 'PostgreSQL'] as $topik) {
            $this->assertStringContainsString($topik, $isi, "Runbook belum memuat panduan insiden: {$topik}");
        }
    }

    public function test_no_outbound_alert_is_sent_when_webhook_is_not_configured(): void
    {
        Http::fake();
        config(['hiking.alerts.failure_webhook_url' => null]);

        $this->mock(TripReminderScheduler::class)
            ->shouldReceive('kirimYangJatuhTempo')
            ->andReturn(['terkirim' => 0, 'dilewati' => 0, 'gagal' => 1]);

        $this->withToken(self::RAHASIA)->getJson(route('api.cron', 'trips-kirim-pengingat'))
            ->assertStatus(500);

        Http::assertNothingSent();
    }

    public function test_failed_scheduled_cron_sends_structured_webhook_when_configured(): void
    {
        Http::fake([
            'https://hooks.contoh.id/*' => Http::response(['ok' => true], 200),
        ]);
        config(['hiking.alerts.failure_webhook_url' => 'https://hooks.contoh.id/memount']);

        $this->mock(TripReminderScheduler::class)
            ->shouldReceive('kirimYangJatuhTempo')
            ->andThrow(new RuntimeException('Koneksi SMTP putus.'));

        $this->withToken(self::RAHASIA)->getJson(route('api.cron', 'trips-kirim-pengingat'))
            ->assertStatus(500);

        Http::assertSent(fn (HttpRequest $req) => $req->url() === 'https://hooks.contoh.id/memount'
            && ($req['jenis'] ?? null) === 'tugas_gagal'
            && ($req['tugas'] ?? null) === 'trips:kirim-pengingat');
    }

    public function test_expired_official_status_triggers_alert_and_webhook_failure_never_crashes_command(): void
    {
        Http::fake([
            'https://hooks.contoh.id/*' => Http::response('Server galat', 500),
        ]);
        config(['hiking.alerts.failure_webhook_url' => 'https://hooks.contoh.id/memount']);

        $gunung = Mountain::factory()->create();
        OfficialStatus::create([
            'statusable_type' => $gunung->getMorphClass(),
            'statusable_id' => $gunung->id,
            'scope' => StatusScope::MOUNTAIN->value,
            'status' => OfficialStatusValue::OPEN->value,
            'source' => 'Balai Taman Nasional',
            'verified_at' => now()->subDays(20),
            'expires_at' => now()->subDay(),
        ]);

        $this->artisan('data:freshness-check')->assertSuccessful();

        Http::assertSent(fn (HttpRequest $req) => $req->url() === 'https://hooks.contoh.id/memount'
            && ($req['jenis'] ?? null) === 'status_kedaluwarsa'
            && ($req['konteks']['kedaluwarsa'] ?? 0) >= 1);
    }
}
