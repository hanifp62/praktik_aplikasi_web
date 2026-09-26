<?php

namespace App\Http\Controllers\Recommendations;

use App\Enums\AnalyticsEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\SelectRecommendedTrailRequest;
use App\Http\Resources\RecommendationResultResource;
use App\Models\RecommendationResult;
use App\Models\RecommendationRun;
use App\Models\Trail;
use App\Models\User;
use App\Services\AnalyticsRecorder;
use App\Support\Timezone;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * FR-05 halaman hasil rekomendasi dengan penjelasan per rute (PRD §30, §32).
 *
 * Run ber-URL permanen dan sengaja tidak dihitung ulang: ia catatan tentang apa yang
 * dinilai saat itu. Yang dikirim ke klien hanya label publik dan kategori faktor;
 * skor internal berhenti di RecommendationResultResource (BR-09).
 */
class RecommendationController extends Controller
{
    /**
     * Berapa jalur teratas yang dikirim. Sisanya ditambahkan sepuluh demi sepuluh atas
     * permintaan lewat parameter `tampil`, tidak pernah dibuang.
     */
    public const SEKALI_TAMPIL = 10;

    public function show(Request $request, RecommendationRun $run): Response
    {
        abort_unless((int) $run->user_id === (int) $request->user()->id, 403);

        $run->load('results.trail.mountain', 'hikingGoal');

        $results = $run->results;
        $eligible = $results->where('eligible', true);
        $excluded = $results->where('eligible', false);

        $tampil = max(self::SEKALI_TAMPIL, (int) $request->integer('tampil', self::SEKALI_TAMPIL));
        $tampil = min($tampil, max(self::SEKALI_TAMPIL, $eligible->count()));

        return Inertia::render('Recommendations/Show', [
            'title' => 'Rekomendasi Jalur',
            'runId' => $run->id,
            'dihasilkan' => Timezone::display($run->generated_at, Timezone::DEFAULT),
            'profilBerubah' => $this->profilBerubah($request->user(), $run),
            'penyebabKosong' => $this->penyebabKosong($results, $run),
            'wilayah' => $run->hikingGoal?->region,
            'eligible' => $eligible->take($tampil)
                ->map(fn (RecommendationResult $r) => (new RecommendationResultResource($r))->resolve($request))
                ->values()
                ->all(),
            'sisaEligible' => max(0, $eligible->count() - $tampil),
            'tampil' => $tampil,
            'sekaliTampil' => self::SEKALI_TAMPIL,
            'excluded' => $excluded->map(fn (RecommendationResult $r) => [
                'id' => $r->id,
                'name' => $r->trail->name,
                'mountain' => $r->trail->mountain?->name,
                'alasan' => array_map(fn (string $rule) => __('recommendation.'.$rule), array_values((array) ($r->failed_rules ?? []))),
            ])->values()->all(),
        ]);
    }

    /**
     * Memilih jalur dari hasil: mencatat funnel lalu mengantar ke pembuatan trip.
     *
     * Tombolnya sudah hilang dari tampilan untuk jalur terarsip, tetapi halaman yang
     * dimuat sebelum jalur itu diarsipkan masih dapat mengirim aksi ini. Ditolak di sini
     * supaya pendaki tidak baru mengetahuinya setelah berpindah halaman.
     */
    public function pilih(SelectRecommendedTrailRequest $request, RecommendationRun $run, AnalyticsRecorder $analytics): SymfonyResponse
    {
        abort_unless((int) $run->user_id === (int) $request->user()->id, 403);

        $trailId = (int) $request->validated('trail');

        if (! Trail::published()->whereKey($trailId)->exists()) {
            return back()->withErrors(['trail' => 'Jalur ini sudah ditarik dari katalog dan tidak dapat dipilih lagi.']);
        }

        $analytics->record(AnalyticsEvent::ROUTE_SELECTED, $request->user(), ['trail_id' => $trailId]);

        return redirect()->route('trips.create', ['trail' => $trailId, 'goal' => $run->hiking_goal_id]);
    }

    /**
     * Apakah profil pendaki sudah berbeda dari yang dipakai menilai run ini.
     *
     * Onboarding merangkap penyunting profil, jadi pendaki dapat menaikkan tingkat
     * pengalamannya lalu membuka kembali run kemarin; label di sana dihitung terhadap
     * orang yang bukan dirinya lagi. Yang dibandingkan nilainya, bukan ada tidaknya
     * penyuntingan.
     */
    private function profilBerubah(User $user, RecommendationRun $run): bool
    {
        $snapshot = $run->input_snapshot;

        if (! is_array($snapshot)) {
            return false;
        }

        $user->loadMissing('profile', 'experience');

        return $this->sidikProfil($snapshot) !== $this->sidikProfil([
            'experience_level' => $user->profile?->experience_level?->value,
            'navigation_experience' => $user->experience?->navigation_experience?->value,
            'terrain_experience' => $user->experience?->terrain_experience,
            'completed_hikes_count' => $user->experience?->completed_hikes_count,
            'highest_elevation_gain_m' => $user->experience?->highest_elevation_gain_m,
        ]);
    }

    /**
     * Medan diurutkan karena urutan pilihan tidak bermakna: memilih ulang dua kotak
     * yang sama dalam urutan berbeda bukan profil yang berbeda.
     *
     * @param  array<string, mixed>  $sumber
     * @return array<string, mixed>
     */
    private function sidikProfil(array $sumber): array
    {
        $medan = array_values(array_filter((array) ($sumber['terrain_experience'] ?? [])));
        sort($medan);

        return [
            'experience_level' => $sumber['experience_level'] ?? null,
            'navigation_experience' => $sumber['navigation_experience'] ?? null,
            'terrain_experience' => $medan,
            'completed_hikes_count' => isset($sumber['completed_hikes_count']) ? (int) $sumber['completed_hikes_count'] : null,
            'highest_elevation_gain_m' => isset($sumber['highest_elevation_gain_m']) ? (int) $sumber['highest_elevation_gain_m'] : null,
        ];
    }

    /**
     * Layar kosong punya tiga sebab yang menuntut tiga saran berbeda. Penyaring wilayah
     * bekerja di tingkat query, jadi jalur yang tersaring tidak pernah menjadi kandidat
     * maupun jalur tersingkir; menyuruh pendaki melonggarkan batas di situ menyuruhnya
     * mengubah hal yang tidak pernah diuji terhadap satu jalur pun.
     *
     * @param  Collection<int, RecommendationResult>  $results
     */
    private function penyebabKosong(Collection $results, RecommendationRun $run): ?string
    {
        if ($results->isNotEmpty()) {
            return null;
        }

        if (! Trail::published()->exists()) {
            return 'katalog';
        }

        return $run->hikingGoal?->region ? 'wilayah' : 'batasan';
    }
}
