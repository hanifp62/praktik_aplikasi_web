<?php

namespace App\Http\Resources;

use App\Enums\OfficialStatusValue;
use App\Models\TripPlan;
use App\Services\RouteFit\TrailFitSummary;
use App\Services\Trips\TripSteps;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Baris daftar trip, sudah diformat di server.
 *
 * Kecocokan yang dibawa adalah kecocokan dasar tanpa goal: daftar trip adalah permukaan
 * pemindaian, dan kecocokan tajam yang sadar-rencana tinggal di halaman trip itu
 * sendiri. Skor internal tidak pernah ikut (BR-09).
 *
 * Kemajuan persiapan dibaca dari kolom hitung (withCount) supaya daftar tidak memuat
 * seluruh butir tiap trip.
 *
 * @mixin TripPlan
 */
class TripRowResource extends JsonResource
{
    public function __construct(
        TripPlan $trip,
        private ?TrailFitSummary $fit = null,
        private ?OfficialStatusValue $statusJalur = null,
    ) {
        parent::__construct($trip);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'url' => route('trips.show', $this->resource),
            'trail' => $this->trail?->name,
            'mountain' => $this->trail?->mountain?->name,
            'tanggal' => $this->planned_date->translatedFormat('d M Y'),
            'tipe' => $this->trip_type->label(),
            'status' => $this->status->label(),
            'persiapan' => isset($this->item_berlaku) && (int) $this->item_berlaku > 0
                ? TripSteps::teksPersiapan([
                    'dikonfirmasi' => (int) $this->item_dikonfirmasi,
                    'berlaku' => (int) $this->item_berlaku,
                ])
                : null,
            'kesiapan' => $this->kesiapan(),
            'fit' => $this->fit === null ? null : [
                'label' => $this->fit->label?->value,
                'labelText' => $this->fit->label?->label() ?? 'Tidak dinilai',
                'alasan' => $this->fit->alasan,
                'denganRencana' => $this->fit->denganRencana,
            ],
        ];
    }

    /**
     * @return array{teks: string, peringatan: bool}|null
     */
    private function kesiapan(): ?array
    {
        if ($this->resource->trailIsWithdrawn()) {
            return ['teks' => 'Jalur ditarik dari katalog', 'peringatan' => true];
        }

        // Status sekarang dioper dari luar, dimuat sekali untuk seluruh halaman.
        if ($this->resource->readinessIsStale($this->statusJalur)) {
            return ['teks' => 'Status jalur berubah, perlu dinilai ulang', 'peringatan' => true];
        }

        if ($this->latestReadinessCheck !== null) {
            return ['teks' => 'Kesiapan: '.$this->latestReadinessCheck->computed_state->label(), 'peringatan' => false];
        }

        return null;
    }
}
