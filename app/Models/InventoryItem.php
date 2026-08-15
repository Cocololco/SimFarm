<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'item_key',
        'quantity',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * Display info (name, icon, unit sell price) for this item, resolved
     * from whichever catalog defines it: a harvested crop, or an animal
     * product (egg, milk, wool, ...).
     *
     * @return array{name: string, icon: string, sell_price: float}
     */
    public function product(): array
    {
        $crop = CropType::where('key', $this->item_key)->first();

        if ($crop) {
            return [
                'name' => $crop->name,
                'icon' => $crop->icon,
                'sell_price' => (float) $crop->sell_price,
            ];
        }

        $animalType = AnimalType::where('produce_key', $this->item_key)->first();

        if ($animalType) {
            return [
                'name' => $animalType->produce_name,
                'icon' => $animalType->produce_icon,
                'sell_price' => (float) $animalType->produce_sell_price,
            ];
        }

        return ['name' => $this->item_key, 'icon' => '📦', 'sell_price' => 0.0];
    }
}
