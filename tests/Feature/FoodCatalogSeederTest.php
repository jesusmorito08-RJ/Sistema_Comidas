<?php

namespace Tests\Feature;

use App\Models\Food;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_preserves_the_chupe_de_cangrejos_catalog_item(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('foods', [
            'name' => 'Chupe de Cangrejos',
            'category' => 'Sopas criollas',
            'description' => 'Rico y Bien preparado el Chupe de Cangrejo',
            'price' => '15.00',
            'image_path' => 'foods/W6rqfs0fJIwH7oSls1aggKIiva3FBKY812IMZ4sP.jpg',
            'is_available' => true,
            'is_featured' => true,
            'deleted_at' => null,
        ]);

        $this->assertSame(
            1,
            Food::withTrashed()->where('name', 'Chupe de Cangrejos')->count(),
        );
    }
}
