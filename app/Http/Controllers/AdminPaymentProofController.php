<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentProof;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminPaymentProofController extends Controller
{
    public function index(): View
    {
        $paymentProofs = PaymentProof::query()
            ->with(['order.user'])
            ->where('status', PaymentProof::STATUS_PENDING)
            ->whereHas('order', function (Builder $query): void {
                $query->where('payment_status', 'pending')
                    ->where('status', '!=', 'cancelled');
            })
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->paginate(15);

        return view('admin.payment-proofs.index', compact('paymentProofs'));
    }

    public function image(PaymentProof $paymentProof): BinaryFileResponse
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($paymentProof->file_path), 404);

        $mimeType = $disk->mimeType($paymentProof->file_path);
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => null,
        };
        abort_unless($extension !== null, 404);

        $filePath = $disk->path($paymentProof->file_path);
        abort_unless(is_file($filePath), 404);

        $response = response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="evidence-'.$paymentProof->getKey().'.'.$extension.'"',
        ]);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    public function approve(Request $request, PaymentProof $paymentProof): RedirectResponse
    {
        $request->validate([
            'confirm_received' => ['required', 'accepted'],
        ]);

        return $this->review(
            $request,
            $paymentProof,
            PaymentProof::STATUS_APPROVED,
            null,
        );
    }

    public function reject(Request $request, PaymentProof $paymentProof): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        return $this->review(
            $request,
            $paymentProof,
            PaymentProof::STATUS_REJECTED,
            $validated['rejection_reason'],
        );
    }

    private function review(
        Request $request,
        PaymentProof $paymentProof,
        string $reviewStatus,
        ?string $rejectionReason,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $paymentProof, $reviewStatus, $rejectionReason): void {
            $lockedProof = PaymentProof::query()
                ->lockForUpdate()
                ->findOrFail($paymentProof->getKey());

            if ($lockedProof->status !== PaymentProof::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'payment_proof' => 'Esta evidencia ya fue revisada.',
                ]);
            }

            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($lockedProof->order_id);

            if ($lockedOrder->status === 'cancelled' || $lockedOrder->payment_status !== 'pending') {
                throw ValidationException::withMessages([
                    'payment_proof' => 'El pedido ya no admite revisión de evidencias.',
                ]);
            }

            if (! in_array($lockedOrder->payment_method, ['yape', 'plin'], true)) {
                throw ValidationException::withMessages([
                    'payment_proof' => 'El método de pago del pedido no admite evidencias Yape o Plin.',
                ]);
            }

            $latestProofId = $lockedOrder->paymentProofs()
                ->orderByDesc('submitted_at')
                ->orderByDesc('id')
                ->value('id');

            if ((int) $latestProofId !== (int) $lockedProof->getKey()) {
                throw ValidationException::withMessages([
                    'payment_proof' => 'Solo se puede revisar el intento de evidencia más reciente.',
                ]);
            }

            $hasOtherApprovedProof = $lockedOrder->paymentProofs()
                ->where('status', PaymentProof::STATUS_APPROVED)
                ->where('id', '!=', $lockedProof->getKey())
                ->exists();

            if ($hasOtherApprovedProof) {
                throw ValidationException::withMessages([
                    'payment_proof' => 'El pedido ya tiene otra evidencia aprobada.',
                ]);
            }

            $lockedProof->update([
                'status' => $reviewStatus,
                'rejection_reason' => $rejectionReason,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            if ($reviewStatus === PaymentProof::STATUS_APPROVED) {
                $lockedOrder->update(['payment_status' => 'paid']);
            }
        });

        $message = $reviewStatus === PaymentProof::STATUS_APPROVED
            ? 'Evidencia aprobada; el pago quedó registrado como pagado.'
            : 'Evidencia rechazada. El cliente puede enviar un nuevo intento.';

        return redirect()->route('admin.payment-proofs.index')->with('success', $message);
    }
}
