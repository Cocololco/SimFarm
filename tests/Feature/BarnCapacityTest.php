<?php

namespace Tests\Feature;

use App\Models\AnimalType;
use App\Models\Farm;
use App\Models\MachineryType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class BarnCapacityTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_base_animal_capacity_is_six(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();

        $this->assertSame(Farm::BASE_ANIMAL_CAPACITY, $user->farm->animalCapacity());
    }

    public function test_cannot_buy_an_animal_beyond_capacity(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 1000]);
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();

        for ($i = 0; $i < Farm::BASE_ANIMAL_CAPACITY; $i++) {
            $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        }
        $this->assertCount(Farm::BASE_ANIMAL_CAPACITY, $user->farm->fresh()->animals);

        $response = $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);

        $response->assertSessionHasErrors('animal_type');
        $this->assertCount(Farm::BASE_ANIMAL_CAPACITY, $user->farm->fresh()->animals);
    }

    public function test_barn_expansion_increases_capacity(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['xp' => Farm::XP_PER_LEVEL, 'cash' => 1000]); // level 2
        $barnExpansion = MachineryType::where('key', 'barn_expansion')->firstOrFail();

        $this->actingAs($user)->post('/machinery/buy', ['machinery_type_id' => $barnExpansion->id]);

        $this->assertSame(Farm::BASE_ANIMAL_CAPACITY + 6, $user->farm->fresh()->animalCapacity());
    }
}
