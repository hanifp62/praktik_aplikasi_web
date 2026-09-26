<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NavigationComplexity;
use App\Enums\TechnicalDemand;
use App\Enums\TerrainCharacter;
use App\Enums\WaterAvailability;
use App\Exceptions\PublicationGateViolation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TrailRequest;
use App\Http\Resources\Admin\TrailAdminRowResource;
use App\Models\DataSource;
use App\Models\Mountain;
use App\Models\Trail;
use App\Services\Admin\TrailDataHealth;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kelola jalur: tambah, ubah, publikasi, dan arsip, dengan ringkasan kesehatan data di
 * atas daftarnya supaya kurator tahu apa yang perlu diurus lebih dulu.
 *
 * Gerbang publikasi (R-008/R-022, PRD §110) tetap dua lapis. Controller menolak lebih
 * dulu dengan daftar syarat yang kurang, supaya kurator membaca apa yang harus
 * dilengkapi. Penjaga di model tetap menjadi kebenaran terakhir: perubahan yang akan
 * membuat jalur tayang tidak lengkap ditolak di sana, dan penolakannya sampai ke kurator
 * sebagai kalimat, bukan halaman 500.
 */
class TrailController extends Controller
{
    public function index(Request $request, TrailDataHealth $kesehatan): Response
    {
        $this->authorize('viewAny', Trail::class);

        $saringan = $request->query('perlu');
        $saringan = is_string($saringan) && $kesehatan->label($saringan) !== null ? $saringan : null;

        $query = Trail::query()->with('mountain')->withPublishabilityData()->orderBy('name');
        $trails = $kesehatan->saring($query, $saringan)->paginate(10)->withQueryString();

        return Inertia::render('Admin/Trails', [
            'title' => 'Kelola Jalur',
            'trails' => [
                'data' => TrailAdminRowResource::collection($trails->getCollection())->resolve($request),
                'links' => array_map(
                    fn (array $l) => ['url' => $l['url'], 'label' => $l['label'], 'active' => $l['active']],
                    $trails->linkCollection()->all(),
                ),
            ],
            'kesehatan' => [
                'temuan' => array_map(
                    fn (array $temuan) => $temuan + ['jumlahTeks' => number_format($temuan['jumlah'], 0, ',', '.').' jalur'],
                    $kesehatan->ringkasan(),
                ),
                'geometriDiperiksa' => Trail::spatialSupported(),
                'saringan' => $saringan,
                'saringanLabel' => $kesehatan->label($saringan),
            ],
            'mountains' => Mountain::active()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Mountain $mountain) => ['value' => (string) $mountain->id, 'label' => $mountain->name])
                ->all(),
            'sources' => DataSource::query()->orderBy('source_name')->get(['id', 'source_name'])
                ->map(fn (DataSource $source) => ['value' => (string) $source->id, 'label' => $source->source_name])
                ->all(),
            'technicalLevels' => $this->pilihan(TechnicalDemand::cases()),
            'navigationLevels' => $this->pilihan(NavigationComplexity::cases()),
            'waterLevels' => $this->pilihan(WaterAvailability::cases()),
            'terrainOptions' => $this->pilihan(TerrainCharacter::cases()),
            'penolakan' => session('penolakan'),
        ]);
    }

    public function store(TrailRequest $request, AuditLogService $audit): RedirectResponse
    {
        $attributes = $this->atribut($request);

        $trail = Trail::create($attributes);
        $audit->record($request->user(), 'trail.created', $trail, null, $attributes);

        return back()->with('status', 'Data jalur tersimpan.');
    }

    public function update(TrailRequest $request, Trail $trail, AuditLogService $audit): RedirectResponse
    {
        try {
            $trail->update($this->atribut($request));
        } catch (PublicationGateViolation $e) {
            return back()->with('penolakan', $e->curatorMessage());
        }

        $audit->recordChange($request->user(), 'trail.updated', $trail);

        return back()->with('status', 'Data jalur tersimpan.');
    }

    public function togglePublish(Request $request, Trail $trail, AuditLogService $audit): RedirectResponse
    {
        if ($trail->is_published) {
            $this->authorize('update', $trail);
            $trail->update(['is_published' => false]);
            $audit->recordChange($request->user(), 'trail.unpublished', $trail);

            return back()->with('status', $trail->name.' ditarik dari publikasi.');
        }

        // PRD §110: publikasi ditahan sampai set kualitas data minimum ada.
        $missing = $trail->publishabilityReport();

        if ($missing !== []) {
            return back()->with('penolakan', 'Jalur belum dapat dipublikasikan. '.implode(' ', $missing));
        }

        $this->authorize('publish', $trail);
        $trail->update(['is_published' => true]);
        $audit->recordChange($request->user(), 'trail.published', $trail);

        return back()->with('status', $trail->name.' dipublikasikan.');
    }

    public function toggleArchive(Request $request, Trail $trail, AuditLogService $audit): RedirectResponse
    {
        $this->authorize('archive', $trail);

        try {
            $trail->update(['archived_at' => $trail->archived_at ? null : now()]);
        } catch (PublicationGateViolation $e) {
            return back()->with('penolakan', $e->curatorMessage());
        }

        $audit->recordChange($request->user(), 'trail.archive_toggled', $trail);

        return back()->with('status', $trail->name.($trail->archived_at ? ' diarsipkan.' : ' diaktifkan kembali.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function atribut(TrailRequest $request): array
    {
        $data = $request->validated();
        $bilangan = fn (string $kunci) => isset($data[$kunci]) ? (int) $data[$kunci] : null;

        return [
            'mountain_id' => (int) $data['mountain_id'],
            'name' => $data['name'],
            'slug' => Str::slug($data['name'].'-'.$data['mountain_id']),
            'description' => $data['description'] ?? null,
            'distance_km' => isset($data['distance_km']) ? (float) $data['distance_km'] : null,
            'elevation_gain_m' => $bilangan('elevation_gain_m'),
            'elevation_loss_m' => $bilangan('elevation_loss_m'),
            'estimated_duration_minutes' => $bilangan('estimated_duration_minutes'),
            'technical_demand' => $data['technical_demand'],
            'navigation_complexity' => $data['navigation_complexity'],
            'water_availability' => $data['water_availability'],
            'terrain_character' => array_values($data['terrain_character'] ?? []),
            'camping_available' => (bool) ($data['camping_available'] ?? false),
            'starting_point' => $data['starting_point'] ?? null,
            'weather_adm4_code' => $data['weather_adm4_code'] ?? null,
            'weather_reference_area' => $data['weather_reference_area'] ?? null,
            'data_source_id' => $bilangan('data_source_id'),
        ];
    }

    /**
     * @param  array<int, TechnicalDemand|NavigationComplexity|WaterAvailability|TerrainCharacter>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    private function pilihan(array $cases): array
    {
        return array_map(fn ($case) => ['value' => $case->value, 'label' => $case->label()], $cases);
    }
}
