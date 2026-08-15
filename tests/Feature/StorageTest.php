<?php

namespace Tests\Feature;

use App\Models\CropType;
use App\Models\Farm;
use App\Models\MachineryType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class StorageTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_base_storage_capacity_is_fifty(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();

        $this->assertSame(Farm::BASE_STORAGE_CAPACITY, $user->farm->storageCapacity());
    }

    public function test_storage_barn_increases_capacity(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['xp' => Farm::XP_PER_LEVEL, 'cash' => 1000]); // level 2, unlocks storage_barn
        $barn = MachineryType::where('key', 'storage_barn')->firstOrFail();

        $this->actingAs($user)->post('/machinery/buy', ['machinery_type_id' => $barn->id]);

        $this->assertSame(Farm::BASE_STORAGE_CAPACITY + 100, $user->farm->fresh()->storageCapacity());
    }

    public function test_harvest_beyond_storage_capacity_is_wasted(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $farm = $user->farm;
        $wheat = CropType::where('key', 'wheat')->firstOrFail(); // yield 5

        // Fill storage to 48/50 so only 2 of the next 5 harvested units fit.
        $farm->inventoryItems()->create(['item_key' => 'filler', 'quantity' => 48]);

        $field = $farm->fields->first();
        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post("/fields/{$field->id}/harvest");

        $wheatItem = $farm->inventoryItems()->where('item_key', 'wheat')->first();
        $this->assertSame(2, $wheatItem->quantity);
        $this->assertSame(50, $farm->inventoryUsed());

        $waste = $farm->transactions()->where('type', 'storage_full')->first();
        $this->assertNotNull($waste);
        $this->assertStringContainsString('3x Wheat', $waste->description);
    }
}
