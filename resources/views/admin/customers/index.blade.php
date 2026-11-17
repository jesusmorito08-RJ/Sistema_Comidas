@extends('layouts.admin')
@section('title', 'Clientes — Sazón')
@section('admin-content')
<div class="admin-heading"><div><span class="eyebrow"><span class="eyebrow-line"></span> COMUNIDAD</span><h1>Clientes</h1><p>Registra clientes, actualiza sus datos y conserva su historial de pedidos.</p></div><div class="admin-heading-actions">@if(!$archived)<a class="button button-primary" href="{{ route('admin.customers.create') }}">＋ Nuevo cliente</a>@endif<a class="button button-outline" href="{{ route('admin.customers.index', ['archived' => $archived ? null : 1]) }}">{{ $archived ? '← Clientes activos' : 'Ver archivados' }}</a></div></div>
<section class="panel-card table-panel"><div class="table-scroll"><table><thead><tr><th>Cliente</th><th>Contacto</th><th>Desde</th><th>Pedidos</th><th>Acciones</th></tr></thead><tbody>
@forelse($customers as $customer)<tr>
    <td class="table-strong"><span class="customer-name"><span class="avatar-circle">{{ mb_substr($customer->name, 0, 1) }}</span>{{ $customer->name }}</span></td>
    <td>{{ $customer->email }}<small class="table-sub">{{ $customer->phone ?: 'Sin teléfono' }}</small></td>
    <td>{{ $customer->created_at->format('d/m/Y') }}</td><td>{{ $customer->orders_count }}</td>
    <td><div class="table-actions">
        @if($archived)
            <form method="POST" action="{{ route('admin.customers.restore', $customer) }}">@csrf<button class="mini-action" type="submit">Reactivar</button></form>
        @else
            <a class="mini-action" href="{{ route('admin.customers.edit', $customer) }}">Editar</a>
            <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}">@csrf @method('DELETE')<button class="mini-action button-danger-soft" type="submit" onclick="return confirm('¿Archivar cliente? Se conservarán sus pedidos.')">Archivar</button></form>
        @endif
    </div></td>
</tr>
@empty<tr><td colspan="5" class="table-empty">No hay clientes {{ $archived ? 'archivados' : 'activos' }}.</td></tr>@endforelse
</tbody></table></div></section>
<div class="pagination-wrap">{{ $customers->links() }}</div>
@endsection
