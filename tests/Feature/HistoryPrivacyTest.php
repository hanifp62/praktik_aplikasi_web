<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Support\SessionKey;
use Tests\TestCase;

/**
 * Riwayat peramban sesudah keluar (butir izin akhir 9; Konflik 5 rencana W6; T8-M4 tinjauan
 * akhir W3).
 *
 * Inertia menyimpan props setiap halaman di history.state. Clear-Site-Data saat keluar (W6)
 * menghapus penyimpanan origin, tetapi bukan riwayat: di perangkat bersama, tombol Kembali
 * sesudah keluar dapat menampilkan halaman pengguna sebelumnya dari riwayat itu. Pasangan
 * encryptHistory dan clearHistory menutupnya, tetapi menyangkut kebijakan data di perangkat
 * (docs/KEBIJAKAN-DATA-PRIBADI.md, Q10), jadi dibangun di balik sakelar
 * INERTIA_ENCRYPT_HISTORY yang mati secara bawaan (pola W3 P8). Pemilik yang menyalakannya.
 */
class HistoryPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_stays_unencrypted_until_the_owner_turns_the_switch_on(): void
    {
        $this->assertFalse((bool) config('inertia.history.encrypt'));

        $halaman = $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()->viewData('page');

        $this->assertArrayNotHasKey('encryptHistory', $halaman);
    }

    public function test_logging_out_with_the_switch_off_leaves_history_alone(): void
    {
        $this->actingAs(User::factory()->create())->post(route('logout'))->assertRedirect('/');

        $this->assertNull(session(SessionKey::CLEAR_HISTORY));
    }

    public function test_with_the_switch_on_pages_encrypt_their_history_and_logging_out_clears_it(): void
    {
        config(['inertia.history.encrypt' => true]);
        $pendaki = User::factory()->create();

        $halaman = $this->actingAs($pendaki)->get(route('dashboard'))->assertOk()->viewData('page');

        $this->assertTrue($halaman['encryptHistory']);

        $this->actingAs($pendaki)->post(route('logout'))
            ->assertRedirect('/')
            ->assertHeader('Clear-Site-Data', '"storage"');

        $this->assertTrue(session(SessionKey::CLEAR_HISTORY));
        $this->assertTrue($this->get('/')->assertOk()->viewData('page')['clearHistory']);
    }
}
