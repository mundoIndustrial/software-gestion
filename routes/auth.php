<?php

use App\Infrastructure\Http\Controllers\Legacy\Auth\AuthenticatedSessionController;
use App\Infrastructure\Http\Controllers\Legacy\Auth\ConfirmablePasswordController;
use App\Infrastructure\Http\Controllers\Legacy\Auth\EmailVerificationNotificationController;
use App\Infrastructure\Http\Controllers\Legacy\Auth\EmailVerificationPromptController;
use App\Infrastructure\Http\Controllers\Legacy\Auth\NewPasswordController;
use App\Infrastructure\Http\Controllers\Legacy\Auth\PasswordController;
use App\Infrastructure\Http\Controllers\Legacy\Auth\PasswordResetLinkController;
use App\Infrastructure\Http\Controllers\Legacy\Auth\RegisteredUserController;
use App\Infrastructure\Http\Controllers\Legacy\Auth\VerifyEmailController;
use App\Infrastructure\Http\Controllers\Legacy\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Google OAuth
    Route::get('auth/google', [GoogleAuthController::class, 'redirect'])
        ->name('auth.google');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->name('auth.google.callback');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    // Ruta GET de logout - redirige para evitar error 405
    Route::get('logout', function () {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/')->with('success', 'Session cerrada correctamente');
    })->name('logout.get');
});
