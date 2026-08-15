<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use Illuminate\View\View;

class FarmProfileController extends Controller
{
    public function show(Farm $farm): View
    {
        $farm->load(['user', 'fields.cropType', 'animals.animalType', 'machinery.machineryType', 'achievements']);

        return view('farm-profile', ['farm' => $farm]);
    }
}
