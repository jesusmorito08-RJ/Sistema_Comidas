@extends('layouts.admin-auth')
@section('title', 'Acceso administrativo — Sazón')
@section('content')
<section class="admin-login-card">
    <span class="eyebrow"><span class="eyebrow-line"></span> ACCESO RESTRINGIDO</span>
    <h1>Administración</h1>
    <p>Ingresa con la cuenta del equipo para gestionar el restaurante.</p>
    <form method="POST" action="{{ route('admin.login.store') }}" class="form-stack">
        @csrf
        <label>Correo administrativo<input type="email" name="email" value="{{ old('email') }}" placeholder="admin@restaurante.com" required autocomplete="username" autofocus></label>
        <label>Contraseña<input type="password" name="password" placeholder="Tu contraseña" required autocomplete="current-password"></label>
        <label class="check-label"><input type="checkbox" name="remember"> Recordarme</label>
        <button class="button button-primary button-full" type="submit">Entrar al panel <span>→</span></button>
    </form>
    <p class="auth-switch">¿Buscas hacer un pedido? <a href="{{ route('login') }}">Ingresa como cliente</a></p>
</section>
@endsection
