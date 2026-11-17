@extends('layouts.app')
@section('title', 'Mis pedidos — Sazón')
@section('content')
<section class="section page-section"><div class="container">
    <div class="page-heading"><span class="eyebrow"><span class="eyebrow-line"></span> TU HISTORIAL</span><h1>Mis pedidos</h1><p>Todos tus antojos, en un solo lugar.</p></div>
    @forelse($orders as $order)
        @php $statusLabels = ['pending' => 'Pendiente', 'confirmed' => 'Confirmado', 'preparing' => 'En preparación', 'on_the_way' => 'En camino', 'delivered' => 'Entregado', 'cancelled' => 'Cancelado']; @endphp
        <article class="order-card"><div class="order-card-head"><div><span class="eyebrow">PEDIDO {{ $order->order_number }}</span><p>{{ $order->created_at->format('d/m/Y · H:i') }}</p></div><span class="status status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span></div><div class="order-lines">@foreach($order->items as $item)<div><span>{{ $item->quantity }} × {{ $item->food_name }}</span><span>${{ number_format($item->subtotal, 2) }}</span></div>@endforeach</div><div class="order-card-foot"><span>Pago: {{ ['cash' => 'Efectivo', 'card' => 'Tarjeta', 'transfer' => 'Transferencia'][$order->payment_method] ?? $order->payment_method }}</span><strong>${{ number_format($order->total, 2) }}</strong></div>
        @if($order->status === 'pending' && $order->payment_status === 'pending')
            <form method="POST" action="{{ route('orders.cancel', $order) }}" class="customer-record-action">@csrf @method('DELETE')<button class="remove-link" type="submit" onclick="return confirm('¿Cancelar este pedido pendiente?')">Cancelar pedido</button></form>
        @endif
        </article>
    @empty
        <div class="empty-state"><span>🍲</span><h2>Aún no has hecho tu primer pedido</h2><p>Hay algo delicioso esperándote.</p><a class="button button-primary" href="{{ route('home') }}#menu">Ver el menú <span>→</span></a></div>
    @endforelse
</div></section>
@endsection
