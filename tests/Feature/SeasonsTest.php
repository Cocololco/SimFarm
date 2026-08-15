<?php

namespace Tests\Feature;

use App\Models\CropType;
use App\Models\Farm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class SeasonsTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_season_cycles_every_seven_days(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $farm = $user->farm;

        $farm->update(['current_day' => 1]);
        $this->assertSame('spring', $farm->fresh()->currentSeason());

        $farm->update(['current_day' => 8]);
        $this->assertSame('summer', $farm->fresh()->currentSeason());

        $farm->update(['current_day' => 15]);
        $this->assertSame('fall', $farm->fresh()->currentSeason());

        $farm->update(['current_day' => 22]);
        $this->assertSame('winter', $farm->fresh()->currentSeason());

        $farm->update(['current_day' => 29]); // cycle repeats
        $this->assertSame('spring', $farm->fresh()->currentSeason());
    }

    public function test_harvesting_in_season_earns_a_yield_bonus(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $farm = $user->farm;
        $farm->update(['current_day' => 1]); // spring
        $wheat = CropType::where('key', 'wheat')->firstOrFail(); // no season
        $carrot = CropType::where('key', 'carrot')->firstOrFail(); // yield 4, season spring
        $field = $farm->fields->first();

        // Plant+harvest wheat first so the carrot planting also earns the
        // rotation bonus — a lone +10% doesn't survive flooring at this
        // yield size, so stack it with rotation to see a visible change.
        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post("/fields/{$field->id}/harvest");

        $this->assertSame('spring', $farm->fresh()->currentSeason()); // still day 3, still spring
        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $carrot->id]);

        $this->assertTrue($field->fresh()->isInSeason());
        $this->assertTrue($field->fresh()->isRotated());
        // 4 * (1 + 0.15 rotation + 0.10 season) = 5.0 -> floor -> 5
        $this->assertSame(5, $field->fresh()->harvestYield());
    }

    public function test_harvesting_out_of_season_earns_no_seasonal_bonus(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $farm = $user->farm;
        $farm->update(['current_day' => 1]); // spring
        $corn = CropType::where('key', 'corn')->firstOrFail(); // summer crop
        $field = $farm->fields->first();

        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $corn->id]);

        $this->assertFalse($field->fresh()->isInSeason());
        $this->assertSame(4, $field->fresh()->harvestYield());
    }

    public function test_a_crop_with_no_season_is_never_in_or_out_of_season(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $wheat = CropType::where('key', 'wheat')->firstOrFail(); // season = null
        $field = $user->farm->fields->first();

        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);

        $this->assertFalse($field->fresh()->isInSeason());
        $this->assertSame(5, $field->fresh()->harvestYield());
    }
}
