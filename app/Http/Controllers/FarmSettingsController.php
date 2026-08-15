<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('farm-settings', ['farm' => $request->user()->farm]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->farm->update(['name' => $data['name']]);

        return back()->with('status', 'Farm renamed.');
    }
}
