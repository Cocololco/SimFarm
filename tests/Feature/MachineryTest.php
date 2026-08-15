<?php

namespace Tests\Feature;

use App\Models\AnimalType;
use App\Models\CropType;
use App\Models\MachineryType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class MachineryTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_user_can_buy_machinery(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 1000]);
        $tractor = MachineryType::where('key', 'tractor')->firstOrFail();

        $response = $this->actingAs($user)->post('/machinery/buy', ['machinery_type_id' => $tractor->id]);

        $response->assertRedirect();
        $farm = $user->farm->fresh();
        $this->assertCount(1, $farm->machinery);
        $this->assertEquals(1000 - $tractor->price, $farm->cash);
    }

    public function test_user_cannot_buy_the_same_machine_twice(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 1000]);
        $tractor = MachineryType::where('key', 'tractor')->firstOrFail();
        $this->actingAs($user)->post('/machinery/buy', ['machinery_type_id' => $tractor->id]);

        $response = $this->actingAs($user)->post('/machinery/buy', ['machinery_type_id' => $tractor->id]);

        $response->assertSessionHasErrors('machinery');
        $this->assertCount(1, $user->farm->fresh()->machinery);
    }

    public function test_growth_speed_machinery_shortens_time_to_harvest(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 1000]);
        $tractor = MachineryType::where('key', 'tractor')->firstOrFail(); // +20% growth_speed
        $this->actingAs($user)->post('/machinery/buy', ['machinery_type_id' => $tractor->id]);

        $corn = CropType::where('key', 'corn')->firstOrFail(); // growth_days = 4
        $field = $user->farm->fresh()->fields->first();
        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $corn->id]);

        // 4 days * (1 - 0.20) = 3.2 -> ceil -> 3 days instead of 4.
        $this->assertSame(3, $field->fresh()->effectiveGrowthDays());
    }

    public function test_yield_boost_machinery_increases_harvest_amount(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 1000]);
        $irrigation = MachineryType::where('key', 'irrigation')->firstOrFail(); // +25% yield_boost
        $this->actingAs($user)->post('/machinery/buy', ['machinery_type_id' => $irrigation->id]);

        $wheat = CropType::where('key', 'wheat')->firstOrFail(); // yield_amount = 5
        $field = $user->farm->fresh()->fields->first();
        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);

        // 5 * 1.25 = 6.25 -> floor -> 6
        $this->assertSame(6, $field->fresh()->harvestYield());
    }

    public function test_feed_discount_machinery_reduces_feed_cost(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 1000]);
        $silo = MachineryType::where('key', 'feed_silo')->firstOrFail(); // -30% feed_discount
        $this->actingAs($user)->post('/machinery/buy', ['machinery_type_id' => $silo->id]);

        $chicken = AnimalType::where('key', 'chicken')->firstOrFail(); // feed_cost = 2
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $animal = $user->farm->fresh()->animals->first();
        $cashBefore = (float) $user->farm->fresh()->cash;

        $this->actingAs($user)->post("/animals/{$animal->id}/feed");

        $expectedCost = round(2 * (1 - 0.30), 2);
        $this->assertEquals($cashBefore - $expectedCost, (float) $user->farm->fresh()->cash);
    }
}
