<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_panel_shows_only_the_authenticated_customers_activity(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $customer->orders()->create([
            'order_number' => 'PD-MI-CUENTA-1',
            'payment_method' => 'cash',
            'total' => 28,
            'delivery_address' => 'Calle cliente',
        ]);
        $otherCustomer->orders()->create([
            'order_number' => 'PD-OTRO-CLIENTE-1',
            'payment_method' => 'cash',
            'total' => 30,
            'delivery_address' => 'Calle ajena',
        ]);
        $customer->reservations()->create([
            'reservation_date' => '2030-04-15',
            'reservation_time' => '18:00',
            'guests' => 2,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSeeText('PD-MI-CUENTA-1')
            ->assertSeeText('15/04/2030')
            ->assertDontSeeText('PD-OTRO-CLIENTE-1');
    }

    public function test_administrators_cannot_open_the_customer_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('customer.dashboard'))
            ->assertForbidden();
    }
}
