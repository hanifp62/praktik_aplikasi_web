<?php

namespace App\Services;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Models\PreparationItem;
use App\Models\PreparationTemplate;
use App\Models\Trail;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Support\Timezone;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Preparation is derived from the selected route, not a generic checklist (PRD §36).
 */
class PreparationService
{
    /** Publik: rincian jalur menyebut butir izin dengan nama yang sama dengan daftar persiapan trip. */
    public const PERMIT_LABEL = 'Booking izin pendakian (SIMAKSI)';

    public function __construct(private readonly PermitService $permits) {}

    /**
     * Butir izin di daftar persiapan trip sudah dikonfirmasi atau ditandai tidak berlaku.
     * Butir itu dikenali lewat PERMIT_LABEL sampai kunci stabil butir sistem tersedia
     * (titipan W8, ruling W4-C7b). Membutuhkan relasi preparationItems sudah dimuat.
     */
    public static function izinDiurus(TripPlan $trip): bool
    {
        return $trip->preparationItems->contains(fn (TripPreparationItem $butir) => $butir->preparation_item_id === null
            && $butir->label === self::PERMIT_LABEL
            && $butir->status !== PreparationStatus::NOT_CONFIRMED);
    }

    /**
     * Builds the trip checklist from the trail-specific template when one exists, otherwise
     * from the default template. Existing item statuses are preserved on regeneration.
     */
    public function generateFor(TripPlan $trip): Collection
    {
        $trip->loadMissing('trail');
        $items = $this->applicableItems($trip);

        return DB::transaction(function () use ($trip, $items) {
            $existing = $trip->preparationItems()->get()->keyBy('preparation_item_id');

            foreach ($items as $item) {
                $current = $existing->get($item->id);

                TripPreparationItem::updateOrCreate(
                    ['trip_plan_id' => $trip->id, 'preparation_item_id' => $item->id],
                    [
                        'category' => $item->category->value,
                        'label' => $item->label,
                        'description' => $item->description,
                        'is_critical' => $item->is_critical,
                        'status' => $current?->status?->value ?? PreparationStatus::NOT_CONFIRMED->value,
                    ]
                );
            }

            $this->addPermitItem($trip);

            return $trip->preparationItems()->get();
        });
    }

    /**
     * Item persiapan khusus untuk perizinan pendakian, dibuat hanya bila jalurnya
     * memang punya aturan tercatat.
     *
     * Ditandai kritis karena tidak seperti perlengkapan, izin tidak dapat diurus di
     * basecamp pada hari keberangkatan, jendela pemesanannya sudah lewat.
     */
    private function addPermitItem(TripPlan $trip): void
    {
        $requirement = $this->permits->requirementFor($trip->trail);

        if ($requirement === null) {
            return;
        }

        // Tanpa alamat pemesanan mentah (tinjauan Tugas 4 M9): halaman Persiapan menautkannya
        // sendiri menurut keadaan jendela (PermitService::pemesanan()).
        $description = $requirement->summary();

        $existing = $trip->preparationItems()
            ->whereNull('preparation_item_id')
            ->where('label', self::PERMIT_LABEL)
            ->first();

        TripPreparationItem::updateOrCreate(
            ['trip_plan_id' => $trip->id, 'preparation_item_id' => null, 'label' => self::PERMIT_LABEL],
            [
                'category' => PreparationCategory::LOGISTICS->value,
                'description' => $description,
                'is_critical' => true,
                'status' => $existing?->status?->value ?? PreparationStatus::NOT_CONFIRMED->value,
            ]
        );
    }

    /**
     * Butir izin trip: baris tanpa templat berlabel PERMIT_LABEL (dibuat addPermitItem). Satu
     * predikat untuk Persiapan dan Cek kesiapan.
     */
    public static function butirIzin(TripPreparationItem $item): bool
    {
        return $item->preparation_item_id === null && $item->label === self::PERMIT_LABEL;
    }

