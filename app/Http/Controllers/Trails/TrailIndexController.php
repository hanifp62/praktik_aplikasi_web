<?php

namespace App\Http\Controllers\Trails;

use App\Enums\TechnicalDemand;
use App\Http\Controllers\Controller;
use App\Http\Resources\TrailRowResource;
use App\Models\Trail;
use App\Services\ConsiderationService;
use App\Services\TrailFitService;
use App\Services\Trails\PetaSebaranJalur;
use App\Services\Trails\TrailSearch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Jelajah: telusur dan saring jalur secara manual, jalan keluar ketika rekomendasi
 * tidak menolong (PRD §94). Halaman React pertama pada migrasi jalur C.
 */
class TrailIndexController extends Controller
{
    public function __invoke(Request $request, TrailFitService $fit, ConsiderationService $consideration, PetaSebaranJalur $peta): Response
    {
        $user = $request->user();
        $pencarian = TrailSearch::dariInput($request->query());
        $trails = $pencarian->terbit();

        // Kecocokan hanya untuk profil yang lengkap: menilai tanpa profil menghasilkan
        // label yang terlihat pasti dan berdasar ketiadaan (§91).
        $ringkasanFit = $user->hasCompletedProfile()
            ? $fit->forTrails($user, $trails->getCollection())
            : [];

        $ditimbang = $consideration->forUser($user);

        // Bentuk jalur, bukan titik gunung (keputusan F2): jalur tanpa geometri tidak
        // tampil di peta. Satu query untuk satu halaman hasil.
        $petaSebaran = $peta->untuk($trails->getCollection());

        return Inertia::render('Trails/Index', [
            'title' => 'Jelajahi Jalur',
            'filter' => $pencarian->filter(),
            'technicalLevels' => array_map(fn (TechnicalDemand $t) => ['value' => $t->value, 'label' => $t->label()], TechnicalDemand::cases()),
            'trails' => [
                'data' => $trails->getCollection()
                    ->map(fn (Trail $t) => (new TrailRowResource($t, $ringkasanFit[$t->id] ?? null, $ditimbang->contains('id', $t->id)))->resolve($request))
                    ->values()
                    ->all(),
                'total' => $trails->total(),
                'links' => $trails->linkCollection()->map(fn (array $l) => ['url' => $l['url'], 'label' => $l['label'], 'active' => $l['active']])->all(),
            ],
            'menunggu' => $pencarian->menunggu()->map(fn (Trail $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'url' => route('trails.show', $t->slug),
                'mountain' => $t->mountain?->name,
                'province' => $t->mountain?->province,
            ])->values()->all(),
            'ditimbang' => $ditimbang->map(fn (Trail $t) => ['id' => $t->id, 'name' => $t->name])->values()->all(),
            'batasTimbangan' => $consideration->batasSimpanan(),
            'petaSebaran' => $petaSebaran,
            'pengumuman' => session('pengumuman', ''),
        ]);
    }
}
