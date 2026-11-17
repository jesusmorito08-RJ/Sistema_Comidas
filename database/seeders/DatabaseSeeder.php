<?php

namespace Database\Seeders;

use App\Models\Food;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@sazon.test'],
            ['name' => 'Administración Sazón', 'password' => 'admin12345', 'role' => 'admin', 'is_active' => true],
        );
        User::updateOrCreate(
            ['email' => 'cliente@sazon.test'],
            ['name' => 'Sofía Ramírez', 'password' => 'cliente12345', 'role' => 'customer', 'phone' => '555 010 2040', 'address' => 'Av. del Sabor 123', 'is_active' => true],
        );

        $foods = [
            ['name' => 'Bowl de la huerta', 'category' => 'Saludables', 'description' => 'Quinoa, aguacate cremoso, verduras de estación y aderezo cítrico.', 'price' => 12.50, 'offer_price' => 10.90, 'image_path' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => true],
            ['name' => 'Pasta al pomodoro', 'category' => 'Pastas', 'description' => 'Pasta artesanal con tomate asado, albahaca fresca y parmesano.', 'price' => 14.00, 'offer_price' => null, 'image_path' => 'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => true],
            ['name' => 'Hamburguesa de la casa', 'category' => 'Hamburguesas', 'description' => 'Carne jugosa, queso cheddar, cebolla dulce y papas doradas.', 'price' => 15.50, 'offer_price' => null, 'image_path' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => true],
            ['name' => 'Tacos de pollo', 'category' => 'Antojitos', 'description' => 'Tres tortillas suaves, pollo especiado, pico de gallo y lima.', 'price' => 11.50, 'offer_price' => 9.90, 'image_path' => 'https://images.unsplash.com/photo-1551504734-5ee1c4a1479b?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => false],
            ['name' => 'Sopa cremosa de temporada', 'category' => 'Sopas criollas', 'description' => 'Verduras frescas, hierbas aromáticas y pan tostado de la casa.', 'price' => 8.50, 'offer_price' => null, 'image_path' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => false],
            ['name' => 'Tarta de frutos rojos', 'category' => 'Postres', 'description' => 'Base crujiente, crema suave y frutos rojos de temporada.', 'price' => 7.00, 'offer_price' => null, 'image_path' => 'https://images.unsplash.com/photo-1464305795204-6f5bbfc7fb81?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => false],
            ['name' => 'Ceviche clásico', 'category' => 'Entradas criollas', 'description' => 'Pescado fresco marinado en limón, con cebolla morada, cilantro, camote y choclo.', 'price' => 18.00, 'offer_price' => null, 'image_path' => 'https://images.unsplash.com/photo-1535399831218-d5bd36d1a6b3?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => true],
            ['name' => 'Causa limeña de pollo', 'category' => 'Entradas criollas', 'description' => 'Papa amarilla al limón y ají amarillo, rellena de pollo y palta.', 'price' => 12.50, 'offer_price' => null, 'image_path' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => false],
            ['name' => 'Papa a la huancaína', 'category' => 'Entradas criollas', 'description' => 'Papas amarillas con cremosa salsa de ají amarillo, queso fresco y aceituna.', 'price' => 10.00, 'offer_price' => null, 'image_path' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => false],
            ['name' => 'Lomo saltado', 'category' => 'Platos criollos', 'description' => 'Lomo salteado al wok con cebolla, tomate, papas doradas, arroz y sillao.', 'price' => 21.00, 'offer_price' => 19.50, 'image_path' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => true],
            ['name' => 'Ají de gallina', 'category' => 'Platos criollos', 'description' => 'Pollo deshilachado en crema suave de ají amarillo, con papa, huevo y arroz.', 'price' => 18.50, 'offer_price' => null, 'image_path' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => true],
            ['name' => 'Arroz con pollo peruano', 'category' => 'Platos criollos', 'description' => 'Arroz verde con cilantro, presa de pollo, arvejas y salsa criolla.', 'price' => 17.50, 'offer_price' => null, 'image_path' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => false],
            ['name' => 'Anticuchos de corazón', 'category' => 'Platos criollos', 'description' => 'Brochetas a la parrilla con aderezo tradicional, papa dorada y ají.', 'price' => 16.00, 'offer_price' => null, 'image_path' => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => false],
            ['name' => 'Caldo de gallina', 'category' => 'Sopas criollas', 'description' => 'Caldo reconfortante con gallina, fideos, huevo y cebolla china.', 'price' => 14.00, 'offer_price' => null, 'image_path' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => false],
            ['name' => 'Suspiro a la limeña', 'category' => 'Postres', 'description' => 'Dulce de leche cremoso coronado con merengue y un toque de canela.', 'price' => 8.00, 'offer_price' => null, 'image_path' => 'https://images.unsplash.com/photo-1464305795204-6f5bbfc7fb81?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => false],
            ['name' => 'Chicha morada', 'category' => 'Bebidas peruanas', 'description' => 'Bebida peruana de maíz morado, piña, canela y limón.', 'price' => 5.00, 'offer_price' => null, 'image_path' => 'https://images.unsplash.com/photo-1513558161293-cdaf765edfd7?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => false],
            ['name' => 'Seco de res con frejoles', 'category' => 'Platos criollos', 'description' => 'Carne guisada lentamente con culantro, frejoles y arroz.', 'price' => 23.00, 'offer_price' => null, 'image_path' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => true],
            ['name' => 'Pollo a la brasa', 'category' => 'Platos a la carta', 'description' => 'Pollo dorado al horno con papas, ensalada fresca y cremas de la casa.', 'price' => 25.00, 'offer_price' => null, 'image_path' => 'https://images.unsplash.com/photo-1532550907401-a500c9a57435?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => true],
            ['name' => 'Tallarines verdes con bistec', 'category' => 'Platos a la carta', 'description' => 'Tallarines en salsa de albahaca y espinaca con bistec a la plancha.', 'price' => 22.00, 'offer_price' => null, 'image_path' => 'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?auto=format&fit=crop&w=900&q=85', 'is_available' => true, 'is_featured' => false],
        ];

        foreach ($foods as $food) {
            Food::withTrashed()->updateOrCreate(['name' => $food['name']], $food)->restore();
        }
    }
}
