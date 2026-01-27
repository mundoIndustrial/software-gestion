<?php

namespace App\Infrastructure\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\DTOs\Edit\EditPrendaPedidoDTO;
use App\DTOs\Edit\EditPrendaVariantePedidoDTO;
use App\Infrastructure\Services\Edit\PrendaPedidoEditService;
use App\Infrastructure\Services\Edit\PrendaVariantePedidoEditService;
use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * PrendaPedidoEditController - Controlador de edición segura de prendas
 * 
 * RESPONSABILIDAD:
 * ─────────────────
 * Manejar endpoints PATCH para edición segura de prendas persistidas.
 * 
 * SEPARACIÓN CLARA:
 * ────────────────
 * POST /api/prendas-pedido               → Crear (construcción desde DOM)
 * PATCH /api/prendas-pedido/{id}/editar → Editar (parcial seguro)
 * 
 * MÉTODOS:
 * ────────
 * editPrenda()        - Editar prenda completa (PATCH)
 * editPrendaFields()  - Editar solo campos simples
 * editTallas()        - Editar solo tallas (MERGE)
 * editVariante()      - Editar variante
 * editVarianteFields()- Editar solo variante campos simples
 * 
 * GARANTÍAS:
 * ──────────
 * ✓ No reconstruye desde DOM
 * ✓ Solo actualiza lo enviado
 * ✓ Valida restricciones de negocio
 * ✓ MERGE en relaciones (no borrado)
 * ✓ Separado de lógica de creación
 */
class PrendaPedidoEditController extends Controller
{
    protected PrendaPedidoEditService $prendaEditService;
    protected PrendaVariantePedidoEditService $varianteEditService;

    public function __construct(
        PrendaPedidoEditService $prendaEditService,
        PrendaVariantePedidoEditService $varianteEditService
    ) {
        $this->prendaEditService = $prendaEditService;
        $this->varianteEditService = $varianteEditService;
    }

    /**
     * Editar prenda completa (operación PATCH)
     * 
     * POST: /api/prendas-pedido/{id}/editar
     * 
     * Payload:
     * {
     *   "nombre_prenda": "CAMISA POLO",
     *   "cantidad": 100,
     *   "tallas": [
     *     {"id": 1, "cantidad": 50},
     *     {"genero": "dama", "talla": "M", "cantidad": 30}
     *   ],
     *   "variantes": [...]
     * }
     * 
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function editPrenda(int $id, Request $request): JsonResponse
    {
        try {
            $prenda = PrendaPedido::findOrFail($id);

            // Crear DTO desde payload
            $dto = EditPrendaPedidoDTO::fromPayload($request->all());
            $dto->id = $id;

            // Ejecutar edición
            $resultado = $this->prendaEditService->edit($prenda, $dto);

            return response()->json($resultado);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'error' => 'Prenda no encontrada',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Editar solo campos simples de prenda
     * 
     * PATCH: /api/prendas-pedido/{id}/editar/campos-simples
     * 
     * Payload:
     * {
     *   "nombre_prenda": "NUEVO NOMBRE",
     *   "descripcion": "Nueva descripción",
     *   "cantidad": 120,
     *   "de_bodega": true
     * }
     * 
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function editPrendaFields(int $id, Request $request): JsonResponse
    {
        try {
            $prenda = PrendaPedido::findOrFail($id);

            $resultado = $this->prendaEditService->updateBasic($prenda, $request->all());

            return response()->json($resultado);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Editar solo tallas (MERGE)
     * 
     * PATCH: /api/prendas-pedido/{id}/editar/tallas
     * 
     * Payload:
     * {
     *   "tallas": [
     *     {"id": 1, "cantidad": 60},
     *     {"genero": "caballero", "talla": "L", "cantidad": 20}
     *   ]
     * }
     * 
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function editTallas(int $id, Request $request): JsonResponse
    {
        try {
            $prenda = PrendaPedido::findOrFail($id);

            if (!$request->has('tallas')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Campo "tallas" es requerido',
                ], 422);
            }

            $resultado = $this->prendaEditService->updateTallas($prenda, $request->input('tallas'));

            return response()->json($resultado);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Editar variante de prenda
     * 
     * PATCH: /api/prendas-pedido/{prendaId}/variantes/{varianteId}/editar
     * 
     * Payload:
     * {
     *   "tipo_manga_id": 2,
     *   "tiene_bolsillos": true,
     *   "colores": [
     *     {"id": 5, "color_id": 3},
     *     {"color_id": 6}
     *   ]
     * }
     * 
     * @param int $prendaId
     * @param int $varianteId
     * @param Request $request
     * @return JsonResponse
     */
    public function editVariante(int $prendaId, int $varianteId, Request $request): JsonResponse
    {
        try {
            $prenda = PrendaPedido::findOrFail($prendaId);
            $variante = $prenda->variantes()->findOrFail($varianteId);

            // Crear DTO
            $dto = EditPrendaVariantePedidoDTO::fromPayload($request->all());
            $dto->id = $varianteId;

            // Validar que puede editarse
            if (!$this->varianteEditService->canEdit($variante, $dto)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Intento de editar campos protegidos',
                ], 422);
            }

