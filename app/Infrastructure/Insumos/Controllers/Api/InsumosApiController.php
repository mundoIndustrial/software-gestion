<?php

namespace App\Infrastructure\Insumos\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domain\Insumos\Services\CalculadorDemoraService;
use Illuminate\Http\Request;

/**
 * API Controller para operaciones de Insumos
 * Expone servicios de dominio al frontend
 */
class InsumosApiController extends Controller
{
    protected CalculadorDemoraService $calculadorDemora;

    public function __construct(CalculadorDemoraService $calculadorDemora)
    {
        $this->calculadorDemora = $calculadorDemora;
    }

    /**
     * Calcular demora entre dos fechas
     * POST /api/insumos/calcular-demora
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calcularDemora(Request $request)
    {
        // Validar datos
        $validated = $request->validate([
            'fecha_pedido' => 'nullable|date_format:Y-m-d',
            'fecha_llegada' => 'nullable|date_format:Y-m-d',
        ]);

        try {
            // Calcular demora usando el Service de Dominio
            $demora = $this->calculadorDemora->calcularDemora(
                $validated['fecha_pedido'] ?? null,
                $validated['fecha_llegada'] ?? null
            );

            // Retornar estructura plana para fácil uso en frontend
            return response()->json([
                'success' => true,
                'dias' => $demora->getDias(),
                'estado' => $demora->getEstado(),
                'texto' => (string) $demora,
                'clase_bg' => $demora->getClaseBg(),
                'clase_text' => $demora->getClaseText(),
                'color_hex' => $demora->getColorHex(),
                'data' => $demora->toArray(), // estructura completa si se necesita
            ]);
        } catch (\Exception $e) {
            \Log::error('Error calculando demora: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al calcular demora',
                'message' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Calcular demora para múltiples materiales
     * POST /api/insumos/calcular-demoras-bulk
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calcularDemorasBulk(Request $request)
    {
        // Validar datos
        $validated = $request->validate([
            'materiales' => 'required|array|min:1',
            'materiales.*.id' => 'nullable|string',
            'materiales.*.fecha_pedido' => 'nullable|date_format:Y-m-d',
            'materiales.*.fecha_llegada' => 'nullable|date_format:Y-m-d',
        ]);

        try {
            $demorasCalculadas = $this->calculadorDemora->calcularDemoraParaMateriales(
                $validated['materiales']
            );

            return response()->json([
                'success' => true,
                'demoras' => $demorasCalculadas,
                'resumen' => $this->calculadorDemora->resumirDemorasPorEstado(
                    $validated['materiales']
                ),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error calculando demoras: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al calcular demoras',
                'message' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Evaluar si una demora es crítica
     * GET /api/insumos/demora-critica
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function esCriticaDemora(Request $request)
    {
        $validated = $request->validate([
            'dias' => 'required|integer|min:0',
        ]);

        $esCritica = $this->calculadorDemora->esCritica($validated['dias']);

        return response()->json([
            'success' => true,
            'es_critica' => $esCritica,
            'dias' => $validated['dias'],
        ]);
    }

    /**
     * Guardar observaciones de un material
     * POST /api/insumos/guardar-observaciones
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function guardarObservaciones(Request $request)
    {
        // Validar datos
        $validated = $request->validate([
            'numero_pedido' => 'required|string',
            'nombre_material' => 'required|string',
            'observaciones' => 'nullable|string|max:5000',
        ]);

        try {
            // Buscar el registro en materiales_orden_insumos
            $material = \App\Models\MaterialesOrdenInsumos::where('numero_pedido', $validated['numero_pedido'])
                ->where('nombre_material', $validated['nombre_material'])
                ->first();

            if (!$material) {
                return response()->json([
                    'success' => false,
                    'error' => 'Material no encontrado',
                ], 404);
            }

            // Actualizar observaciones
            $material->update([
                'observaciones' => $validated['observaciones'],
            ]);

            \Log::info("Observaciones guardadas para material: {$validated['nombre_material']} del pedido {$validated['numero_pedido']}");

            return response()->json([
                'success' => true,
                'message' => 'Observaciones guardadas exitosamente',
                'material_id' => $material->id,
                'observaciones' => $material->observaciones,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error guardando observaciones: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al guardar observaciones',
                'message' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
