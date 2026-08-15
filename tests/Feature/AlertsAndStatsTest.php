<?php

namespace Tests\Feature;

use App\Models\AnimalType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class AlertsAndStatsTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_alerts_page_shows_only_alert_worthy_transactions(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $farm = $user->farm;
        $farm->transactions()->create(['day' => 1, 'type' => 'plant', 'description' => 'Planted Wheat.', 'amount' => -5]);
        $farm->transactions()->create(['day' => 1, 'type' => 'animal_lost', 'description' => 'Your Chicken ran away.', 'amount' => null]);

        $response = $this->actingAs($user)->get('/alerts');

        $response->assertOk();
        $response->assertSee('Your Chicken ran away');
        $response->assertDontSee('Planted Wheat');
    }

    public function test_stats_page_renders_with_platform_totals(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);

        $response = $this->actingAs($user)->get('/stats');

        $response->assertOk();
        $response->assertSee('Farms');
        $response->assertSee('Animals raised');
    }

    public function test_leaderboard_search_filters_by_name(): void
    {
        $this->seedCatalogs();
        $viewer = $this->createUserWithFarm();
        $this->createUserWithFarm(['name' => 'Sunny Acres']);
        $this->createUserWithFarm(['name' => 'Rocky Ridge']);

        $response = $this->actingAs($viewer)->get('/leaderboard?search=Sunny');

        $response->assertOk();
        $response->assertSee('Sunny Acres');
        $response->assertDontSee('Rocky Ridge');
    }

    public function test_leaderboard_can_sort_by_level(): void
    {
        $this->seedCatalogs();
        $viewer = $this->createUserWithFarm();

        $response = $this->actingAs($viewer)->get('/leaderboard?sort=level');

        $response->assertOk();
    }
}
