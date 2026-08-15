<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            ['key' => 'first_harvest', 'name' => 'First Harvest', 'icon' => '🌾', 'description' => 'Harvest a crop for the first time.'],
            ['key' => 'first_animal', 'name' => 'Animal Lover', 'icon' => '🐄', 'description' => 'Buy your first animal.'],
            ['key' => 'first_machine', 'name' => 'Mechanized', 'icon' => '🚜', 'description' => 'Buy your first piece of machinery.'],
            ['key' => 'harvest_veteran', 'name' => 'Harvest Veteran', 'icon' => '🧺', 'description' => 'Harvest crops 10 times.'],
            ['key' => 'level_5', 'name' => 'Seasoned Farmer', 'icon' => '⭐', 'description' => 'Reach farm level 5.'],
            ['key' => 'net_worth_5000', 'name' => 'Prosperous', 'icon' => '💰', 'description' => 'Reach a net worth of $5,000.'],
        ];

        foreach ($achievements as $achievement) {
            Achievement::updateOrCreate(['key' => $achievement['key']], $achievement);
        }
    }
}
