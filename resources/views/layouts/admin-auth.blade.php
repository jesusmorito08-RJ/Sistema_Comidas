<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Administración — Sazón')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=8">
</head>
<body class="admin-auth-body">
    <header class="admin-auth-header">
        <a class="brand" href="{{ route('home') }}"><span class="brand-mark">S</span><span>Sazón<span class="brand-dot">.</span><small>ADMINISTRACIÓN</small></span></a>
        <span class="admin-auth-badge">Panel privado</span>
    </header>
    <main class="admin-auth-main">
        @if($errors->any())
            <div class="flash flash-error admin-auth-error">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </main>
    <footer class="admin-auth-footer"><a href="{{ route('login') }}">← Volver al acceso de clientes</a></footer>
</body>
</html>
