@extends('layouts.admin')
@section('title', 'Editar cliente — Sazón')
@section('admin-content')
<div class="admin-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> DATOS DEL CLIENTE</span><h1>Editar cliente</h1><p>Los pedidos ya realizados se conservan asociados a su cuenta.</p></div><a class="button button-outline" href="{{ route('admin.customers.index') }}">← Volver a clientes</a></div>
<section class="panel-card form-panel"><form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="record-form">@csrf @method('PUT')
    <label>Nombre completo<input name="name" value="{{ old('name', $customer->name) }}" required maxlength="120"></label>
    <label>Correo electrónico<input name="email" type="email" value="{{ old('email', $customer->email) }}" required maxlength="255"></label>
    <label>Teléfono<input name="phone" type="tel" value="{{ old('phone', $customer->phone) }}" maxlength="30"></label>
    <label>Dirección de entrega<input name="address" value="{{ old('address', $customer->address) }}" maxlength="255"></label>
    <div class="record-form-actions"><a class="button button-outline" href="{{ route('admin.customers.index') }}">Cancelar</a><button class="button button-primary" type="submit">Guardar cambios</button></div>
</form></section>
@endsection
