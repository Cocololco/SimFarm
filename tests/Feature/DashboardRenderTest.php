<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class DashboardRenderTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_dashboard_renders_for_a_fresh_farm(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee($user->farm->name);
        $response->assertSee('Level 1');
        $response->assertSee('Day 1');
    }

    public function test_dashboard_renders_with_animals_machinery_loans_and_activity(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 2000]);
        $farm = $user->farm;

        $wheat = \App\Models\CropType::where('key', 'wheat')->firstOrFail();
        $chicken = \App\Models\AnimalType::where('key', 'chicken')->firstOrFail();
        $tractor = \App\Models\MachineryType::where('key', 'tractor')->firstOrFail();

        $this->actingAs($user)->post("/fields/{$farm->fields->first()->id}/plant", ['crop_type_id' => $wheat->id]);
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $this->actingAs($user)->post('/machinery/buy', ['machinery_type_id' => $tractor->id]);
        $this->actingAs($user)->post('/loans', ['amount' => 100]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Wheat');
        $response->assertSee('Chicken');
        $response->assertSee('Tractor');
        $response->assertSee('Outstanding balance');
    }

    public function test_activity_page_renders(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();

        $response = $this->actingAs($user)->get('/activity');

        $response->assertOk();
    }

    public function test_leaderboard_page_renders(): void
    {
        $this->seedCatalogs();
        $alice = $this->createUserWithFarm();
        $this->createUserWithFarm();

        $response = $this->actingAs($alice)->get('/leaderboard');

        $response->assertOk();
        $response->assertSee($alice->farm->user->name);
    }

    public function test_farm_settings_page_renders_and_updates_name(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();

        $response = $this->actingAs($user)->get('/settings/farm');
        $response->assertOk();

        $update = $this->actingAs($user)->patch('/settings/farm', ['name' => 'Sunny Acres']);
        $update->assertRedirect();

        $this->assertSame('Sunny Acres', $user->farm->fresh()->name);
    }
}
