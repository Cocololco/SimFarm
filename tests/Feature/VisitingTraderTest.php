<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class VisitingTraderTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    /**
     * The trader event is probabilistic (~7%/day), so this runs enough
     * days that the chance of it never firing is negligible (~0.1%)
     * rather than forcing a specific roll.
     */
    public function test_visiting_trader_eventually_buys_a_stocked_item_at_a_premium(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $farm = $user->farm;
        $farm->inventoryItems()->create(['item_key' => 'wheat', 'quantity' => 10]);

        for ($i = 0; $i < 100; $i++) {
            $this->actingAs($user)->post('/turn/end');
        }

        $visit = $farm->fresh()->transactions()
            ->where('type', 'trader_visit')
            ->whereNotNull('amount')
            ->first();

        $this->assertNotNull($visit);
        // 10 * $3 * 1.75 = $52.50, paid in one lump sum.
        $this->assertEquals(52.5, (float) $visit->amount);
    }

    public function test_visiting_trader_with_nothing_to_sell_has_no_cash_effect(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();

        for ($i = 0; $i < 100; $i++) {
            $this->actingAs($user)->post('/turn/end');
        }

        $visits = $user->farm->fresh()->transactions()->where('type', 'trader_visit')->get();

        // Every logged visit (if any occurred at all) must be a no-op,
        // since this farm never had any inventory to sell.
        foreach ($visits as $visit) {
            $this->assertNull($visit->amount);
        }
    }
}
