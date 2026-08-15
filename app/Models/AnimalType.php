<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnimalType extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'required_level',
        'name',
        'icon',
        'buy_price',
        'feed_cost',
        'sell_price',
        'produce_key',
        'produce_name',
        'produce_icon',
        'produce_sell_price',
        'produce_interval_days',
    ];

    protected $casts = [
        'buy_price' => 'decimal:2',
        'feed_cost' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'produce_sell_price' => 'decimal:2',
    ];

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }
}
