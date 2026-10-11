<?php

namespace Tests\Feature;

use App\Enums\ConsiderationOutcome;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use App\Services\ConsiderationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Tahap "pertimbangkan": menyimpan jalur.
 *
 * Riset corong Traveloka menemukan masalah pada tiga tahap awal: melihat, menyimpan, dan
 * membandingkan. Di sini tahap menyimpan tidak ada sama sekali, jadi pendaki melompat dari
 * menjelajah langsung ke membuat trip, atau tidak melompat sama sekali.
 *
 * DK-05: simpanan longgar, banding sempit. Batas simpanan adalah batas teknis dari
 * config('hiking.saved_trails.max'); yang dijaga tiga adalah kolom banding, bukan daftar
 * simpanan. Dulu keduanya satu batas lima, sehingga jalur keenam ditolak disimpan.
 */
class ConsiderationTest extends TestCase
{
    use RefreshDatabase;

    private function jalur(): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->published()->create();
    }

    public function test_a_hiker_can_put_a_trail_aside_to_think_about(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();

        $this->assertSame(ConsiderationOutcome::DITAMBAHKAN, app(ConsiderationService::class)->toggle($user, $trail));
        $this->assertTrue(app(ConsiderationService::class)->forUser($user)->contains('id', $trail->id));
    }

    public function test_putting_it_aside_twice_takes_it_back(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();
        $service = app(ConsiderationService::class);

        $service->toggle($user, $trail);
        $this->assertSame(ConsiderationOutcome::DIHAPUS, $service->toggle($user, $trail));
        $this->assertCount(0, $service->forUser($user));
    }

    /**
     * DK-05: batas lima dulu memotong daftar simpanan padahal yang tidak terbaca adalah
     * kolom banding. Dengan config bawaan, jalur keenam kini tersimpan.
     */
    public function test_a_sixth_trail_is_saved_now_that_saving_is_loose(): void
    {
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);
        collect(range(1, 5))->each(fn () => $service->toggle($user, $this->jalur()));
        $keenam = $this->jalur();

        $hasil = $service->toggle($user, $keenam);

        $this->assertSame(ConsiderationOutcome::DITAMBAHKAN, $hasil);
        $this->assertCount(6, $service->forUser($user));
    }

    public function test_the_saved_limit_and_the_comparison_limit_are_read_from_config(): void
    {
        config()->set('hiking.saved_trails.max', 7);
        config()->set('hiking.saved_trails.compare_max', 2);

        $service = app(ConsiderationService::class);

        $this->assertSame(7, $service->batasSimpanan());
        $this->assertSame(2, $service->batasBanding());
    }

    /**
     * Batasnya keras dan dinyatakan, bukan diam-diam memotong yang tertua.
     *
     * Menggeser yang tertua keluar tanpa memberi tahu menghilangkan jalur yang sedang
     * ditimbang pendaki tepat ketika ia sedang menimbangnya.
     */
    public function test_a_trail_beyond_the_saved_limit_is_refused_not_silently_dropped(): void
    {
        config()->set('hiking.saved_trails.max', 3);
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);
        $tiga = collect(range(1, 3))->map(fn () => $this->jalur());
        $tiga->each(fn (Trail $t) => $service->toggle($user, $t));
        $keempat = $this->jalur();

        $hasil = $service->toggle($user, $keempat);

        $this->assertSame(ConsiderationOutcome::DITOLAK, $hasil);
        $this->assertCount(3, $service->forUser($user));
        $this->assertFalse($service->forUser($user)->contains('id', $keempat->id));
        $this->assertTrue($service->forUser($user)->contains('id', $tiga->first()->id), 'Yang tertua tidak boleh terbuang diam-diam.');
    }

    /**
     * DITOLAK dan DIHAPUS harus bisa dibedakan pemanggil.
     *
     * Kalau bercampur jadi satu nilai, halaman bisa menampilkan "dihapus" kepada pendaki
     * yang sebenarnya ditolak karena daftarnya penuh.
     */
    public function test_a_refusal_is_distinguishable_from_a_removal(): void
    {
        config()->set('hiking.saved_trails.max', 2);
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);
        $dua = collect(range(1, 2))->map(fn () => $this->jalur());
        $dua->each(fn (Trail $t) => $service->toggle($user, $t));

        $this->assertSame(ConsiderationOutcome::DITOLAK, $service->toggle($user, $this->jalur()));
        $this->assertSame(ConsiderationOutcome::DIHAPUS, $service->toggle($user, $dua->first()));
    }

    /**
     * Kiriman ganda pada pasangan yang sama tidak boleh menjadi halaman 500.
     *
     * Kunci baris `lockForUpdate()` sungguhan di PostgreSQL tetapi tanpa efek di SQLite
     * yang dipakai test ini, jadi test ini TIDAK membuktikan kuncinya mencegah balapan
     * lintas perangkat. Yang dibuktikan hanyalah bahwa menyisipkan pasangan yang sama dua
     * kali berakhir tenang, bukan melempar galat.
     */
    public function test_the_same_pair_inserted_twice_does_not_throw(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();

        TrailConsideration::createOrFirst(['user_id' => $user->id, 'trail_id' => $trail->id]);
        TrailConsideration::createOrFirst(['user_id' => $user->id, 'trail_id' => $trail->id]);

        $this->assertSame(1, TrailConsideration::where('user_id', $user->id)->where('trail_id', $trail->id)->count());
    }

    public function test_one_hikers_shortlist_is_invisible_to_another(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $trail = $this->jalur();

        app(ConsiderationService::class)->toggle($saya, $trail);

        $this->assertCount(0, app(ConsiderationService::class)->forUser($orangLain));
        $this->assertCount(0, app(ConsiderationService::class)->simpanan($orangLain));
    }

    /**
     * Jalur yang ditarik dari katalog tidak boleh tetap duduk di daftar simpanan seolah
     * masih dapat dipilih.
     */
    public function test_an_archived_trail_leaves_the_shortlist_on_its_own(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();
        $service = app(ConsiderationService::class);

        $service->toggle($user, $trail);
        $trail->update(['archived_at' => now()]);

        $this->assertCount(0, $service->forUser($user));
        $this->assertCount(0, $service->simpanan($user));
    }

    /**
     * Batas dan tampilan harus sepakat: jalur arsip tidak terlihat di permukaan mana pun,
     * jadi ia juga tidak boleh menghabiskan kuota. Kalau ikut dihitung, simpanan macet
     * permanen tanpa jalan keluar yang terlihat.
     */
    public function test_the_cap_reflects_what_the_hiker_can_actually_see(): void
    {
        config()->set('hiking.saved_trails.max', 5);
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);
        $tigaAktif = collect(range(1, 3))->map(fn () => $this->jalur());
        $duaArsip = collect(range(1, 2))->map(fn () => $this->jalur());
        $tigaAktif->each(fn (Trail $t) => $service->toggle($user, $t));
        $duaArsip->each(function (Trail $t) use ($user, $service) {
            $service->toggle($user, $t);
            $t->update(['archived_at' => now()]);
        });

        $this->assertCount(3, $service->forUser($user));
        $this->assertSame(ConsiderationOutcome::DITAMBAHKAN, $service->toggle($user, $this->jalur()));
        $this->assertSame(ConsiderationOutcome::DITAMBAHKAN, $service->toggle($user, $this->jalur()));
        $this->assertSame(ConsiderationOutcome::DITOLAK, $service->toggle($user, $this->jalur()));
    }

    /**
     * Urutan simpanan adalah urutan menyimpannya, terbaru dulu: dari sanalah "tiga
     * simpanan terbaru" yang dibandingkan bila pendaki belum memilih.
     */
    public function test_the_saved_list_is_newest_first(): void
    {
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);
        [$pertama, $kedua, $ketiga] = [$this->jalur(), $this->jalur(), $this->jalur()];
        $service->toggle($user, $pertama);
        $service->toggle($user, $kedua);
        $service->toggle($user, $ketiga);

        $urutan = $service->simpanan($user)->pluck('trail_id')->all();

        $this->assertSame([$ketiga->id, $kedua->id, $pertama->id], $urutan);
    }

    public function test_timbang_refuses_an_unpublished_trail(): void
    {
        $user = User::factory()->create();
        $draft = Trail::factory()->for(Mountain::factory()->create())->unpublished()->create();

        $this->actingAs($user)->post(route('trails.timbang', $draft->id))->assertNotFound();

        $this->assertDatabaseMissing('trail_considerations', ['user_id' => $user->id, 'trail_id' => $draft->id]);
    }

    /**
     * Kalimat penolakan memakai label hasil dan menyebut batas dari config, sehingga
     * mengubah batas tidak meninggalkan kalimat yang menyebut angka lama.
     */
    public function test_the_full_shortlist_flash_uses_the_outcome_label_and_the_configured_limit(): void
    {
        config()->set('hiking.saved_trails.max', 2);
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);
        collect(range(1, 2))->each(fn () => $service->toggle($user, $this->jalur()));
        $ketiga = $this->jalur();

        $this->actingAs($user)
            ->from(route('trails.index'))
            ->post(route('trails.timbang', $ketiga->id))
            ->assertRedirect(route('trails.index'))
            ->assertSessionHas('timbangan-penuh', ConsiderationOutcome::DITOLAK->label().' (2 jalur). Hapus satu dulu sebelum menyimpan jalur lain.');

        $this->assertDatabaseMissing('trail_considerations', ['user_id' => $user->id, 'trail_id' => $ketiga->id]);
    }

    /**
     * Tombol di baris Jelajah adalah tombol simpan. Hasilnya diumumkan lewat wilayah
     * role="status" (prop `pengumuman`), bukan hanya labelnya yang berganti.
     */
    public function test_saving_from_a_result_row_stores_the_trail_and_announces_it(): void
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->for(Mountain::factory()->create())->published()->create(['name' => 'Jalur Kaliurang']);

        $this->actingAs($user)->from(route('trails.index'))->post(route('trails.timbang', $trail->id))->assertRedirect(route('trails.index'));

        $this->assertDatabaseHas('trail_considerations', ['user_id' => $user->id, 'trail_id' => $trail->id]);

        $this->actingAs($user)->get(route('trails.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pengumuman', 'Jalur Kaliurang disimpan.')
            ->where('trails.data.0.ditimbang', true));
    }

    public function test_unsaving_from_a_result_row_removes_the_trail_and_announces_it(): void
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->for(Mountain::factory()->create())->published()->create(['name' => 'Jalur Kaliurang']);
        app(ConsiderationService::class)->toggle($user, $trail);

        $this->actingAs($user)->from(route('trails.index'))->post(route('trails.timbang', $trail->id))->assertRedirect(route('trails.index'));

        $this->assertDatabaseMissing('trail_considerations', ['user_id' => $user->id, 'trail_id' => $trail->id]);

        $this->actingAs($user)->get(route('trails.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pengumuman', 'Jalur Kaliurang dihapus dari simpanan.')
            ->where('trails.data.0.ditimbang', false));
    }

    /**
     * Batas yang disebut ke pembaca sama dengan batas penolakannya. Diperbarui W3: baki
     * Jelajah dilepas bersama prop batasTimbangan, jadi batasnya kini disebut halaman
     * Simpanan, dan penolakan di Jelajah dijawab dialog "Simpanan sudah penuh"
     * (SavedUndoTest).
     */
    public function test_the_saved_page_states_the_configured_saved_limit(): void
    {
        config()->set('hiking.saved_trails.max', 12);

        $this->actingAs(User::factory()->create())
            ->get(route('trails.saved'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('batas', 12));
    }
}
