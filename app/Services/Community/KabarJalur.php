<?php

namespace App\Services\Community;

use App\Enums\ConditionTag;
use App\Enums\FreshnessState;
use App\Enums\OfficialStatusValue;
use App\Models\Mountain;
use App\Models\OfficialStatus;
use App\Models\Trail;
use App\Models\TrailConditionReport;
use App\Models\User;
use App\Models\WeatherSnapshot;
use App\Services\ConditionAggregatorService;
use App\Services\TrailNewsService;
use App\Services\WeatherService;
use App\Support\Timezone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Halaman Kabar dalam tiga kelompok menurut asalnya: status resmi, prakiraan cuaca,
 * dan laporan komunitas (PRD §92). Urutannya mengikuti hierarki otoritas (R-009):
 * yang resmi dibaca lebih dulu, dan laporan komunitas tidak pernah tampil sebagai
 * pengganti status resmi (R-010).
 *
 * Isinya tetap milik TrailNewsService; kelas ini hanya memilah dan memformat, sehingga
 * aturan yang sudah diuji di sana (gunung yang diikuti saja, laporan yang lolos
 * moderasi saja) tidak ditulis dua kali.
 *
 * Cuaca dibaca dari snapshot yang sudah tersimpan, tidak pernah dengan memanggil BMKG
 * saat halaman dibuka. Tanpa snapshot, kalimatnya mengaku tidak tersedia; snapshot
 * yang sudah lama tidak ditampilkan sebagai prakiraan terkini (R-013).
 */
class KabarJalur
{
    /** Perubahan status resmi dan laporan komunitas yang diringkas di Beranda. */
    private const RINGKAS_RESMI = 2;

    private const RINGKAS_LAPORAN = 1;

    public function __construct(
        private readonly TrailNewsService $kabar,
        private readonly WeatherService $cuaca,
        private readonly ConditionAggregatorService $kondisi,
    ) {}

    /**
     * @return array{mengikuti: ?string, resmi: array<int, array<string, mixed>>, cuaca: array<int, array<string, mixed>>, komunitas: array<int, array<string, mixed>>}
     */
    public function untuk(User $user): array
    {
        $gunung = $this->kabar->followedMountains($user);

        if ($gunung->isEmpty()) {
            return ['mengikuti' => null, 'resmi' => [], 'cuaca' => [], 'komunitas' => []];
        }

        $butir = $this->kabar->forUser($user);

        return [
            'mengikuti' => $gunung->pluck('name')->join(', ', ' dan '),
            'resmi' => $butir->where('jenis', TrailNewsService::STATUS)
                ->map(fn (array $b) => $this->resmi($b))
                ->values()
                ->all(),
            'cuaca' => $this->cuacaGunung($gunung),
            'komunitas' => $butir->where('jenis', TrailNewsService::LAPORAN)
                ->map(fn (array $b) => $this->komunitas($b))
                ->values()
                ->all(),
        ];
    }

