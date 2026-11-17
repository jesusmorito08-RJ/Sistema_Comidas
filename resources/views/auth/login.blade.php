@extends('layouts.app')
@section('title', 'Ingresar — Sazón')
@section('content')
<section class="auth-section"><div class="auth-card"><span class="eyebrow"><span class="eyebrow-line"></span> ACCESO DE CLIENTE</span><h1>Bienvenido de vuelta</h1><p>Ingresa a tu cuenta para pedir comida, reservar y revisar tus pedidos.</p>
    <form method="POST" action="{{ route('login.store') }}" class="form-stack">@csrf
        <label>Correo electrónico<input type="email" name="email" value="{{ old('email') }}" placeholder="tu@correo.com" required autocomplete="email" autofocus></label>
        <label>Contraseña<input type="password" name="password" placeholder="Tu contraseña" required autocomplete="current-password"></label>
        <label class="check-label"><input type="checkbox" name="remember"> Recordarme</label>
        <button class="button button-primary button-full" type="submit">Ingresar <span>→</span></button>
    </form>
    <p class="auth-switch">¿Aún no tienes cuenta? <a href="{{ route('register') }}">Crea una aquí</a></p>
    <p class="auth-switch">¿Eres administrador? <a href="{{ route('admin.login') }}">Ingresa al acceso administrativo</a></p>
</div></section>
@endsection
