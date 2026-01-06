<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LogoCotizacion;
use App\Models\TipoLogoCotizacion;
use App\Models\LogoCotizacionTecnicaPrenda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * LogoCotizacionTecnicaController
 * 
 * Controlador para gestionar técnicas y prendas en cotizaciones de logo
 * Estructura: LogoCotizacion -> TipoLogoCotizacion -> LogoCotizacionTecnicaPrenda
 */
class LogoCotizacionTecnicaController extends Controller
{
    /**
     * Obtener tipos de técnicas disponibles (para select en UI)
     */
    public function tiposDisponibles()
    {
        try {
            $tipos = TipoLogoCotizacion::all()
                ->map(fn($tipo) => [
                    'id' => $tipo->id,
                    'nombre' => $tipo->nombre,
                    'codigo' => $tipo->codigo,
                    'color' => $tipo->color,
                    'icono' => $tipo->icono ?? null,
                ]);

            return response()->json([
                'success' => true,
                'data' => $tipos
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener tipos', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de técnicas'
            ], 500);
        }
    }

    /**
     * Agregar una técnica (TipoLogoCotizacion) con prendas a una cotización
     */
    public function agregarTecnica(Request $request)
    {
        try {
            $validated = $request->validate([
                'logo_cotizacion_id' => 'required|integer|exists:logo_cotizaciones,id',
                'tipo_logo_id' => 'required|integer|exists:tipo_logo_cotizaciones,id',
                'prendas' => 'required|array|min:1',
                'prendas.*.nombre_prenda' => 'required|string|max:255',
                'prendas.*.descripcion' => 'nullable|string',
                'prendas.*.ubicaciones' => 'required|array|min:1',
                'prendas.*.talla_cantidad' => 'required|array|min:1',
            ]);

            $logoCotizacionId = $validated['logo_cotizacion_id'];
            $tipoLogoId = $validated['tipo_logo_id'];

            // Verificar que existan
            LogoCotizacion::findOrFail($logoCotizacionId);
            TipoLogoCotizacion::findOrFail($tipoLogoId);

            // Crear prendas
            $prendas = [];
            foreach ($validated['prendas'] as $prendaData) {
                $prenda = LogoCotizacionTecnicaPrenda::create([
                    'logo_cotizacion_id' => $logoCotizacionId,
                    'tipo_logo_id' => $tipoLogoId,
                    'nombre_prenda' => $prendaData['nombre_prenda'],
                    'descripcion' => $prendaData['descripcion'] ?? '',
                    'ubicaciones' => $prendaData['ubicaciones'],
                    'tallas' => $prendaData['talla_cantidad'],
                    'cantidad_general' => 1,
                ]);

                $prendas[] = $prenda;
            }

            Log::info('✅ Técnica agregada a cotización', [
                'logo_cotizacion_id' => $logoCotizacionId,
                'tipo_logo_id' => $tipoLogoId,
                'prendas_count' => count($prendas),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Técnica agregada exitosamente',
                'data' => [
                    'prendas_count' => count($prendas),
                    'prendas' => array_map(fn($p) => [
                        'id' => $p->id,
                        'nombre_prenda' => $p->nombre_prenda,
                    ], $prendas)
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ Error al agregar técnica', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar técnica: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener técnicas y prendas de una cotización
     */
    public function obtenerTecnicas($logoCotizacionId)
    {
        try {
            // Verificar que existe la cotización
            LogoCotizacion::findOrFail($logoCotizacionId);

            // Obtener todas las prendas agrupadas por tipo de logo
            $prendas = LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacionId)
                ->with('tipoLogo')
                ->get()
                ->groupBy('tipo_logo_id')
                ->map(function($prendasPorTipo) {
                    $tipoLogo = $prendasPorTipo->first()->tipoLogo;
                    
                    return [
                        'tipo_logo' => [
                            'id' => $tipoLogo->id,
                            'nombre' => $tipoLogo->nombre,
                            'codigo' => $tipoLogo->codigo,
                            'color' => $tipoLogo->color,
                        ],
                        'prendas' => $prendasPorTipo->map(fn($prenda) => [
                            'id' => $prenda->id,
                            'nombre_prenda' => $prenda->nombre_prenda,
                            'descripcion' => $prenda->descripcion,
                            'ubicaciones' => $prenda->ubicaciones,
                            'talla_cantidad' => $prenda->talla_cantidad,
                            'cantidad_general' => $prenda->cantidad_general,
                        ])->values()->toArray(),
                    ];
                })->values();

            return response()->json([
                'success' => true,
                'data' => $prendas
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cotización no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener técnicas', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener técnicas'
            ], 500);
        }
    }

    /**
     * Eliminar una prenda
     */
    public function eliminarTecnica($prendeId)
    {
        try {
            $prenda = LogoCotizacionTecnicaPrenda::findOrFail($prendeId);
            
            $tipoLogoId = $prenda->tipo_logo_id;
            $logoCotizacionId = $prenda->logo_cotizacion_id;

            // Eliminar la prenda
            $prenda->delete();

            // Si no hay más prendas de este tipo para esta cotización
            $prendasRestantes = LogoCotizacionTecnicaPrenda::where('logo_cotizacion_id', $logoCotizacionId)
                ->where('tipo_logo_id', $tipoLogoId)
                ->count();

            Log::info('✅ Prenda eliminada', [
                'prenda_id' => $prendeId,
                'prendas_restantes_tipo' => $prendasRestantes
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Prenda eliminada exitosamente',
                'prendas_restantes' => $prendasRestantes
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Prenda no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar prenda', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar prenda'
            ], 500);
        }
    }

    /**
     * Actualizar datos de una prenda
     */
    public function actualizarObservaciones(Request $request, $prendeId)
    {
        try {
            $validated = $request->validate([
                'descripcion' => 'nullable|string',
                'ubicaciones' => 'nullable|array',
                'talla_cantidad' => 'nullable|array',
                'cantidad_general' => 'nullable|integer|min:1',
            ]);

            $prenda = LogoCotizacionTecnicaPrenda::findOrFail($prendeId);
            $prenda->update($validated);

            Log::info('✅ Prenda actualizada', ['prenda_id' => $prendeId]);

            return response()->json([
                'success' => true,
                'message' => 'Prenda actualizada exitosamente',
                'data' => [
                    'id' => $prenda->id,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'descripcion' => $prenda->descripcion,
                    'ubicaciones' => $prenda->ubicaciones,
                    'talla_cantidad' => $prenda->talla_cantidad,
                    'cantidad_general' => $prenda->cantidad_general,
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Prenda no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar prenda', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar prenda'
            ], 500);
        }
    }
}

