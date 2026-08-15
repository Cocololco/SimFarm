<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Services\FarmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function sell(Request $request, InventoryItem $item, FarmService $farmService): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $farm = $request->user()->farm;
        $productName = $item->product()['name'];

        $farmService->sellInventory($farm, $item, $data['quantity']);

        return back()->with('status', "Sold {$data['quantity']}x {$productName}.");
    }

    public function sellAll(Request $request, FarmService $farmService): RedirectResponse
    {
        $farm = $request->user()->farm;

        $total = $farmService->sellAllInventory($farm);

        $message = $total > 0 ? 'Sold everything for $'.number_format($total, 2).'.' : 'Nothing to sell.';

        return back()->with('status', $message);
    }
}
