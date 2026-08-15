<?php

namespace Tests\Feature;

use App\Models\CropType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class FieldTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_user_can_plant_a_seed_in_an_empty_field(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $wheat = CropType::where('key', 'wheat')->firstOrFail();
        $field = $user->farm->fields->first();

        $response = $this->actingAs($user)->post("/fields/{$field->id}/plant", [
            'crop_type_id' => $wheat->id,
        ]);

        $response->assertRedirect();
        $field->refresh();
        $this->assertEquals($wheat->id, $field->crop_type_id);
        $this->assertSame(1, $field->planted_on_day);
        $this->assertEquals(500 - $wheat->seed_price, $user->farm->fresh()->cash);
    }

    public function test_user_cannot_plant_without_enough_cash(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 1]);
        $wheat = CropType::where('key', 'wheat')->firstOrFail();
        $field = $user->farm->fields->first();

        $response = $this->actingAs($user)->post("/fields/{$field->id}/plant", [
            'crop_type_id' => $wheat->id,
        ]);

        $response->assertSessionHasErrors('cash');
        $this->assertNull($field->fresh()->crop_type_id);
    }

    public function test_field_is_not_ready_until_growth_days_have_passed(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $wheat = CropType::where('key', 'wheat')->firstOrFail(); // growth_days = 2
        $field = $user->farm->fields->first();
        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);

        $harvestAttempt = $this->actingAs($user)->post("/fields/{$field->id}/harvest");
        $harvestAttempt->assertSessionHasErrors('field');

        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post('/turn/end');

        $response = $this->actingAs($user)->post("/fields/{$field->id}/harvest");
        $response->assertRedirect();

        $field->refresh();
        $this->assertNull($field->crop_type_id);

        $inventory = $user->farm->fresh()->inventoryItems()->where('item_key', 'wheat')->first();
        $this->assertNotNull($inventory);
        $this->assertEquals($wheat->yield_amount, $inventory->quantity);
    }

    public function test_user_cannot_act_on_another_users_field(): void
    {
        $this->seedCatalogs();
        $owner = $this->createUserWithFarm();
        $intruder = $this->createUserWithFarm();
        $wheat = CropType::where('key', 'wheat')->firstOrFail();
        $field = $owner->farm->fields->first();

        $response = $this->actingAs($intruder)->post("/fields/{$field->id}/plant", [
            'crop_type_id' => $wheat->id,
        ]);

        $response->assertSessionHasErrors('field');
        $this->assertNull($field->fresh()->crop_type_id);
    }

    public function test_user_can_buy_a_new_field(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $startingSlots = $user->farm->field_slots;

        $response = $this->actingAs($user)->post('/fields/buy');

        $response->assertRedirect();
        $farm = $user->farm->fresh();
        $this->assertSame($startingSlots + 1, $farm->field_slots);
        $this->assertCount($startingSlots + 1, $farm->fields);
    }
}
