<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthenticatedUserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EpisodeController;
use App\Http\Controllers\PodcastController;
use App\Http\Controllers\ReadingsController;
use App\Http\Controllers\ReflectionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

include __DIR__.'/admin.php';
include __DIR__.'/contributor.php';

Route::middleware(['auth:sanctum'])->group(function () {

    Route::controller(AuthenticatedUserController::class)->prefix('profile')->name('profile.')->group(function () {
        Route::get('/', 'show')->name('show');
        Route::put('/', 'update')->name('update');
        Route::delete('/', 'destroy')->name('destroy');
    });
});

Route::apiResource('articles', ArticleController::class)->only(['index', 'show'])->whereUuid('article');
Route::apiResource('podcasts', PodcastController::class)->only(['index', 'show'])->whereUuid('podcast');
Route::apiResource('podcasts.episodes', EpisodeController::class)->only(['index', 'show'])->shallow()->whereUuid(['podcast', 'episode']);
Route::apiResource('categories', CategoryController::class)->only(['index']);
Route::get('readings/{date}', [ReadingsController::class, 'show'])->name('readings.show');
Route::get('reflections', [ReflectionController::class, 'index'])->name('reflections.index');
Route::get('reflections/{date}', [ReflectionController::class, 'show'])->name('reflections.show');
