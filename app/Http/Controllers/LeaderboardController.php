<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $sort = $request->query('sort', 'net_worth');

        $rankings = Farm::with(['user', 'animals.animalType', 'machinery.machineryType', 'inventoryItems', 'loans'])
            ->get()
            ->when($search !== '', fn ($farms) => $farms->filter(
                fn (Farm $farm) => str_contains(strtolower($farm->name), strtolower($search))
                    || str_contains(strtolower($farm->user->name), strtolower($search))
            ))
            ->map(fn (Farm $farm) => [
                'farm' => $farm,
                'net_worth' => $farm->netWorth(),
            ]);

        $rankings = $sort === 'level'
            ? $rankings->sortByDesc(fn ($row) => $row['farm']->level)
            : $rankings->sortByDesc('net_worth');

        return view('leaderboard', [
            'rankings' => $rankings->values(),
            'search' => $search,
            'sort' => $sort,
        ]);
    }
}
