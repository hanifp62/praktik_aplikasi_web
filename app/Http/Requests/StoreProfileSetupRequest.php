<?php

namespace App\Http\Requests;

use App\Enums\ExperienceLevel;
use App\Enums\InterestRegion;
use App\Enums\NavigationExperience;
use App\Enums\PreferredDuration;
use App\Enums\TerrainCharacter;
use App\Enums\TripType;
use App\Models\Trail;
use App\Services\Profil\TurunanPengalaman;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FR-02 profil pendaki dalam tiga langkah onboarding (DK-41, DK-42; spec v3 bagian 6.16).
 * Pengalaman yang diisi sendiri adalah masukan rekomendasi, bukan sertifikasi (PRD §22).
 *
 * Langkah 2 punya tiga cara: KATALOG (jalur terberat dari katalog; tanjakan, medan, dan
 * navigasi diturunkan server dari jalur itu, bukan dari isian), MANUAL (pendaki mengisi
 * sendiri, termasuk sesudah "Ubah nilai"), dan BELUM (belum pernah mendaki: nol pendakian dan
 * navigasi "Belum ada" adalah jawaban yang diketahui, bukan UNKNOWN yang dianggap rendah).
 *
 * Isian `langkah` (1 atau 2) meminta pemeriksaan satu langkah saja; tanpa `langkah`, atau
 * langkah 3, seluruh aturan berlaku dan profil disimpan. Kolom lama yang tidak lagi ditanyakan
 * (region_preference, bio, preferred_challenge) tidak disentuh.
 */
class StoreProfileSetupRequest extends FormRequest
{
    public const KATALOG = 'KATALOG';

    public const MANUAL = 'MANUAL';

    public const BELUM = 'BELUM';

    /** @var array<int, array<int, string>> */
    private const ISIAN_LANGKAH = [
        1 => ['experience_level'],
        2 => [
            'cara_pengalaman', 'jalur_terberat_id', 'completed_hikes_count', 'terrain_experience',
            'terrain_experience.*', 'navigation_experience', 'highest_elevation_gain_m', 'durasi_terlama_jam',
        ],
    ];

