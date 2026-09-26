<?php

namespace App\Http\Controllers\Community;

use App\Enums\NavigationComplexity;
use App\Enums\SourceType;
use App\Enums\TechnicalDemand;
use App\Enums\TerrainCharacter;
use App\Enums\WaterAvailability;
use App\Exceptions\PublicationGateViolation;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrailContributionRequest;
use App\Models\DataSource;
use App\Models\ExpertCredential;
use App\Models\Trail;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tempat pemandu bersertifikat mengisi data jalur di kawasan yang disahkan untuknya
 * (PRD §43, §109).
 *
 * Yang sengaja TIDAK ada di sini: menerbitkan, mengarsipkan, dan membuat jalur baru.
 * Ahli menyiapkan datanya, admin memutuskan data itu layak dilihat pendaki. Gerbang
 * §110 tidak berpindah tangan.
 */
class TrailContributionController extends Controller
{
    private const KARAKTERISTIK = [
        'distance_km', 'elevation_gain_m', 'elevation_loss_m', 'estimated_duration_minutes',
        'technical_demand', 'navigation_complexity', 'water_availability', 'terrain_character',
        'camping_available', 'starting_point', 'description',
    ];

    public function index(Request $request): Response
    {
        $kredensial = $this->kredensialAktif($request->user());

        // Terbuka hanya untuk pemegang kredensial yang berlaku, sama seperti mount() lama.
        abort_if($kredensial->isEmpty(), 403);

        // Formulir dibuka lewat ?isi={id} supaya otorisasinya tetap di server: policy
        // yang sama dengan penyimpanan, bukan daftar jalur yang dikirim ke klien.
        $isian = null;

        if ($request->filled('isi')) {
            $jalur = Trail::findOrFail($request->integer('isi'));
            $this->authorize('update', $jalur);
            $isian = $this->isian($jalur);
        }

        $gunungId = $kredensial->flatMap(fn (ExpertCredential $k) => $k->mountains->pluck('id'))->unique();

        return Inertia::render('Community/TrailContribution', [
            'title' => 'Kontribusi Data Jalur',
            'kredensial' => $kredensial->map(fn (ExpertCredential $k) => [
                'id' => $k->id,
                'jenjang' => $k->level->label(),
                'penerbit' => $k->issuingAuthority?->displayName(),
                'berlakuSampai' => $k->expires_at?->translatedFormat('d M Y'),
                'kawasan' => $k->mountains->pluck('name')->join(', '),
            ])->values()->all(),
            'trails' => Trail::query()
                ->active()
                ->whereIn('mountain_id', $gunungId)
                // Hitungan pos dan status dimuat sekaligus: awaitingData() membaca dari
                // sini alih-alih bertanya ke basis data untuk setiap jalur.
                ->withPublishabilityData()
                ->with('mountain')
                // Yang datanya belum lengkap didahulukan: itu yang paling butuh diisi.
                ->orderBy('is_published')
                ->orderBy('name')
                ->get()
                ->map(fn (Trail $t) => [
                    'id' => $t->id,
                    'nama' => $t->name,
                    'gunung' => $t->mountain?->name,
                    'menunggu' => $t->awaitingData(),
                    'terbit' => $t->is_published,
                ])
                ->values()
                ->all(),
            'isian' => $isian,
            'pilihan' => [
                'teknis' => $this->opsi(TechnicalDemand::cases()),
                'navigasi' => $this->opsi(NavigationComplexity::cases()),
                'air' => $this->opsi(WaterAvailability::cases()),
                'medan' => $this->opsi(TerrainCharacter::cases()),
            ],
        ]);
    }

    public function store(StoreTrailContributionRequest $request, Trail $trail, AuditLogService $audit): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $kredensial = $user->usableTrailCredentialFor($trail->mountain_id);

        // Penjagaan kedua. Policy sudah memeriksanya, tetapi kredensial dapat kedaluwarsa
        // atau dicabut di antara membuka formulir dan menekan simpan.
        if ($kredensial === null) {
            return back()->withErrors(['form' => 'Kredensial Anda tidak lagi berlaku untuk kawasan ini.']);
        }

        $sebelum = $trail->only(self::KARAKTERISTIK);

        // Satu transaksi: bila gerbang publikasi menolak, sumber data yang baru dibuat
        // dan jejak audit ikut batal, jadi tidak ada sisa setengah jadi.
        try {
            DB::transaction(fn () => $this->simpan($trail, $data, $kredensial, $user, $sebelum, $audit));
        } catch (PublicationGateViolation $galat) {
            // Jalur yang sudah terbit tidak boleh kehilangan data kritisnya (R-022). Dulu
            // penolakan ini lolos sebagai galat 500; kini ahli diberi tahu apa yang kurang.
            return back()->withErrors([
                'form' => 'Perubahan ditolak karena jalur yang sudah tayang harus tetap memenuhi syarat publikasi. '
                    .implode(' ', $galat->missing).' Isi kembali data itu, atau minta pengelola menurunkan jalur ke draf.',
            ]);
        }

