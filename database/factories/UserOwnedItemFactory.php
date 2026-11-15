<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserOwnedItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserOwnedItem>
 */
class UserOwnedItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->randomElement(['Headlamp', 'Jas hujan', 'Jaket hangat', 'Sepatu mendaki', 'Kompor lipat', 'Matras', 'Trekking pole', 'Botol air 1 liter']),
        ];
    }
}
