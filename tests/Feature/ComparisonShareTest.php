<?php

namespace Tests\Feature;

use App\Models\ComparisonShare;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\User;
use App\Services\ConsiderationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Tautan bagikan perbandingan (FR-23, spec v3 bagian 9.3, PRD §148; W3 Tugas 8).
 *
 * Yang dijaga: token acak minimal 128 bit yang tidak pernah tersimpan (hanya hash-nya);
 * tautan kedaluwarsa, dicabut, atau salah tidak dapat dibuka dan dijawab sama; hanya
 * pemiliknya yang dapat mencabut; halaman publik tidak diindeks, tidak membawa catatan
 * pribadi, label kecocokan, atau nama pembuatnya; pembuatan dan pembukaan dibatasi laju.
 */
class ComparisonShareTest extends TestCase
{
    use RefreshDatabase;

    private function jalur(string $nama): Trail
    {
        return Trail::factory()->for(Mountain::factory()->create(['name' => 'Gunung '.$nama]))->published()->create(['name' => $nama]);
    }

    private function pendakiBerprofil(): User
    {
        $user = User::factory()->create(['name' => 'Pembuat Rahasia']);
        $user->profile()->create(['experience_level' => 'INTERMEDIATE', 'completed_at' => now()]);

        return $user->fresh();
    }

    /**
     * @param  array<int, int>  $ids
     * @return array{0: string, 1: TestResponse}
     */
    private function buat(User $user, array $ids): array
    {
        $respons = $this->actingAs($user)->from(route('trails.saved'))->post(route('shares.store'), ['trails' => $ids]);
        $url = session('tautan-bagikan')['url'] ?? '';

        return [$url, $respons];
    }

    public function test_the_token_is_long_random_and_only_its_hash_is_stored(): void
    {
        $user = $this->pendakiBerprofil();
        $a = $this->jalur('Jalur A');
        $b = $this->jalur('Jalur B');

        [$url, $respons] = $this->buat($user, [$a->id, $b->id]);

        $respons->assertRedirect(route('trails.saved'))->assertSessionHas('pengumuman', 'Tautan bagikan dibuat.');
        $this->assertMatchesRegularExpression('#/b/[A-Za-z0-9_-]{32}$#', $url);

        $token = basename($url);
        $baris = ComparisonShare::sole();

        $this->assertGreaterThanOrEqual(128, strlen(base64_decode(strtr($token, '-_', '+/'))) * 8, 'Token harus paling sedikit 128 bit.');
        $this->assertSame(hash('sha256', $token), $baris->token_hash);
        $this->assertStringNotContainsString($token, json_encode($baris->getAttributes()));
        $this->assertSame([$a->id, $b->id], $baris->trail_ids);
        $this->assertEqualsWithDelta(now()->addDays(7)->timestamp, $baris->expires_at->timestamp, 5);
    }

    /**
     * PRD §148: halaman publik hanya memuat data publik jalur beserta sumber dan waktunya;
     * tanpa catatan pribadi, tanpa label kecocokan, tanpa nama atau email pembuatnya, dan
     * tidak diindeks.
     */
    public function test_the_public_page_is_read_only_unindexed_and_carries_nothing_private(): void
    {
        $user = $this->pendakiBerprofil();
        $a = $this->jalur('Jalur A');
        $b = $this->jalur('Jalur B');
        app(ConsiderationService::class)->toggle($user, $a);
        TrailConsideration::where('trail_id', $a->id)->first()->forceFill(['note' => 'Catatan rahasia saya'])->save();
        [$url] = $this->buat($user, [$b->id, $a->id]);
        auth()->logout();

        $respons = $this->get($url)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        // m8 tinjauan akhir W3: tamu juga menerima bandingUrl (bukan null seperti sebelumnya).
        // "Masuk" menuju perbandingan itu; middleware auth menyimpan intended URL, jadi sesudah
        // masuk pembaca kembali ke perbandingannya, bukan ke dasbor. Tautan itu hanya berisi id
        // jalur terbit yang sudah tampil di halaman ini, bukan data pembuatnya.
        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Public/SharedComparison')
            ->where('berlaku', true)
            ->where('jalur.0.nama', 'Jalur B')
            ->where('jalur.1.nama', 'Jalur A')
            ->has('jalur.0.status.label')
            ->where('tamu', true)
            ->where('bandingUrl', route('trails.compare', ['trails' => $b->id.','.$a->id]))
            ->has('baris'));
        $this->get(route('trails.compare', ['trails' => $b->id.','.$a->id]))->assertRedirect(route('login'));
        $this->assertSame(route('trails.compare', ['trails' => $b->id.','.$a->id]), session('url.intended'));
        $this->assertMatchesRegularExpression('/<Button variant="secondary" href=\{props\.bandingUrl\}>\s*Masuk\s*<\/Button>/', File::get(resource_path('js/pages/Public/SharedComparison.tsx')));

