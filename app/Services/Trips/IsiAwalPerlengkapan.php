<?php

namespace App\Services\Trips;

use App\Enums\PreparationCategory;
use App\Enums\PreparationStatus;
use App\Models\TripPlan;
use App\Models\TripPreparationItem;
use App\Models\UserOwnedItem;

/**
 * Perlengkapan pribadi mengisi awal daftar persiapan (spec v3 bagian 6.10 dan 6.15; ruling
 * W7-R8): setiap barang milik pemilik trip masuk sebagai butir Perlengkapan yang belum
 * dikonfirmasi dan tidak kritis, karena memiliki barang tidak sama dengan membawanya di trip ini
 * (BR-10). Barang yang sudah tertaut, atau yang namanya sama dengan butir yang sudah ada, tidak
 * ditambahkan lagi, jadi "Perbarui daftar" tidak menggandakan apa pun. Hanya barang milik pemilik
 * trip yang dibaca.
 */
final class IsiAwalPerlengkapan
{
    public const KETERANGAN = 'Dari perlengkapan pribadi Anda. Tetap dikonfirmasi untuk trip ini.';

    public function isi(TripPlan $trip): void
    {
        $barang = UserOwnedItem::query()->where('user_id', $trip->user_id)->orderBy('name')->orderBy('id')->get(['id', 'name']);

        if ($barang->isEmpty()) {
            return;
        }

        $ada = $trip->preparationItems()->get(['id', 'label', 'user_owned_item_id']);
        $tertaut = $ada->pluck('user_owned_item_id')->filter()->all();
        $label = $ada->map(fn (TripPreparationItem $b) => mb_strtolower($b->label))->all();

        foreach ($barang as $satu) {
            if (in_array($satu->id, $tertaut, true) || in_array(mb_strtolower($satu->name), $label, true)) {
                continue;
            }

            TripPreparationItem::create([
                'trip_plan_id' => $trip->id,
                'user_owned_item_id' => $satu->id,
                'category' => PreparationCategory::EQUIPMENT->value,
                'label' => $satu->name,
                'description' => self::KETERANGAN,
                'is_critical' => false,
                'status' => PreparationStatus::NOT_CONFIRMED->value,
            ]);
        }
    }
}