        return redirect()->route('contribute')->with('status', 'Data tersimpan. Jalur ini belum tayang; admin yang memutuskan '
            .'kelayakannya setelah seluruh syarat publikasi terpenuhi.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $sebelum
     */
    private function simpan(Trail $trail, array $data, ExpertCredential $kredensial, User $user, array $sebelum, AuditLogService $audit): void
    {
        $trail->update([
            'distance_km' => $data['distance_km'] ?? null,
            'elevation_gain_m' => $data['elevation_gain_m'] ?? null,
            'elevation_loss_m' => $data['elevation_loss_m'] ?? null,
            'estimated_duration_minutes' => $data['estimated_duration_minutes'] ?? null,
            'technical_demand' => $data['technical_demand'],
            'navigation_complexity' => $data['navigation_complexity'],
            'water_availability' => $data['water_availability'],
            'terrain_character' => $data['terrain_character'] ?? [],
            'camping_available' => (bool) ($data['camping_available'] ?? false),
            'starting_point' => $data['starting_point'] ?? null,
            'description' => $data['description'] ?? null,
            'data_source_id' => $this->sumberUntuk($kredensial, $data['catatan_sumber'])->id,
        ]);

        // Siapa menyumbang atas dasar sertifikat mana adalah fakta yang harus tersimpan,
        // bukan disimpulkan belakangan (§60).
        $trail->forceFill([
            'contributed_by' => $user->id,
            'contributed_credential_id' => $kredensial->id,
            'contributed_at' => now(),
        ])->save();

        $audit->record($user, 'trail.contributed', $trail, $sebelum, [
            'credential_id' => $kredensial->id,
            'level' => $kredensial->level->value,
            'catatan_sumber' => $data['catatan_sumber'],
        ]);
    }

    /**
     * Sumber data atas nama ahli yang menyumbang, bukan atas nama aplikasi.
     */
    private function sumberUntuk(ExpertCredential $kredensial, string $catatanSumber): DataSource
    {
        return DataSource::updateOrCreate(
            ['source_name' => 'Kontribusi ahli: '.$kredensial->user->name],
            [
                'source_type' => SourceType::ACCREDITED_EXPERT->value,
                'source_owner' => $kredensial->issuingAuthority->displayName(),
                'retrieved_at' => now(),
                'verified_at' => $kredensial->verified_at,
                'freshness_policy' => 'Data lapangan berubah mengikuti musim dan kondisi jalur. '
                    .'Periksa ulang bersama kontributor secara berkala.',
                'notes' => $kredensial->level->label()
                    .($kredensial->certificate_number ? ', sertifikat '.$kredensial->certificate_number : '')
                    .'. '.$catatanSumber,
            ]
        );
    }

    /**
     * @return Collection<int, ExpertCredential>
     */
    private function kredensialAktif(User $user): Collection
    {
        return $user->expertCredentials()
            ->usable()
            ->with('mountains', 'issuingAuthority')
            ->get()
            ->filter(fn (ExpertCredential $k) => $k->mayContributeTrailData())
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function isian(Trail $jalur): array
    {
        return [
            'id' => $jalur->id,
            'nama' => $jalur->name,
            'distance_km' => $jalur->distance_km !== null ? (string) (float) $jalur->distance_km : '',
            'elevation_gain_m' => $jalur->elevation_gain_m !== null ? (string) $jalur->elevation_gain_m : '',
            'elevation_loss_m' => $jalur->elevation_loss_m !== null ? (string) $jalur->elevation_loss_m : '',
            'estimated_duration_minutes' => $jalur->estimated_duration_minutes !== null ? (string) $jalur->estimated_duration_minutes : '',
            'technical_demand' => $jalur->technical_demand?->value ?? '',
            'navigation_complexity' => $jalur->navigation_complexity?->value ?? '',
            'water_availability' => $jalur->water_availability?->value ?? '',
            'terrain_character' => $jalur->terrain_character ?? [],
            'camping_available' => (bool) $jalur->camping_available,
            'starting_point' => $jalur->starting_point ?? '',
            'description' => $jalur->description ?? '',
            'catatan_sumber' => '',
        ];
    }

    /**
     * @param  array<int, \BackedEnum>  $kasus
     * @return array<int, array{value: string, label: string}>
     */
    private function opsi(array $kasus): array
    {
        return array_map(fn ($k) => ['value' => $k->value, 'label' => $k->label()], $kasus);
    }
}
