<?php

namespace Database\Factories;

use App\Enums\AuthorityMemberRole;
use App\Models\Authority;
use App\Models\AuthorityMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuthorityMember>
 */
class AuthorityMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'authority_id' => Authority::factory(),
            'role' => AuthorityMemberRole::KURATOR->value,
            'appointment_reference' => 'Surat tugas '.fake()->numerify('###/2026'),
            'valid_from' => now()->subMonth()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
        ];
    }
}
