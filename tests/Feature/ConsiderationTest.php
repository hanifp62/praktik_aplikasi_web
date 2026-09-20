<?php

namespace Tests\Feature;

use App\Enums\ConsiderationOutcome;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use App\Services\ConsiderationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tahap "pertimbangkan".
 *
 * Riset corong Traveloka menemukan masalah pada tiga tahap awal: melihat, menyimpan, dan
 * membandingkan. Di sini tahap menyimpan tidak ada sama sekali, jadi pendaki melompat dari
 * menjelajah langsung ke membuat trip, atau tidak melompat sama sekali.
 *
 * Batasnya lima dan keras. Lebih dari lima kolom tidak terbaca pada lebar 400px (§88), dan
 * riset AllTrails menunjukkan beban keputusan justru naik ketika pilihan menumpuk.
 */
class ConsiderationTest extends TestCase
{
    use RefreshDatabase;

    private function jalur(): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->create(['is_published' => true]);
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
     * Batasnya keras dan dinyatakan, bukan diam-diam memotong yang tertua.
     *
     * Menggeser yang tertua keluar tanpa memberi tahu menghilangkan jalur yang sedang
     * ditimbang pendaki tepat ketika ia sedang menimbangnya.
     */
    public function test_the_sixth_trail_is_refused_not_silently_dropped(): void
    {
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);

        $lima = collect(range(1, 5))->map(fn () => $this->jalur());
        $lima->each(fn (Trail $t) => $service->toggle($user, $t));

        $keenam = $this->jalur();

        $this->assertSame(ConsiderationOutcome::DITOLAK, $service->toggle($user, $keenam));
        $this->assertCount(5, $service->forUser($user));
        $this->assertFalse($service->forUser($user)->contains('id', $keenam->id));
        $this->assertTrue($service->forUser($user)->contains('id', $lima->first()->id), 'Yang tertua tidak boleh terbuang diam-diam.');
    }

    /**
     * DITOLAK dan DIHAPUS harus bisa dibedakan pemanggil.
     *
     * Kalau bercampur jadi satu nilai, halaman bisa menampilkan "dihapus" kepada pendaki
     * yang sebenarnya ditolak karena daftarnya penuh -- persis kelas kegagalan diam yang
     * coba dicegah batas lima ini.
     */
    public function test_a_refusal_is_distinguishable_from_a_removal(): void
    {
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);

        $lima = collect(range(1, 5))->map(fn () => $this->jalur());
        $lima->each(fn (Trail $t) => $service->toggle($user, $t));

        $keenam = $this->jalur();

        $this->assertSame(ConsiderationOutcome::DITOLAK, $service->toggle($user, $keenam));
        $this->assertSame(ConsiderationOutcome::DIHAPUS, $service->toggle($user, $lima->first()));
    }

    /**
     * Kiriman ganda pada pasangan yang sama tidak boleh menjadi halaman 500.
     *
     * Ini adalah jalur idempoten yang sebenarnya dipakai untuk menahan balapan kiriman
     * ganda pada `toggle()`. Kunci baris `lockForUpdate()` sungguhan di PostgreSQL tetapi
     * tanpa efek di SQLite yang dipakai test ini, jadi test ini TIDAK membuktikan kuncinya
     * mencegah balapan lintas perangkat -- yang dibuktikan hanyalah bahwa menyisipkan
     * pasangan yang sama dua kali berakhir tenang, bukan melempar galat.
     */
    public function test_the_same_pair_inserted_twice_does_not_throw(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();

        TrailConsideration::createOrFirst(['user_id' => $user->id, 'trail_id' => $trail->id]);
        TrailConsideration::createOrFirst(['user_id' => $user->id, 'trail_id' => $trail->id]);

        $this->assertSame(1, TrailConsideration::where('user_id', $user->id)->where('trail_id', $trail->id)->count());
    }

    /**
     * Pertimbangan satu pendaki tidak pernah terlihat pendaki lain.
     */
    public function test_one_hikers_shortlist_is_invisible_to_another(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $trail = $this->jalur();

        app(ConsiderationService::class)->toggle($saya, $trail);

        $this->assertCount(0, app(ConsiderationService::class)->forUser($orangLain));
    }

    /**
     * Jalur yang diarsipkan keluar dengan sendirinya.
     *
     * Jalur yang ditarik dari katalog tidak boleh tetap duduk di daftar timbangan seolah
     * masih dapat dipilih; keadaan yang berubah setelah keputusan diambil adalah kelas
     * cacat yang sudah beberapa kali muncul di produk ini.
     */
    public function test_an_archived_trail_leaves_the_shortlist_on_its_own(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();
        $service = app(ConsiderationService::class);

        $service->toggle($user, $trail);
        $trail->update(['archived_at' => now()]);

        $this->assertCount(0, $service->forUser($user));
    }
}
