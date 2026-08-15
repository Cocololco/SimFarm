<?php

namespace App\Http\Controllers;

use App\Services\FarmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FertilizerController extends Controller
{
    public function store(Request $request, FarmService $farmService): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $farm = $request->user()->farm;

        $farmService->buyFertilizer($farm, (int) $data['quantity']);

        return back()->with('status', "Bought {$data['quantity']}x fertilizer.");
    }
}
