<?php

use App\Http\Controllers\ApiDocumentationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs/api', [ApiDocumentationController::class, 'index'])->name('api.docs');
Route::get('/docs/api/openapi.json', [ApiDocumentationController::class, 'openApi'])->name('api.docs.openapi');
