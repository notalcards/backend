<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [Admin\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [Admin\AuthController::class, 'login']);
    Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('/users/{id}', [Admin\UserController::class, 'show'])->name('users.show');
        Route::post('/users/{id}/credits', [Admin\UserController::class, 'addCredits'])->name('users.credits');
        Route::post('/users/{id}/block', [Admin\UserController::class, 'block'])->name('users.block');
        Route::post('/users/{id}/unblock', [Admin\UserController::class, 'unblock'])->name('users.unblock');
    });
});
