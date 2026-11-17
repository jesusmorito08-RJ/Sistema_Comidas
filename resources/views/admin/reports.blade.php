@extends('layouts.admin')
@section('title', 'Reportes — Sazón')
@section('admin-content')
@php $maxSales = collect($week)->max('total'); $chartScale = max(1, $maxSales); @endphp
<div class="admin-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> NÚMEROS CLAROS</span><h1>Reportes</h1><p>Un resumen simple para tomar mejores decisiones.</p></div></div>
<div class="stats-grid report-stats">
    <article class="stat-card"><span class="stat-icon stat-orange">▤</span><small>Pedidos activos</small><strong>{{ number_format($summary['total_orders']) }}</strong><span class="stat-foot">Excluye cancelados y archivados</span></article>
    <article class="stat-card"><span class="stat-icon stat-green">$</span><small>Ventas registradas</small><strong>${{ number_format($summary['total_sales'], 2) }}</strong><span class="stat-foot">Pedidos no cancelados</span></article>
    <article class="stat-card"><span class="stat-icon stat-purple">♨</span><small>Comidas activas</small><strong>{{ $summary['food_count'] }}</strong><a class="stat-foot stat-link" href="{{ route('admin.foods.index') }}">Gestionar menú →</a></article>
    <article class="stat-card"><span class="stat-icon stat-yellow">◷</span><small>Reservas registradas</small><strong>{{ $summary['reservations'] }}</strong><a class="stat-foot stat-link" href="{{ route('admin.reservations') }}">Ver reservas →</a></article>
</div>
<section class="panel-card report-chart"><div class="panel-heading"><div><h2>Ventas por día</h2><p>Últimos 7 días, según la fecha de creación de pedidos</p></div></div><div class="chart-area chart-large"><div class="chart-y"><span>${{ number_format($maxSales, 0) }}</span><span>${{ number_format($maxSales / 2, 0) }}</span><span>$0</span></div><div class="chart-bars">@foreach($week as $day)<div class="chart-column"><strong class="chart-value">${{ number_format($day['total'], 0) }}</strong><div class="bar-track"><span class="bar-fill" style="height:{{ max(5, ($day['total'] / $chartScale) * 100) }}%"></span></div><small>{{ $day['date'] }}</small></div>@endforeach</div></div></section>
<div class="report-columns"><section class="panel-card"><div class="panel-heading"><div><h2>Métodos de pago</h2><p>Distribución de los pedidos activos</p></div></div>
    @php $paymentNames = ['cash'=>'Efectivo','card'=>'Tarjeta','transfer'=>'Transferencia']; @endphp
    @forelse($paymentTotals as $method=>$row)<div class="report-row"><div><strong>{{ $paymentNames[$method] ?? $method }}</strong><small>{{ $row->count }} pedidos</small></div><strong>${{ number_format($row->total, 2) }}</strong></div>@empty<p class="small-empty">Aún no hay pagos registrados.</p>@endforelse
</section><section class="panel-card"><div class="panel-heading"><div><h2>Más pedidos</h2><p>Productos con más unidades vendidas</p></div></div>
    @forelse($topFoods as $food)<div class="report-row"><div><strong>{{ $food->food_name }}</strong><small>{{ $food->quantity }} unidades</small></div><strong>${{ number_format($food->revenue, 2) }}</strong></div>@empty<p class="small-empty">Aún no hay productos vendidos.</p>@endforelse
</section></div>
@endsection
