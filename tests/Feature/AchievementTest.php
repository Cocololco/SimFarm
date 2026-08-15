<?php

namespace Tests\Feature;

use App\Models\AnimalType;
use App\Models\CropType;
use App\Models\MachineryType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_first_harvest_achievement_unlocks_after_harvesting(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $wheat = CropType::where('key', 'wheat')->firstOrFail();
        $field = $user->farm->fields->first();
        $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $wheat->id]);
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post('/turn/end');
        $this->actingAs($user)->post("/fields/{$field->id}/harvest");

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $this->assertTrue($user->farm->fresh()->achievements->contains('key', 'first_harvest'));
    }

    public function test_first_animal_achievement_unlocks_after_buying_an_animal(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);

        $this->actingAs($user)->get('/dashboard');

        $this->assertTrue($user->farm->fresh()->achievements->contains('key', 'first_animal'));
    }

    public function test_achievement_is_not_unlocked_twice(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);

        $this->actingAs($user)->get('/dashboard');
        $this->actingAs($user)->get('/dashboard');

        $count = $user->farm->fresh()->achievements()->where('key', 'first_animal')->count();
        $this->assertSame(1, $count);
    }

    public function test_gift_giver_achievement_unlocks_after_sending_a_gift(): void
    {
        $this->seedCatalogs();
        $sender = $this->createUserWithFarm();
        $recipient = $this->createUserWithFarm();

        $this->actingAs($sender)->post('/gifts', [
            'recipient_email' => $recipient->email,
            'amount' => 10,
        ]);
        $this->actingAs($sender)->get('/dashboard');

        $this->assertTrue($sender->farm->fresh()->achievements->contains('key', 'gift_giver'));
        // The recipient didn't send anything, so they shouldn't have it.
        $this->actingAs($recipient)->get('/dashboard');
        $this->assertFalse($recipient->farm->fresh()->achievements->contains('key', 'gift_giver'));
    }

    public function test_big_spender_achievement_unlocks_after_spending_a_thousand(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 2000]);
        // tractor 300 + irrigation 400 + feed_silo 350 = 1050 total spend.
        foreach (['tractor', 'irrigation', 'feed_silo'] as $key) {
            $machineryType = MachineryType::where('key', $key)->firstOrFail();
            $this->actingAs($user)->post('/machinery/buy', ['machinery_type_id' => $machineryType->id]);
        }

        $this->actingAs($user)->get('/dashboard');

        $this->assertTrue($user->farm->fresh()->achievements->contains('key', 'big_spender'));
    }

    public function test_loan_free_achievement_unlocks_after_fully_repaying_a_loan(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $this->actingAs($user)->post('/loans', ['amount' => 100]);
        $loan = $user->farm->fresh()->activeLoan();
        $this->actingAs($user)->post("/loans/{$loan->id}/repay", ['amount' => 100]);

        $this->actingAs($user)->get('/dashboard');

        $this->assertTrue($user->farm->fresh()->achievements->contains('key', 'loan_free'));
    }

    public function test_loan_free_achievement_does_not_unlock_while_loan_is_outstanding(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $this->actingAs($user)->post('/loans', ['amount' => 100]);

        $this->actingAs($user)->get('/dashboard');

        $this->assertFalse($user->farm->fresh()->achievements->contains('key', 'loan_free'));
    }

    public function test_green_thumb_achievement_unlocks_after_five_rotated_harvests(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $wheat = CropType::where('key', 'wheat')->firstOrFail(); // 2 days
        $carrot = CropType::where('key', 'carrot')->firstOrFail(); // 3 days
        $field = $user->farm->fields->first();
        $crops = [$wheat, $carrot, $wheat, $carrot, $wheat, $carrot];

        foreach ($crops as $crop) {
            $this->actingAs($user)->post("/fields/{$field->id}/plant", ['crop_type_id' => $crop->id]);
            for ($day = 0; $day < $crop->growth_days; $day++) {
                $this->actingAs($user)->post('/turn/end');
            }
            $this->actingAs($user)->post("/fields/{$field->id}/harvest");
        }

        $this->actingAs($user)->get('/dashboard');

        // First harvest never rotates (no previous crop); the other 5 do.
        $this->assertTrue($user->farm->fresh()->achievements->contains('key', 'green_thumb'));
    }
}
