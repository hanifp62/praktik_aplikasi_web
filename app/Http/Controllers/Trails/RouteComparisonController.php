<?php

namespace App\Http\Controllers\Trails;

use App\Enums\RouteFitLabel;
use App\Http\Controllers\Controller;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Services\ConsiderationService;
use App\Services\Trails\RouteComparisonTable;
use App\Services\Trails\TautanBagikan;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Banding: dua sampai tiga jalur berdampingan, tanpa pemenang (FR-06, R-016, DK-05).
 *
 * Tanpa ?trails= yang dibandingkan simpanan terbaru yang masih terbit; dengan ?trails=
 * yang dibandingkan pilihan pembaca, dari centang di halaman Tersimpan, Banding, atau
 * Rekomendasi. Halaman ini hanya membaca: "hapus dari perbandingan" membuka perbandingan
 * tanpa kolom itu, dan menghapus dari simpanan terjadi di halaman Tersimpan.
 *
 * Tautan ?trails= lama yang pernah dibagikan tetap terbuka: jalurnya ditampilkan apa
 * adanya, dan yang terpotong atau tidak lagi terbit disebut, bukan dibuang diam-diam.
 * Berbagi ke orang tanpa akun lewat tautan bagikan FR-23 (ComparisonShareController); halaman
 * ini membawa tautan yang masih berlaku supaya pembuatnya dapat mencabutnya.
 */
class RouteComparisonController extends Controller
{
    /**
     * Id yang dibaca dari ?trails=. Tautan lama memuat paling banyak lima; batas ini
     * menahan tautan buatan tangan berisi ratusan id supaya tidak memaksa server menilai
     * kecocokan seluruh katalog dalam satu permintaan.
     */
    private const ID_DIBACA = 10;

    public function show(Request $request, ConsiderationService $consideration, RouteComparisonTable $tabel, TautanBagikan $tautan): Response
    {
        $user = $request->user();
        $maks = $consideration->batasBanding();
        $simpanan = $consideration->simpanan($user);
        $idTersimpan = $simpanan->pluck('trail_id')->map(fn ($id) => (int) $id)->all();
        $pesan = [];

        // Keberadaan kuncinya yang dibaca, bukan nilainya: ?trails= kosong adalah pilihan
        // kosong yang disengaja (kolom terakhir dihapus), bukan permintaan pilihan bawaan.
        if ($request->query->has('trails')) {
            [$dipilih, $pesan] = $this->dariTautan($request->query('trails'), $maks);
        } else {
            $dipilih = $consideration->pilihanBawaan($simpanan);
        }

        $susunan = $tabel->susun($dipilih, $user);
        $semuaId = $dipilih->pluck('id')->all();

        foreach ($susunan['jalur'] as $i => $kolom) {
            $susunan['jalur'][$i]['tersimpan'] = in_array($kolom['id'], $idTersimpan, true);
            $susunan['jalur'][$i]['hapus'] = $this->tautan(array_values(array_diff($semuaId, [$kolom['id']])));
        }

        return Inertia::render('Trails/Compare', [
            'title' => 'Bandingkan Jalur',
            'pilihan' => ['maks' => $maks, 'opsi' => $this->opsi($simpanan, $dipilih)],
            'jumlahTersimpan' => $simpanan->count(),
            'pesan' => $pesan,
            'kataBelumDinilai' => RouteFitLabel::UNRATED_TEXT,
            'tautanAktif' => $tautan->ringkasAktif($user),
            'hariBerlakuTautan' => $tautan->hariBerlaku(),
            'pengumuman' => session('pengumuman', ''),
        ] + $susunan);
    }

    /**
     * Pilihan dari URL, dalam urutan yang ditulis pembaca. Jalur yang tidak terbit tidak
     * ikut dan tidak disebut namanya (R-022); yang melewati batas banding disebut.
     *
     * @return array{0: EloquentCollection<int, Trail>, 1: array<int, string>}
     */
    private function dariTautan(mixed $nilai, int $maks): array
    {
        $diminta = $this->idDariTautan($nilai);

        if ($diminta === []) {
            return [new EloquentCollection, []];
        }

        $terbit = Trail::query()->published()->whereIn('id', $diminta)->with('mountain')->get()->keyBy('id');
        $ada = array_values(array_filter($diminta, fn (int $id) => $terbit->has($id)));
        $pesan = [];

        $hilang = count($diminta) - count($ada);

        if ($hilang > 0) {
            $pesan[] = $hilang.' jalur dari pilihan ini tidak lagi tersedia, jadi tidak ikut dibandingkan.';
        }

        if (count($ada) > $maks) {
            $lebih = array_map(fn (int $id) => $terbit->get($id)->name, array_slice($ada, $maks));
            $pesan[] = 'Perbandingan memuat paling banyak '.$maks.' jalur. Tidak ikut ditampilkan: '.implode(', ', $lebih).'.';
            $ada = array_slice($ada, 0, $maks);
        }

        return [new EloquentCollection(array_map(fn (int $id) => $terbit->get($id), $ada)), $pesan];
    }

    /**
     * @return array<int, int>
     */
    private function idDariTautan(mixed $nilai): array
    {
        if (! is_string($nilai)) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(
            array_map('intval', explode(',', $nilai)),
            fn (int $id) => $id > 0
        )));

        return array_slice($ids, 0, self::ID_DIBACA);
    }

    /**
     * Yang dapat dicentang: simpanan yang masih terbit (terbaru dulu), lalu jalur terpilih
     * yang bukan simpanan pembaca, supaya pilihan dari Rekomendasi atau tautan lama tetap
     * dapat dilepas. Simpanan pendaki lain tidak pernah ikut.
     *
     * @param  Collection<int, TrailConsideration>  $simpanan
     * @param  EloquentCollection<int, Trail>  $dipilih
     * @return array<int, array{id: int, name: string, mountain: string|null, dipilih: bool, tersimpan: bool}>
     */
    private function opsi(Collection $simpanan, EloquentCollection $dipilih): array
    {
        $idDipilih = $dipilih->pluck('id')->all();
        $tersimpan = $simpanan->map(fn (TrailConsideration $baris) => $baris->trail)->filter(fn (Trail $t) => $t->is_published)->values();
        $idTersimpan = $tersimpan->pluck('id')->all();
        $luar = $dipilih->reject(fn (Trail $t) => in_array($t->id, $idTersimpan, true));

        return $tersimpan->concat($luar)->map(fn (Trail $t) => [
            'id' => $t->id,
            'name' => $t->name,
            'mountain' => $t->mountain?->name,
            'dipilih' => in_array($t->id, $idDipilih, true),
            'tersimpan' => in_array($t->id, $idTersimpan, true),
        ])->values()->all();
    }

    /**
     * Tautan perbandingan untuk sekumpulan id. Tanpa id, parameternya tetap ada tetapi
     * kosong: pilihan kosong yang disengaja, bukan kembali ke pilihan bawaan.
     *
     * @param  array<int, int>  $ids
     */
    private function tautan(array $ids): string
    {
        return $ids === []
            ? route('trails.compare').'?trails='
            : route('trails.compare', ['trails' => implode(',', $ids)]);
    }
}
