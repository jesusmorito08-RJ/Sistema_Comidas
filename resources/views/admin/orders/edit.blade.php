@extends('layouts.admin')
@section('title', 'Editar pedido — Sazón')
@section('admin-content')
@php
    $transitions = [
        'pending' => ['confirmed' => 'Confirmado', 'cancelled' => 'Cancelado'],
        'confirmed' => ['preparing' => 'En preparación', 'cancelled' => 'Cancelado'],
        'preparing' => ['on_the_way' => 'En camino', 'cancelled' => 'Cancelado'],
        'on_the_way' => ['delivered' => 'Entregado'],
        'delivered' => [],
        'cancelled' => [],
    ];
    $statusLabels = ['pending' => 'Pendiente', 'confirmed' => 'Confirmado', 'preparing' => 'En preparación', 'on_the_way' => 'En camino', 'delivered' => 'Entregado', 'cancelled' => 'Cancelado'];
    $availableStatuses = [$order->status => $statusLabels[$order->status]] + ($transitions[$order->status] ?? []);
@endphp
<div class="admin-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> {{ $order->order_number }}</span><h1>Editar pedido</h1><p>El total y los artículos quedan fijos para conservar el precio del momento de compra.</p></div><a class="button button-outline" href="{{ route('admin.orders') }}">← Volver a pedidos</a></div>
<section class="panel-card form-panel">
    <div class="admin-order-items">@foreach($order->items as $item)<span>{{ $item->quantity }} × {{ $item->food_name }} <small>${{ number_format($item->subtotal, 2) }}</small></span>@endforeach</div>
    <div class="order-edit-total"><span>Total del pedido</span><strong>${{ number_format($order->total, 2) }}</strong></div>
    <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="record-form">@csrf @method('PATCH')
        <label>Estado<select name="status" required>@foreach($availableStatuses as $key=>$label)<option value="{{ $key }}" @selected(old('status', $order->status) === $key)>{{ $label }}</option>@endforeach</select></label>
        <label>Estado del pago<select name="payment_status" required><option value="pending" @selected(old('payment_status', $order->payment_status) === 'pending')>Pendiente</option><option value="paid" @selected(old('payment_status', $order->payment_status) === 'paid')>Pagado</option></select></label>
        <label class="record-form-wide">Dirección de entrega<input name="delivery_address" value="{{ old('delivery_address', $order->delivery_address) }}" required maxlength="255"></label>
        <label class="record-form-wide">Notas para la entrega<textarea name="notes" rows="3" maxlength="1000">{{ old('notes', $order->notes) }}</textarea></label>
        <p class="form-business-rule record-form-wide">Reglas: el pedido avanza por etapas; no se puede reabrir uno entregado o cancelado, revertir un pago ni cancelar un pedido pagado. La dirección solo puede cambiar antes de iniciar la preparación.</p>
        <div class="record-form-actions"><a class="button button-outline" href="{{ route('admin.orders') }}">Cancelar</a><button class="button button-primary" type="submit">Guardar pedido</button></div>
    </form>
</section>
@endsection