    /**
     * Ringkasan Kabar untuk Beranda (spec v3 bagian 6.1, prototype 01): perubahan status resmi
     * terbaru dan laporan komunitas terbaru dari gunung yang diikuti, tetap terpisah menurut
     * asalnya (R-010) dan diformat sama dengan halaman Kabar. Cuaca tidak ikut; ia tinggal di
     * halaman Kabar. Null bila pendaki tidak mengikuti gunung apa pun.
     *
     * @return array{gunung: array<int, string>, resmi: array<int, array<string, mixed>>, komunitas: array<int, array<string, mixed>>}|null
     */
    public function ringkas(User $user): ?array
    {
        $gunung = $this->kabar->followedMountains($user);

        if ($gunung->isEmpty()) {
            return null;
        }

        $terbaru = $this->kabar->terbaru($user, self::RINGKAS_RESMI, self::RINGKAS_LAPORAN);

        return [
            'gunung' => $gunung->pluck('name', 'id')->all(),
            'resmi' => $terbaru['resmi']->map(fn (array $b) => $this->resmi($b))->values()->all(),
            'komunitas' => $terbaru['laporan']->map(fn (array $b) => $this->komunitas($b))->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $butir
     * @return array<string, mixed>
     */
    private function resmi(array $butir): array
    {
        /** @var OfficialStatus $status */
        $status = $butir['model'];

        return [
            'id' => $status->id,
            'judul' => $butir['judul'],
            // UNKNOWN tidak diberi warna apa pun: ia bukan keadaan baik maupun buruk (R-006).
            'status' => $status->status === OfficialStatusValue::UNKNOWN ? null : $status->status?->value,
            'statusLabel' => $status->status?->label(),
            'cakupan' => $status->scope?->label(),
            'alasan' => $butir['alasan'],
            'sumber' => $butir['sumber'] ?? 'belum tercatat',
            // Jenis sumber dari data_sources (R-009, P-2): label "Resmi" tidak lagi
            // ditulis tetap untuk setiap baris di TrailNews.tsx, mengikuti LabelSumber.
            'jenisSumber' => $status->dataSource?->source_type?->value,
            'waktu' => $butir['waktu']->diffForHumans(),
            'waktuIso' => $butir['waktu']->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $butir
     * @return array<string, mixed>
     */
    private function komunitas(array $butir): array
    {
        /** @var TrailConditionReport $laporan */
        $laporan = $butir['model'];

        // Kesegaran laporan diukur dari tanggal pendakiannya, bukan dari tanggal kirim
        // (PRD §51, §59), karena itulah yang menentukan seberapa baru keadaan jalurnya.
        $kesegaran = $this->kondisi->reportFreshness($laporan);

        return [
            'id' => $laporan->id,
            'judul' => $butir['judul'],
            'jalurSlug' => $laporan->trail?->slug,
            'tag' => array_map(fn (ConditionTag $tag) => $tag->label(), $laporan->tags()),
            'catatan' => $laporan->note,
            'pelapor' => $laporan->user?->name ?? 'pendaki yang akunnya sudah dihapus',
            'tanggalPendakian' => $laporan->hike_date?->translatedFormat('d M Y'),
            'kesegaran' => $kesegaran->label(),
            'kesegaranLama' => $kesegaran !== FreshnessState::CURRENT,
            'waktu' => $butir['waktu']->diffForHumans(),
            'waktuIso' => $butir['waktu']->toIso8601String(),
            'terimaKasih' => (int) $laporan->thanks_count,
            'foto' => filled($laporan->photo_path),
        ];
    }

    /**
     * Prakiraan per wilayah acuan untuk setiap gunung yang diikuti.
     *
     * Tiga query berapa pun jumlah gunung dan jalurnya: jalur beserta kode wilayahnya,
     * waktu pengambilan terakhir per wilayah, dan prakiraan di sekitar jam ini.
     *
     * @param  Collection<int, Mountain>  $gunung
     * @return array<int, array<string, mixed>>
     */
    private function cuacaGunung(Collection $gunung): array
    {
        $jalur = Trail::query()
            ->active()
            ->whereIn('mountain_id', $gunung->pluck('id'))
            ->orderBy('name')
            ->get(['id', 'mountain_id', 'name', 'weather_adm4_code', 'weather_reference_area']);

        $kode = $jalur->pluck('weather_adm4_code')->filter()->unique()->values();

        $terakhir = $kode->isEmpty() ? collect() : WeatherSnapshot::query()
            ->whereIn('adm4_code', $kode)
            ->groupBy('adm4_code')
            ->selectRaw('adm4_code, MAX(fetched_at) AS terakhir')
            ->pluck('terakhir', 'adm4_code');

        $prakiraan = $kode->isEmpty() ? collect() : WeatherSnapshot::query()
            ->whereIn('adm4_code', $kode)
            ->whereBetween('forecast_at', [now()->subHours(3), now()->addHours(3)])
            ->orderBy('forecast_at')
            ->get()
            ->groupBy('adm4_code');

        return $gunung->map(function (Mountain $m) use ($jalur, $terakhir, $prakiraan) {
            $zona = Timezone::isSupported($m->timezone) ? $m->timezone : Timezone::DEFAULT;

            $wilayah = $jalur->where('mountain_id', $m->id)
                ->filter(fn (Trail $t) => filled($t->weather_adm4_code))
                ->groupBy('weather_adm4_code')
                ->map(fn (Collection $jalurWilayah, int|string $kode) => $this->wilayah(
                    (string) $kode,
                    $jalurWilayah,
                    $terakhir->get($kode),
                    $prakiraan->get($kode, collect()),
                    $zona,
                ))
                ->values()
                ->all();

            return [
                'gunung' => $m->name,
                'wilayah' => $wilayah,
                'pesan' => $wilayah === [] ? 'Data cuaca tidak tersedia.' : null,
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, Trail>  $jalurWilayah
     * @param  Collection<int, WeatherSnapshot>  $prakiraan
     * @return array<string, mixed>
     */
    private function wilayah(string $kode, Collection $jalurWilayah, ?string $terakhir, Collection $prakiraan, string $zona): array
    {
        $dasar = [
            'kode' => $kode,
            'area' => $jalurWilayah->first()->weather_reference_area ?? $kode,
            'jalur' => $jalurWilayah->pluck('name')->join(', '),
            'sumber' => WeatherService::SOURCE,
            'ringkasan' => null,
            'waktuPrakiraan' => null,
            'diperbarui' => null,
            'kesegaran' => FreshnessState::UNKNOWN->label(),
            'kesegaranLama' => true,
            'pesan' => 'Data cuaca tidak tersedia.',
        ];

        if ($terakhir === null) {
            return $dasar;
        }

        $diambil = Carbon::parse($terakhir);
        $kesegaran = $this->cuaca->freshness($diambil);
        $diperbarui = Timezone::display($diambil, $zona);

        $dasar['diperbarui'] = $diperbarui;
        $dasar['kesegaran'] = $kesegaran->label();
        $dasar['kesegaranLama'] = $kesegaran !== FreshnessState::CURRENT;

        // Snapshot yang sudah lama tidak dibaca sebagai cuaca saat ini: angkanya disimpan,
        // kalimatnya menyebut kapan data terakhir tersedia (sama dengan halaman jalur).
        $terdekat = $prakiraan->sortBy(fn (WeatherSnapshot $s) => abs($s->forecast_at->diffInMinutes(now())))->first();

        if ($kesegaran === FreshnessState::STALE || $terdekat === null) {
            $dasar['pesan'] = 'Data cuaca belum berhasil diperbarui. Data terakhir tersedia pada '.$diperbarui.'.';

            return $dasar;
        }

        $dasar['sumber'] = $terdekat->source ?: WeatherService::SOURCE;
        $dasar['area'] = $terdekat->reference_area ?? $dasar['area'];
        $dasar['waktuPrakiraan'] = 'Prakiraan pukul '.Timezone::display($terdekat->forecast_at, $zona, 'H.i');
        $dasar['ringkasan'] = implode(' · ', array_filter([
            $terdekat->weather_description,
            $terdekat->temperature_c !== null ? number_format((float) $terdekat->temperature_c, 0, ',', '.').' °C' : null,
            $terdekat->wind_speed_kmh !== null ? 'angin '.number_format((float) $terdekat->wind_speed_kmh, 0, ',', '.').' km/jam' : null,
        ]));
        $dasar['pesan'] = null;

        return $dasar;
    }
}
