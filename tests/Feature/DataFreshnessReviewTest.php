<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PRD §93 dan §98.
 *
 * Pemeriksaan yang tidak terlihat sama saja dengan tidak ada. Yang diuji di sini adalah
 * apakah admin benar-benar diberi tahu, bukan sekadar apakah layanannya menghitung benar.
 */
class DataFreshnessReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_expired_status_is_shown_on_the_status_page(): void
    {
        $this->statusKedaluwarsa('Jalur Cibodas');

        $this->actingAs($this->admin())
            ->get('/admin/official-statuses')
            ->assertOk()
            ->assertSee('Perlu ditinjau')
            ->assertSee('Sudah kedaluwarsa')
            ->assertSee('Jalur Cibodas');
    }

    public function test_the_panel_stays_hidden_when_nothing_needs_review(): void
    {
        OfficialStatus::factory()->create([
            'statusable_type' => Trail::class,
            'statusable_id' => Trail::factory()->create()->id,
            'effective_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
            'verified_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/official-statuses')
            ->assertOk()
            ->assertDontSee('Perlu ditinjau');
    }

    /**
     * Lencana dibawa ke setiap halaman admin supaya kemunduran status yang senyap tidak
     * menunggu admin kebetulan membuka halaman status.
     */
    public function test_the_count_badge_appears_on_another_admin_page(): void
    {
        $this->statusKedaluwarsa('Jalur Selo');

        $this->actingAs($this->admin())
            ->get('/admin/trails')
            ->assertOk()
            ->assertSee('status perlu ditinjau');
    }

    public function test_no_badge_when_there_is_nothing_to_review(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/trails')
            ->assertOk()
            ->assertDontSee('status perlu ditinjau');
    }

    /**
     * Lencana ini muncul di setiap halaman admin, jadi biayanya tidak boleh tumbuh
     * bersama jumlah status.
     */
    public function test_the_badge_does_not_grow_with_the_number_of_statuses(): void
    {
        $this->actingAs($this->admin());
        $this->statusKedaluwarsa('Jalur Satu');
        $this->get('/admin/trails');
        $sedikit = $this->hitungQuery('/admin/trails');

        for ($i = 0; $i < 15; $i++) {
            $this->statusKedaluwarsa('Jalur '.$i);
        }

        $this->assertSame($sedikit, $this->hitungQuery('/admin/trails'));
    }

    private function hitungQuery(string $url): int
    {
        $n = 0;
        DB::listen(function () use (&$n) {
            $n++;
        });

        $this->get($url);

        DB::getEventDispatcher()->forget('Illuminate\Database\Events\QueryExecuted');

        return $n;
    }

    private function statusKedaluwarsa(string $namaJalur): OfficialStatus
    {
        return OfficialStatus::factory()->create([
            'statusable_type' => Trail::class,
            'statusable_id' => Trail::factory()->create(['name' => $namaJalur])->id,
            'effective_at' => now()->subMonth(),
            'expires_at' => now()->subDay(),
            'verified_at' => now(),
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }
}
