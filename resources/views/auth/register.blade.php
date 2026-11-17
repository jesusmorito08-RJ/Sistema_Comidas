@extends('layouts.app')
@section('title', 'Crear cuenta — Sazón')
@section('content')
<section class="auth-section"><div class="auth-card"><span class="eyebrow"><span class="eyebrow-line"></span> UN BUEN SABOR EMPIEZA AQUÍ</span><h1>Crea tu cuenta</h1><p>Guarda tus datos y disfruta una experiencia más rápida.</p>
    <form method="POST" action="{{ route('register.store') }}" class="form-stack">@csrf
        <label>Nombre completo<input type="text" name="name" value="{{ old('name') }}" placeholder="¿Cómo te llamas?" required autocomplete="name"></label>
        <label>Correo electrónico<input type="email" name="email" value="{{ old('email') }}" placeholder="tu@correo.com" required autocomplete="email"></label>
        <label>Teléfono <span class="optional">(opcional)</span><input type="tel" name="phone" value="{{ old('phone') }}" placeholder="+00 000 000 000"></label>
        <div class="form-two"><label>Contraseña<input type="password" name="password" required autocomplete="new-password"></label><label>Confirmar contraseña<input type="password" name="password_confirmation" required autocomplete="new-password"></label></div>
        <button class="button button-primary button-full" type="submit">Crear mi cuenta <span>→</span></button>
    </form>
    <p class="auth-switch">¿Ya eres parte de Sazón? <a href="{{ route('login') }}">Ingresa aquí</a></p>
</div></section>
@endsection
