@extends('layouts.admin')
@section('title', 'Agregar comida — Sazón')
@section('admin-content')
<div class="admin-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> NUEVO PRODUCTO</span><h1>Agregar comida</h1><p>El precio debe ser mayor que cero; la oferta, si aplica, menor que el precio normal.</p></div><a class="button button-outline" href="{{ route('admin.foods.index') }}">← Volver al menú</a></div>
<section class="panel-card form-panel"><form method="POST" action="{{ route('admin.foods.store') }}" enctype="multipart/form-data" class="record-form">@csrf
    <label>Nombre del plato<input name="name" value="{{ old('name') }}" required maxlength="120" placeholder="Ej. Lomo saltado"></label>
    <label>Categoría<select name="category" required><option value="">Selecciona una categoría</option>@foreach(\App\Models\Food::CATEGORIES as $category)<option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>@endforeach</select></label>
    <label>Precio regular<input name="price" type="number" step="0.01" min="0.01" max="999999" value="{{ old('price') }}" required placeholder="0.00"></label>
    <label>Precio de oferta <span class="optional">(opcional, debe ser menor al regular)</span><input name="offer_price" type="number" step="0.01" min="0.01" max="999999" value="{{ old('offer_price') }}" placeholder="0.00"></label>
    <label class="record-form-wide">Descripción<textarea name="description" rows="4" required maxlength="2000" placeholder="Describe los ingredientes y preparación.">{{ old('description') }}</textarea></label>
    <label>Imagen <span class="optional">(JPG, PNG o WebP; máximo 4 MB)</span><input name="image" type="file" accept=".jpg,.jpeg,.png,.webp" ></label>
    <div class="record-form-checks"><label class="check-label"><input type="checkbox" name="is_available" value="1" @checked(old('is_available', true))> Disponible para pedir</label><label class="check-label"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured'))> Destacar en el menú</label></div>
    <div class="record-form-actions"><a class="button button-outline" href="{{ route('admin.foods.index') }}">Cancelar</a><button class="button button-primary" type="submit">Crear comida</button></div>
</form></section>
@endsection
