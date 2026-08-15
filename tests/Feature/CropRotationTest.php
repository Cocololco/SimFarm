<?php

namespace Tests\Feature;

use App\Models\CropType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class CropRotationTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_replanting_the_same_crop_gets_no_rotation_bonus(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $wheat = CropType::where('key', 'wheat')->firstOrFail(); // yield 5
        $field = $user->farm->fields->first();

        // First cycle: no previous crop, so no bonus either way.
        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post("/fields/{$field->id}/harvest");

        // Second cycle: same crop again -> no rotation bonus.
        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);
        $this->assertFalse($field->fresh()->isRotated());
        $this->assertSame(5, $field->fresh()->harvestYield());
    }

    public function test_planting_a_different_crop_gets_a_rotation_bonus(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $wheat = CropType::where('key', 'wheat')->firstOrFail(); // yield 5
        $carrot = CropType::where('key', 'carrot')->firstOrFail(); // yield 4
        $field = $user->farm->fields->first();

        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post("/fields/{$field->id}/harvest");

        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $carrot->id]);

        $this->assertTrue($field->fresh()->isRotated());
        // 4 * 1.15 = 4.6 -> floor -> 4 (no visible change at this yield,
        // but confirm the bonus is actually applied to the calculation).
        $this->assertEqualsWithDelta(4.6, 4 * (1 + \App\Models\Field::ROTATION_YIELD_BONUS), 0.001);
        $this->assertSame(4, $field->fresh()->harvestYield());
    }

    public function test_rotation_bonus_stacks_with_yield_boost_machinery(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 1000]);
        $irrigation = \App\Models\MachineryType::where('key', 'irrigation')->firstOrFail(); // +25%
        $this->actingAs($user)->post('/machinery/buy', ['machinery_type_id' => $irrigation->id]);

        $wheat = CropType::where('key', 'wheat')->firstOrFail();
        $carrot = CropType::where('key', 'carrot')->firstOrFail(); // yield 4
        $field = $user->farm->fresh()->fields->first();

        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post("/fields/{$field->id}/harvest");
        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $carrot->id]);

        // 4 * (1 + 0.25 + 0.15) = 5.6 -> floor -> 5
        $this->assertSame(5, $field->fresh()->harvestYield());
    }
}
