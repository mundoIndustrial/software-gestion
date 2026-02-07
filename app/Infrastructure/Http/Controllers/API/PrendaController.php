<?php

namespace App\Infrastructure\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Application\Prenda\Services\ObtenerPrendaParaEdicionApplicationService;
use App\Application\Prenda\Services\GuardarPrendaApplicationService;
use App\Domain\Prenda\Repositories\PrendaRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PrendaController extends Controller
{
    public function __construct(
        private ObtenerPrendaParaEdicionApplicationService $obtenerServicio,
        private GuardarPrendaApplicationService $guardarServicio,
        private PrendaRepositoryInterface $repository
    ) {}

    /**
     * GET /api/prendas/{id}
     * Obtiene una prenda completa para edición (con todas sus relaciones)
     */
    public function show(int $id): JsonResponse
    {
        $resultado = $this->obtenerServicio->ejecutar($id);

        return response()->json($resultado, $resultado['exito'] ? 200 : 404);
    }

    /**
     * POST /api/prendas
     * Crea una nueva prenda
     * 
     * Body esperado:
     * {
     *   "nombre_prenda": "string",
     *   "descripcion": "string|null",
     *   "genero": "int",
     *   "tipo_cotizacion": "string",
     *   "telas": [{"id": int, "nombre": string, "codigo": string}],
     *   "procesos": [{"id": int, "nombre": string}],
     *   "variaciones": [{"id": int, "talla": string, "color": string}]
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $datos = $request->all();

        $resultado = $this->guardarServicio->ejecutar($datos);

        return response()->json($resultado, $resultado['exito'] ? 201 : 422);
    }

    /**
     * PUT /api/prendas/{id}
     * Actualiza una prenda existente
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $datos = $request->all();
        $datos['id'] = $id;

        $resultado = $this->guardarServicio->ejecutar($datos);

        return response()->json($resultado, $resultado['exito'] ? 200 : 422);
    }

    /**
     * DELETE /api/prendas/{id}
     * Elimina una prenda
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Verificar que existe
            $prenda = $this->obtenerServicio->ejecutar($id);
            
            if (!$prenda['exito']) {
                return response()->json([
                    'exito' => false,
                    'errores' => ['Prenda no encontrada'],
                ], 404);
            }

            // Eliminar
            $this->repository->eliminar($id);

            return response()->json([
                'exito' => true,
                'mensaje' => 'Prenda eliminada correctamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'exito' => false,
                'errores' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * GET /api/prendas
     * Lista todas las prendas (con paginación opcional)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $prendas = $this->repository->todas();

            return response()->json([
                'exito' => true,
                'datos' => $prendas,
                'total' => count($prendas),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'exito' => false,
                'errores' => [$e->getMessage()],
            ], 500);
        }
    }

    /**
     * GET /api/prendas/search
     * Buscar prendas por nombre
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $nombre = $request->input('nombre', '');

            if (strlen($nombre) < 2) {
                return response()->json([
                    'exito' => true,
                    'datos' => [],
                    'mensaje' => 'Ingrese al menos 2 caracteres',
                ]);
            }

            $prendas = $this->repository->buscarPorNombre($nombre);

            return response()->json([
                'exito' => true,
                'datos' => $prendas,
                'total' => count($prendas),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'exito' => false,
                'errores' => [$e->getMessage()],
            ], 500);
        }
    }
}
