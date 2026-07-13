<?php

namespace App\Http\Controllers;

use App\Models\Blogs;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
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
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved.',
            'data' => $users,
        ]);
    }

    public function updateUserRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_AUTHOR, User::ROLE_ADMIN])],
        ]);

        if ($user->isAdmin() && $validated['role'] !== User::ROLE_ADMIN && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'At least one admin account must remain.',
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

    public function updateBlogStatus(Request $request, Blogs $blog)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                Blogs::STATUS_DRAFT,
                Blogs::STATUS_PUBLISHED,
                Blogs::STATUS_ARCHIVED,
            ])],
        ]);

        $blog->status = $validated['status'];

        if ($blog->status === Blogs::STATUS_PUBLISHED && ! $blog->published_at) {
            $blog->published_at = now();
            $blog->date = $blog->date ?? now()->toDateString();
        }

        if ($blog->status !== Blogs::STATUS_PUBLISHED) {
            $blog->published_at = null;
        }

        $blog->save();

        return response()->json([
            'success' => true,
            'message' => 'Blog status updated.',
            'data' => $blog->fresh(['authorUser:id,name,username,email,role', 'category:id,name,slug', 'tags:id,name,slug']),
        ]);
    }

    public function deleteBlog(Blogs $blog)
    {
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
}
