<?php

namespace App\Http\Controllers;

use App\Models\AnimalType;
use App\Models\CropType;
use App\Models\MachineryType;
use App\Services\FarmService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmController extends Controller
{
    public function show(Request $request, FarmService $farmService): View
    {
        $farm = $request->user()->farm()->with([
            'fields.cropType',
            'animals.animalType',
            'machinery.machineryType',
            'inventoryItems',
        ])->firstOrFail();

        return view('dashboard', [
            'farm' => $farm,
            'cropTypes' => CropType::orderBy('seed_price')->get(),
            'animalTypes' => AnimalType::orderBy('buy_price')->get(),
            'machineryTypes' => MachineryType::orderBy('price')->get(),
            'nextFieldCost' => $farmService->fieldCost($farm),
        ]);
    }
}
