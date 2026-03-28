<?php

namespace App\Application\Pedidos\Despacho\UseCases;

use App\Models\PedidoProduccion;
use App\Application\Pedidos\Despacho\Services\DespachoGeneradorService;
use App\Application\Pedidos\Despacho\DTOs\FilaDespachoDTO;
use App\Domain\Pedidos\UseCases\ObtenerFilasDespachoUseCaseContract;
use App\Domain\Pedidos\Exceptions\PedidoNoEncontrado;
use Illuminate\Support\Collection;

/**
 * ObtenerFilasDespachoUseCase
 * Use Case (Application Service) para obtener las filas de despacho
 * de un pedido
 * Coordina entre:
 * - Domain Service (DespachoGeneradorService)
 * - Modelos (PedidoProduccion)
 * - DTOs (FilaDespachoDTO)
 */
class ObtenerFilasDespachoUseCase implements ObtenerFilasDespachoUseCaseContract
{
    public function __construct(
        private DespachoGeneradorService $despachoGenerador,
    ) {}

    /**
     * Ejecutar: Obtener todas las filas (prendas + EPP)
     * @param int|string $pedidoId
     * @return Collection<FilaDespachoDTO>
     * @throws \Exception
     */
    public function obtenerTodas(int|string $pedidoId): Collection
    {
        $pedido = $this->obtenerPedido($pedidoId);
        return $this->despachoGenerador->generarFilasDespacho($pedido);
    }

    /**
     * Ejecutar: Obtener solo prendas
     * @param int|string $pedidoId
     * @return Collection<FilaDespachoDTO>
     * @throws \Exception
     */
    public function obtenerPrendas(int|string $pedidoId): Collection
    {
        $pedido = $this->obtenerPedido($pedidoId);
        return $this->despachoGenerador->generarPrendas($pedido);
    }

    /**
     * Ejecutar: Obtener solo EPP
     * @param int|string $pedidoId
     * @return Collection<FilaDespachoDTO>
     * @throws \Exception
     */
    public function obtenerEpp(int|string $pedidoId): Collection
    {
        $pedido = $this->obtenerPedido($pedidoId);
        return $this->despachoGenerador->generarEpp($pedido);
    }

    /**
     * Obtener pedido con sus relaciones
     */
    private function obtenerPedido(int|string $pedidoId): PedidoProduccion
    {
        $pedido = PedidoProduccion::with([
            'prendas.prendaPedidoTallas',
            'epps.epp',
            'epps.imagenes',
        ])->find($pedidoId);

        if (!$pedido) {
            throw is_int($pedidoId)
                ? PedidoNoEncontrado::conId($pedidoId)
                : new PedidoNoEncontrado("Pedido con ID {$pedidoId} no encontrado");
        }

        return $pedido;
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {ObtenerFilasDespachoUseCase}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}





