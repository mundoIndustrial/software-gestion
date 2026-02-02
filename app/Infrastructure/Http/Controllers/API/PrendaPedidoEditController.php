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

            // ============ FIX: PARSEAR FormData CON PATCH ============
            // Cuando se envía FormData con PATCH desde fetch, PHP/Laravel a veces no parsea
            // los parámetros correctamente.
            // SOLUCIÓN: El cliente envía POST con _method=PATCH en el FormData
            // Laravel lo reconoce automáticamente y routea a este método
            // Necesitamos usar $request->all() que ya debería funcionar con POST + FormData
            $inputData = $request->all();
            
            // Fallback: Si request->all() está vacío pero hay datos en $_POST, usarlos
            if (empty($inputData) && !empty($_POST)) {
                $inputData = $_POST;
            }

            // ============ LOG INICIAL ============
            \Log::info('[PROCESOS-ACTUALIZAR-PATCH] Recibido PATCH (POST con _method)', [
                'prenda_id' => $prendaId,
                'proceso_id' => $procesoId,
                'request_method' => $request->getMethod(),
                'request_keys' => array_keys($inputData),
                'ubicaciones' => $inputData['ubicaciones'] ?? 'NOT_SET',
                'observaciones' => substr($inputData['observaciones'] ?? '', 0, 50),
                'has_files' => $request->hasFile('imagenes_nuevas'),
                'files_count' => count((array)$request->file('imagenes_nuevas') ?? []),
                '_method' => $inputData['_method'] ?? 'NOT_SET'
            ]);

            // ============ NUEVO: PROCESAR IMÁGENES NUEVAS (FILES) DEL FORMDATA ============
            $imagenesNuevasRutas = [];
            if ($request->hasFile('imagenes_nuevas')) {
                $files = $request->file('imagenes_nuevas');
                if (!is_array($files)) {
                    $files = [$files];
                }
                
                \Log::info('[PROCESOS-ACTUALIZAR] Archivos de imágenes recibidos:', [
                    'cantidad' => count($files),
                    'archivos' => array_map(function($f) { return $f->getClientOriginalName() ?? 'unknown'; }, $files)
                ]);
                
                $procesoFotoService = new \App\Domain\Pedidos\Services\ProcesoFotoService();
                foreach ($files as $imagen) {
                    if ($imagen && $imagen->isValid()) {
                        try {
                            $rutas = $procesoFotoService->procesarFoto($imagen);
                            $imagenesNuevasRutas[] = $rutas['ruta_webp'] ?? $rutas;
                            \Log::info('[PROCESOS-ACTUALIZAR] Imagen nueva de proceso procesada', [
                                'archivo' => $imagen->getClientOriginalName(),
                                'ruta_webp' => $rutas['ruta_webp'] ?? 'N/A'
                            ]);
                        } catch (\Exception $e) {
                            \Log::warning('[PROCESOS-ACTUALIZAR] Error procesando imagen nueva de proceso', [
                                'error' => $e->getMessage(),
                                'archivo' => $imagen->getClientOriginalName()
                            ]);
                        }
                    }
                }
                
                \Log::info('[PROCESOS-ACTUALIZAR] Imágenes nuevas procesadas:', [
                    'total' => count($imagenesNuevasRutas),
                    'rutas' => $imagenesNuevasRutas
                ]);
            } else {
                \Log::info('[PROCESOS-ACTUALIZAR] Sin archivos de imágenes en el request');
            }

            // Limpieza y preparación de datos ANTES de validar
            // IMPORTANTE: Usar $inputData que ya fue parseado correctamente
            $data = $inputData;

            // Decodificar ubicaciones si vienen como JSON string
            if (isset($data['ubicaciones']) && is_string($data['ubicaciones'])) {
                try {
                    $ubicacionesDecodificadas = json_decode($data['ubicaciones'], true);
                    if (is_array($ubicacionesDecodificadas)) {
                        $data['ubicaciones'] = $ubicacionesDecodificadas;
                    }
                } catch (\Exception $e) {
                    // Si no es JSON válido, mantener como está
                }
            }

            // LOG: Confirmar que los datos se recibieron después del fix
            \Log::info('[PROCESOS-ACTUALIZAR-PATCH] Datos después del FIX de parseo', [
                'data_keys' => array_keys($data),
                'ubicaciones_presente' => isset($data['ubicaciones']),
                'observaciones_presente' => isset($data['observaciones']),
                'ubicaciones_valor' => $data['ubicaciones'] ?? 'NULL',
                'observaciones_valor' => substr($data['observaciones'] ?? '', 0, 100)
            ]);

            // ============ NUEVO: PROCESAR IMÁGENES EXISTENTES Y NUEVAS ============
            $imagenesExistentes = [];
            if (isset($data['imagenes_existentes']) && is_string($data['imagenes_existentes'])) {
                try {
                    $imagenesExistentes = json_decode($data['imagenes_existentes'], true) ?? [];
                    if (!is_array($imagenesExistentes)) {
                        $imagenesExistentes = [];
                    }
                    \Log::info('[PROCESOS-ACTUALIZAR] Imágenes existentes recuperadas', [
                        'cantidad' => count($imagenesExistentes),
                        'detalles' => $imagenesExistentes
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('[PROCESOS-ACTUALIZAR] Error decodificando imágenes_existentes', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // ============ NUEVO: CONSTRUIR LISTA FINAL DE IMÁGENES (SIN DUPLICADOS) ============
            // LÓGICA CORRECTA:
            // 1. Si vienen imagenes (JSON del cambio) → el usuario SÍ cambió imágenes, usar esa lista
            // 2. Sino, Si vienen imagenes_existentes → el usuario NO cambió imágenes, mantener existentes
            // 3. Siempre agregar imagenes_nuevas (files) si existen
            
            $debeActualizarImagenes = false;
            $imagenesFinales = [];
            $imagenesDeJSON = [];
            
            // CASO 1: Cliente envió "imagenes" (cambios explícitos)
            // Esto significa: el usuario modificó la lista de imágenes en el cliente
            if (isset($data['imagenes'])) {
                if (is_string($data['imagenes'])) {
                    try {
                        $imagenesDeJSON = json_decode($data['imagenes'], true) ?? [];
                    } catch (\Exception $e) {
                        // Ignorar si no es JSON válido
                    }
                } elseif (is_array($data['imagenes'])) {
                    $imagenesDeJSON = $data['imagenes'];
                }
                
                // Usar las imágenes del cambio como base
                $imagenesFinales = $imagenesDeJSON;
                $debeActualizarImagenes = true;
                
                \Log::info('[PROCESOS-ACTUALIZAR] Imágenes desde CAMBIO del cliente', [
                    'cantidad' => count($imagenesFinales),
                    'imagenes' => $imagenesFinales,
                    'debe_actualizar' => true
                ]);
            }
            // CASO 2: No hay "imagenes" en cambios, pero sí imagenes_existentes
            // Esto significa: el usuario NO cambió imágenes, mantener las que ya están
            elseif (!empty($imagenesExistentes)) {
                $imagenesFinales = $imagenesExistentes;
                $debeActualizarImagenes = false; // NO actualizar si no hubo cambios
                
                \Log::info('[PROCESOS-ACTUALIZAR] Imágenes desde EXISTENTES (no se modificaron)', [
                    'cantidad' => count($imagenesFinales),
                    'imagenes' => $imagenesFinales,
                    'debe_actualizar' => false
                ]);
            }
            
            // CASO 3: Agregar imágenes nuevas del upload (siempre, si existen)
            if (!empty($imagenesNuevasRutas)) {
                $imagenesFinales = array_merge($imagenesFinales, $imagenesNuevasRutas);
                $debeActualizarImagenes = true; // SÍ actualizar si hay imágenes nuevas
                
                \Log::info('[PROCESOS-ACTUALIZAR] Añadiendo imágenes nuevas del upload', [
                    'nuevas' => count($imagenesNuevasRutas),
                    'total_ahora' => count($imagenesFinales),
                    'debe_actualizar' => true
                ]);
            }
            
            // Eliminar duplicados y reindexar
            $imagenesFinales = array_values(array_unique($imagenesFinales));
            
            // IMPORTANTE: Solo actualizar $data['imagenes'] si DEBE actualizarse
            // Si NO cambiaron las imágenes, NO incluir en validated para evitar procesarlas en BD
            if ($debeActualizarImagenes) {
                $data['imagenes'] = $imagenesFinales;
            } else {
                // Remover la clave 'imagenes' de $data para que NO se procese en el validador
                unset($data['imagenes']);
            }
            
            \Log::info('[PROCESOS-ACTUALIZAR] Lista final de imágenes determinada', [
                'del_cambio' => count($imagenesDeJSON ?? []),
                'existentes' => count($imagenesExistentes),
                'nuevas_upload' => count($imagenesNuevasRutas),
                'total_final' => count($imagenesFinales),
                'debe_actualizar' => $debeActualizarImagenes
            ]);

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

            // ============ FIX: DECODIFICAR JSON STRINGS ANTES DE VALIDAR ============
            // Cuando se envía FormData, los JSON strings llegan como strings, no arrays
            // Necesitamos decodificarlos ANTES de la validación
            
            // Decodificar tallas si es string
            if (isset($data['tallas']) && is_string($data['tallas'])) {
                try {
                    $tallasDecodificadas = json_decode($data['tallas'], true);
                    if (is_array($tallasDecodificadas)) {
                        $data['tallas'] = $tallasDecodificadas;
                    }
                } catch (\Exception $e) {
                    // Si no parsea, dejar como está
                }
            }
            
            // Decodificar imagenes si es string
            if (isset($data['imagenes']) && is_string($data['imagenes'])) {
                try {
                    $imagenesDecodificadas = json_decode($data['imagenes'], true);
                    if (is_array($imagenesDecodificadas)) {
                        $data['imagenes'] = $imagenesDecodificadas;
                    }
                } catch (\Exception $e) {
                    // Si no parsea, dejar como está
                }
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
                // Normalizar ubicaciones para evitar JSON doble-encodeado
                $ubicacionesNormalizadas = $this->normalizarUbicaciones($validated['ubicaciones']);
                
                $ubicacionesLimpias = array_filter($ubicacionesNormalizadas);
                $proceso->ubicaciones = json_encode($ubicacionesLimpias);
                \Log::info('[PROCESOS-ACTUALIZAR] Ubicaciones actualizadas:', [
                    'nuevas' => $ubicacionesLimpias,
                    'json_final' => $proceso->ubicaciones
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
            if (isset($validated['imagenes']) && is_array($validated['imagenes'])) {
                \Log::info('[PROCESOS-ACTUALIZAR] Procesando imágenes:', [
                    'raw_imagenes' => $validated['imagenes'],
                    'total_recibidas' => count($validated['imagenes'])
                ]);

                // Obtener imágenes actuales
                $imagenesActuales = \DB::table('pedidos_procesos_imagenes')
                    ->where('proceso_prenda_detalle_id', $proceso->id)
                    ->pluck('ruta_webp')
                    ->toArray();

                // Limpiar y filtrar imágenes: remover nulls, vacíos y strings "null"
                $imagenesNuevas = array_values(array_filter($validated['imagenes'], function($img) {
                    return !empty($img) && $img !== 'null' && is_string($img) && trim($img) !== '';
                }));

                \Log::info('[PROCESOS-ACTUALIZAR] Imágenes después de filtrado:', [
                    'actuales' => $imagenesActuales,
                    'nuevas' => $imagenesNuevas,
                    'total_nuevas' => count($imagenesNuevas)
                ]);

                // Eliminar SOLO las imágenes que ya no están en la nueva lista
                $imagenesAEliminar = array_diff($imagenesActuales, $imagenesNuevas);
                if (!empty($imagenesAEliminar)) {
                    \DB::table('pedidos_procesos_imagenes')
                        ->where('proceso_prenda_detalle_id', $proceso->id)
                        ->whereIn('ruta_webp', $imagenesAEliminar)
                        ->delete();

                    \Log::info('[PROCESOS-ACTUALIZAR] Imágenes eliminadas:', [
                        'cantidad' => count($imagenesAEliminar),
                        'rutas' => $imagenesAEliminar
                    ]);
                }

                // Agregar SOLO las imágenes nuevas que no existen
                $imagenesAAgregar = array_diff($imagenesNuevas, $imagenesActuales);
                if (!empty($imagenesAAgregar)) {
                    $proximoOrden = \DB::table('pedidos_procesos_imagenes')
                        ->where('proceso_prenda_detalle_id', $proceso->id)
                        ->max('orden') ?? 0;

                    foreach ($imagenesAAgregar as $idx => $ruta) {
                        if ($ruta && is_string($ruta) && trim($ruta) !== '') {
                            \DB::table('pedidos_procesos_imagenes')->insert([
                                'proceso_prenda_detalle_id' => $proceso->id,
                                'ruta_original' => null,
                                'ruta_webp' => trim($ruta),
                                'orden' => $proximoOrden + $idx + 1,
                                'es_principal' => 0,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                    }

                    \Log::info('[PROCESOS-ACTUALIZAR] Imágenes agregadas:', [
                        'cantidad' => count($imagenesAAgregar),
                        'rutas' => $imagenesAAgregar
                    ]);
                }

                \Log::info('[PROCESOS-ACTUALIZAR] Resumen imágenes:', [
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

    /**
     * Normalizar ubicaciones para evitar JSON doble-encodeado
     * Convierte elementos JSON-encodados de vuelta a valores simples
     * @private
     */
    private function normalizarUbicaciones(array $ubicaciones): array
    {
        $normalizadas = [];

        foreach ($ubicaciones as $ub) {
            $valor = $this->extraerValorUbicacion($ub);

            // Agregar si no es vacío
            if (!empty($valor) && is_string($valor) && trim($valor) !== '') {
                $normalizadas[] = trim($valor);
            }
        }

        return $normalizadas;
    }

    /**
     * Extraer el valor simple de una ubicación (que puede ser JSON-encodeada)
     * @private
     */
    private function extraerValorUbicacion($ub): ?string
    {
        // Si es string, limpiar comillas escapadas primero
        if (is_string($ub)) {
            // Remover comillas escapadas: "\"valor\"" → "valor"
            $ub = preg_replace('/^["\\\\]*|["\\\\]*$/','', $ub);
            $ub = trim($ub);
        }

        // Si es string que parece JSON
        if (is_string($ub) && (strpos($ub, '[') === 0 || strpos($ub, '{') === 0)) {
            return $this->parseJsonUbicacion($ub);
        }

        // Si es array con 'ubicacion', extraer valor
        if (is_array($ub) && isset($ub['ubicacion'])) {
            return (string)$ub['ubicacion'];
        }

        // Retornar como string
        return is_string($ub) ? $ub : null;
    }

    /**
     * Parsear ubicación que viene como JSON string
     * @private
     */
    private function parseJsonUbicacion(string $jsonString): ?string
    {
        try {
            $parsed = json_decode($jsonString, true);

            // Si parsea a array, extraer primer elemento
            if (is_array($parsed) && !empty($parsed)) {
                return (string)$parsed[0];
            }

            // Si parsea a objeto, extraer propiedad ubicacion
            if (is_array($parsed) && isset($parsed['ubicacion'])) {
                return (string)$parsed['ubicacion'];
            }

            return null;
        } catch (\Exception $e) {
            // Si no parsea, retornar null
            return null;
        }
    }
}

