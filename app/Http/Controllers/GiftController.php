<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\User;
use App\Services\FarmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GiftController extends Controller
{
    public function store(Request $request, FarmService $farmService): RedirectResponse
    {
        $data = $request->validate([
            'recipient_email' => ['required', 'email'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $recipient = $this->findRecipient($data['recipient_email']);

        $farmService->giftCash($request->user()->farm, $recipient->farm, (float) $data['amount']);

        return back()->with('status', "Gift sent to {$recipient->name}!");
    }

    public function storeItem(Request $request, InventoryItem $item, FarmService $farmService): RedirectResponse
    {
        $data = $request->validate([
            'recipient_email' => ['required', 'email'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $recipient = $this->findRecipient($data['recipient_email']);

        $farmService->giftInventoryItem($request->user()->farm, $recipient->farm, $item, (int) $data['quantity']);

        return back()->with('status', "Sent goods to {$recipient->name}!");
    }

    private function findRecipient(string $email): User
    {
        $recipient = User::where('email', $email)->first();

        if (! $recipient || ! $recipient->farm) {
            throw ValidationException::withMessages(['recipient_email' => 'No farmer found with that email.']);
        }

        return $recipient;
    }
}
