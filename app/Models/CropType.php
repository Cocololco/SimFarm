<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CropType extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'required_level',
        'season',
        'name',
        'icon',
        'seed_price',
        'sell_price',
        'growth_days',
        'yield_amount',
    ];

    protected $casts = [
        'seed_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(Field::class);
    }
}
