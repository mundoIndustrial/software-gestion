<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\DTOs\Edit\EditPrendaPedidoDTO;
use App\DTOs\Edit\EditPrendaVariantePedidoDTO;
use App\Infrastructure\Services\Edit\PrendaPedidoEditService;
use App\Infrastructure\Services\Edit\PrendaVariantePedidoEditService;
use App\Infrastructure\Services\Procesos\ProcesoActualizarService;
use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;
use App\Models\PedidoAnexoHistorial;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * PrendaPedidoEditController - Controlador de ediciÃ³n segura de prendas
 * 
 * RESPONSABILIDAD:
 * â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
 * Manejar endpoints PATCH para ediciÃ³n segura de prendas persistidas.
 * 
 * SEPARACIÃ“N CLARA:
 * â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
 * POST /api/prendas-pedido               â†’ Crear (construcciÃ³n desde DOM)
 * PATCH /api/prendas-pedido/{id}/editar â†’ Editar (parcial seguro)
 * 
 * MÃ‰TODOS:
 * â”€â”€â”€â”€â”€â”€â”€â”€
 * editPrenda()        - Editar prenda completa (PATCH)
 * editPrendaFields()  - Editar solo campos simples
 * editTallas()        - Editar solo tallas (MERGE)
 * editVariante()      - Editar variante
 * editVarianteFields()- Editar solo variante campos simples
 * 
 * GARANTÃAS:
 * â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
 * âœ“ No reconstruye desde DOM
 * âœ“ Solo actualiza lo enviado
 * âœ“ Valida restricciones de negocio
 * âœ“ MERGE en relaciones (no borrado)
 * âœ“ Separado de lÃ³gica de creaciÃ³n
 */
class PrendaPedidoEditController extends Controller
{
    protected PrendaPedidoEditService $prendaEditService;
    protected PrendaVariantePedidoEditService $varianteEditService;
    protected ProcesoActualizarService $procesoActualizarService;

    public function __construct(
        PrendaPedidoEditService $prendaEditService,
        PrendaVariantePedidoEditService $varianteEditService,
        ProcesoActualizarService $procesoActualizarService
    ) {
        $this->prendaEditService = $prendaEditService;
        $this->varianteEditService = $varianteEditService;
        $this->procesoActualizarService = $procesoActualizarService;
    }

