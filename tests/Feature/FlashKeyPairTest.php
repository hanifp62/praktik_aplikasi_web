<?php

namespace Tests\Feature;

use App\Models\TripPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Dua kunci flash Persiapan sampai sebagai dua kabar terpisah (titipan W4 untuk W9; ruling W4-FK).
 *
 * Kabar kedatangan ("Rencana trip tersimpan.") memakai kunci status dan dibacakan dekat kepala
 * halaman; kabar aksi di halaman (sakelar pengingat, Perbarui daftar) memakai kunci kabar-aksi dan
 * dibacakan dekat kendalinya. Penjaga tabrakan prop yang ada hanya memindai prop tingkat atas, jadi
 * pasangan di dalam flash dijaga di sini: keduanya sampai terpisah tanpa saling menimpa, dan hanya
 * Persiapan yang membaca kabar aksi, sehingga kabar itu tidak pernah dibacakan dua kali.
 */
class FlashKeyPairTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_arrival_message_and_an_action_message_reach_the_page_apart(): void
    {
        $trip = TripPlan::factory()->create();

        $this->actingAs($trip->user)
            ->withSession(['status' => 'Rencana trip tersimpan.', 'kabar-aksi' => 'Daftar persiapan diperbarui mengikuti karakteristik jalur.'])
            ->get(route('trips.preparation', $trip))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('flash.status', 'Rencana trip tersimpan.')
                ->where('flash.kabarAksi', 'Daftar persiapan diperbarui mengikuti karakteristik jalur.'));
    }

    public function test_only_the_preparation_page_reads_the_action_message(): void
    {
        $pembaca = [];

        foreach (File::allFiles(resource_path('js')) as $berkas) {
            if (str_contains($berkas->getContents(), 'flash.kabarAksi')) {
                $pembaca[] = str_replace('\\', '/', $berkas->getRelativePathname());
            }
        }

        $this->assertSame(['pages/Trips/Preparation.tsx'], $pembaca);
    }
}
