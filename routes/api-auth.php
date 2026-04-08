<?php

use App\Infrastructure\Http\Controllers\Auth\AuthApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->name('api.auth.')->middleware('web')->group(function () {
    Route::get('/csrf', [AuthApiController::class, 'csrf'])->name('csrf');
    Route::post('/register', [AuthApiController::class, 'register'])->name('register');
    Route::post('/login', [AuthApiController::class, 'login'])->name('login');
    Route::post('/forgot-password', [AuthApiController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('/reset-password', [AuthApiController::class, 'resetPassword'])->name('reset-password');
    Route::get('/me', [AuthApiController::class, 'me'])->name('me');
    Route::post('/logout', [AuthApiController::class, 'logout'])->name('logout');
});
