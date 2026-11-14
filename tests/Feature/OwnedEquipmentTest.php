<?php

namespace Tests\Feature;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Models\TripPlan;
use App\Models\User;
use App\Models\UserOwnedItem;
use App\Services\PreparationService;
use App\Services\Trips\IsiAwalPerlengkapan;
use Database\Seeders\PreparationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Perlengkapan pribadi di Profil (spec v3 bagian 6.15; rencana induk W7 butir 4; ruling W7-R8).
 *
 * Menggantikan test rintisan yang hanya membuka `/` (spec v3 bagian 3.5). Daftarnya milik
 * pendaki yang masuk, dirapikan dan tidak kembar, dan mengisi awal daftar persiapan trip sebagai
 * butir yang tetap harus dikonfirmasi per trip (BR-10).
 */
class OwnedEquipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_hiker_adds_renames_and_removes_an_item(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profile.items.store'), ['nama' => '  Headlamp   cadangan '])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile'))
            ->assertSessionHas('kabar-profil', ['bagian' => 'perlengkapan', 'teks' => '"Headlamp cadangan" ditambahkan ke perlengkapan pribadi.']);
        $barang = $user->ownedItems()->sole();
        $this->assertSame('Headlamp cadangan', $barang->name);

        $this->actingAs($user)->put(route('profile.items.update', $barang), ['nama' => 'Headlamp'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('kabar-profil', ['bagian' => 'perlengkapan', 'teks' => 'Nama barang diganti menjadi "Headlamp".']);
        $this->assertSame('Headlamp', $barang->fresh()->name);

        $this->actingAs($user)->delete(route('profile.items.destroy', $barang))
            ->assertRedirect(route('profile'))
            ->assertSessionHas('kabar-profil', ['bagian' => 'perlengkapan', 'teks' => '"Headlamp" dihapus dari perlengkapan pribadi. Daftar persiapan trip yang sudah ada tidak berubah.']);
        $this->assertSame(0, UserOwnedItem::count());
    }

    public function test_duplicates_regardless_of_case_and_unusable_names_are_refused(): void
    {
        $user = User::factory()->create();
        $user->ownedItems()->create(['name' => 'Jas hujan']);
        $kaos = $user->ownedItems()->create(['name' => 'Kaos cadangan']);

        $this->actingAs($user)->post(route('profile.items.store'), ['nama' => 'jas  HUJAN'])
            ->assertSessionHasErrors(['nama' => '"jas HUJAN" sudah ada di perlengkapan pribadi Anda.']);
        $this->actingAs($user)->put(route('profile.items.update', $kaos), ['nama' => 'JAS HUJAN'])
            ->assertSessionHasErrors('nama');
        $this->actingAs($user)->put(route('profile.items.update', $kaos), ['nama' => 'kaos Cadangan'])
            ->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('profile.items.store'), ['nama' => PreparationService::PERMIT_LABEL])
            ->assertSessionHasErrors(['nama' => 'Nama itu dipakai butir izin di daftar persiapan. Pakai nama lain.']);
        $this->actingAs($user)->post(route('profile.items.store'), ['nama' => '   '])
            ->assertSessionHasErrors(['nama' => 'Tulis nama barangnya.']);
        $this->actingAs($user)->post(route('profile.items.store'), ['nama' => ' a '])
            ->assertSessionHasErrors(['nama' => 'Nama barang paling sedikit 2 karakter.']);
        $this->actingAs($user)->post(route('profile.items.store'), ['nama' => str_repeat('x', 61)])
            ->assertSessionHasErrors(['nama' => 'Nama barang paling banyak 60 karakter.']);

        $this->assertSame(['Jas hujan', 'kaos Cadangan'], $user->ownedItems()->orderBy('id')->pluck('name')->all());
    }

    /**
     * Daftar dibatasi untuk barang baru saja; mengganti nama tetap dapat di batas.
     */
    public function test_the_list_stops_at_its_limit(): void
    {
        $user = User::factory()->create();
        $user->ownedItems()->createMany(collect(range(1, UserOwnedItem::BATAS))->map(fn (int $i) => ['name' => 'Barang '.$i])->all());

        $this->actingAs($user)->post(route('profile.items.store'), ['nama' => 'Satu lagi'])
            ->assertSessionHasErrors(['nama' => 'Perlengkapan pribadi paling banyak 40 barang. Hapus yang tidak lagi Anda bawa.']);
        $this->actingAs($user)->put(route('profile.items.update', $user->ownedItems()->first()), ['nama' => 'Barang pertama'])
            ->assertSessionHasNoErrors();

        $this->assertSame(UserOwnedItem::BATAS, $user->ownedItems()->count());
    }

    /**
     * Pemilik dari sesi: user_id di isian diabaikan, dan barang orang lain tidak dapat diganti
     * atau dihapus walau id-nya diketahui (PRD §119).
     */
    public function test_items_belong_to_the_signed_in_hiker_only(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $miliknya = $orangLain->ownedItems()->create(['name' => 'Kompor lipat']);

        $this->actingAs($saya)->post(route('profile.items.store'), ['nama' => 'Matras', 'user_id' => $orangLain->id])->assertSessionHasNoErrors();
        $this->actingAs($saya)->put(route('profile.items.update', $miliknya), ['nama' => 'Diambil alih'])->assertForbidden();
        $this->actingAs($saya)->delete(route('profile.items.destroy', $miliknya))->assertForbidden();

        $this->assertSame(['Matras'], $saya->ownedItems()->pluck('name')->all());
        $this->assertSame(['Kompor lipat'], $orangLain->ownedItems()->pluck('name')->all());

        auth()->logout();
        $this->post(route('profile.items.store'), ['nama' => 'Tamu'])->assertRedirect(route('login'));
        $this->assertSame(2, UserOwnedItem::count());
    }

    /**
     * Ujung ke ujung lewat halaman Persiapan: daftar trip baru berisi perlengkapan pribadi
     * pemiliknya sebagai butir Perlengkapan yang belum dikonfirmasi dan tidak kritis, dengan
     * keterangan asalnya; perlengkapan orang lain tidak masuk.
     */
    public function test_owned_items_prefill_a_new_trip_preparation_list(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $user = User::factory()->create();
        $user->ownedItems()->createMany([['name' => 'Trekking pole'], ['name' => 'Botol air 1 liter']]);
        User::factory()->create()->ownedItems()->create(['name' => 'Kompor lipat']);
        $trip = TripPlan::factory()->create(['user_id' => $user->id]);

        $kategori = $this->actingAs($user)->get(route('trips.preparation', $trip))->assertOk()->viewData('page')['props']['kategori'];

        $perlengkapan = collect($kategori)->firstWhere('kunci', PreparationCategory::EQUIPMENT->value)['items'];
        $milikSaya = collect($perlengkapan)->where('keterangan', IsiAwalPerlengkapan::KETERANGAN)->values();
        $this->assertEqualsCanonicalizing(['Trekking pole', 'Botol air 1 liter'], $milikSaya->pluck('label')->all());
        $this->assertTrue($milikSaya->every(fn (array $b) => $b['status'] === PreparationStatus::NOT_CONFIRMED->value && $b['kritis'] === false));
        $this->assertFalse(collect($kategori)->flatMap(fn (array $k) => $k['items'])->contains('label', 'Kompor lipat'));
    }

    /**
     * Daftar persiapan trip adalah catatan trip itu: mengganti nama atau menghapus barang di
     * Profil tidak mengubah butir yang sudah ada, dan "Perbarui daftar" tidak menambahkannya
     * kembali.
     */
    public function test_changing_the_profile_list_leaves_existing_trip_lists_alone(): void
    {
        $this->seed(PreparationTemplateSeeder::class);
        $user = User::factory()->create();
        $tenda = $user->ownedItems()->create(['name' => 'Tenda dua orang']);
        $trip = TripPlan::factory()->create(['user_id' => $user->id]);
        app(PreparationService::class)->generateFor($trip);
        $butir = $trip->preparationItems()->where('user_owned_item_id', $tenda->id)->sole();

        $this->actingAs($user)->put(route('profile.items.update', $tenda), ['nama' => 'Tenda ultralight'])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('trips.preparation.regenerate', $trip))->assertRedirect();
        $this->assertSame('Tenda dua orang', $butir->fresh()->label);
        $this->assertSame(0, $trip->preparationItems()->where('label', 'Tenda ultralight')->count(), 'Barang yang sudah tertaut tidak ditambahkan lagi dengan nama barunya.');

        $this->actingAs($user)->delete(route('profile.items.destroy', $tenda))->assertRedirect();
        $this->actingAs($user)->post(route('trips.preparation.regenerate', $trip))->assertRedirect();
        $this->assertNull($butir->fresh()->user_owned_item_id, 'Butirnya tetap ada, tautannya dilepas.');
        $this->assertSame(1, $trip->preparationItems()->where('label', 'Tenda dua orang')->count());
    }
}
