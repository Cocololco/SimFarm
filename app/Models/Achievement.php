<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'icon',
        'description',
    ];

    public function farms(): BelongsToMany
    {
        return $this->belongsToMany(Farm::class, 'farm_achievements')
            ->withPivot('unlocked_on_day')
            ->withTimestamps();
    }
}
