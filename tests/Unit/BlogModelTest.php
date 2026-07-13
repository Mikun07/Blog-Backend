<?php

namespace Tests\Unit;

use App\Models\Blogs;
use App\Models\Comment;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class BlogModelTest extends TestCase
{
    public function test_blog_is_owned_when_user_id_matches(): void
    {
        $user = new User(['username' => 'jane']);
        $user->id = 10;

        $blog = new Blogs(['author' => 'legacy-author']);
        $blog->user_id = 10;

        $this->assertTrue($blog->isOwnedBy($user));
    }

    public function test_blog_is_owned_when_legacy_author_matches_username(): void
    {
        $user = new User(['username' => 'legacy-author']);
        $user->id = 10;

        $blog = new Blogs(['author' => 'legacy-author']);
        $blog->user_id = null;

        $this->assertTrue($blog->isOwnedBy($user));
    }

    public function test_blog_is_not_owned_by_unrelated_user(): void
    {
        $user = new User(['username' => 'other-author']);
        $user->id = 22;

        $blog = new Blogs(['author' => 'legacy-author']);
        $blog->user_id = 10;

        $this->assertFalse($blog->isOwnedBy($user));
    }

    public function test_blog_status_constants_match_api_contract(): void
    {
        $this->assertSame('draft', Blogs::STATUS_DRAFT);
        $this->assertSame('published', Blogs::STATUS_PUBLISHED);
        $this->assertSame('archived', Blogs::STATUS_ARCHIVED);
    }

    public function test_user_admin_role_identifies_admin_access(): void
    {
        $admin = new User(['role' => User::ROLE_ADMIN]);
        $author = new User(['role' => User::ROLE_AUTHOR]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($author->isAdmin());
    }

    public function test_blog_model_allows_full_blog_fields(): void
    {
        $blog = new Blogs();

        $this->assertContains('user_id', $blog->getFillable());
        $this->assertContains('category_id', $blog->getFillable());
        $this->assertContains('slug', $blog->getFillable());
        $this->assertContains('excerpt', $blog->getFillable());
        $this->assertContains('cover_image_url', $blog->getFillable());
        $this->assertContains('status', $blog->getFillable());
        $this->assertContains('published_at', $blog->getFillable());
    }

    public function test_comment_hides_author_email_from_serialized_output(): void
    {
        $comment = new Comment([
            'author_name' => 'Reader',
            'author_email' => 'reader@example.com',
            'content' => 'Helpful post.',
        ]);

        $this->assertContains('author_email', $comment->getHidden());
    }
}
