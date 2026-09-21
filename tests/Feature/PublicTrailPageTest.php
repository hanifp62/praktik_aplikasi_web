<?php

namespace Tests\Feature;

use App\Enums\ModerationStatus;
use App\Enums\OfficialStatusValue;
use App\Enums\StatusScope;
use App\Models\Checkpoint;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman jalur publik.
 *
 * Mesin pertumbuhan AllTrails, dan di aplikasi ini nol: seluruh halaman jalur berada di
 * balik login, sehingga orang yang mencari nama gunung di mesin pencari tidak pernah
 * menemukan apa pun dari sini.
 *
 * ## Batasan yang membentuk seluruh halamannya
 *
 * Halaman publik akan diindeks, disalin, dan ditampilkan sebagai cuplikan yang berumur
 * lebih panjang daripada isinya. Mesin pencari menyimpan salinannya berhari-hari, dan
 * cuplikan itu tidak dapat ditarik kembali.
 *
 * §94 dan §95 melarang menyajikan status basi sebagai keadaan kini, dan pengindeksan
 * membuat persis itu terjadi tanpa ada yang melakukannya dengan sengaja. Jawabannya
 * bukan menambah peringatan pada status, melainkan **tidak menampilkan yang berumur
 * pendek sama sekali**: status resmi dan prakiraan cuaca hanya ada di dalam aplikasi,
 * tempat kesegarannya dapat dijaga.
 */
class PublicTrailPageTest extends TestCase
{
    use RefreshDatabase;

    private function jalur(bool $terbit = true, bool $diarsipkan = false): Trail
    {
        $gunung = Mountain::factory()->create(['name' => 'Merbabu', 'province' => 'Jawa Tengah']);

        // Gerbang publikasi kini invariant model, jadi jalur tak terbit memang tidak
        // boleh melewati published(). Statenya dipasang hanya ketika jalurnya memang
        // terbit, dan kolomnya tidak lagi disetel langsung.
        $pabrik = Trail::factory()->easy()->for($gunung);

        if ($terbit) {
            $pabrik = $pabrik->published();
        }

        $trail = $pabrik->create([
            'name' => 'Jalur Selo',
            'archived_at' => $diarsipkan ? now() : null,
            'elevation_gain_m' => 1400,
        ]);

        Checkpoint::factory()->for($trail)->create([
            'sequence' => 1, 'name' => 'Pos 1 Dok Malang', 'elevation_m' => 1900,
        ]);

        return $trail;
    }

    public function test_anyone_can_open_it_without_logging_in(): void
    {
        $trail = $this->jalur();

        $this->get(route('public.trail', $trail))
            ->assertOk()
            ->assertSee('Jalur Selo')
            ->assertSee('Merbabu')
            ->assertSee('Jawa Tengah')
            ->assertSee('1.400')
            ->assertSee('Pos 1 Dok Malang');
    }

    /**
     * Batasan terpenting halaman ini.
     *
     * Cuplikan mesin pencari berumur lebih panjang daripada isinya, jadi status yang
     * benar hari ini akan tetap terbaca berhari-hari sesudah jalurnya ditutup. Yang
     * berumur pendek karena itu tidak ditampilkan sama sekali, bukan ditampilkan dengan
     * peringatan.
     */
    public function test_it_never_publishes_the_official_status(): void
    {
        $trail = $this->jalur();

        OfficialStatus::create([
            'statusable_type' => (new Trail)->getMorphClass(),
            'statusable_id' => $trail->id,
            'scope' => StatusScope::TRAIL->value,
            'status' => OfficialStatusValue::OPEN->value,
            'source' => 'Balai Besar TN',
            'effective_at' => now()->subDay(),
        ]);

        $halaman = $this->get(route('public.trail', $trail));

        $halaman->assertDontSee('Status resmi');
        $halaman->assertDontSee(OfficialStatusValue::OPEN->label());
    }

    /**
     * Ketiadaan status tidak boleh terbaca sebagai jalurnya terbuka. Halaman ini harus
     * menyebut ke mana status terkini dicari.
     */
    public function test_it_says_where_the_current_status_lives(): void
    {
        $this->get(route('public.trail', $this->jalur()))
            ->assertSee('status resmi terkini')
            ->assertSee(route('login'), escape: false);
    }

