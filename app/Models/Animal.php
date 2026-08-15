<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Animal extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'animal_type_id',
        'nickname',
        'fed_on_day',
        'last_produced_day',
        'purchased_on_day',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function animalType(): BelongsTo
    {
        return $this->belongsTo(AnimalType::class);
    }

    public function isFedToday(): bool
    {
        return $this->fed_on_day === $this->farm->current_day;
    }

    /**
     * Whether this animal is due to produce on the day it is currently fed
     * for (i.e. enough days have passed since it last produced).
     */
    public function isDueToProduce(): bool
    {
        if (is_null($this->last_produced_day)) {
            return true;
        }

        $daysSinceLastProduce = $this->farm->current_day - $this->last_produced_day;

        return $daysSinceLastProduce >= $this->animalType->produce_interval_days;
    }
}
