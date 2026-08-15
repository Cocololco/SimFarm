<?php

namespace Tests\Concerns;

use App\Models\Farm;
use App\Models\User;
use Database\Seeders\AnimalTypeSeeder;
use Database\Seeders\CropTypeSeeder;
use Database\Seeders\MachineryTypeSeeder;

trait InteractsWithFarm
{
    protected function seedCatalogs(): void
    {
        $this->seed(CropTypeSeeder::class);
        $this->seed(AnimalTypeSeeder::class);
        $this->seed(MachineryTypeSeeder::class);
    }

    /**
     * Create a user with a farm of 4 empty fields, bypassing the
     * registration flow (which is exercised separately).
     */
    protected function createUserWithFarm(array $farmAttributes = []): User
    {
        $user = User::factory()->create();

        $farm = Farm::factory()
            ->for($user)
            ->create($farmAttributes);

        for ($plot = 1; $plot <= $farm->field_slots; $plot++) {
            $farm->fields()->create(['plot_number' => $plot]);
        }

        return $user->refresh();
    }
}
