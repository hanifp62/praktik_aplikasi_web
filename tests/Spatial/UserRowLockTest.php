<?php

namespace Tests\Spatial;

use App\Enums\ConsiderationOutcome;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\ConsiderationService;
use App\Services\Trails\TautanBagikan;
use App\Services\Trips\TripLifecycle;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Batas per pendaki yang diperiksa lalu ditulis mengunci baris pengguna lebih dulu (titipan W3
 * dan W6 untuk W9; R-024): simpanan (batas simpanan, W3), tautan bagikan (batas tautan aktif,
 * W3), dan satu pendakian berlangsung per pendaki (W6-R2).
 *
 * Komentar di layanannya sendiri mengakui suite SQLite tidak membuktikan kunci itu. Di sini
 * koneksi kedua memegang `FOR UPDATE` atas baris pengguna: selama dipegang, setiap operasi harus
 * menunggu di kueri `for update` atas users lalu gagal sesudah lock_timeout tanpa menulis
 * apa pun; sesudah dilepas, operasinya berhasil. Trip dibuat sebelum kunci dipegang, karena
 * menyisipkan baris yang menunjuk pengguna terkunci ikut menunggu (FOR KEY SHARE) dan akan
 * mengaburkan kueri mana yang menunggu. Dilewati tanpa SPATIAL_TEST_DSN.
 */
class UserRowLockTest extends SpatialTestCase
{
    /**
     * @return array<int, string>
     */
    protected function connectionsToTransact(): array
    {
        return [];
    }

    /**
     * @return array<string, array{0: Closure(User, Trail, TripPlan): bool}>
     */
    public static function operasiBerbatas(): array
    {
        return [
            'simpan dari kartu' => [fn (User $user, Trail $trail): bool => app(ConsiderationService::class)->simpan($user, $trail) === ConsiderationOutcome::DITAMBAHKAN],
            'tombol simpan bolak-balik' => [fn (User $user, Trail $trail): bool => app(ConsiderationService::class)->toggle($user, $trail) === ConsiderationOutcome::DITAMBAHKAN],
            'tautan bagikan' => [fn (User $user, Trail $trail): bool => app(TautanBagikan::class)->buat($user, new Collection([$trail])) !== null],
            'mulai pendakian' => [fn (User $user, Trail $trail, TripPlan $trip): bool => app(TripLifecycle::class)->start($trip) === null],
        ];
    }

    /**
     * @param  Closure(User, Trail, TripPlan): bool  $operasi
     */
    #[DataProvider('operasiBerbatas')]
    public function test_a_per_hiker_limit_waits_for_the_hiker_row_lock(Closure $operasi): void
    {
        $user = User::factory()->create();
        $trail = Trail::factory()->for(Mountain::factory()->create())->create();
        $trip = TripPlan::factory()->for($user)->create(['trail_id' => $trail->id]);

        config(['database.connections.pgsql_kedua' => config('database.connections.pgsql')]);
        $kedua = DB::connection('pgsql_kedua');

        try {
            $kedua->beginTransaction();
            $kedua->select('select id from users where id = ? for update', [$user->id]);
            DB::statement("set lock_timeout = '500ms'");

            try {
                $operasi($user, $trail, $trip->fresh());
                $this->fail('Operasi berbatas seharusnya menunggu kunci baris pengguna yang dipegang permintaan lain.');
            } catch (QueryException $galat) {
                $this->assertSame('55P03', (string) $galat->getCode());
                $this->assertStringContainsString('for update', strtolower($galat->getSql()));
                $this->assertStringContainsString('users', $galat->getSql());
            } finally {
                DB::statement('set lock_timeout = 0');
                $kedua->rollBack();
            }

            $this->assertTrue($operasi($user->fresh(), $trail, $trip->fresh()), 'Sesudah kunci dilepas, operasinya berjalan.');
        } finally {
            DB::disconnect('pgsql_kedua');
            DB::table('analytics_events')->where('user_id', $user->id)->delete();
            User::query()->whereKey($user->id)->delete();
            Mountain::query()->whereKey($trail->mountain_id)->delete();
        }
    }
}
