<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public const AVAILABLE_TIMES = Reservation::AVAILABLE_TIMES;

    public function index(Request $request): View
    {
        $reservations = $request->user()->reservations()->latest('reservation_date')->latest('reservation_time')->get();

        return view('store.reservations', compact('reservations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'reservation_time' => ['required', Rule::in(self::AVAILABLE_TIMES)],
            'guests' => ['required', 'integer', 'min:1', 'max:12'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        if (Reservation::isSlotInPast($data['reservation_date'], $data['reservation_time'])) {
            throw ValidationException::withMessages(['reservation_time' => 'Selecciona un horario que todavía no haya pasado.']);
        }
        self::ensureNoDuplicate($request->user()->id, $data);
        $request->user()->reservations()->create($data);

        return redirect()->route('reservations.index')->with('success', 'Solicitud de reserva enviada. Te confirmaremos pronto.');
    }

    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        $customerReservation = $request->user()->reservations()->whereKey($reservation->id)->firstOrFail();
        if (! in_array($customerReservation->status, ['pending', 'confirmed'], true) || $customerReservation->slotHasPassed()) {
            throw ValidationException::withMessages(['reservation' => 'Solo puedes cancelar reservas pendientes o confirmadas cuyo horario no haya pasado.']);
        }
        $customerReservation->update(['status' => 'cancelled']);

        return redirect()->route('reservations.index')->with('success', 'Reserva cancelada; conservamos el registro.');
    }

    public static function ensureNoDuplicate(int $userId, array $data, ?int $ignoreId = null): void
    {
        if (Reservation::hasActiveSlotConflict($userId, $data['reservation_date'], $data['reservation_time'], $ignoreId)) {
            throw ValidationException::withMessages(['reservation_time' => 'Ya existe una reserva activa para ese cliente y horario.']);
        }
    }
}
