<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sazón — comida que llega al corazón')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=8">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="{{ route('home') }}"><span class="brand-mark">S</span><span>Sazón<span class="brand-dot">.</span><small>COMIDA HECHA CON AMOR</small></span></a>
        <button class="menu-toggle" type="button" aria-label="Abrir menú" onclick="document.body.classList.toggle('nav-open')">☰</button>
        <nav class="main-nav">
            <a href="{{ route('home') }}">Inicio</a>
            <a href="{{ route('foods.index') }}#menu">Menú</a>
            @auth
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}">Panel administrativo</a>
                @else
                    <a href="{{ route('customer.dashboard') }}">Mi cuenta</a>
                    <a href="{{ route('reservations.index') }}">Reservas</a>
                    <a href="{{ route('orders.mine') }}">Mis pedidos</a>
                @endif
            @else
                <a href="{{ route('login') }}">Acceso de clientes</a>
                <a href="{{ route('admin.login') }}">Acceso administrativo</a>
            @endauth
        </nav>
        <div class="nav-actions">
            @if(!auth()->check() || auth()->user()->role !== 'admin')
                <a class="cart-link" href="{{ route('cart.index') }}">Carrito <span class="cart-count">{{ collect(session('cart', []))->sum() }}</span></a>
            @endif
            @auth
                <span class="nav-user">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-logout" type="submit">Salir</button></form>
            @else
                <a class="nav-login" href="{{ route('login') }}">Ingresar como cliente</a>
            @endauth
        </div>
    </div>
</header>
<main>
    @if(session('success') || $errors->any())
        <div class="container flash-stack">
            @if(session('success'))<div class="flash flash-success">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="flash flash-error">{{ $errors->first() }}</div>@endif
        </div>
    @endif
    @yield('content')
</main>
<footer class="site-footer">
    <div class="container footer-row"><a class="brand brand-light" href="{{ route('home') }}"><span class="brand-mark">S</span><span>Sazón<span class="brand-dot">.</span><small>COMIDA HECHA CON AMOR</small></span></a><p>Buenos momentos, servidos a domicilio.</p><span>© {{ date('Y') }} Sazón</span></div>
</footer>
</body>
</html>
