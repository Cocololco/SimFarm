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
                'key' => 'tractor', 'required_level' => 1, 'name' => 'Tractor', 'icon' => '🚜',
                'description' => 'Speeds up crop growth by 20%.',
                'price' => 300, 'effect_type' => 'growth_speed', 'effect_value' => 0.20,
            ],
            [
                'key' => 'irrigation', 'required_level' => 1, 'name' => 'Irrigation System', 'icon' => '💧',
                'description' => 'Boosts harvest yield by 25%.',
                'price' => 400, 'effect_type' => 'yield_boost', 'effect_value' => 0.25,
            ],
            [
                'key' => 'feed_silo', 'required_level' => 1, 'name' => 'Feed Silo', 'icon' => '🏭',
                'description' => 'Cuts animal feed costs by 30%.',
                'price' => 350, 'effect_type' => 'feed_discount', 'effect_value' => 0.30,
            ],
            [
                'key' => 'storage_barn', 'required_level' => 2, 'name' => 'Storage Barn', 'icon' => '🏚️',
                'description' => 'Adds 100 extra inventory storage slots.',
                'price' => 250, 'effect_type' => 'storage_boost', 'effect_value' => 100,
            ],
            [
                'key' => 'greenhouse', 'required_level' => 3, 'name' => 'Greenhouse', 'icon' => '🏡',
                'description' => 'Speeds up crop growth by 35%.',
                'price' => 700, 'effect_type' => 'growth_speed', 'effect_value' => 0.35,
            ],
            [
                'key' => 'harvester', 'required_level' => 3, 'name' => 'Combine Harvester', 'icon' => '🌿',
                'description' => 'Boosts harvest yield by 50%.',
                'price' => 900, 'effect_type' => 'yield_boost', 'effect_value' => 0.50,
            ],
        ];

        foreach ($machines as $machine) {
            MachineryType::updateOrCreate(['key' => $machine['key']], $machine);
        }
    }
}
