<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_book_an_upcoming_slot_but_not_one_that_has_passed_today(): void
    {
        $this->travelTo('2026-03-20 10:00:00');
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer);
        $this->get(route('reservations.index'))->assertOk();

        $this->post(route('reservations.store'), [
            'reservation_date' => '2026-03-20',
            'reservation_time' => '11:00',
            'guests' => 2,
        ])->assertRedirect(route('reservations.index'));

        $this->assertDatabaseHas('reservations', [
            'user_id' => $customer->id,
            'reservation_date' => '2026-03-20 00:00:00',
            'reservation_time' => '11:00',
        ]);

        $this->travelTo('2026-03-20 12:00:00');
        $this->post(route('reservations.store'), [
            'reservation_date' => '2026-03-20',
            'reservation_time' => '11:00',
            'guests' => 2,
        ])->assertSessionHasErrors('reservation_time');

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_customer_cannot_create_a_duplicate_active_reservation(): void
    {
        $this->travelTo('2026-03-20 10:00:00');
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer);
        $reservationData = [
            'reservation_date' => '2030-04-15',
            'reservation_time' => '18:00',
            'guests' => 2,
        ];

        $this->post(route('reservations.store'), $reservationData)
            ->assertRedirect(route('reservations.index'));
        $this->post(route('reservations.store'), $reservationData)
            ->assertSessionHasErrors('reservation_time');

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_customer_can_cancel_only_their_own_upcoming_reservation(): void
    {
        $this->travelTo('2026-03-20 10:00:00');
        $customer = User::factory()->create(['role' => 'customer']);
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $otherReservation = $otherCustomer->reservations()->create([
            'reservation_date' => '2026-03-21',
            'reservation_time' => '18:00',
            'guests' => 2,
        ]);

        $this->actingAs($customer)
            ->delete(route('reservations.cancel', $otherReservation))
            ->assertNotFound();

        $ownReservation = $customer->reservations()->create([
            'reservation_date' => '2026-03-21',
            'reservation_time' => '19:00',
            'guests' => 2,
        ]);

        $this->delete(route('reservations.cancel', $ownReservation))
            ->assertRedirect(route('reservations.index'));

        $this->assertDatabaseHas('reservations', ['id' => $ownReservation->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('reservations', ['id' => $otherReservation->id, 'status' => 'pending']);
    }

    public function test_admin_can_edit_and_confirm_a_reservation_but_cannot_edit_it_after_confirmation(): void
    {
        $this->travelTo('2026-03-20 10:00:00');
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $reservation = $customer->reservations()->create([
            'reservation_date' => '2026-03-21',
            'reservation_time' => '18:00',
            'guests' => 2,
        ]);
        $this->actingAs($admin);

        $this->get(route('admin.reservations.edit', $reservation))->assertOk();
        $this->get(route('admin.reservations'))->assertOk();
        $this->patch(route('admin.reservations.update', $reservation), [
            'status' => 'pending',
            'reservation_date' => '2026-03-22',
            'reservation_time' => '19:00',
            'guests' => 4,
            'notes' => 'Cumpleaños',
        ])->assertRedirect(route('admin.reservations'));

        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'reservation_date' => '2026-03-22 00:00:00',
            'reservation_time' => '19:00',
            'guests' => 4,
            'notes' => 'Cumpleaños',
        ]);

        $this->patch(route('admin.reservations.update', $reservation), ['status' => 'confirmed'])
            ->assertRedirect(route('admin.reservations'));
        $this->patch(route('admin.reservations.update', $reservation), [
            'status' => 'confirmed',
            'reservation_date' => '2026-03-23',
        ])->assertSessionHasErrors('reservation_date');

        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'status' => 'confirmed']);
    }

    public function test_admin_cannot_confirm_a_reservation_in_a_slot_that_has_passed(): void
    {
        $this->travelTo('2026-03-20 12:00:00');
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $reservation = $customer->reservations()->create([
            'reservation_date' => '2026-03-20',
            'reservation_time' => '11:00',
            'guests' => 2,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.reservations.update', $reservation), ['status' => 'confirmed'])
            ->assertSessionHasErrors('reservation_time');

        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'status' => 'pending']);
    }
}
