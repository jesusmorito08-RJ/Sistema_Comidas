@extends('layouts.app')

@section('title', 'Sazón — comida que llega al corazón')

@section('content')
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow"><span class="eyebrow-line"></span> SABOR CASERO, A TU PUERTA</span>
            <h1>Tu próxima comida<br>favorita <em>está aquí.</em></h1>
            <p>Recetas llenas de sabor, ingredientes frescos y ese toque casero que convierte cualquier día en uno especial.</p>
            <div class="hero-actions"><a class="button button-primary" href="#menu">Explorar el menú <span>↘</span></a><a class="text-link" href="{{ route('reservations.index') }}">¿Prefieres reservar? <span>→</span></a></div>
            <div class="hero-proof"><div class="avatar-stack"><span>M</span><span>A</span><span>J</span></div><div><strong>4.9/5</strong><small>Más de 1,200 clientes felices</small></div><span class="proof-star">★★★★★</span></div>
        </div>
        <div class="hero-visual">
            <div class="hero-image"></div>
            <div class="hero-sticker"><span>✦</span><strong>Fresco<br>y delicioso</strong><small>hecho hoy, para ti</small></div>
            <div class="hero-note"><span class="note-icon">↗</span><span><strong>Entrega rápida</strong><small>Tu cocina está a minutos</small></span></div>
        </div>
    </div>
</section>
<section class="benefits">
    <div class="container benefits-grid">
        <div><span class="benefit-icon">✿</span><span><strong>Ingredientes frescos</strong><small>Elegidos cada día</small></span></div>
        <div><span class="benefit-icon">⌁</span><span><strong>Entrega cuidadosa</strong><small>Caliente hasta tu puerta</small></span></div>
        <div><span class="benefit-icon">♡</span><span><strong>Hecho con cariño</strong><small>Como en casa</small></span></div>
        <div><span class="benefit-icon">✦</span><span><strong>Pago a tu manera</strong><small>Efectivo, tarjeta o transferencia</small></span></div>
    </div>
</section>
<section class="section menu-section" id="menu">
    <div class="container">
        <div class="section-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> SABORES DEL PERÚ Y MÁS</span><h2>Un menú para cada antojo</h2>        <p>Explora platos criollos, opciones a la carta, entradas, postres y más.</p></div><span class="menu-count">{{ $foods->count() }} opciones</span></div>
        @if($categories->isNotEmpty())
            <nav class="category-filters" aria-label="Filtrar comidas por categoría">
                <a class="{{ $selectedCategory === null ? 'active' : '' }}" href="{{ route('foods.index') }}#menu" @if($selectedCategory === null) aria-current="page" @endif>Todos</a>
                @foreach($categories as $category)
                    <a class="{{ $selectedCategory === $category ? 'active' : '' }}" href="{{ route('foods.index', ['category' => $category]) }}#menu" @if($selectedCategory === $category) aria-current="page" @endif>{{ $category }}</a>
                @endforeach
            </nav>
        @endif
        @if($foods->isEmpty())
            <div class="empty-state"><span>🍽️</span><h3>Estamos preparando el menú</h3><p>Muy pronto encontrarás aquí algo delicioso.</p></div>
        @else
            <div class="food-grid">
                @foreach($foods as $food)
                    @php $image = $food->image_path ? (filter_var($food->image_path, FILTER_VALIDATE_URL) ? $food->image_path : \Illuminate\Support\Facades\Storage::disk('public')->url($food->image_path)) : null; @endphp
                    <article class="food-card">
                        <div class="food-photo" @if($image) style="background-image:url('{{ $image }}')" @endif>
                            @if($food->offer_price)<span class="food-tag">OFERTA</span>@elseif($food->is_featured)<span class="food-tag food-tag-soft">FAVORITO</span>@endif
                            <span class="food-category">{{ $food->category }}</span>
                        </div>
                        <div class="food-info"><div class="food-title-row"><h3>{{ $food->name }}</h3><span class="food-rating">★ 4.9</span></div><p>{{ $food->description }}</p><div class="food-bottom"><div class="price">@if($food->offer_price)<del>${{ number_format($food->price, 2) }}</del>@endif <strong>${{ number_format($food->offer_price ?? $food->price, 2) }}</strong></div><form method="POST" action="{{ route('cart.add', $food) }}">@csrf<button class="add-button" type="submit" aria-label="Agregar {{ $food->name }} al carrito">+</button></form></div></div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
<section class="offer-banner"><div class="container offer-content"><span class="offer-spark">✳</span><div><span class="eyebrow">UN PEQUEÑO DETALLE PARA TI</span><h2>Tu antojo de hoy merece algo especial.</h2><p>Descubre nuestras ofertas y encuentra tu nuevo favorito.</p></div><a class="button button-white" href="#menu">Ver el menú <span>→</span></a></div></section>
<section class="section reservation-cta"><div class="container reservation-card"><div><span class="eyebrow"><span class="eyebrow-line"></span> UN MOMENTO PARA COMPARTIR</span><h2>¿Una ocasión especial?</h2><p>Reserva tu horario y déjanos hacer que el momento sea aún más rico.</p></div><a class="button button-primary" href="{{ route('reservations.index') }}">Hacer una reserva <span>→</span></a></div></section>
@endsection
