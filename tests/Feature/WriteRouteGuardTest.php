<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RuteLaravel;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Tinjauan keamanan W9 (CLAUDE.md bagian 4: auth bypass dan rate limiting) atas setiap rute
 * tulis, bukan hanya rute satu gelombang.
 *
 * RouteAccessMatrixTest menyapu rute GET; PersonalDataWriteGuardTest menyapu rute tulis W7.
 * Sapuan ini menutup sisanya: setiap rute POST, PUT, PATCH, dan DELETE butuh sesi kecuali pintu
 * masuk akun, dan setiap rute tulis dibatasi laju kecuali yang tercatat beserta alasannya.
 */
class WriteRouteGuardTest extends TestCase
{
    use RefreshDatabase;

    /** Rute tulis yang memang dibuka tanpa sesi. */
    private const PINTU = [
        'login.store' => 'masuk',
        'register.store' => 'daftar',
        'password.email' => 'meminta tautan atur ulang sandi',
        'password.store' => 'mengatur ulang sandi dengan token',
        'tema.simpan' => 'cookie tema, bukan data akun',
        'tampilan.simpan' => 'cookie tampilan, bukan data akun',
    ];

    /**
     * Rute paket pihak ketiga. Livewire masih terpasang walau tidak ada komponennya lagi;
     * menghapus paketnya butuh izin dependensi (Konflik W9). Titik akhirnya hanya menerima
     * snapshot komponen bertanda checksum dan unggahan ber-URL bertanda tangan.
     */
    private const PAKET = [
        'default.livewire.update' => 'Livewire, tanpa komponen; menunggu keputusan penghapusan paket',
        'livewire.upload-file' => 'Livewire, butuh URL bertanda tangan, sudah dibatasi 60 per menit',
    ];

    /** Rute tulis tanpa middleware throttle, dengan alasannya. */
    private const TANPA_THROTTLE = [
        'login.store' => 'LoginRequest::ensureIsNotRateLimited(): lima percobaan per email dan alamat',
        'logout' => 'mengakhiri sesi sendiri; mengulangnya tidak menulis apa pun',
        'reports.store' => 'batas per jam di ConditionReportController (RateLimitTest)',
        'goals.store' => 'batas per jam di GoalController (RateLimitTest)',
    ];

    /**
     * Konsol pengelola: admin, moderator, kurator, dan pemegang kredensial ahli, peran tepercaya
     * yang setiap aksinya dijaga middleware peran atau Policy.
     */
    private const AWALAN_KONSOL = ['admin.', 'moderation.', 'curator.', 'trails.geometry.', 'trails.checkpoints.', 'trails.segments.', 'contribute.'];

    public function test_every_write_route_needs_a_session_unless_it_opens_one(): void
    {
        $terbuka = [];

        foreach ($this->ruteTulis() as $nama => $rute) {
            if (! isset(self::PINTU[$nama]) && ! isset(self::PAKET[$nama]) && ! in_array('auth', $rute->gatherMiddleware(), true)) {
                $terbuka[] = $nama;
            }
        }

        $this->assertSame([], $terbuka, 'Rute tulis tanpa sesi: '.implode(', ', $terbuka));
    }

    public function test_every_write_route_is_rate_limited_or_says_why_not(): void
    {
        $tanpaBatas = [];

        foreach ($this->ruteTulis() as $nama => $rute) {
            $berbatas = collect($rute->gatherMiddleware())->contains(fn ($m) => is_string($m) && str_starts_with($m, 'throttle:'));

            if ($berbatas || isset(self::TANPA_THROTTLE[$nama]) || isset(self::PAKET[$nama]) || collect(self::AWALAN_KONSOL)->contains(fn (string $awalan) => str_starts_with($nama, $awalan))) {
                continue;
            }

            $tanpaBatas[] = $nama;
        }

        $this->assertSame([], $tanpaBatas, 'Rute tulis tanpa batas laju: '.implode(', ', $tanpaBatas));

        foreach (array_keys(self::TANPA_THROTTLE) as $nama) {
            $this->assertNotNull(Route::getRoutes()->getByName($nama), "Pengecualian basi: rute {$nama} sudah tidak ada.");
        }
    }

    public function test_registration_is_rate_limited_per_address(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('register.store'), [])->assertSessionHasErrors();
        }

        $this->post(route('register.store'), [])->assertStatus(429);
    }

    public function test_asking_for_a_password_reset_link_is_rate_limited_per_address(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('password.email'), ['email' => 'bukan-alamat'])->assertSessionHasErrors('email');
        }

        $this->post(route('password.email'), ['email' => 'bukan-alamat'])->assertStatus(429);
    }

    /**
     * Rute yang dibatasi W9 memakai pola W4 dan W7: batas tulisan pribadi per menit dengan
     * hitungan sendiri per kelompok, jadi menyunting profil tidak menghabiskan jatah unggah jejak.
     */
    public function test_each_newly_limited_hiker_route_counts_on_its_own_key(): void
    {
        $batas = (int) config('hiking.rate_limits.personal_writes_per_minute');

        foreach ([
            'profile.update' => 'profil',
            'profile.password' => 'sandi',
            'profile.preference' => 'profil',
            'profile.destroy' => 'hapus-akun',
            'onboarding.store' => 'onboarding',
            'recommendations.pilih' => 'pilih-rekomendasi',
            'hike.track' => 'jejak',
            'hike.track.destroy' => 'jejak',
        ] as $nama => $kunci) {
            $this->assertContains("throttle:{$batas},1,{$kunci}", Route::getRoutes()->getByName($nama)->gatherMiddleware(), $nama);
        }
    }

    /**
     * Tautan bagikan publik dihitung per alamat dengan awalan sendiri (N5 tinjauan ulang W3), jadi
     * pembaca tautan tidak menghabiskan jatah rute publik lain dari alamat yang sama.
     */
    public function test_the_public_share_link_counts_on_its_own_key(): void
    {
        $batas = (int) config('hiking.share_links.views_per_minute');

        $this->assertContains("throttle:{$batas},1,tautan-buka", Route::getRoutes()->getByName('shares.show')->gatherMiddleware());
    }

    /**
     * Disk lokal tidak lagi menyajikan berkas lewat storage/{path}: berkas pribadi hanya keluar
     * lewat rute yang memeriksa Policy (ReportPhotoAccessTest, EvidenceArchiveTest).
     */
    public function test_private_files_have_no_signed_url_route(): void
    {
        $this->assertFalse(Route::has('storage.local'));
        $this->assertFalse(Route::has('storage.local.upload'));
        $this->get('/storage/report-photos/contoh.jpg')->assertNotFound();
    }

    /**
     * @return array<string, RuteLaravel>
     */
    private function ruteTulis(): array
    {
        $hasil = [];

        foreach (Route::getRoutes() as $rute) {
            $nama = $rute->getName();

            if ($nama === null || array_intersect($rute->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) === [] || str_starts_with($rute->uri(), '_')) {
                continue;
            }

            $hasil[$nama] = $rute;
        }

        ksort($hasil);

        return $hasil;
    }
}
