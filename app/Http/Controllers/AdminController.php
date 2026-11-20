<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Models\Order;
use App\Models\PaymentProof;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $orders = Order::query();
        $stats = [
            'today_orders' => (clone $orders)->whereDate('created_at', today())->count(),
            'pending_orders' => (clone $orders)->whereIn('status', ['pending', 'confirmed', 'preparing'])->count(),
            'customers' => User::where('role', 'customer')->where('is_active', true)->count(),
            'revenue' => (clone $orders)
                ->where('status', '!=', 'cancelled')
                ->where('payment_status', 'paid')
                ->sum('total'),
        ];
        $recentOrders = Order::with('user')->latest()->limit(7)->get();
        $reservations = Reservation::with('user')->where('status', 'pending')->orderBy('reservation_date')->limit(5)->get();
        $week = $this->weeklySales();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'reservations', 'week'));
    }

    public function orders(Request $request): View
    {
        $archived = $request->boolean('archived');
        $orders = Order::with([
            'user',
            'items',
            'paymentProofs' => fn ($query) => $query->where('status', PaymentProof::STATUS_APPROVED),
        ])
            ->when($archived, fn ($query) => $query->onlyTrashed())
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.orders', compact('orders', 'archived'));
    }

    public function updateOrder(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'preparing', 'on_the_way', 'delivered', 'cancelled'])],
            'delivery_address' => ['sometimes', 'required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($data, $order): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());
            $data['delivery_address'] ??= $lockedOrder->delivery_address;
            if (! array_key_exists('notes', $data)) {
                $data['notes'] = $lockedOrder->notes;
            }
            $allowedTransitions = [
                'pending' => ['confirmed', 'cancelled'],
                'confirmed' => ['preparing', 'cancelled'],
                'preparing' => ['on_the_way', 'cancelled'],
                'on_the_way' => ['delivered'],
                'delivered' => [],
                'cancelled' => [],
            ];

            if ($data['status'] !== $lockedOrder->status
                && ! in_array($data['status'], $allowedTransitions[$lockedOrder->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Ese cambio de estado no está permitido para este pedido.']);
            }

            if ($data['status'] === 'cancelled' && $lockedOrder->payment_status === 'paid') {
                throw ValidationException::withMessages(['status' => 'No se puede cancelar un pedido ya pagado.']);
            }

            if ($data['status'] === 'cancelled'
                && $lockedOrder->paymentProofs()->where('status', PaymentProof::STATUS_PENDING)->exists()) {
                throw ValidationException::withMessages(['status' => 'No se puede cancelar un pedido mientras una evidencia de pago está pendiente de revisión.']);
            }

            if (! in_array($lockedOrder->status, ['pending', 'confirmed'], true) && (
                $data['delivery_address'] !== $lockedOrder->delivery_address
                || $data['notes'] !== $lockedOrder->notes
            )) {
                throw ValidationException::withMessages(['delivery_address' => 'La dirección y las notas no se pueden cambiar cuando la preparación ya comenzó.']);
            }

            $lockedOrder->update($data);
        });

        return redirect()->route('admin.orders')->with('success', 'Pedido actualizado.');
    }

    public function editOrder(Order $order): View
    {
        return view('admin.orders.edit', compact('order'));
    }

    public function archiveOrder(Order $order): RedirectResponse
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($lockedOrder->status !== 'cancelled' || $lockedOrder->payment_status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'Solo se pueden archivar pedidos cancelados y sin pago registrado.']);
            }

            $lockedOrder->delete();
        });

        return redirect()->route('admin.orders')->with('success', 'Pedido archivado; se conserva su historial.');
    }

    public function restoreOrder(int $id): RedirectResponse
    {
        Order::onlyTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Pedido restaurado.');
    }

    public function reservations(): View
    {
        $reservations = Reservation::with('user')->latest('reservation_date')->paginate(15);

        return view('admin.reservations', compact('reservations'));
    }

    public function editReservation(Reservation $reservation): View
    {
        abort_unless($reservation->status === 'pending', 404);

        return view('admin.reservations.edit', compact('reservation'));
    }

    public function updateReservation(Request $request, Reservation $reservation): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
            'reservation_date' => ['sometimes', 'required', 'date', 'after_or_equal:today'],
            'reservation_time' => ['sometimes', 'required', Rule::in(Reservation::AVAILABLE_TIMES)],
            'guests' => ['sometimes', 'required', 'integer', 'min:1', 'max:12'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);
        $allowedTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['completed', 'cancelled'],
            'cancelled' => [],
            'completed' => [],
        ];
        if ($data['status'] !== $reservation->status && ! in_array($data['status'], $allowedTransitions[$reservation->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'Ese cambio de estado no está permitido para esta reserva.']);
        }
        $newReservationDate = $data['reservation_date'] ?? $reservation->reservation_date->toDateString();
        $newReservationTime = $data['reservation_time'] ?? substr((string) $reservation->reservation_time, 0, 5);
        $newGuests = $data['guests'] ?? $reservation->guests;
        $newNotes = array_key_exists('notes', $data) ? $data['notes'] : $reservation->notes;
        if ($reservation->status !== 'pending' && (
            $newReservationDate !== $reservation->reservation_date->toDateString()
            || $newReservationTime !== substr((string) $reservation->reservation_time, 0, 5)
            || $newGuests !== $reservation->guests
            || $newNotes !== $reservation->notes
        )) {
            throw ValidationException::withMessages(['reservation_date' => 'Solo se pueden editar los datos de una reserva pendiente.']);
        }
        if (in_array($data['status'], ['pending', 'confirmed'], true) && Reservation::isSlotInPast($newReservationDate, $newReservationTime)) {
            throw ValidationException::withMessages(['reservation_time' => 'No se puede mantener activa una reserva cuyo horario ya pasó.']);
        }
        if ($data['status'] === 'completed' && ! Reservation::isSlotInPast($newReservationDate, $newReservationTime)) {
            throw ValidationException::withMessages(['status' => 'No se puede completar una reserva cuya fecha y hora aún no llegan.']);
        }
        if (Reservation::hasActiveSlotConflict($reservation->user_id, $newReservationDate, $newReservationTime, $reservation->id)
            && in_array($data['status'], ['pending', 'confirmed'], true)) {
            throw ValidationException::withMessages(['reservation_time' => 'Ese cliente ya tiene una reserva pendiente o confirmada para ese horario.']);
        }
        $data['reservation_date'] = $newReservationDate;
        $data['reservation_time'] = $newReservationTime;
        $data['guests'] = $newGuests;
        $data['notes'] = $newNotes;
        $reservation->update($data);

        return redirect()->route('admin.reservations')->with('success', 'Reserva actualizada.');
    }

    public function cancelReservation(Reservation $reservation): RedirectResponse
    {
        if (! in_array($reservation->status, ['pending', 'confirmed'], true) || $reservation->slotHasPassed()) {
            throw ValidationException::withMessages(['reservation' => 'Solo se pueden cancelar reservas pendientes o confirmadas cuyo horario no haya pasado.']);
        }
        $reservation->update(['status' => 'cancelled']);

        return redirect()->route('admin.reservations')->with('success', 'Reserva cancelada; se conserva el registro.');
    }

    public function reports(): View
    {
        $week = $this->weeklySales();
        $orders = Order::query()->where('status', '!=', 'cancelled');
        $paidOrders = (clone $orders)->where('payment_status', 'paid');
        $paymentTotals = (clone $paidOrders)->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('payment_method')->get()->keyBy('payment_method');
        $topFoods = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNull('orders.deleted_at')
            ->where('orders.status', '!=', 'cancelled')
            ->where('orders.payment_status', 'paid')
            ->select('order_items.food_name', DB::raw('SUM(order_items.quantity) as quantity'), DB::raw('SUM(order_items.subtotal) as revenue'))
            ->groupBy('order_items.food_name')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get();
        $summary = [
            'total_orders' => (clone $orders)->count(),
            'total_sales' => (clone $paidOrders)->sum('total'),
            'food_count' => Food::count(),
            'reservations' => Reservation::count(),
        ];

        return view('admin.reports', compact('week', 'paymentTotals', 'topFoods', 'summary'));
    }

    private function weeklySales(): array
    {
        $days = collect(range(6, 0))->map(fn (int $offset) => Carbon::today()->subDays($offset));
        $sales = Order::query()
            ->where('status', '!=', 'cancelled')
            ->where('payment_status', 'paid')
            ->whereDate('created_at', '>=', $days->first()->toDateString())
            ->get(['created_at', 'total'])
            ->groupBy(fn (Order $order) => $order->created_at->toDateString());

        return $days->map(fn (Carbon $day) => [
            'label' => $day->translatedFormat('D'),
            'date' => $day->format('d/m'),
            'total' => (float) $sales->get($day->toDateString(), collect())->sum('total'),
        ])->all();
    }
}
