<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Farm;
use App\Models\Transaction;
use Illuminate\View\View;

class StatsController extends Controller
{
    public function index(): View
    {
        $stats = [
            'farm_count' => Farm::count(),
            'total_cash' => (float) Farm::sum('cash'),
            'total_harvests' => Transaction::where('type', 'harvest')->count(),
            'total_animals' => Animal::count(),
            'highest_level_farm' => Farm::with('user')->get()->sortByDesc('level')->first(),
            'oldest_farm_day' => (int) (Farm::max('current_day') ?? 1),
            // Harvest descriptions read "Harvested Nx CropName.(+bonus note)";
            // the crop name is whatever precedes the first period.
            'top_crop' => Transaction::where('type', 'harvest')
                ->get()
                ->map(fn (Transaction $t) => preg_match('/^Harvested \d+x ([^.]+)\./', $t->description, $m) ? $m[1] : null)
                ->filter()
                ->countBy()
                ->sortDesc()
                ->keys()
                ->first(),
        ];

        return view('stats', ['stats' => $stats]);
    }
}
