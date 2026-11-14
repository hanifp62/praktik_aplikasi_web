<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\ConsiderationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Penanda pemulihan Urungkan sesudah "Hapus dari simpanan" (ruling W3-F2), disapu W9 atas N2 dan
 * N3 tinjauan ulang W3 (ruling W3-F7).
 *
 * Sebelum W9 penanda tidak pernah kedaluwarsa (berlaku sepanjang sesi) dan setiap penghapusan
 * menambah satu kunci sesi. Kini semua penanda tinggal di satu kunci sesi, masing-masing berlaku
 * sepuluh menit, cukup untuk toast yang berhenti menghitung selama disentuh atau difokus (WCAG
 * 2.2.1), dan penanda yang kedaluwarsa dibuang setiap kali ada simpan atau hapus.
 */
class SavedRestoreMarkerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_restore_marker_expires_after_ten_minutes(): void
    {
        $this->freezeTime();
        [$pendaki, $jalur] = $this->jalurDitarikYangDihapus();

        $this->travel(11)->minutes();

        $this->actingAs($pendaki)->post(route('trails.saved.store', $jalur->id), ['catatan' => 'Catatan lama'])->assertNotFound();
        $this->assertDatabaseMissing('trail_considerations', ['user_id' => $pendaki->id, 'trail_id' => $jalur->id]);
    }

    public function test_a_restore_marker_still_works_within_ten_minutes(): void
    {
        $this->freezeTime();
        [$pendaki, $jalur] = $this->jalurDitarikYangDihapus();

        $this->travel(9)->minutes();

        $this->actingAs($pendaki)->post(route('trails.saved.store', $jalur->id), ['catatan' => 'Catatan lama'])->assertRedirect();
        $this->assertDatabaseHas('trail_considerations', ['user_id' => $pendaki->id, 'trail_id' => $jalur->id, 'note' => 'Catatan lama']);
    }

    /**
     * Penanda tidak membuka jalur yang diarsipkan: arsip berarti ditarik dari katalog, bukan
     * sekadar belum terbit (N3; Trail::active()).
     */
    public function test_a_restore_marker_never_reopens_an_archived_trail(): void
    {
        [$pendaki, $jalur] = $this->jalurDitarikYangDihapus();
        $jalur->forceFill(['archived_at' => now()])->saveQuietly();

        $this->actingAs($pendaki)->post(route('trails.saved.store', $jalur->id))->assertNotFound();
    }

    public function test_every_marker_lives_under_one_session_key_and_expired_ones_are_dropped(): void
    {
        $this->freezeTime();
        $pendaki = User::factory()->create();
        $pertama = $this->jalur('Jalur Pertama');
        $kedua = $this->jalur('Jalur Kedua');
        app(ConsiderationService::class)->simpan($pendaki, $pertama, null);
        app(ConsiderationService::class)->simpan($pendaki, $kedua, null);

        $this->actingAs($pendaki)->delete(route('trails.saved.remove', $pertama->id));
        $this->travel(11)->minutes();
        $this->actingAs($pendaki)->delete(route('trails.saved.remove', $kedua->id));

        $this->assertSame([$kedua->id], array_keys(session('simpanan-pemulihan')));
        $this->assertSame([], array_values(array_filter(array_keys(session()->all()), fn (string $kunci) => str_starts_with($kunci, 'simpanan-pulihkan.'))));
    }

    /**
     * @return array{0: User, 1: Trail}
     */
    private function jalurDitarikYangDihapus(): array
    {
        $pendaki = User::factory()->create();
        $jalur = $this->jalur('Jalur Selo');
        app(ConsiderationService::class)->simpan($pendaki, $jalur, 'Catatan lama');
        $jalur->update(['is_published' => false]);

        $this->actingAs($pendaki)->delete(route('trails.saved.remove', $jalur->id))->assertRedirect();

        return [$pendaki, $jalur];
    }

    private function jalur(string $nama): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->published()->create(['name' => $nama]);
    }
}
