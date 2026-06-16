<?php

use App\Http\Controllers\Contributor\ArticleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('contributors')->name('contributors.')->group(function () {

    Route::apiResource('articles', ArticleController::class);

});
