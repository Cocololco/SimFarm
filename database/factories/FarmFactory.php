<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Farm>
 */
class FarmFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->firstName()."'s Farm",
            'cash' => 500,
            'current_day' => 1,
            'field_slots' => 4,
        ];
    }
}
