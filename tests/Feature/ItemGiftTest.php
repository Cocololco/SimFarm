<?php

namespace Tests\Feature;

use App\Models\Farm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class ItemGiftTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_user_can_gift_part_of_an_inventory_stack(): void
    {
        $this->seedCatalogs();
        $sender = $this->createUserWithFarm();
        $recipient = $this->createUserWithFarm();
        $item = $sender->farm->inventoryItems()->create(['item_key' => 'wheat', 'quantity' => 10]);

        $response = $this->actingAs($sender)->post("/gifts/items/{$item->id}", [
            'recipient_email' => $recipient->email,
            'quantity' => 4,
        ]);

        $response->assertRedirect();
        $this->assertSame(6, $item->fresh()->quantity);
        $recipientItem = $recipient->farm->fresh()->inventoryItems()->where('item_key', 'wheat')->first();
        $this->assertSame(4, $recipientItem->quantity);
    }

    public function test_gifting_the_full_stack_removes_the_senders_row(): void
    {
        $this->seedCatalogs();
        $sender = $this->createUserWithFarm();
        $recipient = $this->createUserWithFarm();
        $item = $sender->farm->inventoryItems()->create(['item_key' => 'egg', 'quantity' => 3]);

        $this->actingAs($sender)->post("/gifts/items/{$item->id}", [
            'recipient_email' => $recipient->email,
            'quantity' => 3,
        ]);

        $this->assertDatabaseMissing('inventory_items', ['id' => $item->id]);
    }

    public function test_gift_merges_into_an_existing_recipient_stack(): void
    {
        $this->seedCatalogs();
        $sender = $this->createUserWithFarm();
        $recipient = $this->createUserWithFarm();
        $senderItem = $sender->farm->inventoryItems()->create(['item_key' => 'wheat', 'quantity' => 5]);
        $recipient->farm->inventoryItems()->create(['item_key' => 'wheat', 'quantity' => 2]);

        $this->actingAs($sender)->post("/gifts/items/{$senderItem->id}", [
            'recipient_email' => $recipient->email,
            'quantity' => 5,
        ]);

        $recipientItems = $recipient->farm->fresh()->inventoryItems()->where('item_key', 'wheat')->get();
        $this->assertCount(1, $recipientItems);
        $this->assertSame(7, $recipientItems->first()->quantity);
    }

    public function test_cannot_gift_more_than_owned(): void
    {
        $this->seedCatalogs();
        $sender = $this->createUserWithFarm();
        $recipient = $this->createUserWithFarm();
        $item = $sender->farm->inventoryItems()->create(['item_key' => 'wheat', 'quantity' => 3]);

        $response = $this->actingAs($sender)->post("/gifts/items/{$item->id}", [
            'recipient_email' => $recipient->email,
            'quantity' => 4,
        ]);

        $response->assertSessionHasErrors('quantity');
        $this->assertSame(3, $item->fresh()->quantity);
    }

    public function test_cannot_gift_items_to_self(): void
    {
        $this->seedCatalogs();
        $sender = $this->createUserWithFarm();
        $item = $sender->farm->inventoryItems()->create(['item_key' => 'wheat', 'quantity' => 3]);

        $response = $this->actingAs($sender)->post("/gifts/items/{$item->id}", [
            'recipient_email' => $sender->email,
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors('recipient');
        $this->assertSame(3, $item->fresh()->quantity);
    }

    public function test_cannot_gift_another_farms_item(): void
    {
        $this->seedCatalogs();
        $owner = $this->createUserWithFarm();
        $intruder = $this->createUserWithFarm();
        $item = $owner->farm->inventoryItems()->create(['item_key' => 'wheat', 'quantity' => 3]);

        $response = $this->actingAs($intruder)->post("/gifts/items/{$item->id}", [
            'recipient_email' => $owner->email,
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors('item');
        $this->assertSame(3, $item->fresh()->quantity);
    }

    public function test_gift_is_capped_by_recipients_remaining_storage(): void
    {
        $this->seedCatalogs();
        $sender = $this->createUserWithFarm();
        $recipient = $this->createUserWithFarm();
        $item = $sender->farm->inventoryItems()->create(['item_key' => 'wheat', 'quantity' => 10]);
        // Fill recipient's storage to 48/50, leaving room for only 2.
        $recipient->farm->inventoryItems()->create(['item_key' => 'filler', 'quantity' => 48]);

        $this->actingAs($sender)->post("/gifts/items/{$item->id}", [
            'recipient_email' => $recipient->email,
            'quantity' => 10,
        ]);

        // Only 2 delivered; the other 8 stay with the sender.
        $this->assertSame(8, $item->fresh()->quantity);
        $recipientWheat = $recipient->farm->fresh()->inventoryItems()->where('item_key', 'wheat')->first();
        $this->assertSame(2, $recipientWheat->quantity);
        $this->assertSame(Farm::BASE_STORAGE_CAPACITY, $recipient->farm->fresh()->inventoryUsed());
    }
}
