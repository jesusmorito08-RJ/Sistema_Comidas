<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'Entradas peruanas' => 'Entradas criollas',
            'Platos de fondo' => 'Platos criollos',
            'Anticuchos' => 'Platos criollos',
            'Sopas' => 'Sopas criollas',
            'Bebidas' => 'Bebidas peruanas',
        ] as $oldCategory => $newCategory) {
            DB::table('foods')
                ->where('category', $oldCategory)
                ->update(['category' => $newCategory]);
        }

        $newFoods = [
            [
                'name' => 'Seco de res con frejoles',
                'category' => 'Platos criollos',
                'description' => 'Carne guisada lentamente con culantro, frejoles y arroz.',
                'price' => 23.00,
                'offer_price' => null,
                'image_path' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=85',
                'is_available' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Pollo a la brasa',
                'category' => 'Platos a la carta',
                'description' => 'Pollo dorado al horno con papas, ensalada fresca y cremas de la casa.',
                'price' => 25.00,
                'offer_price' => null,
                'image_path' => 'https://images.unsplash.com/photo-1532550907401-a500c9a57435?auto=format&fit=crop&w=900&q=85',
                'is_available' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Tallarines verdes con bistec',
                'category' => 'Platos a la carta',
                'description' => 'Tallarines en salsa de albahaca y espinaca con bistec a la plancha.',
                'price' => 22.00,
                'offer_price' => null,
                'image_path' => 'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?auto=format&fit=crop&w=900&q=85',
                'is_available' => true,
                'is_featured' => false,
            ],
        ];

        foreach ($newFoods as $food) {
            if (! DB::table('foods')->where('name', $food['name'])->exists()) {
                DB::table('foods')->insert($food + [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('foods')
            ->where('name', 'Anticuchos de corazón')
            ->where('category', 'Platos criollos')
            ->update(['category' => 'Anticuchos']);

        foreach ([
            'Entradas criollas' => 'Entradas peruanas',
            'Platos criollos' => 'Platos de fondo',
            'Sopas criollas' => 'Sopas',
            'Bebidas peruanas' => 'Bebidas',
        ] as $newCategory => $oldCategory) {
            DB::table('foods')
                ->where('category', $newCategory)
                ->update(['category' => $oldCategory]);
        }

        // Keep added dishes so rollback cannot remove foods referenced by order history.
    }
};
