<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class FarmProfileTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_user_can_view_another_farmers_public_profile(): void
    {
        $this->seedCatalogs();
        $viewer = $this->createUserWithFarm();
        $other = $this->createUserWithFarm(['name' => 'Green Acres']);

        $response = $this->actingAs($viewer)->get("/farms/{$other->farm->id}");

        $response->assertOk();
        $response->assertSee('Green Acres');
        $response->assertSee($other->name);
    }

    public function test_help_page_renders(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();

        $response = $this->actingAs($user)->get('/help');

        $response->assertOk();
    }
}
