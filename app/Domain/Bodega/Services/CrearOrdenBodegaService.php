<?php

namespace App\Domain\Bodega\Services;

use App\Domain\Bodega\Entities\OrdenBodega;
use App\Domain\Bodega\Entities\PrendaBodega;
use App\Domain\Bodega\ValueObjects\NumeroPedidoBodega;
use App\Domain\Bodega\ValueObjects\FormaPagoBodega;
use App\Domain\Bodega\Repositories\OrdenBodegaRepositoryInterface;
use Carbon\Carbon;

/**
 * Application Service: Crear Orden en Bodega
 */
final class CrearOrdenBodegaService
{
    public function __construct(
        private OrdenBodegaRepositoryInterface $repository
    ) {}

    public function ejecutar(array $datos): OrdenBodega
    {
        // Validar que el número de pedido no exista
        if ($this->repository->existeNumero($datos['pedido'])) {
            throw new \InvalidArgumentException(
                "El número de pedido {$datos['pedido']} ya existe"
            );
        }

        // Crear Value Objects
        $numeroPedido = NumeroPedidoBodega::crear($datos['pedido']);
        $fechaCreacion = Carbon::parse($datos['fecha_creacion']);

        // Crear Aggregate Root
        $orden = OrdenBodega::crear($numeroPedido, $datos['cliente'], $fechaCreacion);

        // Agregar prendas
        if (!empty($datos['prendas']) && is_array($datos['prendas'])) {
            foreach ($datos['prendas'] as $prendaData) {
                $prenda = PrendaBodega::crear(
                    $prendaData['prenda'],
                    $prendaData['descripcion'] ?? '',
                    $prendaData['tallas'] ?? []
                );
                $orden->agregarPrenda($prenda);
            }
        }

        // Establecer encargado si viene
        if (!empty($datos['encargado'])) {
            $orden->establecerEncargado($datos['encargado']);
        }

        // Establecer forma de pago si viene
        if (!empty($datos['forma_pago'])) {
            $orden->establecerFormaPago(FormaPagoBodega::desde($datos['forma_pago']));
        }

        // Persistir
        $this->repository->guardar($orden);

        return $orden;
    }
}
