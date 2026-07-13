<?php

namespace Tests\Feature;

use App\Models\Blogs;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            ->assertJsonPath('data.users.authors', 1)
            ->assertJsonPath('data.users.active', 2)
            ->assertJsonPath('data.users.suspended', 0);
    }

    public function test_admin_can_create_user(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->postJson('/api/admin/users', [
            'name' => 'New Author',
            'username' => 'new-author',
            'email' => 'new-author@example.com',
            'password' => 'password123',
            'role' => User::ROLE_AUTHOR,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'New Author')
            ->assertJsonPath('data.role', User::ROLE_AUTHOR)
            ->assertJsonPath('data.status', User::STATUS_ACTIVE)
            ->assertJsonMissingPath('data.password');

        $user = User::where('email', 'new-author@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('password123', $user->password));
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

    public function test_admin_can_update_user_details_and_password(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $author = User::factory()->create();

        $this->patchJson("/api/admin/users/{$author->id}", [
            'name' => 'Updated Author',
            'username' => 'updated-author',
            'email' => 'updated-author@example.com',
            'password' => 'new-password123',
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Author')
            ->assertJsonPath('data.username', 'updated-author')
            ->assertJsonPath('data.email', 'updated-author@example.com')
            ->assertJsonPath('data.role', User::ROLE_ADMIN)
            ->assertJsonPath('data.status', User::STATUS_ACTIVE)
            ->assertJsonMissingPath('data.password');

        $author->refresh();

        $this->assertTrue(Hash::check('new-password123', $author->password));
    }

    public function test_admin_cannot_remove_last_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$admin->id}/role", [
            'role' => User::ROLE_AUTHOR,
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'At least one active admin account must remain.');
    }

    public function test_admin_can_suspend_user_and_block_login(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $author = User::factory()->create();
        $author->createToken('api-token');

        $this->patchJson("/api/admin/users/{$author->id}/status", [
            'status' => User::STATUS_SUSPENDED,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', User::STATUS_SUSPENDED);

        $this->assertSame(0, $author->tokens()->count());

        $this->postJson('/api/auth/login', [
            'email' => $author->email,
            'password' => 'password',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'This account has been suspended.');
    }

    public function test_admin_cannot_suspend_own_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$admin->id}/status", [
            'status' => User::STATUS_SUSPENDED,
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Admins cannot suspend their own account.');
    }

    public function test_suspended_user_cannot_use_protected_routes(): void
    {
        Sanctum::actingAs(User::factory()->create(['status' => User::STATUS_SUSPENDED]));

        $this->getJson('/api/me/blogs')
            ->assertForbidden()
            ->assertJsonPath('message', 'This account has been suspended.');
    }

    public function test_admin_can_delete_another_user(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $author = User::factory()->create();

        $this->deleteJson("/api/admin/users/{$author->id}")
            ->assertOk()
            ->assertJsonPath('message', 'User deleted by admin.');

        $this->assertDatabaseMissing('users', ['id' => $author->id]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/admin/users/{$admin->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Admins cannot delete their own account.');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_create_blog_for_any_author(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $author = User::factory()->create();
        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/blogs', [
            'user_id' => $author->id,
            'title' => 'Admin Created Post',
            'content' => 'This post was created from the admin area.',
            'status' => Blogs::STATUS_PUBLISHED,
            'category_name' => 'Admin Notes',
            'tags' => ['Operations', 'Review'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Admin Created Post')
            ->assertJsonPath('data.author_user.id', $author->id)
            ->assertJsonPath('data.status', Blogs::STATUS_PUBLISHED)
            ->assertJsonPath('data.category.name', 'Admin Notes');

        $this->assertDatabaseHas('blogs', [
            'title' => 'Admin Created Post',
            'user_id' => $author->id,
            'author' => $author->username,
        ]);
    }

    public function test_admin_can_upload_cover_image_when_creating_blog(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $author = User::factory()->create();
        Sanctum::actingAs($admin);

        $response = $this->post('/api/admin/blogs', [
            'user_id' => $author->id,
            'title' => 'Admin Image Post',
            'content' => 'This admin-created post includes an uploaded cover image.',
            'status' => Blogs::STATUS_PUBLISHED,
            'cover_image' => $this->fakePng('admin-cover.png'),
        ], ['Accept' => 'application/json']);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Admin Image Post');

        $coverImageUrl = $response->json('data.cover_image_url');

        $this->assertIsString($coverImageUrl);
        $this->assertStringContainsString('/storage/blog-images/', $coverImageUrl);
        Storage::disk('public')->assertExists($this->storagePathFromUrl($coverImageUrl));
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

    public function test_admin_can_view_any_blog_details(): void
    {
        $author = User::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $blog = Blogs::create([
            'user_id' => $author->id,
            'title' => 'Inspected Draft',
            'slug' => 'inspected-draft',
            'content' => 'Draft content for review.',
            'author' => $author->username,
            'status' => Blogs::STATUS_DRAFT,
            'date' => now()->toDateString(),
        ]);

        Comment::create([
            'blog_id' => $blog->id,
            'author_name' => 'Reader',
            'author_email' => 'reader@example.com',
            'content' => 'Pending review.',
            'status' => Comment::STATUS_PENDING,
        ]);

        $this->getJson("/api/admin/blogs/{$blog->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Inspected Draft')
            ->assertJsonPath('data.status', Blogs::STATUS_DRAFT)
            ->assertJsonPath('data.comments_count', 1)
            ->assertJsonPath('data.comments.0.content', 'Pending review.');
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

    public function test_admin_can_view_user_history(): void
    {
        $author = User::factory()->create();
        $otherAuthor = User::factory()->create();
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        Blogs::create([
            'user_id' => $author->id,
            'title' => 'First User Post',
            'slug' => 'first-user-post',
            'content' => 'First content.',
            'author' => $author->username,
            'status' => Blogs::STATUS_PUBLISHED,
            'date' => now()->toDateString(),
            'published_at' => now(),
        ]);

        $otherBlog = Blogs::create([
            'user_id' => $otherAuthor->id,
            'title' => 'Other User Post',
            'slug' => 'other-user-post',
            'content' => 'Other content.',
            'author' => $otherAuthor->username,
            'status' => Blogs::STATUS_PUBLISHED,
            'date' => now()->toDateString(),
            'published_at' => now(),
        ]);

        Comment::create([
            'blog_id' => $otherBlog->id,
            'user_id' => $author->id,
            'author_name' => $author->name,
            'author_email' => $author->email,
            'content' => 'Author comment history.',
            'status' => Comment::STATUS_APPROVED,
        ]);

        $this->getJson("/api/admin/users/{$author->id}/history")
            ->assertOk()
            ->assertJsonPath('data.user.id', $author->id)
            ->assertJsonPath('data.user.blogs_count', 1)
            ->assertJsonPath('data.user.comments_count', 1)
            ->assertJsonPath('data.blogs.data.0.title', 'First User Post')
            ->assertJsonPath('data.comments.data.0.content', 'Author comment history.');
    }

    private function storagePathFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        return ltrim(preg_replace('#^.*?/storage/#', '', $path), '/');
    }

    private function fakePng(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
        );
    }
}
