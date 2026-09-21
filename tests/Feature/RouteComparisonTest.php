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
        return Trail::factory()->for(Mountain::factory()->create())->published()->create();
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

    /**
     * Re-review: tray "Bandingkan N jalur" adalah satu-satunya jalan kebanyakan pendaki
     * menuju halaman ini, dan sebelum diperbaiki tautannya membawa
     * ['trails' => ...->implode(',')]. Itu memaksa RouteComparison masuk mode
     * URL-eksplisit setiap kali, sehingga removeTrail() hanya menyunting URL -- jalur
     * hilang dari tabel perbandingan tetapi baris di trail_considerations tetap ada, dan
     * baki di Jelajah masih berkata "Sedang ditimbang". Mode timbangan-tersimpan yang
     * menulis balik ADA, tetapi tidak pernah tercapai lewat jalan sungguhan.
     *
     * Tautannya diambil dari HTML baki yang dirender sungguhan, bukan disusun sendiri --
     * supaya test ini menempuh jalan yang sama persis dengan pendaki asli, bukan jalan
     * yang menurut penulis test seharusnya dipakai.
     */
    public function test_removing_a_trail_via_the_real_tray_link_writes_through(): void
    {
        $user = User::factory()->create();
        $a = $this->jalur();
        $b = $this->jalur();

        $service = app(ConsiderationService::class);
        $service->toggle($user, $a);
        $service->toggle($user, $b);

        $jelajah = $this->actingAs($user)->get(route('trails.index'))->getContent();

        $this->assertMatchesRegularExpression(
            '#href="([^"]*/trails/compare[^"]*)"[^>]*>\s*Bandingkan#s',
            $jelajah,
            'Tautan "Bandingkan" tidak ditemukan di baki halaman Jelajah.'
        );
        preg_match('#href="([^"]*/trails/compare[^"]*)"[^>]*>\s*Bandingkan#s', $jelajah, $cocok);

        parse_str((string) parse_url($cocok[1], PHP_URL_QUERY), $query);

        // Komponen dimulai dengan $selection persis seperti yang dikirim tautan baki itu:
        // kosong sesudah perbaikan (tautannya telanjang), berisi id sebelum perbaikan.
        $test = Livewire::actingAs($user)->test(RouteComparison::class);
        if (isset($query['trails'])) {
            $test->set('selection', $query['trails']);
        }

        $test->assertSee($a->name)->assertSee($b->name);

        $test->call('removeTrail', $a->id);

        $this->assertDatabaseMissing('trail_considerations', ['user_id' => $user->id, 'trail_id' => $a->id]);
        $this->assertTrue($service->forUser($user)->contains('id', $b->id));

        $this->actingAs($user)
            ->get(route('trails.index'))
            ->assertOk()
            ->assertSee('Sedang ditimbang (1/5)');
    }

    /**
     * Re-review: bare-mode removeTrail() sebelumnya memanggil toggle(), dan toggle()
     * membalik apa pun yang ditemukannya. Klik ganda atau ulang-kirim pada aksi yang
     * sama menemukan barisnya sudah terhapus lalu MENAMBAHKANNYA KEMBALI -- jalur yang
     * tadinya berhasil dihapus muncul lagi tanpa pendaki memilihnya. Mengulang aksi ini
     * wajib tetap aman.
     */
    public function test_removing_the_same_trail_twice_does_not_bring_it_back(): void
    {
        $user = User::factory()->create();
        $a = $this->jalur();
        $b = $this->jalur();

        $service = app(ConsiderationService::class);
        $service->toggle($user, $a);
        $service->toggle($user, $b);

        $test = Livewire::actingAs($user)->test(RouteComparison::class);
        $test->call('removeTrail', $a->id);
        $test->call('removeTrail', $a->id);

        $this->assertFalse($service->forUser($user)->contains('id', $a->id));
        $this->assertTrue($service->forUser($user)->contains('id', $b->id));
        $this->assertCount(1, $service->forUser($user));
    }

    /**
     * Re-review: jaminan tautan berbagi harus tetap berlaku juga pada removeTrail() itu
     * sendiri, bukan hanya pada render halaman -- mode eksplisit tetap hanya menyunting
     * URL, tidak pernah menyentuh timbangan tersimpan pemilik akun.
     */
    public function test_removing_a_trail_via_an_explicit_shared_link_still_does_not_touch_the_stored_shortlist(): void
    {
        $user = User::factory()->create();
        $tersimpan = $this->jalur();
        $dibagikan1 = $this->jalur();
        $dibagikan2 = $this->jalur();

        app(ConsiderationService::class)->toggle($user, $tersimpan);

        Livewire::actingAs($user)
            ->test(RouteComparison::class)
            ->set('selection', $dibagikan1->id.','.$dibagikan2->id)
            ->call('removeTrail', $dibagikan1->id);

        $service = app(ConsiderationService::class);
        $this->assertCount(1, $service->forUser($user));
        $this->assertTrue($service->forUser($user)->contains('id', $tersimpan->id));
        $this->assertFalse($service->forUser($user)->contains('id', $dibagikan1->id));
        $this->assertFalse($service->forUser($user)->contains('id', $dibagikan2->id));
    }
}
