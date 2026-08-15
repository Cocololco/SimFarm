<?php

namespace Tests\Feature;

use App\Models\CropType;
use App\Services\FarmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class FertilizerTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_user_can_buy_fertilizer(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $cashBefore = (float) $user->farm->cash;

        $response = $this->actingAs($user)->post('/fertilizer/buy', ['quantity' => 3]);

        $response->assertRedirect();
        $farm = $user->farm->fresh();
        $this->assertSame(3, $farm->fertilizer_count);
        $this->assertEquals($cashBefore - (3 * FarmService::FERTILIZER_PRICE), (float) $farm->cash);
    }

    public function test_user_cannot_buy_fertilizer_without_enough_cash(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 1]);

        $response = $this->actingAs($user)->post('/fertilizer/buy', ['quantity' => 1]);

        $response->assertSessionHasErrors('cash');
        $this->assertSame(0, $user->farm->fresh()->fertilizer_count);
    }

    public function test_user_can_apply_fertilizer_to_a_growing_field(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $wheat = CropType::where('key', 'wheat')->firstOrFail();
        $field = $user->farm->fields->first();
        $this->actingAs($user)->post('/fertilizer/buy', ['quantity' => 1]);
        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);

        $response = $this->actingAs($user)->post("/fields/{$field->id}/fertilize");

        $response->assertRedirect();
        $this->assertTrue($field->fresh()->fertilized);
        $this->assertSame(0, $user->farm->fresh()->fertilizer_count);
    }

    public function test_cannot_apply_fertilizer_without_any_in_stock(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $wheat = CropType::where('key', 'wheat')->firstOrFail();
        $field = $user->farm->fields->first();
        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);

        $response = $this->actingAs($user)->post("/fields/{$field->id}/fertilize");

        $response->assertSessionHasErrors('fertilizer');
        $this->assertFalse($field->fresh()->fertilized);
    }

    public function test_cannot_apply_fertilizer_to_an_empty_field(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $field = $user->farm->fields->first();
        $this->actingAs($user)->post('/fertilizer/buy', ['quantity' => 1]);

        $response = $this->actingAs($user)->post("/fields/{$field->id}/fertilize");

        $response->assertSessionHasErrors('field');
        $this->assertSame(1, $user->farm->fresh()->fertilizer_count);
    }

    public function test_cannot_apply_fertilizer_twice_to_the_same_field(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $wheat = CropType::where('key', 'wheat')->firstOrFail();
        $field = $user->farm->fields->first();
        $this->actingAs($user)->post('/fertilizer/buy', ['quantity' => 2]);
        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);
        $this->actingAs($user)->post("/fields/{$field->id}/fertilize");

        $response = $this->actingAs($user)->post("/fields/{$field->id}/fertilize");

        $response->assertSessionHasErrors('field');
        $this->assertSame(1, $user->farm->fresh()->fertilizer_count);
    }

    public function test_fertilized_field_yields_more_and_resets_after_harvest(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $wheat = CropType::where('key', 'wheat')->firstOrFail(); // yield 5
        $field = $user->farm->fields->first();
        $this->actingAs($user)->post('/fertilizer/buy', ['quantity' => 1]);
        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);
        $this->actingAs($user)->post("/fields/{$field->id}/fertilize");

        // 5 * 1.20 = 6
        $this->assertSame(6, $field->fresh()->harvestYield());

        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post("/fields/{$field->id}/harvest");

        $farm = $user->farm->fresh();
        $this->assertSame(6, $farm->inventoryItems()->where('item_key', 'wheat')->first()->quantity);
        $this->assertFalse($field->fresh()->fertilized);
    }
}
