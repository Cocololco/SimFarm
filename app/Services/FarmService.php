<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalType;
use App\Models\CropType;
use App\Models\Farm;
use App\Models\Field;
use App\Models\InventoryItem;
use App\Models\Loan;
use App\Models\Machinery;
use App\Models\MachineryType;
use Illuminate\Validation\ValidationException;

/**
 * All farm gameplay actions live here, so controllers stay thin and the
 * rules (costs, growth, production, XP, risk) are testable in isolation.
 */
class FarmService
{
    /** Days an animal can go unfed before it's lost. */
    public const ANIMAL_NEGLECT_DAYS = 3;

    /** Max principal a farm can borrow, and can only hold one loan at a time. */
    public const MAX_LOAN_AMOUNT = 1000.0;

    public const LOAN_DAILY_INTEREST_RATE = 0.05;

    /**
     * Random daily events rolled at the end of each day: [type, weight,
     * description, cash delta (positive or negative)].
     */
    private const RANDOM_EVENTS = [
        ['type' => 'bumper_harvest', 'weight' => 10, 'description' => 'A neighbor paid extra for your goods.', 'amount' => 25],
        ['type' => 'lucky_find', 'weight' => 6, 'description' => 'You found an old chest buried in a field!', 'amount' => 40],
        ['type' => 'storm_damage', 'weight' => 10, 'description' => 'A storm damaged a fence; minor repairs needed.', 'amount' => -15],
        ['type' => 'pest_trouble', 'weight' => 8, 'description' => 'Pests got into storage; some supplies were lost.', 'amount' => -20],
    ];

    /** Out of 100 weight points, the rest of the time nothing happens. */
    private const RANDOM_EVENT_TOTAL_WEIGHT = 100;

    /**
     * One daily quest is deterministically assigned per farm per day (see
     * todaysQuest()) and checked/rewarded automatically at end of day.
     */
    private const DAILY_QUESTS = [
        ['key' => 'harvest_3', 'type' => 'harvest', 'goal' => 3, 'description' => 'Harvest crops 3 times', 'reward_cash' => 30, 'reward_xp' => 15],
        ['key' => 'earn_50', 'type' => 'sell_amount', 'goal' => 50, 'description' => 'Earn $50 selling goods', 'reward_cash' => 25, 'reward_xp' => 10],
        ['key' => 'feed_2', 'type' => 'feed', 'goal' => 2, 'description' => 'Feed 2 animals', 'reward_cash' => 20, 'reward_xp' => 10],
        ['key' => 'plant_2', 'type' => 'plant', 'goal' => 2, 'description' => 'Plant 2 seeds', 'reward_cash' => 15, 'reward_xp' => 10],
    ];

    public function plant(Farm $farm, Field $field, CropType $cropType): void
    {
        if ($field->farm_id !== $farm->id) {
            throw ValidationException::withMessages(['field' => 'That field does not belong to your farm.']);
        }

        if (! $field->isEmpty()) {
            throw ValidationException::withMessages(['field' => 'That field is already planted.']);
        }

        $this->assertLevel($farm, $cropType->required_level, 'crop_type', $cropType->name);

        if (! $farm->canAfford((float) $cropType->seed_price)) {
            throw ValidationException::withMessages(['cash' => 'Not enough cash to buy seeds.']);
        }

        $farm->spendCash((float) $cropType->seed_price);
        $this->logTransaction($farm, 'plant', "Planted {$cropType->name}.", -(float) $cropType->seed_price);

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
        $rotated = $field->isRotated();
        $amount = $field->harvestYield();

        $added = $this->addToInventory($farm, $cropType->key, $amount);

        $rotationNote = $rotated ? ' (+crop rotation bonus)' : '';
        $this->logTransaction($farm, 'harvest', "Harvested {$added}x {$cropType->name}.{$rotationNote}", null);

        if ($added < $amount) {
            $wasted = $amount - $added;
            $this->logTransaction($farm, 'storage_full', "Storage was full — {$wasted}x {$cropType->name} spoiled.", null);
        }

        $farm->addXp($added * 2);

        $field->update([
            'crop_type_id' => null,
            'previous_crop_type_id' => $cropType->id,
            'planted_on_day' => null,
        ]);
    }

    /**
     * Harvests every ready field. Returns how many were harvested.
     */
    public function harvestAllReady(Farm $farm): int
    {
        $count = 0;

        foreach ($farm->fields()->with('cropType')->get() as $field) {
            if ($field->isReady()) {
                $this->harvest($farm, $field);
                $count++;
            }
        }

        return $count;
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
        $this->logTransaction($farm, 'buy_field', 'Bought a new field.', -$cost);

        return $farm->fields()->create([
            'plot_number' => $farm->field_slots,
        ]);
    }

