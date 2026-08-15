<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    /** How many of the most recent transactions to plot on the cash chart. */
    private const CHART_WINDOW = 40;

    private const CHART_WIDTH = 600;

    private const CHART_HEIGHT = 160;

    public function index(Request $request): View
    {
        $farm = $request->user()->farm;

        $transactions = $farm->transactions()->paginate(25);

        return view('activity', [
            'farm' => $farm,
            'transactions' => $transactions,
            'chart' => $this->buildCashChart($farm),
        ]);
    }

    /**
     * Reconstructs the farm's cash balance over its recent transaction
     * history (transactions store deltas, not snapshots) and lays the
     * result out as ready-to-draw SVG polyline/area points.
     *
     * @return array{points: string, area: string, points_data: array, min: float, max: float}|null
     */
    private function buildCashChart(Farm $farm): ?array
    {
        $recent = Transaction::where('farm_id', $farm->id)
            ->orderBy('id')
            ->get()
            ->slice(-self::CHART_WINDOW)
            ->values();

        if ($recent->count() < 2) {
            return null;
        }

        $sumInWindow = $recent->sum(fn (Transaction $t) => (float) ($t->amount ?? 0));
        $running = (float) $farm->cash - $sumInWindow;

        $series = collect([['day' => $recent->first()->day, 'balance' => $running, 'label' => 'Start of window']]);

        foreach ($recent as $transaction) {
            $running += (float) ($transaction->amount ?? 0);
            $series->push(['day' => $transaction->day, 'balance' => $running, 'label' => $transaction->description]);
        }

        $balances = $series->pluck('balance');
        $min = (float) $balances->min();
        $max = (float) $balances->max();
        $range = max(0.01, $max - $min);
        $count = $series->count();

        $pointsData = $series->values()->map(function (array $point, int $index) use ($count, $min, $range) {
            $x = $count > 1 ? ($index / ($count - 1)) * self::CHART_WIDTH : 0;
            $y = self::CHART_HEIGHT - (($point['balance'] - $min) / $range) * self::CHART_HEIGHT;

            return ['x' => round($x, 1), 'y' => round($y, 1), 'day' => $point['day'], 'balance' => $point['balance'], 'label' => $point['label']];
        });

        $points = $pointsData->map(fn ($p) => "{$p['x']},{$p['y']}")->implode(' ');
        $area = "0,".self::CHART_HEIGHT." {$points} ".self::CHART_WIDTH.','.self::CHART_HEIGHT;

        return [
            'points' => $points,
            'area' => $area,
            'points_data' => $pointsData->all(),
            'min' => $min,
            'max' => $max,
            'width' => self::CHART_WIDTH,
            'height' => self::CHART_HEIGHT,
        ];
    }
}
