<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;

/**
 * Every new player gets a starter farm: some cash and a handful of empty
 * fields to plant, so they land straight on a playable dashboard.
 */
class CreateFarmForNewUser
{
    public const STARTING_FIELDS = 4;

    public function handle(Registered $event): void
    {
        $farm = $event->user->farm()->create([
            'name' => $event->user->name."'s Farm",
        ]);

        for ($plot = 1; $plot <= self::STARTING_FIELDS; $plot++) {
            $farm->fields()->create(['plot_number' => $plot]);
        }
    }
}
