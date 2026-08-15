<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'cash',
        'current_day',
        'field_slots',
    ];

    protected $casts = [
        'cash' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(Field::class)->orderBy('plot_number');
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }

    public function machinery(): HasMany
    {
        return $this->hasMany(Machinery::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    /**
     * Whether this farm owns a piece of machinery with the given effect type.
     */
    public function hasEffect(string $effectType): bool
    {
        return $this->machineryEffectValue($effectType) > 0;
    }

    /**
     * Sum of effect_value across all owned machinery of the given effect type.
     * e.g. two +10% yield_boost machines stack to 0.20.
     */
    public function machineryEffectValue(string $effectType): float
    {
        return (float) $this->machinery
            ->loadMissing('machineryType')
            ->filter(fn (Machinery $m) => $m->machineryType->effect_type === $effectType)
            ->sum(fn (Machinery $m) => (float) $m->machineryType->effect_value);
    }

    public function canAfford(float $amount): bool
    {
        return (float) $this->cash >= $amount;
    }

    public function spendCash(float $amount): void
    {
        $this->decrement('cash', $amount);
    }

    public function addCash(float $amount): void
    {
        $this->increment('cash', $amount);
    }
}
