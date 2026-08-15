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
            ['key' => 'gift_giver', 'name' => 'Generous Neighbor', 'icon' => '🎁', 'description' => 'Send a gift to another farmer.'],
            ['key' => 'big_spender', 'name' => 'Big Spender', 'icon' => '🛒', 'description' => 'Spend $1,000 in total.'],
            ['key' => 'loan_free', 'name' => 'Debt Free', 'icon' => '🎉', 'description' => 'Fully repay a loan.'],
            ['key' => 'green_thumb', 'name' => 'Green Thumb', 'icon' => '🔄', 'description' => 'Harvest with a crop rotation bonus 5 times.'],
        ];

        foreach ($achievements as $achievement) {
            Achievement::updateOrCreate(['key' => $achievement['key']], $achievement);
        }
    }
}