            // Ejecutar edición
            $resultado = $this->varianteEditService->edit($variante, $dto);

            return response()->json($resultado);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'error' => 'Prenda o variante no encontrada',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Editar solo campos simples de variante
     * 
     * PATCH: /api/prendas-pedido/{prendaId}/variantes/{varianteId}/editar/campos
     * 
     * @param int $prendaId
     * @param int $varianteId
     * @param Request $request
     * @return JsonResponse
     */
    public function editVarianteFields(int $prendaId, int $varianteId, Request $request): JsonResponse
    {
        try {
            $prenda = PrendaPedido::findOrFail($prendaId);
            $variante = $prenda->variantes()->findOrFail($varianteId);

            $resultado = $this->varianteEditService->updateBasic($variante, $request->all());

            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Editar solo colores de variante (MERGE)
     * 
     * PATCH: /api/prendas-pedido/{prendaId}/variantes/{varianteId}/colores
     * 
     * @param int $prendaId
     * @param int $varianteId
     * @param Request $request
     * @return JsonResponse
     */
    public function editVarianteColores(int $prendaId, int $varianteId, Request $request): JsonResponse
    {
        try {
            $prenda = PrendaPedido::findOrFail($prendaId);
            $variante = $prenda->variantes()->findOrFail($varianteId);

            if (!$request->has('colores')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Campo "colores" es requerido',
                ], 422);
            }

            $resultado = $this->varianteEditService->updateColores($variante, $request->input('colores'));

            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Editar solo telas de variante (MERGE)
     * 
     * PATCH: /api/prendas-pedido/{prendaId}/variantes/{varianteId}/telas
     * 
     * @param int $prendaId
     * @param int $varianteId
     * @param Request $request
     * @return JsonResponse
     */
    public function editVarianteTelas(int $prendaId, int $varianteId, Request $request): JsonResponse
    {
        try {
            $prenda = PrendaPedido::findOrFail($prendaId);
            $variante = $prenda->variantes()->findOrFail($varianteId);

            if (!$request->has('telas')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Campo "telas" es requerido',
                ], 422);
            }

            $resultado = $this->varianteEditService->updateTelas($variante, $request->input('telas'));

            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener estado actual de una prenda (para auditoría)
     * 
     * GET: /api/prendas-pedido/{id}/estado
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function getPrendaState(int $id): JsonResponse
    {
        try {
            $prenda = PrendaPedido::findOrFail($id);
            $estado = $this->prendaEditService->getCurrentState($prenda);

            return response()->json([
                'success' => true,
                'data' => $estado,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener estado actual de una variante (para auditoría)
     * 
     * GET: /api/prendas-pedido/{prendaId}/variantes/{varianteId}/estado
     * 
     * @param int $prendaId
     * @param int $varianteId
     * @return JsonResponse
     */
    public function getVarianteState(int $prendaId, int $varianteId): JsonResponse
    {
        try {
            $prenda = PrendaPedido::findOrFail($prendaId);
            $variante = $prenda->variantes()->findOrFail($varianteId);
            $estado = $this->varianteEditService->getCurrentState($variante);

            return response()->json([
                'success' => true,
                'data' => $estado,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function actualizarProcesoEspecifico(int $prendaId, int $procesoId, Request $request): JsonResponse
    {
        try {
            // Validar que la prenda existe
            $prenda = PrendaPedido::findOrFail($prendaId);

            // Obtener el proceso
            $proceso = $prenda->procesos()->findOrFail($procesoId);

            // Limpieza y preparación de datos ANTES de validar
            $data = $request->all();

            // Limpiar imágenes: convertir a strings, eliminar nulls/vacíos
            if (isset($data['imagenes']) && is_array($data['imagenes'])) {
                $imagenesLimpias = [];
                
                foreach ($data['imagenes'] as $img) {
                    // Saltar nulls y vacíos
                    if ($img === null || $img === '') {
                        continue;
                    }
                    
                    // Si es un string, usar directamente
                    if (is_string($img)) {
                        if (!empty($img) && $img !== 'null') {
                            $imagenesLimpias[] = $img;
                        }
                        continue;
                    }
                    
                    // Si es un objeto, intentar obtener ruta_webp
                    if (is_object($img)) {
                        if (isset($img->ruta_webp) && !empty($img->ruta_webp)) {
                            $imagenesLimpias[] = (string)$img->ruta_webp;
                        }
                        continue;
                    }
                    
                    // Si es un array, intentar obtener la ruta
                    if (is_array($img)) {
                        if (isset($img['ruta_webp']) && !empty($img['ruta_webp'])) {
                            $imagenesLimpias[] = (string)$img['ruta_webp'];
                        } elseif (isset($img[0]) && is_string($img[0]) && !empty($img[0])) {
                            $imagenesLimpias[] = (string)$img[0];
                        }
                        continue;
                    }
                }
                
                $data['imagenes'] = $imagenesLimpias;
            }

            // Validar manualmente con datos limpios (no usar $request->validate para evitar validar request original)
            $validator = \Validator::make($data, [
                'tipo_proceso_id' => 'nullable|integer|exists:tipos_proceso,id',
                'ubicaciones' => 'nullable|array',
                'ubicaciones.*' => 'string|nullable',
                'imagenes' => 'nullable|array',
                'imagenes.*' => 'string|nullable',
                'observaciones' => 'nullable|string|max:1000',
                'tallas' => 'nullable|array',
                'tallas.dama' => 'nullable|array',
                'tallas.caballero' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                throw new \Illuminate\Validation\ValidationException($validator);
            }

            $validated = $validator->validated();

            \Log::info('[PROCESOS-ACTUALIZAR] Actualizando proceso:', [
                'prenda_id' => $prendaId,
                'proceso_id' => $procesoId,
                'cambios' => array_keys($validated)
            ]);

            // Actualizar ubicaciones (REEMPLAZO completo, no merge)
            if (isset($validated['ubicaciones'])) {
                $ubicacionesLimpias = array_filter($validated['ubicaciones']);
                $proceso->ubicaciones = json_encode($ubicacionesLimpias);
                \Log::info('[PROCESOS-ACTUALIZAR] Ubicaciones actualizadas:', [
                    'nuevas' => $ubicacionesLimpias
                ]);
            }

            // Actualizar observaciones
            if (isset($validated['observaciones'])) {
                $proceso->observaciones = $validated['observaciones'];
            }

            // Actualizar tipo_proceso_id si se proporciona
            if (isset($validated['tipo_proceso_id'])) {
                $proceso->tipo_proceso_id = $validated['tipo_proceso_id'];
            }

            // Guardar cambios en tabla principal
            $proceso->save();

            // ============ ACTUALIZAR IMÁGENES (EN TABLA SEPARADA) ============
            if (isset($validated['imagenes'])) {
                // Obtener imágenes actuales
                $imagenesActuales = \DB::table('pedidos_procesos_imagenes')
                    ->where('proceso_prenda_detalle_id', $proceso->id)
                    ->pluck('ruta_webp')
                    ->toArray();

                $imagenesNuevas = array_filter($validated['imagenes']);
                
                // Eliminar SOLO las imágenes que ya no están en la nueva lista
                $imagenesAEliminar = array_diff($imagenesActuales, $imagenesNuevas);
                if (!empty($imagenesAEliminar)) {
                    \DB::table('pedidos_procesos_imagenes')
                        ->where('proceso_prenda_detalle_id', $proceso->id)
                        ->whereIn('ruta_webp', $imagenesAEliminar)
                        ->delete();
                }

                // Agregar SOLO las imágenes nuevas que no existen
                $imagenesAAgregar = array_diff($imagenesNuevas, $imagenesActuales);
                if (!empty($imagenesAAgregar)) {
                    $proximoOrden = \DB::table('pedidos_procesos_imagenes')
                        ->where('proceso_prenda_detalle_id', $proceso->id)
                        ->max('orden') ?? 0;

                    foreach ($imagenesAAgregar as $idx => $ruta) {
                        if ($ruta) {
                            \DB::table('pedidos_procesos_imagenes')->insert([
                                'proceso_prenda_detalle_id' => $proceso->id,
                                'ruta_original' => null,
                                'ruta_webp' => $ruta,
                                'orden' => $proximoOrden + $idx + 1,
                                'es_principal' => 0,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                    }
                }

                \Log::info('[PROCESOS-ACTUALIZAR] Imágenes actualizadas:', [
                    'eliminadas' => count($imagenesAEliminar),
                    'agregadas' => count($imagenesAAgregar),
                    'total_final' => count($imagenesNuevas)
                ]);
            }

            // ============ ACTUALIZAR TALLAS (EN TABLA SEPARADA) ============
            if (isset($validated['tallas'])) {
                // Obtener tallas actuales organizadas por género
                $tallasActuales = \DB::table('pedidos_procesos_prenda_tallas')
                    ->where('proceso_prenda_detalle_id', $proceso->id)
                    ->get()
                    ->groupBy('genero')
                    ->map(function ($grupo) {
                        return $grupo->pluck('cantidad', 'talla')->toArray();
                    })
                    ->toArray();

                $tallasDama = $validated['tallas']['dama'] ?? [];
                $tallasHombre = $validated['tallas']['caballero'] ?? [];
                $tallasActualDama = $tallasActuales['DAMA'] ?? [];
                $tallasActualHombre = $tallasActuales['CABALLERO'] ?? [];

                // DAMA: Eliminar tallas que ya no existen o quedaron en 0
                foreach ($tallasActualDama as $talla => $cantidad) {
                    if (!isset($tallasDama[$talla]) || $tallasDama[$talla] == 0) {
                        \DB::table('pedidos_procesos_prenda_tallas')
                            ->where('proceso_prenda_detalle_id', $proceso->id)
                            ->where('genero', 'DAMA')
                            ->where('talla', $talla)
                            ->delete();
                    }
                }

                // DAMA: Insertar/Actualizar tallas nuevas o modificadas
                foreach ($tallasDama as $talla => $cantidad) {
                    if ($cantidad > 0) {
                        \DB::table('pedidos_procesos_prenda_tallas')
                            ->updateOrInsert(
                                [
                                    'proceso_prenda_detalle_id' => $proceso->id,
                                    'genero' => 'DAMA',
                                    'talla' => $talla
                                ],
                                [
                                    'cantidad' => (int)$cantidad,
                                    'updated_at' => now()
                                ]
                            );
                    }
                }

                // CABALLERO: Eliminar tallas que ya no existen o quedaron en 0
                foreach ($tallasActualHombre as $talla => $cantidad) {
                    if (!isset($tallasHombre[$talla]) || $tallasHombre[$talla] == 0) {
                        \DB::table('pedidos_procesos_prenda_tallas')
                            ->where('proceso_prenda_detalle_id', $proceso->id)
                            ->where('genero', 'CABALLERO')
                            ->where('talla', $talla)
                            ->delete();
                    }
                }

                // CABALLERO: Insertar/Actualizar tallas nuevas o modificadas
                foreach ($tallasHombre as $talla => $cantidad) {
                    if ($cantidad > 0) {
                        \DB::table('pedidos_procesos_prenda_tallas')
                            ->updateOrInsert(
                                [
                                    'proceso_prenda_detalle_id' => $proceso->id,
                                    'genero' => 'CABALLERO',
                                    'talla' => $talla
                                ],
                                [
                                    'cantidad' => (int)$cantidad,
                                    'updated_at' => now()
                                ]
                            );
                    }
                }

                \Log::info('[PROCESOS-ACTUALIZAR] Tallas actualizadas:', [
                    'tallas' => $validated['tallas']
                ]);
            }

            \Log::info('[PROCESOS-ACTUALIZAR] Proceso actualizado exitosamente:', [
                'proceso_id' => $procesoId,
                'prenda_id' => $prendaId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Proceso actualizado correctamente',
                'data' => [
                    'id' => $proceso->id,
                    'tipo' => $proceso->tipo_proceso,
                    'actualizados' => array_keys($validated)
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('[PROCESOS-ACTUALIZAR] Proceso no encontrado:', [
                'prenda_id' => $prendaId,
                'proceso_id' => $procesoId
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Proceso no encontrado en la prenda especificada',
            ], 404);

        } catch (ValidationException $e) {
            \Log::warning('[PROCESOS-ACTUALIZAR] Error de validación:', [
                'errores' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('[PROCESOS-ACTUALIZAR] Error inesperado:', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar el proceso: ' . $e->getMessage(),
            ], 500);
        }
    }
}
