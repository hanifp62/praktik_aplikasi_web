<?php

namespace App\Http\Resources;

use App\Enums\RouteFitLabel;
use App\Enums\TripStatus;
use App\Models\TripPlan;
use App\Services\RouteFit\TrailFitSummary;
use App\Services\Trips\KepalaTrip;
use App\Services\Trips\TripSteps;
use App\Support\Timezone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu kartu indeks trip (spec v3 bagian 6.9), sudah diformat di server: jalur, jenis,
 * tanggal, sisa hari, langkah berikutnya, aksi berikutnya, dan peringatan kesiapan.
 *
 * Kecocokan yang dibawa adalah kecocokan dasar tanpa goal: indeks adalah permukaan
 * pemindaian, dan kecocokan untuk rencana tinggal di ruang kerja trip. Skor internal tidak
 * pernah ikut (BR-09). Status trip hanya ditulis untuk trip yang berlangsung, selesai, atau
 * dibatalkan; trip yang belum dimulai dibaca dari sisa harinya, karena "Siap berangkat" di
 * kartu terbaca sebagai jaminan (R-014).
 *
 * Vonis kesiapan dibaca dari langkah "kesiapan" hasil TripSteps::for(), bukan diturunkan
 * ulang di sini (tinjauan Tugas 2 M7): kartu dan ruang kerja memakai satu turunan.
 *
 * Membutuhkan relasi trail.mountain, preparationItems, latestReadinessCheck,
 * latestConfirmedReadinessCheck, dan history sudah dimuat (IndeksTrip memuatnya sekali untuk
 * semua kartu).
 *
 * @mixin TripPlan
 */
class TripRowResource extends JsonResource
{
    /**
     * @param  array<int, array{keadaan: string, kunci: string, judul: string, keterangan: string, catatan: string|null, peringatan: bool, tautan: array{href: string, teks: string}|null}>  $langkah  hasil TripSteps::for()
     */
    public function __construct(
        TripPlan $trip,
        private array $langkah,
        private ?TrailFitSummary $fit = null,
    ) {
        parent::__construct($trip);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $trip = $this->resource;
        $belumDimulai = $trip->status->belumDimulai();
        $berikutnya = collect($this->langkah)->firstWhere('keadaan', TripSteps::BERIKUTNYA);

        return [
            'id' => $trip->id,
            'nama' => $trip->name,
            'url' => $trip->status === TripStatus::COMPLETED && $trip->history !== null
                ? route('history.summary', $trip)
                : route('trips.show', $trip),
            'jalur' => $trip->trail?->name,
            'gunung' => $trip->trail?->mountain?->name,
            'jenis' => $trip->trip_type->labelPendek(),
            'tanggal' => KepalaTrip::rentang($trip->planned_date, $trip->end_date),
            'hari' => $belumDimulai ? KepalaTrip::hariMenuju($trip->planned_date, Timezone::forTrail($trip->trail)) : null,
            'lewat' => $trip->tanggalRencanaLewat() ? KepalaTrip::TANGGAL_LEWAT : null,
            'keadaan' => $belumDimulai ? null : $trip->status->label(),
            'fase' => $berikutnya === null ? null : [
                'kunci' => $berikutnya['kunci'],
                'judul' => $berikutnya['judul'],
                'keterangan' => $berikutnya['keterangan'],
                'peringatan' => $berikutnya['peringatan'],
            ],
            'aksi' => TripSteps::aksi($trip, $this->langkah),
            'kesiapan' => $trip->status->isActive() ? $this->kesiapan() : null,
            'fit' => $this->fit === null ? null : [
                'label' => $this->fit->label?->value,
                'labelText' => RouteFitLabel::textFor($this->fit->label),
                'alasan' => $this->fit->alasan,
                'denganRencana' => $this->fit->denganRencana,
            ],
        ];
    }

    /**
     * Peringatan langkah Cek (jalur ditarik, vonis basi) atau vonis tersimpan terakhir; null bila
     * trip belum pernah dinilai.
     *
     * @return array{teks: string, peringatan: bool}|null
     */
    private function kesiapan(): ?array
    {
        $langkah = collect($this->langkah)->firstWhere('kunci', 'kesiapan');

        if ($langkah === null) {
            return null;
        }

        if ($langkah['peringatan']) {
            return ['teks' => $langkah['keterangan'], 'peringatan' => true];
        }

        return $this->resource->latestReadinessCheck !== null
            ? ['teks' => 'Kesiapan: '.$langkah['keterangan'], 'peringatan' => false]
            : null;
    }
}
