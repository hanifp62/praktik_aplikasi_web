<?php

namespace Tests\Feature;

use App\Enums\AuthorityType;
use App\Enums\UserRole;
use App\Models\Authority;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Badan resmi menentukan siapa yang berwenang atas sebuah kawasan, dan dari situ
 * mengalir dua hal: nama yang disebut halaman jalur ketika datanya belum ada, dan hak
 * menyunting yang diwarisi pemandu bersertifikat.
 *
 * Karena itu mengubahnya adalah tindakan admin yang tercatat, bukan penyuntingan biasa.
 * Halamannya kini React (Inertia); aksi tulis lewat POST/PUT biasa.
 */
class AuthorityManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_an_admin_may_open_the_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/authorities')->assertForbidden();

        $this->actingAs($this->admin())
            ->get('/admin/authorities')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Authorities')
                ->where('title', 'Kelola Badan Resmi'));
    }

    /**
     * Penjagaan tulis tidak bergantung pada halaman: pendaki yang mengirim langsung ke
     * endpoint-nya tetap ditolak dan tidak ada yang tersimpan.
     */
    public function test_a_hiker_cannot_register_an_authority_directly(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.authorities.store'), $this->isian())
            ->assertForbidden();

        $this->assertSame(0, Authority::count());
    }

    public function test_an_admin_can_register_an_authority_and_its_area(): void
    {
        $gunung = Mountain::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.authorities.store'), $this->isian([
                'jurisdiction' => 'Jawa Barat',
                'mountain_ids' => [$gunung->id],
            ]))
            ->assertRedirect(route('admin.authorities'))
            ->assertSessionHas('status', 'Badan resmi tersimpan.');

        $badan = Authority::where('name', 'Balai Taman Nasional Gunung Ciremai')->firstOrFail();

        $this->assertSame('balai-taman-nasional-gunung-ciremai', $badan->slug);
        $this->assertTrue($badan->mountains->contains('id', $gunung->id));
    }

    /**
     * Inti kegunaannya: setelah dikaitkan, halaman jalur yang datanya belum ada dapat
     * menyebut nama badan yang ditunggu.
     */
    public function test_registering_it_makes_the_waiting_page_able_to_name_it(): void
    {
        $gunung = Mountain::factory()->create();
        $jalur = Trail::factory()->for($gunung)->unpublished()->create();

        $this->assertNull($gunung->fresh()->responsibleAuthority());

        $this->actingAs($this->admin())
            ->post(route('admin.authorities.store'), $this->isian(['mountain_ids' => [$gunung->id]]))
            ->assertSessionHasNoErrors();

        $this->actingAs(User::factory()->create())
            ->get('/trails/'.$jalur->slug)
            ->assertOk()
            ->assertSee('Balai Taman Nasional Gunung Ciremai');
    }

    public function test_editing_replaces_the_area_rather_than_adding_to_it(): void
    {
        $lama = Mountain::factory()->create();
        $baru = Mountain::factory()->create();

        $badan = Authority::create([
            'name' => 'Balai uji',
            'slug' => 'balai-uji',
            'type' => AuthorityType::NATIONAL_PARK->value,
        ]);
        $badan->mountains()->attach($lama->id);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.authorities.update', $badan), $this->isian([
                'name' => 'Balai uji',
                'mountain_ids' => [$baru->id],
            ]))
            ->assertRedirect(route('admin.authorities'));

        $segar = $badan->fresh('mountains');
        $this->assertTrue($segar->mountains->contains('id', $baru->id));
        $this->assertFalse($segar->mountains->contains('id', $lama->id));
        $this->assertSame(1, Authority::count(), 'Mengubah tidak boleh membuat badan kedua.');
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'authority.updated']);
    }

    /**
     * §61: perubahan badan resmi termasuk tindakan kritis yang wajib tercatat.
     */
    public function test_creating_one_lands_in_the_audit_trail(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.authorities.store'), $this->isian(['name' => 'Balai uji audit']));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'authority.created',
        ]);
    }

    public function test_a_malformed_website_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.authorities.store'), $this->isian(['website' => 'bukan-url']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, Authority::count());
    }

    /**
     * Isian form untuk mengubah dibawa setiap baris, termasuk kawasan yang sudah
     * dikaitkan, supaya form ubah tidak membuang kaitan lama tanpa sengaja.
     */
    public function test_each_row_carries_its_current_area_for_editing(): void
    {
        $gunung = Mountain::factory()->create();
        $badan = Authority::create(['name' => 'Balai isian', 'slug' => 'balai-isian', 'type' => AuthorityType::NATIONAL_PARK->value]);
        $badan->mountains()->attach($gunung->id);

        $this->actingAs($this->admin())
            ->get(route('admin.authorities'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('badan.data.0.isian.mountain_ids', [$gunung->id])
                ->where('badan.data.0.jumlahKawasan', 1));
    }

    /**
     * @param  array<string, mixed>  $ganti
     * @return array<string, mixed>
     */
    private function isian(array $ganti = []): array
    {
        return array_merge([
            'name' => 'Balai Taman Nasional Gunung Ciremai',
            'type' => AuthorityType::NATIONAL_PARK->value,
            'mountain_ids' => [],
        ], $ganti);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }
}
