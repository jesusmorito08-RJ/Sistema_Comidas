<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentProof;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentProofReviewTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lX8AAAAASUVORK5CYII=';

    public function test_admin_sees_only_payable_pending_evidence_with_order_and_customer_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['name' => 'Cliente de prueba', 'role' => 'customer']);
        $pendingOrder = $this->createOrder($customer, 'PD-REVIEW-001');
        $pendingOrder->paymentProofs()->create([
            'file_path' => 'payment-proofs/pending.png',
            'submitted_at' => now(),
        ]);
        $rejectedOrder = $this->createOrder($customer, 'PD-REVIEW-002');
        $rejectedOrder->paymentProofs()->create([
            'file_path' => 'payment-proofs/rejected.png',
            'status' => PaymentProof::STATUS_REJECTED,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payment-proofs.index'))
            ->assertOk()
            ->assertSeeText('PD-REVIEW-001')
            ->assertSeeText('Cliente de prueba')
            ->assertSeeText('Yape')
            ->assertSeeText('$42.00')
            ->assertDontSeeText('PD-REVIEW-002');
    }

    public function test_only_an_admin_can_view_a_private_proof_image(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createOrder($customer, 'PD-REVIEW-003');
        $path = 'payment-proofs/private.png';
        Storage::disk('local')->put($path, base64_decode(self::VALID_PNG, true));
        $proof = $order->paymentProofs()->create(['file_path' => $path]);

        $adminResponse = $this->actingAs($admin)->get(route('admin.payment-proofs.image', $proof));

        $adminResponse->assertOk();
        $this->assertSame('image/png', $adminResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('private', (string) $adminResponse->headers->get('Cache-Control'));
        $this->assertSame('nosniff', $adminResponse->headers->get('X-Content-Type-Options'));
        Storage::disk('public')->assertMissing($path);

        $this->actingAs($customer)
            ->get(route('admin.payment-proofs.image', $proof))
            ->assertForbidden();

        auth()->logout();
        $this->get(route('admin.payment-proofs.image', $proof))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_approval_marks_payment_paid_without_changing_logistic_status(): void
    {
        $this->freezeTime();
        $admin = User::factory()->create(['role' => 'admin']);
        $untrustedReviewer = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createOrder($customer, 'PD-REVIEW-004', 'preparing');
        $proof = $order->paymentProofs()->create([
            'file_path' => 'payment-proofs/approval.png',
            'submitted_at' => now()->subMinute(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.payment-proofs.approve', $proof), [
                'confirm_received' => '1',
                'status' => PaymentProof::STATUS_REJECTED,
                'reviewed_by' => $untrustedReviewer->id,
                'reviewed_at' => '2000-01-01 00:00:00',
                'rejection_reason' => 'Valor falsificado',
            ])
            ->assertRedirect(route('admin.payment-proofs.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payment_proofs', [
            'id' => $proof->id,
            'status' => PaymentProof::STATUS_APPROVED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now()->toDateTimeString(),
            'rejection_reason' => null,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'preparing',
            'payment_status' => 'paid',
        ]);
    }

    public function test_admin_must_confirm_money_arrived_before_approving(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createOrder($customer, 'PD-REVIEW-005');
        $proof = $order->paymentProofs()->create(['file_path' => 'payment-proofs/unverified.png']);

        $this->actingAs($admin)
            ->patch(route('admin.payment-proofs.approve', $proof))
            ->assertSessionHasErrors('confirm_received');

        $this->assertDatabaseHas('payment_proofs', [
            'id' => $proof->id,
            'status' => PaymentProof::STATUS_PENDING,
            'reviewed_by' => null,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    public function test_admin_rejection_requires_a_reason_and_preserves_pending_payment(): void
    {
        $this->freezeTime();
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createOrder($customer, 'PD-REVIEW-006', 'confirmed');
        $proof = $order->paymentProofs()->create(['file_path' => 'payment-proofs/rejection.png']);

        $this->actingAs($admin)
            ->patch(route('admin.payment-proofs.reject', $proof))
            ->assertSessionHasErrors('rejection_reason');

        $this->assertDatabaseHas('payment_proofs', [
            'id' => $proof->id,
            'status' => PaymentProof::STATUS_PENDING,
            'reviewed_by' => null,
        ]);

        $reason = '<script>alert(1)</script>';
        $this->patch(route('admin.payment-proofs.reject', $proof), [
            'rejection_reason' => $reason,
        ])->assertRedirect(route('admin.payment-proofs.index'));

        $this->assertDatabaseHas('payment_proofs', [
            'id' => $proof->id,
            'status' => PaymentProof::STATUS_REJECTED,
            'reviewed_by' => $admin->id,
            'rejection_reason' => $reason,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);

        $this->actingAs($customer)
            ->get(route('orders.mine'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee($reason, false);
    }

    public function test_reviewed_evidence_cannot_be_processed_again_by_another_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $secondAdmin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createOrder($customer, 'PD-REVIEW-007');
        $proof = $order->paymentProofs()->create(['file_path' => 'payment-proofs/once.png']);

        $this->actingAs($admin)
            ->patch(route('admin.payment-proofs.approve', $proof), ['confirm_received' => '1'])
            ->assertRedirect(route('admin.payment-proofs.index'));

        $this->actingAs($secondAdmin)
            ->patch(route('admin.payment-proofs.reject', $proof), ['rejection_reason' => 'Intento duplicado'])
            ->assertSessionHasErrors('payment_proof');

        $this->assertDatabaseHas('payment_proofs', [
            'id' => $proof->id,
            'status' => PaymentProof::STATUS_APPROVED,
            'reviewed_by' => $admin->id,
            'rejection_reason' => null,
        ]);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
        ]);
    }

    public function test_customer_cannot_review_payment_evidence(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $owner = User::factory()->create(['role' => 'customer']);
        $order = $this->createOrder($owner, 'PD-REVIEW-008');
        $proof = $order->paymentProofs()->create(['file_path' => 'payment-proofs/forbidden.png']);

        $this->actingAs($customer)
            ->patch(route('admin.payment-proofs.approve', $proof), ['confirm_received' => '1'])
            ->assertForbidden();

        $this->assertDatabaseHas('payment_proofs', [
            'id' => $proof->id,
            'status' => PaymentProof::STATUS_PENDING,
            'reviewed_by' => null,
        ]);
    }

    private function createOrder(User $customer, string $orderNumber, string $status = 'pending'): Order
    {
        return $customer->orders()->create([
            'order_number' => $orderNumber,
            'status' => $status,
            'payment_method' => 'yape',
            'payment_status' => 'pending',
            'total' => 42,
            'delivery_address' => 'Calle de prueba',
        ]);
    }
}
