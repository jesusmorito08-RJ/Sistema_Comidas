@extends('layouts.admin')
@section('title', 'Evidencias de pago — Sazón')
@section('admin-content')
<div class="admin-heading">
    <div>
        <span class="eyebrow"><span class="eyebrow-line"></span> REVISIÓN DE PAGOS</span>
        <h1>Evidencias pendientes</h1>
        <p>Verifica la recepción del dinero en la cuenta del negocio antes de aprobar.</p>
    </div>
</div>
<div class="admin-order-list">
    @forelse($paymentProofs as $proof)
        @php
            $order = $proof->order;
            $paymentLabels = ['yape' => 'Yape', 'plin' => 'Plin'];
        @endphp
        <article class="panel-card admin-order-card">
            <div class="admin-order-top">
                <div>
                    <span class="eyebrow">PEDIDO {{ $order->order_number }}</span>
                    <h2>{{ $order->user->name }}</h2>
                    <p>{{ $proof->submitted_at->format('d/m/Y · H:i') }} · {{ $paymentLabels[$order->payment_method] ?? $order->payment_method }}</p>
                </div>
                <div class="admin-order-total">
                    <span>Importe del pedido</span>
                    <strong>${{ number_format($order->total, 2) }}</strong>
                </div>
            </div>

            <img class="payment-proof-image" src="{{ route('admin.payment-proofs.image', $proof) }}" alt="Evidencia del pedido {{ $order->order_number }}">
            <p class="form-business-rule">La imagen por sí sola no confirma el pago. Comprueba en la cuenta del negocio que el importe haya sido recibido.</p>

            <div class="payment-proof-actions">
                <form method="POST" action="{{ route('admin.payment-proofs.approve', $proof) }}" class="form-stack">
                    @csrf
                    @method('PATCH')
                    <label class="checkbox-label">
                        <input type="checkbox" name="confirm_received" value="1" required>
                        Confirmo que verifiqué la recepción real del dinero.
                    </label>
                    <button class="button button-primary" type="submit">Aprobar pago</button>
                </form>

                <form method="POST" action="{{ route('admin.payment-proofs.reject', $proof) }}" class="form-stack">
                    @csrf
                    @method('PATCH')
                    <label>
                        Motivo del rechazo
                        <textarea name="rejection_reason" rows="3" maxlength="2000" required>{{ old('rejection_reason') }}</textarea>
                    </label>
                    <button class="button button-outline" type="submit">Rechazar evidencia</button>
                </form>
            </div>
        </article>
    @empty
        <div class="panel-card small-empty">No hay evidencias pendientes de revisión.</div>
    @endforelse
</div>
<div class="pagination-wrap">{{ $paymentProofs->links() }}</div>
@endsection
