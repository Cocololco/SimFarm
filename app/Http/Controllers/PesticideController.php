<?php

namespace App\Http\Controllers;

use App\Services\FarmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PesticideController extends Controller
{
    public function store(Request $request, FarmService $farmService): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $farm = $request->user()->farm;

        $farmService->buyPesticide($farm, (int) $data['quantity']);

        return back()->with('status', "Bought {$data['quantity']}x pesticide.");
    }
}
