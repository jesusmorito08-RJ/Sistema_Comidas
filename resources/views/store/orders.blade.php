@extends('layouts.app')
@section('title', 'Mis pedidos — Sazón')
@section('content')
<section class="section page-section"><div class="container">
    <div class="page-heading"><span class="eyebrow"><span class="eyebrow-line"></span> TU HISTORIAL</span><h1>Mis pedidos</h1><p>Todos tus antojos, en un solo lugar.</p></div>
    @if($errors->has('evidence'))
        <p class="form-business-rule">{{ $errors->first('evidence') }}</p>
    @endif
    @forelse($orders as $order)
        @php
            $statusLabels = ['pending' => 'Pendiente', 'confirmed' => 'Confirmado', 'preparing' => 'En preparación', 'on_the_way' => 'En camino', 'delivered' => 'Entregado', 'cancelled' => 'Cancelado'];
            $paymentLabels = ['cash' => 'Efectivo', 'card' => 'Tarjeta', 'transfer' => 'Transferencia', 'yape' => 'Yape', 'plin' => 'Plin'];
            $proofStatusLabels = ['pending' => 'En revisión', 'approved' => 'Aprobada', 'rejected' => 'Rechazada'];
            $latestProof = $order->paymentProofs->first();
            $hasPendingOrApprovedProof = $order->paymentProofs->contains(
                fn ($proof) => in_array($proof->status, ['pending', 'approved'], true)
            );
            $canSubmitProof = in_array($order->payment_method, ['yape', 'plin'], true)
                && $order->status !== 'cancelled'
                && $order->payment_status === 'pending'
                && ! $hasPendingOrApprovedProof
                && ($latestProof === null || $latestProof->status === 'rejected');
        @endphp
        <article class="order-card">
            <div class="order-card-head">
                <div>
                    <span class="eyebrow">PEDIDO {{ $order->order_number }}</span>
                    <p>{{ $order->created_at->format('d/m/Y · H:i') }}</p>
                </div>
                <span class="status status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span>
            </div>
            <div class="order-lines">
                @foreach($order->items as $item)
                    <div><span>{{ $item->quantity }} × {{ $item->food_name }}</span><span>${{ number_format($item->subtotal, 2) }}</span></div>
                @endforeach
            </div>
            <div class="order-card-foot">
                <span>Pago: {{ $paymentLabels[$order->payment_method] ?? $order->payment_method }}</span>
                <strong>${{ number_format($order->total, 2) }}</strong>
            </div>

            @if($order->payment_status === 'paid' && $order->paymentProofs->contains('status', 'approved'))
                <div class="customer-record-action">
                    <a class="button button-outline" href="{{ route('orders.receipt.download', $order) }}">Descargar comprobante PDF</a>
                    <a class="mini-action" href="{{ route('orders.receipt.print', $order) }}" target="_blank" rel="noopener">Imprimir comprobante</a>
                </div>
            @endif

            @if($order->paymentProofs->isNotEmpty())
                <div class="order-payment-history">
                    <h3>Evidencias de pago</h3>
                    @foreach($order->paymentProofs as $proof)
                        <div class="order-payment-attempt">
                            <span>{{ $proof->submitted_at->format('d/m/Y · H:i') }}</span>
                            <strong>{{ $proofStatusLabels[$proof->status] ?? $proof->status }}</strong>
                            @if($proof->status === 'rejected' && $proof->rejection_reason)
                                <p>Motivo del rechazo: {{ $proof->rejection_reason }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if($canSubmitProof)
                <form method="POST" action="{{ route('orders.payment-proofs.store', $order) }}" enctype="multipart/form-data" class="form-stack">
                    @csrf
                    <label>
                        {{ $latestProof?->status === 'rejected' ? 'Reenviar evidencia de pago' : 'Adjuntar evidencia de pago' }}
                        <input type="file" name="evidence" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
                        <span class="optional">JPG, JPEG o PNG; máximo 5 MB.</span>
                    </label>
                    <button class="button button-primary" type="submit">
                        {{ $latestProof?->status === 'rejected' ? 'Reenviar evidencia' : 'Enviar evidencia' }}
                    </button>
                </form>
            @endif

            @if($order->status === 'pending' && $order->payment_status === 'pending')
                <form method="POST" action="{{ route('orders.cancel', $order) }}" class="customer-record-action">
                    @csrf
                    @method('DELETE')
                    <button class="remove-link" type="submit" onclick="return confirm('¿Cancelar este pedido pendiente?')">Cancelar pedido</button>
                </form>
            @endif
        </article>
    @empty
        <div class="empty-state"><span>🍲</span><h2>Aún no has hecho tu primer pedido</h2><p>Hay algo delicioso esperándote.</p><a class="button button-primary" href="{{ route('home') }}#menu">Ver el menú <span>→</span></a></div>
    @endforelse
</div></section>
@endsection
