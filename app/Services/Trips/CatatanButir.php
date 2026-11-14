<?php

namespace App\Services\Trips;

use App\Enums\PreparationCategory;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;

/**
 * Catatan singkat per butir persiapan (titipan W4 ke W7; prototype 09 "Dibawa Dimas"; ruling
 * W7-R9). Catatan menjawab siapa membawa atau mengurus apa, jadi hanya butir Perlengkapan dan
 * Logistik yang dapat diberi catatan; butir pemahaman jalur, cuaca, dan status resmi sudah
 * punya keterangan dari sistem. Catatan tidak mengubah status butir, tidak dihitung sebagai
 * konfirmasi (BR-10), dan berhenti dapat diubah ketika trip selesai atau dibatalkan, sama
 * seperti statusnya.
 */
final class CatatanButir
{
    public const BATAS = 80;

    /** @var list<PreparationCategory> */
    public const KATEGORI = [PreparationCategory::EQUIPMENT, PreparationCategory::LOGISTICS];

    public static function dapatDicatat(TripPreparationItem $item): bool
    {
        return in_array($item->category, self::KATEGORI, true);
    }

    /**
     * Kalimat penolakan menulis catatan, atau null bila boleh.
     */
    public static function penolakan(TripPlan $trip, TripPreparationItem $item): ?string
    {
        if (! $trip->status->isActive()) {
            return sprintf('Catatan butir tidak dapat diubah lagi: trip berstatus %s.', $trip->status->label());
        }

        if (! self::dapatDicatat($item)) {
            return 'Catatan hanya untuk butir Perlengkapan dan Logistik.';
        }

        return null;
    }

    /**
     * Membutuhkan relasi preparationItems sudah dimuat; tanpa kueri tambahan.
     *
     * @return array{dapatDiubah: bool, bisa: list<int>, isi: array<int, string>, batas: int}
     */
    public static function untukHalaman(TripPlan $trip): array
    {
        $butir = $trip->preparationItems;

        return [
            'dapatDiubah' => $trip->status->isActive(),
            'bisa' => $butir->filter(fn (TripPreparationItem $item) => self::dapatDicatat($item))->pluck('id')->values()->all(),
            'isi' => $butir->filter(fn (TripPreparationItem $item) => filled($item->note))
                ->mapWithKeys(fn (TripPreparationItem $item) => [$item->id => $item->note])
                ->all(),
            'batas' => self::BATAS,
        ];
    }
}
