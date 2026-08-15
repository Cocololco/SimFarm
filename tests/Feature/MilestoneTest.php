<?php

namespace Tests\Feature;

use App\Services\MilestoneService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class MilestoneTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_reaching_a_milestone_pays_out_cash_and_xp(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 1000]); // crosses the $1,000 threshold
        $service = app(MilestoneService::class);
        $xpBefore = $user->farm->fresh()->xp;

        $newly = $service->checkAndReward($user->farm->fresh());

        $this->assertCount(1, $newly);
        $this->assertSame(1000, $newly->first()['threshold']);
        $farm = $user->farm->fresh();
        $this->assertEquals(1050, (float) $farm->cash); // 1000 + 5% of 1000
        $this->assertSame($xpBefore + 20, $farm->xp); // 1000 / 50
    }

    public function test_milestone_is_not_paid_out_twice(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 1000]);
        $service = app(MilestoneService::class);

        $service->checkAndReward($user->farm->fresh());
        $second = $service->checkAndReward($user->farm->fresh());

        $this->assertCount(0, $second);
    }

    public function test_multiple_thresholds_can_be_reached_at_once(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 12000]); // crosses $1,000, $5,000, $10,000
        $service = app(MilestoneService::class);

        $newly = $service->checkAndReward($user->farm->fresh());

        $this->assertCount(3, $newly);
        $this->assertSame([1000, 5000, 10000], $newly->pluck('threshold')->all());
    }

    public function test_dashboard_shows_progress_toward_the_next_milestone(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 200]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Next milestone');
    }

    public function test_milestone_reward_appears_on_dashboard_load(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 1000]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $this->assertEquals(1050, (float) $user->farm->fresh()->cash);
    }
}
