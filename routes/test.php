<?php

use Illuminate\Support\Facades\Route;

// Rutas de diagnóstico (solo en desarrollo)
if (config('app.debug')) {
    Route::prefix('test')->group(function () {
        // Probar cálculo de días laborales (librería local, sin APIs externas)
        Route::get('/dias-laborales/{fecha_inicio}', function ($fecha_inicio) {
            try {
                $fechaInicio = \Carbon\Carbon::parse($fecha_inicio);
                $calculator = app(\App\Application\Services\DiaLaboralCalculator::class);
                $dias = $calculator->calcular($fechaInicio);

                return response()->json([
                    'success' => true,
                    'fecha_inicio' => $fechaInicio->format('Y-m-d H:i:s'),
                    'fecha_fin' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    'dias_laborales' => $dias,
                    'message' => "Se han calculado {$dias} dias laborales",
                    'source' => 'cmixin/business-day co-national',
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        });

        // Ruta de prueba: Simular error en creación de pedido
        Route::get('/error-pedido', function () {
            try {
                // Simular que estamos intentando guardar un pedido
                \App\Models\SystemError::registrarDesdeJavaScript([
                    'tipo' => 'ERROR_RED',
                    'mensaje' => '500 Internal Server Error: Connection timeout en BD',
                    'origen' => 'api',
                    'url_pagina' => request()->url(),
                    'contexto' => [
                        'pedido_id' => 584,
                        'usuario_id' => auth()->id() ?? 1,
                        'paso' => 'guardando_procesos',
                        'tabla_afectada' => 'procesos_prenda',
                        'detalles_error' => 'SQLSTATE[HY000]: General error: 1030 Got error 28 from storage engine'
                    ],
                    'detalles' => [
                        'archivo' => 'CrearPedidoController.php',
                        'linea' => 247,
                        'metodo' => 'guardarProcesos',
                        'query' => 'INSERT INTO procesos_prenda (prenda_id, tipo_proceso_id, ...) VALUES (...)'
                    ]
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Error de prueba registrado en sistema',
                    'url_admin' => url('/admin/configuracion/errores')
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        })->middleware('auth');
    });
}