    /**
     * Editar prenda completa (operaciÃ³n PATCH)
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

            // Capturar campos antes del cambio para el diff
            $camposAntes = $prenda->only(['nombre_prenda', 'cantidad', 'descripcion', 'de_bodega']);

            // Ejecutar ediciÃ³n
            $resultado = $this->prendaEditService->edit($prenda, $dto);

            // Construir diff de campos bÃ¡sicos enviados
            $cambiosDetalle = [];
            foreach (['nombre_prenda', 'cantidad', 'descripcion', 'de_bodega'] as $campo) {
                if (array_key_exists($campo, $dto->toArray() ?? [])) {
                    $vAntes = (string)($camposAntes[$campo] ?? '');
                    $vDespues = (string)($dto->$campo ?? $camposAntes[$campo] ?? '');
                    if ($vAntes !== $vDespues) {
                        $cambiosDetalle[] = $campo . ': "' . $vAntes . '" â†’ "' . $vDespues . '"';
                    }
                }
            }

            // Registrar en historial: prenda editada en pedido existente
            PedidoAnexoHistorial::registrarPrendaEditada(
                $prenda->pedido_produccion_id,
                $id,
                $prenda->nombre_prenda ?? 'PRENDA',
                'campos generales',
                $cambiosDetalle ? implode(' | ', $cambiosDetalle) : null
            );

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
     *   "descripcion": "Nueva descripciÃ³n",
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

            // Capturar campos antes del cambio
            $camposAntes = $prenda->only(['nombre_prenda', 'cantidad', 'descripcion', 'de_bodega']);

            $resultado = $this->prendaEditService->updateBasic($prenda, $request->all());

            // Construir diff
            $cambiosDetalle = [];
            foreach (['nombre_prenda', 'cantidad', 'descripcion', 'de_bodega'] as $campo) {
                if ($request->has($campo)) {
                    $vAntes = (string)($camposAntes[$campo] ?? '');
                    $vDespues = (string)$request->input($campo);
                    if ($vAntes !== $vDespues) {
                        $cambiosDetalle[] = $campo . ': "' . $vAntes . '" â†’ "' . $vDespues . '"';
                    }
                }
            }

            // Registrar en historial: prenda editada en pedido existente
            PedidoAnexoHistorial::registrarPrendaEditada(
                $prenda->pedido_produccion_id,
                $id,
                $prenda->nombre_prenda ?? 'PRENDA',
                'campos bÃ¡sicos',
                $cambiosDetalle ? implode(' | ', $cambiosDetalle) : null
            );

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

            // Capturar tallas antes del cambio
            $tallasAntes = $prenda->tallas()->get()->keyBy(
                fn($t) => strtoupper($t->genero ?? '') . '_' . strtoupper($t->talla ?? '')
            );

            $resultado = $this->prendaEditService->updateTallas($prenda, $request->input('tallas'));

            // Construir diff de tallas
            $cambiosDetalle = [];
            foreach ($request->input('tallas', []) as $t) {
                $clave = strtoupper($t['genero'] ?? '') . '_' . strtoupper($t['talla'] ?? '');
                $antes = (int)($tallasAntes[$clave]->cantidad ?? 0);
                $despues = (int)($t['cantidad'] ?? 0);
                if ($antes !== $despues) {
                    $cambiosDetalle[] = ($t['genero'] ?? '?') . ' ' . ($t['talla'] ?? '?') . ': ' . $antes . 'â†’' . $despues;
                }
            }

            // Registrar en historial: prenda editada (tallas) en pedido existente
            PedidoAnexoHistorial::registrarPrendaEditada(
                $prenda->pedido_produccion_id,
                $id,
                $prenda->nombre_prenda ?? 'PRENDA',
                'tallas',
                $cambiosDetalle ? implode(' | ', $cambiosDetalle) : null
            );

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

            // Capturar campos de variante antes del cambio
            $varianteAntes = $variante->only(['tipo_manga_id', 'tipo_broche_boton_id', 'tiene_bolsillos', 'manga_obs', 'broche_boton_obs', 'bolsillos_obs']);

            // Ejecutar ediciÃ³n
            $resultado = $this->varianteEditService->edit($variante, $dto);

            // Construir diff de variante
            $cambiosDetalle = [];
            $data = $request->all();
            foreach (['tipo_manga_id', 'tipo_broche_boton_id', 'tiene_bolsillos', 'manga_obs', 'broche_boton_obs', 'bolsillos_obs'] as $campo) {
                if (array_key_exists($campo, $data)) {
                    $vAntes = (string)($varianteAntes[$campo] ?? '');
                    $vDespues = (string)($data[$campo] ?? '');
                    if ($vAntes !== $vDespues) {
                        $cambiosDetalle[] = $campo . ': "' . $vAntes . '" â†’ "' . $vDespues . '"';
                    }
                }
            }

            // Registrar en historial: variante de prenda editada en pedido existente
            PedidoAnexoHistorial::registrarPrendaEditada(
                $prenda->pedido_produccion_id,
                $prendaId,
                $prenda->nombre_prenda ?? 'PRENDA',
                'variante',
                $cambiosDetalle ? implode(' | ', $cambiosDetalle) : null
            );

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

            // Capturar campos de variante antes del cambio
            $varianteAntes = $variante->only(['tipo_manga_id', 'tipo_broche_boton_id', 'tiene_bolsillos', 'manga_obs', 'broche_boton_obs', 'bolsillos_obs']);

            $resultado = $this->varianteEditService->updateBasic($variante, $request->all());

            // Construir diff
            $cambiosDetalle = [];
            foreach (['tipo_manga_id', 'tipo_broche_boton_id', 'tiene_bolsillos', 'manga_obs', 'broche_boton_obs', 'bolsillos_obs'] as $campo) {
                if ($request->has($campo)) {
                    $vAntes = (string)($varianteAntes[$campo] ?? '');
                    $vDespues = (string)$request->input($campo);
                    if ($vAntes !== $vDespues) {
                        $cambiosDetalle[] = $campo . ': "' . $vAntes . '" â†’ "' . $vDespues . '"';
                    }
                }
            }

            // Registrar en historial: campos de variante editados en pedido existente
            PedidoAnexoHistorial::registrarPrendaEditada(
                $prenda->pedido_produccion_id,
                $prendaId,
                $prenda->nombre_prenda ?? 'PRENDA',
                'campos de variante',
                $cambiosDetalle ? implode(' | ', $cambiosDetalle) : null
            );

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

            // Capturar colores antes del cambio
            $coloresAntes = $prenda->coloresTelas()
                ->whereNotNull('color_id')
                ->with('color')
                ->get()
                ->map(fn($ct) => $ct->color->nombre ?? '#' . $ct->color_id)
                ->unique()->values()->toArray();

            $resultado = $this->varianteEditService->updateColores($variante, $request->input('colores'));

            // Capturar colores despues del cambio (desde request)
            $colorIdsNuevos = collect($request->input('colores', []))
                ->pluck('color_id')->filter()->unique()->values()->toArray();
            $coloresDespues = \App\Models\ColorPrenda::whereIn('id', $colorIdsNuevos)->pluck('nombre')->toArray();
            $detalleColores = 'Antes: ' . (implode(', ', $coloresAntes) ?: 'ninguno')
                . ' â†’ despues: ' . (implode(', ', $coloresDespues) ?: 'ninguno');

            // Registrar en historial: colores de variante editados en pedido existente
            PedidoAnexoHistorial::registrarPrendaEditada(
                $prenda->pedido_produccion_id,
                $prendaId,
                $prenda->nombre_prenda ?? 'PRENDA',
                'colores',
                $detalleColores
            );

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

            // Capturar telas antes del cambio
            $telasAntes = $prenda->coloresTelas()
                ->whereNotNull('tela_id')
                ->with('tela')
                ->get()
                ->map(fn($ct) => $ct->tela->nombre ?? '#' . $ct->tela_id)
                ->unique()->values()->toArray();

            $resultado = $this->varianteEditService->updateTelas($variante, $request->input('telas'));

            // Capturar telas despues del cambio (desde request)
            $telaIdsNuevos = collect($request->input('telas', []))
                ->pluck('tela_id')->filter()->unique()->values()->toArray();
            $telasDespues = \App\Models\TelaPrenda::whereIn('id', $telaIdsNuevos)->pluck('nombre')->toArray();
            $detalleTelas = 'Antes: ' . (implode(', ', $telasAntes) ?: 'ninguna')
                . ' â†’ despues: ' . (implode(', ', $telasDespues) ?: 'ninguna');

            // Registrar en historial: telas de variante editadas en pedido existente
            PedidoAnexoHistorial::registrarPrendaEditada(
                $prenda->pedido_produccion_id,
                $prendaId,
                $prenda->nombre_prenda ?? 'PRENDA',
                'telas',
                $detalleTelas
            );

            return response()->json($resultado);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener estado actual de una prenda (para auditorÃ­a)
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
     * Obtener estado actual de una variante (para auditorÃ­a)
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
            $prenda  = PrendaPedido::findOrFail($prendaId);
            $proceso = $prenda->procesos()->findOrFail($procesoId);

            // FormData enviado como POST + _method=PATCH: usar all() con fallback a $_POST
            $inputData = $request->all();
            if (empty($inputData) && !empty($_POST)) {
                $inputData = $_POST;
            }

            // Recolectar archivos de imÃ¡genes nuevas
            $archivos = [];
            if ($request->hasFile('imagenes_nuevas')) {
                $files    = $request->file('imagenes_nuevas');
                $archivos = is_array($files) ? $files : [$files];
            }

            $resultado = $this->procesoActualizarService->actualizar($proceso, $inputData, $archivos);

            return response()->json([
                'success' => true,
                'message' => 'Proceso actualizado correctamente',
                'data'    => $resultado,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'error'   => 'Proceso no encontrado en la prenda especificada',
            ], 404);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Error al actualizar el proceso: ' . $e->getMessage(),
            ], 500);
        }
    }
}