    public function buyAnimal(Farm $farm, AnimalType $animalType): Animal
    {
        $this->assertLevel($farm, $animalType->required_level, 'animal_type', $animalType->name);

        if ($farm->animalsOwned() >= $farm->animalCapacity()) {
            throw ValidationException::withMessages(['animal_type' => 'Your barn is full — expand it or sell an animal first.']);
        }

        if (! $farm->canAfford((float) $animalType->buy_price)) {
            throw ValidationException::withMessages(['cash' => 'Not enough cash to buy that animal.']);
        }

        $farm->spendCash((float) $animalType->buy_price);
        $this->logTransaction($farm, 'buy_animal', "Bought a {$animalType->name}.", -(float) $animalType->buy_price);
        $farm->addXp(5);

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
        $this->logTransaction($farm, 'feed', "Fed {$animal->animalType->name}.", -$cost);
        $farm->addXp(1);

        $animal->update(['fed_on_day' => $farm->current_day]);
    }

    /**
     * Feeds every unfed animal the farm can afford to, cheapest first.
     * Returns how many were fed.
     */
    public function feedAllHungry(Farm $farm): int
    {
        $count = 0;

        $animals = $farm->animals()->with('animalType')->get()
            ->reject(fn (Animal $a) => $a->isFedToday())
            ->sortBy(fn (Animal $a) => $this->feedCost($farm, $a->animalType));

        foreach ($animals as $animal) {
            if (! $farm->canAfford($this->feedCost($farm, $animal->animalType))) {
                continue;
            }

            $this->feedAnimal($farm, $animal);
            $count++;
        }

        return $count;
    }

    public function sellAnimal(Farm $farm, Animal $animal): void
    {
        if ($animal->farm_id !== $farm->id) {
            throw ValidationException::withMessages(['animal' => 'That animal does not belong to your farm.']);
        }

        $price = (float) $animal->animalType->sell_price;
        $name = $animal->animalType->name;

        $farm->addCash($price);
        $this->logTransaction($farm, 'sell_animal', "Sold {$name}.", $price);
        $farm->addXp(3);

        $animal->delete();
    }

    public function buyMachinery(Farm $farm, MachineryType $machineryType): Machinery
    {
        $this->assertLevel($farm, $machineryType->required_level, 'machinery_type', $machineryType->name);

        if ($farm->machinery()->where('machinery_type_id', $machineryType->id)->exists()) {
            throw ValidationException::withMessages(['machinery' => 'You already own that machine.']);
        }

        if (! $farm->canAfford((float) $machineryType->price)) {
            throw ValidationException::withMessages(['cash' => 'Not enough cash to buy that machine.']);
        }

        $farm->spendCash((float) $machineryType->price);
        $this->logTransaction($farm, 'buy_machinery', "Bought a {$machineryType->name}.", -(float) $machineryType->price);
        $farm->addXp(10);

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

        $product = $item->product();
        $total = $product['sell_price'] * $quantity;

        $farm->addCash($total);
        $this->logTransaction($farm, 'sell', "Sold {$quantity}x {$product['name']}.", $total);
        $farm->addXp($quantity);

        if ($quantity === $item->quantity) {
            $item->delete();
        } else {
            $item->decrement('quantity', $quantity);
        }
    }

    public function takeLoan(Farm $farm, float $amount): Loan
    {
        if ($farm->activeLoan()) {
            throw ValidationException::withMessages(['loan' => 'Repay your existing loan before taking another.']);
        }

        if ($amount < 1 || $amount > self::MAX_LOAN_AMOUNT) {
            throw ValidationException::withMessages(['amount' => 'Loan amount must be between $1 and $'.self::MAX_LOAN_AMOUNT.'.']);
        }

        $loan = $farm->loans()->create([
            'principal' => $amount,
            'balance' => $amount,
            'daily_interest_rate' => self::LOAN_DAILY_INTEREST_RATE,
            'taken_on_day' => $farm->current_day,
        ]);

        $farm->addCash($amount);
        $this->logTransaction($farm, 'loan_taken', 'Took out a loan of $'.number_format($amount, 2).'.', $amount);

        return $loan;
    }

    public function repayLoan(Farm $farm, Loan $loan, float $amount): void
    {
        if ($loan->farm_id !== $farm->id) {
            throw ValidationException::withMessages(['loan' => 'That loan does not belong to your farm.']);
        }

        if ($amount < 0.01 || $amount > (float) $loan->balance) {
            throw ValidationException::withMessages(['amount' => 'Invalid repayment amount.']);
        }

        if (! $farm->canAfford($amount)) {
            throw ValidationException::withMessages(['cash' => 'Not enough cash to repay that much.']);
        }

        $farm->spendCash($amount);
        $loan->decrement('balance', $amount);
        $this->logTransaction($farm, 'loan_repaid', 'Repaid $'.number_format($amount, 2).' of your loan.', -$amount);
    }

