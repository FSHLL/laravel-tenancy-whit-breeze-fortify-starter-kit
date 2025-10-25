<?php

use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->group(function () {
        Route::resource('tenants', TenantController::class);
        Route::resource('tenants.users', TenantUserController::class);
    });
