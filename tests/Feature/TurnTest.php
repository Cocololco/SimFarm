<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class TurnTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_ending_the_day_advances_only_the_current_users_farm(): void
    {
        $this->seedCatalogs();
        $alice = $this->createUserWithFarm();
        $bob = $this->createUserWithFarm();

        $this->actingAs($alice)->post('/turn/end');
        $this->actingAs($alice)->post('/turn/end');

        $this->assertSame(3, $alice->farm->fresh()->current_day);
        $this->assertSame(1, $bob->farm->fresh()->current_day);
    }

    public function test_guests_cannot_end_the_day(): void
    {
        $response = $this->post('/turn/end');

        $response->assertRedirect('/login');
    }
}
