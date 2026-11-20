<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentProof;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderReceiptController extends Controller
{
    public function downloadForCustomer(Request $request, Order $order): Response
    {
        $customerOrder = $this->customerOrder($this->authenticatedCustomer($request), $order);

        return $this->pdfResponse($customerOrder);
    }

    public function printForCustomer(Request $request, Order $order): Response
    {
        $customerOrder = $this->customerOrder($this->authenticatedCustomer($request), $order);

        return $this->printView($customerOrder);
    }

    public function downloadForAdmin(Order $order): Response
    {
        $order->loadMissing(['user', 'items', 'paymentProofs' => $this->approvedProofs()]);

        return $this->pdfResponse($order);
    }

    public function printForAdmin(Order $order): Response
    {
        $order->loadMissing(['user', 'items', 'paymentProofs' => $this->approvedProofs()]);

        return $this->printView($order);
    }

    private function customerOrder(User $customer, Order $order): Order
    {
        $customerOrder = $customer->orders()
            ->whereKey($order->getKey())
            ->with(['user', 'items', 'paymentProofs' => $this->approvedProofs()])
            ->firstOrFail();

        $this->approvedProof($customerOrder);

        return $customerOrder;
    }

    private function pdfResponse(Order $order): Response
    {
        $approvedProof = $this->approvedProof($order);
        $receiptNumber = $this->receiptNumber($order);

        $response = Pdf::setOption('isRemoteEnabled', false)->loadView('receipts.order', [
            'order' => $order,
            'approvedProof' => $approvedProof,
            'receiptNumber' => $receiptNumber,
            'businessName' => $this->businessName(),
            'pdfDownloadUrl' => $this->downloadRoute($order),
            'pdfDocument' => true,
        ])->setPaper('a4')->download('comprobante-'.$receiptNumber.'.pdf');
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    private function printView(Order $order): Response
    {
        $approvedProof = $this->approvedProof($order);

        return response()->view('receipts.order', [
            'order' => $order,
            'approvedProof' => $approvedProof,
            'receiptNumber' => $this->receiptNumber($order),
            'businessName' => $this->businessName(),
            'pdfDownloadUrl' => $this->downloadRoute($order),
            'pdfDocument' => false,
        ])->header('Cache-Control', 'private, no-store, max-age=0');
    }

    private function authenticatedCustomer(Request $request): User
    {
        $customer = $request->user();
        abort_unless($customer instanceof User && $customer->role === 'customer', 403);

        return $customer;
    }

    private function approvedProof(Order $order): PaymentProof
    {
        abort_unless($order->payment_status === 'paid', 404);

        $approvedProof = $order->paymentProofs
            ->firstWhere('status', PaymentProof::STATUS_APPROVED);

        abort_unless($approvedProof instanceof PaymentProof, 404);

        return $approvedProof;
    }

    private function approvedProofs(): \Closure
    {
        return fn (HasMany $query) => $query
            ->where('status', PaymentProof::STATUS_APPROVED)
            ->orderByDesc('reviewed_at')
            ->orderByDesc('id');
    }

    private function receiptNumber(Order $order): string
    {
        return 'RC-'.$order->order_number;
    }

    private function businessName(): string
    {
        return (string) (config('payments.business_name') ?: config('app.name'));
    }

    private function downloadRoute(Order $order): string
    {
        return request()->routeIs('admin.*')
            ? route('admin.orders.receipt.download', $order)
            : route('orders.receipt.download', $order);
    }
}
