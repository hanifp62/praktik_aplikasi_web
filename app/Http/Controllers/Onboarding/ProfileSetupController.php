<?php

namespace App\Http\Controllers\Onboarding;

use App\Enums\AnalyticsEvent;
use App\Enums\ExperienceLevel;
use App\Enums\InterestRegion;
use App\Enums\NavigationExperience;
use App\Enums\PreferredDuration;
use App\Enums\RouteFitLabel;
use App\Enums\TerrainCharacter;
use App\Enums\TripType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProfileSetupRequest;
use App\Models\Trail;
use App\Models\User;
use App\Services\AnalyticsRecorder;
use App\Services\Profil\TurunanPengalaman;
use App\Services\Trails\CariJalur;
use App\Services\Trails\HalamanJelajah;
use App\Services\Trails\TrailSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Onboarding profil pendaki (FR-02; DK-41, DK-42; spec v3 bagian 6.16; prototype 00), yang juga
 * menjadi penyunting pengalaman dan preferensi dari Profil. Tiga langkah: tingkat pengalaman,
 * jalur terberat dengan nilai turunannya, lalu rencana dan wilayah minat. Sesudah disimpan,
 * halaman menyebut akibat langsungnya, "N jalur berlabel Cocok", dihitung server dengan
 * saringan yang sama dengan Jelajah. "Nanti saja" menuju Jelajah tanpa menyimpan apa pun, jadi
 * label tetap "Belum dinilai" (R-006).
 */
class ProfileSetupController extends Controller
{
    public function show(Request $request, CariJalur $cari): Response
    {
        $user = $request->user()->loadMissing('profile', 'experience', 'preference');
        $kata = $request->query('cari');
        $langkah = $request->integer('langkah');

        return Inertia::render('Onboarding/ProfileSetup', [
            'title' => 'Profil Pendaki',
            'sudahLengkap' => $user->hasCompletedProfile(),
            'langkahAwal' => in_array($langkah, [1, 2, 3], true) ? $langkah : 1,
            'nilai' => $this->nilai($user),
            'pilihan' => [
                'tingkat' => array_map(fn (ExperienceLevel $t) => ['nilai' => $t->value, 'label' => $t->label(), 'keterangan' => $t->keterangan()], ExperienceLevel::cases()),
                'navigasi' => array_map(fn (NavigationExperience $n) => ['nilai' => $n->value, 'label' => $n->label(), 'keterangan' => $n->keterangan()], NavigationExperience::cases()),
                'medan' => array_map(fn (TerrainCharacter $m) => ['nilai' => $m->value, 'label' => $m->label()], TerrainCharacter::cases()),
                'jenisTrip' => array_map(fn (TripType $j) => ['nilai' => $j->value, 'label' => $j->labelPendek(), 'keterangan' => self::keteranganJenis($j)], TripType::cases()),
                'durasi' => array_map(fn (PreferredDuration $d) => ['nilai' => $d->value, 'label' => $d->label()], PreferredDuration::cases()),
                'wilayah' => array_map(fn (InterestRegion $w) => ['nilai' => $w->value, 'label' => $w->label()], InterestRegion::cases()),
            ],
            // Hanya lewat muat ulang parsial saat pendaki mencari jalur terberatnya.
            'pilihanJalur' => Inertia::optional(fn () => $cari->untuk(is_string($kata) ? $kata : '')->map(fn (Trail $t) => TurunanPengalaman::baris($t))->all()),
            'selesai' => $request->session()->get('onboarding-selesai'),
            'tautan' => [
                'jelajah' => route('trails.index'),
                'cocok' => route('trails.index', ['kecocokan' => RouteFitLabel::COCOK->value]),
                'profil' => route('profile'),
            ],
        ]);
    }

