<?php

namespace App\Http\Controllers\Onboarding;

use App\Enums\AnalyticsEvent;
use App\Enums\ExperienceLevel;
use App\Enums\NavigationExperience;
use App\Enums\PreferredChallenge;
use App\Enums\PreferredDuration;
use App\Enums\TerrainCharacter;
use App\Enums\TripType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProfileSetupRequest;
use App\Models\User;
use App\Services\AnalyticsRecorder;
use App\Support\Durasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * FR-02 profil pendaki. Halaman ini merangkap penyunting profil: isiannya diisi dari
 * profil yang sudah ada, dan menyimpan memperbarui, bukan menggandakan. Pendaki yang
 * profilnya sudah lengkap melihat ringkasannya lebih dulu, sudah diformat di sini.
 */
class ProfileSetupController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user()->loadMissing('profile', 'experience', 'preference');

        return Inertia::render('Onboarding/ProfileSetup', [
            'title' => 'Profil Pendaki',
            'ringkasan' => $this->ringkasan($user),
            'nilai' => [
                'experience_level' => $user->profile?->experience_level?->value ?? '',
                'region_preference' => $user->profile?->region_preference ?? '',
                'bio' => $user->profile?->bio ?? '',
                'completed_hikes_count' => $user->experience?->completed_hikes_count ?? 0,
                'terrain_experience' => $user->experience?->terrain_experience ?? [],
                'navigation_experience' => $user->experience?->navigation_experience?->value ?? '',
                'longest_hike_duration_minutes' => $user->experience?->longest_hike_duration_minutes ?? '',
                'highest_elevation_gain_m' => $user->experience?->highest_elevation_gain_m ?? '',
                'preferred_duration' => $user->preference?->preferred_duration?->value ?? '',
                'preferred_trip_type' => $user->preference?->preferred_trip_type?->value ?? '',
                'preferred_challenge' => $user->preference?->preferred_challenge?->value ?? '',
                'max_elevation_gain_preference_m' => $user->preference?->max_elevation_gain_preference_m ?? '',
            ],
            'pilihan' => [
                'experienceLevels' => self::opsi(ExperienceLevel::cases()),
                'navigationLevels' => self::opsi(NavigationExperience::cases()),
                'terrainOptions' => self::opsi(TerrainCharacter::cases()),
                'durations' => self::opsi(PreferredDuration::cases()),
                'tripTypes' => self::opsi(TripType::cases()),
                'challenges' => self::opsi(PreferredChallenge::cases()),
            ],
        ]);
    }

    public function store(StoreProfileSetupRequest $request, AnalyticsRecorder $analytics): RedirectResponse
    {
        // Pemeriksaan satu langkah: galat sudah dikembalikan FormRequest; lolos berarti
        // pendaki boleh pindah langkah. Tidak ada yang disimpan.
        if ($request->langkahDiperiksa() !== null) {
            return redirect()->route('onboarding');
        }

        $user = $request->user();
        $data = $request->validated();

        $user->profile()->updateOrCreate([], [
            'experience_level' => $data['experience_level'],
            'region_preference' => $data['region_preference'] ?? null,
            'bio' => $data['bio'] ?? null,
            'completed_at' => now(),
        ]);

        $user->experience()->updateOrCreate([], [
            'completed_hikes_count' => $data['completed_hikes_count'],
            'terrain_experience' => $data['terrain_experience'] ?? [],
            'navigation_experience' => $data['navigation_experience'],
            'longest_hike_duration_minutes' => $data['longest_hike_duration_minutes'] ?? null,
            'highest_elevation_gain_m' => $data['highest_elevation_gain_m'] ?? null,
        ]);

        $user->preference()->updateOrCreate([], [
            'preferred_duration' => $data['preferred_duration'],
            'preferred_trip_type' => $data['preferred_trip_type'],
            'preferred_challenge' => $data['preferred_challenge'] ?? null,
            'max_elevation_gain_preference_m' => $data['max_elevation_gain_preference_m'] ?? null,
            'region_preference' => $data['region_preference'] ?? null,
        ]);

        $analytics->record(AnalyticsEvent::PROFILE_COMPLETED, $user);

        return redirect()->route('goals.create')->with('status', 'Profil pendaki tersimpan.');
    }

    /**
     * Profil tersimpan sebagai daftar siap tampil. Yang belum diisi disebut belum diisi,
     * bukan nol (R-007).
     *
     * @return array<int, array{label: string, nilai: string}>|null
     */
    private function ringkasan(User $user): ?array
    {
        if (! $user->hasCompletedProfile()) {
            return null;
        }

        $belum = 'Belum diisi';
        $profil = $user->profile;
        $pengalaman = $user->experience;
        $preferensi = $user->preference;

        $medan = collect($pengalaman?->terrain_experience ?? [])
            ->map(fn (string $v) => TerrainCharacter::tryFrom($v)?->label())
            ->filter()
            ->implode(', ');

        $meter = fn (?int $m): string => $m === null ? $belum : number_format($m, 0, ',', '.').' m';

        return [
            ['label' => 'Tingkat pengalaman', 'nilai' => $profil?->experience_level?->label() ?? $belum],
            ['label' => 'Preferensi wilayah', 'nilai' => $profil?->region_preference ?: $belum],
            ['label' => 'Jumlah pendakian yang pernah diselesaikan', 'nilai' => $pengalaman ? number_format($pengalaman->completed_hikes_count, 0, ',', '.') : $belum],
            ['label' => 'Medan yang pernah dilalui', 'nilai' => $medan !== '' ? $medan : $belum],
            ['label' => 'Pengalaman navigasi', 'nilai' => $pengalaman?->navigation_experience?->label() ?? $belum],
            ['label' => 'Durasi pendakian terlama', 'nilai' => $pengalaman?->longest_hike_duration_minutes ? Durasi::panjang($pengalaman->longest_hike_duration_minutes) : $belum],
            ['label' => 'Elevation gain tertinggi', 'nilai' => $meter($pengalaman?->highest_elevation_gain_m)],
            ['label' => 'Durasi yang Anda sukai', 'nilai' => $preferensi?->preferred_duration?->label() ?? $belum],
            ['label' => 'Tipe perjalanan', 'nilai' => $preferensi?->preferred_trip_type?->label() ?? $belum],
            ['label' => 'Tingkat tantangan yang dicari', 'nilai' => $preferensi?->preferred_challenge?->label() ?? 'Tidak ada preferensi'],
            ['label' => 'Batas elevation gain yang nyaman', 'nilai' => $meter($preferensi?->max_elevation_gain_preference_m)],
        ];
    }

    /**
     * @param  array<int, \BackedEnum>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    private static function opsi(array $cases): array
    {
        return array_map(fn ($c) => ['value' => $c->value, 'label' => $c->label()], $cases);
    }
}
