<?php

namespace App\Application\Pedidos\UseCases\RegistroOrden;

use App\Models\PedidoProduccion;
use App\Application\Pedidos\UseCases\ObtenerDetalleCompletoUseCase;
use App\Exceptions\GetRecibosDatosException;

/**
 * GetRecibosDatosUseCase
 * UseCase: Obtener datos completos de recibos para un pedido
 * Capa: Application
 * Responsabilidad: Resolver pedido y obtener datos completos enriquecidos
 * Nota: Las excepciones son manejadas por el Handler que renderiza
 * respuestas JSON automáticamente. El UseCase solo lanza excepciones.
 */
class GetRecibosDatosUseCase
{
    private ObtenerDetalleCompletoUseCase $obtenerDetalleCompletoUseCase;

    public function __construct(ObtenerDetalleCompletoUseCase $obtenerDetalleCompletoUseCase)
    {
        $this->obtenerDetalleCompletoUseCase = $obtenerDetalleCompletoUseCase;
    }
    /**
     * Ejecutar obtención de datos de recibos
     * @param string $pedido ID o número de pedido
     * @param bool $esInsumos Indicar si viene del módulo de insumos
     * @return array Datos completos del pedido
     * @throws GetRecibosDatosException
     */
    public function execute(string $pedido, bool $esInsumos = false): array
    {
        // Validar entrada
        if (empty($pedido)) {
            throw GetRecibosDatosException::pedidoInvalido();
        }

        try {
            // Resolver el pedido explícitamente
            $pedidoModel = $this->resolvePedido($pedido);
            $pedidoId = $pedidoModel->id;

            \Log::info('[GetRecibosDatosUseCase] Obteniendo datos', [
                'numero_pedido' => $pedidoModel->numero_pedido,
                'pedido_id' => $pedidoId,
                'es_insumos' => $esInsumos
            ]);

            // Obtener datos del UseCase directamente
            $filtrarProcesosPendientes = !$esInsumos;
            $response = $this->obtenerDetalleCompletoUseCase->ejecutar($pedidoId, $filtrarProcesosPendientes);
            $datos = $response->toArray();

            \Log::info('[GetRecibosDatosUseCase] Datos obtenidos exitosamente', [
                'numero_pedido' => $pedidoModel->numero_pedido,
                'pedido_id' => $pedidoId,
                'cliente' => $datos['cliente'] ?? 'N/A',
                'total_prendas' => isset($datos['prendas']) ? count($datos['prendas']) : 0
            ]);

            return $datos;

        } catch (\Exception $e) {
            // Si es excepción personalizada, re-lanzar directamente
            if ($e instanceof GetRecibosDatosException) {
                throw $e;
            }

            \Log::error('[GetRecibosDatosUseCase] Error: ' . $e->getMessage(), [
                'pedido' => $pedido,
                'trace' => $e->getTraceAsString()
            ]);

            throw GetRecibosDatosException::errorConsulta($e);
        }
    }

    /**
     * Resolver pedido de forma explícita
     * Estrategia clara:
     * 1. Si es 'sin-numero', buscar pedidos sin número asignado
     * 2. Si es numérico, buscar por ID
     * 3. Si es alfanumérico, buscar por número de pedido
     * NO hay fallback automático - ser explícito con el identificador
     * @param string $pedido
     * @return PedidoProduccion
     * @throws GetRecibosDatosException
     */
    private function resolvePedido(string $pedido): PedidoProduccion
    {
        // Caso especial: 'sin-numero'
        if ($pedido === 'sin-numero') {
            $pedidoModel = PedidoProduccion::whereNull('numero_pedido')
                ->orWhere('numero_pedido', '')
                ->orderBy('id', 'desc')
                ->first();

            if (!$pedidoModel) {
                throw GetRecibosDatosException::pedidoNoEncontrado($pedido);
            }

            return $pedidoModel;
        }

        // Si es numérico, buscar por ID (no por numero_pedido)
        if (is_numeric($pedido)) {
            $pedidoModel = PedidoProduccion::find((int) $pedido);
            if (!$pedidoModel) {
                throw GetRecibosDatosException::pedidoNoEncontrado($pedido);
            }
            return $pedidoModel;
        }

        // Si es alfanumérico, buscar por número de pedido
        $pedidoModel = PedidoProduccion::where('numero_pedido', $pedido)->first();
        if (!$pedidoModel) {
            throw GetRecibosDatosException::pedidoNoEncontrado($pedido);
        }

        return $pedidoModel;
    }
}

