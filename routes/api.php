<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use App\Support\RoutePaths;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('health', fn () => response()->json(['status' => 'ok']));

Route::post('auth/register', [UserController::class, 'register'])->middleware('throttle:auth');
Route::post('auth/login', [UserController::class, 'login'])->middleware('throttle:auth');

Route::get('blogs', [BlogController::class, 'index']);
Route::get(RoutePaths::BLOG, [BlogController::class, 'show']);
Route::get(RoutePaths::BLOG . '/comments', [BlogController::class, 'comments']);
Route::post(RoutePaths::BLOG . '/comments', [BlogController::class, 'storeComment']);

Route::get('categories', [CategoryController::class, 'index']);
Route::get('tags', [TagController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [UserController::class, 'getUser']);
    Route::patch('auth/me', [UserController::class, 'updateProfile']);
    Route::post('auth/logout', [UserController::class, 'logout']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);

    Route::get('me/blogs', [BlogController::class, 'mine']);
    Route::post('blogs', [BlogController::class, 'store']);
    Route::put(RoutePaths::BLOG, [BlogController::class, 'update']);
    Route::patch(RoutePaths::BLOG, [BlogController::class, 'update']);
    Route::delete(RoutePaths::BLOG, [BlogController::class, 'destroy']);

    Route::post('categories', [CategoryController::class, 'store']);
    Route::post('tags', [TagController::class, 'store']);

    Route::patch(RoutePaths::COMMENT, [BlogController::class, 'moderateComment']);
    Route::delete(RoutePaths::COMMENT, [BlogController::class, 'deleteComment']);

    Route::get('user', fn (Request $request) => $request->user());

    Route::get('getUser', [UserController::class, 'getUser']);
    Route::post('addBlog', [BlogController::class, 'store']);
    Route::get('listBlogs', [BlogController::class, 'mine']);
    Route::put('editBlog', [BlogController::class, 'legacyUpdate']);
    Route::post('deleteBlog', [BlogController::class, 'legacyDestroy']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('dashboard', [AdminController::class, 'dashboard']);
        Route::get('users', [AdminController::class, 'users']);
        Route::post('users', [AdminController::class, 'createUser']);
        Route::get('users/{user}', [AdminController::class, 'showUser']);
        Route::get('users/{user}/history', [AdminController::class, 'userHistory']);
        Route::patch('users/{user}', [AdminController::class, 'updateUser']);
        Route::delete('users/{user}', [AdminController::class, 'deleteUser']);
        Route::patch('users/{user}/role', [AdminController::class, 'updateUserRole']);
        Route::patch('users/{user}/status', [AdminController::class, 'updateUserStatus']);
        Route::get('blogs', [AdminController::class, 'blogs']);
        Route::post('blogs', [AdminController::class, 'createBlog']);
        Route::get(RoutePaths::BLOG, [AdminController::class, 'showBlog']);
        Route::patch(RoutePaths::BLOG . '/status', [AdminController::class, 'updateBlogStatus']);
        Route::delete(RoutePaths::BLOG, [AdminController::class, 'deleteBlog']);
        Route::get('comments', [AdminController::class, 'comments']);
        Route::patch(RoutePaths::COMMENT, [AdminController::class, 'updateCommentStatus']);
        Route::delete(RoutePaths::COMMENT, [AdminController::class, 'deleteComment']);
    });
});

Route::post('register', [UserController::class, 'register'])->middleware('throttle:auth');
Route::post('login', [UserController::class, 'login'])->middleware('throttle:auth');
Route::get('allBlogs', [BlogController::class, 'index']);
