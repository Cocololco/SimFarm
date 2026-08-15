<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Machinery extends Model
{
    use HasFactory;

    // Eloquent would otherwise guess "machineries"; the migration uses the
    // simpler singular/uncountable "machinery".
    protected $table = 'machinery';

    protected $fillable = [
        'farm_id',
        'machinery_type_id',
        'purchased_on_day',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function machineryType(): BelongsTo
    {
        return $this->belongsTo(MachineryType::class);
    }
}
