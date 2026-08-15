<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'principal',
        'balance',
        'daily_interest_rate',
        'taken_on_day',
    ];

    protected $casts = [
        'principal' => 'decimal:2',
        'balance' => 'decimal:2',
        'daily_interest_rate' => 'decimal:4',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function isActive(): bool
    {
        return (float) $this->balance > 0;
    }
}
