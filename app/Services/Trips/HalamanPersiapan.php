<?php

namespace App\Services\Trips;

use App\Enums\PreparationCategory;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Services\PermitService;
use App\Services\PreparationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Kelas halaman Persiapan (spec v3 bagian 6.10, prototype 09, rencana induk W4 butir 3):
 * kepala trip, kelompok PreparationCategory dengan butirnya, butir cuaca yang menunggu BMKG,
 * tautan izin ke sistem resmi, dan pengingat trip.
 *
 * Hitungan (butir kritis, per kategori, seluruh daftar) dihitung halaman dari butir yang
 * dikirim, karena kotak centangnya optimis: hitungan dari server akan tertinggal satu
 * perjalanan pulang-pergi dari kotak yang sudah dicentang (tinjauan Tugas 4 M4 d).
 *
 * Kueri tetap: butir (dengan urutan templatnya), jalur, gunung, dan pengingat dimuat sekali
 * oleh pemanggil; aturan izin dicari sekali untuk butir izin. Tanpa persen (R-005) dan tanpa
 * kata "tidak dimiliki" (BR-10).
 */
final class HalamanPersiapan
{
    public function __construct(
        private readonly KepalaTrip $kepala,
        private readonly PermitService $permits,
        private readonly TripReminderScheduler $pengingat,
    ) {}

    /**
     * Membutuhkan relasi trail.mountain, preparationItems.preparationItem, dan reminders sudah
     * dimuat.
     *
     * @return array<string, mixed>
     */
    public function props(TripPlan $trip): array
    {
        $items = self::urutkan($trip->preparationItems);
        $terbit = PreparationService::terbitPrakiraan($trip);
        $izin = $this->pemesananIzin($trip);
        $perKategori = $items->groupBy(fn (TripPreparationItem $item) => $item->category->value);

        return [
            'kepala' => $this->kepala->untuk($trip),
            // W4-4d: daftar trip yang selesai atau batal adalah catatan; tidak ada centang,
            // "Tidak berlaku", "Perbarui daftar", atau ajakan memesan. Server menolak hal yang sama
            // (PreparationService::penolakanDaftar).
            'dapatDiubah' => PreparationService::penolakanDaftar($trip) === null,
            'tautanLangkah' => array_filter([
                'jalur' => $trip->trail->terbit() ? route('trails.show', $trip->trail) : null,
                'rencana' => route('trips.show', $trip),
                'kesiapan' => route('trips.readiness', $trip),
            ]),
            // Urutan kategori tetap, ditetapkan di kode; kategori kosong tidak tampil.
            'kategori' => collect(PreparationCategory::cases())
                ->filter(fn (PreparationCategory $k) => $perKategori->has($k->value))
                ->map(fn (PreparationCategory $k) => [
                    'kunci' => $k->value,
                    'nama' => $k->label(),
                    'items' => $perKategori[$k->value]->map(fn (TripPreparationItem $item) => $this->butir($item, $terbit, $izin))->values()->all(),
                ])
                ->values()
                ->all(),
            'pengingat' => $this->pengingat->untukHalaman($trip),
        ];
    }

    /**
     * Butir kritis lebih dulu, lalu urutan templatnya (tinjauan Tugas 4 M10), bukan urutan
     * pembuatan baris: butir yang ditambah templat kemudian tidak jatuh ke akhir kategori. Butir
     * izin tidak punya templat dan berada paling awal di antara butir kritis Logistik.
     *
     * @param  iterable<int, TripPreparationItem>  $items
     * @return Collection<int, TripPreparationItem>
     */
    private static function urutkan(iterable $items): Collection
    {
        return collect($items)
            ->sort(fn (TripPreparationItem $a, TripPreparationItem $b) => [$b->is_critical, $a->preparationItem?->sort_order ?? -1, $a->id] <=> [$a->is_critical, $b->preparationItem?->sort_order ?? -1, $b->id])
            ->values();
    }

    /**
     * @param  array{tautan: array{href: string, teks: string, label: string}|null, keterangan: string}|null  $izin
     * @return array{id: int, label: string, keterangan: string|null, kritis: bool, status: string, menunggu: string|null, tautan: array{href: string, teks: string}|null}
     */
    private function butir(TripPreparationItem $item, ?Carbon $terbit, ?array $izin): array
    {
        $butirIzin = PreparationService::butirIzin($item) && $izin !== null;

        return [
            'id' => $item->id,
            'label' => $item->label,
            // Butir izin menyebut aturan dan keadaan jendelanya tanpa alamat pemesanan mentah;
            // alamatnya menjadi tautan (bila jendelanya mengizinkan), bukan teks.
            'keterangan' => $butirIzin ? $izin['keterangan'] : $item->description,
            'kritis' => (bool) $item->is_critical,
            'status' => $item->status->value,
            'menunggu' => PreparationService::menungguBmkg($item, $terbit)
                ? 'Menunggu BMKG: terbit mulai '.$terbit->translatedFormat('D, j M')
                : null,
            'tautan' => $butirIzin && $izin['tautan'] !== null ? ['href' => $izin['tautan']['href'], 'teks' => $izin['tautan']['teks']] : null,
        ];
    }

    /**
     * Keterangan dan tautan butir izin dari satu penentu per keadaan jendela
     * (PermitService::pemesanan(), M1 tinjauan akhir W4). Aturan yang sudah dihapus tidak
     * jatuh ke keterangan tersimpan butir itu (yang dulu memuat alamat pemesanan mentah).
     *
     * @return array{tautan: array{href: string, teks: string, label: string}|null, keterangan: string}|null
     */
    private function pemesananIzin(TripPlan $trip): ?array
    {
        if (! $trip->preparationItems->contains(fn (TripPreparationItem $item) => PreparationService::butirIzin($item))) {
            return null;
        }

        $aturan = $this->permits->requirementFor($trip->trail);

        if ($aturan === null) {
            return ['tautan' => null, 'keterangan' => 'Aturan izin jalur ini tidak lagi tercatat di Memount. Tanyakan langsung kepada pengelola kawasan.'];
        }

        return $this->permits->pemesanan($trip->trail, $trip->planned_date, $aturan, PreparationService::penolakanDaftar($trip) === null);
    }
}
