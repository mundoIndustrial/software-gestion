<?php

namespace App\Domain\Operario\Repositories;

use App\Domain\Operario\Entities\Operario;
use App\Domain\Operario\ValueObjects\TipoOperario;
use App\Domain\Operario\ValueObjects\AreaOperario;
use Illuminate\Support\Collection;

/**
 * Interface: OperarioRepository
 * 
 * Define contrato para persistencia de operarios
 */
interface OperarioRepository
{
    /**
     * Obtener operario por ID
     */
    public function obtenerPorId(int $id): ?Operario;

    /**
     * Obtener operarios por tipo
     */
    public function obtenerPorTipo(TipoOperario $tipo): Collection;

    /**
     * Obtener operarios por área
     */
    public function obtenerPorArea(AreaOperario $area): Collection;

    /**
     * Obtener todos los operarios activos
     */
    public function obtenerActivos(): Collection;

    /**
     * Obtener operarios por tipo y área
     */
    public function obtenerPorTipoYArea(TipoOperario $tipo, AreaOperario $area): Collection;

    /**
     * Guardar operario
     */
    public function guardar(Operario $operario): void;

    /**
     * Obtener pedidos asignados a un operario
     */
    public function obtenerPedidosAsignados(int $operarioId): Collection;
}
