<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertController extends Controller
{
    /** Transaction types worth surfacing as an "alert" rather than routine bookkeeping. */
    private const ALERT_TYPES = [
        'animal_lost',
        'storage_full',
        'quest_reward',
        'gift_received',
        'gift_item_received',
        'event',
        'loan_interest',
    ];

    public function index(Request $request): View
    {
        $farm = $request->user()->farm;

        $alerts = $farm->transactions()->whereIn('type', self::ALERT_TYPES)->paginate(25);

        return view('alerts', ['farm' => $farm, 'alerts' => $alerts]);
    }
}