    /**
     * Langkah yang hanya diperiksa, bukan disimpan; null berarti simpan.
     */
    public function langkahDiperiksa(): ?int
    {
        $langkah = (int) $this->input('langkah', 3);

        return array_key_exists($langkah, self::ISIAN_LANGKAH) ? $langkah : null;
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $bukanBelum = 'exclude_if:cara_pengalaman,'.self::BELUM;
        $hanyaManual = 'exclude_unless:cara_pengalaman,'.self::MANUAL;

        $semua = [
            'experience_level' => ['required', Rule::enum(ExperienceLevel::class)],
            'cara_pengalaman' => ['required', Rule::in([self::KATALOG, self::MANUAL, self::BELUM])],
            'jalur_terberat_id' => [
                'exclude_unless:cara_pengalaman,'.self::KATALOG,
                'required',
                'integer',
                Rule::exists('trails', 'id')->where(fn ($q) => $q->where('is_published', true)->whereNull('archived_at')),
            ],
            'completed_hikes_count' => [$bukanBelum, 'required', 'integer', 'min:1', 'max:2000'],
            'terrain_experience' => [$hanyaManual, 'array', 'max:'.count(TerrainCharacter::cases())],
            'terrain_experience.*' => [$hanyaManual, 'distinct', Rule::enum(TerrainCharacter::class)],
            'navigation_experience' => [$hanyaManual, 'required', Rule::enum(NavigationExperience::class)],
            'highest_elevation_gain_m' => [$hanyaManual, 'nullable', 'integer', 'min:1', 'max:9000'],
            'durasi_terlama_jam' => [$bukanBelum, 'nullable', 'integer', 'min:1', 'max:24'],
            'preferred_trip_type' => ['required', Rule::enum(TripType::class)],
            'preferred_duration' => ['required', Rule::enum(PreferredDuration::class)],
            'interest_regions' => ['array', 'max:'.count(InterestRegion::cases())],
            'interest_regions.*' => ['distinct', Rule::enum(InterestRegion::class)],
            'max_elevation_gain_preference_m' => ['nullable', 'integer', 'min:1', 'max:9000'],
        ];

        $langkah = $this->langkahDiperiksa();

        return $langkah === null
            ? $semua
            : array_intersect_key($semua, array_flip(self::ISIAN_LANGKAH[$langkah]));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'experience_level.required' => 'Pilih tingkat yang paling mendekati pengalaman Anda.',
            'cara_pengalaman.required' => 'Pilih jalur terberat Anda, isi sendiri, atau pilih "Saya belum pernah mendaki".',
            'jalur_terberat_id.required' => 'Pilih jalur dari hasil pencarian, atau pilih "Jalurnya tidak ada di daftar".',
            'jalur_terberat_id.exists' => 'Jalur itu tidak ada di katalog terbit. Cari lagi, atau isi sendiri.',
            'completed_hikes_count.min' => 'Jumlah pendakian paling sedikit 1. Bila belum pernah mendaki sampai selesai, pilih "Saya belum pernah mendaki".',
            'navigation_experience.required' => 'Pilih pengalaman navigasi Anda.',
            'highest_elevation_gain_m.max' => 'Tanjakan diisi dalam meter, paling banyak 9.000, atau dikosongkan.',
            'highest_elevation_gain_m.min' => 'Tanjakan diisi dalam meter, paling sedikit 1, atau dikosongkan.',
            'durasi_terlama_jam.*' => 'Durasi diisi dalam jam bulat, 1 sampai 24, atau dikosongkan.',
            'preferred_trip_type.required' => 'Pilih jenis trip yang paling sering Anda rencanakan.',
            'preferred_duration.required' => 'Pilih lama pendakian yang Anda sukai.',
            'interest_regions.*' => 'Pilih wilayah dari daftar yang tersedia.',
            'max_elevation_gain_preference_m.*' => 'Batas tanjakan diisi dalam meter, 1 sampai 9.000, atau dikosongkan.',
        ];
    }

    /**
     * Kolom user_experience menurut cara langkah 2. KATALOG membaca jalurnya sekali di sini dan
     * menurunkan nilainya; isian pengalaman dari klien diabaikan untuk cara itu.
     *
     * @return array{completed_hikes_count: int, terrain_experience: list<string>, navigation_experience: string, highest_elevation_gain_m: int|null, longest_hike_duration_minutes: int|null}
     */
    public function pengalaman(): array
    {
        $data = $this->validated();
        $jam = $data['durasi_terlama_jam'] ?? null;
        $durasi = $jam === null ? null : (int) $jam * 60;

        return match ($data['cara_pengalaman']) {
            self::BELUM => [
                'completed_hikes_count' => 0,
                'terrain_experience' => [],
                'navigation_experience' => NavigationExperience::NONE->value,
                'highest_elevation_gain_m' => null,
                'longest_hike_duration_minutes' => null,
            ],
            self::KATALOG => (function () use ($data, $durasi): array {
                $turunan = TurunanPengalaman::dariJalur(Trail::query()->findOrFail($data['jalur_terberat_id']));

                return [
                    'completed_hikes_count' => (int) $data['completed_hikes_count'],
                    'terrain_experience' => $turunan['medan'],
                    'navigation_experience' => $turunan['navigasi'] ?? NavigationExperience::NONE->value,
                    'highest_elevation_gain_m' => $turunan['tanjakan_m'],
                    'longest_hike_duration_minutes' => $durasi,
                ];
            })(),
            default => [
                'completed_hikes_count' => (int) $data['completed_hikes_count'],
                'terrain_experience' => array_values($data['terrain_experience'] ?? []),
                'navigation_experience' => $data['navigation_experience'],
                'highest_elevation_gain_m' => $data['highest_elevation_gain_m'] ?? null,
                'longest_hike_duration_minutes' => $durasi,
            ],
        };
    }

    /**
     * Wilayah minat dalam urutan daftar putih; tanpa pilihan disimpan null, bukan larik kosong.
     *
     * @return list<string>|null
     */
    public function wilayah(): ?array
    {
        $wilayah = array_map(fn (InterestRegion $w) => $w->value, InterestRegion::dari($this->validated('interest_regions')));

        return $wilayah === [] ? null : $wilayah;
    }

    /**
     * Isian kosong dari formulir React datang sebagai string kosong; aturan nullable dan
     * integer membacanya sebagai nilai, bukan ketiadaan.
     */
    protected function prepareForValidation(): void
    {
        $kosong = fn (mixed $v): mixed => $v === '' ? null : $v;

        $this->merge([
            'highest_elevation_gain_m' => $kosong($this->input('highest_elevation_gain_m')),
            'durasi_terlama_jam' => $kosong($this->input('durasi_terlama_jam')),
            'max_elevation_gain_preference_m' => $kosong($this->input('max_elevation_gain_preference_m')),
            'terrain_experience' => array_values((array) $this->input('terrain_experience', [])),
            'interest_regions' => array_values((array) $this->input('interest_regions', [])),
        ]);
    }
}
