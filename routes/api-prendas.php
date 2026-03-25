<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\PrendaController;

/**
 * API Routes for Prendas - Write Operations (POST, PATCH, DELETE)
 * 
 * Protected routes for creating, updating and deleting garments (prendas)
 * 
 * Auth: web guard with authentication
 * Middleware: web, auth
 */
Route::withoutMiddleware(['api'])
    ->middleware(['web', 'auth'])
    ->group(function () {
        Route::apiResource('prendas', PrendaController::class, ['only' => ['store', 'update', 'destroy']]);
    });
