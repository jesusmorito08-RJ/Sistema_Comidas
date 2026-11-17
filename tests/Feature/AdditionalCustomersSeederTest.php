<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdditionalCustomersSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdditionalCustomersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_configured_customers_with_sofias_password(): void
    {
        $emails = [
            'nicoll@example.test',
            'junior@example.test',
            'martin@example.test',
        ];
        config(['app.seed_customer_emails' => $emails]);

        $this->seed(DatabaseSeeder::class);

        $sofia = User::query()->where('email', 'cliente@sazon.test')->firstOrFail();

        foreach ($emails as $email) {
            $customer = User::query()->where('email', $email)->firstOrFail();

            $this->assertSame('customer', $customer->role);
            $this->assertSame($sofia->getRawOriginal('password'), $customer->getRawOriginal('password'));
        }
    }

    public function test_additional_customers_seeder_does_not_overwrite_existing_accounts(): void
    {
        config(['app.seed_customer_emails' => ['existing@example.test']]);

        User::factory()->create(['email' => 'cliente@sazon.test', 'role' => 'customer']);
        $existingUser = User::factory()->create(['email' => 'existing@example.test', 'role' => 'admin']);
        $existingPassword = $existingUser->getRawOriginal('password');

        $this->seed(AdditionalCustomersSeeder::class);

        $existingUser = $existingUser->fresh();
        $this->assertSame('admin', $existingUser->role);
        $this->assertSame($existingPassword, $existingUser->getRawOriginal('password'));
    }

    public function test_additional_customers_seeder_rejects_invalid_email_addresses(): void
    {
        config(['app.seed_customer_emails' => ['not-an-email']]);

        $this->expectException(\InvalidArgumentException::class);

        $this->seed(AdditionalCustomersSeeder::class);
    }
}
