<?php

namespace App\Http\Middleware;

use App\Services\DataFreshnessService;
use App\Support\DisplayPreference;
use App\Support\PenjagaDataContoh;
use App\Support\ThemePreference;
use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Data yang dibagikan ke setiap halaman React.
 *
 * Hanya yang dibutuhkan layout: identitas pengguna secukupnya untuk navigasi dan peran
 * untuk menampilkan tautan Moderasi/Admin. Peran di sini hanya menentukan APA YANG
 * DITAMPILKAN; otorisasi tetap di server (middleware role dan Policy) dan klien tidak
 * pernah dipercaya untuk keputusan akses.
 */
class HandleInertiaRequests extends Middleware
{
    /**
     * Templat akar halaman React. Bukan "app": nama itu milik layout Livewire yang masih
     * hidup selama migrasi, dan dua berkas bernama sama untuk dua dunia mengundang salah
     * sunting.
     *
     * @var string
     */
    protected $rootView = 'inertia';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'isModerator' => $user->isModerator(),
                    'isAdmin' => $user->isAdmin(),
                    'hasCompletedProfile' => $user->hasCompletedProfile(),
                ],
            ],

            'merek' => [
                'nama' => config('app.name'),
                'tagline' => config('brand.tagline'),
            ],

            // Data contoh (DK-35): layout memasang lencana "Data contoh" bila benar.
            // sedangDisajikan() membaca dua sumber (tinjauan W1-9a #4): APP_DEMO eksplisit
            // dan koneksi basis data default yang sedang benar-benar demo.
            'demo' => PenjagaDataContoh::sedangDisajikan(),

            'tema' => ThemePreference::current($request),
            'tampilan' => [
                'efek' => DisplayPreference::efek($request),
                'gerak' => DisplayPreference::gerak($request),
            ],

            // Konfigurasi peta dari config, bukan ditulis di komponen: penyedia tile
            // diganti operator lewat env, dan CSP (SecurityHeaders) membaca sumber yang
            // sama, jadi keduanya tidak bisa berbeda.
            'peta' => fn () => [
                'styleUrl' => config('hiking.map.style_url'),
                'rasterTiles' => array_values(array_filter((array) config('hiking.map.raster_tiles'))),
                'attribution' => config('hiking.map.attribution'),
                'maxZoom' => (int) config('hiking.map.max_zoom', 17),
            ],

            // Hanya untuk admin, dan malas: satu query agregat status yang kedaluwarsa,
            // dibawa ke setiap halaman admin (§95) tanpa membebani pengguna lain.
            'adminNav' => fn () => $user?->isAdmin()
                ? ['perluDitinjau' => app(DataFreshnessService::class)->reviewCount()]
                : null,

            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'timbanganPenuh' => fn () => $request->session()->get('timbangan-penuh'),
            ],
        ];
    }
}
