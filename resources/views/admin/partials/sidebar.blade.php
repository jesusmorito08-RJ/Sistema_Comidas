<aside class="admin-sidebar">
    <div class="sidebar-title">GESTIÓN</div>
    <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><span>▦</span> Resumen</a>
    <a class="{{ request()->routeIs('admin.foods.*') ? 'active' : '' }}" href="{{ route('admin.foods.index') }}"><span>♨</span> Menú y comidas</a>
    <a class="{{ request()->routeIs('admin.orders*') ? 'active' : '' }}" href="{{ route('admin.orders') }}"><span>▤</span> Pedidos</a>
    <a class="{{ request()->routeIs('admin.payment-proofs.*') ? 'active' : '' }}" href="{{ route('admin.payment-proofs.index') }}"><span>✓</span> Evidencias de pago</a>
    <a class="{{ request()->routeIs('admin.reservations*') ? 'active' : '' }}" href="{{ route('admin.reservations') }}"><span>◷</span> Reservas</a>
    <a class="{{ request()->routeIs('admin.customers.*') ? 'active' : '' }}" href="{{ route('admin.customers.index') }}"><span>♙</span> Clientes</a>
    <a class="{{ request()->routeIs('admin.reports') ? 'active' : '' }}" href="{{ route('admin.reports') }}"><span>▥</span> Reportes</a>
    <div class="sidebar-help"><span>✿</span><strong>¿Todo listo?</strong><small>Revisa los pedidos pendientes y prepara algo rico.</small></div>
</aside>
