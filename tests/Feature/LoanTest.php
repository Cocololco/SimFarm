<?php

namespace Tests\Feature;

use App\Services\FarmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class LoanTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_user_can_take_a_loan(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $cashBefore = (float) $user->farm->cash;

        $response = $this->actingAs($user)->post('/loans', ['amount' => 200]);

        $response->assertRedirect();
        $farm = $user->farm->fresh();
        $this->assertEquals($cashBefore + 200, (float) $farm->cash);
        $this->assertNotNull($farm->activeLoan());
        $this->assertEquals(200, (float) $farm->activeLoan()->balance);
    }

    public function test_user_cannot_exceed_the_max_loan_amount(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();

        $response = $this->actingAs($user)->post('/loans', ['amount' => FarmService::MAX_LOAN_AMOUNT + 1]);

        $response->assertSessionHasErrors('amount');
        $this->assertNull($user->farm->fresh()->activeLoan());
    }

    public function test_user_cannot_take_a_second_loan_while_one_is_active(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $this->actingAs($user)->post('/loans', ['amount' => 100]);

        $response = $this->actingAs($user)->post('/loans', ['amount' => 50]);

        $response->assertSessionHasErrors('loan');
        $this->assertCount(1, $user->farm->fresh()->loans);
    }

    public function test_user_can_repay_part_of_a_loan(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $this->actingAs($user)->post('/loans', ['amount' => 200]);
        $loan = $user->farm->fresh()->activeLoan();
        $cashBefore = (float) $user->farm->fresh()->cash;

        $response = $this->actingAs($user)->post("/loans/{$loan->id}/repay", ['amount' => 50]);

        $response->assertRedirect();
        $this->assertEquals(150, (float) $loan->fresh()->balance);
        $this->assertEquals($cashBefore - 50, (float) $user->farm->fresh()->cash);
    }

    public function test_loan_balance_accrues_interest_at_the_end_of_the_day(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $this->actingAs($user)->post('/loans', ['amount' => 200]);
        $loan = $user->farm->fresh()->activeLoan();

        $this->actingAs($user)->post('/turn/end');

        $expected = round(200 * FarmService::LOAN_DAILY_INTEREST_RATE, 2);
        $this->assertEquals(200 + $expected, (float) $loan->fresh()->balance);
    }

    public function test_fully_repaid_loan_frees_up_a_new_loan(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $this->actingAs($user)->post('/loans', ['amount' => 100]);
        $loan = $user->farm->fresh()->activeLoan();
        $this->actingAs($user)->post("/loans/{$loan->id}/repay", ['amount' => 100]);

        $this->assertNull($user->farm->fresh()->activeLoan());

        $response = $this->actingAs($user)->post('/loans', ['amount' => 50]);
        $response->assertRedirect();
        $this->assertCount(2, $user->farm->fresh()->loans);
    }
}
