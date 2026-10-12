<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use App\Services\ConsiderationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Catatan pribadi pada jalur tersimpan (FR-22, DK-07).
 *
 * Catatan hanya milik pemiliknya: ditulis, diubah, dan dihapus olehnya saja, tampil di
 * kartu Tersimpan miliknya, dan tidak pernah ikut ke halaman Banding maupun tautan
 * bagikan (yang terakhir dijaga test tautan bagikan FR-23 ketika fitur itu dibangun;
 * sekarang belum ada). Baris dicari di dalam simpanan
 * pengguna yang masuk, jadi id jalur dari klien tidak pernah menunjuk baris orang lain;
 * Policy menjaga lapis kedua.
 */
class SavedTrailNoteTest extends TestCase
{
    use RefreshDatabase;

    private function jalur(): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->published()->create();
    }

    private function tersimpan(User $user, ?Trail $jalur = null, ?string $catatan = null): Trail
    {
        $jalur ??= $this->jalur();
        app(ConsiderationService::class)->toggle($user, $jalur);

        if ($catatan !== null) {
            TrailConsideration::query()->whereBelongsTo($user)->where('trail_id', $jalur->id)->update(['note' => $catatan]);
        }

        return $jalur;
    }

    private function catatan(User $user, Trail $jalur): ?string
    {
        return TrailConsideration::query()->whereBelongsTo($user)->where('trail_id', $jalur->id)->value('note');
    }

    public function test_the_owner_can_write_a_note_on_a_saved_trail(): void
    {
        $user = User::factory()->create();
        $jalur = $this->tersimpan($user);

        $this->actingAs($user)
            ->from(route('trails.saved'))
            ->put(route('trails.saved.note.update', $jalur->id), ['catatan' => 'Ajak adik, bawa tenda dua orang.'])
            ->assertRedirect(route('trails.saved'))
            ->assertSessionHas('pengumuman', 'Catatan untuk '.$jalur->name.' disimpan.');

        $this->assertSame('Ajak adik, bawa tenda dua orang.', $this->catatan($user, $jalur));
    }

    public function test_the_owner_can_change_a_note(): void
    {
        $user = User::factory()->create();
        $jalur = $this->tersimpan($user, catatan: 'Catatan lama');

        $this->actingAs($user)->put(route('trails.saved.note.update', $jalur->id), ['catatan' => 'Catatan baru'])->assertRedirect();

        $this->assertSame('Catatan baru', $this->catatan($user, $jalur));
    }

    public function test_the_owner_can_delete_a_note(): void
    {
        $user = User::factory()->create();
        $jalur = $this->tersimpan($user, catatan: 'Akan dihapus');

        $this->actingAs($user)
            ->delete(route('trails.saved.note.destroy', $jalur->id))
            ->assertRedirect()
            ->assertSessionHas('pengumuman', 'Catatan untuk '.$jalur->name.' dihapus.');

        $this->assertNull($this->catatan($user, $jalur));
        $this->assertDatabaseHas('trail_considerations', ['user_id' => $user->id, 'trail_id' => $jalur->id]);
    }

    /**
     * Mengosongkan isian lalu menyimpan berarti menghapus catatannya, bukan menyimpan
     * string kosong yang terbaca sebagai catatan.
     */
    public function test_saving_an_empty_note_deletes_it(): void
    {
        $user = User::factory()->create();
        $jalur = $this->tersimpan($user, catatan: 'Akan dikosongkan');

        $this->actingAs($user)->put(route('trails.saved.note.update', $jalur->id), ['catatan' => '   '])->assertRedirect();

        $this->assertNull($this->catatan($user, $jalur));
    }

    public function test_a_note_longer_than_500_characters_is_refused_with_a_clear_message(): void
    {
        $user = User::factory()->create();
        $jalur = $this->tersimpan($user, catatan: 'Tetap');

        $this->actingAs($user)
            ->put(route('trails.saved.note.update', $jalur->id), ['catatan' => str_repeat('a', 501)])
            ->assertSessionHasErrors(['catatan' => 'Catatan pribadi tidak boleh lebih dari 500 karakter.']);

        $this->assertSame('Tetap', $this->catatan($user, $jalur));
    }

    /**
     * Batasnya karakter, bukan bita: 500 huruf beraksen tetap diterima.
     */
    public function test_exactly_500_characters_is_accepted_even_when_they_are_multibyte(): void
    {
        $user = User::factory()->create();
        $jalur = $this->tersimpan($user);
        $panjang = str_repeat('é', 500);

        $this->actingAs($user)->put(route('trails.saved.note.update', $jalur->id), ['catatan' => $panjang])->assertSessionHasNoErrors();

        $this->assertSame($panjang, $this->catatan($user, $jalur));
    }

    public function test_the_note_is_shown_on_the_owners_saved_card(): void
    {
        $user = User::factory()->create();
        $this->tersimpan($user, catatan: 'Bawa jas hujan');

        $this->actingAs($user)->get(route('trails.saved'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('trails.0.catatan', 'Bawa jas hujan'));
    }

    /**
     * Batas catatan di formulir datang dari konstanta yang sama dengan aturan
     * validasinya, jadi hitungan di layar tidak pernah menyebut angka yang berbeda dari
     * yang ditolak server.
     */
    public function test_the_page_states_the_note_limit_from_the_validation_constant(): void
    {
        $user = User::factory()->create();
        $this->tersimpan($user);

        $this->actingAs($user)->get(route('trails.saved'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('batasCatatan', TrailConsideration::CATATAN_MAKS));

        $this->assertStringContainsString('{panjang} dari {batasCatatan} karakter', File::get(resource_path('js/components/trails/banding/KartuSimpanan.tsx')));
    }

    /**
     * Catatan 500 karakter tanpa spasi (misalnya tautan yang ditempel) tidak boleh
     * melebarkan halaman di layar 360 px. overflow-wrap: anywhere mengecilkan lebar
     * minimum kolomnya, sedangkan break-word bawaan body tidak; kolom isi kartu boleh
     * menyempit sampai nol (minmax(0,1fr)).
     */
    public function test_a_long_unbroken_note_wraps_inside_its_card(): void
    {
        $kartu = File::get(resource_path('js/components/trails/banding/KartuSimpanan.tsx'));

        // Upgrade tailwind 4: break-words bernama wrap-break-word, [overflow-wrap:anywhere] bernama wrap-anywhere; CSS-nya sama.
        $this->assertMatchesRegularExpression('/<p className="[^"]*\bwrap-anywhere\b[^"]*">\{jalur\.catatan\}<\/p>/', $kartu);
        $this->assertStringContainsString('grid grid-cols-[minmax(0,1fr)]', $kartu);
        $this->assertStringContainsString('<div className="min-w-0', $kartu);
    }

    /**
     * F1 (WCAG 2.4.3): "Tulis catatan"/"Ubah catatan" lenyap dari DOM begitu ditekan.
     * Tanpa pemindahan fokus eksplisit, fokus jatuh ke <body> dan Tab berikutnya
     * melompati editor yang baru muncul. Efeknya baru berjalan mulai perubahan kedua
     * (pertama.current) supaya membuka Simpanan tidak mencuri fokus halaman.
     */
    public function test_opening_the_note_editor_moves_focus_to_the_textarea(): void
    {
        $kartu = File::get(resource_path('js/components/trails/banding/KartuSimpanan.tsx'));

        $this->assertStringContainsString('const pertama = useRef(true);', $kartu);
        $this->assertMatchesRegularExpression(
            '/document\.getElementById\(menyunting \? idCatatan : idPemicu\)\?\.focus\(\)/',
            $kartu
        );
    }

    /**
     * Batal, simpan sukses, dan hapus catatan sukses sama-sama menutup editor lewat
     * setMenyunting(false); ketiganya harus mengembalikan fokus ke tombol pemicu yang
     * sama, bukan membiarkannya jatuh ke <body>.
     *
     * Offset tombol menjadi -ml-4 di perbaikan akhir W1 (M-6): tombol sm kini px-4, dan
     * offset lama -ml-3 menggeser labelnya dari tepi kolom. Yang dijaga test ini tetap id
     * pemicunya.
     */
    public function test_closing_the_note_editor_returns_focus_to_the_trigger_button(): void
    {
        $kartu = File::get(resource_path('js/components/trails/banding/KartuSimpanan.tsx'));

        $this->assertMatchesRegularExpression('/idPemicu = `pemicu-catatan-\$\{jalur\.id\}`/', $kartu);
        $this->assertStringContainsString(
            '<Button id={idPemicu} variant="ghost" size="sm" className="-ml-4" onClick={mulaiSunting}>',
            $kartu
        );
    }

    /**
     * Tugas 4, minor tertunda: tanpa ini, batal meninggalkan draf yang belum disimpan di
     * form.data, bukan mengembalikannya ke catatan yang sungguh tersimpan.
     */
    public function test_cancelling_the_edit_restores_the_forms_saved_note(): void
    {
        $kartu = File::get(resource_path('js/components/trails/banding/KartuSimpanan.tsx'));
        $mulai = strpos($kartu, 'const batalSunting = () => {');

        $this->assertNotFalse($mulai, 'batalSunting tidak ditemukan.');

        $akhir = strpos($kartu, '};', $mulai);
        $this->assertStringContainsString("form.setData('catatan', jalur.catatan ?? '')", substr($kartu, $mulai, $akhir - $mulai));
    }

    /**
     * F2: ConsiderationService::remove() menghapus baris trail_considerations, termasuk
     * note. Ketika jalur ini punya catatan, "Hapus dari simpanan" bertanya dulu dan menyebut
     * bahwa catatannya ikut terhapus. Sejak W3 pertanyaannya dialog TombolSimpan, dan
     * Urungkan mengembalikan catatannya (SavedUndoTest); tanpa catatan, langsung hapus.
     */
    public function test_removing_a_saved_trail_with_a_note_asks_for_confirmation_first(): void
    {
        $kartu = File::get(resource_path('js/components/trails/banding/KartuSimpanan.tsx'));
        $tombol = File::get(resource_path('js/components/trails/TombolSimpan.tsx'));

        $this->assertStringContainsString('adaCatatan: jalur.catatan !== null', $kartu);
        $this->assertStringContainsString('} else if (jalur.adaCatatan) {', $tombol);
        $this->assertStringContainsString('keterangan="Catatan pribadi pada jalur ini ikut terhapus.', $tombol);
    }

    /**
     * IDOR: pendaki lain menunjuk jalur yang saya simpan. Barisnya dicari di simpanan
     * dia sendiri, tidak ditemukan, dan catatan saya tidak tersentuh.
     */
    public function test_another_hiker_cannot_write_on_my_note(): void
    {
        $saya = User::factory()->create();
        $penyusup = User::factory()->create();
        $jalur = $this->tersimpan($saya, catatan: 'Milik saya');

        $this->actingAs($penyusup)->put(route('trails.saved.note.update', $jalur->id), ['catatan' => 'Ditimpa'])->assertNotFound();
        $this->actingAs($penyusup)->delete(route('trails.saved.note.destroy', $jalur->id))->assertNotFound();

        $this->assertSame('Milik saya', $this->catatan($saya, $jalur));
    }

    /**
     * Dua pendaki menyimpan jalur yang sama: masing-masing hanya menulis barisnya sendiri.
     */
    public function test_a_note_written_by_one_hiker_never_lands_on_another_hikers_row(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $jalur = $this->jalur();
        $this->tersimpan($saya, $jalur, 'Catatan saya');
        $this->tersimpan($orangLain, $jalur);

        $this->actingAs($orangLain)->put(route('trails.saved.note.update', $jalur->id), ['catatan' => 'Catatan dia'])->assertRedirect();

        $this->assertSame('Catatan saya', $this->catatan($saya, $jalur));
        $this->assertSame('Catatan dia', $this->catatan($orangLain, $jalur));
    }

    /**
     * Mass assignment: pemilik dan jalur ditentukan server, bukan badan permintaan.
     */
    public function test_owner_and_trail_fields_in_the_payload_are_ignored(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $jalurSaya = $this->tersimpan($saya);
        $jalurDia = $this->tersimpan($orangLain, catatan: 'Milik dia');

        $this->actingAs($saya)->put(route('trails.saved.note.update', $jalurSaya->id), [
            'catatan' => 'Catatan saya',
            'note' => 'Lewat nama kolom',
            'user_id' => $orangLain->id,
            'trail_id' => $jalurDia->id,
        ])->assertRedirect();

        $this->assertSame('Catatan saya', $this->catatan($saya, $jalurSaya));
        $this->assertSame('Milik dia', $this->catatan($orangLain, $jalurDia));
        $this->assertSame(1, TrailConsideration::query()->whereBelongsTo($saya)->count());
    }

    public function test_a_note_needs_the_trail_to_be_saved_first(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('trails.saved.note.update', $this->jalur()->id), ['catatan' => 'Belum disimpan'])->assertNotFound();

        $this->assertDatabaseCount('trail_considerations', 0);
    }

    public function test_a_guest_cannot_write_a_note(): void
    {
        $user = User::factory()->create();
        $jalur = $this->tersimpan($user, catatan: 'Tetap');

        $this->put(route('trails.saved.note.update', $jalur->id), ['catatan' => 'Tamu'])->assertRedirect(route('login'));

        $this->assertSame('Tetap', $this->catatan($user, $jalur));
    }

    public function test_a_guest_cannot_delete_a_note(): void
    {
        $user = User::factory()->create();
        $jalur = $this->tersimpan($user, catatan: 'Tetap');

        $this->delete(route('trails.saved.note.destroy', $jalur->id))->assertRedirect(route('login'));

        $this->assertSame('Tetap', $this->catatan($user, $jalur));
    }

    /**
     * Permintaan tanpa kolom catatan bukan perintah menghapus: klien yang rusak atau
     * permintaan buatan tangan tidak boleh mengosongkan catatan diam-diam.
     */
    public function test_a_request_without_the_note_field_changes_nothing(): void
    {
        $user = User::factory()->create();
        $jalur = $this->tersimpan($user, catatan: 'Tetap');

        $this->actingAs($user)
            ->put(route('trails.saved.note.update', $jalur->id), [])
            ->assertSessionHasErrors(['catatan' => 'Catatan pribadi tidak terkirim. Muat ulang halaman lalu coba lagi.']);

        $this->assertSame('Tetap', $this->catatan($user, $jalur));
    }

    /**
     * Catatan ditulis bebas oleh pendaki. Isinya disimpan apa adanya, props membawa teks
     * mentah, JSON halaman menyandikan tandanya, dan kartunya merender teks (tanpa
     * dangerouslySetInnerHTML, dijaga SavedRoutesTest).
     */
    public function test_a_note_with_markup_is_kept_as_text_and_never_rendered_as_html(): void
    {
        $user = User::factory()->create();
        $jalur = $this->tersimpan($user);
        $catatan = '<b>Bawa</b> <script>alert(1)</script>';

        $this->actingAs($user)->put(route('trails.saved.note.update', $jalur->id), ['catatan' => $catatan])->assertRedirect();

        $this->assertSame($catatan, $this->catatan($user, $jalur));

        $respons = $this->actingAs($user)->get(route('trails.saved'));

        $respons->assertInertia(fn (AssertableInertia $page) => $page->where('trails.0.catatan', $catatan));
        $respons->assertDontSee('<script>alert(1)</script>', escape: false);
    }

    /**
     * Jalur tanpa catatan mengirim null, bukan string kosong yang terbaca sebagai
     * catatan (R-007: yang tidak ada tetap tidak ada).
     */
    public function test_a_saved_trail_without_a_note_sends_null(): void
    {
        $user = User::factory()->create();
        $this->tersimpan($user);

        $this->actingAs($user)->get(route('trails.saved'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('trails.0.catatan', null));
    }

    public function test_the_policy_lets_only_the_owner_touch_the_note(): void
    {
        $pemilik = User::factory()->create();
        $orangLain = User::factory()->create();
        $jalur = $this->tersimpan($pemilik);
        $baris = TrailConsideration::query()->whereBelongsTo($pemilik)->where('trail_id', $jalur->id)->firstOrFail();

        $this->assertTrue(Gate::forUser($pemilik)->allows('update', $baris));
        $this->assertFalse(Gate::forUser($orangLain)->allows('update', $baris));
    }

    /**
     * Catatan hanya di kartu Tersimpan. Halaman Banding milik pemilik pun tidak
     * membawanya: halaman itulah asal tautan bagikan, dan tidak boleh ada jalan bagi
     * catatan untuk ikut terbawa.
     */
    public function test_the_note_never_reaches_the_comparison_page(): void
    {
        $user = User::factory()->create();
        $a = $this->tersimpan($user, catatan: 'Catatan rahasia banding');
        $b = $this->tersimpan($user);

        $this->actingAs($user)
            ->get(route('trails.compare', ['trails' => $a->id.','.$b->id]))
            ->assertOk()
            ->assertDontSee('Catatan rahasia banding');
    }

    /**
     * Batasnya dibaca dari config saat rute didaftarkan, jadi test memakai nilai bawaan.
     */
    public function test_writing_notes_is_rate_limited(): void
    {
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');
        $user = User::factory()->create();
        $jalur = $this->tersimpan($user);

        foreach (range(1, $batas) as $i) {
            $this->actingAs($user)->put(route('trails.saved.note.update', $jalur->id), ['catatan' => 'Ke-'.$i])->assertRedirect();
        }

        $this->actingAs($user)->put(route('trails.saved.note.update', $jalur->id), ['catatan' => 'Kelebihan'])->assertTooManyRequests();
        $this->assertSame('Ke-'.$batas, $this->catatan($user, $jalur));
    }

    /**
     * Kebijakan data pribadi §2: catatan pribadi dihapus bersama akunnya.
     */
    public function test_deleting_the_account_deletes_the_notes(): void
    {
        $user = User::factory()->create();
        $this->tersimpan($user, catatan: 'Ikut terhapus');

        $user->delete();

        $this->assertDatabaseMissing('trail_considerations', ['note' => 'Ikut terhapus']);
    }
}
