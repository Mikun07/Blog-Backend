<?php

namespace App\Http\Controllers;

use App\Models\Blogs;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Tag;
use App\Models\User;
use App\Services\BlogImageService;
use App\Services\BlogNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'success' => true,
            'message' => 'Admin dashboard metrics retrieved.',
            'data' => [
                'users' => [
                    'total' => User::count(),
                    'admins' => User::where('role', User::ROLE_ADMIN)->count(),
                    'authors' => User::where('role', User::ROLE_AUTHOR)->count(),
                    'active' => User::where('status', User::STATUS_ACTIVE)->count(),
                    'suspended' => User::where('status', User::STATUS_SUSPENDED)->count(),
                ],
                'blogs' => [
                    'total' => Blogs::count(),
                    'draft' => Blogs::where('status', Blogs::STATUS_DRAFT)->count(),
                    'published' => Blogs::where('status', Blogs::STATUS_PUBLISHED)->count(),
                    'archived' => Blogs::where('status', Blogs::STATUS_ARCHIVED)->count(),
                ],
                'comments' => [
                    'total' => Comment::count(),
                    'pending' => Comment::where('status', Comment::STATUS_PENDING)->count(),
                    'approved' => Comment::where('status', Comment::STATUS_APPROVED)->count(),
                    'rejected' => Comment::where('status', Comment::STATUS_REJECTED)->count(),
                ],
                'taxonomy' => [
                    'categories' => Category::count(),
                    'tags' => Tag::count(),
                ],
            ],
        ]);
    }

    public function users(Request $request)
    {
        $users = User::query()
            ->withCount(['blogs', 'comments'])
            ->when($request->query('role'), fn ($query, string $role) => $query->where('role', $role))
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved.',
            'data' => $users,
        ]);
    }

    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', Rule::in([User::ROLE_AUTHOR, User::ROLE_ADMIN])],
            'status' => ['nullable', Rule::in([User::STATUS_ACTIVE, User::STATUS_SUSPENDED])],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? User::ROLE_AUTHOR,
            'status' => $validated['status'] ?? User::STATUS_ACTIVE,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created by admin.',
            'data' => $user,
        ], 201);
    }

    public function showUser(User $user)
    {
        return response()->json([
            'success' => true,
            'message' => 'User details retrieved.',
            'data' => [
                'user' => $user->loadCount(['blogs', 'comments']),
                'recent_blogs' => $user->blogs()
                    ->with(['category:id,name,slug', 'tags:id,name,slug'])
                    ->withCount('comments')
                    ->latest()
                    ->limit(5)
                    ->get(),
                'recent_comments' => $user->comments()
                    ->with('blog:id,title,slug,status')
                    ->latest()
                    ->limit(5)
                    ->get(),
            ],
        ]);
    }

    public function userHistory(Request $request, User $user)
    {
        return response()->json([
            'success' => true,
            'message' => 'User history retrieved.',
            'data' => [
                'user' => $user->loadCount(['blogs', 'comments']),
                'blogs' => $user->blogs()
                    ->with(['category:id,name,slug', 'tags:id,name,slug'])
                    ->withCount('comments')
                    ->latest()
                    ->paginate($this->perPage($request), ['*'], 'blogs_page'),
                'comments' => $user->comments()
                    ->with('blog:id,title,slug,status')
                    ->latest()
                    ->paginate($this->perPage($request), ['*'], 'comments_page'),
            ],
        ]);
    }

    public function updateUserRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_AUTHOR, User::ROLE_ADMIN])],
        ]);

        if ($this->wouldRemoveLastActiveAdmin($user, $validated['role'], $user->status)) {
            return response()->json([
                'success' => false,
                'message' => 'At least one active admin account must remain.',
                'data' => null,
            ], 422);
        }

        $user->update(['role' => $validated['role']]);

        return response()->json([
            'success' => true,
            'message' => 'User role updated.',
            'data' => $user->fresh(),
        ]);
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'username' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'role' => ['sometimes', 'required', Rule::in([User::ROLE_AUTHOR, User::ROLE_ADMIN])],
            'status' => ['sometimes', 'required', Rule::in([User::STATUS_ACTIVE, User::STATUS_SUSPENDED])],
        ]);

        $nextRole = $validated['role'] ?? $user->role;
        $nextStatus = $validated['status'] ?? $user->status;

        if ($request->user()->is($user) && $nextStatus === User::STATUS_SUSPENDED) {
            return response()->json([
                'success' => false,
                'message' => 'Admins cannot suspend their own account.',
                'data' => null,
            ], 422);
        }

        if ($this->wouldRemoveLastActiveAdmin($user, $nextRole, $nextStatus)) {
            return response()->json([
                'success' => false,
                'message' => 'At least one active admin account must remain.',
                'data' => null,
            ], 422);
        }

        if (array_key_exists('password', $validated)) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated by admin.',
            'data' => $user->fresh(),
        ]);
    }

    public function updateUserStatus(Request $request, User $user)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([User::STATUS_ACTIVE, User::STATUS_SUSPENDED])],
        ]);

        if ($request->user()->is($user) && $validated['status'] === User::STATUS_SUSPENDED) {
            return response()->json([
                'success' => false,
                'message' => 'Admins cannot suspend their own account.',
                'data' => null,
            ], 422);
        }

        if ($this->wouldRemoveLastActiveAdmin($user, $user->role, $validated['status'])) {
            return response()->json([
                'success' => false,
                'message' => 'At least one active admin account must remain.',
                'data' => null,
            ], 422);
        }

        $user->update(['status' => $validated['status']]);

        if ($user->isSuspended()) {
            $user->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'User status updated by admin.',
            'data' => $user->fresh(),
        ]);
    }

    public function deleteUser(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Admins cannot delete their own account.',
                'data' => null,
            ], 422);
        }

        if ($this->wouldRemoveLastActiveAdmin($user, User::ROLE_AUTHOR, User::STATUS_SUSPENDED)) {
            return response()->json([
                'success' => false,
                'message' => 'At least one active admin account must remain.',
                'data' => null,
            ], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted by admin.',
            'data' => null,
        ]);
    }

    public function blogs(Request $request)
    {
        $blogs = Blogs::query()
            ->with(['authorUser:id,name,username,email,role', 'category:id,name,slug', 'tags:id,name,slug'])
            ->withCount('comments')
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('search'), function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json([
            'success' => true,
            'message' => 'Admin blogs retrieved.',
            'data' => $blogs,
        ]);
    }

    public function createBlog(Request $request, BlogNotificationService $notifications, BlogImageService $images)
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'cover_image_url' => ['nullable', 'url', 'max:255'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_name' => ['nullable', 'string', 'max:100'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
            'status' => ['nullable', Rule::in([
                Blogs::STATUS_DRAFT,
                Blogs::STATUS_PUBLISHED,
                Blogs::STATUS_ARCHIVED,
            ])],
            'published_at' => ['nullable', 'date'],
            'date' => ['nullable', 'date'],
        ]);

        $author = User::findOrFail($validated['user_id'] ?? $request->user()->id);
        $status = $validated['status'] ?? Blogs::STATUS_DRAFT;
        $publishedAt = $this->publishedAt($request, $status);
        $coverImageUrl = $request->hasFile('cover_image')
            ? $images->store($request->file('cover_image'))
            : ($validated['cover_image_url'] ?? null);

        $blog = Blogs::create([
            'user_id' => $author->id,
            'category_id' => $this->categoryId($request),
            'title' => $validated['title'],
            'slug' => $this->uniqueSlug($validated['title']),
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'],
            'cover_image_url' => $coverImageUrl,
            'author' => $author->username,
            'status' => $status,
            'date' => $validated['date'] ?? optional($publishedAt)->toDateString() ?? now()->toDateString(),
            'published_at' => $publishedAt,
        ]);

        $this->syncTags($blog, $validated['tags'] ?? []);
        $notifications->notifyPublished($blog);

        return response()->json([
            'success' => true,
            'message' => 'Blog created by admin.',
            'data' => $blog->load(['authorUser:id,name,username,email,role', 'category:id,name,slug', 'tags:id,name,slug']),
        ], 201);
    }

    public function showBlog(Blogs $blog)
    {
        return response()->json([
            'success' => true,
            'message' => 'Admin blog details retrieved.',
            'data' => $blog->load([
                'authorUser:id,name,username,email,role',
                'category:id,name,slug',
                'tags:id,name,slug',
                'comments' => fn ($query) => $query
                    ->with('user:id,name,username,email,role')
                    ->latest(),
            ])->loadCount('comments'),
        ]);
    }

    public function updateBlogStatus(Request $request, Blogs $blog, BlogNotificationService $notifications)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                Blogs::STATUS_DRAFT,
                Blogs::STATUS_PUBLISHED,
                Blogs::STATUS_ARCHIVED,
            ])],
        ]);

        $wasPublished = $blog->status === Blogs::STATUS_PUBLISHED;
        $blog->status = $validated['status'];

        if ($blog->status === Blogs::STATUS_PUBLISHED && ! $blog->published_at) {
            $blog->published_at = now();
            $blog->date = $blog->date ?? now()->toDateString();
        }

        if ($blog->status !== Blogs::STATUS_PUBLISHED) {
            $blog->published_at = null;
        }

        $blog->save();

        if (! $wasPublished && $blog->status === Blogs::STATUS_PUBLISHED) {
            $notifications->notifyPublished($blog);
        }

        return response()->json([
            'success' => true,
            'message' => 'Blog status updated.',
            'data' => $blog->fresh(['authorUser:id,name,username,email,role', 'category:id,name,slug', 'tags:id,name,slug']),
        ]);
    }

    public function deleteBlog(Blogs $blog, BlogImageService $images)
    {
        $images->delete($blog->cover_image_url);
        $blog->delete();

        return response()->json([
            'success' => true,
            'message' => 'Blog deleted by admin.',
            'data' => null,
        ]);
    }

    public function comments(Request $request)
    {
        $comments = Comment::query()
            ->with(['blog:id,title,slug,user_id,author,status', 'user:id,name,username,email,role'])
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json([
            'success' => true,
            'message' => 'Admin comments retrieved.',
            'data' => $comments,
        ]);
    }

    public function updateCommentStatus(Request $request, Comment $comment)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                Comment::STATUS_PENDING,
                Comment::STATUS_APPROVED,
                Comment::STATUS_REJECTED,
            ])],
        ]);

        $comment->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Comment status updated by admin.',
            'data' => $comment->fresh(['blog:id,title,slug,user_id,author,status', 'user:id,name,username,email,role']),
        ]);
    }

    public function deleteComment(Comment $comment)
    {
        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted by admin.',
            'data' => null,
        ]);
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->query('per_page', 10), 1), 50);
    }

    private function wouldRemoveLastActiveAdmin(User $user, string $nextRole, string $nextStatus): bool
    {
        return $user->isAdmin()
            && $user->isActive()
            && ($nextRole !== User::ROLE_ADMIN || $nextStatus !== User::STATUS_ACTIVE)
            && User::where('role', User::ROLE_ADMIN)
                ->where('status', User::STATUS_ACTIVE)
                ->count() <= 1;
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

    private function uniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title) ?: Str::random(8);
        $slug = $baseSlug;
        $counter = 2;

        while (Blogs::where('slug', $slug)->exists()) {
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