    /**
     * Advance the farm by one day: fed animals that are due produce goods,
     * neglected animals may be lost, loan interest accrues, a random event
     * may occur, then the day counter ticks forward.
     */
    public function endDay(Farm $farm): void
    {
        // load(), not loadMissing(): $farm may be a reused instance whose
        // animals/loans were cached before other actions changed them.
        $farm->load('animals.animalType', 'loans');

        foreach ($farm->animals as $animal) {
            if ($animal->isFedToday()) {
                if ($animal->isDueToProduce()) {
                    $added = $this->addToInventory($farm, $animal->animalType->produce_key, 1);
                    if ($added > 0) {
                        $animal->update(['last_produced_day' => $farm->current_day]);
                    }
                }

                continue;
            }

            // +1: today (the day that's ending) counts as an unfed day too,
            // so N consecutive end-of-day calls without feeding = N days of
            // neglect, not N-1.
            $lastCaredForDay = $animal->fed_on_day ?? $animal->purchased_on_day;
            $daysSinceCare = ($farm->current_day + 1) - $lastCaredForDay;

            if ($daysSinceCare >= self::ANIMAL_NEGLECT_DAYS) {
                $name = $animal->animalType->name;
                $animal->delete();
                $this->logTransaction($farm, 'animal_lost', "Your {$name} ran away after being neglected too long.", null);
            }
        }

        $loan = $farm->activeLoan();
        if ($loan) {
            $interest = round((float) $loan->balance * (float) $loan->daily_interest_rate, 2);
            $loan->increment('balance', $interest);
            $this->logTransaction($farm, 'loan_interest', 'Loan interest accrued: $'.number_format($interest, 2).'.', null);
        }

        $this->rollRandomEvent($farm);
        $this->rewardQuestIfComplete($farm);

        $farm->increment('current_day');
    }

    /**
     * The quest assigned to a farm for a given day, deterministic so it
     * doesn't need its own storage — same farm + same day always yields
     * the same quest.
     */
    public function todaysQuest(Farm $farm): array
    {
        $index = ($farm->id + $farm->current_day) % count(self::DAILY_QUESTS);

        return self::DAILY_QUESTS[$index];
    }

    /**
     * @return array{quest: array, progress: float, completed: bool}
     */
    public function questProgress(Farm $farm): array
    {
        $quest = $this->todaysQuest($farm);
        $today = $farm->transactions()->where('day', $farm->current_day);

        $progress = match ($quest['type']) {
            'harvest' => (clone $today)->where('type', 'harvest')->count(),
            'sell_amount' => (float) (clone $today)->where('type', 'sell')->sum('amount'),
            'feed' => (clone $today)->where('type', 'feed')->count(),
            'plant' => (clone $today)->where('type', 'plant')->count(),
            default => 0,
        };

        return [
            'quest' => $quest,
            'progress' => $progress,
            'completed' => $progress >= $quest['goal'],
        ];
    }

    private function rewardQuestIfComplete(Farm $farm): void
    {
        $result = $this->questProgress($farm);

        if (! $result['completed']) {
            return;
        }

        $quest = $result['quest'];
        $farm->addCash($quest['reward_cash']);
        $farm->addXp($quest['reward_xp']);
        $this->logTransaction(
            $farm,
            'quest_reward',
            "Daily goal complete: {$quest['description']}!",
            (float) $quest['reward_cash']
        );
    }

    private function rollRandomEvent(Farm $farm): void
    {
        $roll = mt_rand(1, self::RANDOM_EVENT_TOTAL_WEIGHT);
        $cumulative = 0;

        foreach (self::RANDOM_EVENTS as $event) {
            $cumulative += $event['weight'];

            if ($roll > $cumulative) {
                continue;
            }

            $amount = (float) $event['amount'];

            // Never push a farm into debt from bad luck alone.
            if ($amount < 0) {
                $amount = -min(abs($amount), (float) $farm->cash);
            }

            if ($amount > 0) {
                $farm->addCash($amount);
            } elseif ($amount < 0) {
                $farm->spendCash(abs($amount));
            }

            $this->logTransaction($farm, 'event', $event['description'], $amount !== 0.0 ? $amount : null);

            return;
        }

        // Remaining weight: a quiet day, nothing logged.
    }

    private function assertLevel(Farm $farm, int $requiredLevel, string $field, string $name): void
    {
        if ($farm->level < $requiredLevel) {
            throw ValidationException::withMessages([
                $field => "Reach farm level {$requiredLevel} to unlock {$name}.",
            ]);
        }
    }

    /**
     * Adds up to the farm's remaining storage capacity; excess is wasted
     * and reported via the return value so callers can note it happened.
     */
    private function addToInventory(Farm $farm, string $itemKey, int $amount): int
    {
        $remainingCapacity = max(0, $farm->storageCapacity() - $farm->inventoryUsed());
        $toAdd = min($amount, $remainingCapacity);

        if ($toAdd <= 0) {
            return 0;
        }

        $item = $farm->inventoryItems()->firstOrCreate(
            ['item_key' => $itemKey],
            ['quantity' => 0]
        );

        $item->increment('quantity', $toAdd);

        return $toAdd;
    }

    private function logTransaction(Farm $farm, string $type, string $description, ?float $amount): void
    {
        $farm->transactions()->create([
            'day' => $farm->current_day,
            'type' => $type,
            'description' => $description,
            'amount' => $amount,
        ]);
    }
}
