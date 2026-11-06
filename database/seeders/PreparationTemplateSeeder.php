<?php

namespace Database\Seeders;

use App\Enums\PreparationCategory;
use App\Models\PreparationItem;
use App\Models\PreparationTemplate;
use Illuminate\Database\Seeder;

/**
 * Default preparation set (PRD §36). Trail-specific templates extend this; conditional items
 * only attach to trails that match `applies_when`.
 */
class PreparationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template = PreparationTemplate::updateOrCreate(
            ['name' => 'Persiapan dasar pendakian'],
            ['is_default' => true, 'description' => 'Item persiapan yang berlaku untuk sebagian besar jalur.']
        );

        $items = [
            [PreparationCategory::EQUIPMENT, 'Sepatu atau alas kaki yang sesuai medan', true, null],
            [PreparationCategory::EQUIPMENT, 'Headlamp dan cadangan baterai', true, null],
            [PreparationCategory::EQUIPMENT, 'Perlindungan cuaca (jaket, jas hujan)', true, null],
            [PreparationCategory::EQUIPMENT, 'Referensi navigasi jalur', false, null],
            [PreparationCategory::EQUIPMENT, 'Perlengkapan darurat dasar (P3K, peluit)', true, null],
            [PreparationCategory::EQUIPMENT, 'Perlengkapan bermalam (tenda, sleeping bag)', true, ['camping_available' => true]],
            [PreparationCategory::EQUIPMENT, 'Perlengkapan tambahan untuk medan teknis', true, ['technical_demand' => 'VERY_HIGH']],
            [PreparationCategory::ROUTE_KNOWLEDGE, 'Membaca karakteristik jalur', true, null],
            [PreparationCategory::ROUTE_KNOWLEDGE, 'Meninjau daftar checkpoint', false, null],
            [PreparationCategory::ROUTE_KNOWLEDGE, 'Meninjau segmen teknis jalur', false, ['technical_demand' => 'HIGH']],
            [PreparationCategory::ROUTE_KNOWLEDGE, 'Mempelajari titik percabangan jalur', true, ['navigation_complexity' => 'HIGH']],
            [PreparationCategory::LOGISTICS, 'Memastikan titik awal pendakian', true, null],
            [PreparationCategory::LOGISTICS, 'Transportasi menuju titik awal', false, null],
            [PreparationCategory::LOGISTICS, 'Perizinan atau registrasi basecamp', true, null],
            [PreparationCategory::LOGISTICS, 'Rencana lokasi camping', false, ['camping_available' => true]],
            [PreparationCategory::LOGISTICS, 'Kontak darurat yang dapat dihubungi', true, null],
            [PreparationCategory::LOGISTICS, 'Persediaan air sesuai kondisi jalur', true, ['min_elevation_gain_m' => 800]],
            [PreparationCategory::WEATHER, 'Memeriksa prakiraan cuaca terbaru', true, null],
            [PreparationCategory::WEATHER, 'Memeriksa peringatan cuaca', false, null],
            [PreparationCategory::OFFICIAL_STATUS, 'Memeriksa status resmi jalur terbaru', true, null],
        ];

        foreach ($items as $index => [$category, $label, $critical, $appliesWhen]) {
            PreparationItem::updateOrCreate(
                ['preparation_template_id' => $template->id, 'label' => $label],
                [
                    'category' => $category->value,
                    'is_critical' => $critical,
                    'sort_order' => $index,
                    'applies_when' => $appliesWhen,
                ]
            );
        }
    }
}
