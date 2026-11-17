@extends('layouts.admin')
@section('title', 'Editar reserva — Sazón')
@section('admin-content')
<div class="admin-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> AGENDA</span><h1>Editar reserva</h1><p>Cliente: {{ $reservation->user->name }} · {{ $reservation->user->email }}</p></div><a class="button button-outline" href="{{ route('admin.reservations') }}">← Volver a reservas</a></div>
<section class="panel-card form-panel"><form method="POST" action="{{ route('admin.reservations.update', $reservation) }}" class="record-form">@csrf @method('PATCH')
    <input type="hidden" name="status" value="pending">
    <label>Fecha<input type="date" name="reservation_date" min="{{ now()->toDateString() }}" value="{{ old('reservation_date', $reservation->reservation_date->toDateString()) }}" required></label>
    <label>Hora<select name="reservation_time" required>@foreach(\App\Models\Reservation::AVAILABLE_TIMES as $time)<option value="{{ $time }}" @selected(old('reservation_time', substr((string) $reservation->reservation_time, 0, 5)) === $time)>{{ \Carbon\Carbon::createFromFormat('H:i', $time)->format('g:i A') }}</option>@endforeach</select></label>
    <label>Personas<select name="guests" required>@foreach(range(1, 12) as $guests)<option value="{{ $guests }}" @selected(old('guests', $reservation->guests) == $guests)>{{ $guests }} {{ $guests === 1 ? 'persona' : 'personas' }}</option>@endforeach</select></label>
    <label class="record-form-wide">Nota<textarea name="notes" rows="3" maxlength="1000">{{ old('notes', $reservation->notes) }}</textarea></label>
    <p class="form-business-rule record-form-wide">Reglas: fecha desde hoy, horarios habilitados, de 1 a 12 personas y sin otra reserva pendiente/confirmada del mismo cliente en el mismo horario. Solo se editan reservas pendientes.</p>
    <div class="record-form-actions"><a class="button button-outline" href="{{ route('admin.reservations') }}">Cancelar</a><button class="button button-primary" type="submit">Guardar reserva</button></div>
</form></section>
@endsection