    /**
     * Butir khusus satu jalur untuk bagian "Yang perlu disiapkan" di rinciannya (spec v3
     * bagian 6.4): hanya butir templat jalur itu yang berlaku untuknya, bukan templat bawaan,
     * karena bagian itu menjawab apa yang berbeda di jalur ini. Butir yang sama masuk ke
     * daftar persiapan trip lewat generateFor().
     *
     * @return Collection<int, PreparationItem>
     */
    public function khususJalur(Trail $trail): Collection
    {
        $template = PreparationTemplate::query()
            ->where('trail_id', $trail->id)
            ->with('items')
            ->first();

        return ($template?->items ?? collect())
            ->filter(fn (PreparationItem $item) => $item->appliesToTrail($trail))
            ->values();
    }

    /**
     * @return Collection<int, PreparationItem>
     */
    private function applicableItems(TripPlan $trip): Collection
    {
        $template = PreparationTemplate::query()
            ->where('trail_id', $trip->trail_id)
            ->with('items')
            ->first();

        $defaults = PreparationTemplate::query()
            ->where('is_default', true)
            ->with('items')
            ->get()
            ->flatMap->items;

        $items = $defaults->concat($template?->items ?? collect());

        return $items
            ->filter(fn (PreparationItem $item) => $item->appliesToTrail($trip->trail))
            ->unique('id')
            ->values();
    }

    /**
     * Hitungan butir kritis untuk keadaan persiapan (prototype 09) dan cek kesiapan
     * (prototype 10): berapa yang dikonfirmasi dari yang berlaku, dan nama butir kritis yang
     * belum dikonfirmasi. "Tidak berlaku" tidak dihitung. Hitungan, bukan persen (R-005).
     *
     * @param  Collection<int, TripPreparationItem>  $items
     * @return array{dikonfirmasi: int, berlaku: int, belum: array<int, string>}
     */
    public static function kritis(Collection $items): array
    {
        $kritis = $items->where('is_critical', true)->where('status', '!=', PreparationStatus::NOT_APPLICABLE);

        return [
            'dikonfirmasi' => $kritis->where('status', PreparationStatus::CONFIRMED)->count(),
            'berlaku' => $kritis->count(),
            'belum' => $kritis->where('status', PreparationStatus::NOT_CONFIRMED)->pluck('label')->values()->all(),
        ];
    }

    /**
     * Tanggal terbit prakiraan BMKG untuk tanggal trip (config
     * hiking.conditions.weather_forecast_days hari sebelumnya), selama tanggal itu belum tiba
     * menurut zona gunungnya; null sesudahnya. Butir cuaca menunggu sampai tanggal ini (R-013).
     *
     * Null juga untuk rencana yang tidak lagi di depan (TripPlan::masihDirencanakan(): batal,
     * selesai, berlangsung, atau tanggalnya lewat). Butir cuaca trip seperti itu bukan pekerjaan
     * yang menunggu, jadi Persiapan, Cek, dan langkah trip tidak menyebutnya menunggu BMKG
     * (W4-FK N3). Satu gerbang di sini untuk ketiga halaman.
     */
    public static function terbitPrakiraan(TripPlan $trip): ?Carbon
    {
        if (! $trip->masihDirencanakan()) {
            return null;
        }

        $terbit = self::tanggalTerbit($trip->planned_date);

        return $terbit->toDateString() > Carbon::now(Timezone::forTrail($trip->trail))->toDateString() ? $terbit : null;
    }

    /**
     * Tanggal BMKG mulai menerbitkan prakiraan untuk $tanggal (config
     * hiking.conditions.weather_forecast_days hari sebelumnya). Satu aritmetika untuk butir
     * cuaca, Rencanakan, dan rincian jalur (tinjauan Tugas 4 M1), yang dulu menyalinnya.
     */
    public static function tanggalTerbit(CarbonInterface $tanggal): Carbon
    {
        return Carbon::parse($tanggal->toDateString())->subDays((int) config('hiking.conditions.weather_forecast_days'));
    }