        $isi = $respons->getContent();

        foreach (['Catatan rahasia saya', 'Pembuat Rahasia', $user->email, '"fit"', 'labelText', 'COCOK', 'KURANG_COCOK', 'PERLU_PERSIAPAN'] as $terlarang) {
            $this->assertStringNotContainsString($terlarang, $isi, "Halaman publik membawa {$terlarang}.");
        }

        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow" />', File::get(resource_path('js/pages/Public/SharedComparison.tsx')));
    }

    /**
     * R-012: halaman publik adalah permukaan pertama yang menampilkan status resmi kepada
     * pembaca tanpa akun, jadi kalimat OPEN bukan jaminan keselamatan wajib ada di sini juga.
     */
    public function test_the_public_page_states_that_official_status_is_not_a_safety_guarantee(): void
    {
        $this->assertStringContainsString(
            'Status resmi menjelaskan ketentuan pengelola, bukan jaminan keselamatan.',
            File::get(resource_path('js/pages/Public/SharedComparison.tsx')),
        );
    }

    /**
     * Membuka tautan tidak pernah menulis apa pun: tidak ke simpanan pembacanya, tidak ke
     * tautannya sendiri.
     */
    public function test_opening_a_link_writes_nothing(): void
    {
        [$url] = $this->buat($this->pendakiBerprofil(), [$this->jalur('A')->id, $this->jalur('B')->id]);
        $pembaca = User::factory()->create();
        $sebelum = ComparisonShare::sole()->updated_at;

        $this->actingAs($pembaca)->get($url)->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('bandingUrl', fn ($u) => str_contains((string) $u, '/trails/compare')));

        $this->assertSame(0, TrailConsideration::where('user_id', $pembaca->id)->count());
        $this->assertEquals($sebelum, ComparisonShare::sole()->updated_at);
    }

    /**
     * Kedaluwarsa, dicabut, dan tidak dikenal dijawab sama (404, tidak diindeks), dengan
     * kalimat cara memperoleh tautan baru.
     */
    public function test_an_expired_revoked_or_unknown_link_cannot_be_opened(): void
    {
        $user = $this->pendakiBerprofil();
        [$kedaluwarsa] = $this->buat($user, [$this->jalur('A')->id, $this->jalur('B')->id]);
        [$dicabut] = $this->buat($user, [$this->jalur('C')->id, $this->jalur('D')->id]);
        auth()->logout();

        ComparisonShare::query()->oldest('id')->first()->forceFill(['expires_at' => now()->subMinute()])->save();
        ComparisonShare::query()->latest('id')->first()->forceFill(['revoked_at' => now()])->save();

        foreach ([$kedaluwarsa, $dicabut, url('/b/'.str_repeat('A', 32))] as $alamat) {
            $this->get($alamat)->assertNotFound()->assertHeader('X-Robots-Tag', 'noindex, nofollow')
                ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/SharedComparison')->where('berlaku', false));
        }

        $this->get('/b/pendek')->assertNotFound();
        $this->assertStringContainsString('Minta tautan baru dari orang yang membagikannya.', File::get(resource_path('js/pages/Public/SharedComparison.tsx')));
    }

    public function test_only_the_owner_can_revoke_and_others_get_not_found(): void
    {
        $pemilik = $this->pendakiBerprofil();
        $this->buat($pemilik, [$this->jalur('A')->id, $this->jalur('B')->id]);
        $share = ComparisonShare::sole();

        $this->actingAs(User::factory()->create())->delete(route('shares.destroy', $share->id))->assertNotFound();
        $this->assertNull($share->fresh()->revoked_at);

        $this->actingAs($pemilik)->from(route('trails.saved'))->delete(route('shares.destroy', $share->id))->assertRedirect(route('trails.saved'));
        $this->assertNotNull($share->fresh()->revoked_at);
    }

    /**
     * R-022: hanya jalur terbit; dua sampai batas banding; pemilik dari sesi, bukan dari
     * badan permintaan.
     */
    public function test_a_link_holds_two_to_three_published_trails_of_the_signed_in_owner(): void
    {
        $user = $this->pendakiBerprofil();
        $lain = User::factory()->create();
        $draf = Trail::factory()->for(Mountain::factory())->unpublished()->create();
        $a = $this->jalur('A');
        $b = $this->jalur('B');

        $this->actingAs($user)->post(route('shares.store'), ['trails' => [$a->id]])->assertSessionHasErrors('trails');
        $this->actingAs($user)->post(route('shares.store'), ['trails' => [$a->id, $b->id, $this->jalur('C')->id, $this->jalur('D')->id]])->assertSessionHasErrors('trails');
        $this->actingAs($user)->post(route('shares.store'), ['trails' => [$a->id, $draf->id]])->assertSessionHasErrors('trails');
        $this->actingAs($user)->post(route('shares.store'), ['trails' => [$a->id, $a->id]])->assertSessionHasErrors('trails.0');
        $this->assertSame(0, ComparisonShare::count());

        $this->actingAs($user)->post(route('shares.store'), ['trails' => [$a->id, $b->id], 'user_id' => $lain->id])->assertSessionHasNoErrors();
        $this->assertSame($user->id, ComparisonShare::sole()->user_id);
    }

    /**
     * R-022: jalur yang ditarik dari terbit atau diarsipkan sesudah dibagikan tidak boleh
     * bocor ke halaman publik (ruling W3-8c).
     */
    public function test_trails_withdrawn_from_publication_after_sharing_do_not_leak_to_the_public_page(): void
    {
        $user = $this->pendakiBerprofil();
        $tetap = $this->jalur('Tetap Terbit');
        $ditarik = $this->jalur('Ditarik Terbit');
        $diarsipkan = $this->jalur('Diarsipkan');
        [$url] = $this->buat($user, [$tetap->id, $ditarik->id, $diarsipkan->id]);

        $ditarik->forceFill(['is_published' => false])->save();
        $diarsipkan->forceFill(['archived_at' => now()])->save();
        auth()->logout();

        $respons = $this->get($url)->assertOk();

        $respons->assertInertia(fn (AssertableInertia $page) => $page
            ->where('berlaku', true)
            ->has('jalur', 1)
            ->where('jalur.0.nama', 'Tetap Terbit')
            ->where('ditarik', 2));

        $isi = $respons->getContent();

        foreach (['Ditarik Terbit', 'Diarsipkan'] as $nama) {
            $this->assertStringNotContainsString($nama, $isi, "Halaman publik membawa nama jalur yang sudah tidak terbit: {$nama}.");
        }
    }

    public function test_a_guest_cannot_create_or_revoke(): void
    {
        $this->post(route('shares.store'), ['trails' => [1, 2]])->assertRedirect(route('login'));
        $this->delete(route('shares.destroy', 1))->assertRedirect(route('login'));
    }

    public function test_active_links_per_account_are_capped(): void
    {
        config()->set('hiking.share_links.max_active_per_user', 1);
        $user = $this->pendakiBerprofil();
        $a = $this->jalur('A');
        $b = $this->jalur('B');

        $this->buat($user, [$a->id, $b->id]);
        [, $respons] = $this->buat($user, [$b->id, $a->id]);

        $respons->assertSessionHasErrors(['trails' => 'Tautan aktif Anda sudah 1. Cabut satu dulu sebelum membuat yang baru.']);
        $this->assertSame(1, ComparisonShare::count());
    }

    public function test_opening_links_is_rate_limited_per_address(): void
    {
        [$url] = $this->buat($this->pendakiBerprofil(), [$this->jalur('A')->id, $this->jalur('B')->id]);
        auth()->logout();
        $batas = (int) config('hiking.share_links.views_per_minute');

        foreach (range(1, $batas) as $i) {
            $this->get($url)->assertOk();
        }

        $this->get($url)->assertStatus(429);
    }

    /**
     * Pemiliknya dapat mencabut kapan saja: Simpanan membawa tautan yang masih berlaku, tanpa
     * token (yang tidak tersimpan), dan tombol Bagikan baru ada bila ada yang dapat dibagikan
     * atau dicabut (DK-40).
     */
    public function test_the_saved_page_lists_active_links_and_offers_sharing_only_when_useful(): void
    {
        $user = $this->pendakiBerprofil();
        $a = $this->jalur('A');
        $b = $this->jalur('B');
        app(ConsiderationService::class)->toggle($user, $a);
        app(ConsiderationService::class)->toggle($user, $b);
        $this->buat($user, [$a->id, $b->id]);

        $this->actingAs($user)->get(route('trails.saved'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('tautanAktif', 1)
            ->where('tautanAktif.0.jumlahJalur', 2)
            ->missing('tautanAktif.0.url'));

        $halaman = File::get(resource_path('js/pages/Trails/Saved.tsx'));
        $this->assertStringContainsString('tersedia >= 2 || tautanAktif.length > 0 ? (', $halaman);
        $this->assertStringContainsString('onNetworkError', File::get(resource_path('js/components/trails/banding/LembarBagikan.tsx')));
    }

    /**
     * Dua tautan dari hari yang sama tidak berbagi nama tombol Cabut bagi pembaca layar: waktu
     * dibuat memuat jam dan zonanya, dan nama aksesibel tombol memakai waktu itu. Daftar berurut
     * dari yang terbaru.
     *
     * Asersi sumber diubah ruling CF-2 (audit E2E N-3, tinjauan TA-m8): waktu dibuat hanya
     * beresolusi menit, jadi dua tautan yang dibuat dalam menit yang sama masih bernama sama.
     * Teks sr-only kini " tautan ke-{nomor}, dibuat {t.dibuat}", dengan nomor urut di daftar mulai
     * 1, yang selalu unik di satu lembar. Teks yang terlihat tidak berubah.
     */
    public function test_two_links_from_the_same_day_are_told_apart_by_their_time(): void
    {
        $user = $this->pendakiBerprofil();
        $a = $this->jalur('A');
        $b = $this->jalur('B');
        app(ConsiderationService::class)->toggle($user, $a);
        app(ConsiderationService::class)->toggle($user, $b);

        $this->travelTo(Carbon::parse('2026-10-11 01:05:00', 'UTC'));
        $this->buat($user, [$a->id, $b->id]);
        $this->travelTo(Carbon::parse('2026-10-11 14:30:00', 'UTC'));
        $this->buat($user, [$a->id, $b->id]);

        $this->actingAs($user)->get(route('trails.saved'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('tautanAktif', 2)
            ->where('tautanAktif.0.dibuat', '11 Okt 21.30 WIB')
            ->where('tautanAktif.1.dibuat', '11 Okt 08.05 WIB'));
        $lembar = File::get(resource_path('js/components/trails/banding/LembarBagikan.tsx'));
        $this->assertStringContainsString('{tautanAktif.map((t, urutan) => (', $lembar);
        $this->assertStringContainsString('Cabut<span className="sr-only"> tautan ke-{urutan + 1}, dibuat {t.dibuat}</span>', $lembar);
        $this->assertStringContainsString('{t.jumlahJalur} jalur, dibuat {t.dibuat}, berlaku sampai {t.berlakuSampai}', $lembar, 'Teks yang terlihat tidak berubah.');
    }

    /**
     * T8-M3 tinjauan akhir W3: token tautan ada di URL, jadi halaman publik (berlaku maupun
     * tidak) mengirim Referrer-Policy no-referrer; tanpa itu strict-origin-when-cross-origin
     * membawa token lengkap ke log sesama asal. Halaman lain tetap memakai kebijakan bawaan.
     */
    public function test_the_public_page_never_leaks_its_token_through_the_referrer(): void
    {
        [$url] = $this->buat($this->pendakiBerprofil(), [$this->jalur('A')->id, $this->jalur('B')->id]);
        auth()->logout();

        $this->get($url)->assertOk()->assertHeader('Referrer-Policy', 'no-referrer');
        $this->get('/b/'.str_repeat('A', 32))->assertNotFound()->assertHeader('Referrer-Policy', 'no-referrer');
        $this->get(route('welcome'))->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    /**
     * T8-M5 tinjauan akhir W3: id tautan yang melampaui bilangan bulat PHP dijawab 404, bukan
     * TypeError 500 di parameter `int $share`.
     */
    public function test_an_oversized_share_id_is_not_found(): void
    {
        $this->actingAs(User::factory()->create())->delete('/trails/shares/'.str_repeat('9', 20))->assertNotFound();
    }

    /**
     * T8-M6 tinjauan akhir W3 (R-013): waktu di baris sumber data jalur adalah waktu
     * verifikasi sumbernya, bukan trails.updated_at, yang berubah oleh suntingan apa pun.
     * Tanpa waktu verifikasi tidak ada waktu yang ditulis.
     */
    public function test_the_public_page_dates_the_source_by_its_verification(): void
    {
        $a = $this->jalur('A');
        $b = $this->jalur('B');
        $a->dataSource->forceFill(['verified_at' => '2026-03-10 05:00:00'])->save();
        $b->dataSource->forceFill(['verified_at' => null])->save();
        [$url] = $this->buat($this->pendakiBerprofil(), [$a->id, $b->id]);
        auth()->logout();

        $this->get($url)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('jalur.0.diverifikasi', '10 Mar 2026')
            ->where('jalur.1.diverifikasi', null)
            ->missing('jalur.0.diperbarui'));

        $halaman = File::get(resource_path('js/pages/Public/SharedComparison.tsx'));
        $this->assertStringContainsString('{j.diverifikasi ? `, diverifikasi ${j.diverifikasi}` : null}', $halaman);
        $this->assertStringNotContainsString('diperbarui', $halaman);
    }

    /**
     * T8-M7 tinjauan akhir W3: masa berlaku tautan dikirim dari config, tidak ditulis keras
     * "7 hari" di lembar bagikan dan di halaman tautan yang tidak berlaku.
     */
    public function test_the_link_lifetime_comes_from_config(): void
    {
        config()->set('hiking.share_links.ttl_days', 5);
        $user = $this->pendakiBerprofil();
        $a = $this->jalur('A');
        $b = $this->jalur('B');
        app(ConsiderationService::class)->toggle($user, $a);
        app(ConsiderationService::class)->toggle($user, $b);

        $this->actingAs($user)->get(route('trails.saved'))->assertInertia(fn (AssertableInertia $page) => $page->where('hariBerlakuTautan', 5));
        $this->actingAs($user)->get(route('trails.compare'))->assertInertia(fn (AssertableInertia $page) => $page->where('hariBerlakuTautan', 5));
        auth()->logout();
        $this->get('/b/'.str_repeat('A', 32))->assertInertia(fn (AssertableInertia $page) => $page->where('hariBerlaku', 5));

        foreach (['js/components/trails/banding/LembarBagikan.tsx', 'js/pages/Public/SharedComparison.tsx'] as $berkas) {
            $this->assertStringNotContainsString('7 hari', File::get(resource_path($berkas)), "{$berkas} menulis masa berlaku keras.");
        }
    }

    /**
     * T8-M12 tinjauan akhir W3: membuat dan mencabut tautan dibatasi laju per pengguna.
     */
    public function test_creating_and_revoking_links_are_rate_limited(): void
    {
        $user = $this->pendakiBerprofil();
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        foreach (range(1, $batas) as $i) {
            $this->actingAs($user)->post(route('shares.store'), ['trails' => []])->assertRedirect();
        }

        $this->actingAs($user)->post(route('shares.store'), ['trails' => []])->assertStatus(429);
        $this->actingAs($user)->delete(route('shares.destroy', 1))->assertStatus(429);
    }

    /**
     * T8-M13, T8-M14, dan T9-M9 tinjauan akhir W3: URL tautan baru terlihat sebagai teks di
     * samping tombol salin (bukan hanya bila papan klip gagal); hitungan batas tautan aktif
     * memakai COUNT, bukan memuat setiap baris; dan daftar tautan aktif Simpanan dan Banding
     * dibentuk satu metode.
     */
    public function test_the_new_link_is_visible_the_cap_counts_in_sql_and_one_method_lists_active_links(): void
    {
        $lembar = File::get(resource_path('js/components/trails/banding/LembarBagikan.tsx'));
        $this->assertMatchesRegularExpression('/<p[^>]*select-all[^>]*>\{baru\.url\}<\/p>/', $lembar);

        $layanan = File::get(app_path('Services/Trails/TautanBagikan.php'));
        $this->assertStringContainsString('->berlaku()->count() >=', $layanan);
        $this->assertStringContainsString('public function ringkasAktif(User $user): array', $layanan);

        foreach (['SavedRoutesController', 'RouteComparisonController'] as $kelas) {
            $this->assertStringContainsString("'tautanAktif' => \$tautan->ringkasAktif(", File::get(app_path("Http/Controllers/Trails/{$kelas}.php")));
        }
    }

    /**
     * Kebijakan data pribadi bagian 3: data tanpa dasar retensi dihapus bersama akunnya. Tautan
     * bagikan menunjuk pilihan pemilik akun, jadi ikut hilang, dan tautannya tidak lagi
     * terbuka.
     */
    public function test_deleting_the_account_removes_its_links(): void
    {
        $user = $this->pendakiBerprofil();
        [$url] = $this->buat($user, [$this->jalur('A')->id, $this->jalur('B')->id]);

        $user->delete();
        auth()->logout();

        $this->assertSame(0, ComparisonShare::count());
        $this->get($url)->assertNotFound();
    }

    /**
     * Anggaran kueri halaman publik (keputusan koordinator W3 nomor 12): tautan berisi tiga
     * jalur tidak lebih mahal daripada tautan berisi dua. Setiap jalur membawa satu catatan
     * status, yaitu catatan UNKNOWN bawaan factory published() (T8-M11 tinjauan akhir W3:
     * docblock lama menyebut status resmi yang tidak dibuat test ini sendiri).
     */
    public function test_the_public_page_does_not_grow_with_the_number_of_trails(): void
    {
        $user = $this->pendakiBerprofil();
        [$dua] = $this->buat($user, [$this->jalur('Jalur A')->id, $this->jalur('Jalur B')->id]);
        [$tiga] = $this->buat($user, [$this->jalur('Jalur C')->id, $this->jalur('Jalur D')->id, $this->jalur('Jalur E')->id]);
        auth()->logout();

        $this->get($dua)->assertOk();
        $sedikit = $this->hitungKueri($dua);
        $banyak = $this->hitungKueri($tiga);

        $this->assertSame($sedikit, $banyak, "Beban tumbuh: {$sedikit} kueri lalu {$banyak}.");
    }

    private function hitungKueri(string $url): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get($url)->assertOk();
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }
}
