<?php

use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->resource('tenants', TenantController::class);
