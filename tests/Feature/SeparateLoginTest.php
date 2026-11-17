<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeparateLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_administrator_have_separate_login_pages(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSeeText('ACCESO DE CLIENTE')
            ->assertDontSeeText('Panel privado');

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSeeText('Administración')
            ->assertSeeText('Panel privado')
            ->assertDontSeeText('Carrito');
    }

    public function test_customer_login_rejects_administrators_and_redirects_customers_to_their_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $customer = User::factory()->create(['role' => 'customer']);
        $this->post(route('login.store'), ['email' => $customer->email, 'password' => 'password'])
            ->assertRedirect(route('customer.dashboard'));

        $this->assertAuthenticatedAs($customer);
    }

    public function test_administrator_login_rejects_customers_and_redirects_administrators_to_the_admin_panel(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->post(route('admin.login.store'), ['email' => $customer->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_unauthenticated_panels_redirect_to_the_matching_login_page(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('customer.dashboard'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_are_redirected_to_their_own_panel_from_login_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('login'))
            ->assertRedirect(route('admin.dashboard'));

        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)
            ->get(route('admin.login'))
            ->assertRedirect(route('customer.dashboard'));
    }
}
