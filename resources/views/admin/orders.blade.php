@extends('layouts.admin')
@section('title', 'Pedidos — Sazón')
@section('admin-content')
@php $statusLabels = ['pending'=>'Pendiente','confirmed'=>'Confirmado','preparing'=>'En preparación','on_the_way'=>'En camino','delivered'=>'Entregado','cancelled'=>'Cancelado']; $paymentLabels = ['cash'=>'Efectivo','card'=>'Tarjeta','transfer'=>'Transferencia','yape'=>'Yape','plin'=>'Plin']; @endphp
<div class="admin-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> SEGUIMIENTO</span><h1>Pedidos</h1><p>Revisa, actualiza o archiva los pedidos recibidos.</p></div><a class="button button-outline" href="{{ route('admin.orders', ['archived' => $archived ? null : 1]) }}">{{ $archived ? '← Pedidos activos' : 'Ver archivo' }}</a></div>
<div class="admin-order-list">
@forelse($orders as $order)
    <article class="panel-card admin-order-card"><div class="admin-order-top"><div><span class="eyebrow">{{ $order->order_number }}</span><h2>{{ $order->user->name }}</h2><p>{{ $order->created_at->format('d/m/Y · H:i') }} · {{ $order->delivery_address }}</p></div><div class="admin-order-total"><span>Total</span><strong>${{ number_format($order->total, 2) }}</strong></div></div>
        <div class="admin-order-items">@foreach($order->items as $item)<span>{{ $item->quantity }} × {{ $item->food_name }} <small>${{ number_format($item->subtotal, 2) }}</small></span>@endforeach</div>
        <div class="admin-order-bottom"><span>Estado: <span class="status status-{{ $order->status }}">{{ $statusLabels[$order->status] }}</span> · Pago: {{ $paymentLabels[$order->payment_method] ?? $order->payment_method }} · <span class="payment-state">{{ $order->payment_status === 'paid' ? 'Pagado' : 'Por cobrar' }}</span></span>
            @if(!$archived)
                @if($order->payment_status === 'paid' && $order->paymentProofs->contains('status', 'approved'))
                    <a class="mini-action" href="{{ route('admin.orders.receipt.download', $order) }}">Comprobante PDF</a>
                    <a class="mini-action" href="{{ route('admin.orders.receipt.print', $order) }}" target="_blank" rel="noopener">Imprimir</a>
                @endif
                <a class="mini-action" href="{{ route('admin.orders.edit', $order) }}">Editar pedido y estado</a>
                @if($order->status === 'cancelled' && $order->payment_status === 'pending')
                    <form method="POST" action="{{ route('admin.orders.archive', $order) }}">@csrf @method('DELETE')<button class="remove-link" type="submit" onclick="return confirm('¿Archivar este pedido cancelado?')">Archivar</button></form>
                @endif
            @else<form method="POST" action="{{ route('admin.orders.restore', $order->id) }}">@csrf<button class="mini-action" type="submit">Restaurar pedido</button></form>@endif
        </div>
    </article>
@empty
    <div class="panel-card small-empty">No hay pedidos {{ $archived ? 'archivados' : 'registrados' }}.</div>
@endforelse
</div>
<div class="pagination-wrap">{{ $orders->links() }}</div>
@endsection
