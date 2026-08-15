<?php

namespace Database\Seeders;

use App\Models\CropType;
use Illuminate\Database\Seeder;

class CropTypeSeeder extends Seeder
{
    public function run(): void
    {
        $crops = [
            ['key' => 'wheat', 'required_level' => 1, 'name' => 'Wheat', 'icon' => '🌾', 'seed_price' => 5, 'sell_price' => 3, 'growth_days' => 2, 'yield_amount' => 5],
            ['key' => 'carrot', 'required_level' => 1, 'name' => 'Carrot', 'icon' => '🥕', 'seed_price' => 8, 'sell_price' => 4, 'growth_days' => 3, 'yield_amount' => 4],
            ['key' => 'corn', 'required_level' => 1, 'name' => 'Corn', 'icon' => '🌽', 'seed_price' => 15, 'sell_price' => 7, 'growth_days' => 4, 'yield_amount' => 4],
            ['key' => 'potato', 'required_level' => 1, 'name' => 'Potato', 'icon' => '🥔', 'seed_price' => 12, 'sell_price' => 6, 'growth_days' => 3, 'yield_amount' => 5],
            ['key' => 'pumpkin', 'required_level' => 1, 'name' => 'Pumpkin', 'icon' => '🎃', 'seed_price' => 30, 'sell_price' => 18, 'growth_days' => 6, 'yield_amount' => 3],
            ['key' => 'strawberry', 'required_level' => 2, 'name' => 'Strawberry', 'icon' => '🍓', 'seed_price' => 20, 'sell_price' => 12, 'growth_days' => 3, 'yield_amount' => 4],
            ['key' => 'golden_wheat', 'required_level' => 3, 'name' => 'Golden Wheat', 'icon' => '🌟', 'seed_price' => 40, 'sell_price' => 25, 'growth_days' => 3, 'yield_amount' => 5],
            ['key' => 'grape', 'required_level' => 5, 'name' => 'Grape', 'icon' => '🍇', 'seed_price' => 60, 'sell_price' => 40, 'growth_days' => 5, 'yield_amount' => 4],
            ['key' => 'dragon_fruit', 'required_level' => 6, 'name' => 'Dragon Fruit', 'icon' => '🐉', 'seed_price' => 90, 'sell_price' => 60, 'growth_days' => 6, 'yield_amount' => 4],
        ];

        foreach ($crops as $crop) {
            CropType::updateOrCreate(['key' => $crop['key']], $crop);
        }
    }
}
