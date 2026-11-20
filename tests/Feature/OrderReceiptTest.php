<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentProof;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_download_the_same_internal_pdf_after_payment_approval(): void
    {
        $customer = User::factory()->create(['name' => 'Cliente pagado', 'role' => 'customer']);
        $order = $this->createPaidOrder($customer, 'PD-RECEIPT-001');

        $firstResponse = $this->actingAs($customer)->get(route('orders.receipt.download', $order));
        $firstResponse->assertOk();
        $this->assertSame('application/pdf', $firstResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('private', (string) $firstResponse->headers->get('Cache-Control'));
        $this->assertStringStartsWith('%PDF-', $firstResponse->getContent());
        $this->assertStringContainsString('attachment', (string) $firstResponse->headers->get('Content-Disposition'));
        $this->assertStringContainsString('RC-PD-RECEIPT-001', (string) $firstResponse->headers->get('Content-Disposition'));

        $secondResponse = $this->get(route('orders.receipt.download', $order));
        $secondResponse->assertOk();
        $this->assertSame(
            $firstResponse->headers->get('Content-Disposition'),
            $secondResponse->headers->get('Content-Disposition'),
        );
        $this->assertDatabaseCount('payment_proofs', 1);
    }

    public function test_customer_cannot_download_a_receipt_before_payment_is_approved(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $customer->orders()->create([
            'order_number' => 'PD-RECEIPT-002',
            'payment_method' => 'plin',
            'payment_status' => 'pending',
            'total' => 28,
            'delivery_address' => 'Calle de prueba',
        ]);
        $order->paymentProofs()->create([
            'file_path' => 'payment-proofs/pending.png',
            'status' => PaymentProof::STATUS_PENDING,
        ]);

        $this->actingAs($customer)
            ->get(route('orders.receipt.download', $order))
            ->assertNotFound();

        $this->assertDatabaseCount('payment_proofs', 1);
    }

    public function test_paid_order_without_an_approved_proof_has_no_receipt(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $customer->orders()->create([
            'order_number' => 'PD-RECEIPT-006',
            'payment_method' => 'yape',
            'payment_status' => 'paid',
            'total' => 28,
            'delivery_address' => 'Calle de prueba',
        ]);
        $order->paymentProofs()->create([
            'file_path' => 'payment-proofs/pending.png',
            'status' => PaymentProof::STATUS_PENDING,
        ]);

        $this->actingAs($customer)
            ->get(route('orders.receipt.download', $order))
            ->assertNotFound();
    }

    public function test_receipt_is_private_to_the_customer_and_available_to_an_admin(): void
    {
        $owner = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createPaidOrder($owner, 'PD-RECEIPT-003');

        $this->actingAs($otherCustomer)
            ->get(route('orders.receipt.download', $order))
            ->assertNotFound();

        $this->actingAs($owner)
            ->get(route('admin.orders.receipt.download', $order))
            ->assertForbidden();

        $adminPanelResponse = $this->actingAs($admin)->get(route('admin.orders'));
        $adminPanelResponse
            ->assertOk()
            ->assertSee(route('admin.orders.receipt.download', $order), false)
            ->assertSee(route('admin.orders.receipt.print', $order), false);

        $adminResponse = $this->actingAs($admin)
            ->get(route('admin.orders.receipt.download', $order));
        $adminResponse->assertOk();
        $this->assertSame('application/pdf', $adminResponse->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-', $adminResponse->getContent());

        $this->get(route('admin.orders.receipt.print', $order))
            ->assertOk()
            ->assertSeeText('Comprobante interno de pago');
    }

    public function test_print_view_contains_a_print_action_and_non_tax_receipt_disclaimer(): void
    {
        $customer = User::factory()->create(['name' => 'Cliente de prueba', 'role' => 'customer']);
        $order = $this->createPaidOrder($customer, 'PD-RECEIPT-004');

        $printResponse = $this->actingAs($customer)
            ->get(route('orders.receipt.print', $order));

        $printResponse
            ->assertOk()
            ->assertSeeText('Comprobante interno de pago')
            ->assertSeeText('RC-PD-RECEIPT-004')
            ->assertSeeText(config('payments.business_name') ?: config('app.name'))
            ->assertSeeText('Fecha de aprobación')
            ->assertSeeText('PAGADO')
            ->assertSeeText('Este es un comprobante interno de pago. No es una boleta ni una factura electrónica de SUNAT.')
            ->assertSee('window.print()', false)
            ->assertSee(route('orders.receipt.download', $order), false)
            ->assertSeeText('Producto histórico')
            ->assertSeeText('S/ 21.00')
            ->assertSeeText('S/ 42.00')
            ->assertSeeText('Yape')
            ->assertSeeText('Cliente de prueba');
        $cacheControl = (string) $printResponse->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    public function test_receipt_displays_legacy_payment_methods_without_mislabelling_them(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createPaidOrder($customer, 'PD-RECEIPT-007');
        $order->update(['payment_method' => 'card']);

        $this->actingAs($customer)
            ->get(route('orders.receipt.print', $order))
            ->assertOk()
            ->assertSeeText('Tarjeta')
            ->assertDontSeeText('Plin');
    }

    public function test_guest_is_redirected_before_receipt_access(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createPaidOrder($customer, 'PD-RECEIPT-005');

        $this->get(route('orders.receipt.download', $order))
            ->assertRedirect(route('login'));
    }

    private function createPaidOrder(User $customer, string $orderNumber): Order
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $customer->orders()->create([
            'order_number' => $orderNumber,
            'status' => 'delivered',
            'payment_method' => 'yape',
            'payment_status' => 'paid',
            'total' => 42,
            'delivery_address' => 'Calle de prueba',
        ]);
        $order->items()->create([
            'food_id' => null,
            'food_name' => 'Producto histórico',
            'unit_price' => 21,
            'quantity' => 2,
            'subtotal' => 42,
        ]);
        $order->paymentProofs()->create([
            'file_path' => 'payment-proofs/approved.png',
            'status' => PaymentProof::STATUS_APPROVED,
            'reviewed_by' => $admin->id,
            'submitted_at' => now()->subMinutes(3),
            'reviewed_at' => now()->subMinutes(2),
        ]);

        return $order;
    }
}
