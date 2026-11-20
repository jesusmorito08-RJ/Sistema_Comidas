<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentProof;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class PaymentProofController extends Controller
{
    public function store(Request $request, Order $order): RedirectResponse
    {
        abort_unless($request->user()->role === 'customer', 403);

        $customerOrder = $request->user()->orders()->whereKey($order->getKey())->firstOrFail();
        $validated = $request->validate([
            'evidence' => ['required', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:5120'],
        ]);
        $evidence = $validated['evidence'];
        $storedPath = null;

        try {
            DB::transaction(function () use ($customerOrder, $evidence, &$storedPath): void {
                $lockedOrder = Order::query()
                    ->whereKey($customerOrder->getKey())
                    ->where('user_id', $customerOrder->user_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! in_array($lockedOrder->payment_method, ['yape', 'plin'], true)) {
                    throw ValidationException::withMessages([
                        'evidence' => 'Solo puedes adjuntar evidencias para pedidos pagados por Yape o Plin.',
                    ]);
                }

                if ($lockedOrder->status === 'cancelled' || $lockedOrder->payment_status !== 'pending') {
                    throw ValidationException::withMessages([
                        'evidence' => 'Este pedido no admite nuevas evidencias de pago.',
                    ]);
                }

                $latestProof = $lockedOrder->paymentProofs()
                    ->orderByDesc('submitted_at')
                    ->orderByDesc('id')
                    ->first();
                $hasPendingOrApprovedProof = $lockedOrder->paymentProofs()
                    ->whereIn('status', [PaymentProof::STATUS_PENDING, PaymentProof::STATUS_APPROVED])
                    ->exists();

                if ($hasPendingOrApprovedProof
                    || ($latestProof !== null && $latestProof->status !== PaymentProof::STATUS_REJECTED)) {
                    throw ValidationException::withMessages([
                        'evidence' => 'Solo puedes enviar una evidencia nueva si el último intento fue rechazado.',
                    ]);
                }

                $path = $evidence->store('payment-proofs', 'local');
                if (! is_string($path) || $path === '') {
                    throw new RuntimeException('No se pudo guardar la imagen de evidencia.');
                }

                $storedPath = $path;

                $lockedOrder->paymentProofs()->create([
                    'file_path' => $storedPath,
                    'status' => PaymentProof::STATUS_PENDING,
                    'submitted_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            if ($storedPath !== null
                && Storage::disk('local')->exists($storedPath)
                && ! Storage::disk('local')->delete($storedPath)) {
                report(new RuntimeException('No se pudo eliminar el archivo de evidencia que quedó sin registro.', 0, $exception));
            }

            throw $exception;
        }

        return redirect()->route('orders.mine')->with('success', 'Evidencia enviada; el pago está pendiente de revisión.');
    }
}
