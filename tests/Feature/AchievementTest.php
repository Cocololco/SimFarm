<?php

namespace Tests\Feature;

use App\Models\AnimalType;
use App\Models\CropType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_first_harvest_achievement_unlocks_after_harvesting(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $wheat = CropType::where('key', 'wheat')->firstOrFail();
        $field = $user->farm->fields->first();
        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post("/fields/{$field->id}/harvest");

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $this->assertTrue($user->farm->fresh()->achievements->contains('key', 'first_harvest'));
    }

    public function test_first_animal_achievement_unlocks_after_buying_an_animal(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);

        $this->actingAs($user)->get('/dashboard');

        $this->assertTrue($user->farm->fresh()->achievements->contains('key', 'first_animal'));
    }

    public function test_achievement_is_not_unlocked_twice(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);

        $this->actingAs($user)->get('/dashboard');
        $this->actingAs($user)->get('/dashboard');

        $count = $user->farm->fresh()->achievements()->where('key', 'first_animal')->count();
        $this->assertSame(1, $count);
    }
}
