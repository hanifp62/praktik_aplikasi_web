<?php

namespace Tests\Feature;

use App\Enums\AuthorityType;
use App\Enums\UserRole;
use App\Livewire\Admin\AuthorityManager;
use App\Models\Authority;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Badan resmi menentukan siapa yang berwenang atas sebuah kawasan, dan dari situ
 * mengalir dua hal: nama yang disebut halaman jalur ketika datanya belum ada, dan hak
 * menyunting yang diwarisi pemandu bersertifikat.
 *
 * Karena itu mengubahnya adalah tindakan admin yang tercatat, bukan penyuntingan biasa.
 */
class AuthorityManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_an_admin_may_open_the_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/authorities')->assertForbidden();
        $this->actingAs($this->admin())->get('/admin/authorities')->assertOk();
    }

    public function test_an_admin_can_register_an_authority_and_its_area(): void
    {
        $gunung = Mountain::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(AuthorityManager::class)
            ->call('create')
            ->set('name', 'Balai Taman Nasional Gunung Ciremai')
            ->set('type', AuthorityType::NATIONAL_PARK->value)
            ->set('jurisdiction', 'Jawa Barat')
            ->set('mountain_ids', [$gunung->id])
            ->call('save');

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

        Livewire::actingAs($this->admin())
            ->test(AuthorityManager::class)
            ->call('create')
            ->set('name', 'Balai Taman Nasional Gunung Ciremai')
            ->set('type', AuthorityType::NATIONAL_PARK->value)
            ->set('mountain_ids', [$gunung->id])
            ->call('save');

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

        Livewire::actingAs($this->admin())
            ->test(AuthorityManager::class)
            ->call('edit', $badan->id)
            ->set('mountain_ids', [$baru->id])
            ->call('save');

        $segar = $badan->fresh('mountains');
        $this->assertTrue($segar->mountains->contains('id', $baru->id));
        $this->assertFalse($segar->mountains->contains('id', $lama->id));
    }

    /**
     * §61: perubahan badan resmi termasuk tindakan kritis yang wajib tercatat.
     */
    public function test_creating_one_lands_in_the_audit_trail(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(AuthorityManager::class)
            ->call('create')
            ->set('name', 'Balai uji audit')
            ->set('type', AuthorityType::NATIONAL_PARK->value)
            ->call('save');

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'authority.created',
        ]);
    }

    public function test_a_malformed_website_is_refused(): void
    {
        Livewire::actingAs($this->admin())
            ->test(AuthorityManager::class)
            ->call('create')
            ->set('name', 'Balai uji')
            ->set('type', AuthorityType::NATIONAL_PARK->value)
            ->set('website', 'bukan-url')
            ->call('save')
            ->assertHasErrors('website');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::ADMIN->value]);
    }
}
