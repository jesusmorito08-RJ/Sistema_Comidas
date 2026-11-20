<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FoodOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_place_an_order_using_the_current_offer_price(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $food = Food::create([
            'name' => 'Pasta de prueba',
            'category' => 'Pasta',
            'description' => 'Pasta fresca',
            'price' => 15,
            'offer_price' => 12,
            'is_available' => true,
        ]);

        $response = $this->actingAs($customer)
            ->withSession(['cart' => [(string) $food->id => 2]])
            ->post(route('checkout'), [
                'delivery_address' => 'Calle 123',
                'payment_method' => 'yape',
            ]);

        $response->assertRedirect(route('orders.mine'));
        $order = Order::with('items')->firstOrFail();
        $this->assertSame('24.00', $order->total);
        $this->assertSame('Pasta de prueba', $order->items->first()->food_name);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame('yape', $order->payment_method);
        $this->assertSame('pending', $order->status);
        $this->assertSame('pending', $order->payment_status);
    }

    #[DataProvider('paymentMethods')]
    public function test_checkout_creates_yape_and_plin_orders_with_pending_statuses(string $paymentMethod): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $food = Food::create([
            'name' => 'Plato de prueba',
            'category' => 'Menú',
            'description' => 'Plato de prueba',
            'price' => 20,
            'is_available' => true,
        ]);

        $this->actingAs($customer)
            ->withSession(['cart' => [(string) $food->id => 1]])
            ->post(route('checkout'), [
                'delivery_address' => 'Calle 123',
                'payment_method' => $paymentMethod,
            ])
            ->assertRedirect(route('orders.mine'));

        $order = Order::query()->firstOrFail();
        $this->assertSame($paymentMethod, $order->payment_method);
        $this->assertSame('pending', $order->status);
        $this->assertSame('pending', $order->payment_status);
    }

    public function test_cart_displays_quantity_controls_product_details_and_order_total(): void
    {
        // Forzar variables de pago vacías para este test, independientemente del .env local
        config([
            'payments.methods.yape.holder' => null,
            'payments.methods.yape.phone' => null,
            'payments.methods.yape.qr_path' => null,
            'payments.methods.plin.holder' => null,
            'payments.methods.plin.phone' => null,
            'payments.methods.plin.qr_path' => null,
        ]);

        $customer = User::factory()->create(['role' => 'customer']);
        $food = Food::create([
            'name' => 'Pasta de prueba',
            'category' => 'Pasta',
            'description' => 'Pasta fresca',
            'price' => 15,
            'offer_price' => 12,
            'image_path' => 'https://example.test/pasta.jpg',
            'is_available' => true,
        ]);

        $response = $this->actingAs($customer)
            ->withSession(['cart' => [(string) $food->id => 2]])
            ->get(route('cart.index'));

        $response->assertOk()
            ->assertViewIs('store.cart')
            ->assertSee('alt="Pasta de prueba"', false)
            ->assertSee('aria-label="Disminuir cantidad de Pasta de prueba"', false)
            ->assertSee('aria-label="Aumentar cantidad de Pasta de prueba"', false)
            ->assertSee('name="quantity" value="1"', false)
            ->assertSee('name="quantity" value="3"', false)
            ->assertSeeText('Pasta de prueba')
            ->assertSeeText('$12.00 c/u')
            ->assertSeeText('$24.00')
            ->assertSeeText('Productos (2)')
            ->assertSeeText('Total del pedido')
            ->assertSee('value="yape"', false)
            ->assertSee('value="plin"', false)
            ->assertDontSee('value="cash"', false)
            ->assertDontSee('value="card"', false)
            ->assertDontSee('value="transfer"', false)
            ->assertSeeText('Los datos de Yape aún no están configurados.')
            ->assertSeeText('Los datos de Plin aún no están configurados.');
    }

    public function test_cart_does_not_display_a_configured_non_image_as_a_business_qr(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('payment-qr/yape.txt', 'not a QR image');
        config([
            'payments.methods.yape.holder' => 'Titular de prueba',
            'payments.methods.yape.qr_path' => 'payment-qr/yape.txt',
        ]);
        $customer = User::factory()->create(['role' => 'customer']);
        $food = Food::create([
            'name' => 'Plato de prueba',
            'category' => 'Menú',
            'description' => 'Plato de prueba',
            'price' => 20,
            'is_available' => true,
        ]);

        $this->actingAs($customer)
            ->withSession(['cart' => [(string) $food->id => 1]])
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSeeText('Titular: Titular de prueba')
            ->assertSeeText('El QR de Yape no está disponible como imagen pública JPG o PNG.')
            ->assertDontSee('payment-qr/yape.txt', false);
    }

    public function test_customer_cannot_open_the_admin_panel_but_admin_can(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_checkout_rejects_a_cart_with_a_food_that_became_unavailable(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $available = Food::create([
            'name' => 'Plato disponible',
            'category' => 'Menú',
            'description' => 'Disponible',
            'price' => 10,
            'is_available' => true,
        ]);
        $unavailable = Food::create([
            'name' => 'Plato pausado',
            'category' => 'Menú',
            'description' => 'Pausado',
            'price' => 12,
            'is_available' => false,
        ]);

        $this->actingAs($customer)
            ->withSession(['cart' => [(string) $available->id => 1, (string) $unavailable->id => 1]])
            ->post(route('checkout'), [
                'delivery_address' => 'Calle 123',
                'payment_method' => 'yape',
            ])
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_registration_always_creates_a_customer_account(): void
    {
        $this->post(route('register.store'), [
            'name' => 'María Pérez',
            'email' => 'maria@example.test',
            'password' => 'un-password-seguro',
            'password_confirmation' => 'un-password-seguro',
        ])->assertRedirect(route('customer.dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'maria@example.test', 'role' => 'customer']);
        $this->assertAuthenticatedAs(User::where('email', 'maria@example.test')->firstOrFail());
    }

    public function test_storefront_filters_foods_by_category_and_shows_available_categories(): void
    {
        Food::create([
            'name' => 'Lomo Saltado',
            'category' => 'Platos criollos',
            'description' => 'Salteado peruano',
            'price' => 20,
            'is_available' => true,
        ]);
        Food::create([
            'name' => 'Suspiro Limeño',
            'category' => 'Postres',
            'description' => 'Postre tradicional',
            'price' => 8,
            'is_available' => true,
        ]);
        Food::create([
            'name' => 'Plato no disponible',
            'category' => 'Platos criollos',
            'description' => 'Agotado',
            'price' => 12,
            'is_available' => false,
        ]);

        $response = $this->get(route('foods.index', ['category' => 'Platos criollos']));

        $response->assertOk()
            ->assertSeeText('Lomo Saltado')
            ->assertDontSeeText('Suspiro Limeño')
            ->assertDontSeeText('Plato no disponible')
            ->assertSee('Postres')
            ->assertViewHas('selectedCategory', 'Platos criollos')
            ->assertViewHas('categories', fn ($categories) => $categories->contains('Platos criollos')
                && $categories->contains('Platos a la carta')
                && $categories->contains('Postres'));
    }

    public function test_storefront_shows_the_new_a_la_carta_dishes(): void
    {
        $this->get(route('foods.index', ['category' => 'Platos a la carta']))
            ->assertOk()
            ->assertSeeText('Pollo a la brasa')
            ->assertSeeText('Tallarines verdes con bistec');
    }

    public static function paymentMethods(): array
    {
        return [
            'Yape' => ['yape'],
            'Plin' => ['plin'],
        ];
    }
}
