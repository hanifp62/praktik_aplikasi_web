<?php

namespace Database\Factories;

use App\Enums\PhotoLicense;
use App\Models\Mountain;
use App\Models\MountainPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MountainPhoto>
 */
class MountainPhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mountain_id' => Mountain::factory(),
            'path' => 'mountain-photos/'.fake()->regexify('[A-Za-z0-9]{40}').'.jpg',
            'width' => 1600,
            'height' => 900,
            'sha256' => hash('sha256', fake()->uuid()),
            'creator' => 'Rani Prasetyo',
            'license' => PhotoLicense::CC_BY->value,
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Contoh.jpg',
            'attribution' => 'Foto: Rani Prasetyo, CC BY 4.0, Wikimedia Commons',
            'alt_text' => 'Lereng gunung berhutan di bawah langit cerah.',
        ];
    }
}
