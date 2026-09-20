<?php

namespace Tests\Feature;

use App\Livewire\Trails\RouteComparison;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\ConsiderationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Item A tinjauan akhir: menu "Pertimbangkan" menautkan route('trails.compare') telanjang,
 * tanpa parameter, tetapi RouteComparison sebelum ini hanya membaca parameter URL dan
 * tidak pernah membaca ConsiderationService::forUser(). Pendaki yang menimbang beberapa
 * jalur lalu menutup tab dan mengklik "Pertimbangkan" dari menu melihat "Belum ada jalur
 * yang dipilih" padahal barisnya duduk di trail_considerations -- satu-satunya jalan menu
 * menuju fitur itu berujung buntu.
 *
 * Sumber kebenaran tunggal yang dipilih: timbangan tersimpan. Tanpa parameter URL, halaman
 * membacanya langsung. Dengan parameter URL (tautan yang dapat dibagikan), halaman hanya
 * membaca -- kunjungan lewat tautan orang lain tidak boleh menulis ke timbangan pembaca.
 */
class RouteComparisonTest extends TestCase
{
    use RefreshDatabase;

    private function jalur(): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->create(['is_published' => true]);
    }

    public function test_the_bare_menu_link_shows_the_stored_shortlist(): void
    {
        $user = User::factory()->create();
        $a = $this->jalur();
        $b = $this->jalur();

        $service = app(ConsiderationService::class);
        $service->toggle($user, $a);
        $service->toggle($user, $b);

        $this->actingAs($user)
            ->get(route('trails.compare'))
            ->assertOk()
            ->assertSee($a->name)
            ->assertSee($b->name)
            ->assertDontSee('Belum ada jalur yang dipilih');
    }

    public function test_removing_a_trail_here_clears_it_from_the_tray_on_jelajah_too(): void
    {
        $user = User::factory()->create();
        $a = $this->jalur();
        $b = $this->jalur();

        $service = app(ConsiderationService::class);
        $service->toggle($user, $a);
        $service->toggle($user, $b);

        Livewire::actingAs($user)
            ->test(RouteComparison::class)
            ->call('removeTrail', $a->id);

        $this->assertCount(1, $service->forUser($user));
        $this->assertTrue($service->forUser($user)->contains('id', $b->id));
        $this->assertFalse($service->forUser($user)->contains('id', $a->id));

        // Baki di halaman Jelajah membaca sumber yang sama: hitungannya wajib ikut turun,
        // bukan hanya URL komponen perbandingan yang berubah.
        $this->actingAs($user)
            ->get(route('trails.index'))
            ->assertOk()
            ->assertSee('Sedang ditimbang (1/5)');
    }

    public function test_an_explicit_url_selection_does_not_mutate_the_stored_shortlist(): void
    {
        $user = User::factory()->create();
        $tersimpan = $this->jalur();
        $dibagikan = $this->jalur();

        app(ConsiderationService::class)->toggle($user, $tersimpan);

        $this->actingAs($user)
            ->get(route('trails.compare', ['trails' => $dibagikan->id]))
            ->assertOk()
            ->assertSee($dibagikan->name);

        $service = app(ConsiderationService::class);
        $this->assertCount(1, $service->forUser($user));
        $this->assertTrue($service->forUser($user)->contains('id', $tersimpan->id));
        $this->assertFalse($service->forUser($user)->contains('id', $dibagikan->id));
    }
}
