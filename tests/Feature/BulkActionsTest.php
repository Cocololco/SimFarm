<?php

namespace Tests\Feature;

use App\Models\CropType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class BulkActionsTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_sell_all_sells_every_inventory_item(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $farm = $user->farm;
        $farm->inventoryItems()->create(['item_key' => 'wheat', 'quantity' => 5]); // 5 * 3 = 15
        $farm->inventoryItems()->create(['item_key' => 'egg', 'quantity' => 2]); // 2 * 2 = 4
        $cashBefore = (float) $farm->cash;

        $response = $this->actingAs($user)->post('/inventory/sell-all');

        $response->assertRedirect();
        $this->assertEquals($cashBefore + 19, (float) $user->farm->fresh()->cash);
        $this->assertCount(0, $user->farm->fresh()->inventoryItems);
    }

    public function test_plant_all_fills_every_empty_field_it_can_afford(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 12]); // wheat seed = 5, affords 2
        $wheat = CropType::where('key', 'wheat')->firstOrFail();

        $response = $this->actingAs($user)->post('/fields/plant-all', ['crop_type_id' => $wheat->id]);

        $response->assertRedirect();
        $farm = $user->farm->fresh();
        $plantedCount = $farm->fields->filter(fn ($f) => ! $f->isEmpty())->count();
        $this->assertSame(2, $plantedCount);
        $this->assertEquals(2, (float) $farm->cash);
    }

    public function test_plant_all_skips_already_planted_fields(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $wheat = CropType::where('key', 'wheat')->firstOrFail();
        $field = $user->farm->fields->first();
        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);

        $this->actingAs($user)->post('/fields/plant-all', ['crop_type_id' => $wheat->id]);

        $farm = $user->farm->fresh();
        $plantedCount = $farm->fields->filter(fn ($f) => ! $f->isEmpty())->count();
        $this->assertSame(4, $plantedCount); // all 4 starter fields now planted
    }
}
