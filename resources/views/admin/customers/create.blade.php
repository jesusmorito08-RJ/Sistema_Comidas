@extends('layouts.admin')
@section('title', 'Nuevo cliente — Sazón')
@section('admin-content')
<div class="admin-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> NUEVA CUENTA</span><h1>Crear cliente</h1><p>El cliente se registra con rol de cliente y una contraseña de al menos 8 caracteres.</p></div><a class="button button-outline" href="{{ route('admin.customers.index') }}">← Volver a clientes</a></div>
<section class="panel-card form-panel"><form method="POST" action="{{ route('admin.customers.store') }}" class="record-form">@csrf
    <label>Nombre completo<input name="name" value="{{ old('name') }}" required maxlength="120" autocomplete="name"></label>
    <label>Correo electrónico<input name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email"></label>
    <label>Teléfono<input name="phone" type="tel" value="{{ old('phone') }}" maxlength="30"></label>
    <label>Dirección de entrega<input name="address" value="{{ old('address') }}" maxlength="255"></label>
    <label>Contraseña temporal<input name="password" type="password" required minlength="8" autocomplete="new-password"></label>
    <label>Confirmar contraseña<input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></label>
    <div class="record-form-actions"><a class="button button-outline" href="{{ route('admin.customers.index') }}">Cancelar</a><button class="button button-primary" type="submit">Crear cliente</button></div>
</form></section>
@endsection
