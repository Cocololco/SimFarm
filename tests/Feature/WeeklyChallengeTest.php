<?php

namespace Tests\Feature;

use App\Models\AnimalType;
use App\Models\Farm;
use App\Services\FarmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class WeeklyChallengeTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_challenge_is_deterministic_per_farm_per_week(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $service = app(FarmService::class);

        $first = $service->todaysWeeklyChallenge($user->farm);
        $second = $service->todaysWeeklyChallenge($user->farm->fresh());

        $this->assertSame($first, $second);
    }

    public function test_week_index_advances_every_seven_days(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $service = app(FarmService::class);
        $farm = $user->farm;

        $farm->update(['current_day' => 1]);
        $this->assertSame(0, $service->currentWeekIndex($farm->fresh()));

        $farm->update(['current_day' => Farm::SEASON_LENGTH_DAYS]); // still week 0
        $this->assertSame(0, $service->currentWeekIndex($farm->fresh()));

        $farm->update(['current_day' => Farm::SEASON_LENGTH_DAYS + 1]); // week 1 begins
        $this->assertSame(1, $service->currentWeekIndex($farm->fresh()));
    }

    public function test_completing_the_weekly_feed_challenge_pays_out_on_the_last_day(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 2000]);
        $service = app(FarmService::class);
        $farm = $user->farm;

        // Find a week (within the first few cycles) whose deterministic
        // challenge is the feed one, so the test doesn't depend on farm id.
        $weekStartDay = null;
        for ($week = 0; $week < 6; $week++) {
            $probeDay = $week * Farm::SEASON_LENGTH_DAYS + 1;
            $farm->update(['current_day' => $probeDay]);
            if ($service->todaysWeeklyChallenge($farm->fresh())['type'] === 'feed') {
                $weekStartDay = $probeDay;
                break;
            }
        }
        $this->assertNotNull($weekStartDay, 'No week in 6 cycles yielded the feed challenge — WEEKLY_CHALLENGES may have changed.');

        $farm->update(['current_day' => $weekStartDay]);
        $challenge = $service->todaysWeeklyChallenge($farm->fresh());

        // Enough animals that feed-all every day for a week clears the goal.
        $animalsNeeded = (int) ceil($challenge['goal'] / Farm::SEASON_LENGTH_DAYS);
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        for ($i = 0; $i < $animalsNeeded; $i++) {
            $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        }

        for ($i = 0; $i < Farm::SEASON_LENGTH_DAYS; $i++) {
            $this->actingAs($user)->post('/animals/feed-all');
            $this->actingAs($user)->post('/turn/end');
        }

        $reward = $farm->fresh()->transactions()->where('type', 'weekly_challenge_reward')->first();
        $this->assertNotNull($reward);
    }

    public function test_weekly_challenge_progress_reports_completion(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 2000]);
        $service = app(FarmService::class);
        $farm = $user->farm;
        $farm->update(['current_day' => 1]);

        $before = $service->weeklyChallengeProgress($farm->fresh());
        $this->assertFalse($before['completed']);
        $this->assertSame(1, $before['day_in_week']);
    }
}
