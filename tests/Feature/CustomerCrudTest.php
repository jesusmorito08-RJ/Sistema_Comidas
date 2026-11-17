<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_archive_and_restore_a_customer_without_changing_its_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $this->get(route('admin.customers.create'))->assertOk();

        $this->post(route('admin.customers.store'), [
            'name' => 'Cliente de prueba',
            'email' => 'cliente@example.test',
            'phone' => '999111222',
            'address' => 'Calle de prueba',
            'password' => 'password-seguro',
            'password_confirmation' => 'password-seguro',
            'role' => 'admin',
        ])->assertRedirect(route('admin.customers.index'));

        $customer = User::where('email', 'cliente@example.test')->firstOrFail();
        $this->get(route('admin.customers.edit', $customer))->assertOk();
        $this->assertSame('customer', $customer->role);
        $customer->orders()->create([
            'order_number' => 'PD-CUSTOMER-0001',
            'payment_method' => 'cash',
            'total' => 20,
            'delivery_address' => 'Calle de prueba',
        ]);

        $this->put(route('admin.customers.update', $customer), [
            'name' => 'Cliente actualizado',
            'email' => 'cliente-actualizado@example.test',
            'phone' => '999333444',
            'address' => 'Nueva dirección',
        ])->assertRedirect(route('admin.customers.index'));

        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'name' => 'Cliente actualizado',
            'email' => 'cliente-actualizado@example.test',
            'role' => 'customer',
            'is_active' => true,
        ]);

        $this->delete(route('admin.customers.destroy', $customer))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $customer->id, 'is_active' => false]);
        $this->assertDatabaseHas('orders', ['user_id' => $customer->id, 'order_number' => 'PD-CUSTOMER-0001']);

        $this->post(route('admin.customers.restore', $customer))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $customer->id, 'is_active' => true]);
        $this->get(route('admin.customers.index'))->assertOk();
    }

    public function test_customer_cannot_archive_an_administrator_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $anotherAdmin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->delete(route('admin.customers.destroy', $anotherAdmin))
            ->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $anotherAdmin->id, 'is_active' => true]);
    }
}
