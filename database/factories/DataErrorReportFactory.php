<?php

namespace Database\Factories;

use App\Enums\DataErrorCategory;
use App\Enums\DataErrorState;
use App\Models\DataErrorReport;
use App\Models\Mountain;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataErrorReport>
 */
class DataErrorReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'mountain_id' => Mountain::factory(),
            'category' => DataErrorCategory::STATUS->value,
            'note' => 'Pengumuman balai di akun resminya menyebut jalur ini sudah dibuka kembali.',
            'state' => DataErrorState::OPEN->value,
        ];
    }
}
