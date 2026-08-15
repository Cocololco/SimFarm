<?php

namespace Tests\Feature;

use App\Models\AnimalType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithFarm;
use Tests\TestCase;

class RenameTest extends TestCase
{
    use RefreshDatabase, InteractsWithFarm;

    public function test_user_can_rename_a_field(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $field = $user->farm->fields->first();

        $response = $this->actingAs($user)->post("/fields/{$field->id}/rename", ['nickname' => 'North Patch']);

        $response->assertRedirect();
        $this->assertSame('North Patch', $field->fresh()->nickname);
    }

    public function test_user_can_clear_a_fields_nickname(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $field = $user->farm->fields->first();
        $field->update(['nickname' => 'North Patch']);

        $this->actingAs($user)->post("/fields/{$field->id}/rename", ['nickname' => '']);

        $this->assertNull($field->fresh()->nickname);
    }

    public function test_user_cannot_rename_another_users_field(): void
    {
        $this->seedCatalogs();
        $owner = $this->createUserWithFarm();
        $intruder = $this->createUserWithFarm();
        $field = $owner->farm->fields->first();

        $response = $this->actingAs($intruder)->post("/fields/{$field->id}/rename", ['nickname' => 'Hijacked']);

        $response->assertSessionHasErrors('field');
        $this->assertNull($field->fresh()->nickname);
    }

    public function test_user_can_rename_an_animal(): void
    {
        $this->seedCatalogs();
        $user = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($user)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $animal = $user->farm->fresh()->animals->first();

        $response = $this->actingAs($user)->post("/animals/{$animal->id}/rename", ['nickname' => 'Henrietta']);

        $response->assertRedirect();
        $this->assertSame('Henrietta', $animal->fresh()->nickname);
    }

    public function test_user_cannot_rename_another_users_animal(): void
    {
        $this->seedCatalogs();
        $owner = $this->createUserWithFarm();
        $intruder = $this->createUserWithFarm();
        $chicken = AnimalType::where('key', 'chicken')->firstOrFail();
        $this->actingAs($owner)->post('/animals/buy', ['animal_type_id' => $chicken->id]);
        $animal = $owner->farm->fresh()->animals->first();

        $response = $this->actingAs($intruder)->post("/animals/{$animal->id}/rename", ['nickname' => 'Hijacked']);

        $response->assertSessionHasErrors('animal');
        $this->assertNull($animal->fresh()->nickname);
    }
}
