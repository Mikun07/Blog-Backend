<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_own_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'username' => 'old-name',
            'email' => 'old@example.com',
        ]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/auth/me', [
            'name' => 'New Name',
            'username' => 'new-name',
            'email' => 'new@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Profile updated.')
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.username', 'new-name')
            ->assertJsonPath('data.email', 'new@example.com')
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'username' => 'new-name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_profile_update_rejects_duplicate_identity_fields(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create([
            'username' => 'taken-name',
            'email' => 'taken@example.com',
        ]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/auth/me', [
            'username' => $otherUser->username,
            'email' => $otherUser->email,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'email']);
    }

    public function test_user_can_change_password_with_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password123'),
        ]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/auth/me', [
            'current_password' => 'old-password123',
            'password' => 'new-password123',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Profile updated.');

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password123'),
        ]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/auth/me', [
            'current_password' => 'wrong-password',
            'password' => 'new-password123',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Current password is incorrect.')
            ->assertJsonValidationErrors(['current_password']);

        $this->assertTrue(Hash::check('old-password123', $user->fresh()->password));
    }

    public function test_user_cannot_update_own_role_or_status(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_AUTHOR,
            'status' => User::STATUS_ACTIVE,
        ]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/auth/me', [
            'name' => 'Still An Author',
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_SUSPENDED,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Still An Author')
            ->assertJsonPath('data.role', User::ROLE_AUTHOR)
            ->assertJsonPath('data.status', User::STATUS_ACTIVE);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Still An Author',
            'role' => User::ROLE_AUTHOR,
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
