<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('admin.email');
        $password = (string) config('admin.password');

        if ($email === '' || $password === '' || $password === 'replace-this-before-seeding') {
            throw new RuntimeException('Set ADMIN_EMAIL and a strong ADMIN_PASSWORD in .env before seeding the admin account.');
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Live Bajar Administrator',
                'password' => Hash::make($password),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        $user->update([
            'name' => 'Live Bajar Administrator',
            'is_admin' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);
    }
}
