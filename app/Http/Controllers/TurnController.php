<?php

namespace App\Http\Controllers;

use App\Services\FarmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TurnController extends Controller
{
    public function end(Request $request, FarmService $farmService): RedirectResponse
    {
        $farm = $request->user()->farm;

        $farmService->endDay($farm);

        return back()->with('status', 'A new day has begun.');
    }
}
