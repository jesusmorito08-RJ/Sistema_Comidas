<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_skip_order_statuses_and_can_archive_cancelled_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $customer->orders()->create([
            'order_number' => 'PD-TEST-0001',
            'payment_method' => 'cash',
            'total' => 36,
            'delivery_address' => 'Calle 123',
        ]);

        $this->actingAs($admin);
        $this->get(route('admin.orders'))->assertOk();
        $this->get(route('admin.orders.edit', $order))->assertOk();

        $this->patch(route('admin.orders.update', $order), [
            'status' => 'preparing',
            'payment_status' => 'pending',
        ])->assertSessionHasErrors('status');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);

        $this->patch(route('admin.orders.update', $order), [
            'status' => 'cancelled',
            'payment_status' => 'pending',
        ])->assertRedirect(route('admin.orders'));

        $this->delete(route('admin.orders.archive', $order))->assertRedirect(route('admin.orders'));
        $this->assertSoftDeleted('orders', ['id' => $order->id]);

        $this->post(route('admin.orders.restore', $order->id))->assertRedirect();
        $this->assertNotSoftDeleted('orders', ['id' => $order->id]);
    }

    public function test_admin_cannot_cancel_an_order_with_a_registered_payment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $customer->orders()->create([
            'order_number' => 'PD-TEST-0002',
            'payment_method' => 'card',
            'payment_status' => 'paid',
            'total' => 36,
            'delivery_address' => 'Calle 123',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), [
                'status' => 'cancelled',
                'payment_status' => 'paid',
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending', 'payment_status' => 'paid']);
    }
}
