<?php

namespace App\Http\Controllers;

use App\Models\CropType;
use App\Models\Field;
use App\Services\FarmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FieldController extends Controller
{
    public function plant(Request $request, Field $field, FarmService $farmService): RedirectResponse
    {
        $data = $request->validate([
            'crop_type_id' => ['required', 'exists:crop_types,id'],
        ]);

        $farm = $request->user()->farm;
        $cropType = CropType::findOrFail($data['crop_type_id']);

        $farmService->plant($farm, $field, $cropType);

        return back()->with('status', "Planted {$cropType->name}.");
    }

    public function harvest(Request $request, Field $field, FarmService $farmService): RedirectResponse
    {
        $farm = $request->user()->farm;
        $cropName = $field->cropType?->name ?? 'crop';

        $farmService->harvest($farm, $field);

        return back()->with('status', "Harvested {$cropName}.");
    }

    public function buy(Request $request, FarmService $farmService): RedirectResponse
    {
        $farm = $request->user()->farm;

        $farmService->buyField($farm);

        return back()->with('status', 'Bought a new field.');
    }

    public function harvestAll(Request $request, FarmService $farmService): RedirectResponse
    {
        $farm = $request->user()->farm;

        $count = $farmService->harvestAllReady($farm);

        $message = $count > 0 ? "Harvested {$count} field(s)." : 'Nothing was ready to harvest.';

        return back()->with('status', $message);
    }

    public function fertilize(Request $request, Field $field, FarmService $farmService): RedirectResponse
    {
        $farm = $request->user()->farm;

        $farmService->applyFertilizer($farm, $field);

        return back()->with('status', "Fertilized field #{$field->plot_number}.");
    }
}
