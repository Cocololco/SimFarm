<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    use HasFactory;

    /** XP needed to advance one level. Level = floor(xp / this) + 1. */
    public const XP_PER_LEVEL = 100;

    /** Inventory slots available before any storage-boosting machinery. */
    public const BASE_STORAGE_CAPACITY = 50;

    /** Animals a farm can own before any barn-expanding machinery. */
    public const BASE_ANIMAL_CAPACITY = 6;

    protected $fillable = [
        'user_id',
        'name',
        'cash',
        'current_day',
        'field_slots',
        'xp',
        'market_multiplier',
        'fertilizer_count',
    ];

    protected $casts = [
        'cash' => 'decimal:2',
        'xp' => 'integer',
        'market_multiplier' => 'decimal:2',
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

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->latest('id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'farm_achievements')
            ->withPivot('unlocked_on_day')
            ->withTimestamps();
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
     * e.g. two +10% yield_boost machines stack to 0.20. Also used for flat
     * bonuses like storage_boost, where effect_value is a raw slot count.
     *
     * Deliberately queries fresh every time (relation *method*, not the
     * cached property) rather than trusting `$this->machinery` — this is
     * called mid-transaction right after machinery/inventory changes, and a
     * stale cached collection would silently under/overcount.
     */
    public function machineryEffectValue(string $effectType): float
    {
        return (float) $this->machinery()
            ->with('machineryType')
            ->get()
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

    /**
     * Farm level, derived from XP (never stored directly, so it can never
     * drift out of sync): every XP_PER_LEVEL xp is one more level, starting
     * at level 1.
     */
    public function getLevelAttribute(): int
    {
        return intdiv((int) $this->xp, self::XP_PER_LEVEL) + 1;
    }

    public function xpIntoLevel(): int
    {
        return ((int) $this->xp) % self::XP_PER_LEVEL;
    }

    public function addXp(int $amount): void
    {
        $this->increment('xp', $amount);
    }

    /**
     * Total inventory slots available: a base allowance plus any flat
     * storage_boost bonuses from owned machinery (e.g. a storage barn).
     */
    public function storageCapacity(): int
    {
        return self::BASE_STORAGE_CAPACITY + (int) $this->machineryEffectValue('storage_boost');
    }

    public function inventoryUsed(): int
    {
        return (int) $this->inventoryItems()->sum('quantity');
    }

    /**
     * Animals allowed: a base allowance plus any flat animal_capacity_boost
     * bonuses from owned machinery (e.g. a barn expansion).
     */
    public function animalCapacity(): int
    {
        return self::BASE_ANIMAL_CAPACITY + (int) $this->machineryEffectValue('animal_capacity_boost');
    }

    public function animalsOwned(): int
    {
        return (int) $this->animals()->count();
    }

    public function activeLoan(): ?Loan
    {
        return $this->loans()->where('balance', '>', 0)->first();
    }

    /**
     * Rough net worth: cash plus the resale value of everything owned,
     * minus outstanding debt. Used for the leaderboard and achievements.
     */
    public function netWorth(): float
    {
        $animalsValue = $this->animals()->with('animalType')->get()
            ->sum(fn (Animal $a) => (float) $a->animalType->sell_price);

        $machineryValue = $this->machinery()->with('machineryType')->get()
            ->sum(fn (Machinery $m) => (float) $m->machineryType->price);

        $inventoryValue = $this->inventoryItems()->get()
            ->sum(fn (InventoryItem $i) => $i->product()['sell_price'] * $i->quantity);

        $debt = $this->loans()->where('balance', '>', 0)->sum('balance');

        return (float) $this->cash + $animalsValue + $machineryValue + $inventoryValue - (float) $debt;
    }
}
