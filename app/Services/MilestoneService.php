<?php

namespace App\Services;

use App\Models\Farm;
use Illuminate\Support\Collection;

/**
 * A wealth ladder distinct from achievements: crossing a net-worth
 * threshold pays out real cash + XP, once, rather than just a badge.
 * "Already paid" is tracked by checking for a milestone transaction
 * mentioning that threshold, rather than a separate table.
 */
class MilestoneService
{
    public const THRESHOLDS = [1000, 5000, 10000, 25000, 50000];

    /**
     * @return Collection<int, array{threshold: int, reward_cash: float, reward_xp: int}> newly reached milestones
     */
    public function checkAndReward(Farm $farm): Collection
    {
        $farm->loadMissing('transactions');

        $netWorth = $farm->netWorth();
        $alreadyPaid = $farm->transactions->where('type', 'milestone');
        $newlyReached = collect();

        foreach (self::THRESHOLDS as $threshold) {
            if ($netWorth < $threshold) {
                break;
            }

            $marker = '$'.number_format($threshold);

            if ($alreadyPaid->contains(fn ($t) => str_contains($t->description, $marker))) {
                continue;
            }

            $rewardCash = round($threshold * 0.05, 2);
            $rewardXp = intdiv($threshold, 50);

            $farm->addCash($rewardCash);
            $farm->addXp($rewardXp);
            $farm->transactions()->create([
                'day' => $farm->current_day,
                'type' => 'milestone',
                'description' => "Reached a net worth of {$marker}!",
                'amount' => $rewardCash,
            ]);

            $newlyReached->push(['threshold' => $threshold, 'reward_cash' => $rewardCash, 'reward_xp' => $rewardXp]);
        }

        return $newlyReached;
    }

    /**
     * The next unreached threshold and progress toward it, for a dashboard
     * progress bar. Null once every threshold has been reached.
     */
    public function nextThreshold(Farm $farm): ?array
    {
        $netWorth = $farm->netWorth();

        foreach (self::THRESHOLDS as $threshold) {
            if ($netWorth < $threshold) {
                return ['threshold' => $threshold, 'progress' => max(0, $netWorth)];
            }
        }

        return null;
    }
}
