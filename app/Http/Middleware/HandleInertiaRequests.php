<?php

namespace App\Http\Middleware;

use App\Services\ConsiderationService;
use App\Services\DataFreshnessService;
use App\Services\Trips\DaftarPengingat;
use App\Services\Trips\PendakianAktif;
use App\Support\DisplayPreference;
use App\Support\KunciPerangkat;
use App\Support\PenjagaDataContoh;
use App\Support\ThemePreference;
use Illuminate\Http\Request;
use Inertia\Inertia;
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

            // Penyimpanan perangkat per pengguna (W6, ruling W6-R10): kunci basis data paket
            // lapangan dan perekam jejak, serta sakelar paket (Q10, Q14). Null untuk tamu.
            'perangkat' => $user === null ? null : [
                'kunci' => KunciPerangkat::untuk($user),
                'paket' => (bool) config('hiking.field_pack.enabled'),
            ],

            // Hitungan Simpanan di header layar lebar dan kepala Jelajah (spec v3 bagian 5.2):
            // satu kueri terindeks, malas, sehingga muat ulang parsial yang tidak memintanya
            // tidak membayarnya. Null untuk tamu dan di mode lapangan, yang tidak menggambar
            // kerangka (perbaikan akhir W2, N-1).
            'jumlahSimpanan' => fn () => $user === null || $request->routeIs('trips.hike') ? null : app(ConsiderationService::class)->jumlahSimpanan($user),

            // Strip pendakian aktif di atas bilah tab (spec v3 bagian 5.1): satu kueri, malas.
            // Null untuk tamu dan di mode lapangan itu sendiri, tempat strip tidak digambar.
            'pendakianAktif' => fn () => $user === null || $request->routeIs('trips.hike') ? null : app(PendakianAktif::class)->untuk($user),

            // Isi lembar Pengingat (spec v3 bagian 5.1): opsional, jadi tidak pernah ikut
            // kunjungan biasa. Lembar memintanya dengan muat ulang parsial saat dibuka.
            'daftarPengingat' => Inertia::optional(fn () => $user === null ? null : app(DaftarPengingat::class)->untuk($user)),

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
                // Kabar yang datang bersama perpindahan halaman, dibaca halaman tujuannya di
                // wilayah dekat kepala halaman.
                'status' => fn () => $request->session()->get('status'),
                // Kabar aksi di halaman yang sama (Perbarui daftar, sakelar pengingat di
                // Persiapan), dibaca di wilayah dekat aksinya (W4-FK N1).
                'kabarAksi' => fn () => $request->session()->get('kabar-aksi'),
                'timbanganPenuh' => fn () => $request->session()->get('timbangan-penuh'),
                // Jalur yang baru dihapus dari simpanan beserta catatannya, satu kali dan hanya
                // di sesi pemiliknya: toast Urungkan memulihkannya utuh (W3).
                'simpananTerhapus' => fn () => $request->session()->get('simpanan-terhapus'),
                // Baris sudah ada sebelum store() ini berjalan (tab atau perangkat lain):
                // klien tidak menawarkan Urungkan-sebagai-hapus untuk baris yang tidak
                // dibuatnya (tinjauan Tugas 4 temuan 2; ruling W3-4b). Berkunci id jalur, supaya
                // tombol jalur lain tidak membacanya (pola W4-4e).
                'simpananSudahAda' => fn () => $request->session()->get('simpanan-sudah-ada'),
                // Tautan bagikan yang baru dibuat: tokennya hanya ditunjukkan sekali ini (FR-23).
                'tautanBagikan' => fn () => $request->session()->get('tautan-bagikan'),
                // Butir persiapan yang baru diubah: id-nya, status sebelumnya, dan apakah
                // statusnya benar-benar berubah. Toast Urungkan di Preparation.tsx memulihkan
                // status yang tersimpan, bukan pandangan klien, dan tidak ditawarkan bila
                // tidak ada yang berubah (pola W3-4b). Id-nya ada supaya klien dapat menolak
                // payload yang bukan milik butir yang baru dikirimnya; satu kunci, bukan dua
                // kunci lepas, karena dua kunci lepas tidak punya cara menyebut satu sama lain
                // milik butir yang sama (tinjauan Tugas 4 I1b dan putaran 2).
                'butirDiubah' => fn () => $request->session()->get('butir-diubah'),
            ],
        ];
    }
}
