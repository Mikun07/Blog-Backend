<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BlogController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('register',[UserController::class,'register']);
Route::post('login',[UserController::class,'login']);
Route::get('getUser',[UserController::class,'getUser']);
Route::post('addBlog',[BlogController::class,'addBlog']);
Route::get('listBlogs',[BlogController::class,'listBlogs']);
Route::put('editBlog',[BlogController::class,'editBlog']);
Route::post('deleteBlog',[BlogController::class,'deleteBlog']);
Route::get('allBlogs',[BlogController::class,'allBlogs']);