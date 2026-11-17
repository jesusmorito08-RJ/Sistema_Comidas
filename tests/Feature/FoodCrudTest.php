<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_edit_archive_and_restore_a_food(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $this->get(route('admin.foods.create'))->assertOk();
        $this->get(route('admin.foods.index'))->assertOk();

        $this->post(route('admin.foods.store'), [
            'name' => 'Ceviche de prueba',
            'category' => 'Entradas criollas',
            'description' => 'Pescado fresco',
            'price' => 28,
            'offer_price' => 25,
            'is_available' => 1,
        ])->assertRedirect(route('admin.foods.index'));

        $food = Food::where('name', 'Ceviche de prueba')->firstOrFail();
        $this->get(route('admin.foods.edit', $food))->assertOk();

        $this->put(route('admin.foods.update', $food), [
            'name' => 'Ceviche especial',
            'category' => 'Platos criollos',
            'description' => 'Pescado fresco con acompañamiento',
            'price' => 32,
            'offer_price' => 29,
            'is_available' => 1,
        ])->assertRedirect(route('admin.foods.index'));

        $this->assertSame('32.00', $food->fresh()->price);
        $this->assertDatabaseHas('foods', ['id' => $food->id, 'name' => 'Ceviche especial']);

        $this->delete(route('admin.foods.destroy', $food))->assertSessionHasNoErrors();
        $this->assertSoftDeleted('foods', ['id' => $food->id]);

        $this->post(route('admin.foods.restore', $food->id))->assertSessionHasNoErrors();
        $this->assertNotSoftDeleted('foods', ['id' => $food->id]);
    }

    public function test_admin_cannot_restore_an_archived_food_when_its_name_is_in_use(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $archivedFood = Food::create([
            'name' => 'Nombre repetido',
            'category' => 'Entradas',
            'description' => 'Archivado',
            'price' => 10,
            'is_available' => true,
        ]);
        $archivedFood->delete();
        Food::create([
            'name' => 'Nombre repetido',
            'category' => 'Entradas',
            'description' => 'Activo',
            'price' => 12,
            'is_available' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.foods.restore', $archivedFood->id))
            ->assertSessionHasErrors('food');

        $this->assertSoftDeleted('foods', ['id' => $archivedFood->id]);
    }

    public function test_admin_cannot_save_a_food_in_an_unsupported_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.foods.store'), [
                'name' => 'Plato de categoría libre',
                'category' => 'Categoría inventada',
                'description' => 'Descripción de prueba',
                'price' => 20,
            ])
            ->assertSessionHasErrors('category');

        $this->assertDatabaseMissing('foods', ['name' => 'Plato de categoría libre']);
    }
}
