<?php

namespace Tests\Feature;

use App\Models\CropType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class MarketTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_user_can_sell_part_of_an_inventory_stack(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $wheat = CropType::where('key', 'wheat')->firstOrFail();
        $item = $user->farm->inventoryItems()->create(['item_key' => 'wheat', 'quantity' => 10]);
        $cashBefore = (float) $user->farm->cash;

        $response = $this->actingAs($user)->post("/inventory/{$item->id}/sell", ['quantity' => 4]);

        $response->assertRedirect();
        $item->refresh();
        $this->assertSame(6, $item->quantity);
        $this->assertEquals($cashBefore + 4 * (float) $wheat->sell_price, (float) $user->farm->fresh()->cash);
    }

    public function test_selling_the_full_stack_removes_the_inventory_row(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $item = $user->farm->inventoryItems()->create(['item_key' => 'wheat', 'quantity' => 3]);

        $this->actingAs($user)->post("/inventory/{$item->id}/sell", ['quantity' => 3]);

        $this->assertDatabaseMissing('inventory_items', ['id' => $item->id]);
    }

    public function test_user_cannot_sell_more_than_they_have(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $item = $user->farm->inventoryItems()->create(['item_key' => 'wheat', 'quantity' => 3]);

        $response = $this->actingAs($user)->post("/inventory/{$item->id}/sell", ['quantity' => 4]);

        $response->assertSessionHasErrors('quantity');
        $this->assertSame(3, $item->fresh()->quantity);
    }

    public function test_user_cannot_sell_another_users_inventory(): void
    {
        $this->seedCatalogs();
        $owner = $this->createUserWithFarm();
        $intruder = $this->createUserWithFarm();
        $item = $owner->farm->inventoryItems()->create(['item_key' => 'wheat', 'quantity' => 3]);

        $response = $this->actingAs($intruder)->post("/inventory/{$item->id}/sell", ['quantity' => 1]);

        $response->assertSessionHasErrors('item');
        $this->assertSame(3, $item->fresh()->quantity);
    }
}
