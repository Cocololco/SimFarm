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
                'key' => 'barn_expansion', 'required_level' => 2, 'name' => 'Barn Expansion', 'icon' => '🐓',
                'description' => 'Room for 6 more animals.',
                'price' => 300, 'effect_type' => 'animal_capacity_boost', 'effect_value' => 6,
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
            [
                'key' => 'compost_bin', 'required_level' => 2, 'name' => 'Compost Bin', 'icon' => '🪱',
                'description' => 'Turns spoiled (storage-full) crops into fertilizer instead of wasting them.',
                'price' => 200, 'effect_type' => 'compost', 'effect_value' => 1,
            ],
            [
                'key' => 'farmhand', 'required_level' => 3, 'name' => 'Farmhand', 'icon' => '🧑‍🌾',
                'description' => 'Automatically feeds every hungry animal at the end of each day.',
                'price' => 600, 'effect_type' => 'auto_feed', 'effect_value' => 1,
            ],
            [
                'key' => 'auto_harvester_drone', 'required_level' => 4, 'name' => 'Auto-Harvester Drone', 'icon' => '🛸',
                'description' => 'Automatically harvests every ready field at the end of each day.',
                'price' => 1200, 'effect_type' => 'auto_harvest', 'effect_value' => 1,
            ],
            [
                'key' => 'windmill', 'required_level' => 6, 'name' => 'Windmill', 'icon' => '🎡',
                'description' => 'Speeds up crop growth by 50%.',
                'price' => 1600, 'effect_type' => 'growth_speed', 'effect_value' => 0.50,
            ],
        ];

        foreach ($machines as $machine) {
            MachineryType::updateOrCreate(['key' => $machine['key']], $machine);
        }
    }
}
