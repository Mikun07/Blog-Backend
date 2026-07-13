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
        $password = config('admin.password');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Set ADMIN_EMAIL to a valid email address before running AdminUserSeeder.');
        }

        if (! is_string($password) || $password === '') {
            throw new RuntimeException('Set ADMIN_PASSWORD before running AdminUserSeeder.');
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) config('admin.name'),
                'username' => (string) config('admin.username'),
                'password' => Hash::make($password),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ],
        );
    }
}
