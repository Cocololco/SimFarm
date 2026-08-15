<?php

namespace Tests\Feature;

use App\Models\AnimalType;
use App\Services\FarmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class AnimalNeglectTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_animal_is_lost_after_being_unfed_for_too_many_days(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $animal = $user->farm->fresh()->animals->first();

        // Never fed; neglect threshold is 3 days from purchase.
        for ($i = 0; $i < FarmService::ANIMAL_NEGLECT_DAYS; $i++) {
            $this->actingAs($user)->post('/turn/end');
        }

        $this->assertNull($animal->fresh());
        $farm = $user->farm->fresh();
        $this->assertCount(0, $farm->animals);
        $this->assertNotNull($farm->transactions()->where('type', 'animal_lost')->first());
    }

    public function test_animal_survives_if_fed_before_the_neglect_threshold(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $animal = $user->farm->fresh()->animals->first();

        $this->actingAs($user)->post('/turn/end'); // day 1 -> 2, unfed 1 day
        $this->actingAs($user)->post("/animals/{$animal->id}/feed"); // fed on day 2
        $this->actingAs($user)->post('/turn/end'); // day 2 -> 3
        $this->actingAs($user)->post('/turn/end'); // day 3 -> 4

        $this->assertNotNull($animal->fresh());
    }
}
