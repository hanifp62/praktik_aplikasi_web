<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use App\Services\ConsiderationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Simpan dengan urungkan (spec v3 bagian 6.2 dan 9.1; W3 Tugas 4).
 *
 * POST trails/saved/{trail} menyimpan secara idempoten: kiriman ulang atau halaman basi tidak
 * pernah membalik simpanan menjadi terhapus, berbeda dengan toggle lama. Urungkan sesudah
 * "Hapus dari simpanan" memakai rute yang sama dengan catatan yang dititipkan flash, jadi
 * catatan pribadi (FR-22) kembali utuh. Pemilik dari sesi, jalur dari rute, dan hanya
 * `catatan` yang dibaca dari badan permintaan.
 */
class SavedUndoTest extends TestCase
{
    use RefreshDatabase;
    use SumberReact;

    private function jalur(string $nama = 'Jalur Selo'): Trail
    {
        return Trail::factory()->for(Mountain::factory())->published()->create(['name' => $nama]);
    }

    private function catatan(User $user, Trail $trail): ?string
    {
        return TrailConsideration::query()->where('user_id', $user->id)->where('trail_id', $trail->id)->value('note');
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->post(route('trails.saved.store', $this->jalur()->id))->assertRedirect(route('login'));
    }

    public function test_saving_twice_keeps_one_row_and_announces_it(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();

        $this->actingAs($user)->from(route('trails.index'))->post(route('trails.saved.store', $trail->id))
            ->assertRedirect(route('trails.index'))
            ->assertSessionHas('pengumuman', 'Jalur Selo disimpan.');
        $this->actingAs($user)->post(route('trails.saved.store', $trail->id))->assertRedirect();

        $this->assertSame(1, TrailConsideration::query()->where('user_id', $user->id)->count());
    }