    public function test_it_never_publishes_the_weather_forecast(): void
    {
        $this->get(route('public.trail', $this->jalur()))
            ->assertDontSee('Prakiraan cuaca');
    }

    /**
     * Laporan kondisi membawa nama pelapornya, dan nama itu tidak pernah dipublikasikan
     * kepada orang yang belum masuk.
     */
    public function test_it_never_publishes_anyone_personal_data(): void
    {
        $trail = $this->jalur();

        TrailConditionReport::factory()->for($trail)->create([
            'user_id' => User::factory()->create(['name' => 'Rina Pendaki'])->id,
            'moderation_status' => ModerationStatus::APPROVED->value,
            'note' => 'Jembatan Pos 3 putus.',
        ]);

        $halaman = $this->get(route('public.trail', $trail));

        $halaman->assertDontSee('Rina Pendaki');
        $halaman->assertDontSee('Jembatan Pos 3 putus.');
    }

    public function test_an_unpublished_trail_is_not_public(): void
    {
        $this->get(route('public.trail', $this->jalur(terbit: false)))->assertNotFound();
    }

    public function test_an_archived_trail_is_not_public(): void
    {
        $this->get(route('public.trail', $this->jalur(diarsipkan: true)))->assertNotFound();
    }

    /**
     * Membuka satu pintu tidak boleh membuka yang lain. Halaman aplikasi tetap di balik
     * login sebagaimana sebelumnya.
     */
    public function test_opening_this_door_does_not_open_the_others(): void
    {
        $trail = $this->jalur();

        $this->get(route('trails.show', $trail))->assertRedirect(route('login'));
        $this->get(route('news'))->assertRedirect(route('login'));
        $this->get(route('progress'))->assertRedirect(route('login'));
    }

    public function test_the_sitemap_lists_published_trails_only(): void
    {
        $terbit = $this->jalur();
        $belum = $this->jalur(terbit: false);
        $arsip = $this->jalur(diarsipkan: true);

        $peta = $this->get('/sitemap.xml');

        $peta->assertOk();
        $peta->assertHeader('Content-Type', 'application/xml');
        $peta->assertSee(route('public.trail', $terbit), escape: false);
        $peta->assertDontSee(route('public.trail', $belum), escape: false);
        $peta->assertDontSee(route('public.trail', $arsip), escape: false);
    }

    /**
     * Mesin pencari membaca ini sebelum apa pun yang lain, dan halaman tanpa judul
     * sendiri akan muncul sebagai nama aplikasi berulang-ulang di hasil pencarian.
     */
    public function test_it_carries_its_own_title_and_description(): void
    {
        $halaman = $this->get(route('public.trail', $this->jalur()));

        $halaman->assertSee('<title>Jalur Selo', escape: false);
        $halaman->assertSee('<meta name="description"', escape: false);
    }

    /**
     * Item G tinjauan akhir: distance_km di-cast 'decimal:2', jadi nilai mentahnya
     * string ("0.00"), dan (bool) "0.00" bernilai true di PHP -- hanya "" dan "0" yang
     * falsy. Ringkasan halaman publik sebelumnya menjaga dengan truthiness mentah,
     * sehingga jalur berjarak nol menulis "Jarak 0.00 km" di meta description, seolah
     * itu ukuran sungguhan, alih-alih menyembunyikannya sama seperti elevation gain nol.
     */
    public function test_the_summary_omits_zero_distance_instead_of_printing_it(): void
    {
        $gunung = Mountain::factory()->create(['name' => 'Merbabu', 'province' => 'Jawa Tengah']);
        $trail = Trail::factory()->published()->easy()->published()->for($gunung)->create([
            'name' => 'Jalur Selo',
            'distance_km' => 0,
        ]);

        $halaman = $this->get(route('public.trail', $trail));

        $halaman->assertDontSee('Jarak 0');
        $halaman->assertDontSee('Jarak 0,00', escape: false);
    }
}
