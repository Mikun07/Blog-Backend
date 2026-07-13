<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBlogRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateBlogRequest;
use App\Models\Blogs;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $blogs = Blogs::query()
            ->with(['authorUser:id,name,username', 'category:id,name,slug', 'tags:id,name,slug'])
            ->withCount([
                'comments as approved_comments_count' => fn ($query) => $query->where('status', Comment::STATUS_APPROVED),
            ])
            ->published()
            ->when($request->query('search'), function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->when($request->query('category'), function ($query, string $category) {
                $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('slug', $category));
            })
            ->when($request->query('tag'), function ($query, string $tag) {
                $query->whereHas('tags', fn ($tagQuery) => $tagQuery->where('slug', $tag));
            })
            ->latest('published_at')
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json([
            'success' => true,
            'message' => 'Published blogs retrieved.',
            'data' => $blogs,
        ]);
    }

    public function mine(Request $request)
    {
        $blogs = Blogs::query()
            ->with(['category:id,name,slug', 'tags:id,name,slug'])
            ->where(function ($query) use ($request) {
                $query->where('user_id', $request->user()->id)
                    ->orWhere('author', $request->user()->username);
            })
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json([
            'success' => true,
            'message' => 'Your blogs retrieved.',
            'data' => $blogs,
        ]);
    }

    public function show(string $blog)
    {
        $blog = Blogs::query()
            ->with([
                'authorUser:id,name,username',
                'category:id,name,slug',
                'tags:id,name,slug',
                'comments' => fn ($query) => $query
                    ->where('status', Comment::STATUS_APPROVED)
                    ->latest()
                    ->select('id', 'blog_id', 'user_id', 'author_name', 'content', 'status', 'created_at'),
            ])
            ->where(function ($query) use ($blog) {
                $query->where('slug', $blog);

                if (is_numeric($blog)) {
                    $query->orWhere('id', $blog);
                }
            })
            ->published()
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Blog retrieved.',
            'data' => $blog,
        ]);
    }

    public function store(StoreBlogRequest $request)
    {
        $status = $request->validated('status', Blogs::STATUS_DRAFT);
        $publishedAt = $this->publishedAt($request, $status);

        $blog = Blogs::create([
            'user_id' => $request->user()->id,
            'category_id' => $this->categoryId($request),
            'title' => $request->validated('title'),
            'slug' => $this->uniqueSlug($request->validated('title')),
            'excerpt' => $request->validated('excerpt'),
            'content' => $request->validated('content'),
            'cover_image_url' => $request->validated('cover_image_url'),
            'author' => $request->user()->username,
            'status' => $status,
            'date' => $request->validated('date') ?? optional($publishedAt)->toDateString() ?? now()->toDateString(),
            'published_at' => $publishedAt,
        ]);

        $this->syncTags($blog, $request->validated('tags') ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Blog created.',
            'data' => $blog->load(['authorUser:id,name,username', 'category:id,name,slug', 'tags:id,name,slug']),
        ], 201);
    }

    public function update(UpdateBlogRequest $request, Blogs $blog)
    {
        if (! $blog->isOwnedBy($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this blog.',
                'data' => null,
            ], 403);
        }

        $status = $request->validated('status', $blog->status);
        $title = $request->validated('title', $blog->title);

        $publishedAt = $this->publishedAt($request, $status);

        if ($request->has('status') && $status !== Blogs::STATUS_PUBLISHED) {
            $publishedAt = null;
        }

        $blog->fill([
            'category_id' => $this->categoryId($request) ?? $blog->category_id,
            'title' => $title,
            'slug' => $title !== $blog->title ? $this->uniqueSlug($title, $blog->id) : $blog->slug,
            'excerpt' => $request->validated('excerpt', $blog->excerpt),
            'content' => $request->validated('content', $blog->content),
            'cover_image_url' => $request->validated('cover_image_url', $blog->cover_image_url),
            'status' => $status,
            'date' => $request->validated('date', $blog->date),
            'published_at' => $request->has('published_at') || $request->has('status')
                ? $publishedAt
                : $blog->published_at,
        ]);
        $blog->save();

        if ($request->has('tags')) {
            $this->syncTags($blog, $request->validated('tags') ?? []);
        }

        return response()->json([
            'success' => true,
            'message' => 'Blog updated.',
            'data' => $blog->load(['authorUser:id,name,username', 'category:id,name,slug', 'tags:id,name,slug']),
        ]);
    }

    public function destroy(Request $request, Blogs $blog)
    {
        if (! $blog->isOwnedBy($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this blog.',
                'data' => null,
            ], 403);
        }

        $blog->delete();

        return response()->json([
            'success' => true,
            'message' => 'Blog deleted.',
            'data' => null,
        ]);
    }

    public function comments(Blogs $blog)
    {
        return response()->json([
            'success' => true,
            'message' => 'Approved comments retrieved.',
            'data' => $blog->comments()
                ->where('status', Comment::STATUS_APPROVED)
                ->latest()
                ->paginate(20),
        ]);
    }

    public function storeComment(StoreCommentRequest $request, Blogs $blog)
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if (! $user && (! $request->filled('author_name') || ! $request->filled('author_email'))) {
            return response()->json([
                'success' => false,
                'message' => 'Guest comments require author name and author email.',
                'errors' => [
                    'author_name' => ['Author name is required for guest comments.'],
                    'author_email' => ['Author email is required for guest comments.'],
                ],
            ], 422);
        }

        $comment = $blog->comments()->create([
            'user_id' => $user?->id,
            'author_name' => $user?->name ?? $request->validated('author_name'),
            'author_email' => $user?->email ?? $request->validated('author_email'),
            'content' => $request->validated('content'),
            'status' => Comment::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comment submitted for review.',
            'data' => $comment,
        ], 201);
    }

    public function moderateComment(Request $request, Comment $comment)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                Comment::STATUS_PENDING,
                Comment::STATUS_APPROVED,
                Comment::STATUS_REJECTED,
            ])],
        ]);

        if (! $comment->blog->isOwnedBy($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to moderate this comment.',
                'data' => null,
            ], 403);
        }

        $comment->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Comment status updated.',
            'data' => $comment,
        ]);
    }

    public function deleteComment(Request $request, Comment $comment)
    {
        if (! $comment->blog->isOwnedBy($request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this comment.',
                'data' => null,
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted.',
            'data' => null,
        ]);
    }

    public function legacyUpdate(UpdateBlogRequest $request)
    {
        $blog = Blogs::findOrFail($request->validated('id'));

        return $this->update($request, $blog);
    }

    public function legacyDestroy(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:blogs,id'],
        ]);

        $blog = Blogs::findOrFail($validated['id']);

        return $this->destroy($request, $blog);
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->query('per_page', 10), 1), 50);
    }

    private function categoryId(Request $request): ?int
    {
        if ($request->filled('category_id')) {
            return (int) $request->input('category_id');
        }

        if (! $request->filled('category_name')) {
            return null;
        }

        $name = trim($request->input('category_name'));

        return Category::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name]
        )->id;
    }

    /**
     * @param array<int, string> $tags
     */
    private function syncTags(Blogs $blog, array $tags): void
    {
        $tagIds = collect($tags)
            ->filter()
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->unique(fn (string $tag) => Str::slug($tag))
            ->map(function (string $tag) {
                return Tag::firstOrCreate(
                    ['slug' => Str::slug($tag)],
                    ['name' => $tag]
                )->id;
            })
            ->values()
            ->all();

        $blog->tags()->sync($tagIds);
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title) ?: Str::random(8);
        $slug = $baseSlug;
        $counter = 2;

        while (Blogs::where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function publishedAt(Request $request, string $status)
    {
        if ($request->filled('published_at')) {
            return $request->date('published_at');
        }

        if ($status === Blogs::STATUS_PUBLISHED) {
            return now();
        }

        return null;
    }
}
