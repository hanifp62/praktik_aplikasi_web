<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin Demo',
            'email' => 'admin@example.com',
            'role' => UserRole::ADMIN->value,
        ]);

        User::factory()->create([
            'name' => 'Moderator Demo',
            'email' => 'moderator@example.com',
            'role' => UserRole::MODERATOR->value,
        ]);

        User::factory()->create([
            'name' => 'Pendaki Demo',
            'email' => 'pendaki@example.com',
        ]);

        $this->call([
            RecommendationRuleSeeder::class,
            PreparationTemplateSeeder::class,
            MvpDatasetSeeder::class,

            // Urutannya penting: status melekat pada gunung, jadi gunungnya harus ada
            // lebih dulu. MvpDatasetSeeder dibiarkan di depan karena jalur-jalurnya
            // menempel pada gunung yang sama dan diperbarui, bukan digandakan.
            MountainSeeder::class,
            OfficialStatusSeeder::class,
            PermitRequirementSeeder::class,
        ]);
    }
}
