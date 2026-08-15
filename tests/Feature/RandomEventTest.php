<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class RandomEventTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    /**
     * Random events are probabilistic, so instead of asserting an exact
     * outcome we assert the invariants hold over many days: the day always
     * advances, cash never goes negative, and any logged event's amount is
     * within the known bounds of the event catalog.
     */
    public function test_ending_many_days_keeps_cash_non_negative_and_events_within_bounds(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 500]);

        for ($i = 0; $i < 30; $i++) {
            $this->actingAs($user)->post('/turn/end');
        }

        $farm = $user->farm->fresh();
        $this->assertSame(31, $farm->current_day);
        $this->assertGreaterThanOrEqual(0, (float) $farm->cash);

        $eventAmounts = $farm->transactions()->where('type', 'event')->pluck('amount');
        foreach ($eventAmounts as $amount) {
            if (! is_null($amount)) {
                $this->assertGreaterThanOrEqual(-20, (float) $amount);
                $this->assertLessThanOrEqual(40, (float) $amount);
            }
        }
    }
}
