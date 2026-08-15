<?php

namespace Tests\Feature;

use App\Models\AnimalType;
use App\Models\CropType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class QuickActionsTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_harvest_all_harvests_every_ready_field_only(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $wheat = CropType::where('key', 'wheat')->firstOrFail(); // growth_days 2
        $carrot = CropType::where('key', 'carrot')->firstOrFail(); // growth_days 3
        $fields = $user->farm->fields;

        // Plant wheat in two fields, carrot (slower) in a third; leave the fourth empty.
        $this->actingAs($user)->post("/fields/{$fields[0]->id}/plant", ['crop_type_id' => $wheat->id]);
        $this->actingAs($user)->post("/fields/{$fields[1]->id}/plant", ['crop_type_id' => $wheat->id]);
        $this->actingAs($user)->post("/fields/{$fields[2]->id}/plant", ['crop_type_id' => $carrot->id]);

        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post('/turn/end'); // wheat ready (2 days), carrot not (needs 3)

        $response = $this->actingAs($user)->post('/fields/harvest-all');

        $response->assertRedirect();
        $farm = $user->farm->fresh();
        $this->assertNull($fields[0]->fresh()->crop_type_id);
        $this->assertNull($fields[1]->fresh()->crop_type_id);
        $this->assertNotNull($fields[2]->fresh()->crop_type_id); // carrot still growing
        $this->assertSame(10, $farm->inventoryItems()->where('item_key', 'wheat')->first()->quantity);
    }

    public function test_feed_all_feeds_every_unfed_affordable_animal(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 1000]);
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);

        $response = $this->actingAs($user)->post('/animals/feed-all');

        $response->assertRedirect();
        $farm = $user->farm->fresh();
        $this->assertTrue($farm->animals->every(fn ($a) => $a->isFedToday()));
    }

    public function test_feed_all_skips_animals_it_cannot_afford(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 41]); // just enough for exactly one chicken feed (2)
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail(); // buy 40, feed 2
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        // cash now 1 — not enough to feed (2)

        $response = $this->actingAs($user)->post('/animals/feed-all');

        $response->assertRedirect();
        $animal = $user->farm->fresh()->animals->first();
        $this->assertFalse($animal->isFedToday());
    }
}
