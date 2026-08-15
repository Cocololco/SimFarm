<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\AnimalType;
use App\Services\FarmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AnimalController extends Controller
{
    public function store(Request $request, FarmService $farmService): RedirectResponse
    {
        $data = $request->validate([
            'animal_type_id' => ['required', 'exists:animal_types,id'],
        ]);

        $farm = $request->user()->farm;
        $animalType = AnimalType::findOrFail($data['animal_type_id']);

        $farmService->buyAnimal($farm, $animalType);

        return back()->with('status', "Bought a {$animalType->name}.");
    }

    public function feed(Request $request, Animal $animal, FarmService $farmService): RedirectResponse
    {
        $farm = $request->user()->farm;

        $farmService->feedAnimal($farm, $animal);

        return back()->with('status', "Fed {$animal->animalType->name}.");
    }

    public function destroy(Request $request, Animal $animal, FarmService $farmService): RedirectResponse
    {
        $farm = $request->user()->farm;
        $name = $animal->animalType->name;

        $farmService->sellAnimal($farm, $animal);

        return back()->with('status', "Sold {$name}.");
    }

    public function feedAll(Request $request, FarmService $farmService): RedirectResponse
    {
        $farm = $request->user()->farm;

        $count = $farmService->feedAllHungry($farm);

        $message = $count > 0 ? "Fed {$count} animal(s)." : 'No animals needed feeding (or you can\'t afford it).';

        return back()->with('status', $message);
    }
}
