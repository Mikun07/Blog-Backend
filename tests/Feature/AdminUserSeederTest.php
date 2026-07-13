<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_seeder_creates_configured_admin(): void
    {
        config()->set('admin.name', 'Portfolio Admin');
        config()->set('admin.username', 'portfolio-admin');
        config()->set('admin.email', 'ayomikunolaleye@gmail.com');
        config()->set('admin.password', 'TestPassword12#');

        $this->seed(AdminUserSeeder::class);

        $admin = User::query()
            ->where('email', 'ayomikunolaleye@gmail.com')
            ->firstOrFail();

        $this->assertSame('Portfolio Admin', $admin->name);
        $this->assertSame('portfolio-admin', $admin->username);
        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertTrue(Hash::check('TestPassword12#', $admin->password));
    }

    public function test_admin_user_seeder_requires_password(): void
    {
        config()->set('admin.password', null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Set ADMIN_PASSWORD before running AdminUserSeeder.');

        $this->seed(AdminUserSeeder::class);
    }
}
