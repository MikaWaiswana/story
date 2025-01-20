<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\BookmarkController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('login', [UserController::class, 'login']);
Route::post('register', [UserController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [UserController::class, 'logout']);
    Route::put('update-profile', [UserController::class, 'updateProfile']);
    Route::post('update-image', [UserController::class, 'updateImage']);
    Route::put('change-password', [UserController::class, 'changePassword']);
    Route::get('user/{id}', [UserController::class, 'getUserById']);

    // Rute untuk cerita
    Route::resource('stories', StoryController::class)->except('index', 'show');
    // Rute untuk bookmark
    Route::resource('bookmarks', BookmarkController::class);
});

// Rute untuk mendapatkan cerita dengan opsi pengurutan  
Route::get('stories', [StoryController::class, 'index', 'show']);  

// Rute untuk kategori
Route::resource('categories', CategoryController::class);  