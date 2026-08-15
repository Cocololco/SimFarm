<?php

namespace Database\Seeders;

use App\Models\AnimalType;
use Illuminate\Database\Seeder;

class AnimalTypeSeeder extends Seeder
{
    public function run(): void
    {
        $animals = [
            [
                'key' => 'chicken', 'name' => 'Chicken', 'icon' => '🐔',
                'buy_price' => 40, 'feed_cost' => 2, 'sell_price' => 20,
                'produce_key' => 'egg', 'produce_name' => 'Egg', 'produce_icon' => '🥚',
                'produce_sell_price' => 2, 'produce_interval_days' => 1,
            ],
            [
                'key' => 'sheep', 'name' => 'Sheep', 'icon' => '🐑',
                'buy_price' => 100, 'feed_cost' => 4, 'sell_price' => 50,
                'produce_key' => 'wool', 'produce_name' => 'Wool', 'produce_icon' => '🧶',
                'produce_sell_price' => 8, 'produce_interval_days' => 3,
            ],
            [
                'key' => 'cow', 'name' => 'Cow', 'icon' => '🐄',
                'buy_price' => 200, 'feed_cost' => 6, 'sell_price' => 100,
                'produce_key' => 'milk', 'produce_name' => 'Milk', 'produce_icon' => '🥛',
                'produce_sell_price' => 6, 'produce_interval_days' => 2,
            ],
            [
                'key' => 'pig', 'name' => 'Pig', 'icon' => '🐖',
                'buy_price' => 150, 'feed_cost' => 5, 'sell_price' => 80,
                'produce_key' => 'truffle', 'produce_name' => 'Truffle', 'produce_icon' => '🍄',
                'produce_sell_price' => 15, 'produce_interval_days' => 4,
            ],
        ];

        foreach ($animals as $animal) {
            AnimalType::updateOrCreate(['key' => $animal['key']], $animal);
        }
    }
}
