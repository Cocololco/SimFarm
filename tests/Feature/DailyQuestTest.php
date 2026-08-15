<?php

namespace Tests\Feature;

use App\Models\AnimalType;
use App\Models\Farm;
use App\Models\User;
use App\Services\FarmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class DailyQuestTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_quest_is_deterministic_for_a_given_farm_and_day(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $service = app(FarmService::class);

        $first = $service->todaysQuest($user->farm);
        $second = $service->todaysQuest($user->farm->fresh());

        $this->assertSame($first, $second);
    }

    public function test_different_days_can_yield_different_quests(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $service = app(FarmService::class);
        $farm = $user->farm;

        $types = collect(range(1, 4))->map(function (int $day) use ($farm, $service) {
            $farm->update(['current_day' => $day]);

            return $service->todaysQuest($farm->fresh())['type'];
        });

        // Four consecutive days cycle through all four quest types exactly once.
        $this->assertCount(4, $types->unique());
    }

    /**
     * Advances the farm to a day whose deterministic quest is the "feed 2
     * animals" quest, so the test doesn't depend on incidental farm IDs.
     */
    private function advanceToFeedQuestDay(Farm $farm, FarmService $service): array
    {
        for ($day = 1; $day <= 4; $day++) {
            $farm->update(['current_day' => $day]);
            $quest = $service->todaysQuest($farm->fresh());

            if ($quest['type'] === 'feed') {
                return $quest;
            }
        }

        $this->fail('No day in a 4-day cycle yielded the feed quest — DAILY_QUESTS may have changed.');
    }

    public function test_completing_the_feed_quest_awards_reward_at_end_of_day(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 1000]);
        $service = app(FarmService::class);
        $quest = $this->advanceToFeedQuestDay($user->farm, $service);

        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $this->actingAs($user)->post('/animals/feed-all');

        $xpBefore = $user->farm->fresh()->xp;

        $this->actingAs($user)->post('/turn/end');

        $farm = $user->farm->fresh();
        // XP is untouched by the (also end-of-day) random cash events, so
        // it's a reliable signal the reward was actually granted.
        $this->assertSame($xpBefore + $quest['reward_xp'], $farm->xp);

        $reward = $farm->transactions()->where('type', 'quest_reward')->first();
        $this->assertNotNull($reward);
        $this->assertEquals($quest['reward_cash'], (float) $reward->amount);
    }

    public function test_quest_progress_reports_completion(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 1000]);
        $service = app(FarmService::class);
        $this->advanceToFeedQuestDay($user->farm, $service);

        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);

        $before = $service->questProgress($user->farm->fresh());
        $this->assertFalse($before['completed']);

        $this->actingAs($user)->post('/animals/feed-all');

        $after = $service->questProgress($user->farm->fresh());
        $this->assertTrue($after['completed']);
    }

    public function test_quest_is_not_completed_without_reaching_the_goal(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 1000]);
        $service = app(FarmService::class);
        $this->advanceToFeedQuestDay($user->farm, $service);

        // Feed only 1 animal, short of the goal of 2.
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $animal = $user->farm->fresh()->animals->first();
        $this->actingAs($user)->post("/animals/{$animal->id}/feed");

        $this->actingAs($user)->post('/turn/end');

        $farm = $user->farm->fresh();
        $this->assertNull($farm->transactions()->where('type', 'quest_reward')->first());
    }
}
