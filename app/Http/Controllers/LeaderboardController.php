<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function index(): View
    {
        $rankings = Farm::with(['user', 'animals.animalType', 'machinery.machineryType', 'inventoryItems', 'loans'])
            ->get()
            ->map(fn (Farm $farm) => [
                'farm' => $farm,
                'net_worth' => $farm->netWorth(),
            ])
            ->sortByDesc('net_worth')
            ->values();

        return view('leaderboard', ['rankings' => $rankings]);
    }
}
