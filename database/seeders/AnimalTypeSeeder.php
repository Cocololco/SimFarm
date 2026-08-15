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
                'key' => 'chicken', 'required_level' => 1, 'name' => 'Chicken', 'icon' => '🐔',
                'buy_price' => 40, 'feed_cost' => 2, 'sell_price' => 20,
                'produce_key' => 'egg', 'produce_name' => 'Egg', 'produce_icon' => '🥚',
                'produce_sell_price' => 2, 'produce_interval_days' => 1,
            ],
            [
                'key' => 'sheep', 'required_level' => 1, 'name' => 'Sheep', 'icon' => '🐑',
                'buy_price' => 100, 'feed_cost' => 4, 'sell_price' => 50,
                'produce_key' => 'wool', 'produce_name' => 'Wool', 'produce_icon' => '🧶',
                'produce_sell_price' => 8, 'produce_interval_days' => 3,
            ],
            [
                'key' => 'cow', 'required_level' => 1, 'name' => 'Cow', 'icon' => '🐄',
                'buy_price' => 200, 'feed_cost' => 6, 'sell_price' => 100,
                'produce_key' => 'milk', 'produce_name' => 'Milk', 'produce_icon' => '🥛',
                'produce_sell_price' => 6, 'produce_interval_days' => 2,
            ],
            [
                'key' => 'pig', 'required_level' => 1, 'name' => 'Pig', 'icon' => '🐖',
                'buy_price' => 150, 'feed_cost' => 5, 'sell_price' => 80,
                'produce_key' => 'truffle', 'produce_name' => 'Truffle', 'produce_icon' => '🍄',
                'produce_sell_price' => 15, 'produce_interval_days' => 4,
            ],
            [
                'key' => 'goat', 'required_level' => 2, 'name' => 'Goat', 'icon' => '🐐',
                'buy_price' => 120, 'feed_cost' => 4, 'sell_price' => 60,
                'produce_key' => 'goat_milk', 'produce_name' => 'Goat Milk', 'produce_icon' => '🍼',
                'produce_sell_price' => 7, 'produce_interval_days' => 2,
            ],
            [
                'key' => 'alpaca', 'required_level' => 4, 'name' => 'Alpaca', 'icon' => '🦙',
                'buy_price' => 350, 'feed_cost' => 8, 'sell_price' => 180,
                'produce_key' => 'alpaca_wool', 'produce_name' => 'Alpaca Wool', 'produce_icon' => '🧵',
                'produce_sell_price' => 20, 'produce_interval_days' => 4,
            ],
            [
                'key' => 'ostrich', 'required_level' => 6, 'name' => 'Ostrich', 'icon' => '🦤',
                'buy_price' => 500, 'feed_cost' => 10, 'sell_price' => 250,
                'produce_key' => 'ostrich_egg', 'produce_name' => 'Ostrich Egg', 'produce_icon' => '🥚',
                'produce_sell_price' => 25, 'produce_interval_days' => 3,
            ],
        ];

        foreach ($animals as $animal) {
            AnimalType::updateOrCreate(['key' => $animal['key']], $animal);
        }
    }
}
