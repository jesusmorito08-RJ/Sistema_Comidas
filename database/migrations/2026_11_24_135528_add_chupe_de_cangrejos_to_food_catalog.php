<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $food = [
            'category' => 'Sopas criollas',
            'description' => 'Rico y Bien preparado el Chupe de Cangrejo',
            'price' => 15.00,
            'offer_price' => null,
            'image_path' => 'foods/W6rqfs0fJIwH7oSls1aggKIiva3FBKY812IMZ4sP.jpg',
            'is_available' => true,
            'is_featured' => true,
            'deleted_at' => null,
            'updated_at' => now(),
        ];

        $foodId = DB::table('foods')
            ->where('name', 'Chupe de Cangrejos')
            ->value('id');

        if ($foodId !== null) {
            DB::table('foods')->where('id', $foodId)->update($food);

            return;
        }

        DB::table('foods')->insert($food + [
            'name' => 'Chupe de Cangrejos',
            'created_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Keep the dish because it may already exist or be referenced by an order.
    }
};
