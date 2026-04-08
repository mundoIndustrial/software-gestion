<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\UseCases\ObtenerDatosParaCrearPedidoUseCaseContract;

use App\Application\DTOs\ObtenerDatosParaCrearPedidoOutputDTO;
use App\Models\Cliente;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerDatosParaCrearPedidoUseCase
 *  RESPONSABILIDAD ÚNICA: Obtener datos básicos para crear un nuevo pedido
 * Datos que obtiene:
 * - Pedidos recientes del usuario
 * - Clientes disponibles
 * Nota: Otros datos (tallas, técnicas, formas de pago) se cargarán
 * dinámicamente desde endpoints específicos en el frontend
 */
class ObtenerDatosParaCrearPedidoUseCase implements ObtenerDatosParaCrearPedidoUseCaseContract
{
    /**
     * Ejecutar use case
     * 
     * @param int $usuarioId ID del asesor logged
     * @return ObtenerDatosParaCrearPedidoOutputDTO
     */
    public function ejecutar(int $usuarioId): ObtenerDatosParaCrearPedidoOutputDTO
    {
        Log::info('[ObtenerDatosParaCrearPedidoUseCase] Iniciado', [
            'usuario_id' => $usuarioId,
        ]);

        try {
            // 1. Obtener pedidos recientes del usuario
            $pedidos = PedidoProduccion::where('asesor_id', $usuarioId)
                ->latest()
                ->limit(10)
                ->get();

            // 2. Obtener clientes (todos)
            $clientes = Cliente::orderBy('nombre')->get();

            Log::info('[ObtenerDatosParaCrearPedidoUseCase] Completado', [
                'usuario_id' => $usuarioId,
                'pedidos' => $pedidos->count(),
                'clientes' => $clientes->count(),
            ]);

            // 3. Construir y retornar DTO con datos básicos
            return new ObtenerDatosParaCrearPedidoOutputDTO(
                pedidos: $pedidos,
                clientes: $clientes,
                tallas: collect([]),
                tecnicas: collect([]),
                formasPago: collect([]),
            );

        } catch (\Exception $e) {
            Log::error('[ObtenerDatosParaCrearPedidoUseCase] Error', [
                'usuario_id' => $usuarioId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {ObtenerDatosParaCrearPedidoUseCase}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}





