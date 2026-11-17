@extends('layouts.admin')
@section('title', 'Editar comida — Sazón')
@section('admin-content')
<div class="admin-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> EDITAR PRODUCTO</span><h1>{{ $food->name }}</h1><p>Los cambios de precio aplican a pedidos nuevos; el historial conserva el precio comprado.</p></div><a class="button button-outline" href="{{ route('admin.foods.index') }}">← Volver al menú</a></div>
<section class="panel-card form-panel"><form method="POST" action="{{ route('admin.foods.update', $food) }}" enctype="multipart/form-data" class="record-form">@csrf @method('PUT')
    <label>Nombre del plato<input name="name" value="{{ old('name', $food->name) }}" required maxlength="120"></label>
    <label>Categoría<select name="category" required>@foreach(\App\Models\Food::CATEGORIES as $category)<option value="{{ $category }}" @selected(old('category', $food->category) === $category)>{{ $category }}</option>@endforeach</select></label>
    <label>Precio regular<input name="price" type="number" step="0.01" min="0.01" max="999999" value="{{ old('price', $food->price) }}" required></label>
    <label>Precio de oferta <span class="optional">(opcional, menor al regular)</span><input name="offer_price" type="number" step="0.01" min="0.01" max="999999" value="{{ old('offer_price', $food->offer_price) }}"></label>
    <label class="record-form-wide">Descripción<textarea name="description" rows="4" required maxlength="2000">{{ old('description', $food->description) }}</textarea></label>
    <label>Nueva imagen <span class="optional">(opcional; JPG, PNG o WebP, máximo 4 MB)</span><input name="image" type="file" accept=".jpg,.jpeg,.png,.webp"></label>
    <div class="record-form-checks"><label class="check-label"><input type="checkbox" name="is_available" value="1" @checked(old('is_available', $food->is_available))> Disponible para pedir</label><label class="check-label"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $food->is_featured))> Destacar en el menú</label></div>
    <div class="record-form-actions"><a class="button button-outline" href="{{ route('admin.foods.index') }}">Cancelar</a><button class="button button-primary" type="submit">Guardar cambios</button></div>
</form>
@if($food->image_path)
    <div class="record-current-image"><strong>Imagen actual</strong><img src="{{ filter_var($food->image_path, FILTER_VALIDATE_URL) ? $food->image_path : \Illuminate\Support\Facades\Storage::disk('public')->url($food->image_path) }}" alt="Imagen de {{ $food->name }}">
    </div>
@endif
</section>
@endsection
