<?php

namespace App\Http\Resources;

use App\Enums\RouteFitLabel;
use App\Enums\TechnicalDemand;
use App\Models\RecommendationResult;
use App\Support\Durasi;
use App\Support\FaktorKategori;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu baris hasil rekomendasi, sebagaimana dilihat pendaki.
 *
 * Skor internal, bobot, dan skor per faktor berhenti di sini (BR-09): yang dikirim hanya
 * label publik, kategori faktor, alasan, dan angka yang sudah diformat. Angkanya diambil
 * dari snapshot, yaitu jalur sebagaimana dipakai menilai, bukan dari tabel jalur
 * sekarang: menampilkan angka terkini di sebelah label historis membuat pendaki
 * menyimpulkan label itu dihitung dari angka yang sedang ia baca.
 *
 * @mixin RecommendationResult
 */
class RecommendationResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $angka = $this->trailFigures();
        $ditarik = $this->trail->archived_at !== null;

        return [
            'id' => $this->id,
            'trailId' => $this->trail_id,
            'name' => $this->trail->name,
            'mountain' => $this->trail->mountain?->name,
            // Run ber-URL permanen, jadi jalur dapat diarsipkan setelah run dibuat.
            // Barisnya tetap ada (run adalah catatan), tautannya dicabut: halaman jalur
            // terarsip menjawab 404.
            'url' => $ditarik ? null : route('trails.show', $this->trail),
            'ditarik' => $ditarik,
            'eligible' => (bool) $this->eligible,
            'label' => $this->label?->value,
            'labelText' => RouteFitLabel::textFor($this->label),
            'angka' => [
                'jarak' => isset($angka['distance_km']) ? number_format((float) $angka['distance_km'], 1, ',', '.').' km' : '-',
                'tanjakan' => isset($angka['elevation_gain_m']) ? number_format((int) $angka['elevation_gain_m'], 0, ',', '.').' m' : '-',
                'durasi' => ! empty($angka['estimated_duration_minutes']) ? Durasi::panjang((int) $angka['estimated_duration_minutes']) : '-',
                'teknis' => ! empty($angka['technical_demand']) ? TechnicalDemand::from($angka['technical_demand'])->label() : '-',
            ],
            'berubah' => $this->trailHasChangedSinceRun(),
            'warnings' => array_values((array) ($this->warnings ?? [])),
            'faktor' => FaktorKategori::dari($this->matched_factors),
            'penjelasan' => [
                'why_it_fits' => array_values((array) ($this->explanation['why_it_fits'] ?? [])),
                'what_to_watch' => array_values((array) ($this->explanation['what_to_watch'] ?? [])),
                'preparation_gap' => array_values((array) ($this->explanation['preparation_gap'] ?? [])),
            ],
        ];
    }
}
