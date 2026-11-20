@extends('layouts.admin')
@section('title', 'Panel de gestión — Sazón')
@section('admin-content')
@php $maxSales = collect($week)->max('total'); $chartScale = max(1, $maxSales); $statusLabels = ['pending' => 'Pendiente', 'confirmed' => 'Confirmado', 'preparing' => 'Preparando', 'on_the_way' => 'En camino', 'delivered' => 'Entregado', 'cancelled' => 'Cancelado']; $paymentLabels = ['cash' => 'Efectivo', 'card' => 'Tarjeta', 'transfer' => 'Transferencia', 'yape' => 'Yape', 'plin' => 'Plin']; @endphp
<div class="admin-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> CENTRO DE CONTROL</span><h1>Hola, {{ auth()->user()->name }} 👋</h1><p>Aquí tienes lo que está pasando hoy en Sazón.</p></div><a class="button button-primary" href="{{ route('admin.foods.index') }}">＋ Agregar comida</a></div>
<div class="stats-grid">
    <article class="stat-card"><span class="stat-icon stat-orange">▤</span><small>Pedidos de hoy</small><strong>{{ $stats['today_orders'] }}</strong><span class="stat-foot">Pedidos registrados hoy</span></article>
    <article class="stat-card"><span class="stat-icon stat-purple">◷</span><small>Por preparar</small><strong>{{ $stats['pending_orders'] }}</strong><a class="stat-foot stat-link" href="{{ route('admin.orders') }}">Ver pedidos →</a></article>
    <article class="stat-card"><span class="stat-icon stat-green">♙</span><small>Clientes activos</small><strong>{{ number_format($stats['customers']) }}</strong><a class="stat-foot stat-link" href="{{ route('admin.customers.index') }}">Ver clientes →</a></article>
    <article class="stat-card"><span class="stat-icon stat-yellow">$</span><small>Ventas registradas</small><strong>${{ number_format($stats['revenue'], 2) }}</strong><span class="stat-foot">Pagos aprobados</span></article>
</div>
<div class="admin-grid-main">
    <section class="panel-card"><div class="panel-heading"><div><h2>Ventas de la semana</h2><p>Ingresos de los últimos 7 días</p></div><a class="subtle-link" href="{{ route('admin.reports') }}">Ver reportes →</a></div>
        <div class="chart-area"><div class="chart-y"><span>${{ number_format($maxSales, 0) }}</span><span>${{ number_format($maxSales / 2, 0) }}</span><span>$0</span></div><div class="chart-bars">@foreach($week as $day)<div class="chart-column"><div class="bar-track"><span class="bar-fill" style="height:{{ max(5, ($day['total'] / $chartScale) * 100) }}%"></span></div><small>{{ $day['label'] }}</small></div>@endforeach</div></div>
    </section>
    <section class="panel-card"><div class="panel-heading"><div><h2>Reservas pendientes</h2><p>Próximas solicitudes</p></div><a class="subtle-link" href="{{ route('admin.reservations') }}">Ver todas →</a></div>
        @forelse($reservations as $reservation)
            <div class="compact-row"><div class="avatar-circle">{{ mb_substr($reservation->user->name, 0, 1) }}</div><div class="compact-info"><strong>{{ $reservation->user->name }}</strong><small>{{ $reservation->reservation_date->format('d/m') }} · {{ \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A') }}</small></div><span class="guest-pill">{{ $reservation->guests }} pers.</span></div>
        @empty
            <div class="small-empty">No hay reservas pendientes.</div>
        @endforelse
    </section>
</div>
<section class="panel-card table-panel"><div class="panel-heading"><div><h2>Pedidos recientes</h2><p>Seguimiento de los pedidos más nuevos</p></div><a class="subtle-link" href="{{ route('admin.orders') }}">Ver todos →</a></div>
    <div class="table-scroll"><table><thead><tr><th>Pedido</th><th>Cliente</th><th>Fecha</th><th>Método de pago</th><th>Total</th><th>Estado</th></tr></thead><tbody>
    @forelse($recentOrders as $order)<tr><td class="table-strong">{{ $order->order_number }}</td><td>{{ $order->user->name }}</td><td>{{ $order->created_at->format('d/m · H:i') }}</td><td>{{ $paymentLabels[$order->payment_method] ?? $order->payment_method }}</td><td class="table-strong">${{ number_format($order->total, 2) }}</td><td><span class="status status-{{ $order->status }}">{{ $statusLabels[$order->status] }}</span></td></tr>
    @empty<tr><td colspan="6" class="table-empty">Aún no hay pedidos. ¡El primero está por llegar!</td></tr>@endforelse
    </tbody></table></div>
</section>
@endsection
