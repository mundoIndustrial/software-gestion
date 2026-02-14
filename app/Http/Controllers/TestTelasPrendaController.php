<?php

namespace App\Http\Controllers;

use App\Models\LogoCotizacionTelasPrenda;
use App\Models\Cotizacion;
use App\Models\LogoCotizacion;
use App\Models\PrendaCot;
use Illuminate\Support\Facades\Log;

/**
 * Controlador de Prueba para Telas de Prendas
 * 
 * Rutas:
 * GET /test-tela-prenda/crear - Crear registro de prueba
 * GET /test-tela-prenda/listar - Listar todos los registros
 * GET /test-tela-prenda/limpiar - Eliminar todos los registros de prueba
 */
class TestTelasPrendaController extends Controller
{
    /**
     * Crear un registro de prueba
     */
    public function crear()
    {
        try {
            // Buscar datos existentes
            $cotizacion = Cotizacion::first();
            if (!$cotizacion) {
                return response()->json([
                    'error' => 'No hay cotizaciones en la BD. Por favor crea una primero.',
                    'status' => 404
                ], 404);
            }

            $logoCotizacion = $cotizacion->logoCotizacion;
            if (!$logoCotizacion) {
                return response()->json([
                    'error' => 'La cotización ID ' . $cotizacion->id . ' no tiene logo. Por favor crea uno primero.',
                    'status' => 404
                ], 404);
            }

            $prenda = PrendaCot::first();
            if (!$prenda) {
                return response()->json([
                    'error' => 'No hay prendas en la BD. Por favor crea una primero.',
                    'status' => 404
                ], 404);
            }

            // Crear registro de prueba
            $telasPrenda = LogoCotizacionTelasPrenda::create([
                'logo_cotizacion_id' => $logoCotizacion->id,
                'prenda_cot_id' => $prenda->id,
                'tela' => 'Algodón 100% Premium - Prueba ' . now()->timestamp,
                'color' => 'Azul Marino',
                'ref' => 'REF-ALG-' . rand(1000, 9999),
                'img' => null,
            ]);

            Log::info(' Registro de prueba creado', [
                'id' => $telasPrenda->id,
                'logo_cotizacion_id' => $logoCotizacion->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => ' Registro de prueba creado exitosamente',
                'data' => [
                    'id' => $telasPrenda->id,
                    'logo_cotizacion_id' => $telasPrenda->logo_cotizacion_id,
                    'prenda_cot_id' => $telasPrenda->prenda_cot_id,
                    'prenda_nombre' => $prenda->nombre_producto,
                    'tela' => $telasPrenda->tela,
                    'color' => $telasPrenda->color,
                    'ref' => $telasPrenda->ref,
                    'created_at' => $telasPrenda->created_at,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error(' Error en crear:', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Listar todos los registros
     */
    public function listar()
    {
        try {
            $registros = LogoCotizacionTelasPrenda::with(['logoCotizacion', 'prenda'])
                ->get()
                ->map(function ($registro) {
                    return [
                        'id' => $registro->id,
                        'logo_cotizacion_id' => $registro->logo_cotizacion_id,
                        'prenda_id' => $registro->prenda_cot_id,
                        'prenda_nombre' => $registro->prenda?->nombre_producto,
                        'tela' => $registro->tela,
                        'color' => $registro->color,
                        'ref' => $registro->ref,
                        'img' => $registro->img,
                        'created_at' => $registro->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'total' => $registros->count(),
                'data' => $registros,
                'message' => " Se encontraron {$registros->count()} registros"
            ]);

        } catch (\Exception $e) {
            Log::error(' Error en listar:', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    /**
     * Limpiar todos los registros (SOLO PARA PRUEBAS)
     */
    public function limpiar()
    {
        try {
            $deleted = LogoCotizacionTelasPrenda::delete();

            Log::warning('🗑️ Registros de prueba eliminados', ['cantidad' => $deleted]);

            return response()->json([
                'success' => true,
                'message' => "🗑️ Se eliminaron {$deleted} registros",
                'deleted' => $deleted
            ]);

        } catch (\Exception $e) {
            Log::error(' Error en limpiar:', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }
}
