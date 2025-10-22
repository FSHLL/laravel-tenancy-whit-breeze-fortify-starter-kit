<?php

use App\Http\Controllers\ProfileController;
use App\Http\Middleware\InitializeTenancyBySubDomain;
use Illuminate\Support\Facades\Route;

Route::middleware([
    InitializeTenancyBySubDomain::class,
])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});
