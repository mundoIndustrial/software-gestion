<?php

namespace App\Domain\Ordenes\Services;

use App\Domain\Ordenes\Entities\Orden;
use App\Domain\Ordenes\Entities\Prenda;
use App\Domain\Ordenes\Repositories\OrdenRepositoryInterface;
use App\Domain\Ordenes\ValueObjects\NumeroOrden;
use App\Domain\Ordenes\ValueObjects\EstadoOrden;
use App\Domain\Ordenes\ValueObjects\FormaPago;
use App\Domain\Ordenes\ValueObjects\Area;

/**
 * Application Service: CrearOrdenService
 * 
 * Orquesta la creación de una orden.
 * Coordina entre el domain y el repositorio.
 */
class CrearOrdenService
{
    public function __construct(
        private OrdenRepositoryInterface $ordenRepository
    ) {}

    /**
     * Crear nueva orden
     * 
     * @param array $datos
     *   - numero: int
     *   - cliente: string
     *   - forma_pago: string
     *   - area: string
     *   - prendas: array[]
     * 
     * @return int El número de orden creado
     */
    public function ejecutar(array $datos): int
    {
        // Validar datos
        $this->validar($datos);

        // Crear Value Objects
        $numeroOrden = NumeroOrden::desde($datos['numero']);
        $formaPago = FormaPago::desde($datos['forma_pago']);
        $area = Area::desde($datos['area']);

        // Crear el agregado
        $orden = Orden::crear(
            $numeroOrden,
            $datos['cliente'],
            $formaPago,
            $area
        );

        // Agregar prendas
        if (!empty($datos['prendas'])) {
            foreach ($datos['prendas'] as $prendaData) {
                $prenda = Prenda::crear(
                    $prendaData['nombre_prenda'],
                    $prendaData['cantidad_total'],
                    $prendaData['cantidad_talla'] ?? []
                );

                if (!empty($prendaData['descripcion'])) {
                    $prenda->setDescripcion($prendaData['descripcion']);
                }

                $orden->agregarPrenda($prenda);
            }
        }

        // Persistir
        $this->ordenRepository->save($orden);

        // Los eventos se manejarán en los listeners
        return $numeroOrden->toInt();
    }

    private function validar(array $datos): void
    {
        $campos = ['numero', 'cliente', 'forma_pago', 'area'];

        foreach ($campos as $campo) {
            if (empty($datos[$campo])) {
                throw new \DomainException("Campo requerido: {$campo}");
            }
        }
    }
}
