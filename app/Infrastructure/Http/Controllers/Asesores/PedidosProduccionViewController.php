<?php

namespace App\Infrastructure\Http\Controllers\Asesores;


use Illuminate\Support\Facades\Auth;
use App\Application\Services\Asesores\PedidosProduccionViewApplicationFacadeService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * PedidosProduccionViewController
 * Controlador para servir VISTAS HTML de pedidos (NO creacion)
 * El controlador de CREACION es: CrearPedidoEditableController 
 * Responsabilidad: Renderizar vistas y obtener datos para templates
 * NOTA: Los metodos de creacion fueron ELIMINADOS completamente
 * La creacion de pedidos se realiza Unicamente a traves de:
 * POST /asesores/pedidos/crear (CrearPedidoEditableController)
 */
class PedidosProduccionViewController
{
    public function __construct(
        private readonly PedidosProduccionViewApplicationFacadeService $facade
    ) {
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
     * Obtener datos de cotizacion (AJAX)
     */
    public function obtenerDatosCotizacion(int|string $cotizacionId): JsonResponse
    {
        try {
            $usuarioId = (int) Auth::id();
            $resultado = $this->facade->resolverDatosCotizacion((int) $cotizacionId, $usuarioId);
            return $this->json($resultado['payload'], $resultado['status']);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerDatosCotizacion', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error al obtener datos de cotizacion', 500, [
                'prendas' => [],
                'logo' => null,
            ]);
        }
    }

    /**
     * Mostrar plantilla de pedido
     */
    public function plantilla(int|string $id): View
    {
        return view('asesores.pedidos.show', [
            'pedido_id' => $id
        ]);
    }

    /**
     * Obtener datos del pedido para edicion modal
     * GET /asesores/pedidos-produccion/{id}/datos-edicion
     */
    public function obtenerDatosEdicion(int|string $pedidoId): JsonResponse
    {
        try {
            $resultado = $this->facade->obtenerDatosEdicion((int) $pedidoId);
            \Log::info('[DATOS-EDICION] Datos cargados', [
                'pedido_id' => $pedidoId,
                'prendas' => $resultado['meta']['prendas_count'] ?? 0,
            ]);
            return $this->json($resultado['payload'], $resultado['status']);
        } catch (\Exception $e) {
            \Log::error('[DATOS-EDICION] Error:', ['error' => $e->getMessage()]);
            return $this->failure('Error al cargar datos del pedido', 500);
        }
    }

    /**
     * Obtener datos de UNA prenda especifica para edicion
     * Usa Unicamente las 7 tablas transaccionales del pedido
     * GET /asesores/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos
     */
    public function obtenerDatosUnaPrenda(int|string $pedidoId, int|string $prendaId): JsonResponse
    {
        try {
            $resultado = $this->facade->resolverDatosUnaPrenda((int) $pedidoId, (int) $prendaId);
            return $this->json($resultado['payload'], $resultado['status']);
        } catch (\Exception $e) {
            \Log::error('[PRENDA-DATOS] Error obteniendo datos de prenda', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error al obtener datos de la prenda', 500);
        }
    }
    /**
     * Obtener prenda completa desde cotización (para crear/editar pedido)
     * GET /asesores/pedidos-produccion/obtener-prenda-completa/{cotizacionId}/{prendaId}
     */
    public function obtenerPrendaCompleta(int|string $cotizacionId, int|string $prendaId): JsonResponse
    {
        try {
            $resultado = $this->facade->resolverPrendaCompleta((int) $cotizacionId, (int) $prendaId);
            return $this->json($resultado['payload'], $resultado['status']);
        } catch (\Exception $e) {
            \Log::error('[OBTENER-PRENDA-COMPLETA] Error obteniendo prenda completa', [
                'cotizacion_id' => $cotizacionId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);
            return $this->failure('Error al obtener prenda completa', 500);
        }
    }
}

