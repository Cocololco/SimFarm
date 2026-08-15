<?php

namespace Database\Seeders;

use App\Models\MachineryType;
use Illuminate\Database\Seeder;

class MachineryTypeSeeder extends Seeder
{
    public function run(): void
    {
        $machines = [
            [
                'key' => 'tractor', 'name' => 'Tractor', 'icon' => '🚜',
                'description' => 'Speeds up crop growth by 20%.',
                'price' => 300, 'effect_type' => 'growth_speed', 'effect_value' => 0.20,
            ],
            [
                'key' => 'irrigation', 'name' => 'Irrigation System', 'icon' => '💧',
                'description' => 'Boosts harvest yield by 25%.',
                'price' => 400, 'effect_type' => 'yield_boost', 'effect_value' => 0.25,
            ],
            [
                'key' => 'harvester', 'name' => 'Combine Harvester', 'icon' => '🌿',
                'description' => 'Boosts harvest yield by 50%.',
                'price' => 900, 'effect_type' => 'yield_boost', 'effect_value' => 0.50,
            ],
            [
                'key' => 'feed_silo', 'name' => 'Feed Silo', 'icon' => '🏭',
                'description' => 'Cuts animal feed costs by 30%.',
                'price' => 350, 'effect_type' => 'feed_discount', 'effect_value' => 0.30,
            ],
            [
                'key' => 'greenhouse', 'name' => 'Greenhouse', 'icon' => '🏡',
                'description' => 'Speeds up crop growth by 35%.',
                'price' => 700, 'effect_type' => 'growth_speed', 'effect_value' => 0.35,
            ],
        ];

        foreach ($machines as $machine) {
            MachineryType::updateOrCreate(['key' => $machine['key']], $machine);
        }
    }
}