    /**
     * Butir yang menunggu BMKG: butir cuaca yang belum dikonfirmasi selama prakiraan untuk
     * tanggal trip belum terbit ($terbit dari terbitPrakiraan()). Butir seperti ini belum dapat
     * dikerjakan, jadi bukan langkah berikutnya dan bukan aksi. Satu predikat untuk Persiapan,
     * Cek kesiapan, dan langkah trip (ruling W4-FB, I2 tinjauan akhir W4).
     */
    public static function menungguBmkg(TripPreparationItem $item, ?Carbon $terbit): bool
    {
        return $terbit !== null
            && $item->category === PreparationCategory::WEATHER
            && $item->status === PreparationStatus::NOT_CONFIRMED;
    }

    /**
     * Penolakan menulis status butir, atau null bila boleh. Daftar trip yang sudah selesai atau
     * dibatalkan adalah catatan, bukan pekerjaan, jadi tidak dapat diubah lagi (W4-4d). Butir
     * cuaca tidak dapat dikonfirmasi sebelum prakiraan untuk tanggal trip terbit: "sudah
     * diperiksa" atas prakiraan yang belum ada adalah pernyataan kosong. Penolakannya di server,
     * bukan hanya di antarmuka.
     *
     * @return string|null kalimat penolakan, atau null bila status itu boleh ditulis
     */
    public static function penolakanKonfirmasi(TripPlan $trip, TripPreparationItem $item, PreparationStatus $status): ?string
    {
        if (! $trip->status->isActive()) {
            return self::penolakanDaftar($trip);
        }

        if ($status !== PreparationStatus::CONFIRMED || $item->category !== PreparationCategory::WEATHER) {
            return null;
        }

        $terbit = self::terbitPrakiraan($trip);

        return $terbit === null
            ? null
            : 'Prakiraan BMKG untuk tanggal trip ini belum terbit. Butir cuaca dapat dikonfirmasi mulai '.$terbit->translatedFormat('D, j M').'.';
    }

    /**
     * Penolakan mengubah daftar persiapan trip yang sudah selesai atau dibatalkan (W4-4d), atau
     * null bila daftarnya masih dapat diubah.
     */
    public static function penolakanDaftar(TripPlan $trip): ?string
    {
        return $trip->status->isActive()
            ? null
            : sprintf('Daftar persiapan tidak dapat diubah lagi: trip berstatus %s.', $trip->status->label());
    }

    /**
     * Konfirmasi butir cuaca berlaku untuk tanggal yang diperiksa. Bila tanggal trip pindah,
     * "prakiraan sudah diperiksa" untuk tanggal lama bukan lagi pernyataan tentang tanggal baru
     * (R-013), jadi butir cuaca yang dikonfirmasi kembali belum dikonfirmasi. Tidak berlaku
     * tetap: itu pernyataan tentang butirnya, bukan tentang tanggal (W4-4d).
     *
     * @return int jumlah butir yang dikembalikan
     */
    public function batalkanKonfirmasiCuaca(TripPlan $trip): int
    {
        return $trip->preparationItems()
            ->where('category', PreparationCategory::WEATHER->value)
            ->where('status', PreparationStatus::CONFIRMED->value)
            ->update([
                'status' => PreparationStatus::NOT_CONFIRMED->value,
                'status_updated_at' => now(),
            ]);
    }

    public function updateStatus(TripPreparationItem $item, PreparationStatus $status): TripPreparationItem
    {
        $item->update([
            'status' => $status,
            'status_updated_at' => now(),
        ]);

        return $item;
    }

    /**
     * @return array<string, mixed> snapshot consumed by ReadinessService
     */
    public function state(TripPlan $trip): array
    {
        $trip->loadMissing('preparationItems');
        $items = $trip->preparationItems;

        return [
            'total_items' => $items->count(),
            'confirmed' => $items->where('status', PreparationStatus::CONFIRMED)->count(),
            'not_confirmed' => $items->where('status', PreparationStatus::NOT_CONFIRMED)->count(),
            'not_applicable' => $items->where('status', PreparationStatus::NOT_APPLICABLE)->count(),
            'completion_percent' => $trip->preparationCompletionPercent(),
            // Himpunan yang sama dengan kritis()['belum'] (tinjauan Tugas 4 M1), satu hitungan.
            'critical_outstanding' => self::kritis($items)['belum'],
        ];
    }
}
