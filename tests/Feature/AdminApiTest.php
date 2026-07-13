<?php

namespace Tests\Feature;

use App\Models\Blogs;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_cannot_access_admin_dashboard(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/dashboard')
            ->assertForbidden()
            ->assertJsonPath('message', 'Admin access is required.');
    }

    public function test_admin_can_view_dashboard_metrics(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->create();
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.users.total', 2)
            ->assertJsonPath('data.users.admins', 1)
            ->assertJsonPath('data.users.authors', 1);
    }

    public function test_admin_can_promote_user_to_admin(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $author = User::factory()->create();

        $this->patchJson("/api/admin/users/{$author->id}/role", [
            'role' => User::ROLE_ADMIN,
        ])
            ->assertOk()
            ->assertJsonPath('data.role', User::ROLE_ADMIN);

        $this->assertDatabaseHas('users', [
            'id' => $author->id,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_admin_cannot_remove_last_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$admin->id}/role", [
            'role' => User::ROLE_AUTHOR,
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'At least one admin account must remain.');
    }

    public function test_admin_can_archive_any_blog(): void
    {
        $author = User::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $blog = Blogs::create([
            'user_id' => $author->id,
            'title' => 'Admin Managed Post',
            'slug' => 'admin-managed-post',
            'content' => 'Admin managed content.',
            'author' => $author->username,
            'status' => Blogs::STATUS_PUBLISHED,
            'date' => now()->toDateString(),
            'published_at' => now(),
        ]);

        $this->patchJson("/api/admin/blogs/{$blog->id}/status", [
            'status' => Blogs::STATUS_ARCHIVED,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', Blogs::STATUS_ARCHIVED)
            ->assertJsonPath('data.published_at', null);
    }

    public function test_admin_can_moderate_any_comment(): void
    {
        $author = User::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $blog = Blogs::create([
            'user_id' => $author->id,
            'title' => 'Commented Post',
            'slug' => 'commented-post',
            'content' => 'Commented content.',
            'author' => $author->username,
            'status' => Blogs::STATUS_PUBLISHED,
            'date' => now()->toDateString(),
            'published_at' => now(),
        ]);

        $comment = Comment::create([
            'blog_id' => $blog->id,
            'author_name' => 'Reader',
            'author_email' => 'reader@example.com',
            'content' => 'Needs review.',
            'status' => Comment::STATUS_PENDING,
        ]);

        $this->patchJson("/api/admin/comments/{$comment->id}", [
            'status' => Comment::STATUS_REJECTED,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', Comment::STATUS_REJECTED);
    }
}
