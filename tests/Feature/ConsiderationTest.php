<?php

namespace Tests\Feature;

use App\Enums\ConsiderationOutcome;
use App\Livewire\Trails\TrailIndex;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use App\Services\ConsiderationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    /**
     * Batas dan tampilan harus sepakat.
     *
     * Sebelum diperbaiki, batas dihitung dari seluruh baris trail_considerations
     * termasuk yang jalurnya sudah diarsipkan, sementara forUser() menyembunyikan jalur
     * arsip itu. Pendaki dengan lima baris tersimpan tetapi dua di antaranya arsip
     * melihat "3/5" di baki -- karena forUser() hanya menampilkan tiga -- tetapi
     * ditolak menambah jalur keempat: timbangan macet permanen tanpa jalan keluar yang
     * terlihat, karena dua jalur yang mengunci sisa kuota tidak tampak di permukaan
     * mana pun.
     */
    public function test_the_cap_reflects_what_the_hiker_can_actually_see(): void
    {
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);

        $tigaAktif = collect(range(1, 3))->map(fn () => $this->jalur());
        $duaArsip = collect(range(1, 2))->map(fn () => $this->jalur());

        $tigaAktif->each(fn (Trail $t) => $service->toggle($user, $t));
        $duaArsip->each(function (Trail $t) use ($user, $service) {
            $service->toggle($user, $t);
            $t->update(['archived_at' => now()]);
        });

        // Lima baris tersimpan, tetapi hanya tiga yang terlihat.
        $this->assertCount(3, $service->forUser($user));

        // Kuotanya masih tersisa dua, mengikuti yang terlihat, bukan tertutup oleh arsip.
        $keempat = $this->jalur();
        $kelima = $this->jalur();

        $this->assertSame(ConsiderationOutcome::DITAMBAHKAN, $service->toggle($user, $keempat));
        $this->assertSame(ConsiderationOutcome::DITAMBAHKAN, $service->toggle($user, $kelima));

        $keenam = $this->jalur();
        $this->assertSame(ConsiderationOutcome::DITOLAK, $service->toggle($user, $keenam));
    }

    /**
     * Item G tinjauan akhir: timbang() sebelumnya resolve dengan active() saja, bukan
     * published(). Halaman perbandingan memfilter published() (lihat RouteComparison),
     * jadi jalur yang belum terbit bisa masuk timbangan dari sini lalu lenyap tanpa kabar
     * begitu pembaca membuka halaman Pertimbangkan.
     */
    public function test_timbang_refuses_an_unpublished_trail(): void
    {
        $user = User::factory()->create();
        $draft = Trail::factory()->for(Mountain::factory()->create())->unpublished()->create();

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($user)->test(TrailIndex::class)->call('timbang', $draft->id);
    }

    /**
     * Item G tinjauan akhir: ConsiderationOutcome::label() tidak pernah dipanggil siapa
     * pun, sementara TrailIndex::timbang() menulis kalimat penolakannya sendiri --
     * dua kalimat untuk satu keadaan, tanpa jaminan tetap sinkron.
     */
    public function test_the_full_shortlist_flash_uses_the_outcome_label(): void
    {
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);

        collect(range(1, 5))->each(fn () => $service->toggle($user, $this->jalur()));

        $keenam = $this->jalur();

        Livewire::actingAs($user)
            ->test(TrailIndex::class)
            ->call('timbang', $keenam->id)
            ->assertSee(ConsiderationOutcome::DITOLAK->label().' (5 jalur). Keluarkan satu dulu sebelum menambah.');
    }
}
