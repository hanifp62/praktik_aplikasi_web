<?php

namespace App\Http\Resources;

use App\Enums\FreshnessState;
use App\Enums\OfficialStatusValue;
use App\Enums\RouteFitLabel;
use App\Models\ReadinessCheck;
use App\Support\Timezone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Hasil perhitungan kesiapan, siap tampil.
 *
 * Yang dibungkus adalah ReadinessCheck yang TIDAK disimpan: membuka halaman kesiapan
 * hanya menghitung (ReadinessDashboardTest). Waktu dibaca dalam zona gunungnya lengkap
 * dengan penandanya (§93), dan kemajuan persiapan dikirim sebagai hitungan, bukan
 * persentase, supaya tidak terbaca sebagai nilai kesiapan (R-005).
 *
 * @mixin ReadinessCheck
 */
class TripReadinessResource extends JsonResource
{
    public function __construct(ReadinessCheck $check, private string $zona)
    {
        parent::__construct($check);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $persiapan = $this->preparation_state ?? [];
        $berlaku = (int) ($persiapan['total_items'] ?? 0) - (int) ($persiapan['not_applicable'] ?? 0);
        $status = OfficialStatusValue::tryFrom($this->official_status_snapshot['status'] ?? 'UNKNOWN') ?? OfficialStatusValue::UNKNOWN;

        return [
            'keadaan' => $this->computed_state?->value,
            'label' => $this->computed_state?->label() ?? 'Belum dinilai',
            'dihitung' => Timezone::display($this->computed_at, $this->zona),
            'kecocokan' => RouteFitLabel::textFor(RouteFitLabel::tryFrom($this->route_fit_snapshot['label'] ?? '')),
            'persiapan' => $berlaku > 0
                ? sprintf('%d dari %d item dikonfirmasi', (int) ($persiapan['confirmed'] ?? 0), $berlaku)
                : 'Belum ada item persiapan.',
            'kritisBelum' => count($persiapan['critical_outstanding'] ?? []),
            'statusResmi' => [
                // StatusAkses hanya mengenal tiga status resmi; UNKNOWN tampil sebagai
                // "Belum diketahui", bukan diperlakukan sebagai buruk (R-006).
                'status' => $status === OfficialStatusValue::UNKNOWN ? null : $status->value,
                'label' => $status->label(),
                'sumber' => $this->official_status_snapshot['source'] ?? 'Belum tercatat',
            ],
            'penjelasan' => $this->penjelasan(),
            'cuaca' => $this->cuaca(),
        ];
    }

    /**
     * @return array<int, array{judul: string, baris: array<int, string>}>
     */
    private function penjelasan(): array
    {
        $kelompok = [];

        foreach (['reasons' => 'Dasar penilaian', 'outstanding' => 'Yang belum selesai', 'condition_warnings' => 'Catatan kondisi'] as $kunci => $judul) {
            $baris = array_values($this->explanation[$kunci] ?? []);

            if ($baris !== []) {
                $kelompok[] = ['judul' => $judul, 'baris' => $baris];
            }
        }

        return $kelompok;
    }

    /**
     * Cuaca selalu membawa sumber, area referensi, dan kesegarannya (R-013). Kegagalan
     * mengambil prakiraan dilaporkan dengan pesannya, tidak diisi angka karangan.
     *
     * @return array<string, mixed>
     */
    private function cuaca(): array
    {
        $cuaca = $this->condition_snapshot['weather'] ?? [];

        if (! ($cuaca['available'] ?? false)) {
            return ['tersedia' => false, 'pesan' => $cuaca['message'] ?? 'Data cuaca tidak tersedia.'];
        }

        $zona = Timezone::isSupported($cuaca['timezone'] ?? null) ? $cuaca['timezone'] : Timezone::DEFAULT;
        $kesegaran = $cuaca['freshness'] ?? null;

        return [
            'tersedia' => true,
            'area' => $cuaca['reference_area'] ?? 'Tidak dicatat',
            'sumber' => $cuaca['source'] ?? 'BMKG',
            'diperbarui' => isset($cuaca['fetched_at'])
                ? Timezone::display(Carbon::parse($cuaca['fetched_at']), $zona, 'd M Y H:i')
                : null,
            'kesegaran' => is_string($kesegaran) ? FreshnessState::tryFrom($kesegaran)?->label() : null,
        ];
    }
}
