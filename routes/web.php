<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\FarmController;
use App\Http\Controllers\FarmProfileController;
use App\Http\Controllers\FarmSettingsController;
use App\Http\Controllers\FertilizerController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MachineryController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\PesticideController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TurnController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [FarmController::class, 'show'])->name('dashboard');

    Route::post('/fields/buy', [FieldController::class, 'buy'])->name('fields.buy');
    Route::post('/fields/harvest-all', [FieldController::class, 'harvestAll'])->name('fields.harvest-all');
    Route::post('/fields/plant-all', [FieldController::class, 'plantAll'])->name('fields.plant-all');
    Route::post('/fields/{field}/plant', [FieldController::class, 'plant'])->name('fields.plant');
    Route::post('/fields/{field}/harvest', [FieldController::class, 'harvest'])->name('fields.harvest');
    Route::post('/fields/{field}/fertilize', [FieldController::class, 'fertilize'])->name('fields.fertilize');
    Route::post('/fields/{field}/rename', [FieldController::class, 'rename'])->name('fields.rename');

    Route::post('/fertilizer/buy', [FertilizerController::class, 'store'])->name('fertilizer.buy');
    Route::post('/pesticide/buy', [PesticideController::class, 'store'])->name('pesticide.buy');

    Route::post('/animals/buy', [AnimalController::class, 'store'])->name('animals.buy');
    Route::post('/animals/feed-all', [AnimalController::class, 'feedAll'])->name('animals.feed-all');
    Route::post('/animals/{animal}/feed', [AnimalController::class, 'feed'])->name('animals.feed');
    Route::post('/animals/{animal}/rename', [AnimalController::class, 'rename'])->name('animals.rename');
    Route::post('/animals/{animal}/insure', [AnimalController::class, 'insure'])->name('animals.insure');
    Route::delete('/animals/{animal}', [AnimalController::class, 'destroy'])->name('animals.sell');

    Route::post('/inventory/sell-all', [MarketController::class, 'sellAll'])->name('inventory.sell-all');
    Route::post('/inventory/{item}/sell', [MarketController::class, 'sell'])->name('inventory.sell');

    Route::post('/machinery/buy', [MachineryController::class, 'store'])->name('machinery.buy');

    Route::post('/loans', [LoanController::class, 'store'])->name('loans.store');
    Route::post('/loans/{loan}/repay', [LoanController::class, 'repay'])->name('loans.repay');

    Route::post('/turn/end', [TurnController::class, 'end'])->name('turn.end');

    Route::post('/gifts', [GiftController::class, 'store'])->name('gifts.store');
    Route::post('/gifts/items/{item}', [GiftController::class, 'storeItem'])->name('gifts.store-item');

    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');
    Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');
    Route::get('/farms/{farm}', [FarmProfileController::class, 'show'])->name('farms.show');

    Route::get('/help', function () {
        return view('help');
    })->name('help');

    Route::get('/settings/farm', [FarmSettingsController::class, 'edit'])->name('farm-settings.edit');
    Route::patch('/settings/farm', [FarmSettingsController::class, 'update'])->name('farm-settings.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