    /**
     * Urungkan mengembalikan jalur beserta catatannya: flash menitipkan catatan satu kali
     * kepada pemiliknya, lalu rute simpan menulisnya kembali.
     */
    public function test_undo_after_removal_restores_the_trail_with_its_note(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();
        $baris = TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $trail->id]);
        $baris->forceFill(['note' => 'Bawa jas hujan'])->save();

        $this->actingAs($user)->from(route('trails.index'))->delete(route('trails.saved.remove', $trail->id))
            ->assertSessionHas('simpanan-terhapus', ['trailId' => $trail->id, 'nama' => 'Jalur Selo', 'catatan' => 'Bawa jas hujan']);
        $this->assertNull($this->catatan($user, $trail));

        $this->actingAs($user)->get(route('trails.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('flash.simpananTerhapus.catatan', 'Bawa jas hujan'));

        $this->actingAs($user)->post(route('trails.saved.store', $trail->id), ['catatan' => 'Bawa jas hujan'])->assertRedirect();
        $this->assertSame('Bawa jas hujan', $this->catatan($user, $trail));
    }

    /**
     * Idempoten juga untuk catatan: simpan ulang tidak menimpa catatan yang sudah ada.
     */
    public function test_saving_again_never_overwrites_an_existing_note(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();
        TrailConsideration::create(['user_id' => $user->id, 'trail_id' => $trail->id])->forceFill(['note' => 'Catatan lama'])->save();

        $this->actingAs($user)->post(route('trails.saved.store', $trail->id), ['catatan' => 'Catatan baru'])->assertRedirect();

        $this->assertSame('Catatan lama', $this->catatan($user, $trail));
    }

    public function test_the_note_is_limited_like_any_private_note(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();

        $this->actingAs($user)->post(route('trails.saved.store', $trail->id), ['catatan' => str_repeat('a', TrailConsideration::CATATAN_MAKS + 1)])
            ->assertSessionHasErrors('catatan');

        $this->assertDatabaseMissing('trail_considerations', ['user_id' => $user->id, 'trail_id' => $trail->id]);
    }

    /**
     * Pemilik dari sesi: user_id dan trail_id di badan permintaan diabaikan (mass assignment).
     */
    public function test_ids_in_the_request_body_never_reach_the_database(): void
    {
        $user = User::factory()->create();
        $lain = User::factory()->create();
        $trail = $this->jalur();
        $trailLain = $this->jalur('Jalur Lain');

        $this->actingAs($user)->post(route('trails.saved.store', $trail->id), ['user_id' => $lain->id, 'trail_id' => $trailLain->id])->assertRedirect();

        $this->assertDatabaseHas('trail_considerations', ['user_id' => $user->id, 'trail_id' => $trail->id]);
        $this->assertDatabaseMissing('trail_considerations', ['user_id' => $lain->id]);
        $this->assertDatabaseMissing('trail_considerations', ['trail_id' => $trailLain->id]);
    }

    public function test_only_published_trails_can_be_saved(): void
    {
        $user = User::factory()->create();
        $draf = Trail::factory()->for(Mountain::factory())->unpublished()->create();

        $this->actingAs($user)->post(route('trails.saved.store', $draf->id))->assertNotFound();
        $this->actingAs($user)->post(route('trails.saved.store', 999999))->assertNotFound();
    }

    /**
     * C1 tinjauan akhir W3 (ruling W3-F2): Simpanan menampilkan jalur yang ditarik dari terbit
     * bersama catatannya, dan dialog hapus menjanjikan Urungkan. Urungkan memanggil rute simpan,
     * yang hanya menerima jalur terbit, jadi tanpa penanda pemulihan catatannya hilang permanen.
     * Hapus menitipkan penanda sekali-pakai di sesi pemiliknya; simpan memakai jalur aktif hanya
     * bila penanda itu ada, lalu menghapusnya.
     */
    public function test_undo_restores_a_withdrawn_trail_with_its_note(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();
        app(ConsiderationService::class)->simpan($user, $trail, 'Bawa jas hujan');
        $trail->update(['is_published' => false]);

        $this->actingAs($user)->from(route('trails.saved'))->delete(route('trails.saved.remove', $trail->id))
            ->assertSessionHas('simpanan-terhapus', ['trailId' => $trail->id, 'nama' => 'Jalur Selo', 'catatan' => 'Bawa jas hujan']);
        $this->assertNull($this->catatan($user, $trail));

        $this->actingAs($user)->from(route('trails.saved'))->post(route('trails.saved.store', $trail->id), ['catatan' => 'Bawa jas hujan'])
            ->assertRedirect(route('trails.saved'));
        $this->assertSame('Bawa jas hujan', $this->catatan($user, $trail));

        // Sekali pakai: sesudah dipulihkan, simpan ulang jalur yang tidak terbit kembali ditolak.
        app(ConsiderationService::class)->remove($user, $trail->id);
        $this->actingAs($user)->post(route('trails.saved.store', $trail->id))->assertNotFound();
    }

    /**
     * Tanpa penanda pemulihan, jalur yang tidak terbit tetap tidak dapat disimpan, juga bila
     * jalur itu pernah tersimpan: penanda hanya lahir dari penghapusan oleh pemiliknya.
     */
    public function test_a_withdrawn_trail_cannot_be_saved_without_a_recovery_marker(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();
        $trail->update(['is_published' => false]);

        $this->actingAs($user)->post(route('trails.saved.store', $trail->id), ['catatan' => 'Catatan'])->assertNotFound();
        $this->assertDatabaseMissing('trail_considerations', ['user_id' => $user->id, 'trail_id' => $trail->id]);
    }

    /**
     * Penanda milik pendaki yang menghapus: pendaki lain (sesinya sendiri, atau sesi yang sama
     * sesudah berganti akun) tidak dapat memakainya untuk menyimpan jalur yang tidak terbit.
     */
    public function test_a_recovery_marker_only_serves_the_hiker_who_removed_the_trail(): void
    {
        $pemilik = User::factory()->create();
        $lain = User::factory()->create();
        $trail = $this->jalur();
        app(ConsiderationService::class)->simpan($pemilik, $trail, 'Catatan pemilik');
        $trail->update(['is_published' => false]);

        $this->actingAs($pemilik)->delete(route('trails.saved.remove', $trail->id))->assertRedirect();

        $this->actingAs($lain)->post(route('trails.saved.store', $trail->id))->assertNotFound();

        $this->flushSession();
        $this->actingAs($lain)->post(route('trails.saved.store', $trail->id))->assertNotFound();
        $this->assertDatabaseMissing('trail_considerations', ['user_id' => $lain->id, 'trail_id' => $trail->id]);
    }

    /**
     * m4 tinjauan akhir W3: menghapus jalur yang tidak ada di simpanan tidak menawarkan Urungkan
     * (Urungkan itu akan menambah baris yang tidak pernah dihapus) dan tidak menyebut nama jalur
     * apa pun, termasuk draf yang namanya belum terbit.
     */
    public function test_removing_a_trail_that_is_not_saved_offers_no_undo_and_names_nothing(): void
    {
        $user = User::factory()->create();
        $draf = Trail::factory()->for(Mountain::factory())->unpublished()->create(['name' => 'Jalur Rahasia']);

        $respons = $this->actingAs($user)->delete(route('trails.saved.remove', $draf->id))->assertRedirect();

        $respons->assertSessionMissing('simpanan-terhapus');
        $this->assertStringNotContainsString('Jalur Rahasia', (string) session('pengumuman'));
        $this->actingAs($user)->post(route('trails.saved.store', $draf->id))->assertNotFound();

        $tombol = File::get(resource_path('js/components/trails/TombolSimpan.tsx'));
        $this->assertStringContainsString('if (!terhapus) {', $tombol);
    }

    /**
     * m4: menghapus dari simpanan adalah tulisan pribadi seperti simpan, jadi dibatasi laju yang
     * sama dengan awalan kuncinya sendiri.
     */
    public function test_removing_is_rate_limited(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        for ($i = 0; $i < $batas; $i++) {
            $this->actingAs($user)->delete(route('trails.saved.remove', $trail->id))->assertRedirect();
        }

        $this->actingAs($user)->delete(route('trails.saved.remove', $trail->id))->assertStatus(429);
    }

    /**
     * T8-M5 tinjauan akhir W3: id yang melampaui bilangan bulat PHP tidak boleh menjadi TypeError
     * 500 di parameter `int $trail`; pola rute membatasinya 18 digit, jadi jawabannya 404.
     */
    public function test_an_oversized_trail_id_is_not_found_rather_than_an_error(): void
    {
        $user = User::factory()->create();
        $raksasa = str_repeat('9', 20);

        $this->actingAs($user)->post('/trails/saved/'.$raksasa)->assertNotFound();
        $this->actingAs($user)->delete('/trails/saved/'.$raksasa)->assertNotFound();
        $this->actingAs($user)->put('/trails/saved/'.$raksasa.'/catatan', ['catatan' => 'x'])->assertNotFound();
        $this->actingAs($user)->delete('/trails/saved/'.$raksasa.'/catatan')->assertNotFound();
    }

    /**
     * Simpanan yang penuh ditolak dengan batas yang berlaku, dan tombolnya menjawab dengan
     * dialog di tempat tombol ditekan (bukan pesan di ujung halaman).
     */
    public function test_a_full_list_is_refused_with_the_configured_limit(): void
    {
        config()->set('hiking.saved_trails.max', 1);
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('trails.saved.store', $this->jalur()->id));
        $kedua = $this->jalur('Jalur Kedua');

        $this->actingAs($user)->post(route('trails.saved.store', $kedua->id))
            ->assertSessionHas('timbangan-penuh', 'Simpanan sudah penuh (1 jalur). Hapus satu dulu sebelum menyimpan jalur lain.');
        $this->assertDatabaseMissing('trail_considerations', ['user_id' => $user->id, 'trail_id' => $kedua->id]);

        $tombol = File::get(resource_path('js/components/trails/TombolSimpan.tsx'));
        $this->assertStringContainsString('judul="Simpanan sudah penuh"', $tombol);
        $this->assertStringContainsString('<Button href={saved.url()}>Buka Simpanan</Button>', $tombol);
    }

    public function test_saving_is_rate_limited(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        for ($i = 0; $i < $batas; $i++) {
            $this->actingAs($user)->post(route('trails.saved.store', $trail->id))->assertRedirect();
        }

        $this->actingAs($user)->post(route('trails.saved.store', $trail->id))->assertStatus(429);
    }

    /**
     * Tombol simpan memakai dua rute idempoten, membawa keadaannya lewat aria-pressed, dan
     * bertanya dulu sebelum melepas simpanan yang bercatatan.
     */
    public function test_the_save_button_uses_idempotent_routes_and_asks_before_losing_a_note(): void
    {
        $tombol = File::get(resource_path('js/components/trails/TombolSimpan.tsx'));

        $this->assertStringContainsString('router.post(store.url({ trail: jalur.id }), { catatan }', $tombol);
        $this->assertStringContainsString('router.delete(remove.url({ trail: jalur.id })', $tombol);
        $this->assertDoesNotMatchRegularExpression('/\btimbang\b/', $tombol, 'Rute toggle lama dapat membalik simpanan pada halaman basi.');
        $this->assertStringContainsString('aria-pressed={jalur.ditimbang}', $tombol);
        $this->assertStringContainsString('judul="Hapus dari simpanan?"', $tombol);
        $this->assertStringContainsString('simpan(terhapus?.catatan ?? null, false)', $tombol);
    }

    /**
     * Baris yang sudah ada tetap sudah ada: halaman yang basi (jalur sudah disimpan
     * bercatatan di tab atau perangkat lain) tidak boleh ditawari Urungkan-sebagai-hapus
     * untuk baris yang tidak dibuat tombol ini, karena Urungkan itu akan menghapus
     * catatan lama secara permanen (tinjauan Tugas 4 temuan 2; ruling W3-4b).
     */
    public function test_saving_a_trail_that_is_already_saved_says_so_and_offers_no_undo(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur();
        app(ConsiderationService::class)->simpan($user, $trail, 'Bawa jas hujan');

        $this->actingAs($user)->from(route('trails.index'))->post(route('trails.saved.store', $trail->id))
            ->assertRedirect(route('trails.index'))
            ->assertSessionHas('simpanan-sudah-ada', true)
            ->assertSessionHas('pengumuman', 'Jalur Selo sudah ada di simpanan.');

        $this->assertSame(1, TrailConsideration::query()->where('user_id', $user->id)->where('trail_id', $trail->id)->count());
        $this->assertSame('Bawa jas hujan', $this->catatan($user, $trail));

        $tombol = File::get(resource_path('js/components/trails/TombolSimpan.tsx'));
        $posisiSudahAda = strpos($tombol, 'sudahAda');
        $posisiToast = strpos($tombol, 'tampilkanToast(`${jalur.name} disimpan.`');
        $this->assertNotFalse($posisiSudahAda, 'Cabang simpananSudahAda harus ada supaya Urungkan tidak ditawarkan untuk baris yang tidak dibuat tombol ini.');
        $this->assertNotFalse($posisiToast);
        $this->assertLessThan($posisiToast, $posisiSudahAda, 'Cabang sudahAda harus diperiksa sebelum toast Urungkan ditawarkan.');
    }

    /**
     * Hanya kunjungan GET yang tidak async boleh menyalakan skeleton "memuat": POST/DELETE
     * dari TombolSimpan memakai preserveState, dan skeleton yang menyala melepas kartu serta
     * TombolSimpan di dalamnya, menutup dialog "Simpanan sudah penuh" dan panel Kenapa? yang
     * sedang terbuka (tinjauan Tugas 4 temuan 1; ruling W3-4a). Belum ada runner JS (Q17),
     * jadi asersinya pada string sumber.
     */
    public function test_only_page_visits_show_the_loading_skeleton(): void
    {
        $index = File::get(resource_path('js/pages/Trails/Index.tsx'));

        $this->assertStringContainsString("event.detail.visit.method === 'get'", $index);
        $this->assertStringContainsString('!event.detail.visit.async', $index);
    }

    /**
     * Ruling W2-5i: toast Urungkan hidup selama komponen pemanggil useKerangka terpasang.
     * Kartu dan chip dapat hilang oleh aksinya sendiri (Simpanan, saringan), jadi useKerangka
     * dipanggil halaman dan tampilkanToast diteruskan ke bawah; aksi yang memunculkan Urungkan
     * memakai preserveState supaya halamannya tidak dipasang ulang.
     */
    public function test_the_undo_toast_belongs_to_the_page_not_the_card(): void
    {
        $pemanggil = [];

        foreach ($this->berkasTsx('components/trails', true) as $berkas) {
            if (str_contains($this->tanpaKomentarJs($berkas->getContents()), 'useKerangka(')) {
                $pemanggil[] = str_replace('\\', '/', $berkas->getRelativePathname());
            }
        }

        $this->assertSame([], $pemanggil, 'Komponen jalur tidak boleh memiliki toast Urungkan sendiri.');
        $this->assertStringContainsString('const { tampilkanToast } = useKerangka();', File::get(resource_path('js/pages/Trails/Index.tsx')));
        $this->assertStringContainsString('const opsi = { preserveScroll: true, preserveState: true } as const;', File::get(resource_path('js/components/trails/TombolSimpan.tsx')));
    }
}
