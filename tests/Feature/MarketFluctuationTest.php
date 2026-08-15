<?php

namespace Tests\Feature;

use App\Models\CropType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class MarketFluctuationTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_default_market_multiplier_is_one(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();

        $this->assertEquals(1.00, (float) $user->farm->market_multiplier);
    }

    public function test_selling_applies_the_market_multiplier(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $user->farm->update(['market_multiplier' => 1.20]);
        $wheat = CropType::where('key', 'wheat')->firstOrFail(); // sell_price 3
        $item = $user->farm->inventoryItems()->create(['item_key' => 'wheat', 'quantity' => 10]);
        $cashBefore = (float) $user->farm->fresh()->cash;

        $this->actingAs($user)->post("/inventory/{$item->id}/sell", ['quantity' => 10]);

        // 10 * 3 * 1.20 = 36.00
        $this->assertEquals($cashBefore + 36.00, (float) $user->farm->fresh()->cash);
    }

    public function test_market_multiplier_drifts_but_stays_within_bounds_over_many_days(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();

        for ($i = 0; $i < 40; $i++) {
            $this->actingAs($user)->post('/turn/end');
        }

        $multiplier = (float) $user->farm->fresh()->market_multiplier;
        $this->assertGreaterThanOrEqual(0.70, $multiplier);
        $this->assertLessThanOrEqual(1.30, $multiplier);
    }

    public function test_market_multiplier_changes_by_at_most_the_max_step_per_day(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $before = (float) $user->farm->market_multiplier;

        $this->actingAs($user)->post('/turn/end');

        $after = (float) $user->farm->fresh()->market_multiplier;
        $this->assertLessThanOrEqual(0.081, abs($after - $before));
    }
}
