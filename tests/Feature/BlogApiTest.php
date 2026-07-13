<?php

namespace Tests\Feature;

use App\Models\Blogs;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BlogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_publish_a_blog(): void
    {
        $registerResponse = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'username' => 'jane-doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $registerResponse
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $token = $registerResponse->json('data.token');

        $createResponse = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/blogs', [
                'title' => 'Building a Full Blog API',
                'content' => 'This post explains the backend design.',
                'excerpt' => 'A backend design note.',
                'status' => 'published',
                'category_name' => 'Engineering',
                'tags' => ['Laravel', 'API'],
            ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Building a Full Blog API')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.category.name', 'Engineering');

        $this->getJson('/api/blogs')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.data.0.title', 'Building a Full Blog API');
    }

    public function test_guest_comment_requires_author_identity(): void
    {
        $user = User::factory()->create();

        $blog = Blogs::create([
            'user_id' => $user->id,
            'title' => 'Published Post',
            'slug' => 'published-post',
            'content' => 'Published content.',
            'author' => $user->username,
            'status' => Blogs::STATUS_PUBLISHED,
            'date' => now()->toDateString(),
            'published_at' => now(),
        ]);

        $this->postJson("/api/blogs/{$blog->id}/comments", [
            'content' => 'This is a guest comment.',
        ])->assertStatus(422);
    }

    public function test_author_can_upload_cover_image_when_creating_blog(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->post('/api/blogs', [
            'title' => 'Post With Cover Image',
            'content' => 'This post has an uploaded cover image.',
            'status' => Blogs::STATUS_DRAFT,
            'cover_image' => $this->fakePng('cover.png'),
        ], ['Accept' => 'application/json']);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Post With Cover Image');

        $coverImageUrl = $response->json('data.cover_image_url');

        $this->assertIsString($coverImageUrl);
        $this->assertStringContainsString('/storage/blog-images/', $coverImageUrl);
        Storage::disk('public')->assertExists($this->storagePathFromUrl($coverImageUrl));
    }

    public function test_author_can_replace_uploaded_cover_image_and_delete_it_with_blog(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);
        Storage::disk('public')->put('blog-images/old-cover.jpg', 'old image');

        $blog = Blogs::create([
            'user_id' => $user->id,
            'title' => 'Image Update Post',
            'slug' => 'image-update-post',
            'content' => 'Image update content.',
            'cover_image_url' => Storage::disk('public')->url('blog-images/old-cover.jpg'),
            'author' => $user->username,
            'status' => Blogs::STATUS_DRAFT,
            'date' => now()->toDateString(),
        ]);

        $response = $this->patch("/api/blogs/{$blog->id}", [
            'cover_image' => $this->fakePng('new-cover.png'),
        ], ['Accept' => 'application/json']);

        $response->assertOk();

        $newCoverImageUrl = $response->json('data.cover_image_url');

        $this->assertIsString($newCoverImageUrl);
        $this->assertStringContainsString('/storage/blog-images/', $newCoverImageUrl);
        Storage::disk('public')->assertMissing('blog-images/old-cover.jpg');
        Storage::disk('public')->assertExists($this->storagePathFromUrl($newCoverImageUrl));

        $this->deleteJson("/api/blogs/{$blog->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        Storage::disk('public')->assertMissing($this->storagePathFromUrl($newCoverImageUrl));
    }

    public function test_author_can_update_and_delete_own_blog(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $blog = Blogs::create([
            'user_id' => $user->id,
            'title' => 'Draft Post',
            'slug' => 'draft-post',
            'content' => 'Draft content.',
            'author' => $user->username,
            'status' => Blogs::STATUS_DRAFT,
            'date' => now()->toDateString(),
        ]);

        $this->patchJson("/api/blogs/{$blog->id}", [
            'title' => 'Updated Draft Post',
            'content' => 'Updated content.',
            'status' => 'published',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Draft Post')
            ->assertJsonPath('data.status', Blogs::STATUS_PUBLISHED);

        $this->deleteJson("/api/blogs/{$blog->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('blogs', ['id' => $blog->id]);
    }

    public function test_non_owner_cannot_update_or_delete_blog(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $blog = Blogs::create([
            'user_id' => $owner->id,
            'title' => 'Owner Post',
            'slug' => 'owner-post',
            'content' => 'Owner content.',
            'author' => $owner->username,
            'status' => Blogs::STATUS_DRAFT,
            'date' => now()->toDateString(),
        ]);

        $this->patchJson("/api/blogs/{$blog->id}", [
            'title' => 'Unauthorized Update',
        ])->assertForbidden();

        $this->deleteJson("/api/blogs/{$blog->id}")->assertForbidden();

        $this->assertDatabaseHas('blogs', [
            'id' => $blog->id,
            'title' => 'Owner Post',
        ]);
    }

    public function test_owner_can_moderate_comments(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);

        $blog = Blogs::create([
            'user_id' => $owner->id,
            'title' => 'Moderated Post',
            'slug' => 'moderated-post',
            'content' => 'Moderated content.',
            'author' => $owner->username,
            'status' => Blogs::STATUS_PUBLISHED,
            'date' => now()->toDateString(),
            'published_at' => now(),
        ]);

        $comment = Comment::create([
            'blog_id' => $blog->id,
            'author_name' => 'Reader',
            'author_email' => 'reader@example.com',
            'content' => 'Useful article.',
            'status' => Comment::STATUS_PENDING,
        ]);

        $this->patchJson("/api/comments/{$comment->id}", [
            'status' => Comment::STATUS_APPROVED,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', Comment::STATUS_APPROVED);

        $this->getJson("/api/blogs/{$blog->id}/comments")
            ->assertOk()
            ->assertJsonPath('data.data.0.content', 'Useful article.');
    }

    public function test_validation_rejects_invalid_blog_payload(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/blogs', [
            'title' => '',
            'content' => '',
            'status' => 'published-now',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content', 'status']);
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
