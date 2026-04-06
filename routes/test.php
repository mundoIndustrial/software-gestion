<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

// Rutas de diagnóstico (solo en desarrollo)
if (config('app.debug')) {
    Route::prefix('test')->group(function () {
        // Probar conectividad con API de festivos
        Route::get('/festivos-api', function () {
            try {
                $response = Http::timeout(5)
                    ->withoutVerifying()
                    ->get('https://api.nager.date/v3/PublicHolidays/2026/CO');
                
                if ($response->successful()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Conexión a API exitosa ✅',
                        'status' => $response->status(),
                        'data_count' => count($response->json()),
                        'sample' => array_slice($response->json(), 0, 2)
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'status' => $response->status(),
                        'message' => 'API respondió con error ❌'
                    ], 500);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                    'message' => 'No hay conexión a la API de festivos ❌'
                ], 500);
            }
        });

        // Probar conectividad general
        Route::get('/connectivity', function () {
            $urls = [
                'Google DNS' => 'https://8.8.8.8',
                'Google' => 'https://google.com',
                'Nager.Date API' => 'https://api.nager.date/v3/PublicHolidays/2026/CO'
            ];

            $results = [];

            foreach ($urls as $name => $url) {
                try {
                    $response = Http::timeout(3)
                        ->withoutVerifying()
                        ->get($url);
                    $results[$name] = [
                        'reachable' => true,
                        'status' => $response->status()
                    ];
                } catch (\Exception $e) {
                    $results[$name] = [
                        'reachable' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'connectivity_tests' => $results,
                'system_info' => [
                    'php_curl_enabled' => extension_loaded('curl'),
                    'allow_url_fopen' => ini_get('allow_url_fopen'),
                    'openssl_enabled' => extension_loaded('openssl')
                ]
            ]);
        });

        // Probar cálculo de días laborales
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
                    'message' => "Se han calculado {$dias} días laborales"
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
        });
    });
}

