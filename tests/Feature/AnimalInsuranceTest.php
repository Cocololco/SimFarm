<?php

namespace Tests\Feature;

use App\Models\AnimalType;
use App\Services\FarmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class AnimalInsuranceTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_user_can_insure_an_animal(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $animal = $user->farm->fresh()->animals->first();
        $cashBefore = (float) $user->farm->fresh()->cash;

        $response = $this->actingAs($user)->post("/animals/{$animal->id}/insure");

        $response->assertRedirect();
        $this->assertTrue($animal->fresh()->isInsured());
        $this->assertEquals($cashBefore - FarmService::INSURANCE_PRICE, (float) $user->farm->fresh()->cash);
    }

    public function test_cannot_insure_an_already_insured_animal(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $animal = $user->farm->fresh()->animals->first();
        $this->actingAs($user)->post("/animals/{$animal->id}/insure");

        $response = $this->actingAs($user)->post("/animals/{$animal->id}/insure");

        $response->assertSessionHasErrors('animal');
    }

    public function test_insured_animal_survives_neglect_that_would_otherwise_lose_it(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $animal = $user->farm->fresh()->animals->first();
        $this->actingAs($user)->post("/animals/{$animal->id}/insure"); // 5 days of protection

        // Never fed; would normally be lost after 3 end-day calls.
        for ($i = 0; $i < FarmService::ANIMAL_NEGLECT_DAYS; $i++) {
            $this->actingAs($user)->post('/turn/end');
        }

        $this->assertNotNull($animal->fresh());
    }

    public function test_insurance_expires_after_its_duration(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $animal = $user->farm->fresh()->animals->first();
        $this->actingAs($user)->post("/animals/{$animal->id}/insure"); // covers days 1..6

        // Never fed; run well past both the neglect threshold and the
        // insurance window so protection has definitely lapsed.
        for ($i = 0; $i < FarmService::INSURANCE_DAYS + FarmService::ANIMAL_NEGLECT_DAYS + 1; $i++) {
            $this->actingAs($user)->post('/turn/end');
        }

        $this->assertNull($animal->fresh());
    }
}
