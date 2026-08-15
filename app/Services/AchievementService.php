<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Farm;
use Illuminate\Support\Collection;

/**
 * Checks a farm's stats against the achievement catalog and unlocks any
 * newly-earned ones. Cheap enough to call on every dashboard load rather
 * than tracking extra counters.
 */
class AchievementService
{
    /**
     * @return Collection<int, Achievement> newly unlocked achievements
     */
    public function checkAndUnlock(Farm $farm): Collection
    {
        $farm->loadMissing(['transactions', 'achievements']);

        $unlockedKeys = $farm->achievements->pluck('key');

        $checks = [
            'first_harvest' => fn () => $farm->transactions->where('type', 'harvest')->isNotEmpty(),
            'first_animal' => fn () => $farm->transactions->where('type', 'buy_animal')->isNotEmpty(),
            'first_machine' => fn () => $farm->transactions->where('type', 'buy_machinery')->isNotEmpty(),
            'harvest_veteran' => fn () => $farm->transactions->where('type', 'harvest')->count() >= 10,
            'level_5' => fn () => $farm->level >= 5,
            'net_worth_5000' => fn () => $farm->netWorth() >= 5000,
        ];

        $newlyUnlocked = collect();

        foreach ($checks as $key => $isEarned) {
            if ($unlockedKeys->contains($key) || ! $isEarned()) {
                continue;
            }

            $achievement = Achievement::where('key', $key)->first();

            if (! $achievement) {
                continue;
            }

            $farm->achievements()->attach($achievement->id, ['unlocked_on_day' => $farm->current_day]);
            $newlyUnlocked->push($achievement);
        }

        return $newlyUnlocked;
    }
}
