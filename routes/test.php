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
    });
}

