<?php

use App\Http\Controllers\Contributor\ArticleController;
use App\Http\Controllers\Contributor\EpisodeController;
use App\Http\Controllers\Contributor\PodcastController;
use App\Http\Controllers\Contributor\ReflectionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('contributors')->name('contributors.')->group(function () {

    Route::apiResource('articles', ArticleController::class)->whereUuid('article');
    Route::apiResource('podcasts', PodcastController::class)->whereUuid('podcast');
    Route::apiResource('podcasts.episodes', EpisodeController::class)->shallow()->whereUuid(['podcast', 'episode']);
    Route::apiResource('reflections', ReflectionController::class);

});
