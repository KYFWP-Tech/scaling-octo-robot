<?php

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum'])->group(function () {

    Route::apiResource('admins', AdminController::class);

    Route::prefix('admins')->name('admins.')->group(function () {
        Route::post('accept-invitation', [AdminController::class, 'acceptInvitation'])->name('accept-invitation');
    });
});
