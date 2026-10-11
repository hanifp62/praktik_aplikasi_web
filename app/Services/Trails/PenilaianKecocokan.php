<?php

namespace App\Services\Trails;

use App\Enums\CompatibilityFactor;
use App\Enums\OfficialStatusValue;
use App\Enums\PreparationStatus;
use App\Enums\RouteFitLabel;
use App\Enums\TerrainCharacter;
use App\Enums\TripStatus;
use App\Models\HikingGoal;
use App\Models\PermitRequirement;
use App\Models\Trail;
use App\Models\TrailConsideration;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\ConsiderationService;
use App\Services\OfficialStatusService;
use App\Services\PermitService;
use App\Services\RouteFit\CompatibilityScorer;
use App\Services\RouteFit\FactorScore;
use App\Services\RouteFit\RouteFitResult;
use App\Services\RouteFitService;
use App\Support\Durasi;
use App\Support\FaktorKategori;
use App\Support\Timezone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;

/**
 * Props halaman penilaian kecocokan (spec v3 bagian 6.5, prototype 05): mengapa sebuah jalur
 * berlabel seperti itu untuk pendaki ini, dalam kata-kata, tanpa satu pun angka skor atau
 * bobot (BR-09, R-005).
 *
 * Tiga lapis mesin tampil sebagai tiga bagian: syarat dasar (lapis 1), tujuh faktor diurut
 * menurut bobotnya (lapis 2 dan 3), dan kalimat "Kenapa bukan Cocok?" yang disusun dari
 * keadaan faktor dengan aturan tertulis, bukan AI. Bobot hanya sampai ke klien sebagai kata
 * pengaruh (besar, sedang, kecil) yang dibaca dari recommendation_rules; keadaan faktor
 * memakai ambang yang sama dengan penjelasan mesin (FaktorKategori). Faktor yang datanya
 * belum ada tertulis "Belum diketahui", tidak pernah "Perlu perhatian" (R-006).
 *
 * Perbaikan putaran 1 (tinjauan opus atas tree ca81822..5889c54): status resmi UNKNOWN
 * punya keadaan sendiri, bukan terhitung syarat yang terpenuhi (P-1, R-006); pembatasan
 * segmen dan status Dibatasi/UNKNOWN sampai ke halaman sebagai "peringatan" dalam kata
 * (P-2, R-005; sejak tinjauan akhir W3 dibentuk RouteFitService::peringatan(), pembentuk
 * yang sama dengan penjelasan mesin, W3-3c); jalur yang gagal syarat dasar tidak lagi mengirim
 * hitungan/daftar faktor (P-3); status dan bobot masing-masing dibaca sekali lalu dioper
 * ke evaluate() (P-4); faktor kritis memakai RouteFitService::CRITICAL_FACTORS langsung,
 * bukan salinan lokal (P-5).
 */
final class PenilaianKecocokan
{
    /** Ambang kata pengaruh (spec v3 bagian 6.5): besar ≥0,25, sedang ≥0,12, kecil di bawahnya. */
    private const PENGARUH_BESAR = 0.25;

    private const PENGARUH_SEDANG = 0.12;

