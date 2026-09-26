<?php

namespace Tests\Feature;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Route as RuteLaravel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Controller as InertiaController;
use Inertia\Response as InertiaResponse;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use Tests\TestCase;

/**
 * Komponen Tautan memilih kunjungan Inertia atau muat penuh berdasarkan daftar
 * pengecualian resources/js/lib/bukanHalamanReact.json. Daftar itu dicocokkan di sini
 * dengan rute GET sungguhan, dua arah: rute yang tidak merender Inertia wajib tercakup
 * (kalau tidak, tautannya membuka jendela galat), dan halaman Inertia tidak boleh ikut
 * terkecualikan (kalau tidak, tautannya memuat ulang seluruh aplikasi).
 *
 * Jenis rute dibaca dari tipe kembalian aksinya. Aksi tanpa tipe kembalian dianggap
 * bukan Inertia, sehingga test ini sekaligus menuntut tipe kembalian yang eksplisit.
 */
class InertiaLinkRegistryTest extends TestCase
{
    /** Rute perkakas paket, tidak pernah ditautkan dari antarmuka. */
    private const PERKAKAS = ['/_inertia', '/livewire', '/_boost'];

    public function test_the_exclusion_list_matches_the_routes_that_do_not_render_inertia(): void
    {
        $pola = json_decode(File::get(resource_path('js/lib/bukanHalamanReact.json')), true, flags: JSON_THROW_ON_ERROR)['pola'];
        $dikecualikan = fn (string $path): bool => collect($pola)->contains(fn (string $p): bool => preg_match('#'.$p.'#', $path) === 1);

        $tidakTercakup = [];
        $salahDikecualikan = [];

        foreach (Route::getRoutes() as $rute) {
            $uri = '/'.ltrim($rute->uri(), '/');

            if (! in_array('GET', $rute->methods(), true) || Str::startsWith($uri, self::PERKAKAS)) {
                continue;
            }

            $path = preg_replace('/[{][^}]+[}]/', 'contoh', $uri);
            $jenis = $this->jenis($rute);

            if ($jenis === 'inertia' && $dikecualikan($path)) {
                $salahDikecualikan[] = $uri;
            }

            if ($jenis === 'lain' && ! $dikecualikan($path)) {
                $tidakTercakup[] = $uri;
            }
        }

        $this->assertSame([], $tidakTercakup, 'Rute bukan Inertia yang belum ada di bukanHalamanReact.json: '.implode(', ', $tidakTercakup));
        $this->assertSame([], $salahDikecualikan, 'Halaman Inertia yang ikut dikecualikan: '.implode(', ', $salahDikecualikan));
    }

    /**
     * @return 'inertia'|'alih'|'lain'
     */
    private function jenis(RuteLaravel $rute): string
    {
        $aksi = $rute->getAction('uses');

        if ($aksi instanceof Closure) {
            $tipe = (new ReflectionFunction($aksi))->getReturnType();
        } else {
            [$kelas, $metode] = Str::parseCallback($aksi, '__invoke');

            // Nama kanonis tanpa garis miring depan yang ditulis Route::inertia().
            if ((new ReflectionClass($kelas))->getName() === InertiaController::class) {
                return 'inertia';
            }

            $tipe = (new ReflectionMethod($kelas, $metode))->getReturnType();
        }

        $nama = match (true) {
            $tipe instanceof ReflectionNamedType => [$tipe->getName()],
            $tipe instanceof ReflectionUnionType => array_map(fn (ReflectionNamedType $t) => $t->getName(), $tipe->getTypes()),
            default => [],
        };

        return match (true) {
            in_array(InertiaResponse::class, $nama, true) => 'inertia',
            // Kunjungan Inertia mengikuti pengalihan ke halaman Inertia, jadi aman keduanya.
            $nama === [RedirectResponse::class] => 'alih',
            default => 'lain',
        };
    }
}
