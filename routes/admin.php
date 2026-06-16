<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {

    Route::apiResource('admins', AdminController::class);

    Route::prefix('admins')->name('admins.')->group(function () {
        Route::apiResource('users', UserController::class)->except(['store']);
        Route::post('accept-invitation', [AdminController::class, 'acceptInvitation'])->name('accept-invitation');
        Route::put('{admin}/role', [AdminController::class, 'assignRole'])->name('assign-role');
    });

    Route::apiResource('roles', RoleController::class)->except(['store', 'destroy']);
    Route::apiResource('permissions', PermissionController::class)->except(['store', 'destroy']);
});
