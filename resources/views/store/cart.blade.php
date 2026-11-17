@extends('layouts.app')
@section('title', 'Tu carrito — Sazón')
@section('content')
<section class="section page-section"><div class="container">
    <div class="page-heading"><span class="eyebrow"><span class="eyebrow-line"></span> UN BUEN PEDIDO EMPIEZA AQUÍ</span><h1>Tu carrito</h1><p>Revisa tus favoritos antes de confirmar.</p></div>
    @if(count($cart))
        @php
            $total = collect($cart)->sum('subtotal');
            $itemCount = collect($cart)->sum('quantity');
        @endphp
        <div class="cart-layout">
            <div class="cart-items">
                @foreach($cart as $line)
                    @php
                        $food = $line['food'];
                        $image = $food->image_path
                            ? (filter_var($food->image_path, FILTER_VALIDATE_URL) ? $food->image_path : \Illuminate\Support\Facades\Storage::disk('public')->url($food->image_path))
                            : null;
                    @endphp
                    <article class="cart-item">
                        <div class="cart-thumb" @if($image) role="img" aria-label="Imagen de {{ $food->name }}" @endif>
                            @if($image)
                                <img src="{{ $image }}" alt="{{ $food->name }}">
                            @else
                                <span aria-hidden="true">🍽️</span>
                            @endif
                        </div>
                        <div class="cart-item-main">
                            <div class="cart-product-info">
                                <h3>{{ $food->name }}</h3>
                                <span class="cart-unit-price">${{ number_format($food->offer_price ?? $food->price, 2) }} c/u</span>
                            </div>
                            <div class="cart-item-actions">
                                <div class="cart-controls">
                                    <span class="quantity-label">Cantidad</span>
                                    <div class="quantity-stepper" aria-label="Cambiar cantidad de {{ $food->name }}">
                                        <form method="POST" action="{{ route('cart.update', $food) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="quantity" value="{{ max(1, $line['quantity'] - 1) }}">
                                            <button class="quantity-button" type="submit" aria-label="Disminuir cantidad de {{ $food->name }}" @disabled($line['quantity'] <= 1)>−</button>
                                        </form>
                                        <span class="quantity-value" aria-label="Cantidad actual">{{ $line['quantity'] }}</span>
                                        <form method="POST" action="{{ route('cart.update', $food) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="quantity" value="{{ min(20, $line['quantity'] + 1) }}">
                                            <button class="quantity-button" type="submit" aria-label="Aumentar cantidad de {{ $food->name }}" @disabled($line['quantity'] >= 20)>+</button>
                                        </form>
                                    </div>
                                    <form class="quantity-edit-form" method="POST" action="{{ route('cart.update', $food) }}">
                                        @csrf
                                        @method('PATCH')
                                        <label class="sr-only" for="quantity-{{ $food->id }}">Editar cantidad de {{ $food->name }}</label>
                                        <input id="quantity-{{ $food->id }}" type="number" name="quantity" min="1" max="20" value="{{ $line['quantity'] }}">
                                        <button class="mini-submit" type="submit">Actualizar</button>
                                    </form>
                                </div>
                                <form class="cart-remove-form" method="POST" action="{{ route('cart.remove', $food) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="remove-link" type="submit"><span aria-hidden="true">×</span> Quitar</button>
                                </form>
                            </div>
                            <div class="cart-line-total"><span>Subtotal</span><strong>${{ number_format($line['subtotal'], 2) }}</strong></div>
                        </div>
                    </article>
                @endforeach
            </div>
            <aside class="checkout-card">
                <div class="checkout-heading">
                    <span class="eyebrow">CASI LISTO</span>
                    <h2>Resumen del pedido</h2>
                </div>
                <div class="summary-row"><span>Productos ({{ $itemCount }})</span><strong>${{ number_format($total, 2) }}</strong></div>
                <div class="summary-row"><span>Entrega</span><span class="free-label">Por confirmar</span></div>
                <div class="summary-total"><span>Total del pedido</span><strong>${{ number_format($total, 2) }}</strong></div>
                <p class="delivery-note">El costo de entrega se confirmará al coordinar tu pedido.</p>
                @auth
                    <form method="POST" action="{{ route('checkout') }}" class="form-stack checkout-form">
                        @csrf
                        <label>Dirección de entrega<input name="delivery_address" value="{{ old('delivery_address', auth()->user()->address) }}" required maxlength="255" placeholder="Calle, número, ciudad"></label>
                        <label>Método de pago<select name="payment_method" required><option value="cash">Efectivo al recibir</option><option value="card">Tarjeta</option><option value="transfer">Transferencia</option></select></label>
                        <label>Nota para la cocina <span class="optional">(opcional)</span><textarea name="notes" rows="2" placeholder="Indicaciones especiales"></textarea></label>
                        <button class="button button-primary button-full" type="submit">Confirmar pedido <span>→</span></button>
                    </form>
                @else
                    <p class="checkout-hint">Ingresa o crea una cuenta para confirmar tu pedido y guardar tus datos.</p>
                    <a class="button button-primary button-full" href="{{ route('login') }}">Ingresar para continuar <span>→</span></a>
                @endauth
            </aside>
        </div>
    @else
        <div class="empty-state"><span>🛍️</span><h2>Tu carrito está esperando algo rico</h2><p>Explora nuestro menú y agrega tus favoritos.</p><a class="button button-primary" href="{{ route('home') }}#menu">Explorar el menú <span>→</span></a></div>
    @endif
</div></section>
@endsection
