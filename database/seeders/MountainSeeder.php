<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mountain;
use App\Models\Trail;
use App\Models\Checkpoint;

class MountainSeeder extends Seeder
{
    public function run(): void
{
    $mountain = Mountain::create([
        'name' => 'Gunung Merapi',
        'elevation_mdpl' => 2968,
        'region' => 'Jawa Tengah',
    ]);

    $trail = Trail::create([
        'mountain_id' => $mountain->id,
        'name' => 'Jalur Selo',
        'distance_km' => 5.5,
        'elevation_gain_m' => 1200,
        'estimated_duration_hours' => 8,
        'technical_demand' => 'HARD',
        'terrain_character' => 'Batuan dan pasir vulkanik',
    ]);

    Checkpoint::create([
        'trail_id' => $trail->id,
        'name' => 'Basecamp',
        'order_index' => 1,
    ]);

    Checkpoint::create([
        'trail_id' => $trail->id,
        'name' => 'Pos 1',
        'order_index' => 2,
    ]);

    Checkpoint::create([
        'trail_id' => $trail->id,
        'name' => 'Puncak',
        'order_index' => 3,
    ]);
}
}