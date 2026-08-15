<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\FarmController;
use App\Http\Controllers\FarmSettingsController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MachineryController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\ProfileController;
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
    Route::post('/fields/{field}/plant', [FieldController::class, 'plant'])->name('fields.plant');
    Route::post('/fields/{field}/harvest', [FieldController::class, 'harvest'])->name('fields.harvest');

    Route::post('/animals/buy', [AnimalController::class, 'store'])->name('animals.buy');
    Route::post('/animals/feed-all', [AnimalController::class, 'feedAll'])->name('animals.feed-all');
    Route::post('/animals/{animal}/feed', [AnimalController::class, 'feed'])->name('animals.feed');
    Route::delete('/animals/{animal}', [AnimalController::class, 'destroy'])->name('animals.sell');

    Route::post('/inventory/{item}/sell', [MarketController::class, 'sell'])->name('inventory.sell');

    Route::post('/machinery/buy', [MachineryController::class, 'store'])->name('machinery.buy');

    Route::post('/loans', [LoanController::class, 'store'])->name('loans.store');
    Route::post('/loans/{loan}/repay', [LoanController::class, 'repay'])->name('loans.repay');

    Route::post('/turn/end', [TurnController::class, 'end'])->name('turn.end');

    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');

    Route::get('/settings/farm', [FarmSettingsController::class, 'edit'])->name('farm-settings.edit');
    Route::patch('/settings/farm', [FarmSettingsController::class, 'update'])->name('farm-settings.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
