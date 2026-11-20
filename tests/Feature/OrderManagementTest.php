<?php

namespace Tests\Feature;

use App\Models\PaymentProof;
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
        $this->get(route('admin.orders.edit', $order))
            ->assertOk()
            ->assertDontSee('name="payment_status"', false);

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

    public function test_admin_cannot_mark_an_order_paid_from_the_logistic_order_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $customer->orders()->create([
            'order_number' => 'PD-TEST-0003',
            'payment_method' => 'yape',
            'payment_status' => 'pending',
            'total' => 36,
            'delivery_address' => 'Calle 123',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), [
                'status' => 'pending',
                'payment_status' => 'paid',
            ])
            ->assertRedirect(route('admin.orders'));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    public function test_admin_cannot_cancel_an_order_with_an_unreviewed_payment_proof(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $customer->orders()->create([
            'order_number' => 'PD-TEST-0004',
            'payment_method' => 'yape',
            'payment_status' => 'pending',
            'total' => 36,
            'delivery_address' => 'Calle 123',
        ]);
        $order->paymentProofs()->create(['file_path' => 'payment-proofs/pending.png']);

        $this->actingAs($admin)
            ->patch(route('admin.orders.update', $order), [
                'status' => 'cancelled',
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
        $this->assertDatabaseHas('payment_proofs', [
            'order_id' => $order->id,
            'status' => PaymentProof::STATUS_PENDING,
        ]);
    }

    public function test_revenue_reports_include_only_orders_whose_payments_are_paid(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $pendingOrder = $customer->orders()->create([
            'order_number' => 'PD-TEST-0005',
            'payment_method' => 'yape',
            'payment_status' => 'pending',
            'total' => 100,
            'delivery_address' => 'Calle 123',
        ]);
        $pendingOrder->items()->create([
            'food_id' => null,
            'food_name' => 'No pagado',
            'unit_price' => 100,
            'quantity' => 1,
            'subtotal' => 100,
        ]);
        $paidOrder = $customer->orders()->create([
            'order_number' => 'PD-TEST-0006',
            'status' => 'delivered',
            'payment_method' => 'plin',
            'payment_status' => 'paid',
            'total' => 25,
            'delivery_address' => 'Calle 123',
        ]);
        $paidOrder->items()->create([
            'food_id' => null,
            'food_name' => 'Pagado',
            'unit_price' => 25,
            'quantity' => 1,
            'subtotal' => 25,
        ]);
        $customer->orders()->create([
            'order_number' => 'PD-TEST-0007',
            'status' => 'cancelled',
            'payment_method' => 'card',
            'payment_status' => 'paid',
            'total' => 50,
            'delivery_address' => 'Calle 123',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('stats', fn (array $stats): bool => (float) $stats['revenue'] === 25.0)
            ->assertViewHas('week', fn (array $week): bool => array_sum(array_column($week, 'total')) === 25.0);

        $this->get(route('admin.reports'))
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary): bool => $summary['total_orders'] === 2
                && (float) $summary['total_sales'] === 25.0)
            ->assertViewHas('paymentTotals', fn ($totals): bool => $totals->has('plin')
                && ! $totals->has('yape'))
            ->assertViewHas('topFoods', fn ($foods): bool => $foods->contains('food_name', 'Pagado')
                && ! $foods->contains('food_name', 'No pagado'));
    }
}
