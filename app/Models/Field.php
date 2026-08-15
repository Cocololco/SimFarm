<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Field extends Model
{
    use HasFactory;

    /** Yield bonus for planting a different crop than grew here last (crop rotation). */
    public const ROTATION_YIELD_BONUS = 0.15;

    /** Yield bonus for a field that's had fertilizer applied this cycle. */
    public const FERTILIZER_YIELD_BONUS = 0.20;

    protected $fillable = [
        'farm_id',
        'plot_number',
        'nickname',
        'crop_type_id',
        'previous_crop_type_id',
        'planted_on_day',
        'fertilized',
    ];

    protected $casts = [
        'fertilized' => 'boolean',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function cropType(): BelongsTo
    {
        return $this->belongsTo(CropType::class);
    }

    public function previousCropType(): BelongsTo
    {
        return $this->belongsTo(CropType::class, 'previous_crop_type_id');
    }

    /**
     * Whether the currently planted crop differs from whatever grew here
     * last time — crop rotation earns a yield bonus.
     */
    public function isRotated(): bool
    {
        return ! is_null($this->previous_crop_type_id)
            && $this->previous_crop_type_id !== $this->crop_type_id;
    }

    public function isEmpty(): bool
    {
        return is_null($this->crop_type_id);
    }

    public function isGrowing(): bool
    {
        return ! $this->isEmpty() && ! $this->isReady();
    }

    /**
     * How many days a planted crop needs to mature, after machinery
     * growth-speed bonuses (e.g. 0.20 = 20% faster) are applied.
     */
    public function effectiveGrowthDays(): int
    {
        if (! $this->cropType) {
            return 0;
        }

        $speedBonus = $this->farm->machineryEffectValue('growth_speed');

        return (int) max(1, floor($this->cropType->growth_days * (1 - $speedBonus)));
    }

    public function isReady(): bool
    {
        if ($this->isEmpty()) {
            return false;
        }

        $daysGrown = $this->farm->current_day - $this->planted_on_day;

        return $daysGrown >= $this->effectiveGrowthDays();
    }

    public function daysRemaining(): int
    {
        if ($this->isEmpty()) {
            return 0;
        }

        $daysGrown = $this->farm->current_day - $this->planted_on_day;

        return max(0, $this->effectiveGrowthDays() - $daysGrown);
    }

    /**
     * Units harvested, after machinery yield-boost and crop-rotation
     * bonuses are applied (they stack additively).
     */
    public function harvestYield(): int
    {
        if (! $this->cropType) {
            return 0;
        }

        $yieldBonus = $this->farm->machineryEffectValue('yield_boost');

        if ($this->isRotated()) {
            $yieldBonus += self::ROTATION_YIELD_BONUS;
        }

        if ($this->fertilized) {
            $yieldBonus += self::FERTILIZER_YIELD_BONUS;
        }

        return (int) max($this->cropType->yield_amount, floor($this->cropType->yield_amount * (1 + $yieldBonus)));
    }
}
