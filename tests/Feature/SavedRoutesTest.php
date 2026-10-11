<?php

namespace Tests\Feature;

use App\Models\Mountain;
use App\Models\Trail;
use App\Models\User;
use App\Services\ConsiderationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Daftar rute tersimpan.
 *
 * Sumbernya simpanan di basis data (trail_considerations), sumber yang sama dengan hitungan
 * Simpanan di kepala halaman dan pilihan bawaan halaman Banding (baki Jelajah lama sudah
 * diganti tautan Simpanan sejak W3). Versi sebelumnya membaca sesi, sehingga daftarnya
 * kosong lagi setiap kali pendaki keluar: Logout memanggil Session::invalidate().
 *
 * Halamannya React (Inertia): data diuji lewat props yang dikirim server, dan teks yang
 * dirender klien dijaga dari sumber halamannya.
 */
class SavedRoutesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function jalur(array $atribut = []): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create())->published()->create($atribut);
    }

    private function daftar(User $user): TestResponse
    {
        return $this->actingAs($user)->get(route('trails.saved'))->assertOk();
    }

    private function sumber(string $berkas = 'js/pages/Trails/Saved.tsx'): string
    {
        return File::get(resource_path($berkas));
    }

    /**
     * Tiga angka kartu Simpanan (prototype 06): jarak, tanjakan, dan lama naik. Sebelum
     * tinjauan akhir W3 test ini juga menegaskan `technical` ("difficulty"); kolom itu hanya
     * dirender TrailRow.tsx, yang dihapus (W3-C8), jadi tidak lagi dikirim (ruling W3-F3, m5)
     * dan test ini menegaskan ketiadaannya, bukan kata tingkat teknisnya.
     */
    public function test_the_list_shows_each_saved_trail_with_its_three_numbers(): void
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->for(Mountain::factory()->create())->easy()->published()->create([
            'name' => 'Jalur Selo',
            'distance_km' => 12.5,
            'elevation_gain_m' => 1250,
            'estimated_duration_minutes' => 390,
        ]);
        app(ConsiderationService::class)->toggle($user, $trail);

        $this->daftar($user)->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Trails/Saved')
            ->where('title', 'Simpanan')
            ->where('trails.0.name', 'Jalur Selo')
            ->where('trails.0.jarak', '12,5 km')
            ->where('trails.0.tanjakan', '1.250 m')
            ->where('trails.0.durasi', '6,5 j')
            ->missing('trails.0.technical')
            ->missing('trails.0.curam')
            ->missing('trails.0.kemudahan'));

        // T7-M2: angka yang tidak ada dibuang dan setiap angka bernama untuk pembaca layar,
        // lewat satu komponen yang dipakai juga pratinjau peta Jelajah.
        $this->assertStringContainsString('<AngkaRingkas jalur={jalur}', $this->sumber('js/components/trails/banding/KartuSimpanan.tsx'));
        $this->assertStringContainsString('<AngkaRingkas jalur={pratinjau}', $this->sumber('js/components/trails/PetaJelajah.tsx'));
        $angka = $this->sumber('js/components/trails/AngkaRingkas.tsx');
        $this->assertStringContainsString(".filter((a) => a.nilai !== '-')", $angka);
        $this->assertStringContainsString('<span className="sr-only">Jarak </span>', $angka);
    }

    /**
     * Kriteria spesifikasi: jalur tersimpan tetap ada setelah keluar lalu masuk kembali.
     */
    public function test_saved_trails_are_still_listed_after_logging_out_and_back_in(): void
    {
        $user = User::factory()->create();
        $trail = $this->jalur(['name' => 'Jalur Wekas']);
        $this->actingAs($user)->post(route('trails.saved.store', $trail->id));

        $this->actingAs($user)->post(route('logout'));
        $this->assertGuest();

        $this->daftar($user)->assertInertia(fn (AssertableInertia $page) => $page->where('trails.0.name', 'Jalur Wekas'));
    }

    public function test_trails_saved_by_another_hiker_are_not_listed(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $service = app(ConsiderationService::class);
        $service->toggle($saya, $this->jalur(['name' => 'Jalur Milik Saya']));
        $service->toggle($orangLain, $this->jalur(['name' => 'Jalur Milik Orang Lain']));

        $nama = array_column($this->daftar($saya)->viewData('page')['props']['trails'], 'name');

        $this->assertSame(['Jalur Milik Saya'], $nama);
    }

    /**
     * Terbaru dulu, urutan yang sama dengan "tiga simpanan terbaru" di halaman Banding.
     */
    public function test_the_list_is_newest_first(): void
    {
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);
        $service->toggle($user, $this->jalur(['name' => 'Jalur Disimpan Pertama']));
        $service->toggle($user, $this->jalur(['name' => 'Jalur Disimpan Kedua']));

        $nama = array_column($this->daftar($user)->viewData('page')['props']['trails'], 'name');

        $this->assertSame(['Jalur Disimpan Kedua', 'Jalur Disimpan Pertama'], $nama);
    }

    public function test_an_empty_list_explains_how_to_save_a_trail(): void
    {
        $this->daftar(User::factory()->create())->assertInertia(fn (AssertableInertia $page) => $page->has('trails', 0));

        $this->assertStringContainsString('Belum ada jalur tersimpan', $this->sumber());
        $this->assertStringContainsString('Cari jalur di Jelajah', $this->sumber());
    }

    public function test_a_guest_is_redirected_to_the_login_page(): void
    {
        $this->get(route('trails.saved'))->assertRedirect(route('login'));
    }

    /**
     * Nama jalur tidak hanya ditulis admin: pemegang kredensial Ahli juga mengirimnya.
     * Props membawa teks mentah, JSON halaman menyandikan tandanya, dan React merendernya
     * sebagai teks (tanpa dangerouslySetInnerHTML).
     */
    public function test_a_trail_name_is_escaped(): void
    {
        $user = User::factory()->create();
        app(ConsiderationService::class)->toggle($user, $this->jalur(['name' => 'Jalur <script>alert(1)</script>']));

        $respons = $this->daftar($user);

        $respons->assertInertia(fn (AssertableInertia $page) => $page->where('trails.0.name', 'Jalur <script>alert(1)</script>'));
        $respons->assertDontSee('<script>alert(1)</script>', escape: false);

        foreach (['js/pages/Trails/Saved.tsx', 'js/components/trails/banding/KartuSimpanan.tsx'] as $berkas) {
            $this->assertStringNotContainsString('dangerouslySetInnerHTML', $this->sumber($berkas));
        }
    }

    /**
     * Batas simpanan dan batas banding disebut sebelum ditabrak, keduanya dari config.
     */
    public function test_the_list_states_its_size_against_the_configured_limits(): void
    {
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);
        $service->toggle($user, $this->jalur());
        $service->toggle($user, $this->jalur());

        $this->daftar($user)->assertInertia(fn (AssertableInertia $page) => $page
            ->has('trails', 2)
            ->where('batas', 50)
            ->where('batasBanding', 3));
        $this->assertStringContainsString('`${trails.length} jalur tersimpan, maksimal ${batas}.', $this->sumber());
    }

    /**
     * R-022: jalur yang ditarik dari terbit tetap tampil bersama catatannya, tetapi tidak
     * dapat dicentang untuk banding, dan alasannya disebut di kartunya.
     */
    public function test_a_saved_trail_that_is_no_longer_published_cannot_be_picked_for_comparison(): void
    {
        $user = User::factory()->create();
        $ditarik = $this->jalur(['name' => 'Jalur Ditarik']);
        app(ConsiderationService::class)->toggle($user, $ditarik);
        $ditarik->update(['is_published' => false]);

        $this->daftar($user)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('trails.0.name', 'Jalur Ditarik')
            ->where('trails.0.terbit', false));

        $kartu = $this->sumber('js/components/trails/banding/KartuSimpanan.tsx');
        $this->assertStringContainsString('!jalur.terbit', $kartu);
        $this->assertStringContainsString('Jalur ini sedang tidak terbit, jadi tidak dapat dibandingkan.', $kartu);
        $this->assertStringContainsString('disabled={!jalur.terbit}', $kartu);
        $this->assertStringContainsString('trails.filter((t) => t.terbit)', $this->sumber());
    }

    /**
     * Regresi 26 September 2026: halaman ini mengimpor `remove` dari keluaran Wayfinder
     * yang basi (`@/routes/trails/compare`), sehingga "Hapus" mengirim DELETE ke alamat
     * yang tidak dilayani. Rute hapus simpanan adalah trails.saved.remove.
     */
    public function test_removal_goes_through_the_saved_list_route(): void
    {
        $kartu = $this->sumber('js/components/trails/banding/KartuSimpanan.tsx');

        // Sejak W3 penghapusan lewat TombolSimpan (bentuk "hapus"), rute yang sama dengan Jelajah.
        $this->assertStringContainsString('bentuk="hapus"', $kartu);
        $tombol = $this->sumber('js/components/trails/TombolSimpan.tsx');
        $this->assertStringContainsString("import { remove, store } from '@/routes/trails/saved';", $tombol);
        $this->assertStringNotContainsString('@/routes/trails/compare', $kartu);
        $this->assertStringNotContainsString('@/routes/trails/compare', $this->sumber());

        // Ruling W3-7a: kendali destruktif tidak boleh menumpuk target tautan di sebelahnya,
        // jadi cabang hapus tidak boleh memakai margin negatif yang menggeser kotak tekannya
        // ke kiri atas "Lihat rincian" (KartuSimpanan menaruh keduanya sebaris).
        $mulaiHapus = strpos($tombol, "bentuk === 'hapus'");
        $this->assertNotFalse($mulaiHapus, 'Cabang bentuk hapus tidak ditemukan.');
        $akhirHapus = strpos($tombol, ') : bentuk ===', $mulaiHapus);
        $this->assertStringNotContainsString('-ml-4', substr($tombol, $mulaiHapus, $akhirHapus - $mulaiHapus));
    }

    /**
     * DK-05: yang dicentang dihitung terhadap batas banding dari config, dan batasnya
     * disebut sebelum ditabrak.
     */
    public function test_the_selection_states_its_count_against_the_comparison_limit(): void
    {
        $aksi = $this->sumber('js/components/trails/banding/AksiPilihan.tsx');

        $this->assertStringContainsString('Dipilih {jumlah} dari {maks}.', $aksi);
        $this->assertStringContainsString('Pilih minimal 2 jalur untuk membandingkan.', $aksi);
        $this->assertStringContainsString('Batas banding tercapai.', $aksi);
        $this->assertStringContainsString('maks={batasBanding}', $this->sumber());
    }

    /**
     * Baki banding di layar sempit adalah ringkasan satu baris (tinjauan akhir W2, W2-F1). Baki
     * Jelajah lama yang mendaftar semua nama tersimpan membuat lapisan bawah menutup 40 sampai
     * 57 persen layar 320 sampai 360 px. Baki Simpanan hanya membawa hitungan, ruasnya, pesan
     * batas, dan satu aksi pada satu waktu; nama jalur yang dipilih sudah terlihat dari centang
     * di kartunya, jadi tidak diulang dan baki tidak membungkus menjadi daftar.
     */
    public function test_the_compare_tray_is_a_one_row_summary_without_names(): void
    {
        $aksi = $this->sumber('js/components/trails/banding/AksiPilihan.tsx');

        $this->assertStringContainsString('<div className="flex w-full items-center gap-3">', $aksi);
        $this->assertStringNotContainsString('flex-wrap', $aksi, 'Baki tidak boleh membungkus aksinya ke baris baru.');
        $this->assertStringContainsString('dipilih: number[];', $aksi, 'Baki hanya menerima id pilihan, bukan nama.');
        $this->assertDoesNotMatchRegularExpression('/\{[^}]*\.(?:name|nama)\b/', $aksi, 'Nama jalur tidak diulang di baki.');
        $this->assertSame(3, substr_count($aksi, 'className="shrink-0"') + substr_count($aksi, 'shrink-0 text-meta'), 'Setiap keadaan menampilkan tepat satu aksi atau petunjuk yang tidak menyusut.');
        $this->assertStringContainsString('<BarAksi label="Pilihan banding" lebarPenuh>', $this->sumber());
    }

    /**
     * T7-M6 (temuan tangkapan W3 butir 8, ruling W3-G1 butir f): isi baki menumpuk di kiri
     * mulai 640 px (`simpanan-1440-light.webp`) karena anak BarAksi tidak melebar sendiri
     * walau `.chrome` sudah selebar kartu. `lebarPenuh` pada baki Simpanan membuatnya
     * mengisi lebar yang tersedia; Fit.tsx dan bar aksi rincian jalur tidak memakainya,
     * karena isinya kelompok tombol ringkas yang sengaja tidak melebar (bukti T12,
     * belum diukur untuk keduanya).
     */
    public function test_the_compare_tray_fills_the_available_width_from_640px(): void
    {
        $bar = File::get(resource_path('js/components/ui/BarAksi.tsx'));

        $this->assertMatchesRegularExpression("/lebarPenuh\?:\s*boolean;/", $bar);
        $this->assertStringContainsString(
            "<div className={kelas('flex flex-wrap gap-2 sm:justify-end', lebarPenuh && 'sm:flex-1')}>{children}</div>",
            $bar
        );

        $this->assertStringContainsString('<BarAksi label="Pilihan banding" lebarPenuh>', $this->sumber());
        $this->assertStringNotContainsString('lebarPenuh', File::get(resource_path('js/pages/Trails/Fit.tsx')), 'Fit.tsx sengaja tetap berupa kelompok tombol, bukan baris melebar.');
        $this->assertStringNotContainsString('lebarPenuh', File::get(resource_path('js/pages/Trails/Show.tsx')), 'Bar aksi rincian jalur sengaja tetap berupa kelompok tombol, bukan baris melebar.');
    }

    /**
     * Tanpa centang, tautannya telanjang: halaman Banding lalu membandingkan tiga simpanan
     * terbaru. Dengan centang, pilihan dibawa di ?trails=.
     */
    public function test_saved_trails_can_be_compared_from_the_list(): void
    {
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);
        $service->toggle($user, $this->jalur());
        $service->toggle($user, $this->jalur());

        $this->daftar($user)->assertInertia(fn (AssertableInertia $page) => $page->has('trails', 2));

        $aksi = $this->sumber('js/components/trails/banding/AksiPilihan.tsx');
        $this->assertStringContainsString('compare.url()', $aksi);
        $this->assertStringContainsString("compare.url({ query: { trails: dipilih.join(',') } })", $aksi);
        $this->assertStringContainsString('<AksiPilihan', $this->sumber());
    }

    public function test_a_single_saved_trail_explains_what_comparing_needs(): void
    {
        $user = User::factory()->create();
        app(ConsiderationService::class)->toggle($user, $this->jalur());

        $this->daftar($user)->assertInertia(fn (AssertableInertia $page) => $page->has('trails', 1));
        $this->assertStringContainsString('Simpan satu jalur lagi untuk membandingkan.', $this->sumber());
        $this->assertStringContainsString('tersedia >= 2', $this->sumber());

        // T7-M4 dan T7-M5 tinjauan akhir W3: tanpa centang, deskripsi tidak menawarkan "Pilih
        // sampai N" dan formulir catatan tidak terbungkus kelompok pilihan banding.
        $this->assertStringContainsString('${tersedia >= 2 ? ` Pilih sampai ${batasBanding} untuk dibandingkan.` : \'\'}', $this->sumber());
        $this->assertMatchesRegularExpression('/\{tersedia >= 2 \? \(\s*<fieldset>/', $this->sumber());
        $this->assertStringNotContainsString('penuh', $this->sumber('js/components/trails/banding/KartuSimpanan.tsx'), 'Prop pilihan.penuh tidak pernah dibaca (T7-M3).');
    }

    /**
     * F5 (antislop R-26, kontrol harus berfungsi): dua simpanan tetapi hanya satu yang
     * masih terbit. Hanya satu kotak yang dapat dicentang di sini, jadi kontrol pilihan
     * tidak berguna dan tidak boleh tampil; banding butuh minimal dua jalur terbit.
     * Gerbangnya harus menghitung simpanan yang terbit (tersedia), bukan trails.length
     * yang ikut menghitung simpanan yang ditarik dari terbit.
     */
    public function test_the_selection_control_is_hidden_when_only_one_saved_trail_is_still_published(): void
    {
        $user = User::factory()->create();
        $terbit = $this->jalur(['name' => 'Jalur Terbit']);
        $ditarik = $this->jalur(['name' => 'Jalur Ditarik']);
        $service = app(ConsiderationService::class);
        $service->toggle($user, $terbit);
        $service->toggle($user, $ditarik);
        $ditarik->update(['is_published' => false]);

        $respons = $this->daftar($user);
        $respons->assertInertia(fn (AssertableInertia $page) => $page->has('trails', 2));

        $status = array_column($respons->viewData('page')['props']['trails'], 'terbit');
        $this->assertEqualsCanonicalizing([true, false], $status);

        $this->assertStringContainsString('tersedia >= 2', $this->sumber());
        $this->assertStringNotContainsString('trails.length >= 2', $this->sumber());
    }

    /**
     * Cacat yang pernah ditutup: simpanan tidak dapat dicapai dari menunya sendiri
     * (HANDOVER.md §7). Daftar ini wajib punya pintu dari dua tempat pendaki berada.
     */
    public function test_jelajah_links_to_the_list(): void
    {
        $user = User::factory()->create();
        app(ConsiderationService::class)->toggle($user, $this->jalur());

        // W3: baki Jelajah diganti tautan Simpanan berhitungan di kepala halaman (ponsel) dan
        // di header layar lebar (spec v3 bagian 5.2 dan 6.2); pintunya tetap dua.
        $this->actingAs($user)->get(route('trails.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('jumlahSimpanan', 1));
        $this->assertStringContainsString('aksi={<TautanSimpanan className="lg:hidden" />}', $this->sumber('js/pages/Trails/Index.tsx'));
        $this->assertStringContainsString('<TautanSimpanan', $this->sumber('js/layouts/KepalaLebar.tsx'));
        $this->assertStringContainsString('href={saved.url()}', $this->sumber('js/layouts/TautanSimpanan.tsx'));
    }

    public function test_the_compare_page_links_to_the_list(): void
    {
        $user = User::factory()->create();
        $service = app(ConsiderationService::class);
        $service->toggle($user, $this->jalur());
        $service->toggle($user, $this->jalur());

        $this->actingAs($user)->get(route('trails.compare'))->assertInertia(fn (AssertableInertia $page) => $page->has('jalur', 2));
        $this->assertStringContainsString('href={saved.url()}', $this->sumber('js/pages/Trails/Compare.tsx'));
    }

    /**
     * Jalur dihapus dari simpanan di halaman ini, lewat aksi yang menyebut dirinya
     * penghapusan, bukan sakelar berlabel "Tersimpan".
     */
    public function test_a_trail_can_be_removed_from_the_list(): void
    {
        $user = User::factory()->create();
        $tetap = $this->jalur(['name' => 'Jalur Tetap']);
        $dihapus = $this->jalur(['name' => 'Jalur Dihapus']);
        $service = app(ConsiderationService::class);
        $service->toggle($user, $tetap);
        $service->toggle($user, $dihapus);

        $this->actingAs($user)
            ->from(route('trails.saved'))
            ->delete(route('trails.saved.remove', $dihapus->id))
            ->assertRedirect(route('trails.saved'))
            ->assertSessionHas('pengumuman', 'Jalur Dihapus dihapus dari simpanan.');

        $nama = array_column($this->daftar($user)->viewData('page')['props']['trails'], 'name');

        $this->assertSame(['Jalur Tetap'], $nama);
        $this->assertStringContainsString('remove.url({ trail: ', $this->sumber('js/components/trails/TombolSimpan.tsx'));
    }

    /**
     * "Hapus" adalah penghapusan, bukan sakelar: mengulangnya tidak mengembalikan jalur.
     */
    public function test_removing_the_same_trail_twice_does_not_bring_it_back(): void
    {
        $user = User::factory()->create();
        [$a, $b] = [$this->jalur(), $this->jalur()];
        $service = app(ConsiderationService::class);
        $service->toggle($user, $a);
        $service->toggle($user, $b);

        $this->actingAs($user)->delete(route('trails.saved.remove', $a->id))->assertRedirect();
        $this->actingAs($user)->delete(route('trails.saved.remove', $a->id))->assertRedirect();

        $this->assertSame([$b->id], $service->simpanan($user)->pluck('trail_id')->all());
    }

    /**
     * IDOR: id jalur datang dari klien, tetapi baris yang dihapus selalu milik pengguna
     * yang masuk.
     */
    public function test_removing_never_touches_another_hikers_list(): void
    {
        $saya = User::factory()->create();
        $orangLain = User::factory()->create();
        $jalur = $this->jalur();
        app(ConsiderationService::class)->toggle($orangLain, $jalur);

        $this->actingAs($saya)->delete(route('trails.saved.remove', $jalur->id))->assertRedirect();

        $this->assertDatabaseHas('trail_considerations', ['user_id' => $orangLain->id, 'trail_id' => $jalur->id]);
    }

    public function test_a_guest_cannot_remove_anything(): void
    {
        $user = User::factory()->create();
        $jalur = $this->jalur();
        app(ConsiderationService::class)->toggle($user, $jalur);

        $this->delete(route('trails.saved.remove', $jalur->id))->assertRedirect(route('login'));

        $this->assertDatabaseHas('trail_considerations', ['user_id' => $user->id, 'trail_id' => $jalur->id]);
    }

    /**
     * Prototype 06: setiap kartu membawa label kecocokan dasar pembacanya, dinilai sekaligus
     * untuk seluruh simpanan, tanpa skor (BR-09). Jalur yang ditarik dari terbit tidak dinilai.
     */
    public function test_each_saved_trail_carries_the_readers_fit_label(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => 'INTERMEDIATE', 'completed_at' => now()]);
        $user = $user->fresh();
        $terbit = $this->jalur();
        $ditarik = $this->jalur();
        app(ConsiderationService::class)->toggle($user, $terbit);
        app(ConsiderationService::class)->toggle($user, $ditarik);
        $ditarik->forceFill(['is_published' => false])->saveQuietly();

        $trails = collect($this->daftar($user)->viewData('page')['props']['trails'])->keyBy('id');

        $this->assertContains($trails[$terbit->id]['fit']['label'], ['COCOK', 'PERLU_PERSIAPAN', 'KURANG_COCOK']);
        $this->assertNull($trails[$ditarik->id]['fit']);
        $this->assertStringNotContainsString('internalScore', json_encode($trails->all()));

        $this->daftar(User::factory()->create())->assertInertia(fn (AssertableInertia $page) => $page->where('kataBelumDinilai', 'Belum dinilai'));
    }

    /**
     * Ruling W2-5i: kartu hilang oleh penghapusannya sendiri, jadi halaman yang memiliki toast
     * Urungkan dan meneruskannya ke kartu.
     */
    public function test_the_page_owns_the_undo_toast_for_removals(): void
    {
        $this->assertStringContainsString('const { tampilkanToast } = useKerangka();', $this->sumber());
        $this->assertStringContainsString('tampilkanToast={tampilkanToast}', $this->sumber());
        $this->assertStringContainsString('tampilkanToast={tampilkanToast} bentuk="hapus"', $this->sumber('js/components/trails/banding/KartuSimpanan.tsx'));
    }

    /**
     * Melewati batas banding tidak dimatikan diam-diam: centangnya tetap aktif dan bakinya
     * menyebut batasnya (prototype 06). Centang memakai KotakCentang berlabel nama jalur di
     * dalam kelompok yang menyebut batasnya (keputusan koordinator W3 nomor 5). Pesannya
     * pendek supaya baki tetap satu baris di 320 px (W2-F1).
     *
     * Sejak tinjauan akhir W3 (T7-M1) pesannya lewat umumkan(), yang memberi pembeda tak
     * terlihat pada pengulangan teks yang sama supaya wilayah role="status" mengumumkannya
     * lagi, dan pesan dikosongkan begitu pilihan berubah dari luar centang.
     */
    public function test_going_past_the_comparison_limit_is_explained(): void
    {
        $halaman = $this->sumber();
        $kartu = $this->sumber('js/components/trails/banding/KartuSimpanan.tsx');

        $this->assertStringContainsString('umumkan(`Maksimal ${batasBanding}. Lepas satu dulu.`);', $halaman);
        $this->assertStringContainsString('setPesan((lama) => (lama === teks ? teks + String.fromCharCode(0xa0) : teks));', $halaman);
        $this->assertStringContainsString('useEffect(() => setPesan(null), [kunciPilihan]);', $halaman);
        $this->assertStringContainsString('<AksiPilihan dipilih={pilihanBerlaku} maks={batasBanding} tersedia={tersedia} pesan={pesan} />', $halaman);
        $this->assertStringContainsString('<legend className="sr-only">Pilih sampai {batasBanding} jalur untuk dibandingkan</legend>', $halaman);
        $this->assertStringContainsString('<KotakCentang', $kartu);
        $this->assertStringContainsString('label={jalur.name}', $kartu);
        $this->assertStringNotContainsString('<Checkbox', $kartu);
    }

    /**
     * Anggaran kueri (keputusan koordinator W3 nomor 12): penilaian kecocokan seluruh simpanan
     * batch, jadi jumlah kueri tidak tumbuh bersama jumlah simpanan.
     */
    public function test_the_page_does_not_grow_with_the_number_of_saved_trails(): void
    {
        $user = User::factory()->create();
        $user->profile()->create(['experience_level' => 'INTERMEDIATE', 'completed_at' => now()]);
        $user = $user->fresh();
        app(ConsiderationService::class)->toggle($user, $this->jalur());
        app(ConsiderationService::class)->toggle($user, $this->jalur());

        $this->daftar($user);
        $sedikit = $this->hitungKueri($user);

        foreach (range(1, 10) as $i) {
            app(ConsiderationService::class)->toggle($user, $this->jalur());
        }

        $this->assertSame($sedikit, $this->hitungKueri($user));
    }

    private function hitungKueri(User $user): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->daftar($user);
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }
}
