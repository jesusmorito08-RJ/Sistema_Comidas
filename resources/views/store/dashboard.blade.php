@extends('layouts.app')
@section('title', 'Mi cuenta — Sazón')
@section('content')
@php
    $orderStatuses = ['pending' => 'Pendiente', 'confirmed' => 'Confirmado', 'preparing' => 'En preparación', 'on_the_way' => 'En camino', 'delivered' => 'Entregado', 'cancelled' => 'Cancelado'];
    $reservationStatuses = ['pending' => 'Pendiente', 'confirmed' => 'Confirmada', 'cancelled' => 'Cancelada', 'completed' => 'Completada'];
@endphp
<section class="section page-section">
    <div class="container">
        <div class="page-heading"><span class="eyebrow"><span class="eyebrow-line"></span> TU ESPACIO DE CLIENTE</span><h1>Hola, {{ $customer->name }}</h1><p>Desde aquí puedes revisar tus pedidos y organizar tus próximas reservas.</p></div>
        <div class="customer-dashboard-actions">
            <a class="button button-primary" href="{{ route('foods.index') }}#menu">Ver el menú <span>→</span></a>
            <a class="button button-outline" href="{{ route('orders.mine') }}">Mis pedidos</a>
            <a class="button button-outline" href="{{ route('reservations.index') }}">Reservar una mesa</a>
        </div>
        <div class="customer-stats-grid">
            <article class="stat-card"><small>Pedidos realizados</small><strong>{{ number_format($stats['orders']) }}</strong></article>
            <article class="stat-card"><small>Pedidos en curso</small><strong>{{ number_format($stats['pending_orders']) }}</strong></article>
            <article class="stat-card"><small>Reservas activas</small><strong>{{ number_format($stats['reservations']) }}</strong></article>
        </div>
        <div class="customer-activity-grid">
            <section class="panel-card">
                <div class="panel-heading"><div><h2>Pedidos recientes</h2><p>El estado de tus compras.</p></div><a class="subtle-link" href="{{ route('orders.mine') }}">Ver todos →</a></div>
                @forelse($recentOrders as $order)
                    <a class="customer-activity-row" href="{{ route('orders.mine') }}">
                        <span><strong>{{ $order->order_number }}</strong><small>{{ $order->created_at->format('d/m/Y') }} · ${{ number_format($order->total, 2) }}</small></span>
                        <span class="status status-{{ $order->status }}">{{ $orderStatuses[$order->status] ?? $order->status }}</span>
                    </a>
                @empty
                    <p class="customer-empty">Todavía no tienes pedidos. <a href="{{ route('foods.index') }}#menu">Explora el menú.</a></p>
                @endforelse
            </section>
            <section class="panel-card">
                <div class="panel-heading"><div><h2>Tus reservas</h2><p>Próximas visitas al restaurante.</p></div><a class="subtle-link" href="{{ route('reservations.index') }}">Ver todas →</a></div>
                @forelse($recentReservations as $reservation)
                    <div class="customer-activity-row">
                        <span><strong>{{ $reservation->reservation_date->format('d/m/Y') }} · {{ \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A') }}</strong><small>{{ $reservation->guests }} {{ $reservation->guests === 1 ? 'persona' : 'personas' }}</small></span>
                        <span class="status status-{{ $reservation->status }}">{{ $reservationStatuses[$reservation->status] ?? $reservation->status }}</span>
                    </div>
                @empty
                    <p class="customer-empty">Aún no tienes reservas. <a href="{{ route('reservations.index') }}">Solicita una.</a></p>
                @endforelse
            </section>
        </div>
    </div>
</section>
@endsection
