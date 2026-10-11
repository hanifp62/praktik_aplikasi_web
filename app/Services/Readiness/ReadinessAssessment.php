<?php

namespace App\Services\Readiness;

use App\Enums\ReadinessState;
use App\Models\TrailConditionReport;

/**
 * Hasil perhitungan readiness sebagai nilai murni, tanpa efek samping.
 *
 * Memisahkan perhitungan dari penyimpanan penting karena halaman readiness dibuka
 * berkali-kali. Sebelumnya tiap kali dibuka sebuah baris baru ditulis, dan baris
 * terbaru menimpa penanda pre_departure_confirmed milik baris sebelumnya, sehingga
 * konfirmasi pengguna hilang begitu halaman dimuat ulang.
 */
readonly class ReadinessAssessment
{
    /**
     * @param  array<string, mixed>  $routeFitSnapshot
     * @param  array<string, mixed>  $preparationState
     * @param  array<string, mixed>  $officialStatusSnapshot
     * @param  array<string, mixed>  $conditionSnapshot
     * @param  array<string, array<int, string>>  $explanation
     */
    public function __construct(
        public ReadinessState $state,
        public array $routeFitSnapshot,
        public array $preparationState,
        public array $officialStatusSnapshot,
        public array $conditionSnapshot,
        public array $explanation,
    ) {}

    /**
     * @return array<string, mixed> atribut untuk disimpan sebagai ReadinessCheck
     */
    public function toAttributes(): array
    {
        return [
            'computed_state' => $this->state->value,
            'route_fit_snapshot' => $this->routeFitSnapshot,
            'preparation_state' => $this->preparationState,
            'official_status_snapshot' => $this->officialStatusSnapshot,
            'condition_snapshot' => $this->minimalConditionSnapshot(),
            'explanation' => $this->explanation,
            'computed_at' => now(),
        ];
    }

    /**
     * `conditionSnapshot` dalam memori membawa laporan komunitas sebagai model
     * `TrailConditionReport` utuh, supaya halaman kesiapan dapat memakai method model
     * (`tags()`, `daysSinceHike()`) selagi tampil. Yang DISIMPAN tidak boleh membawa itu:
     * `#[Fillable]` model tidak membatasi kolom mana yang ikut ter-serialize ke JSON, jadi
     * tanpa minimisasi ini `user_id`, nama pelapor, `note`, `photo_path`, dan `moderated_by`
     * ikut tersalin ke readiness_checks setiap kali pendaki menekan "Simpan hasil cek ini"
     * atau menandai tinjauan (W4-6g butir 1; docs/KEBIJAKAN-DATA-PRIBADI.md bagian 2, 3, 5).
     * Kuncinya sengaja terbatas pada yang "Sejak cek terakhir" dan kartu laporan butuhkan:
     * id, waktu masuk, tanggal pendakian, tanda kondisi, dan nama segmen.
     *
     * @return array<string, mixed>
     */
    private function minimalConditionSnapshot(): array
    {
        $snapshot = $this->conditionSnapshot;
        $reports = $snapshot['community']['reports'] ?? [];

        $snapshot['community']['reports'] = array_map(self::laporanMinimal(...), $reports);

        return $snapshot;
    }

    /**
     * Satu laporan ke kunci minimal: id, created_at, hike_date, condition_tags, dan segmen
     * {id, name}. Satu sumber untuk baris baru (toAttributes) dan pembersihan baris lama
     * (perintah memount:bersihkan-snapshot-kondisi, W4-6h).
     *
     * Gagal-tertutup untuk entri yang bukan model (n-3, NIT-4, Nit E W4-6l): setiap kunci
     * diperiksa jenisnya, dan nilai yang tidak sesuai dibuang, bukan diteruskan. Segmen berupa
     * teks atau objek, id bersarang, atau tanda kondisi yang bukan teks tidak pernah lolos.
     *
     * @return array<string, mixed>
     */
    public static function laporanMinimal(mixed $r): array
    {
        if ($r instanceof TrailConditionReport) {
            return [
                'id' => $r->id,
                'created_at' => $r->created_at?->toIso8601String(),
                'hike_date' => $r->hike_date?->toDateString(),
                'condition_tags' => $r->condition_tags ?? [],
                'segment' => $r->segment ? ['id' => $r->segment->id, 'name' => $r->segment->name] : null,
            ];
        }

        if (! is_array($r)) {
            return [];
        }

        $minimal = [];

        if (is_int($r['id'] ?? null)) {
            $minimal['id'] = $r['id'];
        }

        // Null dipertahankan seperti yang ditulis cabang model, supaya baris minimal tetap
        // identik ketika dibersihkan ulang (perintah pembersihan idempoten).
        foreach (['created_at', 'hike_date'] as $kunci) {
            if (array_key_exists($kunci, $r) && ($r[$kunci] === null || is_string($r[$kunci]))) {
                $minimal[$kunci] = $r[$kunci];
            }
        }

        if (is_array($r['condition_tags'] ?? null)) {
            $minimal['condition_tags'] = array_values(array_filter($r['condition_tags'], 'is_string'));
        }

        if (array_key_exists('segment', $r) && ($r['segment'] === null || is_array($r['segment']))) {
            $minimal['segment'] = $r['segment'] === null ? null : array_filter([
                'id' => is_int($r['segment']['id'] ?? null) ? $r['segment']['id'] : null,
                'name' => is_string($r['segment']['name'] ?? null) ? $r['segment']['name'] : null,
            ], fn ($nilai) => $nilai !== null);
        }

        return $minimal;
    }
}
