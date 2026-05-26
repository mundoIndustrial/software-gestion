<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Lavanderia\LavanderiaController;

/**
 * Rutas del módulo de Lavandería
 * Requiere autenticación y rol de gestor-lavanderia o admin
 */

Route::middleware(['auth', 'lavanderia-access'])->prefix('gestion-lavanderia')->name('gestion-lavanderia.')->group(function () {
    
    // Ruta raíz - dashboard principal
    Route::get('/', [LavanderiaController::class, 'index'])
        ->name('index');
    
    // Ruta de diagnóstico
    Route::get('/diagnostico', function() {
        $user = auth()->user();
        return response()->json([
            'usuario_autenticado' => true,
            'usuario_id' => $user->id,
            'usuario_email' => $user->email,
            'usuario_nombre' => $user->name,
            'roles_ids_raw' => $user->roles_ids,
            'roles_ids' => $user->roles_ids ? ($user->roles_ids) : [],
            'roles_nombres' => $user->getRoleNames()->toArray(),
        ], 200);
    })->name('diagnostico');
});
