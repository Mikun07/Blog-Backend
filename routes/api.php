<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

const COMMENT_ROUTE = 'comments/{comment}';
const BLOG_ROUTE = 'blogs/{blog}';

Route::get('health', fn () => response()->json(['status' => 'ok']));

Route::post('auth/register', [UserController::class, 'register'])->middleware('throttle:auth');
Route::post('auth/login', [UserController::class, 'login'])->middleware('throttle:auth');

Route::get('blogs', [BlogController::class, 'index']);
Route::get(BLOG_ROUTE, [BlogController::class, 'show']);
Route::get(BLOG_ROUTE.'/comments', [BlogController::class, 'comments']);
Route::post(BLOG_ROUTE.'/comments', [BlogController::class, 'storeComment']);

Route::get('categories', [CategoryController::class, 'index']);
Route::get('tags', [TagController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [UserController::class, 'getUser']);
    Route::post('auth/logout', [UserController::class, 'logout']);

    Route::get('me/blogs', [BlogController::class, 'mine']);
    Route::post('blogs', [BlogController::class, 'store']);
    Route::put(BLOG_ROUTE, [BlogController::class, 'update']);
    Route::patch(BLOG_ROUTE, [BlogController::class, 'update']);
    Route::delete(BLOG_ROUTE, [BlogController::class, 'destroy']);

    Route::post('categories', [CategoryController::class, 'store']);
    Route::post('tags', [TagController::class, 'store']);

    Route::patch(COMMENT_ROUTE, [BlogController::class, 'moderateComment']);
    Route::delete(COMMENT_ROUTE, [BlogController::class, 'deleteComment']);

    Route::get('user', fn (Request $request) => $request->user());

    Route::get('getUser', [UserController::class, 'getUser']);
    Route::post('addBlog', [BlogController::class, 'store']);
    Route::get('listBlogs', [BlogController::class, 'mine']);
    Route::put('editBlog', [BlogController::class, 'legacyUpdate']);
    Route::post('deleteBlog', [BlogController::class, 'legacyDestroy']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('dashboard', [AdminController::class, 'dashboard']);
        Route::get('users', [AdminController::class, 'users']);
        Route::patch('users/{user}/role', [AdminController::class, 'updateUserRole']);
        Route::get('blogs', [AdminController::class, 'blogs']);
        Route::patch(BLOG_ROUTE.'/status', [AdminController::class, 'updateBlogStatus']);
        Route::delete(BLOG_ROUTE, [AdminController::class, 'deleteBlog']);
        Route::get('comments', [AdminController::class, 'comments']);
        Route::patch(COMMENT_ROUTE, [AdminController::class, 'updateCommentStatus']);
        Route::delete(COMMENT_ROUTE, [AdminController::class, 'deleteComment']);
    });
});

Route::post('register', [UserController::class, 'register'])->middleware('throttle:auth');
Route::post('login', [UserController::class, 'login'])->middleware('throttle:auth');
Route::get('allBlogs', [BlogController::class, 'index']);
