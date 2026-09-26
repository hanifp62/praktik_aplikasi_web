<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Mountain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sasaran status resmi ditunjuk dengan id mentah, dan satu-satunya yang memeriksa
 * keberadaannya adalah findOrFail di dalam save().
 *
 * Integritasnya memang aman: kelas modelnya dipilih lewat match atas enum tervalidasi,
 * jadi tidak ada kelas yang dapat disisipkan, dan baris berstatus tidak pernah dibuat
 * untuk sasaran yang tidak ada. Yang salah adalah umpan baliknya. Salah ketik satu
 * angka menghasilkan halaman 404 alih-alih galat pada isiannya, sehingga pekerjaan
 * yang sudah diisi pada form itu hilang dan tidak ada yang menunjukkan isian mana
 * yang keliru (WCAG 3.3.1).
 */
class OfficialStatusTargetTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }

    public function test_a_target_that_does_not_exist_is_reported_on_the_field(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.statuses.store'), [
                'scope' => 'MOUNTAIN',
                'statusable_id' => 999999,
                'status' => 'OPEN',
            ])
            ->assertSessionHasErrors('statusable_id');
    }

    /**
     * Pemeriksaannya harus mengikuti scope: id gunung yang sah bukan id jalur yang sah,
     * dan menerimanya akan membuat status melekat pada sasaran yang keliru.
     */
    public function test_the_check_follows_the_selected_scope(): void
    {
        $mountain = Mountain::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.statuses.store'), [
                'scope' => 'TRAIL',
                'statusable_id' => $mountain->id,
                'status' => 'OPEN',
            ])
            ->assertSessionHasErrors('statusable_id');
    }

    public function test_a_real_target_still_saves(): void
    {
        $mountain = Mountain::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.statuses.store'), [
                'scope' => 'MOUNTAIN',
                'statusable_id' => $mountain->id,
                'status' => 'OPEN',
            ])
            ->assertSessionHasNoErrors();
    }
}
