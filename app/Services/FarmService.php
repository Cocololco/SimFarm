<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalType;
use App\Models\CropType;
use App\Models\Farm;
use App\Models\Field;
use App\Models\InventoryItem;
use App\Models\Machinery;
use App\Models\MachineryType;
use Illuminate\Validation\ValidationException;

/**
 * All farm gameplay actions live here, so controllers stay thin and the
 * rules (costs, growth, production) are testable in isolation.
 */
class FarmService
{
    public function plant(Farm $farm, Field $field, CropType $cropType): void
    {
        if ($field->farm_id !== $farm->id) {
            throw ValidationException::withMessages(['field' => 'That field does not belong to your farm.']);
        }

        if (! $field->isEmpty()) {
            throw ValidationException::withMessages(['field' => 'That field is already planted.']);
        }

        if (! $farm->canAfford((float) $cropType->seed_price)) {
            throw ValidationException::withMessages(['cash' => 'Not enough cash to buy seeds.']);
        }

        $farm->spendCash((float) $cropType->seed_price);

        $field->update([
            'crop_type_id' => $cropType->id,
            'planted_on_day' => $farm->current_day,
        ]);
    }

    public function harvest(Farm $farm, Field $field): void
    {
        if ($field->farm_id !== $farm->id) {
            throw ValidationException::withMessages(['field' => 'That field does not belong to your farm.']);
        }

        if (! $field->isReady()) {
            throw ValidationException::withMessages(['field' => 'That crop is not ready to harvest yet.']);
        }

        $cropType = $field->cropType;
        $amount = $field->harvestYield();

        $this->addToInventory($farm, $cropType->key, $amount);

        $field->update([
            'crop_type_id' => null,
            'planted_on_day' => null,
        ]);
    }

    public function fieldCost(Farm $farm): float
    {
        return 50 * ($farm->field_slots + 1);
    }

    public function buyField(Farm $farm): Field
    {
        $cost = $this->fieldCost($farm);

        if (! $farm->canAfford($cost)) {
            throw ValidationException::withMessages(['cash' => 'Not enough cash to buy a new field.']);
        }

        $farm->spendCash($cost);
        $farm->increment('field_slots');

        return $farm->fields()->create([
            'plot_number' => $farm->field_slots,
        ]);
    }

    public function buyAnimal(Farm $farm, AnimalType $animalType): Animal
    {
        if (! $farm->canAfford((float) $animalType->buy_price)) {
            throw ValidationException::withMessages(['cash' => 'Not enough cash to buy that animal.']);
        }

        $farm->spendCash((float) $animalType->buy_price);

        return $farm->animals()->create([
            'animal_type_id' => $animalType->id,
            'purchased_on_day' => $farm->current_day,
        ]);
    }

    public function feedCost(Farm $farm, AnimalType $animalType): float
    {
        $discount = $farm->machineryEffectValue('feed_discount');

        return round((float) $animalType->feed_cost * (1 - $discount), 2);
    }

    public function feedAnimal(Farm $farm, Animal $animal): void
    {
        if ($animal->farm_id !== $farm->id) {
            throw ValidationException::withMessages(['animal' => 'That animal does not belong to your farm.']);
        }

        if ($animal->isFedToday()) {
            throw ValidationException::withMessages(['animal' => 'That animal has already been fed today.']);
        }

        $cost = $this->feedCost($farm, $animal->animalType);

        if (! $farm->canAfford($cost)) {
            throw ValidationException::withMessages(['cash' => 'Not enough cash to buy feed.']);
        }

        $farm->spendCash($cost);

        $animal->update(['fed_on_day' => $farm->current_day]);
    }

    public function sellAnimal(Farm $farm, Animal $animal): void
    {
        if ($animal->farm_id !== $farm->id) {
            throw ValidationException::withMessages(['animal' => 'That animal does not belong to your farm.']);
        }

        $farm->addCash((float) $animal->animalType->sell_price);

        $animal->delete();
    }

    public function buyMachinery(Farm $farm, MachineryType $machineryType): Machinery
    {
        if ($farm->machinery()->where('machinery_type_id', $machineryType->id)->exists()) {
            throw ValidationException::withMessages(['machinery' => 'You already own that machine.']);
        }

        if (! $farm->canAfford((float) $machineryType->price)) {
            throw ValidationException::withMessages(['cash' => 'Not enough cash to buy that machine.']);
        }

        $farm->spendCash((float) $machineryType->price);

        return $farm->machinery()->create([
            'machinery_type_id' => $machineryType->id,
            'purchased_on_day' => $farm->current_day,
        ]);
    }

    public function sellInventory(Farm $farm, InventoryItem $item, int $quantity): void
    {
        if ($item->farm_id !== $farm->id) {
            throw ValidationException::withMessages(['item' => 'That item does not belong to your farm.']);
        }

        if ($quantity < 1 || $quantity > $item->quantity) {
            throw ValidationException::withMessages(['quantity' => 'Invalid quantity to sell.']);
        }

        $unitPrice = $item->product()['sell_price'];

        $farm->addCash($unitPrice * $quantity);

        if ($quantity === $item->quantity) {
            $item->delete();
        } else {
            $item->decrement('quantity', $quantity);
        }
    }

    /**
     * Advance the farm by one day: fed animals that are due produce goods,
     * then the day counter ticks forward.
     */
    public function endDay(Farm $farm): void
    {
        $farm->loadMissing('animals.animalType');

        foreach ($farm->animals as $animal) {
            if ($animal->isFedToday() && $animal->isDueToProduce()) {
                $this->addToInventory($farm, $animal->animalType->produce_key, 1);
                $animal->update(['last_produced_day' => $farm->current_day]);
            }
        }

        $farm->increment('current_day');
    }

    private function addToInventory(Farm $farm, string $itemKey, int $amount): void
    {
        $item = $farm->inventoryItems()->firstOrCreate(
            ['item_key' => $itemKey],
            ['quantity' => 0]
        );

        $item->increment('quantity', $amount);
    }
}
