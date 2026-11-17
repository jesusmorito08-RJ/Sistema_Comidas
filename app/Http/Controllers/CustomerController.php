<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = User::query()
            ->where('role', 'customer')
            ->withCount('orders')
            ->where('is_active', ! $request->boolean('archived'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'archived' => $request->boolean('archived'),
        ]);
    }

    public function create(): View
    {
        return view('admin.customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', 'string', 'min:8'],
        ]);
        $data['role'] = 'customer';
        $data['is_active'] = true;

        User::create($data);

        return redirect()->route('admin.customers.index')->with('success', 'Cliente creado correctamente.');
    }

    public function edit(User $customer): View
    {
        $this->ensureCustomer($customer);

        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, User $customer): RedirectResponse
    {
        $this->ensureCustomer($customer);

        if (! $customer->is_active) {
            throw ValidationException::withMessages(['customer' => 'Reactiva al cliente antes de editar sus datos.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $customer->update($data);

        return redirect()->route('admin.customers.index')->with('success', 'Datos del cliente actualizados.');
    }

    public function destroy(Request $request, User $customer): RedirectResponse
    {
        $this->ensureCustomer($customer);

        if ($request->user()->is($customer)) {
            throw ValidationException::withMessages(['customer' => 'No puedes archivar tu propia cuenta.']);
        }

        $customer->update(['is_active' => false]);

        return back()->with('success', 'Cliente archivado; su historial de pedidos se conserva.');
    }

    public function restore(User $customer): RedirectResponse
    {
        $this->ensureCustomer($customer);
        $customer->update(['is_active' => true]);

        return back()->with('success', 'Cliente reactivado.');
    }

    private function ensureCustomer(User $customer): void
    {
        abort_unless($customer->role === 'customer', 404);
    }
}
