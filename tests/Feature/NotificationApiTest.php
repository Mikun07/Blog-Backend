<?php

namespace Tests\Feature;

use App\Models\Blogs;
use App\Models\User;
use App\Notifications\BlogPublishedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_blog_notifies_active_users_across_roles(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $author = User::factory()->create(['role' => User::ROLE_AUTHOR]);
        $suspended = User::factory()->create(['status' => User::STATUS_SUSPENDED]);
        Sanctum::actingAs($author);

        $this->postJson('/api/blogs', [
            'title' => 'Notification Ready Post',
            'content' => 'This post should notify active users.',
            'status' => Blogs::STATUS_PUBLISHED,
        ])->assertCreated();

        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $author->id,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $suspended->id,
        ]);
    }

    public function test_user_can_view_and_mark_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $blog = Blogs::create([
            'user_id' => $author->id,
            'title' => 'Published Notification Post',
            'slug' => 'published-notification-post',
            'content' => 'Notification content.',
            'author' => $author->username,
            'status' => Blogs::STATUS_PUBLISHED,
            'date' => now()->toDateString(),
            'published_at' => now(),
        ]);

        Notification::send($user, new BlogPublishedNotification($blog));
        $notification = $user->notifications()->firstOrFail();
        Sanctum::actingAs($user);

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);

        $this->getJson('/api/notifications?unread=1')
            ->assertOk()
            ->assertJsonPath('data.data.0.data.type', 'blog.published')
            ->assertJsonPath('data.data.0.data.blog.title', 'Published Notification Post');

        $this->patchJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($notification->fresh()->read_at);

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_mark_all_as_read_updates_only_current_users_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $author = User::factory()->create();
        $firstBlog = Blogs::create([
            'user_id' => $author->id,
            'title' => 'First Notification Post',
            'slug' => 'first-notification-post',
            'content' => 'First notification content.',
            'author' => $author->username,
            'status' => Blogs::STATUS_PUBLISHED,
            'date' => now()->toDateString(),
            'published_at' => now(),
        ]);
        $secondBlog = Blogs::create([
            'user_id' => $author->id,
            'title' => 'Second Notification Post',
            'slug' => 'second-notification-post',
            'content' => 'Second notification content.',
            'author' => $author->username,
            'status' => Blogs::STATUS_PUBLISHED,
            'date' => now()->toDateString(),
            'published_at' => now(),
        ]);

        Notification::send($user, new BlogPublishedNotification($firstBlog));
        Notification::send($user, new BlogPublishedNotification($secondBlog));
        Notification::send($otherUser, new BlogPublishedNotification($firstBlog));
        Sanctum::actingAs($user);

        $this->patchJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.read_count', 2);

        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertSame(1, $otherUser->unreadNotifications()->count());
    }

    public function test_publishing_an_existing_draft_only_notifies_once(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $author = User::factory()->create();
        Sanctum::actingAs($admin);

        $blog = Blogs::create([
            'user_id' => $author->id,
            'title' => 'Draft Before Notification',
            'slug' => 'draft-before-notification',
            'content' => 'Draft content.',
            'author' => $author->username,
            'status' => Blogs::STATUS_DRAFT,
            'date' => now()->toDateString(),
        ]);

        $this->patchJson("/api/admin/blogs/{$blog->id}/status", [
            'status' => Blogs::STATUS_PUBLISHED,
        ])->assertOk();

        $this->assertDatabaseCount('notifications', 2);

        $this->patchJson("/api/admin/blogs/{$blog->id}/status", [
            'status' => Blogs::STATUS_PUBLISHED,
        ])->assertOk();

        $this->assertDatabaseCount('notifications', 2);
    }
}
