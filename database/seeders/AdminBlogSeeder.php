<?php

namespace Database\Seeders;

use App\Models\Blogs;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class AdminBlogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()
            ->where('email', (string) config('admin.email'))
            ->orWhere('username', (string) config('admin.username'))
            ->orWhere('role', User::ROLE_ADMIN)
            ->first();

        if (! $admin) {
            throw new RuntimeException('Run AdminUserSeeder before AdminBlogSeeder.');
        }

        $category = Category::query()->firstOrCreate(
            ['slug' => 'engineering'],
            [
                'name' => 'Engineering',
                'description' => 'Practical notes from building and maintaining software.',
            ],
        );

        $tagIds = collect(['Laravel', 'API', 'Backend'])
            ->map(fn (string $tag) => Tag::query()->firstOrCreate(
                ['slug' => Str::slug($tag)],
                ['name' => $tag],
            )->id)
            ->all();

        $posts = [
            [
                'title' => 'Designing a Clean Blog API',
                'excerpt' => 'How a small set of resource routes, validation rules, and response shapes can make a blog API easier to maintain.',
                'content' => 'A clean blog API starts with predictable endpoints, focused request validation, and response payloads that remain consistent across success and error states. Keeping those contracts stable makes the frontend easier to build and gives future contributors a reliable surface to extend.',
            ],
            [
                'title' => 'Admin Workflows That Stay Simple',
                'excerpt' => 'A practical look at keeping admin publishing flows clear, auditable, and easy to recover from.',
                'content' => 'Admin workflows benefit from simple ownership rules, clear status transitions, and repeatable seed data. Draft, published, and archived states cover most publishing needs without making moderation or recovery feel complicated.',
            ],
            [
                'title' => 'Using Seeders for Reliable Local Data',
                'excerpt' => 'Seeders give every developer a known starting point when building and testing backend features.',
                'content' => 'Reliable local data helps teams move faster. A seeder can create realistic users, posts, categories, and tags in a way that is safe to rerun, which makes demos, development, and regression checks much less fragile.',
            ],
            [
                'title' => 'Making Blog Content Discoverable',
                'excerpt' => 'Categories, tags, slugs, and excerpts all work together to help readers find the right article.',
                'content' => 'Discoverability is not only a frontend concern. Storing normalized categories and tags, generating stable slugs, and writing useful excerpts gives the API enough structure to support search, filtering, sharing, and detail pages.',
            ],
            [
                'title' => 'Keeping Backend Readiness Visible',
                'excerpt' => 'Readiness docs, tests, and deployment notes make it easier to understand what is production-ready and what still needs attention.',
                'content' => 'Engineering readiness improves when important operational details are visible. Documented setup steps, API behavior, known risks, and verification commands help future maintainers understand the system quickly and make safer changes.',
            ],
        ];

        foreach ($posts as $index => $post) {
            $publishedAt = now()->subDays(count($posts) - $index);
            $slug = Str::slug($post['title']);

            $blog = Blogs::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'user_id' => $admin->id,
                    'category_id' => $category->id,
                    'title' => $post['title'],
                    'excerpt' => $post['excerpt'],
                    'content' => $post['content'],
                    'cover_image_url' => null,
                    'author' => $admin->username,
                    'status' => Blogs::STATUS_PUBLISHED,
                    'date' => $publishedAt->toDateString(),
                    'published_at' => $publishedAt,
                ],
            );

            $blog->tags()->sync($tagIds);
        }
    }
}
