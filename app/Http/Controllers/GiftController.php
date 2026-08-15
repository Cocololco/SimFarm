<?php

namespace App\Http\Controllers;

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

        $recipientUser = User::where('email', $data['recipient_email'])->first();

        if (! $recipientUser || ! $recipientUser->farm) {
            throw ValidationException::withMessages(['recipient_email' => 'No farmer found with that email.']);
        }

        $farmService->giftCash($request->user()->farm, $recipientUser->farm, (float) $data['amount']);

        return back()->with('status', "Gift sent to {$recipientUser->name}!");
    }
}
