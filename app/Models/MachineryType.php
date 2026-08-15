<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MachineryType extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'icon',
        'description',
        'price',
        'effect_type',
        'effect_value',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'effect_value' => 'decimal:2',
    ];

    public function machinery(): HasMany
    {
        return $this->hasMany(Machinery::class);
    }
}
