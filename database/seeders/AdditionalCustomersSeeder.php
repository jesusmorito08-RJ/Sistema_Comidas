<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdditionalCustomersSeeder extends Seeder
{
    public function run(): void
    {
        $sofia = User::query()
            ->where('email', 'cliente@sazon.test')
            ->firstOrFail();

        $emails = [
            'lopeznorieganicoll21@gmail.com',
            'juniormartinhurtadoaguilar7@gmail.com',
            'martinguerrerotalledo@gmail.com',
            'jesusmorito08@gmail.com',
        ];

        foreach ($emails as $email) {
            User::updateOrCreate(
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
