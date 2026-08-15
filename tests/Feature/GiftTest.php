<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class GiftTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_user_can_gift_cash_to_another_farmer(): void
    {
        $this->seedCatalogs();
        $sender = $this->createUserWithFarm(['cash' => 500]);
        $recipient = $this->createUserWithFarm(['cash' => 100]);

        $response = $this->actingAs($sender)->post('/gifts', [
            'recipient_email' => $recipient->email,
            'amount' => 50,
        ]);

        $response->assertRedirect();
        $this->assertEquals(450, (float) $sender->farm->fresh()->cash);
        $this->assertEquals(150, (float) $recipient->farm->fresh()->cash);

        $this->assertNotNull($sender->farm->fresh()->transactions()->where('type', 'gift_sent')->first());
        $this->assertNotNull($recipient->farm->fresh()->transactions()->where('type', 'gift_received')->first());
    }

    public function test_user_cannot_gift_more_cash_than_they_have(): void
    {
        $this->seedCatalogs();
        $sender = $this->createUserWithFarm(['cash' => 10]);
        $recipient = $this->createUserWithFarm();

        $response = $this->actingAs($sender)->post('/gifts', [
            'recipient_email' => $recipient->email,
            'amount' => 50,
        ]);

        $response->assertSessionHasErrors('cash');
        $this->assertEquals(10, (float) $sender->farm->fresh()->cash);
    }

    public function test_user_cannot_gift_to_a_nonexistent_email(): void
    {
        $this->seedCatalogs();
        $sender = $this->createUserWithFarm(['cash' => 500]);

        $response = $this->actingAs($sender)->post('/gifts', [
            'recipient_email' => 'nobody@example.com',
            'amount' => 10,
        ]);

        $response->assertSessionHasErrors('recipient_email');
        $this->assertEquals(500, (float) $sender->farm->fresh()->cash);
    }

    public function test_user_cannot_gift_to_themselves(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm(['cash' => 500]);

        $response = $this->actingAs($user)->post('/gifts', [
            'recipient_email' => $user->email,
            'amount' => 10,
        ]);

        $response->assertSessionHasErrors('recipient');
        $this->assertEquals(500, (float) $user->farm->fresh()->cash);
    }
}
