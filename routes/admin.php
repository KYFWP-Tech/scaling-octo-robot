<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\EpisodeController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PodcastController;
use App\Http\Controllers\Admin\ReflectionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {

    Route::prefix('admins')->name('admins.')->group(function () {
        Route::apiResource('users', UserController::class)->except(['store'])->whereUuid('user');
        Route::post('accept-invitation', [AdminController::class, 'acceptInvitation'])->name('accept-invitation');
        Route::put('{admin}/role', [AdminController::class, 'assignRole'])->name('assign-role');
        Route::apiResource('roles', RoleController::class)->except(['store', 'destroy']);
        Route::apiResource('permissions', PermissionController::class)->except(['store', 'destroy']);
        Route::apiResource('articles', ArticleController::class)->except(['store'])->whereUuid('article');
        Route::apiResource('podcasts', PodcastController::class)->except(['store'])->whereUuid('podcast');
        Route::apiResource('podcasts.episodes', EpisodeController::class)->except(['store'])->shallow()->whereUuid(['podcast', 'episode']);
        Route::apiResource('reflections', ReflectionController::class)->whereUuid('reflection');
        Route::apiResource('categories', CategoryController::class)->whereUuid('category');
    });

    Route::apiResource('admins', AdminController::class)->whereUuid('admin');

});