    public function store(StoreProfileSetupRequest $request, AnalyticsRecorder $analytics, HalamanJelajah $jelajah): RedirectResponse
    {
        // Pemeriksaan satu langkah: galat sudah dikembalikan FormRequest; lolos berarti
        // pendaki boleh pindah langkah. Tidak ada yang disimpan.
        if ($request->langkahDiperiksa() !== null) {
            return redirect()->route('onboarding');
        }

        $user = $request->user();
        $data = $request->validated();
        $pengalaman = $request->pengalaman();

        DB::transaction(function () use ($user, $data, $pengalaman, $request) {
            $user->profile()->updateOrCreate([], [
                'experience_level' => $data['experience_level'],
                'completed_at' => now(),
            ]);
            $user->experience()->updateOrCreate([], $pengalaman);
            $user->preference()->updateOrCreate([], [
                'preferred_trip_type' => $data['preferred_trip_type'],
                'preferred_duration' => $data['preferred_duration'],
                'max_elevation_gain_preference_m' => $data['max_elevation_gain_preference_m'] ?? null,
                'interest_regions' => $request->wilayah(),
            ]);
        });

        $analytics->record(AnalyticsEvent::PROFILE_COMPLETED, $user);

        if ($intended = $request->session()->pull('url.intended')) {
            return redirect()->to($intended)->with('status', 'Profil pendaki tersimpan.');
        }

        // Saringan dan penilaian yang sama dengan Jelajah ?kecocokan=COCOK, jadi angka di sini
        // sama dengan jumlah yang tampil begitu pendaki membukanya.
        $cocok = $jelajah->props(TrailSearch::dariInput(['kecocokan' => RouteFitLabel::COCOK->value]), $user->fresh(), $request)['ringkasan']['jumlah'];

        return redirect()->route('onboarding')->with('onboarding-selesai', ['cocok' => $cocok]);
    }

    /**
     * Isian awal dari profil yang sudah ada. Pengalaman yang sudah tersimpan dibuka sebagai isian
     * sendiri (MANUAL), karena jalur terberatnya tidak disimpan; nol pendakian dibuka sebagai
     * "belum pernah mendaki". Yang belum diisi tetap kosong (R-007).
     *
     * @return array<string, mixed>
     */
    private function nilai(User $user): array
    {
        $pengalaman = $user->experience;
        $cara = match (true) {
            $pengalaman === null => StoreProfileSetupRequest::KATALOG,
            $pengalaman->completed_hikes_count === 0 => StoreProfileSetupRequest::BELUM,
            default => StoreProfileSetupRequest::MANUAL,
        };
        $durasi = $pengalaman?->longest_hike_duration_minutes;

        return [
            'experience_level' => $user->profile?->experience_level?->value ?? '',
            'cara_pengalaman' => $cara,
            'jalur_terberat_id' => '',
            'completed_hikes_count' => max(1, $pengalaman?->completed_hikes_count ?? 1),
            'terrain_experience' => array_values($pengalaman?->terrain_experience ?? []),
            'navigation_experience' => $pengalaman?->navigation_experience?->value ?? '',
            'highest_elevation_gain_m' => $pengalaman?->highest_elevation_gain_m ?? '',
            'durasi_terlama_jam' => $durasi !== null && $durasi > 0 ? (string) min(24, (int) ceil($durasi / 60)) : '',
            'preferred_trip_type' => $user->preference?->preferred_trip_type?->value ?? '',
            'preferred_duration' => $user->preference?->preferred_duration?->value ?? '',
            'interest_regions' => array_map(fn (InterestRegion $w) => $w->value, InterestRegion::dari($user->preference?->interest_regions)),
            'max_elevation_gain_preference_m' => $user->preference?->max_elevation_gain_preference_m ?? '',
        ];
    }

    private static function keteranganJenis(TripType $jenis): string
    {
        return match ($jenis) {
            TripType::TEKTOK => 'Naik dan turun di hari yang sama.',
            TripType::CAMPING => 'Bermalam satu kali di area kemah.',
            TripType::MULTI_DAY => 'Lebih dari dua hari, dengan logistik lebih banyak.',
        };
    }
}
