<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Kriteria feedback dan error prevention.
 *
 * Dari 27 tampilan Livewire, hanya satu yang punya indikator sedang memproses. Di
 * jaringan tipis pengguna menekan "Simpan", tidak ada yang terlihat berubah, lalu ia
 * menekannya lagi. Aksi ganda itu bukan kesalahan pengguna, melainkan akibat antarmuka
 * yang diam.
 *
 * Perbaikannya diletakkan di komponen tombol, bukan ditambal satu per satu di 26
 * berkas, supaya perilakunya seragam dan tetap seragam ketika halaman baru ditambahkan.
 */
class ButtonFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_button_disables_itself_while_its_action_runs(): void
    {
        $isi = Blade::render('<x-ui.button wire:click="simpan">Simpan</x-ui.button>');

        $this->assertStringContainsString('wire:loading.attr="disabled"', $isi);
    }

    /**
     * Tanpa wire:target, setiap permintaan Livewire di halaman itu akan memutar semua
     * spinner sekaligus, termasuk tombol yang tidak ada hubungannya.
     */
    public function test_the_indicator_follows_only_its_own_action(): void
    {
        $isi = Blade::render('<x-ui.button wire:click="togglePublish(7)">Publikasikan</x-ui.button>');

        $this->assertStringContainsString('wire:target="togglePublish(7)"', $isi);
    }

    public function test_a_submit_button_can_name_the_action_it_waits_for(): void
    {
        $isi = Blade::render('<x-ui.button type="submit" target="save">Simpan</x-ui.button>');

        $this->assertStringContainsString('wire:target="save"', $isi);
    }

    public function test_the_indicator_is_announced_to_screen_readers(): void
    {
        $isi = Blade::render('<x-ui.button wire:click="simpan">Simpan</x-ui.button>');

        $this->assertStringContainsString('aria-hidden="true"', $isi);
        $this->assertStringContainsString('Memproses', $isi);
    }

    /**
     * Livewire baru menyembunyikan elemen wire:loading setelah hidrasi. Tanpa
     * display:none dari server, setiap tombol menampilkan spinner dan membacakan
     * "Memproses" kepada pembaca layar sebelum JavaScript sempat jalan, dan seterusnya
     * bila JavaScript gagal dimuat sama sekali.
     */
    public function test_the_indicator_is_hidden_before_javascript_runs(): void
    {
        $isi = Blade::render('<x-ui.button wire:click="simpan">Simpan</x-ui.button>');

        $this->assertMatchesRegularExpression(
            '/<span wire:loading[^>]*style="display: none;"/',
            $isi,
            'Spinner harus sudah tersembunyi dari server.'
        );
    }

    /**
     * Tautan bukan aksi Livewire, jadi tidak boleh membawa atribut yang tidak berlaku.
     */
    public function test_a_link_button_carries_no_loading_attributes(): void
    {
        $isi = Blade::render('<x-ui.button href="/trails">Jalur</x-ui.button>');

        $this->assertStringNotContainsString('wire:loading', $isi);
    }

    public function test_a_destructive_action_asks_before_it_runs(): void
    {
        $isi = Blade::render('<x-ui.button wire:click="toggleArchive(1)" confirm="Arsipkan jalur ini?">Arsipkan</x-ui.button>');

        $this->assertStringContainsString('wire:confirm="Arsipkan jalur ini?"', $isi);
    }

    public function test_archiving_a_trail_asks_for_confirmation_in_the_admin_list(): void
    {
        Trail::factory()->create();

        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN->value]))
            ->get('/admin/trails')
            ->assertOk()
            ->assertSee('wire:confirm', escape: false);
    }
}
