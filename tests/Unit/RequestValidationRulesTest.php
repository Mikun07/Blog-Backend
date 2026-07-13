<?php

namespace Tests\Unit;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\StoreBlogRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateBlogRequest;
use PHPUnit\Framework\TestCase;

class RequestValidationRulesTest extends TestCase
{
    public function test_register_request_requires_identity_and_secure_password(): void
    {
        $rules = (new RegisterRequest())->rules();

        $this->assertRuleContains($rules, 'name', 'required');
        $this->assertRuleContains($rules, 'username', 'required');
        $this->assertRuleContains($rules, 'username', 'alpha_dash');
        $this->assertRuleContains($rules, 'email', 'email');
        $this->assertRuleContains($rules, 'password', 'min:8');
    }

    public function test_login_request_requires_email_and_password(): void
    {
        $rules = (new LoginRequest())->rules();

        $this->assertRuleContains($rules, 'email', 'required');
        $this->assertRuleContains($rules, 'email', 'email');
        $this->assertRuleContains($rules, 'password', 'required');
    }

    public function test_store_blog_request_requires_title_and_content(): void
    {
        $rules = (new StoreBlogRequest())->rules();

        $this->assertRuleContains($rules, 'title', 'required');
        $this->assertRuleContains($rules, 'content', 'required');
        $this->assertRuleContains($rules, 'tags', 'array');
        $this->assertRuleContains($rules, 'published_at', 'date');
    }

    public function test_update_blog_request_accepts_legacy_id_and_partial_updates(): void
    {
        $rules = (new UpdateBlogRequest())->rules();

        $this->assertRuleContains($rules, 'id', 'exists:blogs,id');
        $this->assertRuleContains($rules, 'title', 'sometimes');
        $this->assertRuleContains($rules, 'content', 'sometimes');
    }

    public function test_store_comment_request_requires_content(): void
    {
        $rules = (new StoreCommentRequest())->rules();

        $this->assertRuleContains($rules, 'content', 'required');
        $this->assertRuleContains($rules, 'content', 'max:2000');
        $this->assertRuleContains($rules, 'author_email', 'email');
    }

    /**
     * @param array<string, mixed> $rules
     */
    private function assertRuleContains(array $rules, string $field, string $expectedRule): void
    {
        $this->assertArrayHasKey($field, $rules);
        $this->assertContains($expectedRule, $rules[$field]);
    }
}
