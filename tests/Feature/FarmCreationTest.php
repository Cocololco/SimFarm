<?php

namespace Tests\Feature;

use App\Listeners\CreateFarmForNewUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_creates_a_farm_with_starting_fields(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jordan Farmer',
            'email' => 'jordan@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/dashboard');

        $user = User::whereEmail('jordan@example.com')->firstOrFail();

        $this->assertNotNull($user->farm);
        $this->assertSame("Jordan Farmer's Farm", $user->farm->name);
        $this->assertEquals(500, $user->farm->cash);
        $this->assertSame(1, $user->farm->current_day);
        $this->assertCount(CreateFarmForNewUser::STARTING_FIELDS, $user->farm->fields);
        $this->assertTrue($user->farm->fields->every(fn ($field) => $field->isEmpty()));
    }
}
