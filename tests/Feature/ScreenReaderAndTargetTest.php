<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Sapuan aksesibilitas W9 atas butir yang ditunda W3 dan W4.
 *
 * - Pengumuman tunggal (T4-M7, T7-M10 tinjauan akhir W3): menyimpan atau melepas jalur dengan
 *   toast Urungkan diumumkan dua kali, sekali oleh toast (aria-live) dan sekali oleh wilayah
 *   role="status" halaman dari flash `pengumuman`. Kini aksi yang menampilkan toast meminta
 *   server tidak menulis pengumumannya; hasil yang tidak memunculkan toast (sudah ada, sudah
 *   tidak ada, Urungkan) tetap diumumkan halaman. Satu kalimat per kejadian.
 * - Ruas kosong hitungan butir kritis (W4-6h OOS7) memakai garis batas kendali yang 3:1 terhadap
 *   kartu di kedua tema, bukan isian sumur yang hampir tak terlihat.
 * - Tautan gunung di kepala rincian jalur dan aksi kolom kanan 1440 px (W3, W8).
 *
 * Bunyi pembaca layar sendiri butuh telinga manusia (docs/PROTOKOL-UJI-KEGUNAAN.md bagian 4);
 * test ini menjaga sumber kejadiannya.
 */
class ScreenReaderAndTargetTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_save_announced_by_its_undo_toast_is_not_announced_again_by_the_page(): void
    {
        $pendaki = User::factory()->create();
        $jalur = $this->jalur();

        $this->actingAs($pendaki)->post(route('trails.saved.store', $jalur), ['dengan_toast' => true])
            ->assertRedirect()
            ->assertSessionMissing('pengumuman');

        $this->assertDatabaseHas('trail_considerations', ['user_id' => $pendaki->id, 'trail_id' => $jalur->id]);
    }

    public function test_a_save_without_a_toast_is_still_announced_by_the_page(): void
    {
        $pendaki = User::factory()->create();
        $jalur = $this->jalur();

        $this->actingAs($pendaki)->post(route('trails.saved.store', $jalur))
            ->assertSessionHas('pengumuman', 'Gede via Cibodas disimpan.');
    }

    /**
     * Baris yang sudah ada tidak memunculkan toast (ruling W3-4b), jadi halaman yang mengumumkan.
     */
    public function test_an_already_saved_trail_is_announced_by_the_page_even_when_a_toast_was_expected(): void
    {
        $pendaki = User::factory()->create();
        $jalur = $this->jalur();
        TrailConsideration::create(['user_id' => $pendaki->id, 'trail_id' => $jalur->id]);

        $this->actingAs($pendaki)->post(route('trails.saved.store', $jalur), ['dengan_toast' => true])
            ->assertSessionHas('pengumuman', 'Gede via Cibodas sudah ada di simpanan.')
            ->assertSessionHas('simpanan-sudah-ada', ['trailId' => $jalur->id]);
    }

    public function test_a_removal_announced_by_its_undo_toast_is_not_announced_again_by_the_page(): void
    {
        $pendaki = User::factory()->create();
        $jalur = $this->jalur();
        TrailConsideration::create(['user_id' => $pendaki->id, 'trail_id' => $jalur->id]);

        $this->actingAs($pendaki)->delete(route('trails.saved.remove', $jalur), ['dengan_toast' => true])
            ->assertSessionMissing('pengumuman')
            ->assertSessionHas('simpanan-terhapus', ['trailId' => $jalur->id, 'nama' => 'Gede via Cibodas', 'catatan' => null]);

        $this->actingAs($pendaki)->delete(route('trails.saved.remove', $jalur), ['dengan_toast' => true])
            ->assertSessionHas('pengumuman', 'Jalur ini sudah tidak ada di simpanan.');
    }

    /**
     * Tombol simpan meminta pengumuman tunggal hanya untuk aksi yang memang menampilkan toast:
     * simpan biasa dan lepas simpanan. Urungkan (simpan dengan urungkan false, dan hapus dari
     * toast simpan) tidak memunculkan toast, jadi halaman yang mengumumkannya.
     */
    public function test_the_save_button_asks_for_a_single_announcer_only_when_it_shows_a_toast(): void
    {
        $sumber = File::get(resource_path('js/components/trails/TombolSimpan.tsx'));

        $this->assertStringContainsString('{ catatan, dengan_toast: urungkan }', $sumber);
        $this->assertStringContainsString("router.delete(remove.url({ trail: jalur.id }), {\n            ...opsi,\n            data: { dengan_toast: true },", $sumber);
        $this->assertStringContainsString('() => router.delete(remove.url({ trail: jalur.id }), opsi)', $sumber, 'Urungkan simpan tidak memunculkan toast, jadi pengumumannya tetap milik halaman.');
    }

    public function test_empty_progress_segments_are_outlined_with_the_control_border(): void
    {
        foreach (['js/components/beranda/KartuPerjalanan.tsx', 'js/pages/Trips/Preparation.tsx', 'js/pages/Trips/Readiness.tsx'] as $berkas) {
            $sumber = File::get(resource_path($berkas));

            $this->assertMatchesRegularExpression("/'h-2 flex-1 rounded-full', i < \\w+\\.dikonfirmasi \\? 'bg-brand-700' : 'border border-control'/", $sumber, $berkas);
            $this->assertStringNotContainsString("'bg-brand-700' : 'bg-surface-sunken'", $sumber, $berkas);
        }
    }

    public function test_the_mountain_link_in_the_trail_header_is_a_full_touch_target(): void
    {
        $sumber = File::get(resource_path('js/pages/Trails/Show.tsx'));

        $this->assertMatchesRegularExpression('/<Tautan href=\{trail\.mountainUrl\} className="inline-flex min-h-11 items-center /', $sumber);
    }

    /**
     * Kolom kanan 22rem (352 px) memuat tiga tombol yang membungkus tanpa sengaja (W3-G2). Kini
     * aksi utama "Rencanakan trip" berdiri sendiri selebar kolom, lalu simpan dan Banding
     * berdampingan, sehingga susunannya sama apa pun panjang labelnya.
     */
    public function test_the_wide_column_puts_the_main_action_on_its_own_row(): void
    {
        $sumber = File::get(resource_path('js/pages/Trails/Show.tsx'));

        $this->assertStringContainsString('<Button href={rencanaUrl} className="col-span-2">', $sumber);
        $this->assertStringContainsString('<div className="grid grid-cols-2 gap-2">{aksiLebar}</div>', $sumber);
    }

    private function jalur(): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->published()->create(['name' => 'Gede via Cibodas']);
    }
}
