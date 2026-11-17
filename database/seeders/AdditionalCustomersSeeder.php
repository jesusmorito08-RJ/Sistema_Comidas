<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class AdditionalCustomersSeeder extends Seeder
{
    public function run(): void
    {
        $emails = array_values(array_unique(config('app.seed_customer_emails', [])));

        if ($emails === []) {
            return;
        }

        foreach ($emails as $email) {
            if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new InvalidArgumentException('SEED_CUSTOMER_EMAILS must contain only valid email addresses.');
            }
        }

        $sofia = User::query()->where('email', 'cliente@sazon.test')->first();

        if ($sofia === null) {
            throw new RuntimeException('Seed the default Sofia account before seeding additional customers.');
        }

        foreach ($emails as $email) {
            User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => Str::title(Str::before($email, '@')),
                    'password' => $sofia->getRawOriginal('password'),
                    'role' => 'customer',
                    'is_active' => true,
                ],
            );
        }
    }
}
