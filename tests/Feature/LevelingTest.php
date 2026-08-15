<?php

namespace Tests\Feature;

use App\Models\AnimalType;
use App\Models\CropType;
use App\Models\Farm;
use App\Models\MachineryType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class LevelingTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_farm_level_is_derived_from_xp(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['xp' => 0]);
        $this->assertSame(1, $user->farm->level);

        $user->farm->update(['xp' => Farm::XP_PER_LEVEL]);
        $this->assertSame(2, $user->farm->fresh()->level);

        $user->farm->update(['xp' => Farm::XP_PER_LEVEL * 4]);
        $this->assertSame(5, $user->farm->fresh()->level);
    }

    public function test_harvesting_awards_xp(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $wheat = CropType::where('key', 'wheat')->firstOrFail(); // yield 5 -> 10 xp
        $field = $user->farm->fields->first();
        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post('/turn/end');

        $this->actingAs($user)->post("/fields/{$field->id}/harvest");

        $this->assertSame(10, $user->farm->fresh()->xp);
    }

    public function test_cannot_plant_a_crop_above_farm_level(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $strawberry = CropType::where('key', 'strawberry')->firstOrFail(); // required_level = 2
        $field = $user->farm->fields->first();

        $response = $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $strawberry->id]);

        $response->assertSessionHasErrors('crop_type');
        $this->assertNull($field->fresh()->crop_type_id);
    }

    public function test_can_plant_a_gated_crop_after_leveling_up(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['xp' => Farm::XP_PER_LEVEL]); // level 2
        $strawberry = CropType::where('key', 'strawberry')->firstOrFail();
        $field = $user->farm->fields->first();

        $response = $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $strawberry->id]);

        $response->assertRedirect();
        $this->assertEquals($strawberry->id, $field->fresh()->crop_type_id);
    }

    public function test_cannot_buy_an_animal_above_farm_level(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $goat = AnimalType::where('key', 'goat')->firstOrFail(); // required_level = 2

        $response = $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $goat->id]);

        $response->assertSessionHasErrors('animal_type');
        $this->assertCount(0, $user->farm->fresh()->animals);
    }

    public function test_cannot_buy_machinery_above_farm_level(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 1000]);
        $greenhouse = MachineryType::where('key', 'greenhouse')->firstOrFail(); // required_level = 3

        $response = $this->actingAs($user)->post('/machinery/buy', ['machinery_type_id' => $greenhouse->id]);

        $response->assertSessionHasErrors('machinery_type');
        $this->assertCount(0, $user->farm->fresh()->machinery);
    }
}
