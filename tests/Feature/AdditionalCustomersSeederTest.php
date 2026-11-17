<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdditionalCustomersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_requested_customers_with_sofias_password(): void
    {
        $this->seed(DatabaseSeeder::class);

        $sofia = User::query()->where('email', 'cliente@sazon.test')->firstOrFail();
        $emails = [
            'lopeznorieganicoll21@gmail.com',
            'juniormartinhurtadoaguilar7@gmail.com',
            'martinguerrerotalledo@gmail.com',
            'jesusmorito08@gmail.com',
        ];

        foreach ($emails as $email) {
            $customer = User::query()->where('email', $email)->firstOrFail();

            $this->assertSame('customer', $customer->role);
            $this->assertTrue($customer->is_active);
            $this->assertSame(
                $sofia->getRawOriginal('password'),
                $customer->getRawOriginal('password'),
            );
        }
    }
}
