<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentProof;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentProofTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lX8AAAAASUVORK5CYII=';

    public function test_order_preserves_multiple_payment_attempts_and_reviewer_relationships(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $customer->orders()->create([
            'order_number' => 'PD-TEST-PROOF-0001',
            'payment_method' => 'yape',
            'total' => 42,
            'delivery_address' => 'Calle 123',
        ]);
        $rejectedProof = $order->paymentProofs()->create([
            'file_path' => 'payment-proofs/first.jpg',
            'status' => PaymentProof::STATUS_REJECTED,
            'rejection_reason' => 'Imagen ilegible',
            'reviewed_by' => $admin->id,
            'submitted_at' => '2026-11-17 10:00:00',
            'reviewed_at' => '2026-11-17 10:10:00',
        ]);
        $pendingProof = $order->paymentProofs()->create([
            'file_path' => 'payment-proofs/second.jpg',
            'submitted_at' => '2026-11-17 11:00:00',
        ]);

        $this->assertDatabaseCount('payment_proofs', 2);
        $this->assertSame($order->id, $rejectedProof->order->id);
        $this->assertSame($admin->id, $rejectedProof->reviewer->id);
        $this->assertSame($rejectedProof->id, $admin->reviewedPaymentProofs()->firstOrFail()->id);
        $this->assertSame(PaymentProof::STATUS_PENDING, $pendingProof->fresh()->status);
        $this->assertSame(2, $order->paymentProofs()->count());

        $admin->delete();

        $this->assertNull($rejectedProof->fresh()->reviewed_by);
        $this->assertNull($rejectedProof->fresh()->reviewer);
    }

    public function test_customer_uploads_a_valid_private_image_as_pending_without_setting_review_fields(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createOrder($customer);

        $response = $this->actingAs($customer)->post(route('orders.payment-proofs.store', $order), [
            'evidence' => $this->validPng(),
            'status' => PaymentProof::STATUS_APPROVED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => '2026-11-17 12:00:00',
            'file_path' => 'public/forged.png',
        ]);

        $response->assertRedirect(route('orders.mine'))
            ->assertSessionHas('success');
        $proof = $order->paymentProofs()->firstOrFail();
        $this->assertSame(PaymentProof::STATUS_PENDING, $proof->status);
        $this->assertNull($proof->reviewed_by);
        $this->assertNotSame('public/forged.png', $proof->file_path);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
        Storage::disk('local')->assertExists($proof->file_path);
        Storage::disk('public')->assertMissing($proof->file_path);
    }

    public function test_customer_cannot_upload_a_file_that_is_not_an_image(): void
    {
        Storage::fake('local');
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createOrder($customer);

        $this->actingAs($customer)
            ->post(route('orders.payment-proofs.store', $order), [
                'evidence' => UploadedFile::fake()->createWithContent('voucher.txt', 'not an image'),
            ])
            ->assertSessionHasErrors('evidence');

        $this->assertDatabaseCount('payment_proofs', 0);
        Storage::disk('local')->assertEmpty();
    }

    public function test_customer_cannot_upload_an_image_with_an_unapproved_extension(): void
    {
        Storage::fake('local');
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createOrder($customer);

        $this->actingAs($customer)
            ->post(route('orders.payment-proofs.store', $order), [
                'evidence' => $this->validPng('voucher.txt'),
            ])
            ->assertSessionHasErrors('evidence');

        $this->assertDatabaseCount('payment_proofs', 0);
        Storage::disk('local')->assertEmpty();
    }

    public function test_customer_cannot_upload_an_image_larger_than_five_megabytes(): void
    {
        Storage::fake('local');
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createOrder($customer);

        $this->actingAs($customer)
            ->post(route('orders.payment-proofs.store', $order), [
                'evidence' => $this->validPng()->size(5121),
            ])
            ->assertSessionHasErrors('evidence');

        $this->assertDatabaseCount('payment_proofs', 0);
        Storage::disk('local')->assertEmpty();
    }

    public function test_customer_cannot_upload_evidence_for_another_customers_order(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $order = $this->createOrder($owner);

        $this->actingAs($otherCustomer)
            ->post(route('orders.payment-proofs.store', $order), [
                'evidence' => $this->validPng(),
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('payment_proofs', 0);
        Storage::disk('local')->assertEmpty();
    }

    public function test_customer_can_resubmit_after_rejection_and_the_previous_attempt_is_preserved(): void
    {
        Storage::fake('local');
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createOrder($customer);
        $previousProof = $order->paymentProofs()->create([
            'file_path' => 'payment-proofs/previous.png',
            'status' => PaymentProof::STATUS_REJECTED,
            'rejection_reason' => 'Imagen ilegible',
            'submitted_at' => now()->subMinute(),
        ]);

        $this->actingAs($customer)
            ->post(route('orders.payment-proofs.store', $order), [
                'evidence' => $this->validPng('replacement.png'),
            ])
            ->assertRedirect(route('orders.mine'));

        $newProof = $order->paymentProofs()->latest('id')->firstOrFail();
        $this->assertDatabaseCount('payment_proofs', 2);
        $this->assertSame(PaymentProof::STATUS_REJECTED, $previousProof->fresh()->status);
        $this->assertSame(PaymentProof::STATUS_PENDING, $newProof->status);
        Storage::disk('local')->assertExists($newProof->file_path);
    }

    public function test_customer_sees_rejected_status_and_reason_in_order_history(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createOrder($customer);
        $order->paymentProofs()->create([
            'file_path' => 'payment-proofs/rejected.png',
            'status' => PaymentProof::STATUS_REJECTED,
            'rejection_reason' => 'La imagen no permite verificar el pago.',
        ]);

        $this->actingAs($customer)
            ->get(route('orders.mine'))
            ->assertOk()
            ->assertSeeText('Rechazada')
            ->assertSeeText('Motivo del rechazo: La imagen no permite verificar el pago.')
            ->assertSeeText('Reenviar evidencia')
            ->assertSee('name="evidence"', false);
    }

    public function test_customer_sees_pending_status_without_another_upload_form(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createOrder($customer);
        $order->paymentProofs()->create([
            'file_path' => 'payment-proofs/pending.png',
            'status' => PaymentProof::STATUS_PENDING,
        ]);

        $this->actingAs($customer)
            ->get(route('orders.mine'))
            ->assertOk()
            ->assertSeeText('En revisión')
            ->assertDontSee('name="evidence"', false);
    }

    public function test_customer_cannot_cancel_an_order_while_its_payment_evidence_is_pending(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createOrder($customer);
        $order->paymentProofs()->create([
            'file_path' => 'payment-proofs/pending-cancellation.png',
            'status' => PaymentProof::STATUS_PENDING,
        ]);

        $this->actingAs($customer)
            ->delete(route('orders.cancel', $order))
            ->assertSessionHasErrors('order');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    #[DataProvider('nonResubmittableStatuses')]
    public function test_customer_cannot_resubmit_while_latest_evidence_is_not_rejected(string $status): void
    {
        Storage::fake('local');
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createOrder($customer);
        $order->paymentProofs()->create([
            'file_path' => 'payment-proofs/current.png',
            'status' => $status,
        ]);

        $this->actingAs($customer)
            ->post(route('orders.payment-proofs.store', $order), [
                'evidence' => $this->validPng('duplicate.png'),
            ])
            ->assertSessionHasErrors('evidence');

        $this->assertDatabaseCount('payment_proofs', 1);
        Storage::disk('local')->assertEmpty();
    }

    #[DataProvider('activeStatuses')]
    public function test_customer_cannot_resubmit_if_an_older_attempt_is_still_active(string $status): void
    {
        Storage::fake('local');
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createOrder($customer);
        $order->paymentProofs()->create([
            'file_path' => 'payment-proofs/active.png',
            'status' => $status,
            'submitted_at' => now()->subMinutes(2),
        ]);
        $order->paymentProofs()->create([
            'file_path' => 'payment-proofs/latest-rejected.png',
            'status' => PaymentProof::STATUS_REJECTED,
            'submitted_at' => now()->subMinute(),
        ]);

        $this->actingAs($customer)
            ->get(route('orders.mine'))
            ->assertOk()
            ->assertDontSee('name="evidence"', false);

        $this->actingAs($customer)
            ->post(route('orders.payment-proofs.store', $order), [
                'evidence' => $this->validPng('blocked.png'),
            ])
            ->assertSessionHasErrors('evidence');

        $this->assertDatabaseCount('payment_proofs', 2);
        Storage::disk('local')->assertEmpty();
    }

    public static function nonResubmittableStatuses(): array
    {
        return [
            'pending' => [PaymentProof::STATUS_PENDING],
            'approved' => [PaymentProof::STATUS_APPROVED],
        ];
    }

    public static function activeStatuses(): array
    {
        return [
            'pending' => [PaymentProof::STATUS_PENDING],
            'approved' => [PaymentProof::STATUS_APPROVED],
        ];
    }

    public function test_guest_cannot_upload_payment_evidence(): void
    {
        Storage::fake('local');
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createOrder($customer);

        $this->post(route('orders.payment-proofs.store', $order), [
            'evidence' => $this->validPng(),
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('payment_proofs', 0);
        Storage::disk('local')->assertEmpty();
    }

    public function test_admin_cannot_upload_customer_payment_evidence(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createOrder($admin);

        $this->actingAs($admin)
            ->post(route('orders.payment-proofs.store', $order), [
                'evidence' => $this->validPng(),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('payment_proofs', 0);
        Storage::disk('local')->assertEmpty();
    }

    private function createOrder(User $customer): Order
    {
        return $customer->orders()->create([
            'order_number' => 'PD-TEST-PROOF-UPLOAD',
            'payment_method' => 'yape',
            'total' => 42,
            'delivery_address' => 'Calle 123',
        ]);
    }

    private function validPng(string $name = 'voucher.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode(self::VALID_PNG, true),
        );
    }
}
