<?php

namespace App\Http\Controllers\Goals;

use App\Enums\AnalyticsEvent;
use App\Enums\PreferredChallenge;
use App\Enums\TripType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGoalRequest;
use App\Models\HikingGoal;
use App\Services\AnalyticsRecorder;
use App\Services\RouteFitService;
use App\Support\Timezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-03 rencana pendakian: konteks trip yang dimasukkan ke Route Fit Engine.
 */
class GoalController extends Controller
{
    public function create(Request $request): Response
    {
        $preference = $request->user()->preference;

        return Inertia::render('Goals/Create', [
            'title' => 'Rencana Pendakian',
            'nilai' => [
                'target_date' => '',
                'region' => $preference?->region_preference ?? '',
                'trip_type' => $preference?->preferred_trip_type?->value ?? '',
                'expected_duration_minutes' => $preference?->preferred_duration?->approximateMinutes() ?? '',
                'preferred_challenge' => $preference?->preferred_challenge?->value ?? '',
                'max_elevation_gain_m' => $preference?->max_elevation_gain_preference_m ?? '',
                'notes' => '',
            ],
            'pilihan' => [
                'tripTypes' => array_map(fn (TripType $t) => ['value' => $t->value, 'label' => $t->label()], TripType::cases()),
                'challenges' => array_map(fn (PreferredChallenge $c) => ['value' => $c->value, 'label' => $c->label()], PreferredChallenge::cases()),
            ],
            'tanggalTerawal' => Timezone::earliestDateInIndonesia(),
        ]);
    }

    public function store(StoreGoalRequest $request, RouteFitService $routeFit, AnalyticsRecorder $analytics): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasCompletedProfile()) {
            return redirect()->route('onboarding')->with('status', 'Lengkapi profil pendaki terlebih dahulu.');
        }

        // PRD §100. Satu run mengevaluasi seluruh jalur kandidat dan menyimpan jejak
        // auditnya, jadi biayanya sebanding dengan ukuran dataset.
        $key = 'recommendation-run:'.$user->id;
        $limit = (int) config('hiking.rate_limits.recommendation_runs_per_hour');

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return back()->withErrors(['form' => sprintf(
                'Anda sudah membuat %d rencana dalam satu jam terakhir. Coba lagi dalam %d menit.',
                $limit,
                (int) ceil(RateLimiter::availableIn($key) / 60)
            )]);
        }

        RateLimiter::hit($key, 3600);

        $goal = HikingGoal::create(['user_id' => $user->id, ...$request->validated()]);

        $run = $routeFit->recommend($user, $goal);
        $analytics->record(AnalyticsEvent::RECOMMENDATION_VIEWED, $user, ['recommendation_run_id' => $run->id]);

        return redirect()->route('recommendations.show', ['run' => $run->id]);
    }
}
