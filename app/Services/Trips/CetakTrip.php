<?php

namespace App\Services\Trips;

use App\Models\Checkpoint;
use App\Models\TripPlan;
use App\Models\User;
use App\Services\CheckpointPaceService;
use App\Services\PermitService;
use App\Services\Trails\CheckpointJourney;
use App\Support\Timezone;

/**
 * Versi cetak satu trip (spec v3 bagian 10.8; rencana induk W6 butir 8; ruling W6-R4): sketsa
 * rute, tabel pos, aturan resmi bertanggal, kartu darurat, dan rencana. Cadangan kertas untuk
 * paket di ponsel, jadi isinya data stabil yang dimiliki pendaki dan tidak ada status terkini
 * yang dapat basi tanpa tanda (R-013): yang bertanggal menyebut tanggal ceknya.
 *
 * Jam mulai dan jam kembali ditulis dalam zona gunung dengan penandanya. Jam kembali yang tidak
 * sesudah mulai trip ditulis sebagai perlu diperbarui, bukan dicetak seolah masih berlaku, dan
 * pesan eskalasinya tidak ikut (pra-tinjau PT7-7a, satu predikat dengan PesanRencana). Trip
 * yang sudah selesai atau dibatalkan tidak membawa pesan eskalasi ke kertas (PT7-8), sama
 * dengan gerbang prop keselamatan di ruang kerja.
 */
final class CetakTrip
{
    public function __construct(
        private readonly KartuDarurat $darurat,
        private readonly PesanRencana $pesan,
        private readonly PermitService $izin,
        private readonly CheckpointPaceService $pace,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function untuk(TripPlan $trip, User $pengguna): array
    {
        $trip->loadMissing('trail.mountain.authorities', 'trail.checkpoints');
        $jalur = $trip->trail;
        $zona = Timezone::forTrail($jalur);
        $rencanaDanAturan = $this->rencanaDanAturan($trip, $pengguna);
        $garis = $jalur->readGeoJson('geometry');
        $rencana = $this->pesan->untuk($trip, $pengguna);

        return [
            'jalur' => [
                'nama' => $jalur->name,
                'gunung' => $jalur->mountain?->name,
                'wilayah' => implode(' · ', array_filter([$jalur->mountain?->name, $jalur->mountain?->province])),
            ],
            'rencana' => $rencanaDanAturan['rencana'],
            'sketsa' => [
                'garis' => ($garis['type'] ?? null) === 'LineString' ? $garis['coordinates'] : null,
                'pos' => $jalur->checkpoints
                    ->filter(fn (Checkpoint $p) => $p->latitude !== null && $p->longitude !== null)
                    ->sortBy('sequence')
                    ->map(fn (Checkpoint $p) => ['urutan' => (int) $p->sequence, 'lng' => (float) $p->longitude, 'lat' => (float) $p->latitude])
                    ->values()
                    ->all(),
            ],
            'pos' => CheckpointJourney::daftar($jalur->checkpoints, $this->pace->forTrail($jalur), []),
            'aturan' => $rencanaDanAturan['aturan'],
            'darurat' => $this->darurat->untuk($jalur),
            'pesan' => $trip->status->isActive() ? $rencana['pesan'] : null,
            'dicetak' => Timezone::display(now(), $zona, 'j F Y H.i'),
            'tripId' => $trip->id,
        ];
    }

    /**
     * Rencana pendaki dan aturan resmi bertanggal cek, bagian yang juga dibawa paket lapangan
     * luring (ruling W6-8f; PaketLapangan). Metode sempit ini tidak membaca geometri, tempo,
     * sketsa, kartu darurat, atau pesan, sehingga paket tidak membayar kueri ST_AsGeoJSON yang
     * tidak dipakainya. untuk() memakai metode yang sama, jadi kalimat rencana dan aturan tetap
     * satu sumber untuk kertas dan paket.
     *
     * @return array{rencana: array{nama: string, tanggal: string, jamMulai: string|null, kembali: string|null, registrasi: string|null}, aturan: array{ringkasan: string, catatan: string|null, dicek: string|null}|null}
     */
    public function rencanaDanAturan(TripPlan $trip, User $pengguna): array
    {
        $trip->loadMissing('trail.mountain');
        $jalur = $trip->trail;
        $zona = Timezone::forTrail($jalur);
        $izin = $this->izin->requirementFor($jalur);

        return [
            'rencana' => [
                'nama' => $trip->name,
                'tanggal' => $trip->ringkasan(),
                'jamMulai' => $trip->start_time ? str_replace(':', '.', substr((string) $trip->start_time, 0, 5)).' '.Timezone::label($zona) : null,
                'kembali' => $this->pesan->kembaliTidakSesudahMulai($trip, $zona)
                    ? 'Perlu diperbarui di ruang kerja trip: jam kembali tidak sesudah mulai trip.'
                    : Timezone::display($trip->expected_return_at, $zona, 'l, j F Y H.i'),
                'registrasi' => $trip->registration_number,
            ],
            'aturan' => $izin === null ? null : [
                'ringkasan' => $izin->summary(),
                'catatan' => $izin->notes,
                'dicek' => Timezone::display($izin->verified_at, $zona, 'j F Y'),
            ],
        ];
    }
}
