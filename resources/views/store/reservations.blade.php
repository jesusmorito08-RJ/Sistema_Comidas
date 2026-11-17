@extends('layouts.app')
@section('title', 'Reservas — Sazón')
@section('content')
<section class="section page-section"><div class="container">
    <div class="page-heading"><span class="eyebrow"><span class="eyebrow-line"></span> UN LUGAR EN LA MESA</span><h1>Reserva tu momento</h1><p>Cuéntanos cuándo vienes y cuántos serán. Te confirmamos pronto.</p></div>
    <div class="reservation-layout"><div class="reservation-intro"><div class="reservation-art"><span>✦</span><strong>Los mejores<br>momentos se<br>comparten.</strong></div><p>Solicita una reserva y nuestro equipo confirmará el horario contigo. También puedes dejar una nota para que preparemos todo.</p></div>
        <form class="reservation-form form-stack" method="POST" action="{{ route('reservations.store') }}">@csrf
            <h2>Nueva reserva</h2>
            <label>Fecha<input type="date" name="reservation_date" min="{{ now()->toDateString() }}" value="{{ old('reservation_date') }}" required></label>
            <label>Hora<select name="reservation_time" required><option value="">Selecciona un horario</option>@foreach(\App\Models\Reservation::AVAILABLE_TIMES as $time)<option value="{{ $time }}" @selected(old('reservation_time') === $time)>{{ \Carbon\Carbon::createFromFormat('H:i', $time)->format('g:i A') }}</option>@endforeach</select></label>
            <label>Número de personas<select name="guests" required>@foreach(range(1, 12) as $count)<option value="{{ $count }}" @selected(old('guests', 2) == $count)>{{ $count }} {{ $count === 1 ? 'persona' : 'personas' }}</option>@endforeach</select></label>
            <label>Nota <span class="optional">(opcional)</span><textarea name="notes" rows="3" placeholder="¿Celebración especial?">{{ old('notes') }}</textarea></label>
            <button class="button button-primary button-full" type="submit">Solicitar reserva <span>→</span></button>
        </form>
    </div>
    <div class="reservation-history"><div class="section-heading"><div><h2>Tus reservas</h2><p>Consulta el estado de tus solicitudes.</p></div></div>
        @forelse($reservations as $reservation)
            @php $reservationStatuses = ['pending' => 'Pendiente', 'confirmed' => 'Confirmada', 'cancelled' => 'Cancelada', 'completed' => 'Completada']; @endphp
            <div class="reservation-row"><div class="reservation-date"><strong>{{ $reservation->reservation_date->format('d') }}</strong><span>{{ $reservation->reservation_date->translatedFormat('M') }}</span></div><div><strong>{{ \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A') }}</strong><small>{{ $reservation->guests }} {{ $reservation->guests === 1 ? 'persona' : 'personas' }}</small></div><span class="status status-{{ $reservation->status }}">{{ $reservationStatuses[$reservation->status] ?? $reservation->status }}</span>
                @if(in_array($reservation->status, ['pending', 'confirmed'], true) && !$reservation->slotHasPassed())
                    <form method="POST" action="{{ route('reservations.cancel', $reservation) }}">@csrf @method('DELETE')<button class="remove-link" type="submit" onclick="return confirm('¿Cancelar esta reserva?')">Cancelar</button></form>
                @endif
            </div>
        @empty
            <p class="muted">Todavía no tienes reservas.</p>
        @endforelse
    </div>
</div></section>
@endsection
