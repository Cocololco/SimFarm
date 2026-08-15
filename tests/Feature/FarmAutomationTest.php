<?php

namespace Tests\Feature;

use App\Models\AnimalType;
use App\Models\CropType;
use App\Models\Farm;
use App\Models\MachineryType;
use App\Services\FarmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class FarmAutomationTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_user_can_buy_pesticide(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $cashBefore = (float) $user->farm->cash;

        $response = $this->actingAs($user)->post('/pesticide/buy', ['quantity' => 2]);

        $response->assertRedirect();
        $farm = $user->farm->fresh();
        $this->assertSame(2, $farm->pesticide_count);
        $this->assertEquals($cashBefore - (2 * FarmService::PESTICIDE_PRICE), (float) $farm->cash);
    }

    public function test_farmhand_auto_feeds_animals_at_end_of_day(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['xp' => Farm::XP_PER_LEVEL * 2, 'cash' => 2000]); // level 3, unlocks farmhand
        $farmhand = MachineryType::where('key', 'farmhand')->firstOrFail();
        $this->actingAs($user)->post('/machinery/buy', ['machinery_type_id' => $farmhand->id]);

        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $animal = $user->farm->fresh()->animals->first();
        $this->assertFalse($animal->isFedToday());

        $this->actingAs($user)->post('/turn/end');

        $this->assertTrue($animal->fresh()->isFedToday());
    }

    public function test_without_farmhand_animals_are_not_auto_fed(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $animal = $user->farm->fresh()->animals->first();

        $this->actingAs($user)->post('/turn/end');

        $this->assertFalse($animal->fresh()->isFedToday());
    }

    public function test_auto_harvester_drone_harvests_ready_fields_at_end_of_day(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['xp' => Farm::XP_PER_LEVEL * 3, 'cash' => 2000]); // level 4, unlocks drone
        $drone = MachineryType::where('key', 'auto_harvester_drone')->firstOrFail();
        $this->actingAs($user)->post('/machinery/buy', ['machinery_type_id' => $drone->id]);

        $wheat = CropType::where('key', 'wheat')->firstOrFail(); // 2 days to grow
        $field = $user->farm->fresh()->fields->first();
        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);
        $this->actingAs($user)->post('/turn/end'); // day 1 -> 2, not ready yet

        $this->assertFalse($field->fresh()->isEmpty());

        $this->actingAs($user)->post('/turn/end'); // ready during this end-day, drone harvests it

        $this->assertTrue($field->fresh()->isEmpty());
        $wheatItem = $user->farm->fresh()->inventoryItems()->where('item_key', 'wheat')->first();
        $this->assertNotNull($wheatItem);
    }

    public function test_compost_bin_converts_wasted_harvest_into_fertilizer(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['xp' => Farm::XP_PER_LEVEL, 'cash' => 1000]); // level 2, unlocks compost bin
        $compostBin = MachineryType::where('key', 'compost_bin')->firstOrFail();
        $this->actingAs($user)->post('/machinery/buy', ['machinery_type_id' => $compostBin->id]);

        $farm = $user->farm->fresh();
        $wheat = CropType::where('key', 'wheat')->firstOrFail(); // yield 5
        // Fill storage to 48/50 so only 2 of the next 5 harvested units fit (3 wasted).
        $farm->inventoryItems()->create(['item_key' => 'filler', 'quantity' => 48]);
        $field = $farm->fields->first();

        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post("/fields/{$field->id}/harvest");

        // 3 wasted / COMPOST_WASTE_PER_FERTILIZER(3) = 1 fertilizer.
        $this->assertSame(1, $user->farm->fresh()->fertilizer_count);
    }

    /**
     * Pesticide blocking is probabilistic (only fires against a pest_trouble
     * roll), so this asserts the outcome over enough days that the chance
     * of zero pest rolls is astronomically small (~0.1%), rather than
     * forcing a specific roll.
     */
    public function test_pesticide_stock_blocks_pest_events_over_time(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 2000]);
        $this->actingAs($user)->post('/pesticide/buy', ['quantity' => 50]);

        for ($i = 0; $i < 80; $i++) {
            $this->actingAs($user)->post('/turn/end');
        }

        $farm = $user->farm->fresh();
        $this->assertLessThan(50, $farm->pesticide_count);
        $this->assertGreaterThanOrEqual(0, $farm->pesticide_count);
        $this->assertNotNull($farm->transactions()->where('type', 'pesticide_blocked')->first());
    }

    public function test_without_compost_bin_wasted_harvest_yields_no_fertilizer(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $farm = $user->farm;
        $wheat = CropType::where('key', 'wheat')->firstOrFail();
        $farm->inventoryItems()->create(['item_key' => 'filler', 'quantity' => 48]);
        $field = $farm->fields->first();

        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post("/fields/{$field->id}/harvest");

        $this->assertSame(0, $user->farm->fresh()->fertilizer_count);
    }
}