    public function __construct(
        private readonly RouteFitService $mesin,
        private readonly CompatibilityScorer $penilai,
        private readonly OfficialStatusService $statusResmi,
        private readonly ConsiderationService $simpanan,
        private readonly StatusJalurRingkas $statusRingkas,
        private readonly PermitService $izin,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function props(Trail $trail, User $pembaca, ?TripPlan $trip): array
    {
        $zona = Timezone::forTrail($trail);
        $dasar = [
            'jalur' => [
                'id' => $trail->id,
                'nama' => $trail->name,
                'url' => route('trails.show', $trail),
                'gunung' => $trail->mountain->name,
                'gunungUrl' => route('mountains.show', $trail->mountain),
            ],
            // P-7: trip yang sudah final (selesai atau batal) tidak lagi menawarkan
            // persiapan untuk dilanjutkan; persiapannya sudah tidak relevan.
            'aksi' => $trip !== null && $trip->status !== TripStatus::COMPLETED && $trip->status !== TripStatus::CANCELLED
                ? ['label' => 'Lanjutkan persiapan', 'url' => route('trips.preparation', $trip)]
                : ['label' => 'Rencanakan trip', 'url' => route('trips.create', ['trail' => $trail->id])],
        ];

        if (! $pembaca->hasCompletedProfile()) {
            return $dasar + ['penilaian' => null];
        }

        $pembaca->loadMissing(['profile', 'experience', 'preference']);
        $goal = $trip?->tujuanPenilaian();

        // P-4: status dan bobot masing-masing dibaca sekali di sini, lalu dioper ke
        // evaluate() supaya mesin tidak membacanya ulang untuk jalur yang sama.
        // Ringkasan status (StatusJalurRingkas) memakai fungsi kaskade §42 yang sama
        // dengan rincian jalur dan daftar Jelajah (OfficialStatusService::catatanPenentu()),
        // sehingga sumber dan waktu yang dikutip di syaratDasar() selalu sepakat dengan
        // halaman lain untuk jalur yang sama, dengan biaya kueri yang sama dengan
        // effectiveStatusForTrail() sendirian (P-1).
        $ringkasStatus = $this->statusRingkas->untuk(collect([$trail]))[$trail->id];
        $status = $ringkasStatus['status'] !== null ? OfficialStatusValue::from($ringkasStatus['status']) : OfficialStatusValue::UNKNOWN;
        $segmentRestrictions = $this->statusResmi->segmentRestrictionsForTrail($trail);
        $permit = $this->izin->requirementFor($trail);
        $bobot = $this->mesin->weights();
        $hasil = $this->mesin->evaluate($pembaca, $goal, $trail, $bobot, $status, $segmentRestrictions, $permit);
        $faktor = $this->faktor($hasil, $bobot, $trail, $pembaca, $goal);

        return $dasar + [
            'penilaian' => [
                'label' => $hasil->label?->value,
                'labelText' => RouteFitLabel::textFor($hasil->label),
                'definisi' => $hasil->label?->description(),
                'layak' => $hasil->eligible,
                'denganRencana' => $goal !== null,
                'alasanTidakDinilai' => array_map($this->kalimatSyarat(...), $hasil->failedRules),
            ],
            'dinilaiDari' => [
                'profil' => $this->ringkasProfil($pembaca),
                'profilUrl' => route('profile'),
                'rencana' => $trip?->ringkasan(),
                'rencanaUrl' => $trip === null ? null : route('trips.edit', $trip),
            ],
            'syaratDasar' => $this->syaratDasar($hasil, $goal, $status, $ringkasStatus),
            // P-2: status Dibatasi/belum diketahui, pembatasan segmen, dan jendela izin,
            // dalam kata, dekat syarat dasar (lihat peringatan() di akhir kelas).
            'peringatan' => $this->peringatan($status, $segmentRestrictions, $trail, $goal, $permit),
            // P-3: jalur yang gagal syarat dasar tidak diberi hitungan atau daftar
            // keadaan faktor; mesin tetap menghitungnya untuk peringkat kandidat lain,
            // tetapi menampilkannya di sini membuat pembaca menyangka jalur ini sudah
            // dinilai padahal penilaiannya berhenti di lapis 1.
            ...($hasil->eligible ? [
                'faktor' => $faktor,
                'hitungan' => [
                    'sesuai' => count(array_filter($faktor, fn (array $f) => $f['kategori'] === 'kuat')),
                    'perluPerhatian' => count(array_filter($faktor, fn (array $f) => in_array($f['kategori'], ['cukup', 'lemah'], true))),
                    'belumDiketahui' => count(array_filter($faktor, fn (array $f) => $f['kategori'] === 'tak-diketahui')),
                ],
            ] : []),
            'kenapaBukanCocok' => $this->kenapaBukanCocok($hasil, $faktor, $segmentRestrictions),
            'yangBisaDilakukan' => $this->langkah($trail, $pembaca, $trip),
            'dinilai' => Timezone::display(Carbon::now(), $zona, 'j M Y, H.i'),
            'aturan' => 'aturan Route Fit '.str_replace('route-fit-', '', RouteFitService::ENGINE_VERSION),
        ];
    }

    /**
     * Tujuh faktor, diurut menurut bobotnya dari yang paling berpengaruh; seri diputus urutan
     * enum supaya urutannya tetap. Setiap baris membawa kategori (ambang FaktorKategori), kata
     * pengaruh, rincian mesin, dan perbandingan "Jalur ini" lawan "Anda" dalam kata.
     *
     * @param  array<string, float>  $bobot
     * @return array<int, array{kunci: string, label: string, detail: string|null, kategori: string, pengaruh: string, jalurIni: string, anda: string}>
     */
    private function faktor(RouteFitResult $hasil, array $bobot, Trail $trail, User $pembaca, ?HikingGoal $goal): array
    {
        $kategori = FaktorKategori::dari($hasil->factorsToArray());
        $urutan = array_flip(array_map(fn (CompatibilityFactor $f) => $f->value, CompatibilityFactor::cases()));
        $baris = [];

        foreach ($hasil->factors as $i => $faktor) {
            $berat = $bobot[$faktor->factor->value] ?? $faktor->factor->defaultWeight();

            $baris[] = [
                'kunci' => $faktor->factor->value,
                'label' => $this->nama($faktor->factor),
                'detail' => $kategori[$i]['detail'],
                'kategori' => $kategori[$i]['kategori'],
                'pengaruh' => $berat >= self::PENGARUH_BESAR ? 'besar' : ($berat >= self::PENGARUH_SEDANG ? 'sedang' : 'kecil'),
                'jalurIni' => $this->jalurIni($faktor->factor, $trail),
                'anda' => $this->anda($faktor->factor, $pembaca, $goal),
                '_berat' => $berat,
                '_urut' => $urutan[$faktor->factor->value],
            ];
        }

        usort($baris, fn (array $a, array $b) => [$b['_berat'], $a['_urut']] <=> [$a['_berat'], $b['_urut']]);

        return array_map(function (array $b) {
            unset($b['_berat'], $b['_urut']);

            return $b;
        }, $baris);
    }

    private function nama(CompatibilityFactor $faktor): string
    {
        return match ($faktor) {
            CompatibilityFactor::EXPERIENCE_MATCH => 'Pengalaman',
            CompatibilityFactor::DURATION_MATCH => 'Durasi',
            CompatibilityFactor::TERRAIN_MATCH => 'Medan',
            CompatibilityFactor::TECHNICAL_MATCH => 'Tuntutan teknis',
            CompatibilityFactor::ELEVATION_GAIN_MATCH => 'Tanjakan',
            CompatibilityFactor::NAVIGATION_MATCH => 'Navigasi',
            CompatibilityFactor::TRIP_PREFERENCE => 'Jenis trip',
        };
    }

    private function jalurIni(CompatibilityFactor $faktor, Trail $trail): string
    {
        return match ($faktor) {
            CompatibilityFactor::EXPERIENCE_MATCH => $this->bebanFisik($trail),
            CompatibilityFactor::DURATION_MATCH => ucfirst(Durasi::panjang($trail->estimated_duration_minutes)),
            CompatibilityFactor::TERRAIN_MATCH => $trail->terrainTypes() === [] ? 'Belum diketahui' : TrailFigures::medan($trail),
            CompatibilityFactor::TECHNICAL_MATCH => $trail->technical_demand->label(),
            CompatibilityFactor::ELEVATION_GAIN_MATCH => $trail->elevation_gain_m === null ? 'Belum diketahui' : '↑'.number_format($trail->elevation_gain_m, 0, ',', '.').' m',
            CompatibilityFactor::NAVIGATION_MATCH => $trail->navigation_complexity->label(),
            CompatibilityFactor::TRIP_PREFERENCE => $trail->camping_available ? 'Ada area kemah' : 'Tanpa area kemah resmi',
        };
    }

    private function anda(CompatibilityFactor $faktor, User $pembaca, ?HikingGoal $goal): string
    {
        $pengalaman = $pembaca->experience;
        $belum = 'Belum Anda isi';

        return match ($faktor) {
            CompatibilityFactor::EXPERIENCE_MATCH, CompatibilityFactor::TECHNICAL_MATCH => trim(($pembaca->profile?->experience_level?->label() ?? $belum)
                .($pengalaman?->completed_hikes_count !== null ? ', '.$pengalaman->completed_hikes_count.' pendakian selesai' : '')),
            CompatibilityFactor::DURATION_MATCH => $goal?->expected_duration_minutes
                ? 'Target '.Durasi::panjang($goal->expected_duration_minutes)
                : ($pembaca->preference?->preferred_duration?->label() ?? $belum),
            CompatibilityFactor::TERRAIN_MATCH => ($pengalaman?->terrain_experience ?? []) === []
                ? $belum
                : collect($pengalaman->terrain_experience)->map(fn (string $t) => TerrainCharacter::tryFrom($t)?->label())->filter()->join(', '),
            CompatibilityFactor::ELEVATION_GAIN_MATCH => $pengalaman?->highest_elevation_gain_m !== null
                ? 'Tertinggi '.number_format($pengalaman->highest_elevation_gain_m, 0, ',', '.').' m'
                : $belum,
            CompatibilityFactor::NAVIGATION_MATCH => $pengalaman?->navigation_experience?->label() ?? $belum,
            CompatibilityFactor::TRIP_PREFERENCE => ($goal?->trip_type ?? $pembaca->preference?->preferred_trip_type)?->label() ?? $belum,
        };
    }

    private function bebanFisik(Trail $trail): string
    {
        $beban = $this->penilai->physicalDemand($trail);

        if (! $beban['known']) {
            return 'Belum diketahui';
        }

        return 'Beban fisik '.match ($beban['rank']) {
            1 => 'ringan',
            2 => 'sedang',
            3 => 'berat',
            default => 'sangat berat',
        };
    }

    /**
     * Lapis 1 dalam kata. Setiap syarat punya tiga keadaan yang mungkin: terpenuhi,
     * gagal, atau tak-diketahui (perbaikan P-1, R-006). Status resmi UNKNOWN bukan Tutup,
     * tetapi juga bukan sesuatu yang sudah dipastikan terpenuhi: menandainya terpenuhi
     * membuat pembaca layar mendengar "Terpenuhi" untuk sesuatu yang justru belum
     * diketahui siapa pun. Kalimat statusnya menyebut sumber dan waktu catatan penentu
     * (StatusJalurRingkas, kaskade §42 tunggal) supaya sepakat dengan rincian jalur dan
     * daftar Jelajah, atau mengaku belum ada catatan bila memang tidak ada. Syarat rencana
     * hanya muncul bila ada rencana yang membawanya.
     *
     * @param  array{sumber: array{jenis: string|null, label: string|null, nama: string|null}|null, waktu: string|null, awalanWaktu: string|null}  $ringkasStatus
     * @return array<int, array{teks: string, keadaan: string}>
     */
    private function syaratDasar(RouteFitResult $hasil, ?HikingGoal $goal, OfficialStatusValue $status, array $ringkasStatus): array
    {
        $gagal = fn (string $aturan) => in_array($aturan, $hasil->failedRules, true);

        $keadaanStatus = match (true) {
            $status === OfficialStatusValue::CLOSED => 'gagal',
            $status === OfficialStatusValue::UNKNOWN => 'tak-diketahui',
            default => 'terpenuhi',
        };

        // Waktu bersama awalannya dari kolom yang dipakai (W3-2c), dengan tahun bila bukan
        // tahun berjalan (m2): halaman ini mengutipnya tanpa lencana kesegaran.
        $sumberWaktu = $ringkasStatus['sumber'] !== null
            ? 'Menurut '.($ringkasStatus['sumber']['nama'] ?? 'sumber tidak tercatat')
                .($ringkasStatus['waktu'] !== null ? ', '.mb_strtolower((string) $ringkasStatus['awalanWaktu']).' '.$ringkasStatus['waktu'] : '').'.'
            : 'Belum ada catatan pengelola yang berlaku.';

        $syarat = [
            ['teks' => 'Status resmi tidak Tutup (sekarang '.$status->label().'). '.$sumberWaktu, 'keadaan' => $keadaanStatus],
            ['teks' => 'Data kritis jalur lengkap: sumber, jarak, tanjakan, durasi, pos, status resmi, dan garis peta', 'keadaan' => $gagal('trail_data_incomplete') ? 'gagal' : 'terpenuhi'],
        ];

        if ($goal?->trip_type !== null) {
            $syarat[] = ['teks' => 'Lama perjalanan sesuai jenis trip ('.mb_strtolower($goal->trip_type->label()).')', 'keadaan' => $gagal('duration_requires_overnight') ? 'gagal' : 'terpenuhi'];
        }

        if ($goal?->max_elevation_gain_m !== null) {
            $syarat[] = ['teks' => 'Tanjakan tidak melebihi batas yang Anda tentukan', 'keadaan' => $gagal('elevation_gain_above_goal_limit') ? 'gagal' : 'terpenuhi'];
        }

        return $syarat;
    }

    /**
     * "Kenapa bukan Cocok?" dari keadaan faktor, dengan aturan yang sama dengan mesin: faktor
     * kritis yang belum diketahui atau jauh di bawah ambang menahan label di Kurang cocok, dan
     * faktor yang belum diketahui tidak pernah menaikkan label (§95). Null bila labelnya Cocok
     * atau jalurnya tidak dinilai.
     *
     * Perbaikan P-2: pembatasan segmen juga dapat menurunkan Cocok ke Perlu persiapan
     * (RouteFitService::label()) walau ketujuh faktor sudah kuat; sebab sebenarnya itu
     * disebut di sini, bukan tertutup kalimat generik "Tidak ada syarat dasar yang gagal".
     *
     * @param  array<int, array<string, string|null>>  $faktor
     * @param  array<int, array{segment: string, status: OfficialStatusValue, reason: ?string}>  $segmentRestrictions
     */
    private function kenapaBukanCocok(RouteFitResult $hasil, array $faktor, array $segmentRestrictions): ?string
    {
        if ($hasil->label === null || $hasil->label === RouteFitLabel::COCOK) {
            return null;
        }

        $perhatian = array_values(array_filter($faktor, fn (array $f) => in_array($f['kategori'], ['cukup', 'lemah'], true)));
        $belum = array_values(array_filter($faktor, fn (array $f) => $f['kategori'] === 'tak-diketahui'));
        $besar = array_values(array_filter($perhatian, fn (array $f) => $f['pengaruh'] === 'besar'));
        $kalimat = [];

        if ($perhatian !== []) {
            $kalimat[] = count($perhatian).' faktor perlu perhatian'
                .($besar !== [] ? ', dan '.mb_strtolower($besar[0]['label']).' berpengaruh besar' : '').'.';
        }

        if ($belum !== []) {
            $kalimat[] = count($belum).' faktor belum diketahui, dan faktor yang belum diketahui tidak pernah menaikkan label.';
        }

        $lantai = (float) config('hiking.route_fit.critical_factor_floor');
        $kritis = array_values(array_filter($hasil->factors, fn (FactorScore $f) => in_array($f->factor, RouteFitService::CRITICAL_FACTORS, true) && ($f->isUnknown || $f->score < $lantai)));

        // Skor mentah tidak pernah sampai ke halaman (R-005); dipakai di sini hanya untuk
        // memutuskan kalimat mana yang ditulis, persis ambang yang dipakai
        // RouteFitService::label() sendiri untuk menurunkan Cocok bila ada pembatasan segmen.
        $lolosAmbangCocok = $hasil->internalScore >= ((float) config('hiking.route_fit.label_threshold_fit')) * 100;

        if ($hasil->label === RouteFitLabel::KURANG_COCOK && $kritis !== []) {
            $kalimat[] = 'Faktor kritis ('.implode(', ', array_map(fn (FactorScore $f) => mb_strtolower($this->nama($f->factor)), $kritis)).') menahan label di Kurang cocok, berapa pun faktor lainnya.';
        } elseif ($hasil->label === RouteFitLabel::PERLU_PERSIAPAN && $perhatian === [] && $belum === [] && $lolosAmbangCocok && $segmentRestrictions !== []) {
            $daftar = implode(', ', array_map(fn (array $r) => $r['segment'].' ('.$r['status']->label().')', $segmentRestrictions));
            $kalimat[] = 'Segmen '.$daftar.' menahan label naik ke Cocok, walau ketujuh faktor sudah sesuai.';
        } elseif ($hasil->label === RouteFitLabel::PERLU_PERSIAPAN) {
            $kalimat[] = 'Tidak ada syarat dasar yang gagal, jadi jalur ini tetap relevan asal Anda bersiap.';
        } else {
            $kalimat[] = 'Gabungan faktornya belum cukup untuk Perlu persiapan.';
        }

        return implode(' ', $kalimat);
    }

    /**
     * Yang bisa dilakukan pendaki (spec v3 bagian 6.5): butir persiapan kritis bila ada trip,
     * saran pendamping yang tidak mengubah label, dan banding dengan simpanannya sendiri.
     *
     * @return array<int, array{judul: string, keterangan: string, url: string|null}>
     */
    private function langkah(Trail $trail, User $pembaca, ?TripPlan $trip): array
    {
        $langkah = [];

        if ($trip !== null) {
            $kritis = $trip->preparationItems()
                ->where('is_critical', true)
                ->where('status', PreparationStatus::NOT_CONFIRMED->value)
                ->orderBy('id')
                ->pluck('label');

            if ($kritis->isNotEmpty()) {
                $langkah[] = [
                    'judul' => 'Selesaikan '.$kritis->count().' butir persiapan kritis',
                    'keterangan' => $kritis->take(3)->join(', '),
                    'url' => route('trips.preparation', $trip),
                ];
            }
        }

        $langkah[] = [
            'judul' => 'Mendaki bersama yang sudah pernah lewat jalur ini',
            'keterangan' => 'Pengalaman pendamping tidak mengubah label, tetapi mengurangi yang perlu Anda tebak di jalur.',
            'url' => null,
        ];

        $lain = $this->simpanan->simpanan($pembaca)
            ->map(fn (TrailConsideration $baris) => $baris->trail)
            ->filter(fn (Trail $t) => $t->is_published && $t->id !== $trail->id)
            ->take(2)
            ->values();

        if ($lain->isNotEmpty()) {
            $langkah[] = [
                'judul' => 'Bandingkan dengan jalur lain',
                'keterangan' => $lain->pluck('name')->join(' dan ').' ada di simpanan Anda.',
                'url' => route('trails.compare', ['trails' => $lain->pluck('id')->prepend($trail->id)->join(',')]),
            ];
        }

        return $langkah;
    }

    private function ringkasProfil(User $pembaca): string
    {
        $pengalaman = $pembaca->experience;

        return implode(', ', array_filter([
            $pembaca->profile?->experience_level?->label(),
            $pengalaman?->completed_hikes_count !== null ? $pengalaman->completed_hikes_count.' pendakian selesai' : null,
            $pengalaman?->highest_elevation_gain_m !== null ? 'tanjakan tertinggi '.number_format($pengalaman->highest_elevation_gain_m, 0, ',', '.').' m' : null,
            $pengalaman?->longest_hike_duration_minutes ? 'terlama '.Durasi::panjang($pengalaman->longest_hike_duration_minutes) : null,
        ]));
    }

    private function kalimatSyarat(string $aturan): string
    {
        return Lang::has('recommendation.'.$aturan) ? __('recommendation.'.$aturan) : 'Tidak memenuhi syarat dasar untuk ditawarkan.';
    }

    /**
     * Peringatan yang tidak menggagalkan syarat dasar tetapi dapat menahan label naik
     * (perbaikan P-2, spec v3 bagian 6.5): status Dibatasi atau belum diketahui,
     * pembatasan per segmen, dan jendela izin.
     *
     * Sejak tinjauan akhir W3 (W3-3c) kalimatnya dibentuk RouteFitService::peringatan(),
     * pembentuk yang sama dengan penjelasan mesin, bukan salinan di sini. Kata statusnya
     * sudah lewat OfficialStatusValue::label() di sana, jadi tidak ada nilai enum mentah
     * yang sampai ke halaman (R-005). Fakta yang dioper sama dengan yang dibaca props():
     * status ringkas, pembatasan segmen, dan aturan izin, masing-masing dibaca sekali.
     *
     * @param  array<int, array{segment: string, status: OfficialStatusValue, reason: ?string}>  $segmentRestrictions
     * @return array<int, string>
     */
    private function peringatan(OfficialStatusValue $status, array $segmentRestrictions, Trail $trail, ?HikingGoal $goal, ?PermitRequirement $permit): array
    {
        return $this->mesin->peringatan($trail, $status, $segmentRestrictions, $goal, $permit);
    }
}
