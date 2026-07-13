<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('health', fn () => response()->json(['status' => 'ok']));

Route::post('auth/register', [UserController::class, 'register'])->middleware('throttle:auth');
Route::post('auth/login', [UserController::class, 'login'])->middleware('throttle:auth');

Route::get('blogs', [BlogController::class, 'index']);
Route::get('blogs/{blog}', [BlogController::class, 'show']);
Route::get('blogs/{blog}/comments', [BlogController::class, 'comments']);
Route::post('blogs/{blog}/comments', [BlogController::class, 'storeComment']);

Route::get('categories', [CategoryController::class, 'index']);
Route::get('tags', [TagController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/me', [UserController::class, 'getUser']);
    Route::post('auth/logout', [UserController::class, 'logout']);

    Route::get('me/blogs', [BlogController::class, 'mine']);
    Route::post('blogs', [BlogController::class, 'store']);
    Route::put('blogs/{blog}', [BlogController::class, 'update']);
    Route::patch('blogs/{blog}', [BlogController::class, 'update']);
    Route::delete('blogs/{blog}', [BlogController::class, 'destroy']);

    Route::post('categories', [CategoryController::class, 'store']);
    Route::post('tags', [TagController::class, 'store']);

    Route::patch('comments/{comment}', [BlogController::class, 'moderateComment']);
    Route::delete('comments/{comment}', [BlogController::class, 'deleteComment']);

    Route::get('user', fn (Request $request) => $request->user());

    Route::get('getUser', [UserController::class, 'getUser']);
    Route::post('addBlog', [BlogController::class, 'store']);
    Route::get('listBlogs', [BlogController::class, 'mine']);
    Route::put('editBlog', [BlogController::class, 'legacyUpdate']);
    Route::post('deleteBlog', [BlogController::class, 'legacyDestroy']);
});

Route::post('register', [UserController::class, 'register'])->middleware('throttle:auth');
Route::post('login', [UserController::class, 'login'])->middleware('throttle:auth');
Route::get('allBlogs', [BlogController::class, 'index']);
