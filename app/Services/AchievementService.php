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
     * Each entry maps a key to a numeric progress getter + goal, so the
     * same definitions power both unlock checks and locked-achievement
     * progress hints in the UI.
     *
     * @return array<string, array{progress: callable(Farm): float, goal: float}>
     */
    private function definitions(): array
    {
        return [
            'first_harvest' => [
                'progress' => fn (Farm $f) => $f->transactions->where('type', 'harvest')->isNotEmpty() ? 1 : 0,
                'goal' => 1,
            ],
            'first_animal' => [
                'progress' => fn (Farm $f) => $f->transactions->where('type', 'buy_animal')->isNotEmpty() ? 1 : 0,
                'goal' => 1,
            ],
            'first_machine' => [
                'progress' => fn (Farm $f) => $f->transactions->where('type', 'buy_machinery')->isNotEmpty() ? 1 : 0,
                'goal' => 1,
            ],
            'harvest_veteran' => [
                'progress' => fn (Farm $f) => $f->transactions->where('type', 'harvest')->count(),
                'goal' => 10,
            ],
            'level_5' => [
                'progress' => fn (Farm $f) => $f->level,
                'goal' => 5,
            ],
            'net_worth_5000' => [
                'progress' => fn (Farm $f) => $f->netWorth(),
                'goal' => 5000,
            ],
            'gift_giver' => [
                'progress' => fn (Farm $f) => $f->transactions->whereIn('type', ['gift_sent', 'gift_item_sent'])->isNotEmpty() ? 1 : 0,
                'goal' => 1,
            ],
            'big_spender' => [
                'progress' => fn (Farm $f) => $f->transactions
                    ->filter(fn ($t) => ! is_null($t->amount) && (float) $t->amount < 0)
                    ->sum(fn ($t) => abs((float) $t->amount)),
                'goal' => 1000,
            ],
            'loan_free' => [
                'progress' => fn (Farm $f) => $f->loans->isNotEmpty() && $f->loans->contains(fn ($l) => (float) $l->balance <= 0) ? 1 : 0,
                'goal' => 1,
            ],
            'green_thumb' => [
                'progress' => fn (Farm $f) => $f->transactions
                    ->where('type', 'harvest')
                    ->filter(fn ($t) => str_contains($t->description, 'crop rotation'))
                    ->count(),
                'goal' => 5,
            ],
        ];
    }

    /**
     * @return Collection<int, Achievement> newly unlocked achievements
     */
    public function checkAndUnlock(Farm $farm): Collection
    {
        $farm->loadMissing(['transactions', 'achievements', 'loans']);

        $unlockedKeys = $farm->achievements->pluck('key');
        $newlyUnlocked = collect();

        foreach ($this->definitions() as $key => $definition) {
            if ($unlockedKeys->contains($key) || $definition['progress']($farm) < $definition['goal']) {
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

    /**
     * Progress toward each still-locked achievement, keyed by achievement
     * key, for display as e.g. "6 / 10" on the locked badge.
     *
     * @return array<string, array{progress: float, goal: float}>
     */
    public function progressFor(Farm $farm): array
    {
        $farm->loadMissing(['transactions', 'loans']);

        $progress = [];

        foreach ($this->definitions() as $key => $definition) {
            $progress[$key] = [
                'progress' => min($definition['progress']($farm), $definition['goal']),
                'goal' => $definition['goal'],
            ];
        }

        return $progress;
    }
}
