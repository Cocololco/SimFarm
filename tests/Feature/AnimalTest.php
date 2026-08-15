<?php

namespace Tests\Feature;

use App\Models\AnimalType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class AnimalTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_user_can_buy_an_animal(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();

        $response = $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);

        $response->assertRedirect();
        $farm = $user->farm->fresh();
        $this->assertCount(1, $farm->animals);
        $this->assertEquals(500 - $chicken->buy_price, $farm->cash);
    }

    public function test_user_can_feed_an_animal_once_per_day(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $animal = $user->farm->fresh()->animals->first();

        $response = $this->actingAs($user)->post("/animals/{$animal->id}/feed");
        $response->assertRedirect();
        $this->assertSame(1, $animal->fresh()->fed_on_day);

        $secondFeedAttempt = $this->actingAs($user)->post("/animals/{$animal->id}/feed");
        $secondFeedAttempt->assertSessionHasErrors('animal');
    }

    public function test_fed_animal_produces_goods_when_the_day_ends(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail(); // produce_interval_days = 1
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $animal = $user->farm->fresh()->animals->first();
        $this->actingAs($user)->post("/animals/{$animal->id}/feed");

        $this->actingAs($user)->post('/turn/end');

        $farm = $user->farm->fresh();
        $egg = $farm->inventoryItems()->where('item_key', 'egg')->first();
        $this->assertNotNull($egg);
        $this->assertSame(1, $egg->quantity);
        $this->assertSame(2, $farm->current_day);
    }

    public function test_unfed_animal_does_not_produce_goods(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);

        $this->actingAs($user)->post('/turn/end');

        $farm = $user->farm->fresh();
        $this->assertNull($farm->inventoryItems()->where('item_key', 'egg')->first());
    }

    public function test_user_can_sell_an_animal(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $animal = $user->farm->fresh()->animals->first();
        $cashAfterBuying = $user->farm->fresh()->cash;

        $response = $this->actingAs($user)->delete("/animals/{$animal->id}");

        $response->assertRedirect();
        $farm = $user->farm->fresh();
        $this->assertCount(0, $farm->animals);
        $this->assertEquals((float) $cashAfterBuying + (float) $chicken->sell_price, (float) $farm->cash);
    }
}
