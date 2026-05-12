<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChartController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{slug}', [ArticleController::class, 'show']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/charts/precalculate', [ChartController::class, 'precalculate']);
Route::post('/payments/webhook', [PaymentController::class, 'webhook']);

Route::middleware(['auth:sanctum', 'check.not_blocked'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('profiles', ProfileController::class);
    Route::post('profiles/{profile}/set-default', [ProfileController::class, 'setDefault']);

    Route::apiResource('charts', ChartController::class)->except(['update']);
    Route::post('charts/generate', [ChartController::class, 'generate'])->middleware('check.credits');

    Route::get('user/profile', [UserController::class, 'profile']);
    Route::put('user/profile', [UserController::class, 'updateProfile']);

    Route::get('payments/tariffs', [PaymentController::class, 'tariffs']);
    Route::post('payments/create', [PaymentController::class, 'create']);
});
