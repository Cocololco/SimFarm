<?php

namespace Tests\Feature;

use App\Models\AnimalType;
use App\Models\Farm;
use App\Models\MachineryType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class AnimalBreedingTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    /**
     * Breeding is probabilistic (5%/day per fed animal), so this keeps
     * several animals fed every day for enough days that the chance of
     * zero births is negligible, rather than forcing a specific roll.
     */
    public function test_fed_animals_occasionally_breed_given_enough_time(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['xp' => Farm::XP_PER_LEVEL, 'cash' => 3000]); // level 2, unlocks barn expansion
        $barnExpansion = MachineryType::where('key', 'barn_expansion')->firstOrFail();
        $this->actingAs($user)->post('/machinery/buy', ['machinery_type_id' => $barnExpansion->id]);

        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        for ($i = 0; $i < 4; $i++) {
            $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        }

        // 4 animals fed every day for 40 days: chance of zero breeding
        // rolls across ~160 fed-animal-days is (0.95)^160 ≈ 0.02%.
        for ($day = 0; $day < 40; $day++) {
            $this->actingAs($user)->post('/animals/feed-all');
            $this->actingAs($user)->post('/turn/end');
        }

        $farm = $user->farm->fresh();
        $this->assertGreaterThan(4, $farm->animals->count());
        $this->assertNotNull($farm->transactions()->where('type', 'animal_born')->first());
    }

    public function test_breeding_never_exceeds_barn_capacity(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 3000]);
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        for ($i = 0; $i < Farm::BASE_ANIMAL_CAPACITY; $i++) {
            $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        }

        for ($day = 0; $day < 20; $day++) {
            $this->actingAs($user)->post('/animals/feed-all');
            $this->actingAs($user)->post('/turn/end');
        }

        $this->assertLessThanOrEqual(Farm::BASE_ANIMAL_CAPACITY, $user->farm->fresh()->animals->count());
    }
}
