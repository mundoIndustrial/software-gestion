<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Application\Services\Asesores\PrendaPedidoEditApplicationFacadeService;
use App\Infrastructure\Http\Requests\Asesores\ActualizarProcesoEspecificoRequest;
use App\Infrastructure\Http\Requests\Asesores\EditPrendaFieldsRequest;
use App\Infrastructure\Http\Requests\Asesores\EditPrendaRequest;
use App\Infrastructure\Http\Requests\Asesores\EditPrendaTallasRequest;
use App\Infrastructure\Http\Requests\Asesores\EditVarianteFieldsRequest;
use App\Infrastructure\Http\Requests\Asesores\EditVarianteColoresRequest;
use App\Infrastructure\Http\Requests\Asesores\EditVarianteTelasRequest;
use App\Infrastructure\Http\Requests\Asesores\EditVarianteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * PrendaPedidoEditController - Controlador para editar prendas de pedido
 */
class PrendaPedidoEditController extends Controller
{
    protected PrendaPedidoEditApplicationFacadeService $facade;

    public function __construct(
        PrendaPedidoEditApplicationFacadeService $facade
    ) {
        $this->facade = $facade;
    }

    private function json(mixed $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    private function failure(string $message, int $status, array $extra = []): JsonResponse
    {
        return $this->json(array_merge([
            'success' => false,
            'error' => $message,
        ], $extra), $status);
    }

    /**
     * Editar prenda completa (operacion PATCH)
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
    public function editPrenda(int $id, EditPrendaRequest $request): JsonResponse
    {
        try {
            $resultado = $this->facade->editPrenda($id, $request->all());

            return $this->json($resultado);
        } catch (ValidationException $e) {
            return $this->failure('Validacion fallida', 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->failure('Prenda no encontrada', 404);
        } catch (\Exception $e) {
            return $this->failure($e->getMessage(), 500);
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
     *   "descripcion": "Nueva descripcion",
     *   "cantidad": 120,
     *   "de_bodega": true
     * }
     * 
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function editPrendaFields(int $id, EditPrendaFieldsRequest $request): JsonResponse
    {
        try {
            $resultado = $this->facade->editPrendaFields($id, $request->all());

            return $this->json($resultado);
        } catch (ValidationException $e) {
            return $this->failure('Validacion fallida', 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            return $this->failure($e->getMessage(), 500);
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
    public function editTallas(int $id, EditPrendaTallasRequest $request): JsonResponse
    {
        try {
            $resultado = $this->facade->editTallas($id, $request->input('tallas'));

            return $this->json($resultado);
        } catch (ValidationException $e) {
            return $this->failure('Validacion fallida', 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            return $this->failure($e->getMessage(), 500);
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
    public function editVariante(int $prendaId, int $varianteId, EditVarianteRequest $request): JsonResponse
    {
        try {
            $resultado = $this->facade->editVariante($prendaId, $varianteId, $request->all());

            return $this->json($resultado);
        } catch (\InvalidArgumentException $e) {
            return $this->failure($e->getMessage(), 422);
        } catch (ValidationException $e) {
            return $this->failure('Validacion fallida', 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->failure('Prenda o variante no encontrada', 404);
        } catch (\Exception $e) {
            return $this->failure($e->getMessage(), 500);
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
    public function editVarianteFields(int $prendaId, int $varianteId, EditVarianteFieldsRequest $request): JsonResponse
    {
        try {
            $resultado = $this->facade->editVarianteFields($prendaId, $varianteId, $request->all());

            return $this->json($resultado);
        } catch (\Exception $e) {
            return $this->failure($e->getMessage(), 500);
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
    public function editVarianteColores(int $prendaId, int $varianteId, EditVarianteColoresRequest $request): JsonResponse
    {
        try {
            $resultado = $this->facade->editVarianteColores($prendaId, $varianteId, $request->input('colores'));

            return $this->json($resultado);
        } catch (\Exception $e) {
            return $this->failure($e->getMessage(), 500);
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
    public function editVarianteTelas(int $prendaId, int $varianteId, EditVarianteTelasRequest $request): JsonResponse
    {
        try {
            $resultado = $this->facade->editVarianteTelas($prendaId, $varianteId, $request->input('telas'));

            return $this->json($resultado);
        } catch (\Exception $e) {
            return $this->failure($e->getMessage(), 500);
        }
    }

    /**
     * Obtener estado actual de una prenda (para auditoria)
     * 
     * GET: /api/prendas-pedido/{id}/estado
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function getPrendaState(int $id): JsonResponse
    {
        try {
            $estado = $this->facade->getPrendaState($id);

            return $this->json([
                'success' => true,
                'data' => $estado,
            ]);
        } catch (\Exception $e) {
            return $this->failure($e->getMessage(), 500);
        }
    }

    /**
     * Obtener estado actual de una variante (para auditoria)
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
            $estado = $this->facade->getVarianteState($prendaId, $varianteId);

            return $this->json([
                'success' => true,
                'data' => $estado,
            ]);
        } catch (\Exception $e) {
            return $this->failure($e->getMessage(), 500);
        }
    }

    public function actualizarProcesoEspecifico(int $prendaId, int $procesoId, ActualizarProcesoEspecificoRequest $request): JsonResponse
    {
        try {
            // FormData enviado como POST + _method=PATCH: usar all() con fallback a $_POST
            $inputData = $request->all();
            if (empty($inputData) && !empty($_POST)) {
                $inputData = $_POST;
            }

            // Recolectar archivos de imagenes nuevas
            $archivos = [];
            if ($request->hasFile('imagenes_nuevas')) {
                $files    = $request->file('imagenes_nuevas');
                $archivos = is_array($files) ? $files : [$files];
            }

            $resultado = $this->facade->actualizarProcesoEspecifico($prendaId, $procesoId, $inputData, $archivos);

            return $this->json([
                'success' => true,
                'message' => 'Proceso actualizado correctamente',
                'data'    => $resultado,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->failure('Proceso no encontrado en la prenda especificada', 404);

        } catch (ValidationException $e) {
            return $this->failure('Validacion fallida', 422, [
                'errors' => $e->errors(),
            ]);

        } catch (\Exception $e) {
            return $this->failure('Error al actualizar el proceso: ' . $e->getMessage(), 500);
        }
    }
}
