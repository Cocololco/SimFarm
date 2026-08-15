<?php

namespace App\Http\Controllers;

use App\Models\MachineryType;
use App\Services\FarmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MachineryController extends Controller
{
    public function store(Request $request, FarmService $farmService): RedirectResponse
    {
        $data = $request->validate([
            'machinery_type_id' => ['required', 'exists:machinery_types,id'],
        ]);

        $farm = $request->user()->farm;
        $machineryType = MachineryType::findOrFail($data['machinery_type_id']);

        $farmService->buyMachinery($farm, $machineryType);

        return back()->with('status', "Bought a {$machineryType->name}.");
    }
}
