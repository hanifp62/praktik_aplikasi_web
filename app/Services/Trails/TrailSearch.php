<?php

namespace App\Services\Trails;

use App\Models\Trail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Pencarian dan penyaringan jalur untuk halaman Jelajah.
 *
 * Dipisahkan dari komponen halaman supaya query yang sama dipakai oleh halaman React
 * dan, selama migrasi, oleh komponen Livewire lama: dua halaman tidak boleh punya dua
 * definisi tentang jalur mana yang boleh tampil. Aturan yang dijaga di sini:
 *
 * - hanya jalur terbit yang masuk hasil (R-022); pencarian nama gunung dikurung supaya
 *   OR-nya tidak menggantung di luar published() dan meloloskan draft;
 * - jalur yang dikenal tetapi belum berdata ditampilkan TERPISAH, tidak dicampur;
 * - data kelengkapan dimuat di muka (withPublishabilityData) supaya penilaian fit tidak
 *   menambah tiga query per baris (§96).
 */
final class TrailSearch
{
    public const PER_HALAMAN = 12;

    public const MAKS_MENUNGGU = 8;

    /**
     * @param  array{search?: string, technical?: string, region?: string, distance?: float|null, duration?: int|null}  $filter
     */
    public function __construct(private array $filter) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function dariInput(array $input): self
    {
        $angka = static fn (mixed $v): ?float => is_numeric($v) ? (float) $v : null;

        return new self([
            'search' => trim((string) ($input['search'] ?? '')),
            'technical' => (string) ($input['technical'] ?? ''),
            'region' => trim((string) ($input['region'] ?? '')),
            'distance' => $angka($input['distance'] ?? null),
            'duration' => $angka($input['duration'] ?? null) === null ? null : (int) $angka($input['duration']),
        ]);
    }

    /**
     * Filter yang sedang berlaku, untuk dikembalikan ke isian halaman.
     *
     * @return array{search: string, technical: string, region: string, distance: float|null, duration: int|null}
     */
    public function filter(): array
    {
        return [
            'search' => $this->filter['search'] ?? '',
            'technical' => $this->filter['technical'] ?? '',
            'region' => $this->filter['region'] ?? '',
            'distance' => $this->filter['distance'] ?? null,
            'duration' => $this->filter['duration'] ?? null,
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Trail>
     */
    public function terbit(): LengthAwarePaginator
    {
        return $this->terapkan(Trail::query()->withPublishabilityData()->published())
            ->paginate(self::PER_HALAMAN)
            ->withQueryString();
    }

    /**
     * Jalur yang dikenal sistem tetapi datanya belum dimasukkan pihak berwenang.
     *
     * @return Collection<int, Trail>
     */
    public function menunggu(): Collection
    {
        return $this->terapkan(Trail::query()->active()->where('is_published', false))
            ->limit(self::MAKS_MENUNGGU)
            ->get();
    }

    /**
     * @param  Builder<Trail>  $query
     * @return Builder<Trail>
     */
    private function terapkan(Builder $query): Builder
    {
        [$search, $technical, $region] = $this->terurai();
        $distance = $this->filter['distance'] ?? null;
        $duration = $this->filter['duration'] ?? null;

        return $query
            ->with('mountain')
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $group) use ($search) {
                    $group->where('name', 'like', "%{$search}%")
                        ->orWhereHas('mountain', fn (Builder $m) => $m->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($technical !== '', fn (Builder $q) => $q->where('technical_demand', $technical))
            ->when($region !== '', function (Builder $q) use ($region) {
                $q->whereHas('mountain', fn (Builder $m) => $m->where('region', $region)->orWhere('province', $region));
            })
            ->when($distance !== null, fn (Builder $q) => $q->where('distance_km', '<=', (float) $distance))
            ->when($duration !== null, fn (Builder $q) => $q->where('estimated_duration_minutes', '<=', (int) $duration * 60))
            ->orderBy('name');
    }

    /**
     * Kalimat sederhana seperti "dekat Malang untuk pemula" diurai menjadi wilayah dan
     * tingkat teknis rendah. Filter eksplisit dari isian selalu menang atas tebakan ini.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function terurai(): array
    {
        $search = $this->filter['search'] ?? '';
        $technical = $this->filter['technical'] ?? '';
        $region = $this->filter['region'] ?? '';

        if ($search === '' || ! preg_match('/(?:\b(?:untuk|bagi|buat)\s+(?:pemula|beginner|novice)\b|\b(?:pemula|beginner|novice)\b)/i', $search)) {
            return [$search, $technical, $region];
        }

        $lokasi = null;

        if (preg_match('/\b(?:dekat|sekitar|di|ke|dari)\s+([a-z0-9\s-]+?)(?=\s+(?:untuk|bagi|buat)\s+(?:pemula|beginner|novice)\b|\s*$)/i', $search, $m)) {
            $lokasi = trim($m[1]);
        } elseif (preg_match('/\b([a-z0-9\s-]+?)\s+(?:untuk|bagi|buat)\s+(?:pemula|beginner|novice)\b/i', $search, $m)) {
            $lokasi = trim($m[1]);
        }

        $alias = match (strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $lokasi)))) {
            'malang', 'surabaya' => 'Jawa Timur',
            'bandung', 'bogor' => 'Jawa Barat',
            'semarang' => 'Jawa Tengah',
            default => null,
        };

        if ($alias === null) {
            return [$search, $technical, $region];
        }

        return ['', $technical !== '' ? $technical : 'LOW', $region !== '' ? $region : $alias];
    }
}
