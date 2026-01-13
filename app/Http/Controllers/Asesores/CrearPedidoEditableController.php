<?php

namespace App\Http\Controllers\Asesores;

use App\Application\DTOs\ItemPedidoDTO;
use App\Domain\PedidoProduccion\Services\GestionItemsPedidoService;
use App\Domain\PedidoProduccion\Services\TransformadorCotizacionService;
use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrearPedidoEditableController extends Controller
{
    public function __construct(
        private GestionItemsPedidoService $gestionItems,
        private TransformadorCotizacionService $transformador,
    ) {}

    public function index(?string $tipoInicial = null): View
    {
        $cotizaciones = Cotizacion::with(['cliente', 'asesor', 'prendasCotizaciones'])
            ->where('estado', 'aprobada')
            ->get();

        $cotizacionesTransformadas = $this->transformador->transformarCotizacionesParaFrontend($cotizaciones);

        return view('asesores.pedidos.crear-desde-cotizacion-editable', [
            'tipoInicial' => $tipoInicial,
            'cotizaciones' => $cotizaciones,
            'cotizacionesData' => $cotizacionesTransformadas,
        ]);
    }

    public function agregarItem(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tipo' => 'required|in:cotizacion,nuevo',
                'prenda' => 'required|array',
                'origen' => 'required|in:bodega,confeccion',
                'procesos' => 'array',
                'es_proceso' => 'boolean',
                'cotizacion_id' => 'nullable|integer|exists:cotizaciones,id',
                'tallas' => 'nullable|array',
                'variaciones' => 'nullable|array',
                'imagenes' => 'nullable|array',
            ]);

            $itemDTO = ItemPedidoDTO::fromArray($validated);
            $this->gestionItems->agregarItem($itemDTO);

            return response()->json([
                'success' => true,
                'message' => 'Ítem agregado correctamente',
                'items' => $this->gestionItems->obtenerItemsArray(),
                'count' => $this->gestionItems->contar(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar ítem: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function eliminarItem(Request $request): JsonResponse
    {
        try {
            $index = $request->integer('index');
            
            if ($index < 0 || $index >= $this->gestionItems->contar()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Índice de ítem inválido',
                ], 422);
            }

            $this->gestionItems->eliminarItem($index);

            return response()->json([
                'success' => true,
                'message' => 'Ítem eliminado correctamente',
                'items' => $this->gestionItems->obtenerItemsArray(),
                'count' => $this->gestionItems->contar(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar ítem: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function obtenerItems(): JsonResponse
    {
        return response()->json([
            'items' => $this->gestionItems->obtenerItemsArray(),
            'count' => $this->gestionItems->contar(),
            'tieneItems' => $this->gestionItems->tieneItems(),
        ]);
    }

    public function validarPedido(): JsonResponse
    {
        $errores = $this->gestionItems->validar();

        if (!empty($errores)) {
            return response()->json([
                'valid' => false,
                'errores' => $errores,
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Pedido válido',
        ]);
    }

    public function crearPedido(Request $request): JsonResponse
    {
        try {
            $errores = $this->gestionItems->validar();
            
            if (!empty($errores)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido tiene errores',
                    'errores' => $errores,
                ], 422);
            }

            $validated = $request->validate([
                'cliente' => 'required|string',
                'asesora' => 'required|string',
                'forma_de_pago' => 'nullable|string',
            ]);

            $items = $this->gestionItems->obtenerItemsArray();

            // Aquí iría la lógica para crear el pedido en BD
            // $pedido = PedidoProduccion::create([...]);

            $this->gestionItems->limpiar();

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado correctamente',
                'pedido_id' => 1, // Reemplazar con ID real
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear pedido: ' . $e->getMessage(),
            ], 422);
        }
    }
}
