<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Services\FarmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function store(Request $request, FarmService $farmService): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $farm = $request->user()->farm;

        $farmService->takeLoan($farm, (float) $data['amount']);

        return back()->with('status', 'Loan approved — cash added to your farm.');
    }

    public function repay(Request $request, Loan $loan, FarmService $farmService): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $farm = $request->user()->farm;

        $farmService->repayLoan($farm, $loan, (float) $data['amount']);

        return back()->with('status', 'Loan repayment made.');
    }
}
