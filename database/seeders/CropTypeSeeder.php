<?php

namespace Database\Seeders;

use App\Models\CropType;
use Illuminate\Database\Seeder;

class CropTypeSeeder extends Seeder
{
    public function run(): void
    {
        $crops = [
            ['key' => 'wheat', 'name' => 'Wheat', 'icon' => '🌾', 'seed_price' => 5, 'sell_price' => 3, 'growth_days' => 2, 'yield_amount' => 5],
            ['key' => 'carrot', 'name' => 'Carrot', 'icon' => '🥕', 'seed_price' => 8, 'sell_price' => 4, 'growth_days' => 3, 'yield_amount' => 4],
            ['key' => 'corn', 'name' => 'Corn', 'icon' => '🌽', 'seed_price' => 15, 'sell_price' => 7, 'growth_days' => 4, 'yield_amount' => 4],
            ['key' => 'potato', 'name' => 'Potato', 'icon' => '🥔', 'seed_price' => 12, 'sell_price' => 6, 'growth_days' => 3, 'yield_amount' => 5],
            ['key' => 'pumpkin', 'name' => 'Pumpkin', 'icon' => '🎃', 'seed_price' => 30, 'sell_price' => 18, 'growth_days' => 6, 'yield_amount' => 3],
        ];

        foreach ($crops as $crop) {
            CropType::updateOrCreate(['key' => $crop['key']], $crop);
        }
    }
}
