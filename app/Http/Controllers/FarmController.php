<?php

namespace App\Http\Controllers;

use App\Models\AnimalType;
use App\Models\CropType;
use App\Models\MachineryType;
use App\Services\AchievementService;
use App\Services\FarmService;
use App\Services\MilestoneService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FarmController extends Controller
{
    public function show(
        Request $request,
        FarmService $farmService,
        AchievementService $achievementService,
        MilestoneService $milestoneService
    ): View {
        $farm = $request->user()->farm()->with([
            'fields.cropType',
            'animals.animalType',
            'machinery.machineryType',
            'inventoryItems',
            'loans',
            'achievements',
        ])->firstOrFail();

        $newlyUnlocked = $achievementService->checkAndUnlock($farm);
        $newMilestones = $milestoneService->checkAndReward($farm);

        $celebrations = $newlyUnlocked->map(fn ($a) => "{$a->icon} {$a->name}")
            ->concat($newMilestones->map(fn ($m) => '💰 Reached $'.number_format($m['threshold']).' net worth'));

        if ($celebrations->isNotEmpty()) {
            session()->flash('status', 'Achievement unlocked: '.$celebrations->implode(', ').'!');
            session()->flash('celebrate', true);
            $farm->load('achievements');
        }

        return view('dashboard', [
            'farm' => $farm,
            'cropTypes' => CropType::orderBy('required_level')->orderBy('seed_price')->get(),
            'animalTypes' => AnimalType::orderBy('required_level')->orderBy('buy_price')->get(),
            'machineryTypes' => MachineryType::orderBy('required_level')->orderBy('price')->get(),
            'nextFieldCost' => $farmService->fieldCost($farm),
            'recentTransactions' => $farm->transactions()->limit(8)->get(),
            'maxLoanAmount' => FarmService::MAX_LOAN_AMOUNT,
            'questProgress' => $farmService->questProgress($farm),
            'weeklyChallengeProgress' => $farmService->weeklyChallengeProgress($farm),
            'achievementProgress' => $achievementService->progressFor($farm),
            'nextMilestone' => $milestoneService->nextThreshold($farm),
        ]);
    }
}
