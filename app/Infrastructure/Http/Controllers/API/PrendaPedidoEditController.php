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
}
