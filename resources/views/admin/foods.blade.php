@extends('layouts.admin')
@section('title', 'Menú y comidas — Sazón')
@section('admin-content')
<div class="admin-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> CATÁLOGO</span><h1>Menú y comidas</h1><p>Administra platos, precios, fotos y ofertas.</p></div><div class="admin-heading-actions">@if(!request()->boolean('archived'))<a class="button button-primary" href="{{ route('admin.foods.create') }}">＋ Agregar comida</a>@endif<a class="button button-outline" href="{{ route('admin.foods.index', ['archived' => request()->boolean('archived') ? null : 1]) }}">{{ request()->boolean('archived') ? '← Ver menú activo' : 'Ver archivadas' }}</a></div></div>
<section class="panel-card table-panel"><div class="panel-heading"><div><h2>{{ request()->boolean('archived') ? 'Comidas archivadas' : 'Comidas del menú' }}</h2><p>{{ $foods->total() }} {{ $foods->total() === 1 ? 'plato' : 'platos' }} en esta vista</p></div></div>
    <div class="food-admin-list">
        @forelse($foods as $food)
            @php $image = $food->image_path ? (filter_var($food->image_path, FILTER_VALIDATE_URL) ? $food->image_path : \Illuminate\Support\Facades\Storage::disk('public')->url($food->image_path)) : null; @endphp
            <article class="food-admin-row">
                <div class="admin-food-thumb" @if($image) style="background-image:url('{{ $image }}')" @endif></div>
                <div class="admin-food-name"><strong>{{ $food->name }}</strong><small>{{ $food->category }} · {{ $food->is_featured ? 'Favorito' : 'Menú' }}</small></div>
                <div class="admin-food-price">@if($food->offer_price)<del>${{ number_format($food->price, 2) }}</del> @endif<strong>${{ number_format($food->offer_price ?? $food->price, 2) }}</strong></div>
                @if(request()->boolean('archived'))
                    <form method="POST" action="{{ route('admin.foods.restore', $food->id) }}">@csrf<button class="mini-action" type="submit">Restaurar</button></form>
                @else
                    <span class="availability {{ $food->is_available ? 'available' : 'unavailable' }}">{{ $food->is_available ? 'Disponible' : 'Pausado' }}</span>
                    <a class="mini-action" href="{{ route('admin.foods.edit', $food) }}">Editar</a>
                    <form method="POST" action="{{ route('admin.foods.destroy', $food) }}">@csrf @method('DELETE')<button class="remove-link" type="submit" onclick="return confirm('¿Archivar esta comida? Se conserva en el historial de pedidos.')">Archivar</button></form>
                @endif
            </article>
        @empty
            <div class="small-empty">No hay comidas en esta vista.</div>
        @endforelse
    </div>
</section>
<div class="pagination-wrap">{{ $foods->links() }}</div>
@endsection
