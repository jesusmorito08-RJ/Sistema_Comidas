@extends('layouts.admin')
@section('title', 'Reservas — Sazón')
@section('admin-content')
@php $reservationStatuses = ['pending'=>'Pendiente','confirmed'=>'Confirmada','cancelled'=>'Cancelada','completed'=>'Completada']; @endphp
<div class="admin-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> AGENDA</span><h1>Reservas</h1><p>Organiza los horarios y confirma las solicitudes.</p></div></div>
<section class="panel-card table-panel"><div class="table-scroll"><table><thead><tr><th>Cliente</th><th>Fecha y hora</th><th>Personas</th><th>Nota</th><th>Estado</th><th>Actualizar</th></tr></thead><tbody>
@forelse($reservations as $reservation)<tr><td class="table-strong">{{ $reservation->user->name }}<small class="table-sub">{{ $reservation->user->email }}</small></td><td>{{ $reservation->reservation_date->format('d/m/Y') }}<small class="table-sub">{{ \Carbon\Carbon::parse($reservation->reservation_time)->format('g:i A') }}</small></td><td>{{ $reservation->guests }}</td><td>{{ $reservation->notes ?: '—' }}</td><td><span class="status status-{{ $reservation->status }}">{{ $reservationStatuses[$reservation->status] }}</span></td><td><div class="table-actions">
    @if($reservation->status === 'pending')<a class="mini-action" href="{{ route('admin.reservations.edit', $reservation) }}">Editar</a>@endif
    @if($reservation->status === 'pending')
        <form method="POST" action="{{ route('admin.reservations.update', $reservation) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="confirmed"><button class="mini-action" type="submit">Confirmar</button></form>
    @elseif($reservation->status === 'confirmed')
        <form method="POST" action="{{ route('admin.reservations.update', $reservation) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="completed"><button class="mini-action" type="submit">Completar</button></form>
    @endif
    @if(in_array($reservation->status, ['pending','confirmed'], true) && !$reservation->slotHasPassed())
        <form method="POST" action="{{ route('admin.reservations.cancel', $reservation) }}">@csrf @method('DELETE')<button class="remove-link" type="submit" onclick="return confirm('¿Cancelar esta reserva?')">Cancelar</button></form>
    @endif
    </div></td></tr>
@empty<tr><td colspan="6" class="table-empty">No hay reservas registradas.</td></tr>@endforelse
</tbody></table></div></section>
<div class="pagination-wrap">{{ $reservations->links() }}</div>
@endsection
