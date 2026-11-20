<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $cart = $this->cartItems($request);

        return view('store.cart', compact('cart'));
    }

    public function add(Request $request, Food $food): RedirectResponse
    {
        abort_unless($food->is_available, 404);
        $data = $request->validate(['quantity' => ['nullable', 'integer', 'min:1', 'max:20']]);
        $cart = $request->session()->get('cart', []);
        $id = (string) $food->id;
        $cart[$id] = min(20, ($cart[$id] ?? 0) + ($data['quantity'] ?? 1));
        $request->session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', 'Agregado al carrito.');
    }

    public function update(Request $request, Food $food): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:20']]);
        $cart = $request->session()->get('cart', []);
        $cart[(string) $food->id] = $data['quantity'];
        $request->session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', 'Carrito actualizado.');
    }

    public function remove(Request $request, Food $food): RedirectResponse
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[(string) $food->id]);
        $request->session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', 'Producto retirado del carrito.');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'delivery_address' => ['required', 'string', 'max:255'],
            'payment_method' => ['required', Rule::in(['yape', 'plin'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $cart = $request->session()->get('cart', []);

        if (! is_array($cart) || $cart === []) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Tu carrito está vacío.']);
        }

        $order = DB::transaction(function () use ($cart, $data, $request): Order {
            $foods = Food::query()->whereIn('id', array_keys($cart))->where('is_available', true)->lockForUpdate()->get()->keyBy('id');
            if ($foods->count() !== count($cart)) {
                throw ValidationException::withMessages(['cart' => 'Un producto del carrito ya no está disponible. Actualiza tu carrito e inténtalo de nuevo.']);
            }
            $lines = [];
            $total = 0;

            foreach ($cart as $id => $quantity) {
                $food = $foods->get((int) $id);
                $quantity = max(1, min(20, (int) $quantity));
                $price = (float) ($food->offer_price ?? $food->price);
                $subtotal = round($price * $quantity, 2);
                $lines[] = [
                    'food_id' => $food->id,
                    'food_name' => $food->name,
                    'unit_price' => $price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];
                $total += $subtotal;
            }

            if ($lines === []) {
                abort(422, 'Los productos del carrito ya no están disponibles.');
            }

            $order = Order::create([
                'user_id' => $request->user()->id,
                'order_number' => 'PD-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                'status' => 'pending',
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'total' => round($total, 2),
                'delivery_address' => $data['delivery_address'],
                'notes' => $data['notes'] ?? null,
            ]);
            $order->items()->createMany($lines);

            return $order;
        });

        $request->session()->forget('cart');

        return redirect()->route('orders.mine')->with('success', 'Pedido '.$order->order_number.' recibido. ¡Gracias!');
    }

    public function cancelOrder(Request $request, Order $order): RedirectResponse
    {
        $customerOrder = $request->user()->orders()->whereKey($order->id)->firstOrFail();

        DB::transaction(function () use ($customerOrder, $request): void {
            $lockedOrder = Order::query()
                ->whereKey($customerOrder->getKey())
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status !== 'pending' || $lockedOrder->payment_status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'Solo puedes cancelar pedidos pendientes y todavía no pagados.']);
            }

            if ($lockedOrder->paymentProofs()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['order' => 'No puedes cancelar un pedido mientras una evidencia de pago está pendiente de revisión.']);
            }

            $lockedOrder->update(['status' => 'cancelled']);
        });

        return redirect()->route('orders.mine')->with('success', 'Pedido cancelado.');
    }

    private function cartItems(Request $request): array
    {
        $quantities = $request->session()->get('cart', []);
        $foods = Food::query()->whereIn('id', array_keys(is_array($quantities) ? $quantities : []))->get()->keyBy('id');

        return collect($quantities)->map(function ($quantity, $id) use ($foods) {
            $food = $foods->get((int) $id);
            if (! $food) {
                return null;
            }

            $quantity = max(1, min(20, (int) $quantity));
            $price = (float) ($food->offer_price ?? $food->price);

            return ['food' => $food, 'quantity' => $quantity, 'subtotal' => $price * $quantity];
        })->filter()->values()->all();
    }
}
